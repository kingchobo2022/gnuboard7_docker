# Modules API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Modules 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/modules
<!-- @generated:start:api.admin.modules.index -->
- **라우트명**: `api.admin.modules.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@index`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read|core.menus.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| search | query | string | 아니오 | max 255 | 검색어 (지정한 검색 대상 필드에서 부분 일치) |
| filters | query | array | 아니오 | max 10 | 추가 필터 조건 맵 (필드별 조건) |
| status | query | string | 아니오 | `installed`, `not_installed`, `active`, `inactive` | 상태 필터 (해당 상태의 항목만 조회) |
| with | query | array | 아니오 | max 5 | 함께 포함할 추가 데이터 옵션 목록 (허용값 `custom_menus` — 각 모듈의 커스텀 메뉴 데이터 포함) |
| per_page | query | integer | 아니오 | min 1, max 100 | 페이지당 항목 수 |
| page | query | integer | 아니오 | min 1 | 조회할 페이지 번호 (1부터 시작) |
| include_hidden | query | boolean | 아니오 | — | manifest `hidden=true` 로 표시된 숨김 확장까지 목록에 포함할지 여부 (기본 제외) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.index_validation_rules`).

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| identifier | string | `sirsoft-board` | 모듈 고유 식별자 (vendor-module 형식) |
| vendor | string | `sirsoft` | 벤더/개발자명 |
| name | string | `게시판` | 모듈 이름 (다국어 JSON) |
| version | string | `1.0.0` | 모듈 버전 |
| description | string | `게시판 관리를 위한 모듈` | 모듈 설명 (다국어 JSON) |
| dependencies | array | `[]` | 의존하는 확장 맵 (manifest 파생 — {modules, plugins}) |
| status | string | `active` | 상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중) |
| assets | object | `{"js":"\/api\/modules\/assets\/sirsoft-ecommerce\/dist\/j…` | 프론트엔드 에셋 매니페스트 (manifest 파생 — js/css 진입점·로딩 전략) |
| update_available | boolean | `false` | 최신 버전 대비 업데이트 가능 여부 |
| update_source | null | `null` | 업데이트 감지 출처 (github, bundled 등) |
| latest_version | string | `1.0.0` | 감지된 최신 배포 버전 |
| file_version | string | `1.0.0` | 설치된 파일의 manifest 버전 |
| github_url | string | `https://github.com/gnuboard/g7-module…` | GitHub 저장소 URL |
| github_changelog_url | string | `https://github.com/gnuboard/g7-module…` | GitHub 변경 내역 URL |
| is_pending | boolean | `false` | _pending 대기소에 있어 설치 대기 중인지 여부 |
| is_bundled | boolean | `false` | 코어에 선탑재된 번들 확장인지 여부 |
| deactivated_reason | null | `null` | 비활성화 사유: manual(사용자 수동) \| incompatible_core(코어 버전 호환성) \| null(active) |
| deactivated_at | null | `null` | deactivated 일시 |
| incompatible_required_version | null | `null` | 요구 코어 버전 미충족 시 필요한 버전 (호환되면 null) |
| abilities | object | `{"can_install":true,"can_activate":true,"can_uninstall":t…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read|core.menus.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 설치된 모듈과 미설치 모듈을 모두 포함한 전체 모듈 목록을 페이지네이션으로 조회합니다. `search` 는 이름·식별자·설명·벤더에 대한 OR 검색이고 `filters` 는 AND 조건으로 적용되며, `with[]` 에 `custom_menus` 를 지정하면 커스텀 메뉴 데이터를 함께 포함합니다. `core.modules.read` 또는 `core.menus.read` 권한 중 하나가 필요하고, 응답의 `abilities` 는 현재 사용자의 수행 가능 작업 맵을 담습니다. 관리자 모듈 관리 화면의 목록 그리드를 구성하는 기본 엔드포인트입니다.


### POST /api/admin/modules/activate
<!-- @generated:start:api.admin.modules.activate -->
- **라우트명**: `api.admin.modules.activate`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@activate`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.activate`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| module_name | body | string | 예 | max 255 | module 이름 (식별자) |
| force | body | boolean | 아니오 | — | 강제 실행 여부 (안전 확인/선행 검사 우회) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.activate_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.activate`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 설치된 모듈을 활성화합니다. `core.modules.activate` 권한이 필요합니다. `force` 없이 호출했을 때 필요한 의존 확장이 충족되지 않으면 409 응답으로 `missing_modules`·`missing_plugins` 목록과 함께 경고를 반환하므로, 사용자 확인 후 `force: true` 로 재요청해야 합니다. 재활성화 시 cascade 로 함께 비활성화됐던 번들 언어팩 목록이 `pending_language_packs` 로 응답에 포함됩니다.


### POST /api/admin/modules/check-updates
<!-- @generated:start:api.admin.modules.check-updates -->
- **라우트명**: `api.admin.modules.check-updates`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@checkUpdates`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |

<!-- @generated:end -->

**설명** 설치된 모든 모듈에 대해 GitHub·번들 소스를 조회하여 새 버전 배포 여부를 일괄 확인합니다. `core.modules.install` 권한이 필요합니다. 파라미터 없이 호출하며, 각 모듈의 업데이트 가능 여부와 감지된 최신 버전 정보를 반환합니다. 모듈 목록 화면 진입 시 업데이트 뱃지를 갱신하는 용도로 사용됩니다.


### POST /api/admin/modules/deactivate
<!-- @generated:start:api.admin.modules.deactivate -->
- **라우트명**: `api.admin.modules.deactivate`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@deactivate`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.activate`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| module_name | body | string | 예 | max 255 | module 이름 (식별자) |
| force | body | boolean | 아니오 | — | 강제 실행 여부 (안전 확인/선행 검사 우회) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.deactivate_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.activate`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 활성 모듈을 비활성화합니다. `core.modules.activate` 권한이 필요합니다. `force` 없이 호출했을 때 이 모듈에 의존하는 템플릿·모듈·플러그인이 있으면 409 응답으로 `dependent_templates`·`dependent_modules`·`dependent_plugins` 목록과 함께 경고를 반환합니다. 의존 관계 확인 후 `force: true` 로 강제 비활성화할 수 있습니다.


### POST /api/admin/modules/install
<!-- @generated:start:api.admin.modules.install -->
- **라우트명**: `api.admin.modules.install`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@install`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| module_name | body | string | 예 | max 255 | module 이름 (식별자) |
| vendor_mode | body | string | 아니오 | `auto`, `composer`, `bundled` | 벤더 설치 모드 (auto/composer/bundled) |
| dependencies | body | array | 아니오 | — | 함께 설치할 의존 확장 목록 (cascade 1단계). 각 원소는 `type`(module\|plugin)·`identifier` 로 구성하며, install-preview 응답에서 사용자가 선택한 항목 |
| language_packs | body | array | 아니오 | — | 함께 설치할 번들 언어팩 식별자 목록 (cascade 2단계, best-effort). 원소는 언어팩 식별자 문자열 |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.install_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** `_pending`·`_bundled` 대기소에 있는 모듈을 활성 디렉토리로 설치합니다. `core.modules.install` 권한이 필요합니다. `vendor_mode` 로 Composer 의존성 설치 방식을(auto/composer/bundled) 지정하며, 요청 본문의 `dependencies` 로 선택한 의존 확장을 먼저 설치(cascade 1단계, 실패 시 전체 중단)한 뒤 `language_packs` 로 지정한 번들 언어팩을 best-effort 로 함께 설치합니다(cascade 2단계). 성공 시 201 상태로 반환하고, 언어팩 설치 실패는 응답의 `language_pack_failures` 에 담깁니다.


### POST /api/admin/modules/install-from-file
<!-- @generated:start:api.admin.modules.install-from-file -->
- **라우트명**: `api.admin.modules.install-from-file`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@installFromFile`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| file | body | file | 예 | max 51200 | 업로드 파일 |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.install_from_file_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 업로드된 ZIP 파일에서 모듈을 설치합니다. `core.modules.install` 권한이 필요하며, 파일은 최대 50MB(51200KB)까지 허용됩니다. ZIP 압축 해제 후 module.json 검증을 거쳐 설치하며, 성공 시 201 상태로 설치된 모듈 정보를 반환합니다. 설치 전 manifest 만 미리 확인하려면 `manifest-preview` 를 먼저 호출하는 것이 안전합니다.


### POST /api/admin/modules/install-from-github
<!-- @generated:start:api.admin.modules.install-from-github -->
- **라우트명**: `api.admin.modules.install-from-github`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@installFromGithub`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| github_url | body | string | 예 | — | GitHub 저장소 URL |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.install_from_github_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** GitHub 저장소 URL 에서 모듈을 내려받아 설치합니다. `core.modules.install` 권한이 필요합니다. `github_url` 로 지정한 공개 저장소의 릴리스/소스를 받아 압축 해제·검증 후 설치하며, 성공 시 201 상태로 설치된 모듈 정보를 반환합니다.


### GET /api/admin/modules/installed
<!-- @generated:start:api.admin.modules.installed -->
- **라우트명**: `api.admin.modules.installed`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@installed`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| identifier | string | `sirsoft-board` | 모듈 고유 식별자 (vendor-module 형식) |
| vendor | string | `sirsoft` | 벤더/개발자명 |
| name | string | `게시판` | 모듈 이름 (다국어 JSON) |
| version | string | `1.0.0` | 모듈 버전 |
| description | string | `게시판 관리를 위한 모듈` | 모듈 설명 (다국어 JSON) |
| dependencies | array | `[]` | 의존하는 확장 맵 (manifest 파생 — {modules, plugins}) |
| status | string | `active` | 상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중) |
| assets | object | `{"js":"\/api\/modules\/assets\/sirsoft-ecommerce\/dist\/j…` | 프론트엔드 에셋 매니페스트 (manifest 파생 — js/css 진입점·로딩 전략) |
| update_available | boolean | `false` | 최신 버전 대비 업데이트 가능 여부 |
| update_source | null | `null` | 업데이트 감지 출처 (github, bundled 등) |
| latest_version | string | `1.0.0` | 감지된 최신 배포 버전 |
| file_version | string | `1.0.0` | 설치된 파일의 manifest 버전 |
| github_url | string | `https://github.com/gnuboard/g7-module…` | GitHub 저장소 URL |
| github_changelog_url | string | `https://github.com/gnuboard/g7-module…` | GitHub 변경 내역 URL |
| is_pending | boolean | `false` | _pending 대기소에 있어 설치 대기 중인지 여부 |
| is_bundled | boolean | `false` | 코어에 선탑재된 번들 확장인지 여부 |
| deactivated_reason | null | `null` | 비활성화 사유: manual(사용자 수동) \| incompatible_core(코어 버전 호환성) \| null(active) |
| deactivated_at | null | `null` | deactivated 일시 |
| incompatible_required_version | null | `null` | 요구 코어 버전 미충족 시 필요한 버전 (호환되면 null) |
| abilities | object | `{"can_install":true,"can_activate":true,"can_uninstall":t…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |

<!-- @generated:end -->

**설명** 현재 설치된 모듈만 조회합니다(미설치 항목 제외). 이 엔드포인트는 세부 권한 미들웨어 없이 `auth:sanctum` 인증만 요구하므로, 다른 화면이 활성/설치된 모듈 목록을 참조할 때 사용하는 경량 조회 API 입니다. 페이지네이션 없이 설치된 항목 배열을 반환합니다.


### POST /api/admin/modules/manifest-preview
<!-- @generated:start:api.admin.modules.manifest-preview -->
- **라우트명**: `api.admin.modules.manifest-preview`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@manifestPreview`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| file | body | file | 예 | max 51200 | 업로드 파일 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 업로드된 ZIP 파일의 module.json manifest 와 검증 결과만 추출합니다(실제 설치는 수행하지 않음). `core.modules.install` 권한이 필요하며 파일은 최대 50MB 까지 허용됩니다. 설치 모달에서 사용자가 파일 선택 직후 manifest 유효성과 검증 실패 사유를 미리 확인하는 용도이며, 검증 오류 시 422 로 사유를 반환합니다.


### POST /api/admin/modules/refresh-layouts
<!-- @generated:start:api.admin.modules.refresh-layouts -->
- **라우트명**: `api.admin.modules.refresh-layouts`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@refreshLayouts`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.activate`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| module_name | body | string | 예 | max 255 | module 이름 (식별자) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.refresh_layouts_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.activate`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 모듈의 레이아웃 파일을 파일에서 다시 읽어 DB 에 동기화합니다. `core.modules.activate` 권한이 필요합니다. 파일에서 변경된 레이아웃은 갱신되고 삭제된 레이아웃은 DB 에서도 제거되며, 갱신된 모듈 정보를 반환합니다. 모듈의 `_bundled` 레이아웃 JSON 을 수정한 뒤 재빌드 없이 반영할 때 사용합니다.


### DELETE /api/admin/modules/uninstall
<!-- @generated:start:api.admin.modules.uninstall -->
- **라우트명**: `api.admin.modules.uninstall`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@uninstall`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.uninstall`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| module_name | query | string | 예 | max 255 | module 이름 (식별자) |
| delete_data | query | boolean | 아니오 | — | 제거 시 모듈이 생성한 DB 데이터까지 함께 삭제할지 여부 (기본 false — 데이터 보존) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.uninstall_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.uninstall`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 모듈을 시스템에서 제거합니다. `core.modules.uninstall` 권한이 필요합니다. 활성 디렉토리만 삭제하고 `_bundled` 원본은 보존합니다. `delete_data: true` 인 경우 모듈이 생성한 DB 데이터까지 함께 삭제하며, 기본값은 데이터 보존입니다. 삭제될 데이터 범위는 사전에 `uninstall-info` 로 확인할 수 있습니다.


### GET /api/admin/modules/uninstalled
<!-- @generated:start:api.admin.modules.uninstalled -->
- **라우트명**: `api.admin.modules.uninstalled`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@uninstalled`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| identifier | string | `gnuboard7-hello_module` | 모듈 고유 식별자 (vendor-module 형식) |
| vendor | string | `gnuboard7` | 벤더/개발자명 |
| name | string | `Hello 모듈` | 모듈 이름 (다국어 JSON) |
| version | string | `0.1.0` | 모듈 버전 |
| description | string | `학습용 최소 샘플 모듈 (Memo CRUD)` | 모듈 설명 (다국어 JSON) |
| dependencies | array | `[]` | 의존하는 확장 맵 (manifest 파생 — {modules, plugins}) |
| status | string | `uninstalled` | 상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중) |
| assets | null | `null` | 프론트엔드 에셋 매니페스트 (manifest 파생 — js/css 진입점·로딩 전략) |
| update_available | boolean | `false` | 최신 버전 대비 업데이트 가능 여부 |
| update_source | null | `null` | 업데이트 감지 출처 (github, bundled 등) |
| latest_version | null | `null` | 감지된 최신 배포 버전 |
| file_version | null | `null` | 설치된 파일의 manifest 버전 |
| github_url | null | `null` | GitHub 저장소 URL |
| github_changelog_url | null | `null` | GitHub 변경 내역 URL |
| is_pending | boolean | `false` | _pending 대기소에 있어 설치 대기 중인지 여부 |
| is_bundled | boolean | `true` | 코어에 선탑재된 번들 확장인지 여부 |
| deactivated_reason | null | `null` | 비활성화 사유: manual(사용자 수동) \| incompatible_core(코어 버전 호환성) \| null(active) |
| deactivated_at | null | `null` | deactivated 일시 |
| incompatible_required_version | null | `null` | 요구 코어 버전 미충족 시 필요한 버전 (호환되면 null) |
| abilities | object | `{"can_install":true,"can_activate":true,"can_uninstall":t…` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |

<!-- @generated:end -->

**설명** 아직 설치되지 않은 모듈만 조회합니다(예: 번들로 제공되나 미설치 상태인 샘플 모듈). `core.modules.read` 권한이 필요합니다. 설치 가능한 모듈을 사용자에게 노출하는 화면에서 사용하며, 미설치 항목은 assets·latest_version 등 설치 후에만 채워지는 필드가 null 로 반환됩니다.


### GET /api/admin/modules/{identifier}/changelog
<!-- @generated:start:api.admin.modules.changelog -->
- **라우트명**: `api.admin.modules.changelog`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@changelog`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |
| source | query | string | 아니오 | `active`, `bundled`, `github` | 변경 내역 조회 출처 (active: 활성 설치본, bundled: 번들 원본, github: 원격 저장소) |
| from_version | query | string | 아니오 | — | 시작 버전 (범위 하한) |
| to_version | query | string | 아니오 | — | 대상 버전 (범위 상한) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.extension.changelog_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 특정 모듈의 변경 내역(CHANGELOG)을 조회합니다. `core.modules.read` 권한이 필요합니다. `source` 로 조회 출처를(active: 활성 설치본, bundled: 번들 원본, github: 원격 저장소) 선택하고, `from_version`·`to_version` 으로 버전 구간을 좁힐 수 있습니다. 업데이트 전 사용자에게 변경 사항을 안내하는 데 사용됩니다.


### GET /api/admin/modules/{identifier}/dependent-templates
<!-- @generated:start:api.admin.modules.dependent-templates -->
- **라우트명**: `api.admin.modules.dependent-templates`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@dependentTemplates`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 이 모듈에 의존하는 템플릿 목록을 조회합니다. `core.modules.read` 권한이 필요합니다. 응답으로 의존 템플릿 배열과 총 개수를 반환하며, 모듈 비활성화·제거 전 영향을 받는 템플릿을 사용자에게 미리 알리는 데 사용됩니다.


### GET /api/admin/modules/{identifier}/license
<!-- @generated:start:api.admin.modules.license -->
- **라우트명**: `api.admin.modules.license`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@license`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈에 포함된 라이선스 파일의 원문 내용을 반환합니다. `core.modules.read` 권한이 필요합니다. `identifier` 는 소문자·숫자·하이픈·언더스코어 형식만 허용되며 형식에 맞지 않거나 라이선스 파일이 없으면 404 를 반환합니다. 라이선스 고지 화면에 전문을 표시하는 용도입니다.


### GET /api/admin/modules/{moduleName}
<!-- @generated:start:api.admin.modules.show -->
- **라우트명**: `api.admin.modules.show`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@show`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| moduleName | path | string | 예 | — | 대상 module의 이름 (식별자) |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 특정 모듈의 상세 정보를 조회합니다. `core.modules.read` 권한이 필요합니다. 목록보다 자세한 `toDetailArray()` 형태를 반환하며, 이 모듈이 지원하는 번들 언어팩 정보가 함께 주입됩니다. 모듈을 찾을 수 없으면 404 를 반환합니다.


### GET /api/admin/modules/{moduleName}/check-modified-layouts
<!-- @generated:start:api.admin.modules.check-modified-layouts -->
- **라우트명**: `api.admin.modules.check-modified-layouts`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@checkModifiedLayouts`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| moduleName | path | string | 예 | — | 대상 module의 이름 (식별자) |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 특정 모듈에서 사용자가 수정한 레이아웃이 있는지 확인합니다. `core.modules.read` 권한이 필요합니다. 업데이트 실행 전 이 정보를 조회하여 레이아웃 전략(overwrite: 새 버전으로 교체, keep: 사용자 수정본 유지) 선택을 안내하는 데 사용됩니다.


### GET /api/admin/modules/{moduleName}/install-preview
<!-- @generated:start:api.admin.modules.install-preview -->
- **라우트명**: `api.admin.modules.install-preview`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@installPreview`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| moduleName | path | string | 예 | — | 대상 module의 이름 (식별자) |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈 설치 시 함께 처리될 cascade 후보(의존 확장 + 동반 가능한 번들 언어팩) 트리를 반환합니다. `core.modules.install` 권한이 필요합니다. 설치 모달 오픈 시 호출되어 사용자가 함께 설치할 항목을 선택하도록 노출하며, ZIP 업로드 기반의 `manifest-preview` 와 달리 이미 알려진 식별자에 대한 GET 조회입니다.


### GET /api/admin/modules/{moduleName}/uninstall-info
<!-- @generated:start:api.admin.modules.uninstall-info -->
- **라우트명**: `api.admin.modules.uninstall-info`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@uninstallInfo`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.uninstall`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| moduleName | path | string | 예 | — | 대상 module의 이름 (식별자) |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.uninstall`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈 제거 시 삭제될 데이터 정보를 조회합니다. `core.modules.uninstall` 권한이 필요합니다. 제거 확인 모달에서 사용자에게 어떤 데이터가 사라지는지 미리 보여주는 용도이며, 모듈을 찾을 수 없으면 404 를 반환합니다.


### POST /api/admin/modules/{moduleName}/update
<!-- @generated:start:api.admin.modules.update -->
- **라우트명**: `api.admin.modules.update`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\ModuleController@performUpdate`
- **인증/권한**: `auth:sanctum` + `permission:core.modules.install`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| moduleName | path | string | 예 | — | 대상 module의 이름 (식별자) |
| layout_strategy | body | string | 아니오 | `overwrite`, `keep` | 업데이트 시 레이아웃 처리 전략 (overwrite: 새 버전으로 교체, keep: 사용자 수정본 유지) |
| vendor_mode | body | string | 아니오 | `auto`, `composer`, `bundled` | 벤더 설치 모드 (auto/composer/bundled) |
| force | body | boolean | 아니오 | — | 강제 실행 여부 (안전 확인/선행 검사 우회) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.module.perform_update_validation_rules`).

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.modules.install`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 특정 모듈을 최신 버전으로 업데이트합니다. `core.modules.install` 권한이 필요합니다. `layout_strategy` 로 레이아웃 처리 방식을(overwrite: 새 버전으로 교체, keep: 사용자 수정본 유지) 지정하며, `vendor_mode` 로 Composer 의존성 처리 방식을 선택합니다. 버전 제약·호환성 문제로 막힐 경우 `force: true` 로 강제 진행할 수 있습니다.


### GET /api/modules/assets/{identifier}/{path}
<!-- @generated:start:api.public.modules.assets -->
- **라우트명**: `api.public.modules.assets`
- **컨트롤러**: `App\Http\Controllers\Api\Public\PublicModuleController@serveAsset`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |
| path | path | string | 예 | — | 경로 |
| identifier | query | string | 예 | — | 대상 확장/리소스의 식별자 |
| path | query | string | 예 | — | 경로 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈의 개별 프론트엔드 에셋 파일(JS/CSS/이미지 등)을 서빙하는 공개 엔드포인트입니다. 인증이 필요하지 않으며, 경로·확장자 보안 검증은 FormRequest 에서 완료됩니다. 모듈 미존재·파일 미존재·허용되지 않은 파일 유형은 각각 404/404/403 으로 응답하고, 정상 파일은 ETag 와 1년 캐시 헤더를 붙여 반환합니다. 소스맵 등 개별 에셋을 직접 참조할 때 사용되며, 통합 로딩은 `bundle.js`/`bundle.css` 를 사용합니다.


### GET /api/modules/bundle.css
<!-- @generated:start:api.public.modules.bundle.css -->
- **라우트명**: `api.public.modules.bundle.css`
- **컨트롤러**: `App\Http\Controllers\Api\Public\PublicModuleController@serveBundleCss`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

<!-- 실측 제외: http-200 — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

_대표 에러 없음 (공개 조회). 활성 모듈 에셋이 없어도 빈 200 응답을 반환하므로 404 를 내지 않습니다._

<!-- @generated:end -->

**설명** 활성 모듈들의 프론트엔드 CSS 를 서버에서 하나로 병합한 번들을 서빙하는 공개 엔드포인트입니다. 인증이 필요하지 않습니다. 활성 global 모듈 에셋이 없으면 빈 200(text/css) 응답을 반환하고, 있으면 병합 파일을 ETag·환경별 Cache-Control 과 함께 서빙합니다. 페이지가 모듈 스타일을 요청 1건으로 로드하도록 합니다.


### GET /api/modules/bundle.js
<!-- @generated:start:api.public.modules.bundle.js -->
- **라우트명**: `api.public.modules.bundle.js`
- **컨트롤러**: `App\Http\Controllers\Api\Public\PublicModuleController@serveBundleJs`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

<!-- 실측 제외: http-200 — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

_대표 에러 없음 (공개 조회). 활성 모듈 에셋이 없어도 빈 200 응답을 반환하므로 404 를 내지 않습니다._

<!-- @generated:end -->

**설명** 활성 모듈들의 프론트엔드 IIFE JS 를 서버에서 하나로 병합한 번들을 서빙하는 공개 엔드포인트입니다. 인증이 필요하지 않습니다. 활성 global 모듈 에셋이 없으면 빈 200(text/javascript) 응답을 반환하고(프론트는 빈 스크립트 로드로 무해), 있으면 병합 파일을 ETag·환경별 Cache-Control 과 함께 서빙합니다. 프론트는 `G7Config.bundleUrls` 를 읽어 이 번들을 로드합니다.


### GET /api/modules/{identifier}/components.json
<!-- @generated:start:api.public.modules.components -->
- **라우트명**: `api.public.modules.components`
- **컨트롤러**: `App\Http\Controllers\Api\Public\PublicModuleController@serveComponents`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈의 컴포넌트 정의 파일(components.json)을 서빙하는 공개 엔드포인트입니다. 인증이 필요하지 않습니다. 편집 모드 부팅 시 ComponentRegistry 가 활성 확장 매니페스트를 네임스페이스 병합하기 위해 fetch 하며, 구버전 모듈처럼 파일이 없으면 빈 components 로 폴백합니다. 응답은 1시간 캐시됩니다. 모듈 미존재 시 404.


### GET /api/modules/{identifier}/editor-spec
<!-- @generated:start:api.public.modules.editor_spec -->
- **라우트명**: `api.public.modules.editor_spec`
- **컨트롤러**: `App\Http\Controllers\Api\Public\PublicModuleController@serveEditorSpec`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| identifier | path | string | 예 | — | 대상 리소스의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: unresolved-path-param — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 모듈의 레이아웃 편집기 스펙(editor-spec.json)을 서빙하는 공개 엔드포인트입니다. 인증이 필요하지 않습니다. 활성 모듈만 대상으로 하며 활성 디렉토리 → `_bundled` 폴백 순으로 읽어 `data.spec` 형태로 반환합니다. 비활성·미존재 모듈은 404 이고, 편집기 스펙 파일을 작성하지 않은 경우 spec=null 로 정상 응답합니다.


