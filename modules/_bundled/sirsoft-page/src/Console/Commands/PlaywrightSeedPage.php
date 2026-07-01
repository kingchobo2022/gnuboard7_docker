<?php

namespace Modules\Sirsoft\Page\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Playwright E2E 용 페이지 도메인 시드 커맨드 (stub).
 *
 * 페이지 + 첨부 등 모듈 도메인 데이터를 발급하여 stdout 에 JSON 으로 반환한다. 본 stub 은
 * 시그니처와 보안 가드만 구현되어 있으며, 실제 시드 로직은 페이지 모듈 후속 작업 세션에서
 * Factory 재사용으로 채워질 예정 (이커머스 PlaywrightSeedEcommerce 와 동일 패턴).
 *
 * 보안 가드 (코어 PlaywrightIssueToken 과 동일 3중 패턴):
 *   ① CLI 한정 — `php_sapi_name() === 'cli'`
 *   ② G7_PLAYWRIGHT_BYPASS=1 환경변수 옵트인
 *   ③ APP_DEBUG=true inline override — production + debug=false 환경에서도 동작
 *
 * 호출 예시 (활성화 후):
 *   $env:G7_PLAYWRIGHT_BYPASS='1'; php artisan playwright:seed-page --pages=3 --json
 */
class PlaywrightSeedPage extends Command
{
    /**
     * 커맨드 이름 및 시그니처
     *
     * @var string
     */
    protected $signature = 'playwright:seed-page
        {--pages=0 : 발급할 페이지 수}
        {--attachments=0 : 페이지당 발급할 첨부 수}
        {--json : 결과를 JSON 으로 출력}';

    /**
     * 커맨드 설명
     *
     * @var string
     */
    protected $description = 'Playwright E2E 용 페이지 도메인 데이터 시드 (CLI + G7_PLAYWRIGHT_BYPASS 가드)';

    /**
     * 커맨드를 실행합니다.
     *
     * 본 stub 은 가드 통과 후 LogicException 을 throw 한다.
     * 페이지 모듈 후속 작업 세션에서 stub 본문을 실제 시드 로직으로 교체할 때까지 활성화 차단.
     *
     * @return int 종료 코드
     */
    public function handle(): int
    {
        // ① CLI 한정
        if (php_sapi_name() !== 'cli') {
            $this->error('CLI 전용 커맨드입니다.');

            return self::FAILURE;
        }

        // ② 명시 옵트인
        if (env('G7_PLAYWRIGHT_BYPASS') !== '1') {
            $this->error('G7_PLAYWRIGHT_BYPASS=1 환경변수가 필요합니다.');

            return self::FAILURE;
        }

        // ③ APP_DEBUG 강제 — SettingsServiceProvider 의 bypass 분기가 settings JSON 덮어쓰기 차단 상태
        Config::set('app.debug', true);

        // TODO (페이지 모듈 후속 작업 세션):
        //   - 페이지 N개 생성 (Factory 재사용)
        //   - 페이지당 첨부 N개 생성 (업로드 순서 검증용, order 1..N 부여)
        //   - 반환: {pageIds: [...], attachmentIds: [...]}
        // 개발자 전용 stub 메시지 — 사용자 노출 0 (본문 구현 시 즉시 삭제).
        throw new \LogicException( /* i18n:exempt — developer-only stub */
            'playwright:seed-page 시드 로직은 아직 구현되지 않았습니다. '
            .'대상 spec 을 test.describe.skip 로 유지하거나, 본 커맨드 본문을 실제 시드 로직으로 교체하세요.'
        );
    }
}
