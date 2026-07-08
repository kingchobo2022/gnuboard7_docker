<?php

namespace Tests\Unit\Support\ApiDoc;

use App\Support\ApiDoc\ApiDocScaffolder;
use App\Support\ApiDoc\ResponseSchemaInferrer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * API 문서 생성 파이프라인 단위 테스트.
 *
 * 실측 응답 스키마 추론과 스캐폴딩 병합(사람 서술 보존)의 순수 로직을
 * HTTP 의존 없이 검증한다.
 */
class ApiDocPipelineTest extends TestCase
{
    #[Test]
    public function 목록_응답에서_배열_항목_필드와_페이지네이션을_추론한다(): void
    {
        $inferrer = new ResponseSchemaInferrer;

        $body = [
            'success' => true,
            'message' => '조회 성공',
            'data' => [
                'data' => [
                    ['id' => 1, 'name' => '홍길동', 'is_active' => true, 'deleted' => null],
                ],
                'pagination' => ['current_page' => 1, 'total' => 10],
            ],
        ];

        $schema = $inferrer->infer($body);

        $this->assertSame('collection', $schema['shape']);
        $this->assertTrue($schema['pagination']);
        $this->assertSame(['success', 'message', 'data'], $schema['envelope']);

        $fields = collect($schema['fields'])->keyBy('name');
        $this->assertSame('integer', $fields['id']['type']);
        $this->assertSame('string', $fields['name']['type']);
        $this->assertSame('boolean', $fields['is_active']['type']);
        $this->assertSame('null', $fields['deleted']['type']);
        $this->assertSame('홍길동', $fields['name']['sample']);
    }

    #[Test]
    public function 단건_응답에서_객체_필드를_추론한다(): void
    {
        $inferrer = new ResponseSchemaInferrer;

        $body = [
            'success' => true,
            'data' => ['total' => 155, 'ratio' => 0.5, 'labels' => ['ko' => 91]],
        ];

        $schema = $inferrer->infer($body);

        $this->assertSame('object', $schema['shape']);
        $this->assertFalse($schema['pagination']);

        $fields = collect($schema['fields'])->keyBy('name');
        $this->assertSame('integer', $fields['total']['type']);
        $this->assertSame('number', $fields['ratio']['type']);
        $this->assertSame('object', $fields['labels']['type']);
    }

    #[Test]
    public function 표_셀에서_파이프_문자를_이스케이프한다(): void
    {
        $inferrer = new ResponseSchemaInferrer;

        $body = ['success' => true, 'data' => ['note' => 'a|b|c']];
        $schema = $inferrer->infer($body);

        $fields = collect($schema['fields'])->keyBy('name');
        $this->assertStringNotContainsString('|b', str_replace('\\|', '', $fields['note']['sample']));
        $this->assertStringContainsString('\\|', $fields['note']['sample']);
    }

    #[Test]
    public function 스캐폴더가_엔드포인트_섹션을_표준_포맷으로_생성한다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET',
            'uri' => '/api/admin/users',
            'name' => 'api.admin.users.index',
            'controller' => 'App\\Http\\Controllers\\Api\\Admin\\UserController',
            'controller_method' => 'index',
            'permission' => 'core.users.read',
            'middleware' => ['auth:sanctum', 'App\\Http\\Middleware\\AdminMiddleware'],
            'path_params' => [],
        ];

        $request = ['request_class' => 'X', 'params' => [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'allowed' => 'min 1'],
        ], 'hook_filters' => ['core.user.list_validation_rules']];

        $schema = [
            'envelope' => ['success', 'data'],
            'shape' => 'collection',
            'fields' => [['name' => 'id', 'type' => 'integer', 'sample' => '1']],
            'pagination' => true,
        ];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null]);

        $this->assertStringContainsString('### GET /api/admin/users', $section);
        $this->assertStringContainsString('@generated:start:api.admin.users.index', $section);
        $this->assertStringContainsString('`auth:sanctum` + `admin` + `permission:core.users.read`', $section);
        $this->assertStringContainsString('| page | query | integer | 아니오 | min 1 |', $section);
        $this->assertStringContainsString('core.user.list_validation_rules', $section);
        $this->assertStringContainsString('| id | integer | `1` |', $section);
        // 에러 응답 표: auth:sanctum→401, admin+permission→403, FormRequest(params/hook)→422
        $this->assertStringContainsString('**에러 응답**', $section);
        $this->assertStringContainsString('| 401 | Unauthenticated |', $section);
        $this->assertStringContainsString('| 403 | Forbidden | 요구 권한(`core.users.read`)이 없는 경우 |', $section);
        $this->assertStringContainsString('| 422 | Unprocessable Entity |', $section);
        $this->assertStringContainsString('@generated:end', $section);
        // 에러 표는 @generated 블록 내부(재생성 대상)여야 한다
        $genStart = strpos($section, '@generated:start');
        $genEnd = strpos($section, '@generated:end');
        $errorPos = strpos($section, '**에러 응답**');
        $this->assertGreaterThan($genStart, $errorPos);
        $this->assertLessThan($genEnd, $errorPos);
    }

    #[Test]
    public function 에러_섹션이_라우트_메타에서_대표_상태코드를_추론한다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        // path param 존재 + admin + permission + FormRequest → 401/403/422/404 전부
        $route = [
            'method' => 'PUT',
            'uri' => '/api/admin/users/{user}',
            'name' => 'api.admin.users.update',
            'controller' => 'C', 'controller_method' => 'update',
            'permission' => 'core.users.update',
            'middleware' => ['auth:sanctum', 'App\\Http\\Middleware\\AdminMiddleware'],
            'path_params' => ['user'],
        ];
        $request = ['request_class' => 'X', 'params' => [
            ['name' => 'name', 'type' => 'string', 'required' => true, 'allowed' => ''],
        ], 'hook_filters' => []];

        $section = $scaffolder->endpointSection($route, $request, null, ['status' => null, 'skipped_reason' => 'write-method']);

        $this->assertStringContainsString('| 401 | Unauthenticated |', $section);
        $this->assertStringContainsString('| 403 | Forbidden | 요구 권한(`core.users.update`)이 없는 경우 |', $section);
        $this->assertStringContainsString('| 422 | Unprocessable Entity |', $section);
        $this->assertStringContainsString('| 404 | Not Found |', $section);
    }

    #[Test]
    public function optional_sanctum_공개조회는_401을_유발하지_않는다(): void
    {
        // optional.sanctum(선택 인증)은 미인증도 허용 → 401 없음.
        // path param 만 있으므로 404 만 노출.
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET',
            'uri' => '/api/modules/sirsoft-board/boards/{slug}',
            'name' => 'api.modules.sirsoft-board.boards.show',
            'controller' => 'C', 'controller_method' => 'show',
            'permission' => null,
            'middleware' => ['api', 'optional.sanctum'],
            'path_params' => ['slug'],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = ['envelope' => ['data'], 'shape' => 'object', 'fields' => [['name' => 'id', 'type' => 'integer', 'sample' => '1']], 'pagination' => false];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null]);

        $this->assertStringNotContainsString('| 401 |', $section);
        $this->assertStringNotContainsString('| 403 |', $section);
        $this->assertStringNotContainsString('| 422 |', $section);
        $this->assertStringContainsString('| 404 | Not Found |', $section);
    }

    #[Test]
    public function 완전_공개_조회는_대표_에러_없음으로_표기한다(): void
    {
        // 인증·권한·FormRequest·path param 전무 → 대표 에러 없음.
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET', 'uri' => '/api/locales', 'name' => 'api.locales.index',
            'controller' => 'C', 'controller_method' => 'index', 'permission' => null,
            'middleware' => ['api'], 'path_params' => [],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = ['envelope' => ['data'], 'shape' => 'collection', 'fields' => [['name' => 'code', 'type' => 'string', 'sample' => 'ko']], 'pagination' => false];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null]);

        $this->assertStringContainsString('대표 에러 없음', $section);
    }

    #[Test]
    public function optional_sanctum_라우트는_선택적_인증으로_표기한다(): void
    {
        // optional.sanctum(회원/비회원 모두 접근)을 auth:sanctum(인증 필수)로
        // 오표기하면 공개 API 계약이 왜곡된다(게시판 공개 조회 등). 별도 표기 강제.
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET',
            'uri' => '/api/modules/sirsoft-board/boards/{slug}/posts',
            'name' => 'api.modules.sirsoft-board.boards.posts.index',
            'controller' => 'C', 'controller_method' => 'index',
            'permission' => 'user,sirsoft-board.{slug}.posts.read',
            'middleware' => ['api', 'optional.sanctum', 'throttle:600,1'],
            'path_params' => ['slug'],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = ['envelope' => ['data'], 'shape' => 'collection', 'fields' => [], 'pagination' => true];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null]);

        // optional.sanctum 은 선택적 인증으로 표기되고 auth:sanctum 으로 오표기되지 않는다
        $this->assertStringContainsString('optional.sanctum', $section);
        $this->assertStringNotContainsString('`auth:sanctum`', $section);
    }

    #[Test]
    public function 컬럼_주석이_있으면_응답_필드_설명으로_채운다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET', 'uri' => '/api/admin/users', 'name' => 'api.admin.users.index',
            'controller' => 'C', 'controller_method' => 'index', 'permission' => null,
            'middleware' => [], 'path_params' => [],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = [
            'envelope' => ['data'], 'shape' => 'object',
            'fields' => [
                ['name' => 'nickname', 'type' => 'string', 'sample' => 'hong'],
                ['name' => 'unknown_field', 'type' => 'string', 'sample' => 'x'],
            ],
            'pagination' => false,
        ];
        $commentMap = ['nickname' => '닉네임'];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null], $commentMap);

        // 주석 있는 필드는 설명이 채워지고, 없는 필드는 TODO 유지
        $this->assertStringContainsString('| nickname | string | `hong` | 닉네임 |', $section);
        $this->assertStringContainsString('| unknown_field | string | `x` | <!-- TODO: 설명 --> |', $section);
    }

    #[Test]
    public function 쓰기_메서드는_응답_필드를_실측_제외로_표기한다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'POST', 'uri' => '/api/admin/users', 'name' => 'api.admin.users.store',
            'controller' => 'C', 'controller_method' => 'store', 'permission' => 'core.users.create',
            'middleware' => ['auth:sanctum'], 'path_params' => [],
        ];

        $section = $scaffolder->endpointSection(
            $route,
            ['request_class' => null, 'params' => [], 'hook_filters' => []],
            null,
            ['status' => null, 'skipped_reason' => 'write-method']
        );

        $this->assertStringContainsString('실측 제외: write-method', $section);
    }

    #[Test]
    public function 재생성_시_사람이_작성한_설명을_보존한다(): void
    {
        $scaffolder = new ApiDocScaffolder;

        $route = [
            'method' => 'GET', 'uri' => '/api/x', 'name' => 'api.x.index',
            'controller' => 'C', 'controller_method' => 'index', 'permission' => null,
            'middleware' => [], 'path_params' => [],
        ];
        $request = ['request_class' => null, 'params' => [], 'hook_filters' => []];
        $schema = ['envelope' => ['data'], 'shape' => 'object', 'fields' => [['name' => 'a', 'type' => 'integer', 'sample' => '1']], 'pagination' => false];

        $section = $scaffolder->endpointSection($route, $request, $schema, ['status' => 200, 'skipped_reason' => null]);
        $header = "# X\n";

        // 최초 생성
        $first = $scaffolder->mergeDocument(null, $header, [$section], ['api.x.index']);
        $this->assertStringContainsString('TODO: 이 엔드포인트의 용도', $first);

        // 사람이 설명을 채운 상태
        $withProse = str_replace(
            '**설명** <!-- TODO: 이 엔드포인트의 용도·주의사항·예시 시나리오를 작성하세요 -->',
            '**설명** 실제 사람이 작성한 설명입니다.',
            $first
        );

        // 재생성: 새 섹션으로 병합해도 사람 서술 보존
        $regenerated = $scaffolder->mergeDocument($withProse, $header, [$section], ['api.x.index']);

        $this->assertStringContainsString('실제 사람이 작성한 설명입니다.', $regenerated);
        $this->assertStringNotContainsString('TODO: 이 엔드포인트의 용도', $regenerated);
    }
}
