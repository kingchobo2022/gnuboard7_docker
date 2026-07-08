# Notification Definitions API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Notification Definitions 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/notification-definitions
<!-- @generated:start:api.admin.notification-definitions.index -->
- **라우트명**: `api.admin.notification-definitions.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationDefinitionController@index`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| search | query | string | 아니오 | max 255 | 검색어 (지정한 검색 대상 필드에서 부분 일치) |
| extension_type | query | string | 아니오 | `core`, `module`, `plugin` | 확장 유형 (core/module/plugin/template) |
| extension_identifier | query | string | 아니오 | max 100 | 확장 식별자 |
| channel | query | string | 아니오 | max 50 | 채널 필터 — 활성 채널(`channels`) 배열에 이 채널을 포함하는 정의만 조회 (mail, database 등) |
| is_active | query | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| per_page | query | integer | 아니오 | min 1, max 100 | 페이지당 항목 수 |
| sort_by | query | string | 아니오 | `id`, `type`, `extension_type`, `is_active`, `created_at`, `updated_at` | 정렬 기준 필드명 |
| sort_order | query | string | 아니오 | `asc`, `desc` | 정렬 방향 (asc 오름차순 / desc 내림차순) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.notification_definition.filter_index_rules`).

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| number | integer | `1` | 목록에서의 순번 (페이지네이션 반영 행 번호 — HasRowNumber 파생) |
| id | integer | `1` | 기본 키 (내부 식별자) |
| type | string | `welcome` | 알림 타입 (welcome, order_confirmed 등) |
| hook_prefix | string | `core.auth` | 훅 접두사 (core.auth, sirsoft-ecommerce 등) |
| extension_type | string | `core` | 확장 타입: core, module, plugin |
| extension_identifier | string | `core` | 확장 식별자: core, sirsoft-board 등 |
| name | object | `{"ko":"회원가입 환영","en":"Welcome","ja":"会員登録 ウェルカム"}` | 다국어 이름 ({"ko": "회원가입 환영", "en": "Welcome"}) |
| description | object | `{"ko":"회원가입 완료 시 발송되는 환영 알림","en":"Welcome notification s…` | 다국어 설명 |
| variables | array | `[{"key":"name","description":"수신자 이름"},{"key":"app_name",…` | 사용 가능 변수 메타데이터 ([{key, description}]) |
| channels | array | `["mail","database"]` | 활성 채널 (["mail", "database"]) |
| hooks | array | `["core.auth.after_register"]` | 트리거 훅 목록 (["core.auth.after_register"]) |
| is_active | boolean | `true` | active 여부 |
| is_default | boolean | `true` | default 여부 |
| templates | array | `[{"id":1,"definition_id":1,"channel":"mail","subject":{"k…` | 채널별 알림 템플릿 목록 (templates 관계 로드 시 NotificationTemplateResource 배열, 미로드 시 null) |
| created_at | string | `2026-05-27 15:20:18` | 생성 일시 |
| updated_at | string | `2026-06-30 13:33:16` | 최종 수정 일시 |
| abilities | object | `{"can_update":true,"can_delete":true}` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 등록된 알림 정의 목록을 페이지네이션으로 조회합니다. 인증(`auth:sanctum`)과 `core.settings.read` 권한이 필요합니다. `search`, `extension_type`, `extension_identifier`, `channel`, `is_active` 로 필터링하고 `sort_by`/`sort_order` 로 정렬하며, 확장이 `core.notification_definition.filter_index_rules` 훅으로 필터를 추가할 수 있습니다. 관리자 알림 정의 관리 목록 화면을 렌더링할 때 사용합니다.


### GET /api/admin/notification-definitions/{definition}
<!-- @generated:start:api.admin.notification-definitions.show -->
- **라우트명**: `api.admin.notification-definitions.show`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationDefinitionController@show`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| definition | path | string | 예 | — | 대상 definition의 식별자 |

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `21` | 기본 키 (내부 식별자) |
| type | string | `apidoc-sample.event` | 알림 타입 (welcome, order_confirmed 등) |
| hook_prefix | string | `core` | 훅 접두사 (core.auth, sirsoft-ecommerce 등) |
| extension_type | string | `core` | 확장 타입: core, module, plugin |
| extension_identifier | string | `` | 확장 식별자: core, sirsoft-board 등 |
| name | object | `{"ko":"API 문서 샘플 알림","en":"API Doc Sample Notification"}` | 다국어 이름 ({"ko": "회원가입 환영", "en": "Welcome"}) |
| description | object | `{"ko":"문서 실측용 알림 정의","en":"Sample notification"}` | 다국어 설명 |
| variables | array | `[]` | 사용 가능 변수 메타데이터 ([{key, description}]) |
| channels | array | `["database","mail"]` | 활성 채널 (["mail", "database"]) |
| hooks | array | `[]` | 트리거 훅 목록 (["core.auth.after_register"]) |
| is_active | boolean | `true` | active 여부 |
| is_default | boolean | `false` | default 여부 |
| templates | array | `[{"id":41,"definition_id":21,"channel":"mail","subject":"…` | 채널별 알림 템플릿 목록 (templates 관계 로드 시 NotificationTemplateResource 배열, 미로드 시 null) |
| created_at | string | `2026-07-06 19:15:16` | 생성 일시 |
| updated_at | string | `2026-07-06 19:15:16` | 최종 수정 일시 |
| abilities | object | `{"can_update":true,"can_delete":true}` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 단일 알림 정의의 상세 정보를 조회하며, 응답에 소속 템플릿(`templates`)을 함께 로드합니다. 인증(`auth:sanctum`)과 `core.settings.read` 권한이 필요합니다. `definition` 경로 파라미터로 대상을 지정하며, 정의 편집 화면 진입 시 채널별 템플릿을 포함한 전체 구성을 불러올 때 사용합니다.


### PUT /api/admin/notification-definitions/{definition}
<!-- @generated:start:api.admin.notification-definitions.update -->
- **라우트명**: `api.admin.notification-definitions.update`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationDefinitionController@update`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| definition | path | string | 예 | — | 대상 definition의 식별자 |
| channels | body | array | 아니오 | min 1 | 활성 채널 목록 — 이 정의가 발송에 사용할 채널 배열 (각 원소 최대 50자, mail·database 등). 지정 시 최소 1개 필요 |
| hooks | body | array | 아니오 | — | 트리거 훅 목록 — 이 알림을 발송시키는 훅 이름 배열 (각 원소 최대 255자, core.auth.after_register 등) |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.notification_definition.filter_update_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 알림 정의의 활성 채널(`channels`), 트리거 훅(`hooks`), 활성 상태(`is_active`)를 수정합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. Service 계층에서 수정 후 템플릿을 다시 로드해 반환하며, 확장이 `core.notification_definition.filter_update_rules` 훅으로 추가 파라미터를 검증에 넣을 수 있습니다. 발송 채널 구성이나 훅 연결을 변경할 때 사용합니다.


### POST /api/admin/notification-definitions/{definition}/reset
<!-- @generated:start:api.admin.notification-definitions.reset -->
- **라우트명**: `api.admin.notification-definitions.reset`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationDefinitionController@reset`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| definition | path | string | 예 | — | 대상 definition의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 알림 정의에 속한 모든 채널 템플릿을 기본값(default) 데이터로 일괄 복원하고, 정의 자체를 default 상태로 표시합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. 각 템플릿의 제목·본문을 기본값으로 덮어쓰는 파괴적 작업이므로 사용자 편집분이 사라집니다. 관리자가 커스터마이징한 알림 문구를 초기 상태로 되돌릴 때 사용합니다.


### PATCH /api/admin/notification-definitions/{definition}/toggle-active
<!-- @generated:start:api.admin.notification-definitions.toggle-active -->
- **라우트명**: `api.admin.notification-definitions.toggle-active`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationDefinitionController@toggleActive`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| definition | path | string | 예 | — | 대상 definition의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 알림 정의의 활성 상태(`is_active`)를 현재 값의 반대로 토글합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. 비활성 정의는 해당 알림 발송이 중단되므로, 관리자가 목록에서 특정 알림을 켜거나 끄는 스위치 조작에 사용합니다.


