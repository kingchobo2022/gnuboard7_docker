# Permissions API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Permissions 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/permissions
<!-- @generated:start:api.admin.permissions.index -->
- **라우트명**: `api.admin.permissions.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\PermissionController@index`
- **인증/권한**: `auth:sanctum` + `permission:core.permissions.read`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| admin | object | `{"label":"관리자 권한","icon":"cog","permissions":[{"id":1,"id…` | 관리자(admin) 타입 권한 그룹. `label`·`icon`(PermissionType::Admin 의 label()/icon() 산물)과 admin 타입으로 필터링된 권한 트리(`permissions`)를 담는다. |
| user | object | `{"label":"사용자 권한","icon":"user","permissions":[{"id":1,"i…` | 사용자(user) 타입 권한 그룹. `label`·`icon`(PermissionType::User 의 label()/icon() 산물)과 user 타입으로 필터링된 권한 트리(`permissions`)를 담는다. |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`core.permissions.read`)이 없는 경우 |

<!-- @generated:end -->

**설명**

시스템의 전체 권한을 계층형 트리로 조회한다. 역할 생성·편집 화면(`admin_role_form.json`)의 권한 선택 트리를 채우는 데 사용된다. 권한은 모듈 → 카테고리 → 개별 권한 순으로 중첩되며(각 노드의 `children`), 코어 권한이 먼저 오도록 정렬된다. 응답의 `permissions` 는 권한 타입별(admin/user)로 그룹화되고, 각 그룹은 `label`·`icon` 메타와 필터링된 권한 트리를 담는다. 함께 반환되는 `types`(권한 타입 목록), `default_type`(기본 탭), `scope_options`(scope_type 선택지: 전체/역할/본인)는 편집 UI 구성에 쓰인다. 리프 노드만 실제 부여 가능한 권한(`is_assignable`)이다. `core.permissions.read` 권한이 필요하다.


