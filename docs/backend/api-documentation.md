# API 레퍼런스 문서 규정 (API Documentation)

> **관련 문서**: [routing.md](routing.md) | [api-resources.md](api-resources.md) | [response-helper.md](response-helper.md) | [validation.md](validation.md)

---

## TL;DR (5초 요약)

```text
1. 모든 API 엔드포인트는 레퍼런스 문서 필수 — 메서드/URI/파라미터/응답 필드 전수 기재
2. 위치: 코어 = docs/backend/api/, 확장 = {modules|plugins}/_bundled/{id}/docs/api/
3. 생성: php artisan api:docgen — 코드에서 추출한 스캐폴딩 + 사람이 서술 보강 (순수 수기 금지)
4. 추출 불가분(훅 주입 파라미터·동적 응답)은 <!-- TODO --> 마커 남기고 사람이 채움
5. Swagger/OpenAPI 도구 미사용 — 마크다운 레퍼런스 전용
```

---

## 목차

1. [왜 이 규정이 필요한가](#왜-이-규정이-필요한가)
2. [문서 위치 규칙](#문서-위치-규칙)
3. [표준 문서 포맷](#표준-문서-포맷)
4. [생성 커맨드 api:docgen](#생성-커맨드-apidocgen)
5. [문서 갱신 의무](#문서-갱신-의무)
6. [체크리스트](#체크리스트)

---

## 왜 이 규정이 필요한가

G7 의 REST API 는 라우트 `->name()` 규약은 있으나 엔드포인트별 공개 레퍼런스가 부재했다. 프론트엔드
(레이아웃 JSON `data_sources`)와 외부 통합 개발자가 소비하는 요청/응답 계약이 코드에만 존재해, 변경 시
소비처가 침묵 속에서 깨진다(이슈 #64 의 `data_source` `auth_required` 계약 변화 사고가 계기).

문서는 **코드에서 추출한 스캐폴딩 + 사람이 채운 서술의 하이브리드**로 유지한다. 674개 규모에서 완전 수기
문서는 반드시 drift 하고, 완전 자동 추출은 훅 주입 파라미터·동적 응답을 못 잡으므로 둘 다 단독으로는
불충분하다.

---

## 문서 위치 규칙

| 대상 | 문서 위치 | 예시 |
|------|----------|------|
| 코어 | `docs/backend/api/{도메인}.md` | `docs/backend/api/users.md` |
| 모듈 | `modules/_bundled/{id}/docs/api/{도메인}.md` | `modules/_bundled/sirsoft-ecommerce/docs/api/products.md` |
| 플러그인 | `plugins/_bundled/{id}/docs/api/{도메인}.md` | `plugins/_bundled/sirsoft-gdpr/docs/api/consents.md` |

확장 API 문서는 **확장이 소유**한다(코어에 모으지 않음). 확장을 배포/삭제하면 그 API 문서도 함께 이동한다.

도메인 그룹핑은 URI/라우트명 prefix 기준(`api.admin.users.*` → `users.md`)으로 커맨드가 자동 분류한다.

---

## 표준 문서 포맷

엔드포인트 1개당 아래 4개 구성(헤더 · 요청 파라미터 · 응답 필드 · 에러 응답)을 따른다.
`<!-- @generated:start -->` ~ `<!-- @generated:end -->` 사이는 `api:docgen` 이 재생성하는 추출 블록이며,
그 바깥의 사람 서술은 재생성 시 보존된다.

에러 응답 표는 라우트 메타에서 대표 상태코드를 자동 추론한다: 인증 필수(`auth:sanctum`)→401,
`admin`/`permission:` 요구→403, FormRequest 검증 규칙 존재→422, path 파라미터 존재→404.
`optional.sanctum`(선택 인증)은 401 을 유발하지 않는다. 도메인 특이 에러(409·429 등)는 사람이 보강한다.

```markdown
### GET /api/admin/users
<!-- @generated:start:api.admin.users.index -->
- **라우트명**: `api.admin.users.index`
- **컨트롤러**: `App\Http\Controllers\Api\Admin\UserController@index`
- **인증/권한**: `auth:sanctum` + `admin` + `permission:admin,core.users.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
|------|------|------|------|--------|------|
| keyword | query | string | 아니오 | — | <!-- TODO: 용도 --> |
| status | query | string | 아니오 | `active`, `dormant`, `withdrawn` | <!-- TODO: 용도 --> |

> 이 엔드포인트는 확장이 파라미터를 추가할 수 있음 (`core.user.search_validation_rules`).

**응답 필드** (`data` 내부)

| 필드 | 타입 | 용도/설명 |
|------|------|-----------|
| id | integer | <!-- TODO: 설명 --> |
| uuid | string | <!-- TODO: 설명 --> |

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`admin,core.users.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |
<!-- @generated:end -->

**설명** <!-- 사람이 작성: 이 엔드포인트의 용도, 주의사항, 예시 시나리오 -->

**응답 예시**

​```json
{ "success": true, "data": { }, "message": null, "error": null }
​```
```

### 응답 envelope 표준

모든 응답은 `ResponseHelper` 로 `{success, data, message, error}` 로 래핑된다(response-helper.md).
문서의 "응답 필드" 표는 이 envelope 의 `data` 내부 필드를 기재한다.

- 목록 응답 pagination: `BaseApiCollection::paginationMeta()` →
  `{current_page, last_page, per_page, total, from, to, has_more_pages}`
- 권한 메타: `BaseApiResource::resourceMeta()` → `is_owner` + `abilities.can_*`

### 파라미터 위치 판정

| 위치 | 판정 근거 |
|------|----------|
| `path` | URI 의 `{param}` 세그먼트 |
| `query` | GET/DELETE 요청의 FormRequest rule |
| `body` | POST/PUT/PATCH 요청의 FormRequest rule |

허용값은 FormRequest rule 의 `in:`, `max:`, `min:`, `Rule::in(...)`, `boolean`, `date` 등에서 유추한다.

---

## 생성 커맨드 api:docgen

```bash
# 코어 스캐폴딩 생성 (docs/backend/api/*.md)
php artisan api:docgen --scope=core

# 특정 확장 스캐폴딩 생성
php artisan api:docgen --scope=module:sirsoft-ecommerce
php artisan api:docgen --scope=plugin:sirsoft-gdpr

# 전체
php artisan api:docgen --scope=all

# 생성 없이 누락/drift 만 리포트 (하네스가 소비)
php artisan api:docgen --check

# 생성될 대상만 미리보기
php artisan api:docgen --scope=core --dry-run
```

동작 (실측 기반):

1. `route:list --json` 으로 API 라우트 전수 수집 (method·uri·name·middleware·action).
2. name prefix 로 소유 확장 판별 (`api.modules.{id}.*` / `api.plugins.{id}.*` / 그 외 코어) → 출력 파일 라우팅.
3. 컨트롤러 메서드의 FormRequest 타입힌트 → `rules()` 리플렉션 → 요청 파라미터 표 (타입·필수·허용값).
4. **실측**: 임시 Sanctum 토큰 발급 → 실제 요청 파라미터로 엔드포인트 호출 → **실제 응답 JSON** 관측.
   - GET/HEAD: 실호출(read-only). 목록이 비면 최소 시드 데이터 자동 생성 후 재호출.
   - 쓰기(POST/PUT/PATCH/DELETE): DB 트랜잭션 내 실행 후 롤백(응답 shape 만 관측, 영속 안 함).
   - 외부 부수효과(결제 PG·외부 인증 콜백·메일)가 있는 라우트: allowlist 로 실호출 제외 → 정적+예시 대체.
5. 실제 응답 JSON 의 키·타입·샘플값 → 응답 필드 표 + 응답 예시. `@generated` 블록만 갱신, 사람 서술 보존.
6. 실측 후 임시 토큰·시드 데이터 정리.

한계 / 보강:

- FormRequest 가 `HookManager::applyFilters` 로 규칙을 주입하는 경우(163개) 정적 리플렉션은 훅 주입분을
  못 읽는다 → 커맨드가 훅 필터 존재 시 주석을 남기고 사람이 보강. 단 **응답 필드는 실측이므로 훅으로
  병합된 응답 필드까지 실제로 포착**된다.
- `route:list`(=`RouteFacade::getRoutes()`)는 활성 확장만 노출한다. 명시 범위(`module:{id}`/`plugin:{id}`)로
  지정한 확장이 비활성/미설치여서 등록 라우트가 0건이면, 인벤토리가 그 확장의 번들 라우트 파일
  (`{modules|plugins}/_bundled/{id}/src/routes/api.php`)을 프로바이더와 동일한 prefix
  (`api/{modules|plugins}/{id}`)·name(`api.{modules|plugins}.{id}.`)·`api` 미들웨어 규약으로 로드해
  **정적 폴백 수집**한다. 이때 실측(HTTP 호출)은 불가하므로 응답 필드는 `<!-- 실측 제외 -->` + 정적
  추정으로 대체되며, 설치 후 `--seed` 실측으로 채운다. 폴백은 `api/` 로 시작하는 라우트만 대상이므로
  web(admin) 라우트는 자동 제외된다.
- 실측이 불가한 라우트(외부 의존·allowlist 제외)는 `<!-- 실측 제외: {사유} -->` 마커 + 정적 추정으로 대체.

### 확장 실측 샘플 시더 (`--seed`)

`--seed` 는 상세 GET 실측 시 응답 필드가 null 로 관측되는 것을 줄이기 위해, 도메인 대표 엔티티에
완전한 샘플 레코드를 멱등 시드한다. 코어 도메인은 `App\Support\ApiDoc\ApiDocSampleService` 가 담당한다.

확장은 자신의 도메인 샘플을 **확장이 소유**한다. `App\Contracts\ApiDoc\ApiDocSampleSeeder` 를 구현한
클래스를 규약 위치 `{확장 네임스페이스}\Support\ApiDoc\ApiDocSampleService`
(예: `Modules\Sirsoft\Page\Support\ApiDoc\ApiDocSampleService`, 파일은 `src/Support/ApiDoc/`)에 두면,
`api:docgen --scope=module:{id} --seed` 실행 시 커맨드가 자동으로 발견해 코어 시드 뒤에 병합한다.

- `seed()` 반환 맵의 키는 라우트 도메인 그룹명(`pages` 등), 값은 `{model, key, value}`
  (모델 FQCN·route key 이름·route key 값)이다.
- 이 맵은 상세 GET 의 path 파라미터 치환에 쓰인다. 라우트-모델 바인딩이 없는 확장 패턴
  (`show(int $id)`)도, 파라미터명이 도메인의 단수 리소스명과 일치하면(`pages/{page}`) 이 맵으로 실측된다.
  `{slug}`·`{hash}`·`{versionId}` 처럼 route key 가 다른 문자열/보조 파라미터는 폴백하지 않고 실측 제외된다.
- 확장에 새 PHP 클래스를 추가했으므로 `_bundled` 작업 후 `{type}:update {id} --force` 로 활성 디렉토리에
  반영해야 오토로드된다.

---

## 문서 갱신 의무

컨트롤러/라우트/FormRequest/Resource 를 추가·변경하면 대응 API 문서를 같은 변경 단위에서 갱신한다.

- 트리거: `app/Http/Controllers/**`, `routes/api.php`, `app/Http/Requests/**`, `app/Http/Resources/**`
  (+ 확장 대응 경로) 편집.
- 절차: 코드 변경 → `api:docgen --scope=...` 재실행 → `@generated` 블록 갱신 → 신규 TODO 서술 채움.
- 검증: `api:docgen --check` 로 drift 0 확인. audit 룰 `api-doc-coverage` 가 변경셋에 대응 문서
  동반 여부를 검사한다. severity 는 **대상별**로 부여된다 — 문서가 완비된 대상은 `error`(문서
  미동반 변경 차단), 진행 중 대상은 `warn`. 코어(`docs/backend/api/`)는 2026-07-08 완료로
  `error` 승격됨. 즉 코어 API 표면(`routes/api.php`·`app/Http/{Controllers,Requests,Resources}/**`)을
  변경하면서 코어 API 문서를 함께 갱신하지 않으면 세션 종료 시 차단된다. 나머지 확장은 문서 완비
  시 순차 승격된다(룰의 `ENFORCED_TARGETS`).

---

## 체크리스트

```text
□ 엔드포인트가 대응 위치(코어 docs/backend/api/ 또는 확장 docs/api/)에 문서화되었는가?
□ 요청 파라미터 표에 위치/타입/필수/허용값/용도가 모두 기재되었는가?
□ 응답 필드 표가 envelope 의 data 내부 기준으로 작성되었는가?
□ 훅 주입 파라미터가 있으면 주석 + 사람 보강이 되었는가?
□ TODO 마커가 모두 채워졌는가?
□ api:docgen --check 가 drift 0 인가?
```

---

## 관련 문서

- [routing.md](routing.md) - 라우트 네이밍/URL 규칙 (확장 URL 스킴은 `/api/modules/{module}/...`)
- [api-resources.md](api-resources.md) - 응답 필드/pagination/abilities 형태
- [response-helper.md](response-helper.md) - 응답 envelope 표준
- [validation.md](validation.md) - FormRequest rule → 파라미터 허용값 유추 근거
