-- 2025_11_05_add_order_failure_refund_columns.sql
-- Extend khm_membership_orders with failure/refund auditing columns.

ALTER TABLE `khm_membership_orders`
    ADD COLUMN `failure_code` VARCHAR(64) DEFAULT NULL AFTER `notes`,
    ADD COLUMN `failure_message` TEXT DEFAULT NULL AFTER `failure_code`,
    ADD COLUMN `failure_at` DATETIME DEFAULT NULL AFTER `failure_message`,
    ADD COLUMN `refund_amount` DECIMAL(10,2) DEFAULT NULL AFTER `failure_at`,
    ADD COLUMN `refund_reason` TEXT DEFAULT NULL AFTER `refund_amount`,
    ADD COLUMN `refunded_at` DATETIME DEFAULT NULL AFTER `refund_reason`;

CREATE INDEX `idx_failure_at` ON `khm_membership_orders` (`failure_at`);
CREATE INDEX `idx_refunded_at` ON `khm_membership_orders` (`refunded_at`);
