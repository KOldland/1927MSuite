import { test, expect } from '@playwright/test';
import path from 'path';

const testBaseUrl = 'http://example.test';
const restBase = `${testBaseUrl}/wp-json/khm/v1`;
const accountUrl = `${testBaseUrl}/account`;
const accountScriptPath = path.resolve(__dirname, '../../public/js/account.js');

const accountFixtureHtml = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>KHM Account Test Fixture</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <div id="khm-account-messages"></div>

  <div class="khm-membership-card" id="card-period">
    <span class="khm-badge">Active</span>
    <div class="khm-membership-actions">
      <button type="button" class="khm-button khm-button-cancel-period-end" data-level-id="42">
        Cancel at Period End
      </button>
    </div>
  </div>

  <div class="khm-membership-card" id="card-immediate">
    <span class="khm-badge">Active</span>
    <div class="khm-membership-actions">
      <button type="button" class="khm-button khm-button-cancel-now" data-level-id="84">
        Cancel Now
      </button>
    </div>
  </div>

  <div class="khm-membership-card" id="card-reactivate">
    <span class="khm-badge">Active (cancels soon)</span>
    <div class="khm-membership-actions">
      <button type="button" class="khm-button khm-button-reactivate" data-level-id="126">
        Reactivate Subscription
      </button>
    </div>
  </div>

  <div class="khm-membership-card" id="card-payment">
    <span class="khm-badge">Active</span>
    <div class="khm-membership-actions">
      <button type="button" class="khm-button khm-button-update-card-toggle" data-level-id="64">
        Update Card
      </button>
    </div>
    <div class="khm-update-card" id="khm-update-card-64" style="display:none;">
      <h5>Update Payment Method</h5>
      <div class="khm-card-element" id="khm-card-element-64"></div>
      <button type="button" class="khm-button khm-button-primary khm-button-save-card" data-level-id="64">
        Save Card
      </button>
    </div>
  </div>
</body>
</html>
`;

test.describe('Account subscription actions', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(({ restUrl }) => {
      (window as any).khmAccount = {
        restUrl,
        restNonce: 'nonce-123',
        stripeKey: 'pk_test_stub',
        confirmCancel: 'Are you sure?',
      };

      (window as any).__reloadCalls = 0;
      try {
        window.location.reload = () => {
          (window as any).__reloadCalls += 1;
        };
      } catch (err) {
        Object.defineProperty(window.location, 'reload', {
          value: () => {
            (window as any).__reloadCalls += 1;
          },
        });
      }

      window.confirm = () => true;

      (window as any).__stripeKeys = [];
      (window as any).__stripeMounts = [];
      (window as any).__stripeConfirmInvocations = [];
      (window as any).__stripeConfirmResult = {
        setupIntent: {
          payment_method: 'pm_test_token',
        },
      };

      window.Stripe = function (key: string) {
        (window as any).__stripeKeys.push(key);
        return {
          elements() {
            return {
              create(type: string) {
                return {
                  mount(selector: string) {
                    (window as any).__stripeMounts.push({ selector, type });
                  },
                };
              },
            };
          },
          async confirmCardSetup(clientSecret: string, options: unknown) {
            (window as any).__stripeConfirmInvocations.push({ clientSecret, options });
            return (window as any).__stripeConfirmResult;
          },
        };
      };
    }, { restUrl: restBase });

    await page.route(accountUrl, (route) => {
      return route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: accountFixtureHtml,
      });
    });

    await page.goto(accountUrl);
    await page.waitForFunction(() => typeof (window as any).jQuery !== 'undefined');
    await page.addScriptTag({ path: accountScriptPath });
  });

  test('cancelling at period end posts REST payload and updates UI', async ({ page }) => {
    const cancelPayloads: Array<Record<string, unknown>> = [];

    await page.route(`${restBase}/subscription/cancel`, async (route) => {
      const request = route.request();
      if (request.method() === 'POST') {
        cancelPayloads.push(request.postDataJSON() as Record<string, unknown>);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ success: true, message: 'Queued cancel.' }),
        });
        return;
      }
      await route.continue();
    });

    await page.click('#card-period .khm-button-cancel-period-end');
    await expect(page.locator('#khm-account-messages .khm-message.success')).toContainText('Queued cancel.');
    expect(cancelPayloads).toEqual([{ level_id: 42, at_period_end: true }]);
    await expect(page.locator('#card-period .khm-badge')).toHaveText(/cancels at period end/i);
  });

  test('cancelling immediately flags at_period_end false and removes actions', async ({ page }) => {
    const cancelPayloads: Array<Record<string, unknown>> = [];

    await page.route(`${restBase}/subscription/cancel`, async (route) => {
      const request = route.request();
      if (request.method() === 'POST') {
        cancelPayloads.push(request.postDataJSON() as Record<string, unknown>);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ success: true, message: 'Cancelled immediately.' }),
        });
        return;
      }
      await route.continue();
    });

    await page.click('#card-immediate .khm-button-cancel-now');
    await expect(page.locator('#khm-account-messages .khm-message.success')).toContainText('Cancelled immediately.');
    expect(cancelPayloads).toEqual([{ level_id: 84, at_period_end: false }]);
    await expect(page.locator('#card-immediate .khm-membership-actions')).toHaveCount(0);
  });

  test('reactivating subscription calls REST endpoint', async ({ page }) => {
    const reactivatePayloads: Array<Record<string, unknown>> = [];

    await page.route(`${restBase}/subscription/reactivate`, async (route) => {
      const request = route.request();
      if (request.method() === 'POST') {
        reactivatePayloads.push(request.postDataJSON() as Record<string, unknown>);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ success: true, message: 'Reactivated.' }),
        });
        return;
      }
      await route.continue();
    });

    await page.click('#card-reactivate .khm-button-reactivate');
    await expect(page.locator('#khm-account-messages .khm-message.success')).toContainText('Reactivated.');
    expect(reactivatePayloads).toEqual([{ level_id: 126 }]);
  });

  test('updating payment method performs setup intent then update', async ({ page }) => {
    const setupPayloads: Array<Record<string, unknown>> = [];
    const updatePayloads: Array<Record<string, unknown>> = [];

    await page.route(`${restBase}/payment-method/setup-intent`, async (route) => {
      const request = route.request();
      if (request.method() === 'POST') {
        setupPayloads.push(request.postDataJSON() as Record<string, unknown>);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            client_secret: 'cs_test_secret',
          }),
        });
        return;
      }
      await route.continue();
    });

    await page.route(`${restBase}/payment-method/update`, async (route) => {
      const request = route.request();
      if (request.method() === 'POST') {
        updatePayloads.push(request.postDataJSON() as Record<string, unknown>);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            message: 'Payment method updated.',
          }),
        });
        return;
      }
      await route.continue();
    });

    await page.click('#card-payment .khm-button-update-card-toggle');
    await expect(page.locator('#khm-update-card-64')).toBeVisible();

    await page.click('#card-payment .khm-button-save-card');
    await expect(page.locator('#khm-account-messages .khm-message.success')).toContainText('Payment method updated.');

    expect(setupPayloads).toEqual([{ level_id: 64 }]);
    expect(updatePayloads).toEqual([{ level_id: 64, payment_method_id: 'pm_test_token' }]);

    const confirmInvocations = await page.evaluate(() => (window as any).__stripeConfirmInvocations);
    expect(confirmInvocations).toHaveLength(1);
    expect(confirmInvocations[0].clientSecret).toBe('cs_test_secret');

    const mounts = await page.evaluate(() => (window as any).__stripeMounts);
    expect(mounts).toContainEqual({ selector: '#khm-card-element-64', type: 'card' });
  });
});
