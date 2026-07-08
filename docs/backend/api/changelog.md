# Changelog API 레퍼런스

> **소유**: 코어 · **생성**: `php artisan api:docgen` (실측 기반). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## TL;DR (5초 요약)

```text
1. 이 문서는 실제 API 호출로 실측한 Changelog 엔드포인트 레퍼런스입니다
2. 각 엔드포인트: 메서드/URI/권한 + 요청 파라미터 표 + 실측 응답 필드 표
3. 응답 필드의 예시값은 실제 호출 응답에서 관측된 값입니다
4. 갱신: 코드 변경 후 php artisan api:docgen 재실행
5. 설명(TODO) 칸은 사람이 채웁니다
```

---


### GET /api/admin/changelog
<!-- @generated:start:api.admin.changelog -->
- **라우트명**: `api.admin.changelog`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\LicenseController@changelog`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

_요청 파라미터 없음._

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| content | string | `# Changelog  이 프로젝트의 모든 주요 변경사항을 기록합니…` | 본문 내용 |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |

<!-- @generated:end -->

**설명** 코어의 `CHANGELOG.md` 파일 원문 텍스트를 `content`로 반환합니다. `auth:sanctum` 인증이 필요하며, 파일이 없으면 404(`common.not_found`)를 반환합니다. 관리자 화면에서 코어의 전체 변경 이력을 마크다운 원문 그대로 표시하는 용도로 사용합니다.


