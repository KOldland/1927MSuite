import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  reporter: [
    ['list'],
    ['junit', { outputFile: 'tests/reports/playwright-results.xml' }],
  ],
  use: {
    baseURL: 'http://example.test',
    headless: true,
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
  },
});
