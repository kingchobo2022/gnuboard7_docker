# Menus API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Menus 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/menus
<!-- @generated:start:api.admin.menus.index -->
- **라우트명**: `api.admin.menus.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@index`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| is_active | query | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| filters | query | array | 아니오 | max 10 | 추가 필터 조건 맵 (필드별 조건) |
| sort_by | query | string | 아니오 | `created_at`, `name`, `slug`, `order` | 정렬 기준 필드명 |
| sort_order | query | string | 아니오 | `asc`, `desc` | 정렬 방향 (asc 오름차순 / desc 내림차순) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.menu.list_validation_rules`).

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| name | object | `{"ko":"대시보드","en":"Dashboard","ja":"ダッシュボード"}` | 메뉴 이름 (다국어 JSON) |
| slug | string | `admin-dashboard` | 메뉴 슬러그 |
| url | string | `/admin/dashboard` | 메뉴 URL |
| icon | string | `fas fa-tachometer-alt` | 메뉴 아이콘 |
| order | integer | `1` | 메뉴 순서 |
| is_active | boolean | `true` | active 여부 |
| parent_id | null | `null` | 상위 메뉴 ID |
| extension_type | string | `core` | 확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의) |
| extension_identifier | string | `core` | 확장 식별자 (예: core, sirsoft-board, sirsoft-payment) |
| children | array | `[]` | 하위 항목 배열 (계층 트리 — children 관계 파생) |
| creator | null | `null` | 생성자 정보 객체 (uuid/name/email — creator 관계 파생) |
| roles | array | `[{"id":1,"name":{"ko":"관리자","en":"Administrator"},"permis…` | 이 메뉴 노출이 허용된 역할 목록 (원소 id/name/permission_type — roles 관계 파생, permission_type 은 pivot 의 노출 권한 유형) |
| created_at | string | `2026-05-27 15:20:18` | 생성 일시 |
| updated_at | string | `2026-05-27 15:21:38` | 최종 수정 일시 |
| is_owner | boolean | `false` | 현재 인증 사용자가 이 리소스의 소유자인지 여부 (BaseApiResource 표준 메타) |
| abilities | object | `{"can_create":true,"can_update":true,"can_delete":true}` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명**

관리자 메뉴 관리 화면(`partials/admin_menu_list/*`)의 목록 표시 기준 엔드포인트. `is_active` 또는 `filters` 가 있으면 필터링된 관리용 메뉴를, 없으면 최상위 메뉴 전체를 반환한다. `filters` 는 `field`(name/slug/url/all)·`value`·`operator`(like/eq/starts_with/ends_with) 조합의 배열이며 최대 10개까지 허용된다. `name` 은 다국어 JSON 객체로 내려오고, 각 항목에 `children`/`creator`/`roles` 관계와 `abilities`(현재 사용자의 수정/삭제 가능 여부)가 포함된다. 인증 계약: `auth:sanctum` + `permission:core.menus.read`.


### POST /api/admin/menus
<!-- @generated:start:api.admin.menus.store -->
- **라우트명**: `api.admin.menus.store`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@store`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.create`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| name | body | string | 예 | — | 대상의 이름/명칭 |
| slug | body | string | 예 | max 255 | URL 친화 식별자 (slug) |
| url | body | string | 아니오 | max 500 | URL |
| icon | body | string | 아니오 | max 100 | 아이콘 |
| parent_id | body | integer | 아니오 | — | parent 식별자 |
| order | body | integer | 아니오 | min 0 | 표시 정렬 순서 값 (작을수록 우선) |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| extension_type | body | string | 아니오 | `core`, `module`, `plugin` | 확장 유형 (core/module/plugin/template) |
| extension_identifier | body | string | 아니오 | max 255 | 확장 식별자 |
| roles | body | array | 아니오 | — | 이 메뉴 노출을 허용할 역할 ID 배열 (각 원소는 존재하는 role id — 지정 시 노출 허용 역할 목록으로 설정/교체) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.menu.create_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.create`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명**

새 메뉴를 생성한다. `name` 은 다국어 값(문자열로 보내면 지원 로케일 전체에 동일 값으로 자동 확장)이며 `slug` 는 `menus` 테이블 내 유일해야 한다. `parent_id` 로 하위 메뉴를 만들 수 있고, `roles` 배열로 이 메뉴 노출을 허용할 역할 ID 를 지정한다. 성공 시 `201` 과 함께 생성된 메뉴(관계 eager-load 포함)를 `MenuResource` 로 반환한다. 관리자 메뉴 관리 화면의 메뉴 추가 폼에서 소비된다. 인증 계약: `auth:sanctum` + `permission:core.menus.create`.


### GET /api/admin/menus/active
<!-- @generated:start:api.admin.menus.active -->
- **라우트명**: `api.admin.menus.active`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@active`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.read`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| name | object | `{"ko":"대시보드","en":"Dashboard","ja":"ダッシュボード"}` | 메뉴 이름 (다국어 JSON) |
| slug | string | `admin-dashboard` | 메뉴 슬러그 |
| url | string | `/admin/dashboard` | 메뉴 URL |
| icon | string | `fas fa-tachometer-alt` | 메뉴 아이콘 |
| order | integer | `1` | 메뉴 순서 |
| is_active | boolean | `true` | active 여부 |
| children | array | `[]` | 하위 항목 배열 (계층 트리 — children 관계 파생) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.read`)이 없는 경우 |

<!-- @generated:end -->

**설명**

관리자 레이아웃 전역 부트스트랩 엔드포인트. `sirsoft-admin_basic/layouts/_admin_base.json` 의 `admin_menu` `data_source`(`/api/admin/menus/active`)가 모든 관리자 페이지 진입 시 자동 호출해, 사이드바 메뉴 트리를 채운다. 인증 사용자의 역할 기준으로 접근 가능한 활성 메뉴만 계층 구조(`children` 중첩)로 반환하며, `data` 는 최상위 메뉴 배열이다. 사용자 정보가 없을 때는 모든 활성 메뉴를 fallback 으로 반환한다. 인증 계약: `auth:sanctum` + `permission:core.menus.read`.


### GET /api/admin/menus/extension/{type}/{identifier}
<!-- @generated:start:api.admin.menus.by-extension -->
- **라우트명**: `api.admin.menus.by-extension`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@getByExtension`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| type | path | string | 예 | — | 확장 소유 타입 (ExtensionOwnerType 으로 파싱 — module: 모듈, plugin: 플러그인. 미유효 값이면 422 menu.invalid_extension_type) |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

특정 확장이 소유한 메뉴 목록을 조회한다. `type` 은 `ExtensionOwnerType`(module, plugin)으로 파싱되며, 유효하지 않은 값이면 `422 menu.invalid_extension_type` 을 반환한다. `identifier` 는 확장 식별자(예: `sirsoft-board`)로, 해당 확장이 등록한 메뉴만 `MenuCollection` 으로 내려준다. 확장 설치/제거 시 그 확장 소유 메뉴를 확인·정리하는 관리 흐름에서 사용된다. 인증 계약: `auth:sanctum` + `permission:core.menus.read`.


### GET /api/admin/menus/hierarchy
<!-- @generated:start:api.admin.menus.hierarchy -->
- **라우트명**: `api.admin.menus.hierarchy`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@hierarchy`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.read`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| name | object | `{"ko":"대시보드","en":"Dashboard","ja":"ダッシュボード"}` | 메뉴 이름 (다국어 JSON) |
| slug | string | `admin-dashboard` | 메뉴 슬러그 |
| url | string | `/admin/dashboard` | 메뉴 URL |
| icon | string | `fas fa-tachometer-alt` | 메뉴 아이콘 |
| order | integer | `1` | 메뉴 순서 |
| is_active | boolean | `true` | active 여부 |
| children | array | `[]` | 하위 항목 배열 (계층 트리 — children 관계 파생) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.read`)이 없는 경우 |

<!-- @generated:end -->

**설명**

전체 메뉴를 계층 구조로 반환한다. `MenuService::getMenuHierarchy()` 로 조회한 메뉴를 `MenuCollection::toNavigationArray()` 로 변환해 최상위 메뉴 + `children` 중첩 형태로 내려준다. `active` 와 달리 사용자 역할 기반 접근 필터 없이 메뉴 트리 전체를 노출하므로, 관리자 메뉴 관리 화면의 트리 표현·순서 편집 기준 데이터로 사용된다. 인증 계약: `auth:sanctum` + `permission:core.menus.read`.


### PUT /api/admin/menus/order
<!-- @generated:start:api.admin.menus.update-order -->
- **라우트명**: `api.admin.menus.update-order`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@updateOrder`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| parent_menus | body | array | 예 | min 1 | 최상위 메뉴의 새 순서 목록 (원소 `{id, order}` — id: 대상 메뉴, order: 1 이상 표시 순번) |
| child_menus | body | array | 아니오 | — | 부모별 하위 메뉴의 새 순서 목록 (부모 그룹핑된 2차원 배열, 각 원소 `{id, order}` — id: 하위 메뉴, order: 1 이상 표시 순번) |
| moved_items | body | array | 아니오 | — | 부모가 바뀐 항목 목록 (원소 `{id, new_parent_id}` — new_parent_id 는 새 부모 메뉴 ID 또는 null(최상위 이동), 순환 참조(자기 자신·자손 지정) 시 거부) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.menu.update_order_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명**

관리자 메뉴 관리 화면의 드래그 앤 드롭 순서 변경을 반영한다. `parent_menus` 는 `{id, order}` 배열로 최상위 메뉴 순서를, `child_menus` 는 부모별 하위 메뉴 순서를, `moved_items` 는 부모가 바뀐 항목(`{id, new_parent_id}`)을 전달한다. `moved_items` 의 `new_parent_id` 에는 순환 참조 방지(`NotCircularParent`) 검증이 적용되어, 자기 자신이나 자손을 부모로 지정하면 거부된다. 각 id/parent_id 는 실제 메뉴로 존재해야 한다. 응답은 성공 메시지만 반환한다. 인증 계약: `auth:sanctum` + `permission:core.menus.update`.


### DELETE /api/admin/menus/{menu}
<!-- @generated:start:api.admin.menus.destroy -->
- **라우트명**: `api.admin.menus.destroy`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@destroy`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.delete`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| menu | path | string | 예 | — | 대상 menu의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.delete`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

지정한 메뉴를 삭제한다. `{menu}` 는 라우트 모델 바인딩으로 해석되며, 삭제는 `MenuService::deleteMenu()` 가 수행한다. 성공 시 `menu.delete_success` 메시지만 반환하고, 삭제 불가 조건은 `422 menu.delete_failed` 로 내려온다. 관리자 메뉴 관리 화면의 메뉴 삭제 동작에서 소비된다. 인증 계약: `auth:sanctum` + `permission:core.menus.delete`.


### GET /api/admin/menus/{menu}
<!-- @generated:start:api.admin.menus.show -->
- **라우트명**: `api.admin.menus.show`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@show`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| menu | path | string | 예 | — | 대상 menu의 식별자 |

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `33` | 기본 키 (내부 식별자) |
| name | object | `{"ko":"API 문서 샘플 메뉴","en":"API Doc Sample Menu"}` | 메뉴 이름 (다국어 JSON) |
| slug | string | `apidoc-sample-menu` | 메뉴 슬러그 |
| url | string | `/admin/apidoc-sample` | 메뉴 URL |
| icon | string | `fas fa-book` | 메뉴 아이콘 |
| order | integer | `28` | 메뉴 순서 |
| is_active | boolean | `true` | active 여부 |
| parent_id | null | `null` | 상위 메뉴 ID |
| extension_type | null | `null` | 확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의) |
| extension_identifier | null | `null` | 확장 식별자 (예: core, sirsoft-board, sirsoft-payment) |
| parent | null | `null` | 상위 항목 객체 (parent 관계 파생) |
| children | array | `[{"id":34,"name":{"ko":"하위 메뉴","en":"Child Menu"},"slug":…` | 하위 항목 배열 (계층 트리 — children 관계 파생) |
| creator | object | `{"uuid":"a231747f-e82e-4cf2-9ae1-a261849dce40","name":"AP…` | 생성자 정보 객체 (uuid/name/email — creator 관계 파생) |
| roles | array | `[]` | 이 메뉴 노출이 허용된 역할 목록 (원소 id/name/permission_type — roles 관계 파생, permission_type 은 pivot 의 노출 권한 유형) |
| created_at | string | `2026-07-06 19:15:16` | 생성 일시 |
| updated_at | string | `2026-07-06 19:15:16` | 최종 수정 일시 |
| is_owner | boolean | `true` | 현재 인증 사용자가 이 리소스의 소유자인지 여부 (BaseApiResource 표준 메타) |
| abilities | object | `{"can_create":true,"can_update":true,"can_delete":true}` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

단일 메뉴의 상세 정보를 조회한다. `{menu}` 는 라우트 모델 바인딩으로 해석되며, `creator`/`parent`/`children`/`roles` 관계를 eager-load 해 함께 반환한다. `children` 에는 하위 메뉴가 order 오름차순으로, 각 하위 메뉴의 `roles` 까지 포함된다. 관리자 메뉴 관리 화면의 메뉴 편집 폼 초기값 로딩에 사용된다. 인증 계약: `auth:sanctum` + `permission:core.menus.read`.


### PUT /api/admin/menus/{menu}
<!-- @generated:start:api.admin.menus.update -->
- **라우트명**: `api.admin.menus.update`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@update`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| menu | path | string | 예 | — | 대상 menu의 식별자 |
| name | body | string | 아니오 | — | 대상의 이름/명칭 |
| slug | body | string | 예 | max 255 | URL 친화 식별자 (slug) |
| url | body | string | 아니오 | max 500 | URL |
| icon | body | string | 아니오 | max 100 | 아이콘 |
| parent_id | body | integer | 아니오 | — | parent 식별자 |
| order | body | integer | 아니오 | min 0 | 표시 정렬 순서 값 (작을수록 우선) |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |
| extension_type | body | string | 아니오 | `core`, `module`, `plugin` | 확장 유형 (core/module/plugin/template) |
| extension_identifier | body | string | 아니오 | max 255 | 확장 식별자 |
| roles | body | array | 아니오 | — | 이 메뉴 노출을 허용할 역할 ID 배열 (각 원소는 존재하는 role id — 지정 시 노출 허용 역할 목록으로 설정/교체) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.menu.update_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

기존 메뉴 정보를 수정한다. `{menu}` 는 라우트 모델 바인딩으로 해석되며, 전달된 필드만 갱신한다. `slug` 는 여전히 유일해야 하고, `roles` 배열을 보내면 이 메뉴 노출이 허용된 역할 목록을 교체한다. 성공 시 갱신된 메뉴를 관계 eager-load 포함해 `MenuResource` 로 반환하고, 실패 시 `menu.update_failed`(422 검증 오류 포함)를 반환한다. 관리자 메뉴 관리 화면의 메뉴 편집 폼 저장에서 소비된다. 인증 계약: `auth:sanctum` + `permission:core.menus.update`.


### PATCH /api/admin/menus/{menu}/toggle-status
<!-- @generated:start:api.admin.menus.toggle-status -->
- **라우트명**: `api.admin.menus.toggle-status`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\MenuController@toggleStatus`
- **인증/권한**: `auth:sanctum` + `permission:core.menus.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| menu | path | string | 예 | — | 대상 menu의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.menus.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명**

지정한 메뉴의 활성화 상태(`is_active`)를 반대 값으로 토글한다. `{menu}` 는 라우트 모델 바인딩으로 해석되며, 본문 파라미터 없이 현재 상태를 뒤집는다. 성공 시 갱신된 메뉴를 `MenuResource` 로 반환한다. 관리자 메뉴 관리 화면의 활성/비활성 스위치에서 소비된다. 인증 계약: `auth:sanctum` + `permission:core.menus.update`.


