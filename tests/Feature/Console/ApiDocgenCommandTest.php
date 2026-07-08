<?php

namespace Tests\Feature\Console;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Support\ApiDoc\ApiDocScaffolder;
use App\Support\ApiDoc\ApiRouteInventory;
use App\Support\ApiDoc\FormRequestIntrospector;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * api:docgen 커맨드 통합 동작 Feature 테스트.
 *
 * 계획서(#447) 단계 5 가 명시한 커맨드 5대 동작을 검증한다:
 * (a) 라우트 전수 수집, (b) 확장별 파일 라우팅, (c) FormRequest rules 추출,
 * (d) idempotent 재생성(사람 서술 보존), (e) --check drift 검출.
 *
 * 라이브 실측(HTTP 프로브)에 의존하지 않는 결정론적 경로(--dry-run·정적 추출·
 * 스캐폴더 병합)로 커맨드 표면을 검증한다. 실측 자체는 ApiDocPipelineTest·
 * ApiDocSampleServiceTest 가 별도 커버한다.
 */
class ApiDocgenCommandTest extends TestCase
{
    /**
     * (a) 코어 스코프 실행 시 라우트를 전수 수집해 개수를 보고합니다.
     */
    #[Test]
    public function 코어_스코프는_라우트를_전수_수집한다(): void
    {
        // 인벤토리가 코어 라우트를 수집하는지 직접 확인 (커맨드가 이 결과를 소비).
        $routes = app(ApiRouteInventory::class)->collect('core');

        $this->assertNotEmpty($routes, '코어 API 라우트가 수집되어야 한다.');
        // 대표 코어 라우트(사용자 목록)가 포함되는지 (전수 수집 실증).
        $uris = array_column($routes, 'uri');
        $this->assertContains('/api/admin/users', $uris);

        // --dry-run 실행이 수집 개수를 보고하고 생성 없이 종료한다.
        $this->artisan('api:docgen', ['--scope' => 'core', '--dry-run' => true])
            ->expectsOutputToContain('라우트 수집 (scope=core)')
            ->assertSuccessful();
    }

    /**
     * (b) 확장 소유 라우트는 확장 디렉토리 파일로 라우팅됩니다.
     */
    #[Test]
    public function 확장_라우트는_확장_디렉토리_파일로_라우팅된다(): void
    {
        $inventory = app(ApiRouteInventory::class);

        // 코어 라우트의 소유는 core, 출력 파일은 docs/backend/api 하위여야 한다.
        $coreRoutes = $inventory->collect('core');
        foreach ($coreRoutes as $route) {
            $this->assertSame('core', $route['owner']['type']);
        }

        // 번들 확장 라우트가 있으면 그 소유 type 이 module/plugin 이고 id 가 있어야 한다
        // (targetFile 이 이 owner 로 modules|plugins/_bundled/{id}/docs/api 경로를 만든다).
        $all = $inventory->collect('all');
        $extRoutes = array_filter($all, fn ($r) => $r['owner']['type'] !== 'core');

        if ($extRoutes === []) {
            // 이 환경에 활성 확장이 없으면(라우트 0) 스킵 — core 라우팅 검증만으로 충분.
            $this->markTestSkipped('활성 확장 라우트 없음 — 확장 라우팅 검증 스킵.');
        }

        foreach ($extRoutes as $route) {
            $this->assertContains($route['owner']['type'], ['module', 'plugin']);
            $this->assertNotEmpty($route['owner']['id']);
        }
    }

    /**
     * (c) 컨트롤러 메서드의 FormRequest rules() 를 정적 리플렉션으로 추출합니다.
     */
    #[Test]
    public function form_request_rules를_정적_추출한다(): void
    {
        $introspector = app(FormRequestIntrospector::class);

        // 로그인 컨트롤러는 email/password rules 를 가진 FormRequest 를 받는다.
        $result = $introspector->introspect(
            AuthController::class,
            'login'
        );

        $names = array_column($result['params'], 'name');
        $this->assertContains('email', $names, 'FormRequest rules 의 email 파라미터가 추출되어야 한다.');
        $this->assertContains('password', $names);
    }

    /**
     * (d) 재생성해도 사람이 채운 서술은 보존되고 추출 블록만 갱신됩니다(멱등).
     */
    #[Test]
    public function 재생성은_사람_서술을_보존하는_멱등_동작이다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET', 'uri' => '/api/me', 'name' => 'api.me.show',
            'controller' => 'C', 'controller_method' => 'show', 'permission' => null,
            'middleware' => ['auth:sanctum'], 'path_params' => [],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = ['envelope' => ['success', 'data'], 'shape' => 'object', 'fields' => [['name' => 'id', 'type' => 'integer', 'sample' => '1']], 'pagination' => false];
        $probeMeta = ['status' => 200, 'skipped_reason' => null, 'base_url' => 'https://api.example.com', 'resolved_uri' => '/api/me', 'body' => ['success' => true, 'data' => ['id' => 1], 'message' => null, 'error' => null]];

        $section = $scaffolder->endpointSection($route, $request, $schema, $probeMeta);
        $header = "# Me\n";
        $first = $scaffolder->mergeDocument(null, $header, [$section], ['api.me.show']);

        // @generated 블록 밖 `**설명**` 슬롯의 TODO 스텁을 사람 서술로 채운다
        // (extractHumanProse 는 기본 TODO 스텁이 남아 있으면 보존 대상 없음으로 판정).
        $human = str_replace(
            '**설명** <!-- TODO: 이 엔드포인트의 용도·주의사항·예시 시나리오를 작성하세요 -->',
            '**설명** 이 엔드포인트는 현재 로그인 사용자를 반환합니다. (사람 서술)',
            $first
        );

        // 재생성 시 사람 서술은 보존되고 생성 블록만 갱신된다.
        $second = $scaffolder->mergeDocument($human, $header, [$section], ['api.me.show']);

        $this->assertStringContainsString('이 엔드포인트는 현재 로그인 사용자를 반환합니다. (사람 서술)', $second);
        $this->assertStringNotContainsString('TODO: 이 엔드포인트의 용도', $second);
        // 다시 병합해도 사람 서술 중복 없이 멱등.
        $third = $scaffolder->mergeDocument($second, $header, [$section], ['api.me.show']);
        $this->assertSame(
            substr_count($second, '(사람 서술)'),
            substr_count($third, '(사람 서술)'),
            '재병합 시 사람 서술이 중복되지 않아야 한다.'
        );
    }

    /**
     * (e) --check 는 대응 문서가 없으면 drift 로 실패 코드를 반환합니다.
     */
    #[Test]
    public function check는_문서_누락을_drift로_검출한다(): void
    {
        // 존재하지 않는 확장 스코프(문서 파일 부재 보장)로 --check 실행.
        // 라우트가 0이면 커맨드는 성공으로 조기 종료하므로, 라우트가 있는 core 로 검증한다.
        // core 문서는 이미 존재하므로(35파일), 임시로 한 파일을 백업/삭제해 누락을 유발한다.
        $target = base_path('docs/backend/api/me.md');
        $backup = null;
        if (File::exists($target)) {
            $backup = File::get($target);
            File::delete($target);
        }

        try {
            // me.md 가 없으면 --check 는 "문서 파일 없음" drift 로 FAILURE 를 반환한다.
            $this->artisan('api:docgen', ['--scope' => 'core', '--check' => true])
                ->assertFailed();
        } finally {
            if ($backup !== null) {
                File::put($target, $backup);
            }
        }
    }
}
