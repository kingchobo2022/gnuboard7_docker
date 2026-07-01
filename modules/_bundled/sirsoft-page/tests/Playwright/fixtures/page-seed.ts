/**
 * 페이지 모듈 도메인 시드 fixture (stub).
 *
 * `playwright:seed-page` artisan 커맨드를 호출하여 도메인 데이터(페이지 + 첨부)를 발급받는다.
 * 본 stub 은 시드 커맨드가 LogicException 을 throw 하도록 구성되어 있어(시그니처/보안 가드만 구현)
 * 실제 사용 전에는 대상 spec 이 test.describe.skip 로 비활성화되어야 한다.
 *
 * 활성화 절차 (페이지 모듈 후속 작업 세션):
 *   1. PlaywrightSeedPage 의 handle() 본문을 LogicException 에서 실제 시드 로직으로 교체
 *      (Page 모델 + 첨부 Factory 재사용, 업로드 순서 검증용 첨부 N건 포함)
 *   2. 본 fixture 를 사용하는 spec 의 test.describe.skip 표기 제거
 *   3. teardown 시 cleanup 커맨드(예: playwright:cleanup-page) 호출 추가 권장
 */
import { test as base } from '@playwright/test';
import { execSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

// ESM 환경(package.json "type": "module")에서는 __dirname 미정의 → import.meta.url 로 재구성.
const __dirname = dirname(fileURLToPath(import.meta.url));

type SeedFixtures = {
  /** 시드된 페이지 도메인 데이터 (페이지 ID + 첨부 ID 배열) */
  seededPage: { pageIds: number[]; attachmentIds: number[] };
};

export const test = base.extend<SeedFixtures>({
  seededPage: async ({}, use) => {
    // 6단계 상위 = 코어 루트 (artisan 실행 cwd)
    const coreRoot = process.env.G7_ROOT || resolve(__dirname, '../../../../../../');
    const out = execSync('php artisan playwright:seed-page --pages=3 --attachments=3 --json', {
      cwd: coreRoot,
      encoding: 'utf-8',
      env: {
        ...process.env,
        G7_PLAYWRIGHT_BYPASS: '1',
      },
    });
    const seed = JSON.parse(out);
    await use(seed);
    // TODO: teardown 시 cleanup 커맨드 호출
  },
});

export { expect } from '@playwright/test';
