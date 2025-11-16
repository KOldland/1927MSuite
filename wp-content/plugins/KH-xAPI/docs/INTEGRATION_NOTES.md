# Integration Notes

## Legacy GrassBlade References
- `Inspiration/developed/grassblade/addons/...`: Use these files as parity references when migrating add-ons into `KH-xAPI/addons`. The reports UI pulls templates/JS from `addons/reports`, and state helpers live under `addons/nss_xapi_state.class.php`.
- Search for `grassblade` across the repository before deployment to ensure no production code continues to load the old plugin:
  ```bash
  rg -n "grassblade" -g"*.php" wp-content
  ```

## Touchpoints for KH Projects
- **KHM Marketing Suite (`wp-content/plugins/khm-plugin`)**: future attribution or course modules can use `kh_xapi()->data()` to persist completions instead of custom tables. Expose the new REST endpoints to JS dashboards instead of calling third-party APIs.
- **KHM SEO (`wp-content/plugins/khm-seo`)**: no direct GrassBlade references, but any analytics or validation dashboards that currently rely on offsite tracking can be extended by consuming `/kh-xapi/v1/reports`.
- **Dual GPT plugin**: when embedding interactive content, call `kh_xapi()->state()->send_state()` to push resume data rather than the previous GrassBlade methods.

## Recommended Refactors
1. Replace any `[grassblade]` shortcodes with KH-xAPI equivalents (wrap `addons/reports/form.php` inside a `[kh_xapi_reports]` shortcode when ready).
2. Centralise enqueue logic: use the settings page (`Settings → KH xAPI`) to declare extra script handles so admin pages don’t need to enqueue GrassBlade assets manually.
3. When running acceptance tests, call the WP-CLI snippets in `docs/STAGING_CHECKLIST.md` to validate schema and REST endpoints.

Keep this file updated as we discover more dependencies during the migration.
