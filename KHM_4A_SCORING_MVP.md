# 4A Dashboard Scoring (MVP) – Technical Design

**Session Date:** November 12, 2025  
**Scope:** Prospect-intent scoring for People & Companies using the 4A framework inside the KHM Marketing Suite (WordPress + auxiliary services).  
**Goal:** Deliver concrete schemas, weight seeds, event mappers, and UI wireframes so engineering can begin implementation immediately.

---

## 1. Data Model

### 1.1 Core Tables

| Table | Purpose | Key Columns (type) | Notes |
|-------|---------|--------------------|-------|
| `cp_events` | Canonical raw events (12‑month TTL) | `id (BIGSERIAL PK)`, `event_id (UUID unique)`, `occurred_at (TIMESTAMPTZ)`, `ingested_at (TIMESTAMPTZ default now)`, `actor_email (CITEXT)`, `actor_name (TEXT)`, `company_domain (CITEXT)`, `source (VARCHAR 40)`, `touchpoint (VARCHAR 60)`, `stage_hint (VARCHAR 30)`, `depth_scroll (NUMERIC)`, `depth_dwell_sec (NUMERIC)`, `depth_pct_complete (NUMERIC)`, `topic_tax (TEXT[] via JSONB)`, `rep_involved (TEXT)`, `metadata (JSONB)` | Partition monthly for TTL. Store `topic_tax` as `JSONB` array to preserve original taxonomy. |
| `cp_scores_person` | Daily person rollups (120‑day window) | `id (BIGSERIAL PK)`, `actor_email (CITEXT)`, `date (DATE)`, `person_score (NUMERIC)`, `stage (VARCHAR 30)`, `last_touch (VARCHAR 60)`, `last_touch_at (TIMESTAMPTZ)`, `mql_flag (BOOLEAN)`, `sql_flag (BOOLEAN)`, `nba_recommendation (JSONB)` | Composite unique index `(actor_email, date)`. `nba_recommendation` stores message, asset slug, CTA. |
| `cp_scores_company` | Daily company rollups | `id`, `company_domain`, `date`, `company_score`, `stage_mode`, `engaged_contacts (INT)`, `hot_flag (BOOLEAN)`, `hot_since (DATE)`, `nba_recommendation (JSONB)` | `hot_flag` when score ≥120 and engaged contacts ≥3 in last 21d. |
| `cp_weights` | Touchpoint weight catalog | `id`, `touchpoint`, `base_weight (NUMERIC)`, `stage_default (VARCHAR 30)`, `category (ENUM Low/Medium/High/PoS)`, `description`, `is_active (BOOLEAN)` | Seeded from Touchpoint Matrix; editable in WP Admin. |
| `cp_actions` | Next-best-action templates | `id`, `stage`, `topic_taxonomy (TEXT[])`, `title`, `body_markdown`, `asset_url`, `cta_type`, `priority (INT)` | Stage/topic lookup when generating NBAs. |
| `cp_ingestion_logs` | Monitor inbound connectors | `id`, `source`, `last_success (TIMESTAMPTZ)`, `last_error (TIMESTAMPTZ)`, `error_payload (JSONB)`, `event_count_24h (INT)` | For governance + alerts. |
| `cp_usage_audit` | Track read/export actions | `id`, `user_id`, `role`, `action (ENUM view/export/webhook)`, `target (person/company)`, `performed_at`, `metadata` | Supports compliance requirements. |

### 1.2 Indices & Retention

- `cp_events`: partition by month (`cp_events_2025_11`). Index on `(actor_email)`, `(company_domain)`, `(touchpoint, occurred_at)`, and `GIN` on `metadata` for ad hoc filters.
- `cp_scores_person`: rolling 180 days stored; aggregated nightly; `actor_email` referenced from WordPress user meta when possible.
- `cp_scores_company`: maintain aggregated `engaged_contacts` as count of distinct actor_emails with event in last 21 days for same domain.

---

## 2. Scoring Logic (Deterministic)

```python
base = cp_weights.base_weight
freq = 1 + log1p(actor_events_last30d(actor_email))
depth = clamp(
    0.25*(scroll_pct / median_scroll) +
    0.25*(dwell_sec / median_dwell) +
    0.5*(pct_complete / median_pct_complete),
    0.5,
    1.2
)
topic = 1.2 if overlap(topic_tax, actor_profile_topics) else 1.0
decay = 0.9 ** (days_since(occurred_at) / 7)
event_score = base * freq * depth * topic * decay
```

- **Person score:** sum of `event_score` for actor over trailing 120 days.
- **Company score:** sum grouped by `company_domain` over trailing 120 days; only include distinct actors to avoid over-weighting one person.
- **Stage inference:** majority stage_hint across last 45 days; override to **Acceptance** when any Point-of-Sale (PoS) touchpoint appears.

### Tier Logic (evaluated after rollup)

| Tier | Criteria |
|------|----------|
| MQL | `person_score ≥ 30` **AND** `stage ∈ {Diagnosis, Solution}` |
| SQL | `person_score ≥ 60` **OR** PoS touchpoint in last 14 days |
| Hot Account | `company_score ≥ 120` **AND** `engaged_contacts ≥ 3` in trailing 21 days |

---

## 3. Touchpoint Weight Seed

Initial seed lives in `wp-content/plugins/khm-plugin/db/seeds/cp_weights_seed.json`. Example payload:

```json
[
  {"touchpoint":"article_complete", "stage_default":"Attention", "category":"Low", "base_weight":10, "description":"Full article read ≥85%"},
  {"touchpoint":"webinar_full_attend", "stage_default":"Diagnosis", "category":"Medium", "base_weight":15},
  {"touchpoint":"copilot_query", "stage_default":"Diagnosis", "category":"Medium", "base_weight":14},
  {"touchpoint":"workshop_signup", "stage_default":"Solution", "category":"High", "base_weight":18},
  {"touchpoint":"one_to_one_meeting", "stage_default":"Solution", "category":"PoS", "base_weight":24},
  {"touchpoint":"proposal_shared", "stage_default":"Acceptance", "category":"PoS", "base_weight":28},
  {"touchpoint":"exclusivity_accept", "stage_default":"Acceptance", "category":"PoS", "base_weight":35}
]
```

- `category` drives guard rails for admin editing (e.g., Low=5‑12, Medium=12‑18, High=18‑24, PoS=24‑36).
- Additional touchpoints for day-one ingestion (≥10) include: `ad_builder_publish`, `form_demo_request`, `email_reply_long`, `ga4_paid_click`, `social_direct_msg`, `crm_rep_send`, `knowledge_base_completion`.

---

## 4. Event Ingestion Mappers

### 4.1 GA4 Mapper

| GA4 Signal | Mapping |
|------------|---------|
| `event_name` | Map specific events (`scroll_complete`, `form_submit`, `video_complete`) → `touchpoint`. |
| `event_timestamp` | Convert microseconds → `occurred_at`. |
| `user_properties.email` | → `actor.email`; fallback to hashed ID if consent missing. |
| `page_location` + `page_referrer` | drop into `metadata`. |
| `content_group` or `page_category` | populate `topic_tax`. |
| `engagement_time_msec` | used for `depth_dwell_sec`. |
| Consent flags | stored in `metadata.privacy`. |

Implementation notes:
- GA4 batch webhook hits `wp-json/khm/4a/ingest/ga4`.
- Validate shared secret + replay window.
- Normalize numeric fields (scroll 0‑100, etc.).

### 4.2 Email (ESP Webhook e.g., SendGrid)

| Field | Mapping |
|-------|---------|
| `event` | `open`, `click`, `reply` → respective touchpoints (`email_open`, `email_click_deep`, `email_reply_long`). |
| `timestamp` | `occurred_at`. |
| `sg_message_id` | `event_id`. |
| `email` | `actor.email`. |
| `marketing_campaign_id` | `metadata.campaign`. |
| `subject`/`category` | feed `topic_tax`. |
| `reply_body` (if captured via mailbox) | store hashed excerpt for privacy. |

Depth heuristic: `reply` events set `depth` to 1.2; multiple clicks within 10 min collapse to single event via `event_id` hash `(email+campaign+hour)`.

### 4.3 Webinar Platform (Zoom/BigMarker)

| Source Field | Canonical Field |
|--------------|-----------------|
| `participant.email` | `actor.email` |
| `join_time`, `leave_time` | `occurred_at` (join) + dwell seconds for depth |
| `attendance_pct` | map to `depth_pct_complete` |
| `questions_asked`, `poll_responses` | stored in `metadata.engagement` |
| `webinar_id` | `metadata.asset_id`, also crosswalk to `topic_tax` |
| `stage_hint` | default to `Diagnosis` unless flagged PoS (e.g., product deep-dive) |

Business rule: `attendance_pct ≥ 80%` → `touchpoint = webinar_full_attend`; `40–79%` → `webinar_partial`.

---

## 5. Surfaces & Wireframe Notes

### 5.1 WP Admin – “4A Intelligence” Screen

- **Hot Accounts List (table component)**
  - Columns: Company, Score (sparkline last 7 pts), Stage, Engaged Contacts, Last Touch (touchpoint + timestamp), NBA button.
  - Filters: Stage, Industry (taxonomy), Rep, Date.
  - CTA: “Send to CRM” (fires webhook manually).

- **Score Trend Module**
  - Dual-axis chart: person vs company score for selected account over 30 days.
  - Hover reveals touchpoint contributing most on that day.

- **Stage Distribution Card**
  - Donut chart of current MQL/SQL/Other counts.
  - Badge when Stage inference accuracy (manual QA) < 80%.

Wireframe reference: `wp-admin/images/4a-dashboard-wireframe.png` (to be created) with stacked layout (Hot Accounts top, Score Trend right column, Stage distribution below).

### 5.2 Client Portal Widget

- Shows only accounts tied to client’s tenant ID.
- Cards per prospect:
  - Header: Person Name + Stage pill.
  - Body: Score bar (0‑100 scale), last touch summary, recommended action (title + link).
  - Footer: `Download CSV` (filters to 30 days) + `Subscribe to CRM` (toggles webhook deliveries).
- Table view toggle for bulk export. CSV includes hashed emails unless client has Analyst/Admin role.

### 5.3 Admin Widgets (WP Dashboard)

1. **Hot Accounts Mini-widget**
   - Top 5 companies with score trend sparkline.
2. **Next Best Action Queue**
   - List of NBAs generated in last run, assignable to reps.

---

## 6. Webhooks & Governance

- **Events**
  - `person.upserted`: payload includes email, score, stage, last_touch, nba suggestion.
  - `score.updated`: diff of previous vs current person/company score (≥10 delta).
  - `stage.changed`: old_stage → new_stage, triggered immediately when inference flips.
- Delivery: signed HMAC SHA256 headers (`X-KHM-Signature`), 3 retries (exponential backoff).
- **Roles**
  - `khm_4a_admin`: manage weights/actions.
  - `khm_4a_analyst`: view scores, export hashed data.
  - `khm_4a_sales`: view assigned accounts only; no CSV export.
- **PII Handling**
  - Hash emails (`SHA256(email+salt)`) when exporting for portals lacking consent.
  - `cp_usage_audit` records CSV/webhook usage.

---

## 7. Acceptance Criteria Alignment

| Requirement | Implementation Hook |
|-------------|---------------------|
| ≥10 touchpoints ingested | Seeds + mappers listed; ingestion logs verify counts. |
| Recompute hourly | WP cron → CLI command `php wp khm-4a recompute --window=2h` calling Python scorer via REST or internal service. |
| Stage inference ≥80% | QA dashboard compares predicted vs manual tags stored in `metadata.qa_stage`. |
| MQL→SQL 15% reply correlation | Store outcome metrics in `cp_actions` usage; measure reply rate via ESP webhook linking to actor email. |
| CSV + CRM webhooks functioning | Manual test scripts hitting `/wp-json/khm/4a/export` and `/wp-json/khm/4a/webhook-test`. |

---

## 8. Open Questions (for Today’s Standup)

1. **Touchpoint list lock-in:** Need final confirmation on day-one connectors (GA4, SendGrid, Zoom, WP native forms, Ad Builder, Co-Pilot).  
2. **Taxonomy source:** Recommend fallback to WP categories, but allow custom taxonomy `khm_topic`. Decide before ingestion parser finalization.  
3. **CRM Target:** HubSpot vs SFDC determines webhook field names. Default template prepared for HubSpot (`vid`, `lifecycle_stage`).  
4. **Privacy Banner:** Are dwell/scroll signals already covered by existing consent banner? If not, create new message referencing 4A analytics.  
5. **Median Depth Benchmarks:** Need baseline per content type for depth normalization; propose nightly job computing medians.

---

## 9. Next Steps

1. Build schema migrations in `wp-content/plugins/khm-plugin/db/migrations/2025_11_12_create_cp_tables.sql`.  
2. Implement weight seed importer + admin CRUD (`CPWeightController`).  
3. Stand up ingestion endpoints (`/wp-json/khm/4a/ingest/<source>`).  
4. Implement hourly scorer job (PHP CLI wrapper hitting Python scorer or native PHP math).  
5. Develop admin UI React components (Hot Accounts table, Score Trend chart).  
6. QA stage inference with sampled accounts to reach ≥80% target.

---

## Appendix – Scoring Job Implementation Notes

- `wp khm-4a recompute --window=7200` uses `FourAScoringService` to pull all events with `ingested_at` inside the window (default 2h), re-score the affected people/companies using the deterministic formula, and upsert into `cp_scores_person` / `cp_scores_company`.
- An hourly WP cron hook (`khm_4a_hourly_recompute`) is scheduled on plugin load to call the same service so the dashboard stays up to date even when WP-CLI isn’t available.
- Stage inference honors PoS (Point-of-Sale) touchpoints by automatically promoting to Acceptance when those events occur inside the 45-day window.
- Company `hot_flag` flips on when score ≥120 and there are ≥3 distinct engaged contacts (events in last 21 days). Engaged counts, tier flags, and timestamps are maintained directly in the rollup tables to simplify dashboard queries.
