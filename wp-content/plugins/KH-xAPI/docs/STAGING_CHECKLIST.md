# KH xAPI Staging Checklist

## Database Prep

```bash
wp plugin activate kh-xapi
wp db tables | grep kh_xapi
```
Expect to see:
- `wp_kh_xapi_completions`
- `wp_kh_xapi_scorm_state`

To inspect row counts:
```bash
wp db query "SELECT COUNT(*) FROM wp_kh_xapi_completions;"
```

## Settings Bootstrap

```bash
wp option update kh_xapi_lrs '{"endpoint":"https://lrs.example/api/","username":"user","password":"pass","version":"1.0.3"}'
wp option update kh_xapi_reports '{"scripts":"kh-xapi-reports"}'
```

## REST Diagnostics

```bash
wp eval "print_r( rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports', ['summary'=>true] ) ) );"
wp eval "print_r( rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports/aggregate', ['dimension'=>'content'] ) ) );"
```

## CSV Export via CLI

```bash
wp eval "echo rest_do_request( new WP_REST_Request( 'GET', '/kh-xapi/v1/reports/export' ) )->get_data();" > /tmp/kh-xapi-report.csv
```

Run these commands after deploying to ensure migrations, settings, and endpoints all function before exposing the UI to stakeholders.
