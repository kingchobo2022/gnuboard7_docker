<?php

namespace App\Console\Commands;

use App\Contracts\ApiDoc\ApiDocSampleSeeder;
use App\Models\Module;
use App\Models\Plugin;
use App\Models\Template;
use App\Models\User;
use App\Support\ApiDoc\ApiDocSampleService;
use App\Support\ApiDoc\ApiDocScaffolder;
use App\Support\ApiDoc\ApiEndpointProbe;
use App\Support\ApiDoc\ApiRouteInventory;
use App\Support\ApiDoc\ColumnCommentResolver;
use App\Support\ApiDoc\FormRequestIntrospector;
use App\Support\ApiDoc\ResponseSchemaInferrer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * API 레퍼런스 문서 생성 커맨드
 *
 * 등록된 API 라우트를 전수 수집하고, GET 엔드포인트는 실제 HTTP 호출로 응답을
 * 실측하여 요청 파라미터·응답 필드를 담은 마크다운 레퍼런스 문서를 생성합니다.
 *
 * @generated 블록만 갱신하며 사람이 작성한 서술은 보존합니다.
 */
class ApiDocgenCommand extends Command
{
    /**
     * @var string 커맨드 시그니처
     */
    protected $signature = 'api:docgen
        {--scope=core : 범위 (core, module:vendor-id, plugin:vendor-id, all)}
        {--base-url= : 실측 기준 URL (미지정 시 .env APP_URL)}
        {--user= : 실측 토큰 발급 대상 사용자 ID}
        {--seed : 실측 전 완전 샘플 데이터를 시드 (개발 환경 전용)}
        {--check : 생성하지 않고 누락/미실측만 리포트}
        {--dry-run : 생성 대상만 출력}';

    /**
     * @var string 커맨드 설명
     */
    protected $description = 'API 레퍼런스 문서를 실측 기반으로 생성/갱신합니다';

    /**
     * 커맨드를 실행합니다.
     *
     * @param  ApiRouteInventory  $inventory  라우트 인벤토리
     * @param  FormRequestIntrospector  $introspector  FormRequest 분석기
     * @param  ResponseSchemaInferrer  $inferrer  응답 스키마 추론기
     * @param  ApiDocScaffolder  $scaffolder  스캐폴딩 생성기
     * @param  ColumnCommentResolver  $commentResolver  컬럼 주석 해석기
     * @return int 종료 코드
     */
    public function handle(
        ApiRouteInventory $inventory,
        FormRequestIntrospector $introspector,
        ResponseSchemaInferrer $inferrer,
        ApiDocScaffolder $scaffolder,
        ColumnCommentResolver $commentResolver
    ): int {
        $scope = (string) $this->option('scope');
        $routes = $inventory->collect($scope);

        if ($routes === []) {
            $this->warn("범위 '{$scope}' 에 해당하는 API 라우트가 없습니다.");

            return self::SUCCESS;
        }

        $this->info(count($routes)."개 라우트 수집 (scope={$scope})");

        // 도메인 파일 단위로 그룹핑
        $grouped = [];
        foreach ($routes as $route) {
            $file = $this->targetFile($route);
            $grouped[$file][] = $route;
        }

        if ($this->option('dry-run')) {
            foreach ($grouped as $file => $items) {
                $this->line(sprintf('  %s (%d endpoints)', $file, count($items)));
            }

            return self::SUCCESS;
        }

        // --seed: 도메인별 완전 샘플을 시드해 상세 GET 실측 시 null 응답을 최소화
        // (인증보다 먼저 수행해 완전 샘플 사용자로 토큰을 발급 → /me 응답도 채워짐)
        $sampleMap = [];
        if ($this->option('seed')) {
            if (app()->environment('production')) {
                $this->error('--seed 는 개발 환경 전용입니다 (production 차단).');

                return self::FAILURE;
            }

            // 코어 샘플 우선 시드 — 확장 샘플의 소유자/actor 로 쓰이는 완전 사용자를 확보한다.
            $sampleMap = (new ApiDocSampleService)->seed();
            $this->info('완전 샘플 시드: '.count($sampleMap).'개 도메인 (코어)');

            // 확장 소유 라우트가 있으면 그 확장의 규약 시더를 발견해 도메인 샘플을 병합한다.
            foreach ($this->discoverExtensionSeeders($routes) as $label => $seeder) {
                $extMap = $seeder->seed();
                $sampleMap = array_merge($sampleMap, $extMap);
                $this->info('완전 샘플 시드: '.count($extMap)."개 도메인 ({$label})");
            }
        }

        $probe = new ApiEndpointProbe($this->option('base-url') ?: null);
        // 인증 사용자: --user 명시 > 완전 샘플 사용자(/me 응답 충실) > 첫 사용자
        $authUserId = $this->option('user')
            ? (int) $this->option('user')
            : $this->sampleUserId($sampleMap);
        $probed = $probe->authenticate($authUserId);

        if (! $probed) {
            $this->warn('실측 토큰 발급 실패 — 실측 없이 정적 추출만 진행합니다.');
        } else {
            $this->info('실측 기준 URL: '.$probe->baseUrl());
        }

        $stats = ['files' => 0, 'endpoints' => 0, 'probed' => 0, 'skipped' => 0];
        $checkFindings = [];

        foreach ($grouped as $file => $items) {
            $sections = [];
            $sectionKeys = [];

            foreach ($items as $route) {
                $request = $introspector->introspect($route['controller'], $route['controller_method']);
                [$schema, $probeMeta] = $this->probeEndpoint($probe, $inferrer, $route, $probed, $sampleMap);

                if ($probeMeta['skipped_reason'] === null) {
                    $stats['probed']++;
                } else {
                    $stats['skipped']++;
                    $checkFindings[] = "{$route['method']} {$route['uri']} — {$probeMeta['skipped_reason']}";
                }

                $commentMap = $this->columnComments($route, $commentResolver, $sampleMap);
                $sections[] = $scaffolder->endpointSection($route, $request, $schema, $probeMeta, $commentMap);
                $sectionKeys[] = $route['name'] ?: $route['uri'];
                $stats['endpoints']++;
            }

            if ($this->option('check')) {
                if (! File::exists($file)) {
                    $checkFindings[] = "문서 파일 없음: {$file}";
                }

                continue;
            }

            $header = $this->documentHeader($file, $items[0]);
            $existing = File::exists($file) ? File::get($file) : null;
            $content = $scaffolder->mergeDocument($existing, $header, $sections, $sectionKeys);

            File::ensureDirectoryExists(dirname($file));
            File::put($file, $content);
            $stats['files']++;
        }

        $probe->cleanup();

        if ($this->option('check')) {
            if ($checkFindings !== []) {
                $this->warn('실측 제외/문서 누락 '.count($checkFindings).'건:');
                foreach (array_slice($checkFindings, 0, 50) as $f) {
                    $this->line('  - '.$f);
                }

                return self::FAILURE;
            }

            $this->info('drift 없음.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(sprintf(
            '완료: 파일 %d개, 엔드포인트 %d개 (실측 %d, 제외 %d)',
            $stats['files'],
            $stats['endpoints'],
            $stats['probed'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }

    /**
     * 수집된 라우트의 소유 확장에서 규약 위치의 샘플 시더를 발견합니다.
     *
     * 확장 컨트롤러 FQCN(`Modules\Vendor\Ext\Http\Controllers\...`)에서 확장 베이스
     * 네임스페이스를 추출하고, `{Base}\Support\ApiDoc\ApiDocSampleService` 규약 클래스가
     * 존재하며 ApiDocSampleSeeder 를 구현하면 인스턴스를 반환합니다. 각 확장은 1회만 처리합니다.
     *
     * @param  array<int, array<string, mixed>>  $routes  수집된 라우트 목록
     * @return array<string, ApiDocSampleSeeder> 확장 라벨(key) => 시더 인스턴스
     */
    private function discoverExtensionSeeders(array $routes): array
    {
        $seeders = [];
        $seen = [];

        foreach ($routes as $route) {
            $owner = $route['owner'];

            if ($owner['type'] === 'core' || $owner['id'] === null) {
                continue;
            }

            $label = $owner['key'];

            if (isset($seen[$label])) {
                continue;
            }
            $seen[$label] = true;

            $base = $this->extensionBaseNamespace($route['controller'] ?? null);

            if ($base === null) {
                continue;
            }

            $class = $base.'\\Support\\ApiDoc\\ApiDocSampleService';

            if (class_exists($class) && is_subclass_of($class, ApiDocSampleSeeder::class)) {
                $seeders[$label] = app($class);
            }
        }

        return $seeders;
    }

    /**
     * 확장 컨트롤러 FQCN 에서 확장 베이스 네임스페이스를 추출합니다.
     *
     * 두 컨트롤러 배치 규약을 모두 지원한다:
     *   - `\Http\Controllers\` (대다수 확장): `Modules\Sirsoft\Page\Http\Controllers\Admin\PageController`
     *     → `Modules\Sirsoft\Page`
     *   - `\Controllers\` (일부 플러그인): `Plugins\Sirsoft\PayKginicis\Controllers\PaymentSignatureController`
     *     → `Plugins\Sirsoft\PayKginicis`
     *
     * `\Http\Controllers\` 를 우선 판정하고, 없으면 `\Controllers\` 세그먼트 앞까지를 베이스로 본다.
     *
     * @param  string|null  $controller  컨트롤러 FQCN (클로저면 null)
     * @return string|null 확장 베이스 네임스페이스 (추출 불가 시 null)
     */
    private function extensionBaseNamespace(?string $controller): ?string
    {
        if ($controller === null) {
            return null;
        }

        if (Str::contains($controller, '\\Http\\Controllers\\')) {
            return Str::before($controller, '\\Http\\Controllers\\');
        }

        if (Str::contains($controller, '\\Controllers\\')) {
            return Str::before($controller, '\\Controllers\\');
        }

        return null;
    }

    /**
     * 엔드포인트를 실측하고 응답 스키마를 추론합니다.
     *
     * @param  ApiEndpointProbe  $probe  실측 프로브
     * @param  ResponseSchemaInferrer  $inferrer  스키마 추론기
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  bool  $probed  실측 가능 여부
     * @param  array<string, array{model: class-string, key: string, value: string}>  $sampleMap  도메인별 대표 샘플 맵
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>} 스키마와 실측 메타
     */
    private function probeEndpoint(ApiEndpointProbe $probe, ResponseSchemaInferrer $inferrer, array $route, bool $probed, array $sampleMap = []): array
    {
        if (! $probed) {
            return [null, ['status' => null, 'skipped_reason' => 'no-token']];
        }

        $uri = $this->resolvePathParams($route, $sampleMap);
        $result = $probe->probe($route['method'], $uri);

        if (! $result['ok'] || $result['body'] === null) {
            return [null, ['status' => $result['status'], 'skipped_reason' => $result['skipped_reason'] ?? ('http-'.$result['status'])]];
        }

        return [$inferrer->infer($result['body']), ['status' => $result['status'], 'skipped_reason' => null]];
    }

    /**
     * URI 의 path 파라미터를 바인딩된 모델의 실제 레코드로 치환합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  array<string, array{model: class-string, key: string, value: string}>  $sampleMap  도메인별 대표 샘플 맵
     * @return string 치환된 URI (치환 실패 시 원본 유지 → 프로브가 실측 제외)
     */
    private function resolvePathParams(array $route, array $sampleMap = []): string
    {
        $uri = $route['uri'];
        $domain = $route['domain_group'] ?? null;

        // path 파라미터가 있으면: 완전 샘플 대표 레코드 우선, 없으면 첫 레코드 키로 치환
        foreach ($route['path_params'] as $param) {
            $modelClass = $route['path_bindings'][$param] ?? null;

            // 라우트-모델 바인딩이 없는 확장 패턴(예: show(int $id))은 도메인 샘플 맵의
            // 대표 모델로 폴백한다. 단, 파라미터명이 도메인의 단수 리소스명과 일치할 때만
            // (pages/{page} → Page). {slug}/{hash}/{versionId} 등 route key 가 다른
            // 문자열/보조 파라미터에는 폴백하지 않아 잘못된 치환(404)을 피한다.
            if (! $modelClass && $domain !== null && isset($sampleMap[$domain]) && $this->paramMatchesDomain($param, $domain)) {
                $modelClass = $sampleMap[$domain]['model'];
            }

            if (! $modelClass) {
                // route-model binding 도 도메인 폴백도 없는 문자열 path 파라미터
                // (예: board 의 boards/{slug}/posts/{id}). 도메인 대표 샘플이 명시한
                // path_params 맵(param 명 => 실제 값)에 해당 param 이 있으면 그 값으로 치환한다.
                // slug 라우팅을 쓰는 확장이 route key(id) 와 무관한 slug/id 조합을
                // 실측할 수 있도록 하는 일반 경로다. (파라미터명 정확 일치만 허용 → 오치환 방지)
                $explicit = $domain !== null ? ($sampleMap[$domain]['path_params'][$param] ?? null) : null;

                if ($explicit !== null) {
                    $uri = str_replace('{'.$param.'}', (string) $explicit, $uri);
                }

                continue;
            }

            // 시드된 완전 샘플 중 같은 모델이 있으면 그 route key 를 우선 사용
            $value = $this->sampleKeyForModel($modelClass, $sampleMap) ?? $this->firstRouteKey($modelClass);

            if ($value !== null) {
                $uri = str_replace('{'.$param.'}', (string) $value, $uri);
            }
        }

        // 목록 GET 은 여러 행을 받아 필드별 non-null 대표 샘플을 확보한다
        // (per_page=1 이면 첫 행이 우연히 비어 "항상 null" 처럼 보임).
        if ($route['method'] === 'GET' && ! Str::contains($uri, '{')) {
            return $uri.(Str::contains($uri, '?') ? '&' : '?').'per_page=25';
        }

        return $uri;
    }

    /**
     * path 파라미터명이 도메인의 단수 리소스명과 일치하는지 판정합니다.
     *
     * 도메인 그룹은 복수형(pages)이고 라우트 파라미터는 단수(page)이므로,
     * 파라미터명 == 도메인명 또는 파라미터명 == 도메인 단수형일 때만 매칭으로 본다.
     * (page ↔ pages). {slug}/{hash}/{versionId} 는 어느 쪽과도 일치하지 않는다.
     *
     * @param  string  $param  path 파라미터명
     * @param  string  $domain  도메인 그룹명 (복수형 가능)
     * @return bool 도메인 리소스 파라미터 여부
     */
    private function paramMatchesDomain(string $param, string $domain): bool
    {
        return $param === $domain
            || $param === Str::singular($domain)
            || Str::plural($param) === $domain;
    }

    /**
     * 라우트의 주 모델 컬럼 주석 맵을 반환합니다 (응답 필드 설명 기본값).
     *
     * path 파라미터에 바인딩된 모델을 우선하고, 없으면 도메인 샘플 맵의 대표
     * 모델을 주 모델로 보고 그 테이블 주석을 사용합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  ColumnCommentResolver  $resolver  컬럼 주석 해석기
     * @param  array<string, array{model: class-string, key: string, value: string}>  $sampleMap  도메인별 대표 샘플 맵
     * @return array<string, string> 컬럼명 => 주석
     */
    private function columnComments(array $route, ColumnCommentResolver $resolver, array $sampleMap = []): array
    {
        $bindings = $route['path_bindings'] ?? [];

        // 마지막 path 바인딩 모델(가장 구체적인 리소스)을 주 모델로 사용
        $modelClass = null;
        foreach ($bindings as $class) {
            $modelClass = $class;
        }

        // path 바인딩이 없으면(목록/단건 me 등) 도메인 샘플 맵 → 정적 힌트 순으로 유추
        if (! $modelClass) {
            $domain = $route['domain_group'] ?? null;
            $modelClass = ($domain && isset($sampleMap[$domain]) ? $sampleMap[$domain]['model'] : null)
                ?? $this->domainModelHint($domain);
        }

        return $modelClass ? $resolver->forModel($modelClass) : [];
    }

    /**
     * 샘플 맵으로 유추되지 않는 도메인의 주 모델을 정적 힌트로 매핑합니다.
     *
     * me/auth/profile/password 등은 path 바인딩도 없고 sampleMap 키(users)와도
     * 도메인명이 달라 자동 유추가 안 되지만, 모두 User 필드를 반환합니다.
     *
     * @param  string|null  $domain  도메인 그룹명
     * @return class-string|null 주 모델 FQCN (없으면 null)
     */
    private function domainModelHint(?string $domain): ?string
    {
        if ($domain === null) {
            return null;
        }

        $hints = [
            'me' => User::class,
            'auth' => User::class,
            'profile' => User::class,
            'password' => User::class,
            'modules' => Module::class,
            'plugins' => Plugin::class,
            'templates' => Template::class,
        ];

        return $hints[$domain] ?? null;
    }

    /**
     * 완전 샘플 맵에서 샘플 사용자의 DB id 를 조회합니다 (토큰 발급 대상).
     *
     * @param  array<string, array{model: class-string, key: string, value: string}>  $sampleMap  대표 샘플 맵
     * @return int|null 샘플 사용자 id (없으면 null → 첫 사용자로 fallback)
     */
    private function sampleUserId(array $sampleMap): ?int
    {
        $users = $sampleMap['users'] ?? null;

        if (! $users) {
            return null;
        }

        return User::query()
            ->where($users['key'], $users['value'])
            ->value('id');
    }

    /**
     * 시드된 완전 샘플 맵에서 해당 모델의 대표 route key 를 찾습니다.
     *
     * @param  class-string  $modelClass  모델 FQCN
     * @param  array<string, array{model: class-string, key: string, value: string}>  $sampleMap  대표 샘플 맵
     * @return string|null 대표 route key 값 (없으면 null)
     */
    private function sampleKeyForModel(string $modelClass, array $sampleMap): ?string
    {
        foreach ($sampleMap as $sample) {
            if ($sample['model'] === $modelClass) {
                return $sample['value'];
            }
        }

        return null;
    }

    /**
     * 모델의 첫 레코드 route key 값을 반환합니다.
     *
     * @param  class-string  $modelClass  모델 FQCN
     * @return mixed route key 값 (레코드 없으면 null)
     */
    private function firstRouteKey(string $modelClass): mixed
    {
        try {
            /** @var Model $model */
            $model = new $modelClass;
            $keyName = $model->getRouteKeyName();

            $record = $modelClass::query()->orderBy($model->getKeyName())->first();

            return $record?->getAttribute($keyName);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 라우트가 저장될 문서 파일 경로를 산출합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @return string 절대 파일 경로
     */
    private function targetFile(array $route): string
    {
        $owner = $route['owner'];
        $domain = $route['domain_group'];

        if ($owner['type'] === 'core') {
            return base_path("docs/backend/api/{$domain}.md");
        }

        $base = $owner['type'] === 'module' ? 'modules' : 'plugins';

        return base_path("{$base}/_bundled/{$owner['id']}/docs/api/{$domain}.md");
    }

    /**
     * 문서 헤더(제목 + TL;DR)를 생성합니다.
     *
     * @param  string  $file  문서 파일 경로
     * @param  array<string, mixed>  $firstRoute  대표 라우트
     * @return string 문서 헤더
     */
    private function documentHeader(string $file, array $firstRoute): string
    {
        $domain = Str::headline(pathinfo($file, PATHINFO_FILENAME));
        $owner = $firstRoute['owner'];
        $ownerLabel = $owner['type'] === 'core' ? '코어' : "{$owner['type']} `{$owner['id']}`";

        return <<<MD
        # {$domain} API 레퍼런스

        > **소유**: {$ownerLabel} · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

        ---

        ## TL;DR (5초 요약)

        ```text
        1. 이 문서는 실제 API 호출로 실측한 {$domain} 엔드포인트 레퍼런스입니다
        2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
        3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
        4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
        5. 설명(TODO) 칸은 사람이 채웁니다
        ```

        ---


        MD;
    }
}
