# KH Bounce QA Checklist

Use this runbook before enabling the plugin on a live site.

## 1. Templates & Copy
1. Review the quick-reference PNGs in `assets/previews/` (or share them with non-admin stakeholders) so everyone knows what each template should resemble.
2. Go to **Settings → KH Bounce** and switch between Classic, Minimal, and Edge layouts. Confirm the preview panes update and the frontend modal matches each selection.
3. Update title/body/CTA copy and ensure translations still wrap the strings.

## 2. Visibility Controls
1. With `Only on front page` enabled, verify the modal does not appear on other pages unless `?kh-bounce-test=1` is appended.
2. Disable `Display on mobile devices?` and confirm phones/tablets never show the modal (unless in QA mode). Re-enable to validate mobile rendering.
3. Toggle `QA Test Mode` and confirm admins (or anyone using `?kh-bounce-test=1`) immediately see the modal despite exit-intent/session gates.

## 3. Exit Intent & Frequency Capping
1. In a desktop browser, move the cursor toward the top chrome and confirm the modal fires once per session.
2. Refresh the page and ensure the modal stays hidden until sessionStorage is cleared (or test mode is on).
3. Press `ESC` or click outside the modal and confirm it dismisses cleanly.

## 4. Telemetry
1. Set Telemetry Mode to `CustomEvents` and watch DevTools → Event Listeners for the `khBounceEvent`. Verify `impression`, `dismiss`, and `conversion` events fire.
2. Set Telemetry Mode to `REST beacon` and confirm POST requests hit `/wp-json/kh-bounce/v1/event`. Hook into the `kh_bounce_telemetry` action to log payloads and ensure it fires.

## 5. Automation
1. Run `npm install && npm run build:css` to rebuild assets. (See README note about migrating Sass `@import` to `@use` soon.)
2. Run `composer install` to pull the dev PHPUnit stack, then `composer test` (ensuring `WP_PHPUNIT__DIR` points to your WordPress test suite).
3. Regenerate translations via `bin/make-pot.sh` once WP-CLI is on PATH.
