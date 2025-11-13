# 4A Ingestion Endpoints

The REST layer now accepts canonical events from three connector classes. All
endpoints live under `khm/v1/ingest/*` and write to `cp_events` plus
`cp_ingestion_logs` via `CpEventIngestionService`.

## Authentication

Set an ingest token with:

```php
update_option( 'khm_4a_ingest_token', wp_generate_password( 32, false ) );
```

Clients must pass it via the `X-KHM-Ingest-Key` header or `?token=` query param.
If the option is empty, requests are accepted without authentication (useful
for local testing only).

## 1. GA4

`POST /wp-json/khm/v1/ingest/ga4`

Accepts either a Measurement Protocol relay payload:

```json
{
  "client_id": "123.456",
  "events": [
    {
      "event_name": "form_submit",
      "event_timestamp": 1731379200000,
      "event_params": {
        "percent_scrolled": { "value": 92 },
        "touchpoint": { "value": "form_demo_request" }
      },
      "user_properties": {
        "email": { "value": "founder@example.com" },
        "name": { "value": "Taylor Stripe" }
      },
      "stage_hint": "diagnosis",
      "topic_tax": ["ai", "analytics"]
    }
  ]
}
```

or a single object. Keys are mapped to canonical rows with automatic timestamp
normalization, topic aggregation, and touchpoint inference.

## 2. Email / ESP

`POST /wp-json/khm/v1/ingest/email`

SendGrid/Mailgun style webhooks can be relayed unchanged; the controller
expects `event`, `email`, `timestamp` and optional metadata. Example:

```json
[
  { "event": "open", "email": "cmo@brand.com", "timestamp": 1731379200, "campaign": "welcome" },
  { "event": "reply", "email": "ceo@brand.com", "timestamp": 1731380000, "body": "Let's chat" }
]
```

## 3. Webinar

`POST /wp-json/khm/v1/ingest/webinar`

Map Zoom/BigMarker payloads to:

```json
{
  "participant": { "email": "ops@brand.com", "name": "Jordan Ops" },
  "join_time": "2025-11-10T16:00:00Z",
  "leave_time": "2025-11-10T17:15:00Z",
  "attendance_pct": 87,
  "topic": "AI Diagnostics",
  "stage_hint": "diagnosis",
  "webinar_id": "ai-series-2025"
}
```

Attendance automatically toggles between `webinar_full_attend`,
`webinar_partial_attend`, or `webinar_interest`.

## Responses

- Success (`201`): `{ "stored": 3, "errors": [] }`
- Partial (`207`): `{ "stored": 2, "errors": [ { "index": 1, "error": "..." } ] }`
- Errors log to `cp_ingestion_logs` with per-source counters.

These endpoints provide the data foundation the scoring job will use to build
person/company rollups. When new sources are introduced, follow the same pattern
and call `CpEventIngestionService::record_ingestion()` with the source slug.
