# Discount Metadata Persistence

## Overview

Order records now store complete discount metadata for auditing, emails, and reporting.

## Database Changes

**Migration**: `db/migrations/2025_10_29_add_discount_metadata_to_orders.sql`

New columns added to `khm_membership_orders`:

| Column | Type | Description |
|--------|------|-------------|
| `discount_code` | VARCHAR(32) | The discount code applied |
| `discount_amount` | DECIMAL(10,2) | Amount discounted from subtotal |
| `trial_days` | INT UNSIGNED | Number of trial days (if applicable) |
| `trial_amount` | DECIMAL(10,2) | Amount due during trial (0 for free trial) |
| `first_payment_only` | TINYINT(1) | Whether discount applies only to first payment |
| `recurring_discount_type` | ENUM('percent','amount') | Type of recurring discount |
| `recurring_discount_amount` | DECIMAL(10,2) | Value of recurring discount |

Index added: `idx_discount_code` for efficient code lookups.

## Running the Migration

Use the migration CLI tool:

```bash
cd /path/to/khm-plugin
php bin/migrate.php apply
```

Or via WP-CLI (if available):

```bash
wp khm migrate
```

## Code Changes

### OrderRepository

- **File**: `src/Services/OrderRepository.php`
- **Method**: `create()`
- Now accepts and sanitizes discount metadata fields:
  - Sanitizes `discount_code` as text
  - Converts `first_payment_only` boolean to int (0 or 1)
  - Casts numeric fields to float/int
  - Validates `recurring_discount_type` enum

### DiscountCodeHooks

- **File**: `src/Hooks/DiscountCodeHooks.php`
- **Filter**: `khm_checkout_order_data`
- Appends discount metadata to order data array before order creation
- Fields added:
  - `discount_code`, `discount_amount`
  - `trial_days`, `trial_amount` (if trial present)
  - `first_payment_only` (if applicable)
  - `recurring_discount_type`, `recurring_discount_amount` (if recurring)

### CheckoutShortcode

- **File**: `src/Public/CheckoutShortcode.php`
- Calls `apply_filters('khm_checkout_order_data', ...)` before creating order
- Discount metadata flows seamlessly from filter → OrderRepository → database

## Testing

**New Test**: `tests/OrderRepositoryDiscountTest.php`

- Validates discount fields are accepted by `create()`
- Tests sanitization logic for boolean → int conversion

Run tests:

```bash
./bin/run-tests.sh
```

All 93 tests passing (192 assertions).

## Benefits

- **Auditing**: See exactly which code was used and what trial/recurring terms applied
- **Emails**: Templates can now display discount details, trial info, and savings
- **Reports**: Analyze discount effectiveness, trial conversion, LTV by code
- **Support**: Quickly resolve customer questions about charges and trials
- **Webhooks**: Future sync logic can use these fields to reconcile Stripe events

## Next Steps

- Update email templates to show discount line items
- Build admin discount usage report with revenue impact and cohort analysis
- Implement webhook sync for trial end and coupon lifecycle
