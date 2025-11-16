# KH xAPI Pre-Staging Handoff

## 1. Smoke Test Checklist
1. Activate **KH xAPI** in staging (`wp plugin activate kh-xapi`).
2. Verify schema tables:
   ```bash
   wp db tables | grep kh_xapi
   ```
3. Visit **Settings → KH xAPI** and enter LRS endpoint, username, password, and xAPI version.
4. Navigate to the reports page (either the shortcode or template described below) and run each report:
   - Completion Rows
   - Content Performance
   - User Progress Overview
   - Status Distribution
5. For every report click **Show Report** (grid populates) and **Download Report as CSV** (file downloads).
6. Optional REST checks (mirrors UI):
   ```bash
   wp eval "print_r( rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports', ['summary'=>true] ) ) );"
   wp eval "print_r( rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports/aggregate', ['dimension'=>'content'] ) ) );"
   wp eval "echo rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports/export' ) )->get_data();" > /tmp/kh-xapi-report.csv
   ```

## 2. Embedding the Reports UI
Use the new shortcode on any admin page/post:
```
[kh_xapi_reports]
```
This renders `addons/reports/form.php` and enqueues the built-in JS/CSS bundle. Alternatively, PHP templates can include the file directly:
```php
if ( function_exists( 'kh_xapi' ) ) {
    include KH_XAPI_PATH . '/addons/reports/form.php';
}
```
Ensure the destination page exists before handoff (e.g., a WP admin page or dedicated dashboard tab).

## 3. Automated Tests
To run the PHPUnit suite locally or on staging:
```bash
cd wp-content/plugins/KH-xAPI
WP_TESTS_DIR=/path/to/wordpress-tests-lib phpunit
```
The current spec (`tests/LearningDataServiceTest.php`) validates aggregate calculations; extend this as additional features land.

## 4. Configuration Tips
- Settings live at **Settings → KH xAPI**; use the same LRS credentials intended for production.
- If additional scripts/styles must load on the reports page, list their handles in the "Reports Hooks" textarea (comma separated). KH-xAPI will enqueue them automatically.
- Provide the staging team with any secrets/credentials via secure channels; the plugin simply reads from the `kh_xapi_lrs` option.

With these steps complete, the staging team can verify parity and sign off ahead of Monday's deployment.
