# Core Update API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Core Update 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/core-update/changelog
<!-- @generated:start:api.admin.core-update.changelog -->
- **라우트명**: `api.admin.core-update.changelog`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\CoreUpdateController@changelog`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| source | query | string | 아니오 | `active`, `bundled`, `github` | 어느 위치의 CHANGELOG를 조회할지 지정 (active: 활성 설치본, bundled: 번들 원본, github: 원격 릴리스). 미지정 시 기본 조회 경로를 사용 |
| from_version | query | string | 아니오 | — | 시작 버전 (범위 하한) |
| to_version | query | string | 아니오 | — | 대상 버전 (범위 상한) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.extension.changelog_rules`).

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| changelog | array | `[{"version":"7.0.2","date":"2026-07-05","categories":[{"n…` | 변경 이력 텍스트 (원격/파일 CHANGELOG 본문) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 코어의 버전별 변경사항(CHANGELOG)을 구조화된 배열로 조회합니다. `source`(active/bundled/github)로 어느 위치의 CHANGELOG를 읽을지, `from_version`/`to_version`으로 조회 범위를 지정합니다. `core.settings.read` 권한이 필요하며, 업데이트 안내 화면에서 새 버전에 무엇이 바뀌는지 보여줄 때 사용합니다. 확장은 `core.extension.changelog_rules` 훅으로 파라미터를 확장할 수 있습니다.


### POST /api/admin/core-update/check
<!-- @generated:start:api.admin.core-update.check -->
- **라우트명**: `api.admin.core-update.check`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\CoreUpdateController@checkForUpdates`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| update_available | boolean | 업데이트 가능 여부 (최신 버전이 현재 버전보다 높으면 true) |
| current_version | string | 현재 설치된 코어 버전 |
| latest_version | string | GitHub 릴리스에서 확인한 최신 버전 (조회 실패 시 현재 버전으로 대체) |
| github_url | string | 버전 확인 대상 GitHub 저장소 URL (`config('app.update.github_url')`) |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |

<!-- @generated:end -->

**설명** GitHub 릴리스를 기준으로 코어 업데이트 가능 여부를 확인합니다. 현재 버전과 최신 버전을 비교한 결과를 반환하며, 조회에 실패하면 실패 사유·현재 버전·github_url과 함께 422를 반환합니다. `core.settings.update` 권한이 필요하고, 관리자가 업데이트 확인 버튼을 눌러 새 버전 유무를 점검하는 시나리오에 사용합니다.


