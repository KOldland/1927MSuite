# KHM Plugin Architecture

Date: 2025-10-25

This document defines the target architecture for the KHM Membership plugin: modules, data flow, boundaries, and extension points.

---

## Goals

- Clear separation of concerns (contracts, services, gateways, UI, REST)
- Testable units with minimal WordPress coupling
- Backwards-friendly hooks for customization
- Resilient webhook handling (idempotent, observable)

---

## Modules

- Contracts (Interfaces)
  - GatewayInterface, WebhookVerifierInterface, IdempotencyStoreInterface
  - OrderRepositoryInterface, MembershipRepositoryInterface, EmailServiceInterface, AccessControlInterface
- Services (Business Logic)
  - OrderRepository, MembershipRepository, EmailService, DatabaseIdempotencyStore
  - Future: AccessControlService, ExportService, DiscountCodeService
- Gateways
  - StripeGateway, StripeWebhookVerifier (future: PayPal, Braintree, Authorize.Net, 2Checkout)
- REST API
  - Routes under `/wp-json/khm/v1/...`
  - Webhooks: `/webhooks/{gateway}`
  - Admin AJAX endpoints for UI actions
- Admin UI
  - Members, Levels, Orders, Discount Codes, Reports
- Frontend UI
  - Shortcodes: [khm_checkout], [khm_account], [khm_levels]
  - Blocks (future): Checkout, Levels Grid, Account
- Schedulers
  - Daily cron: expirations, warnings, cleanup for idempotency store

---

## Component Map

```
     +-------------------+            +-------------------------+
     |   Frontend UI     |            |        Admin UI         |
     |  (Shortcodes/JS)  |            | (Pages, List Tables)    |
     +---------+---------+            +------------+------------+
          |                                   |
          v                                   v
     +----+-----------------------------------+----+
     |               REST Controllers              |
     |  - WebhooksController (Stripe, ...)         |
     |  - OrdersController (future)                |
     +----+------------------+-----------------+----+
          |                  |                 |
          v                  v                 v
       +-------+------+   +-------+------+   +------+--------+
       |   Services   |   |   Gateways   |   |   Repos       |
       |  Email, ACL  |   | Stripe, ...  |   | Orders, Members|
       +-------+------+   +-------+------+   +------+--------+
          |                  |                 |
          +---------+--------+-----------------+
          v
        +------+------+
        |   Contracts  |
        | Interfaces   |
        +------+------+
          |
          v
        +------+------+
        |   WordPress |
        |  DB, Hooks  |
        +-------------+
```

Boundaries:
- Contracts define interfaces; Services and Gateways implement them.
- REST controllers depend on interfaces only; inject concrete implementations in bootstrap.
- Repositories encapsulate DB access via $wpdb; no direct SQL elsewhere.
- UI layers talk to REST or Services, not to DB/gateways directly.

---

## Directory Layout

```
src/
├── Contracts/
├── Gateways/
├── Models/
├── Rest/
├── Services/
├── Admin/
└── Public/
```

- Rest/ will contain controllers (e.g., `WebhooksController.php`, `OrdersController.php`)
- Admin/ will contain page controllers and list tables
- Public/ will contain shortcodes and blocks

---

## Data Flow

### Checkout (Card)
1. Frontend (shortcode) collects payment method (Stripe Elements)
2. Gateway.charge(order) → Result
3. On success: create Order (status=success), assign Membership
4. Send email(s): member + admin
5. Redirect to confirmation

### Subscription
1. Gateway.createSubscription(order)
2. Store `subscription_transaction_id` on Order
3. Assign Membership with enddate if applicable

### Webhook
1. REST `/webhooks/stripe` receives event
2. Verify signature via `StripeWebhookVerifier`
3. Check idempotency via `DatabaseIdempotencyStore`
4. Route to handler by event type
5. Update Order/Membership
6. Fire hooks and send notifications

Sequence (invoice.payment_succeeded):
1. Stripe → POST → `/khm/v1/webhooks/stripe`
2. Controller → verify signature (StripeWebhookVerifier)
3. Controller → check idempotency (DatabaseIdempotencyStore)
4. Controller → built-in handler resolves user/level → create/update Order → assign membership
5. Controller → do_action hooks (generic + typed)
6. Controller → mark processed → 200 OK

---

## REST Endpoints (initial)

- POST `/khm/v1/webhooks/stripe` → Stripe webhooks
- GET `/khm/v1/orders/{code}` → Read-only order lookup (optional)

Response shapes (examples):
- Webhook success: `{ ok: true, status: "processed", id: "evt_...", type: "invoice.payment_succeeded" }`
- Webhook duplicate: `{ ok: true, status: "duplicate", id: "evt_...", type: "..." }`
- Error: `{ code: "khm_invalid_signature", message: "..." }` with appropriate HTTP status

Versioning:
- Namespace `khm/v1`; additive changes only; breaking changes → v2.

---

## Hooks (selected)

- Actions
  - `khm_gateway_before_charge`, `khm_gateway_after_charge`
  - `khm_membership_assigned`, `khm_membership_cancelled`, `khm_membership_expired`
  - `khm_order_created`, `khm_order_status_changed`, `khm_order_deleted`
  - `khm_email_sent`
- Filters
  - `khm_email_subject`, `khm_email_body`, `khm_email_data`, `khm_email_headers`
  - `khm_order_tax`, `khm_order_code`
  - `khm_stripe_*` parameter filters
  - Mapping filters for webhooks: `khm_stripe_map_customer_to_user`, `khm_stripe_map_plan_to_level`

---

## Error Handling & Observability

- Use `Result` for gateway operations
- Log failures to WP debug log; add filter to integrate with external loggers
- Idempotency ensures duplicate webhook events are safe
- Add `request_id` correlation for tracing (future)
- Add action `khm_log` for external logging adapters (optional)
- Record webhook processing metadata in `khm_webhook_events` table

---

## Security

- Nonces and capability checks on all admin actions
- REST endpoints: webhooks exempt from auth but signature-verified
- Sanitize all user inputs in public forms
- Prepared statements for DB access
- Capabilities:
  - `manage_khm` for full admin access
  - `edit_khm_orders`, `read_khm_orders`, `export_khm_reports` (granular)
  - Map roles on activation (filterable)

---

## Edge Cases

- Duplicate webhooks → return 200, no side effects
- Partial refunds → update order notes and totals
- Card SCA required → surface `requires_action` to frontend
- Subscription proration on plan change → follow gateway defaults
- Timezone consistency: store UTC, display site TZ
- Cold-start activation: create required tables; defer heavy migrations until CLI run
- Retries/backoff: Stripe will retry webhooks; controller always returns 2xx for duplicates

---

## Database Schema (overview)

- `khm_membership_orders`
  - id (PK), code (unique), user_id, membership_id
  - status, gateway, gateway_environment
  - total, subtotal, tax, currency
  - payment_transaction_id (index), subscription_transaction_id (index)
  - notes (text), timestamp (datetime)

- `khm_memberships_users`
  - id (PK), user_id (index), membership_id (index)
  - status (active, cancelled, expired)
  - startdate, enddate, billing_amount, trial_amount, etc.

- `khm_webhook_events`
  - id (PK), event_id (unique), gateway, metadata (json), processed_at (index)

Migrations:
- Use dbDelta for create/alter; versioned migration runner in `db/migrations`.

---

## Roadmap Alignment

- Next features to implement against this architecture:
  1. REST Webhook controller and router
    2. AccessControlService + `[khm_member]`/`[khm_checkout]` shortcodes
    3. Admin Orders page (list table + details)
    4. Scheduled tasks: expirations, email warnings
    5. Reporting/exports with streaming CSV

---

## Trade-offs

- Keeping repositories WP-DB-centric prioritizes compatibility over ORM flexibility
- Price auto-creation in Stripe simplifies dev but may create many Price objects; allow override via filter/setting
- Email templating uses simple token replacement for speed; consider Twig only if needed
- REST controllers return minimal JSON to reduce coupling; deeper admin JSON is auth-protected
- Stripe as first-class gateway accelerates delivery; abstracted for others later

---

## Success Criteria

- All flows work offline with test gateways
- Webhooks are idempotent and observable
- Clear dev ergonomics via hooks and docs
- Unit tests for services; integration tests for gateways/webhooks
- Endpoints documented; webhook test suite passes across happy/duplicate/invalid-signature
- Tables provisioned on activation; migrations are idempotent and reversible
