-- Add discount metadata columns to khm_membership_orders
-- Migration: 2025_10_29_add_discount_metadata_to_orders.sql

ALTER TABLE khm_membership_orders
ADD COLUMN discount_code VARCHAR(32) DEFAULT NULL AFTER notes,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT NULL AFTER discount_code,
ADD COLUMN trial_days INT UNSIGNED DEFAULT NULL AFTER discount_amount,
ADD COLUMN trial_amount DECIMAL(10,2) DEFAULT NULL AFTER trial_days,
ADD COLUMN first_payment_only TINYINT(1) DEFAULT 0 AFTER trial_amount,
ADD COLUMN recurring_discount_type ENUM('percent','amount') DEFAULT NULL AFTER first_payment_only,
ADD COLUMN recurring_discount_amount DECIMAL(10,2) DEFAULT NULL AFTER recurring_discount_type,
ADD INDEX idx_discount_code (discount_code);
