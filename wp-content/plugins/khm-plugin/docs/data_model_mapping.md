# Data Model Mapping: PMPro → KHM

Date: 2025-10-25

This document maps key Paid Memberships Pro (PMPro) tables/fields to the KHM schema and outlines a migration plan with acceptance criteria.

---

## Scope

- Membership assignments (users ↔ levels)
- Orders (transactions and subscriptions)
- Levels (reference data)
- Discount codes (reference + usage)
- Webhook idempotency

---

## Tables Overview

Legacy PMPro (source):
- pmpro_memberships_users
- pmpro_membership_orders
- pmpro_membership_levels
- pmpro_membership_levelmeta
- pmpro_discount_codes (kept for now)
- pmpro_discount_codes_levels (kept for now)
- pmpro_discount_codes_uses (kept for now)

KHM (target):
- khm_membership_levels
- khm_membership_levelmeta
- khm_memberships_users
- khm_membership_orders
- khm_webhook_events (new; idempotency)
- (Future) khm_discount_codes, etc.

---

## Field Mapping

### Memberships: pmpro_memberships_users → khm_memberships_users

| PMPro Field                | KHM Field              | Notes |
|----------------------------|------------------------|-------|
| id                         | id                     | Auto-increment PK
| user_id                    | user_id                | bigint unsigned
| membership_id              | membership_id          | bigint unsigned
| code_id                    | code_id                | bigint unsigned nullable
| initial_payment            | initial_payment        | decimal(10,2)
| billing_amount             | billing_amount         | decimal(10,2)
| cycle_number               | cycle_number           | int
| cycle_period               | cycle_period           | enum('Day','Week','Month','Year')
| billing_limit              | billing_limit          | int
| trial_amount               | trial_amount           | decimal(10,2)
| trial_limit                | trial_limit            | int
| status                     | status                 | varchar(20) (active, cancelled, expired)
| startdate                  | startdate              | datetime (UTC)
| enddate                    | enddate                | datetime nullable (UTC)
| modified                   | modified               | datetime auto-updated

### Orders: pmpro_membership_orders → khm_membership_orders

| PMPro Field                | KHM Field                    | Notes |
|----------------------------|------------------------------|-------|
| id                         | id                           | Auto-increment PK
| code                       | code                         | unique-ish public identifier
| session_id                 | session_id                   | nullable
| user_id                    | user_id                      | bigint unsigned
| membership_id              | membership_id                | bigint unsigned
| paypal_token               | paypal_token                 | nullable
| billing_name               | billing_name                 | nullable
| billing_street             | billing_street               | nullable
| billing_city               | billing_city                 | nullable
| billing_state              | billing_state                | nullable
| billing_zip                | billing_zip                  | nullable
| billing_country            | billing_country              | nullable
| billing_phone              | billing_phone                | nullable
| subtotal                   | subtotal                     | decimal(10,2)
| tax                        | tax                          | decimal(10,2)
| total                      | total                        | decimal(10,2)
| payment_type               | payment_type                 | nullable
| cardtype                   | cardtype                     | nullable
| accountnumber              | accountnumber                | nullable
| expirationmonth            | expirationmonth              | nullable
| expirationyear             | expirationyear               | nullable
| status                     | status                       | varchar(64) (pending, success, failed, refunded, cancelled, deleted)
| gateway                    | gateway                      | varchar(64)
| gateway_environment        | gateway_environment          | varchar(64)
| payment_transaction_id     | payment_transaction_id       | varchar(128) (index)
| subscription_transaction_id| subscription_transaction_id  | varchar(128) (index)
| timestamp                  | timestamp                    | datetime default CURRENT_TIMESTAMP
| affiliate_id               | affiliate_id                 | nullable
| affiliate_subid            | affiliate_subid              | nullable
| notes                      | notes                        | text

### Membership Levels: pmpro_membership_levels → khm_membership_levels

| PMPro Field          | KHM Field            | Notes |
|----------------------|----------------------|-------|
| id                   | id                   | Auto-increment PK (preserves legacy IDs during migration) |
| name                 | name                 | VARCHAR(255) |
| description          | description          | LONGTEXT |
| confirmation         | confirmation         | LONGTEXT |
| initial_payment      | initial_payment      | DECIMAL(10,2) |
| billing_amount       | billing_amount       | DECIMAL(10,2) |
| cycle_number         | cycle_number         | INT |
| cycle_period         | cycle_period         | ENUM('Day','Week','Month','Year') |
| billing_limit        | billing_limit        | INT |
| trial_amount         | trial_amount         | DECIMAL(10,2) |
| trial_limit          | trial_limit          | INT |
| allow_signups        | allow_signups        | TINYINT(1) |
| expiration_number    | expiration_number    | INT UNSIGNED |
| expiration_period    | expiration_period    | ENUM('Day','Week','Month','Year') |
| *(n/a)*              | created_at / updated_at | Timestamps added in KHM for auditing |

`pmpro_membership_levelmeta` entries are copied into `khm_membership_levelmeta` keyed by `level_id` and `meta_key`, preserving serialized values.

### Levels and Discounts

- Membership levels now live in `khm_membership_levels` and `khm_membership_levelmeta`. All runtime code resolves level data via `KHM\Services\LevelRepository`.
- Discount codes still reference `pmpro_discount_codes*` tables temporarily. Future migration will introduce `khm_discount_codes` family as source of truth.

### Idempotency: khm_webhook_events

- New to KHM; no PMPro equivalent. Populated as webhooks arrive.

---

## Migration Plan

1) **Provision target tables**
   - Run `0001_create_khm_tables.sql`
   - Run `2025_10_27_create_membership_levels.sql`

2) **Bulk copy data**
   - Run `0002_migrate_pmpro_to_khm.sql` to copy membership levels, level meta, memberships_users, and membership_orders from pmpro_* to khm_*
   - Validate row counts and sample records

3) **Add indexes**
   - Run `0003_indexes.sql` (post-load)

4) **Update references**
   - KHM code reads from `khm_membership_levels`, `khm_membership_levelmeta`, `khm_memberships_users`, and `khm_membership_orders`.
   - Discount codes still reference pmpro tables until the dedicated `khm_discount_codes*` migrations land.

5) **Cutover**
   - Activate KHM plugin; deactivate or leave PMPro plugin for read-only access.
   - All new orders/memberships write to khm_* tables; webhooks update khm_* tables.
   - Legacy pmpro_* data remains for reference or rollback.

6) **Verification**
   - Reconcile sample orders by transaction IDs
   - Confirm subscription events continue updating khm_membership_orders and khm_memberships_users via webhooks

---

## Acceptance Criteria

- Counts match for membership levels, level meta, memberships, and orders between PMPro and KHM after migration
- Random sample of 20 orders match exactly on user_id, membership_id, total, transaction IDs
- Subscription renewals continue to create orders and maintain active membership via webhooks
- Indexes exist on payment_transaction_id and subscription_transaction_id
- Dry run shows no SQL errors; real run creates a backup and completes with success

---

## Gaps and Decisions

- **Meta tables**:
  - Legacy PMPro uses usermeta/postmeta for extension data. KHM can introduce `khm_ordermeta` and `khm_membershipmeta` later if needed. For now, use the `notes` field in orders and repository filters for extensibility.

- **Discounts**:
  - Discount code data still lives in pmpro_* tables. A follow-up migration will move these into `khm_discount_codes`, `khm_discount_codes_levels`, and `khm_discount_codes_uses`.

- **Order states**:
  - KHM standardizes on: pending, success, failed, refunded, cancelled, deleted. Map PMPro's custom states (e.g., `error`) to `failed` or `cancelled` as appropriate.

- **Timezone**:
  - Store in UTC in KHM. If PMPro stored site-local time, convert on migration (use WP timezone setting or assume UTC if unknown).

---

## CLI Usage (recap)

```bash
# Dry run all migrations
php bin/migrate.php

# Apply all migrations
php bin/migrate.php --apply

# Run specific migrations
php bin/migrate.php --migrations=0001_create_khm_tables.sql,2025_10_27_create_membership_levels.sql,0002_migrate_pmpro_to_khm.sql --apply

# Validate migration after applying
php bin/validate_migration.php
```

The CLI auto-detects WordPress DB credentials from wp-config.php and creates a backup when applying changes. The validation script checks row counts, sample data integrity, and index presence.
