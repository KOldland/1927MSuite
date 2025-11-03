-- 0002_migrate_pmpro_to_khm.sql
-- Migration script to copy data from legacy pmpro_ tables to khm_ tables.
-- Schema matches standard PMPro setup.sql with wp_ prefix stripped.

-- Migrate membership levels
INSERT INTO khm_membership_levels (
  id,
  name,
  description,
  confirmation,
  initial_payment,
  billing_amount,
  cycle_number,
  cycle_period,
  billing_limit,
  trial_amount,
  trial_limit,
  allow_signups,
  expiration_number,
  expiration_period,
  created_at,
  updated_at
)
SELECT
  id,
  name,
  description,
  confirmation,
  initial_payment,
  billing_amount,
  cycle_number,
  cycle_period,
  billing_limit,
  trial_amount,
  trial_limit,
  allow_signups,
  expiration_number,
  expiration_period,
  NOW(),
  NOW()
FROM pmpro_membership_levels
WHERE NOT EXISTS (
  SELECT 1 FROM khm_membership_levels k
  WHERE k.id = pmpro_membership_levels.id
);

-- Migrate membership level meta
INSERT INTO khm_membership_levelmeta (
  level_id,
  meta_key,
  meta_value
)
SELECT
  pmpro_membership_level_id,
  meta_key,
  meta_value
FROM pmpro_membership_levelmeta
WHERE NOT EXISTS (
  SELECT 1 FROM khm_membership_levelmeta k
  WHERE k.level_id = pmpro_membership_levelmeta.pmpro_membership_level_id
    AND k.meta_key = pmpro_membership_levelmeta.meta_key
);

-- Migrate memberships_users rows
INSERT INTO khm_memberships_users (
  user_id,
  membership_id,
  code_id,
  initial_payment,
  billing_amount,
  cycle_number,
  cycle_period,
  billing_limit,
  trial_amount,
  trial_limit,
  status,
  startdate,
  enddate
)
SELECT
  user_id,
  membership_id,
  code_id,
  initial_payment,
  billing_amount,
  cycle_number,
  cycle_period,
  billing_limit,
  trial_amount,
  trial_limit,
  status,
  startdate,
  enddate
FROM pmpro_memberships_users
WHERE NOT EXISTS (
  SELECT 1 FROM khm_memberships_users k
  WHERE k.user_id = pmpro_memberships_users.user_id
  AND k.membership_id = pmpro_memberships_users.membership_id
);

-- Migrate membership orders (note PMPro stores subtotal/tax/total as varchar, converted to decimal in khm)
INSERT INTO khm_membership_orders (
  code,
  session_id,
  user_id,
  membership_id,
  paypal_token,
  billing_name,
  billing_street,
  billing_city,
  billing_state,
  billing_zip,
  billing_country,
  billing_phone,
  subtotal,
  tax,
  total,
  payment_type,
  cardtype,
  accountnumber,
  expirationmonth,
  expirationyear,
  status,
  gateway,
  gateway_environment,
  payment_transaction_id,
  subscription_transaction_id,
  timestamp,
  affiliate_id,
  affiliate_subid,
  notes
)
SELECT
  code,
  session_id,
  user_id,
  membership_id,
  paypal_token,
  billing_name,
  billing_street,
  billing_city,
  billing_state,
  billing_zip,
  billing_country,
  billing_phone,
  CAST(NULLIF(subtotal, '') AS DECIMAL(10,2)),
  CAST(NULLIF(tax, '') AS DECIMAL(10,2)),
  CAST(NULLIF(total, '') AS DECIMAL(10,2)),
  payment_type,
  cardtype,
  accountnumber,
  expirationmonth,
  expirationyear,
  status,
  gateway,
  gateway_environment,
  payment_transaction_id,
  subscription_transaction_id,
  timestamp,
  affiliate_id,
  affiliate_subid,
  notes
FROM pmpro_membership_orders
WHERE NOT EXISTS (
  SELECT 1 FROM khm_membership_orders k
  WHERE k.code = pmpro_membership_orders.code
);

-- VALIDATION STEPS:
-- 1. Verify row counts:
--    SELECT COUNT(*) FROM pmpro_memberships_users;
--    SELECT COUNT(*) FROM khm_memberships_users;
--    SELECT COUNT(*) FROM pmpro_membership_orders;
--    SELECT COUNT(*) FROM khm_membership_orders;
-- 2. Spot-check sample rows by user_id or payment_transaction_id
-- 3. Run 0003_indexes.sql after this migration

-- NOTES:
-- - Discount codes remain in pmpro_discount_codes* (referenced via code_id).
-- - PMPro stores subtotal/tax/total as varchar; we cast to decimal(10,2) for khm.
-- - PMPro has additional fields (couponamount, checkout_id, certificate_id, certificateamount) 
--   that aren't in khm schema; these can be added to khm_membership_orders if needed.
