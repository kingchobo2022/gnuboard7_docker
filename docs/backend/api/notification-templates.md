# Notification Templates API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Notification Templates 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### POST /api/admin/notification-templates/preview
<!-- @generated:start:api.admin.notification-templates.preview -->
- **라우트명**: `api.admin.notification-templates.preview`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationTemplateController@preview`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| definition_id | body | integer | 예 | — | definition 식별자 |
| subject | body | array | 예 | — | 제목 |
| body | body | array | 예 | — | 본문 |
| locale | body | string | 아니오 | max 10 | 로케일 코드 (표시 언어/지역) |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 저장 전 알림 템플릿의 렌더링 결과를 미리 확인합니다. `definition_id` 와 다국어 `subject`/`body`, 선택적 `locale` 을 받아 샘플 변수로 치환된 제목·본문을 반환합니다. 인증(`auth:sanctum`)과 `core.settings.read` 권한이 필요하며, 실제 발송이나 저장은 일어나지 않습니다. 템플릿 편집 화면에서 변수 치환 결과를 실시간으로 확인할 때 사용합니다.


### PUT /api/admin/notification-templates/{template}
<!-- @generated:start:api.admin.notification-templates.update -->
- **라우트명**: `api.admin.notification-templates.update`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationTemplateController@update`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| template | path | string | 예 | — | 대상 template의 식별자 |
| subject | body | array | 예 | — | 제목 |
| body | body | array | 예 | — | 본문 |
| click_url | body | string | 아니오 | max 500 | 알림 클릭 시 이동할 대상 URL (미설정 시 이동 없음) |
| recipients | body | array | 아니오 | — | 수신자 규칙 목록. 각 원소는 type(trigger_user: 이벤트 유발 사용자, related_user: 연관 사용자, role: 역할 대상, specific_users: 지정 사용자), value(대상 식별값), relation(연관 사용자 관계명), exclude_trigger_user(유발 사용자 제외 여부)로 구성 |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있습니다 (`core.notification_template.filter_update_rules`).

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

**설명** 단일 채널 알림 템플릿의 다국어 제목(`subject`)·본문(`body`)과 클릭 URL, 수신자(`recipients`), 활성 상태를 수정합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. `template` 경로 파라미터로 대상을 지정하며, 확장이 `core.notification_template.filter_update_rules` 훅으로 추가 파라미터를 검증에 넣을 수 있습니다. 관리자가 특정 채널의 알림 문구를 편집해 저장할 때 사용합니다.


### POST /api/admin/notification-templates/{template}/reset
<!-- @generated:start:api.admin.notification-templates.reset -->
- **라우트명**: `api.admin.notification-templates.reset`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationTemplateController@reset`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| template | path | string | 예 | — | 대상 template의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 단일 채널 템플릿을 소속 정의의 기본값 데이터로 복원합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. 소속 정의가 없으면 404, 해당 채널의 기본 데이터가 없으면 404 를 반환합니다. 편집한 문구를 버리고 기본값 하나만 되돌릴 때 사용하며, 정의 전체를 복원하는 정의 reset 과 달리 대상 템플릿에만 적용됩니다.


### PATCH /api/admin/notification-templates/{template}/toggle-active
<!-- @generated:start:api.admin.notification-templates.toggle-active -->
- **라우트명**: `api.admin.notification-templates.toggle-active`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\NotificationTemplateController@toggleActive`
- **인증/권한**: `auth:sanctum` + `permission:core.settings.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| template | path | string | 예 | — | 대상 template의 식별자 |

**응답 필드** (`data` 내부)

<!-- 실측 제외: write-method — 응답 필드는 사람이 작성하세요. -->

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.settings.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 단일 채널 알림 템플릿의 활성 상태(`is_active`)를 현재 값의 반대로 토글합니다. 인증(`auth:sanctum`)과 `core.settings.update` 권한이 필요합니다. 비활성 템플릿은 해당 채널로의 발송이 중단되므로, 정의는 유지한 채 특정 채널만 켜거나 끌 때 사용합니다.


