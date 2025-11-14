# KH Bounce

Exit-intent modal inspired by the original wBounce experience, rebuilt for KH Marketing Suite.

## Features
- Three responsive templates (classic, minimal slide, edge spotlight) with SCSS source files and build scripts.
- Admin previews show live markup, contextual help tabs, and validation on required fields / URLs.
- Optional telemetry via browser `CustomEvent` or REST beacons hitting `kh-bounce/v1/event` (fires the `kh_bounce_telemetry` action).
- QA/Test Mode toggle that forces the modal to appear for admins or anyone using `?kh-bounce-test=1`, plus an explicit mobile disable switch so parity with legacy behavior is maintained.
- Session Storage frequency cap plus dismissal/conversion hooks in the frontend controller.
- Preview image placeholders for each template live under `assets/previews/` so product/design teams can review layouts without booting WordPress.
- Accessibility-friendly modal wrapper with `role="dialog"`, focus trap, ESC handling, and ARIA labelling.
- Translation-ready strings with a `languages/kh-bounce.pot` file and helper script for regenerating via WP-CLI.
- PHPUnit test suite covering activation defaults, sanitizer fallbacks, and frontend rendering.

## Development Workflow
1. Install dependencies once: `cd wp-content/plugins/KH-Bounce && npm install`.
2. Build CSS from SCSS: `npm run build:css` (or `npm run watch:css` while iterating templates).
3. Lint SCSS: `npm run lint:css` (Stylelint allows current vendor-prefixed mixins but will flag regressions once we migrate to `@use`).
4. Regenerate the POT template after changing strings: `bin/make-pot.sh` (requires `wp` CLI on your path).
5. Run automated tests: `WP_PHPUNIT__DIR=/path/to/wordpress-tests-lib vendor/bin/phpunit` (or `composer test`).
6. TODO: Sass currently uses deprecated `@import`. When Dart Sass 3.0 lands, migrate the partials to `@use/@forward` (plus tighten the Stylelint rules) to remove the warnings.

## Template Structure
```
assets/scss/
  helpers           // variables + mixins
  layout            // modal chrome / flex behavior
  templates/
    _classic.scss   // centered hero card (default)
    _minimal.scss   // bottom slide-up bar
    _edge.scss      // right-docked spotlight card
```
Each template shares the same markup (see `KH_Bounce_Templates::render`) so switching layouts is zero-copy. Add new templates by creating a SCSS partial, extending `KH_Bounce_Templates::all()`, and the admin preview grid picks it up automatically.

## Sandbox Preview
Need a quick look without booting WordPress? Open `sandbox.html` in a browser. It loads the compiled CSS/JS plus sample markup so designers can toggle data or telemetry flags by editing `window.khBounceSettings` directly.

## QA Checklist
- Toggle each template in **Settings → KH Bounce** and confirm the preview updates along with the frontend modal.
- Validate required fields reject empty values and CTA URL enforces proper scheme.
- Use QA Test Mode (or append `?kh-bounce-test=1`) to force the modal without exit intent. Verify it stays suppressed on mobile when the toggle is off, and that exiting test mode restores normal gating.
- Exercise exit intent in a browser (move cursor outside viewport) and ensure the modal appears once per session.
- With telemetry mode set to `events`, listen for the `khBounceEvent` CustomEvent in DevTools; with `rest`, confirm POSTs hit `kh-bounce/v1/event` and your `kh_bounce_telemetry` hook fires.
- Run `npm run build:css` before committing to keep compiled CSS in sync.
- Execute the PHPUnit suite to guard activation defaults, sanitizer fallbacks, and renderer output before packaging for production tests.
- See `docs/QA.md` for the full pre-launch runbook covering telemetry verification and automation commands.
