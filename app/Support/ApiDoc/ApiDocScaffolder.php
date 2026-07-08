<?php

namespace App\Support\ApiDoc;

use Illuminate\Support\Str;

/**
 * API 문서 스캐폴딩 생성기
 *
 * 라우트 메타데이터 + 요청 파라미터 + 실측 응답 스키마를 표준 마크다운 포맷으로
 * 조립합니다. @generated 블록 경계로 기존 문서의 사람 서술을 보존(idempotent)합니다.
 */
class ApiDocScaffolder
{
    /**
     * @var string 생성 블록 시작 마커 접두
     */
    private const GEN_START = '<!-- @generated:start:';

    /**
     * @var string 생성 블록 종료 마커
     */
    private const GEN_END = '<!-- @generated:end -->';

    /**
     * @param  ResourceFieldDescriber  $fieldDescriber  accessor/computed 필드 설명기
     * @param  ParameterDescriber  $paramDescriber  공통 요청 파라미터 설명기
     */
    public function __construct(
        private readonly ResourceFieldDescriber $fieldDescriber = new ResourceFieldDescriber,
        private readonly ParameterDescriber $paramDescriber = new ParameterDescriber
    ) {}

    /**
     * 단일 엔드포인트의 마크다운 섹션을 생성합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  array<string, mixed>  $request  FormRequest 분석 결과
     * @param  array<string, mixed>|null  $schema  실측 응답 스키마 (null=실측 안 됨)
     * @param  array<string, mixed>  $probeMeta  실측 메타 (status, skipped_reason)
     * @param  array<string, string>  $commentMap  컬럼명 => 주석 (필드 설명 기본값)
     * @return string 마크다운 섹션
     */
    public function endpointSection(array $route, array $request, ?array $schema, array $probeMeta, array $commentMap = []): string
    {
        $name = $route['name'] ?: '(unnamed)';
        $heading = "### {$route['method']} {$route['uri']}";
        $genKey = $name;

        $lines = [];
        $lines[] = $heading;
        $lines[] = self::GEN_START.$genKey.' -->';
        $lines[] = "- **라우트명**: `{$name}`";
        if ($route['controller']) {
            $lines[] = "- **컨트롤러**: `{$route['controller']}@{$route['controller_method']}`";
        }
        $lines[] = '- **인증/권한**: '.$this->authLine($route);
        $lines[] = '';

        $lines[] = '**요청 파라미터**';
        $lines[] = '';
        $lines[] = $this->requestParamTable($route, $request);
        if (! empty($request['hook_filters'])) {
            $hooks = implode('`, `', $request['hook_filters']);
            $lines[] = '';
            $lines[] = "> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`{$hooks}`).";
        }
        $lines[] = '';

        $lines[] = '**응답 필드** (`data` 내부)';
        $lines[] = '';
        $lines[] = $this->responseFieldTable($schema, $probeMeta, $commentMap);
        $lines[] = '';

        $lines[] = '**에러 응답**';
        $lines[] = '';
        $lines[] = $this->errorTable($route, $request);
        $lines[] = '';
        $lines[] = self::GEN_END;
        $lines[] = '';
        $lines[] = '**설명** <!-- TODO: 이 엔드포인트의 용도·주의사항·예시 시나리오를 작성하세요 -->';
        $lines[] = '';

        return implode("\n", $lines)."\n";
    }

    /**
     * 인증/권한 라인을 구성합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @return string 인증/권한 설명
     */
    private function authLine(array $route): string
    {
        $mw = $route['middleware'] ?? [];
        $parts = [];

        // optional.sanctum(회원/비회원 모두 접근 — Bearer 토큰 있으면 인증, 없으면 guest)은
        // auth:sanctum(인증 필수)과 계약이 다르므로 별도 표기한다. 'sanctum' 부분일치가
        // optional.sanctum 까지 auth:sanctum 으로 오표기하던 회귀를 막는다.
        if ($this->hasMiddleware($mw, 'optional.sanctum')) {
            $parts[] = '`optional.sanctum` (선택적 인증: 회원/비회원 모두 접근)';
        } elseif ($this->hasMiddleware($mw, 'sanctum')) {
            $parts[] = '`auth:sanctum`';
        }
        if ($this->hasMiddleware($mw, 'AdminMiddleware')) {
            $parts[] = '`admin`';
        }
        if ($route['permission']) {
            $parts[] = "`permission:{$route['permission']}`";
        }

        return $parts === [] ? '공개 (인증 불필요)' : implode(' + ', $parts);
    }

    /**
     * 미들웨어 목록에 특정 토큰이 포함되는지 확인합니다.
     *
     * @param  array<int, string>  $middleware  미들웨어 목록
     * @param  string  $needle  검색 토큰
     * @return bool 포함 여부
     */
    private function hasMiddleware(array $middleware, string $needle): bool
    {
        foreach ($middleware as $mw) {
            if (Str::contains($mw, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 요청 파라미터 표를 생성합니다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  array<string, mixed>  $request  FormRequest 분석 결과
     * @return string 마크다운 표
     */
    private function requestParamTable(array $route, array $request): string
    {
        $rows = [];

        foreach ($route['path_params'] as $pathParam) {
            $desc = $this->paramDescriber->describe($pathParam, 'path', 'string');
            $descCell = $desc !== null ? $this->escapeCell($desc) : '<!-- TODO: 용도 -->';
            $rows[] = "| {$pathParam} | path | string | 예 | — | {$descCell} |";
        }

        $location = in_array($route['method'], ['GET', 'DELETE'], true) ? 'query' : 'body';

        foreach ($request['params'] as $p) {
            $required = $p['required'] ? '예' : '아니오';
            $allowed = $p['allowed'] !== '' ? $p['allowed'] : '—';
            $desc = $this->paramDescriber->describe($p['name'], $location, $p['type']);
            $descCell = $desc !== null ? $this->escapeCell($desc) : '<!-- TODO: 용도 -->';
            $rows[] = "| {$p['name']} | {$location} | {$p['type']} | {$required} | {$allowed} | {$descCell} |";
        }

        if ($rows === []) {
            return '_요청 파라미터 없음._';
        }

        return "| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |\n| --- | --- | --- | --- | --- | --- |\n".implode("\n", $rows);
    }

    /**
     * 응답 필드 표를 생성합니다.
     *
     * @param  array<string, mixed>|null  $schema  실측 응답 스키마
     * @param  array<string, mixed>  $probeMeta  실측 메타
     * @param  array<string, string>  $commentMap  컬럼명 => 주석 (필드 설명 기본값)
     * @return string 마크다운 표 또는 실측 제외 사유
     */
    private function responseFieldTable(?array $schema, array $probeMeta, array $commentMap = []): string
    {
        if ($schema === null) {
            $reason = $probeMeta['skipped_reason'] ?? 'not-probed';

            return "<!-- 실측 제외: {$reason} — 응답 필드는 사람이 작성하세요. -->";
        }

        $note = '';
        if ($schema['shape'] === 'collection') {
            $note = '_목록 응답: `data.data[]` 배열 항목의 필드'.($schema['pagination'] ? ' + `data.pagination`' : '').'._';
        } elseif ($schema['shape'] === 'object') {
            $note = '_단건 응답: `data` 객체의 필드._';
        }

        if ($schema['fields'] === []) {
            return $note."\n\n<!-- 실측 응답에 필드 없음(빈 목록 등) — 데이터가 있는 상태로 재실측하거나 사람이 작성. -->";
        }

        $rows = [];
        foreach ($schema['fields'] as $f) {
            // 필드 설명 우선순위:
            //   1) 리소스 계약 사전 (accessor/computed — status_label, is_owner, *_at 등)
            //   2) 컬럼 주석 (한국어 comment — 테이블 실제 컬럼)
            //   3) TODO (사람 보강)
            // 계약 사전이 앞서는 이유: created_at 은 어느 테이블이든 "생성 일시" 이고,
            // status_label 은 컬럼이 아니라 Enum label() 산물이라 주석이 없기 때문.
            $desc = $this->fieldDescriber->describe($f['name'], $f['type'] ?? '')
                ?? ($commentMap[$f['name']] ?? null);
            $descCell = $desc !== null ? $this->escapeCell($desc) : '<!-- TODO: 설명 -->';
            $rows[] = "| {$f['name']} | {$f['type']} | `{$f['sample']}` | {$descCell} |";
        }

        $table = "| 필드 | 타입 | 실측 예시값 | 용도/설명 |\n| --- | --- | --- | --- |\n".implode("\n", $rows);

        return $note !== '' ? $note."\n\n".$table : $table;
    }

    /**
     * 에러 응답 표를 생성합니다.
     *
     * 라우트 메타에서 대표 에러 상태코드와 발생 조건을 자동 추론합니다.
     *   - 401: 인증 필수(`auth:sanctum`) 미들웨어. `optional.sanctum`(선택 인증)은 제외.
     *   - 403: `admin` 미들웨어 또는 `permission:` 요구 → 권한 부족 시.
     *   - 422: FormRequest 검증 규칙 존재 → 검증 실패 시.
     *   - 404: path 파라미터 존재 → 대상 리소스 미발견 시.
     *
     * 자동 추론은 대표 상태코드의 초안이며, 도메인 특이 에러(409 충돌·429 제한 등)는
     * `@generated` 블록 밖 사람 서술에서 보강한다.
     *
     * @param  array<string, mixed>  $route  라우트 메타데이터
     * @param  array<string, mixed>  $request  FormRequest 분석 결과
     * @return string 마크다운 표
     */
    private function errorTable(array $route, array $request): string
    {
        $mw = $route['middleware'] ?? [];
        $rows = [];

        // 401: 인증 필수. optional.sanctum(선택 인증)은 미인증도 허용하므로 제외.
        if (! $this->hasMiddleware($mw, 'optional.sanctum') && $this->hasMiddleware($mw, 'sanctum')) {
            $rows[] = '| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |';
        }

        // 403: admin 게이트 또는 permission 요구 → 권한 부족.
        if ($this->hasMiddleware($mw, 'AdminMiddleware') || ! empty($route['permission'])) {
            $cond = ! empty($route['permission'])
                ? "요구 권한(`{$route['permission']}`)이 없는 경우"
                : '관리자 권한이 없는 경우';
            $rows[] = "| 403 | Forbidden | {$cond} |";
        }

        // 422: FormRequest 검증 규칙 존재 → 검증 실패. (훅 주입 규칙 포함 가능)
        $hasValidation = ! empty($request['params']) || ! empty($request['hook_filters']);
        if ($hasValidation) {
            $rows[] = '| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |';
        }

        // 404: path 파라미터 존재 → 대상 리소스 미발견.
        if (! empty($route['path_params'])) {
            $rows[] = '| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |';
        }

        if ($rows === []) {
            return '_대표 에러 없음 (공개 조회). <!-- TODO: 도메인 특이 에러가 있으면 보강 -->_';
        }

        return "| 상태코드 | 의미 | 발생 조건 |\n| --- | --- | --- |\n".implode("\n", $rows);
    }

    /**
     * 마크다운 표 셀 안에서 안전하도록 파이프/개행을 이스케이프합니다.
     *
     * @param  string  $text  원본 텍스트
     * @return string 이스케이프된 텍스트
     */
    private function escapeCell(string $text): string
    {
        return str_replace(['|', "\n", "\r"], ['\\|', ' ', ''], $text);
    }

    /**
     * 기존 문서에 새 생성 블록을 병합합니다. 사람 서술은 보존합니다.
     *
     * @param  string|null  $existing  기존 문서 내용 (null=신규)
     * @param  string  $header  문서 헤더 (제목 + TL;DR 등)
     * @param  array<int, string>  $sections  엔드포인트 섹션 목록 (라우트명 순)
     * @param  array<int, string>  $sectionKeys  각 섹션의 라우트명 키
     * @return string 병합된 문서 내용
     */
    public function mergeDocument(?string $existing, string $header, array $sections, array $sectionKeys): string
    {
        if ($existing === null) {
            return $header."\n".implode("\n", $sections);
        }

        $merged = $header."\n";

        foreach ($sections as $i => $section) {
            $key = $sectionKeys[$i];
            $preserved = $this->extractHumanProse($existing, $key);
            $merged .= $this->applyPreservedProse($section, $preserved)."\n";
        }

        return $merged;
    }

    /**
     * 기존 문서에서 특정 엔드포인트의 사람 서술(생성 블록 밖)을 추출합니다.
     *
     * @param  string  $existing  기존 문서
     * @param  string  $key  라우트명 키
     * @return string|null 보존할 사람 서술 (없으면 null)
     */
    private function extractHumanProse(string $existing, string $key): ?string
    {
        $startMarker = self::GEN_START.$key.' -->';
        $startPos = strpos($existing, $startMarker);

        if ($startPos === false) {
            return null;
        }

        $endPos = strpos($existing, self::GEN_END, $startPos);
        if ($endPos === false) {
            return null;
        }

        $afterGen = substr($existing, $endPos + strlen(self::GEN_END));
        // 다음 ### 헤딩 전까지가 이 엔드포인트의 사람 서술
        $nextHeading = preg_match('/\n### /', $afterGen, $m, PREG_OFFSET_CAPTURE)
            ? $m[0][1]
            : strlen($afterGen);

        $prose = trim(substr($afterGen, 0, $nextHeading));

        // 기본 TODO 스텁만 있으면 보존할 것 없음
        if ($prose === '' || Str::contains($prose, 'TODO: 이 엔드포인트의 용도')) {
            return null;
        }

        return $prose;
    }

    /**
     * 새 섹션의 기본 서술 스텁을 보존된 사람 서술로 치환합니다.
     *
     * @param  string  $section  새로 생성된 섹션
     * @param  string|null  $preserved  보존할 사람 서술
     * @return string 서술이 반영된 섹션
     */
    private function applyPreservedProse(string $section, ?string $preserved): string
    {
        if ($preserved === null) {
            return $section;
        }

        $stub = '**설명** <!-- TODO: 이 엔드포인트의 용도·주의사항·예시 시나리오를 작성하세요 -->';

        return str_replace($stub, $preserved, $section);
    }
}
