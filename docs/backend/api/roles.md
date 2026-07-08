# Roles API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Roles 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/roles
<!-- @generated:start:api.admin.roles.index -->
- **라우트명**: `api.admin.roles.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@index`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 100 | 페이지당 항목 수 |
| search | query | string | 아니오 | max 255 | 검색어 (지정한 검색 대상 필드에서 부분 일치) |
| is_active | query | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `23` | 기본 키 (내부 식별자) |
| identifier | string | `sirsoft-board.archive.manager` | 역할명 (예: admin, user, manager) |
| name | string | `아카이브 게시판 관리자` | 역할 이름 (다국어 JSON) |
| name_raw | object | `{"ko":"아카이브 게시판 관리자","en":"Archive Board Manager"}` | `name` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| description | string | `아카이브 게시판의 관리자 역할` | 역할 설명 (다국어 JSON) |
| description_raw | object | `{"ko":"아카이브 게시판의 관리자 역할","en":"Manager role for Archive b…` | `description` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| extension_type | string | `module` | 확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의) |
| extension_identifier | string | `sirsoft-board` | 확장 식별자 (예: core, sirsoft-board, sirsoft-payment) |
| extension_name | string | `게시판` | 이 리소스를 소유한 확장의 표시 이름 (manifest name) |
| is_deletable | boolean | `false` | deletable 여부 |
| is_active | boolean | `true` | active 여부 |
| users_count | integer | `1` | users 개수 (집계) |
| permission_ids | array | `[312,313,314,315,316,317,318,319,320,321,322,323,324,325,…` | permission 식별자 배열 (연관 리소스 참조) |
| permission_values | array | `[{"id":312,"scope_type":null},{"id":313,"scope_type":null…` | 할당된 각 권한의 id와 적용 범위만 담은 경량 목록 (원소 id/scope_type — 역할-권한 pivot 파생). scope_type: null=전체, role=역할 범위, self=본인 범위 |
| permissions | array | `[{"id":85,"parent_id":null,"identifier":"sirsoft-board","…` | 연결된 권한 목록 (id/identifier/name — 역할 경유 권한 관계 파생) |
| created_at | string | `2026-06-04 09:35:35` | 생성 일시 |
| updated_at | string | `2026-06-04 09:35:35` | 최종 수정 일시 |
| abilities | object | `{"can_create":true,"can_update":true,"can_delete":true,"c…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명**

역할 관리 화면(`admin_role_list.json`)의 목록 데이터를 제공하는 페이지네이션 조회 엔드포인트다. `search`(identifier/name 텍스트 검색)와 `is_active`(활성 여부)로 필터링하며 `per_page` 로 페이지 크기를 조절한다. 응답에는 각 역할의 할당 권한(permission_ids/permission_values/permissions), 소유 확장 정보, 사용자 수, 현재 사용자의 조작 가능 여부(abilities)가 포함된다. `core.permissions.read` 권한이 필요하다.


### POST /api/admin/roles
<!-- @generated:start:api.admin.roles.store -->
- **라우트명**: `api.admin.roles.store`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@store`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.create`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | body | string | 예 | max 100 | 대상 확장/리소스의 식별자 |
| name | body | string | 예 | — | 대상의 이름/명칭 |
| description | body | string | 아니오 | — | 설명 |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| permissions | body | array | 아니오 | — | 역할에 부여할 권한 목록. 각 원소는 `{id, scope_type}` (id=권한 식별자, scope_type=적용 범위: null 전체 / role 역할 범위 / self 본인 범위). 전달된 목록 기준으로 역할의 권한 집합이 재설정됨 |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.role.store_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.create`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명**

새 역할을 생성한다. `identifier` 는 소문자로 시작하는 영숫자·언더스코어 형식(`^[a-z][a-z0-9_]*$`)이어야 하고 전역 고유해야 한다. `name`·`description` 은 다국어 필드로, 문자열로 보내면 설정된 로케일 전체에 동일 값이 채워지고 객체(`{"ko":..., "en":...}`)로도 보낼 수 있다. `permissions` 는 `[{id, scope_type}]` 형식으로 부여할 권한과 각 권한의 적용 범위(scope_type: null=전체, role, self)를 지정한다. 검증 규칙은 `core.role.store_validation_rules` 필터 훅으로 확장이 확장할 수 있다. `core.permissions.create` 권한이 필요하며 성공 시 201 로 생성된 역할을 반환한다.


### GET /api/admin/roles/active
<!-- @generated:start:api.admin.roles.active -->
- **라우트명**: `api.admin.roles.active`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@active`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| identifier | string | `admin` | 역할명 (예: admin, user, manager) |
| name | string | `관리자` | 역할 이름 (다국어 JSON) |
| name_raw | object | `{"ko":"관리자","en":"Administrator"}` | `name` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| description | string | `시스템의 모든 기능에 접근할 수 있는 최고 관리자입니다.` | 역할 설명 (다국어 JSON) |
| description_raw | object | `{"ko":"시스템의 모든 기능에 접근할 수 있는 최고 관리자입니다.","en":"Super admin…` | `description` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| extension_type | string | `core` | 확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의) |
| extension_identifier | string | `core` | 확장 식별자 (예: core, sirsoft-board, sirsoft-payment) |
| extension_name | string | `이커머스` | 이 리소스를 소유한 확장의 표시 이름 (manifest name) |
| is_deletable | boolean | `false` | deletable 여부 |
| is_active | boolean | `true` | active 여부 |
| created_at | string | `2026-05-27 15:20:18` | 생성 일시 |
| updated_at | string | `2026-06-30 13:41:48` | 최종 수정 일시 |
| abilities | object | `{"can_create":true,"can_update":true,"can_delete":true,"c…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |

<!-- @generated:end -->

**설명**

셀렉트 UI(사용자 폼·메뉴 편집의 역할 선택 등)에 채울 활성 역할 목록을 제공한다. 별도 권한 미들웨어가 없어 인증만 되면 호출 가능하지만, 내부에서 권한에 따라 범위가 갈린다. `core.permissions.read` 권한 보유자는 전체 활성 역할을 받고(사용자에게 역할을 부여하는 관리 용도), 미보유자는 자신에게 부여된 활성 역할만 받는다(자기 정보 폼 표시 용도). 응답의 `abilities.can_assign_roles` 는 `core.permissions.update` 권한 보유 여부를 나타낸다.


### DELETE /api/admin/roles/{role}
<!-- @generated:start:api.admin.roles.destroy -->
- **라우트명**: `api.admin.roles.destroy`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@destroy`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.delete`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| role | path | string | 예 | — | 대상 role의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.delete`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

역할을 삭제한다. 코어 소유 역할(admin/user 등)은 403(`role.system_role_delete_error`)으로, 모듈·플러그인이 소유한 확장 역할은 403(`role.extension_owned_role_delete_error`)으로 거부된다. 삭제 가능한(사용자 정의) 역할만 제거되며, CASCADE 에 의존하지 않고 권한·메뉴·사용자 매핑을 명시적으로 해제한 뒤 역할을 삭제한다. `core.permissions.delete` 권한이 필요하다.


### GET /api/admin/roles/{role}
<!-- @generated:start:api.admin.roles.show -->
- **라우트명**: `api.admin.roles.show`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@show`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| role | path | string | 예 | — | 대상 role의 식별자 |

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `66` | 기본 키 (내부 식별자) |
| identifier | string | `apidoc-sample-role` | 역할명 (예: admin, user, manager) |
| name | string | `API 문서 샘플 역할` | 역할 이름 (다국어 JSON) |
| name_raw | object | `{"ko":"API 문서 샘플 역할","en":"API Doc Sample Role"}` | `name` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| description | string | `문서 실측용 역할` | 역할 설명 (다국어 JSON) |
| description_raw | object | `{"ko":"문서 실측용 역할","en":"Sample role for API docs"}` | `description` 의 원본 값 (현재 로케일 미해석 원본 JSON/문자열) |
| extension_type | null | `null` | 확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의) |
| extension_identifier | null | `null` | 확장 식별자 (예: core, sirsoft-board, sirsoft-payment) |
| extension_name | null | `null` | 이 리소스를 소유한 확장의 표시 이름 (manifest name) |
| is_deletable | boolean | `true` | deletable 여부 |
| is_active | boolean | `true` | active 여부 |
| users_count | integer | `0` | users 개수 (집계) |
| permission_ids | array | `[1,85,100]` | permission 식별자 배열 (연관 리소스 참조) |
| permission_values | array | `[{"id":1,"scope_type":null},{"id":85,"scope_type":null},{…` | 할당된 각 권한의 id와 적용 범위만 담은 경량 목록 (원소 id/scope_type — 역할-권한 pivot 파생). scope_type: null=전체, role=역할 범위, self=본인 범위 |
| permissions | array | `[{"id":1,"parent_id":null,"identifier":"core","name":"코어"…` | 연결된 권한 목록 (id/identifier/name — 역할 경유 권한 관계 파생) |
| created_at | string | `2026-07-06 19:15:16` | 생성 일시 |
| updated_at | string | `2026-07-06 19:15:16` | 최종 수정 일시 |
| abilities | object | `{"can_create":true,"can_update":true,"can_delete":true,"c…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

단일 역할의 상세 정보를 조회한다. 역할 편집 화면과 복제(clone_from) 시 원본 값을 채우는 데 사용된다. 목록 응답과 달리 permissions 관계를 pivot(scope_type)과 함께 로드하므로 `permission_ids`·`permission_values`·`permissions`(계층 트리)와 `users_count` 가 항상 포함된다. `core.permissions.read` 권한이 필요하다.


### PUT /api/admin/roles/{role}
<!-- @generated:start:api.admin.roles.update -->
- **라우트명**: `api.admin.roles.update`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@update`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| role | path | string | 예 | — | 대상 role의 식별자 |
| name | body | string | 예 | — | 대상의 이름/명칭 |
| description | body | string | 아니오 | — | 설명 |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| permissions | body | array | 아니오 | — | 역할에 부여할 권한 목록. 각 원소는 `{id, scope_type}` (id=권한 식별자, scope_type=적용 범위: null 전체 / role 역할 범위 / self 본인 범위). 전달된 목록 기준으로 역할의 권한 집합이 재설정됨 |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.role.update_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

기존 역할의 `name`·`description`·`is_active`·`permissions` 를 수정한다. 생성과 달리 `identifier` 는 변경 대상이 아니며, 각 필드는 `sometimes` 규칙이라 전달된 항목만 갱신된다. `permissions` 를 보내면 `[{id, scope_type}]` 형식으로 역할의 권한 집합 전체가 동기화된다(전달된 목록 기준으로 재설정). `name`·`description` 은 문자열/다국어 객체 양쪽을 받는다. 검증 규칙은 `core.role.update_validation_rules` 필터 훅으로 확장할 수 있다. `core.permissions.update` 권한이 필요하다.


### PATCH /api/admin/roles/{role}/toggle-status
<!-- @generated:start:api.admin.roles.toggle-status -->
- **라우트명**: `api.admin.roles.toggle-status`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\RoleController@toggleStatus`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| role | path | string | 예 | — | 대상 role의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

역할의 `is_active` 상태를 반대로 토글한다(활성↔비활성). 목록 화면의 상태 스위치에서 호출되며, 별도 본문 없이 대상 역할만 지정하면 된다. 성공 시 사용자 수를 다시 집계한 갱신된 역할 리소스를 반환한다. `core.permissions.update` 권한이 필요하다.


