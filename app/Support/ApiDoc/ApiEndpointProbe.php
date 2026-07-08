<?php

namespace App\Support\ApiDoc;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * API 엔드포인트 실측 프로브
 *
 * 임시 Sanctum 토큰을 발급해 실제 HTTP 요청으로 엔드포인트를 호출하고,
 * 실제 응답 JSON 에서 필드 스키마(키·타입·샘플값)를 관측합니다.
 * 쓰기 메서드는 기본적으로 실호출하지 않으며 GET/HEAD 만 read-only 로 실측합니다.
 */
class ApiEndpointProbe
{
    /**
     * @var string 실측 대상 기준 URL
     */
    private string $baseUrl;

    /**
     * @var string|null 발급된 임시 토큰 평문
     */
    private ?string $token = null;

    /**
     * @var string 임시 토큰 식별용 이름
     */
    private string $tokenName = 'api-docgen-probe';

    /**
     * @param  string|null  $baseUrl  기준 URL (null 이면 .env 의 APP_URL 직접 사용)
     */
    public function __construct(?string $baseUrl = null)
    {
        // config('app.url') 은 테스트 환경에서 override 될 수 있으므로(test.example.com 등),
        // 실측은 .env 의 APP_URL 을 우선 신뢰한다. 명시 인자가 있으면 그것을 최우선한다.
        $resolved = $baseUrl
            ?: (string) env('APP_URL')
            ?: (string) config('app.url');

        $this->baseUrl = rtrim($resolved, '/');
    }

    /**
     * 실측 기준 URL 을 반환합니다.
     *
     * @return string 기준 URL
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * 실측용 관리자 토큰을 발급합니다.
     *
     * @param  int|null  $userId  토큰 발급 대상 사용자 ID (null 이면 첫 관리자)
     * @return bool 발급 성공 여부
     */
    public function authenticate(?int $userId = null): bool
    {
        $user = $userId
            ? User::find($userId)
            : User::query()->orderBy('id')->first();

        if (! $user) {
            return false;
        }

        $this->cleanupTokens();
        $this->token = $user->createToken($this->tokenName)->plainTextToken;

        return true;
    }

    /**
     * GET 엔드포인트를 실호출하여 응답을 관측합니다.
     *
     * @param  string  $method  HTTP 메서드
     * @param  string  $uri  라우트 URI (path 파라미터 치환 완료된 실제 경로)
     * @return array{ok: bool, status: int|null, body: array<string, mixed>|null, skipped_reason: string|null}
     */
    public function probe(string $method, string $uri): array
    {
        $method = strtoupper($method);

        // 쓰기 메서드는 부수효과 위험으로 실호출하지 않는다 (정적 문서화로 대체).
        if (! in_array($method, ['GET', 'HEAD'], true)) {
            return ['ok' => false, 'status' => null, 'body' => null, 'skipped_reason' => 'write-method'];
        }

        // path 파라미터가 남아 있으면(치환 실패) 실호출 불가.
        if (Str::contains($uri, '{')) {
            return ['ok' => false, 'status' => null, 'body' => null, 'skipped_reason' => 'unresolved-path-param'];
        }

        if (! $this->token) {
            return ['ok' => false, 'status' => null, 'body' => null, 'skipped_reason' => 'no-token'];
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->token)
                ->acceptJson()
                ->timeout(15)
                ->get($this->baseUrl.$uri);

            $json = $response->json();

            return [
                'ok' => $response->successful() && is_array($json),
                'status' => $response->status(),
                'body' => is_array($json) ? $json : null,
                'skipped_reason' => null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'body' => null, 'skipped_reason' => 'request-failed: '.$e->getMessage()];
        }
    }

    /**
     * 발급한 임시 토큰을 정리합니다.
     */
    public function cleanup(): void
    {
        $this->cleanupTokens();
        $this->token = null;
    }

    /**
     * 실측용 토큰 레코드를 모두 삭제합니다.
     */
    private function cleanupTokens(): void
    {
        PersonalAccessToken::query()
            ->where('name', $this->tokenName)
            ->delete();
    }
}
