# KH xAPI Plugin

## Activation & Migration Verification

1. Upload `KH-xAPI` to a staging WordPress site.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Confirm the following database tables exist (via phpMyAdmin or WP-CLI):
   - `wp_kh_xapi_completions`
   - `wp_kh_xapi_scorm_state`
4. Visit **Settings → KH xAPI** and set LRS endpoint credentials.
5. Navigate to a page that renders the Reports form (use the `[kh_xapi_reports]` shortcode or include `addons/reports/form.php`) and:
   - Select a report and click **Show Report** to ensure data loads.
   - Click **Download Report as CSV** to verify the export endpoint.
6. Re-run activation (deactivate/activate) to confirm migrations skip when up-to-date.

## REST Endpoints

- `GET /wp-json/kh-xapi/v1/reports`
- `GET /wp-json/kh-xapi/v1/reports/aggregate`
- `GET /wp-json/kh-xapi/v1/reports/export`

Both require a logged-in user with `edit_posts` or `manage_options` capability and a valid `X-WP-Nonce` or `_wpnonce` value.
