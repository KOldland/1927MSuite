-- 0001_create_khm_tables.sql
-- Create the core KHM tables for memberships, levels, and orders.
-- Edit columns to match your existing PMPro schema if needed before running.

CREATE TABLE IF NOT EXISTS `khm_membership_levels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `confirmation` LONGTEXT NOT NULL,
  `initial_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billing_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cycle_number` INT NOT NULL DEFAULT 0,
  `cycle_period` ENUM('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
  `billing_limit` INT NOT NULL DEFAULT 0,
  `trial_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `trial_limit` INT NOT NULL DEFAULT 0,
  `allow_signups` TINYINT(1) NOT NULL DEFAULT 1,
  `expiration_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `expiration_period` ENUM('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_allow_signups` (`allow_signups`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `khm_membership_levelmeta` (
  `meta_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level_id` BIGINT UNSIGNED NOT NULL,
  `meta_key` VARCHAR(255) NOT NULL,
  `meta_value` LONGTEXT,
  PRIMARY KEY (`meta_id`),
  KEY `idx_level_id` (`level_id`),
  KEY `idx_meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `khm_memberships_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `membership_id` BIGINT UNSIGNED NOT NULL,
  `code_id` BIGINT UNSIGNED DEFAULT NULL,
  `initial_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billing_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cycle_number` INT NOT NULL DEFAULT 0,
  `cycle_period` ENUM('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
  `billing_limit` INT NOT NULL DEFAULT 0,
  `trial_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `trial_limit` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `startdate` DATETIME NOT NULL,
  `enddate` DATETIME DEFAULT NULL,
  `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `membership_id` (`membership_id`),
  KEY `status` (`status`),
  KEY `modified` (`modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `khm_membership_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(64) NOT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `membership_id` BIGINT UNSIGNED NOT NULL,
  `paypal_token` VARCHAR(128) DEFAULT NULL,
  `billing_name` VARCHAR(191) DEFAULT NULL,
  `billing_street` VARCHAR(191) DEFAULT NULL,
  `billing_city` VARCHAR(191) DEFAULT NULL,
  `billing_state` VARCHAR(64) DEFAULT NULL,
  `billing_zip` VARCHAR(32) DEFAULT NULL,
  `billing_country` VARCHAR(128) DEFAULT NULL,
  `billing_phone` VARCHAR(32) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_type` VARCHAR(64) DEFAULT NULL,
  `cardtype` VARCHAR(64) DEFAULT NULL,
  `accountnumber` VARCHAR(64) DEFAULT NULL,
  `expirationmonth` CHAR(2) DEFAULT NULL,
  `expirationyear` VARCHAR(4) DEFAULT NULL,
  `status` VARCHAR(64) NOT NULL DEFAULT 'pending',
  `gateway` VARCHAR(64) DEFAULT NULL,
  `gateway_environment` VARCHAR(64) DEFAULT NULL,
  `payment_transaction_id` VARCHAR(128) DEFAULT NULL,
  `subscription_transaction_id` VARCHAR(128) DEFAULT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `affiliate_id` VARCHAR(64) DEFAULT NULL,
  `affiliate_subid` VARCHAR(64) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `failure_code` VARCHAR(64) DEFAULT NULL,
  `failure_message` TEXT DEFAULT NULL,
  `failure_at` DATETIME DEFAULT NULL,
  `refund_amount` DECIMAL(10,2) DEFAULT NULL,
  `refund_reason` TEXT DEFAULT NULL,
  `refunded_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `membership_id` (`membership_id`),
  KEY `status` (`status`),
  KEY `timestamp` (`timestamp`),
  KEY `gateway` (`gateway`),
  KEY `gateway_environment` (`gateway_environment`),
  KEY `payment_transaction_id` (`payment_transaction_id`(64)),
  KEY `subscription_transaction_id` (`subscription_transaction_id`(64)),
  KEY `idx_failure_at` (`failure_at`),
  KEY `idx_refunded_at` (`refunded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Discount Codes Table
CREATE TABLE IF NOT EXISTS `khm_discount_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) NOT NULL UNIQUE,
  `type` ENUM('percent','amount') NOT NULL DEFAULT 'amount',
  `value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `start_date` DATETIME DEFAULT NULL,
  `end_date` DATETIME DEFAULT NULL,
  `usage_limit` INT UNSIGNED DEFAULT NULL,
  `per_user_limit` INT UNSIGNED DEFAULT NULL,
  `levels` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','expired','inactive') NOT NULL DEFAULT 'active',
  `times_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code_status` (`code`, `status`),
  KEY `idx_start_end` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Discount Code to Level Mapping Table
CREATE TABLE IF NOT EXISTS `khm_discount_codes_levels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `discount_code_id` BIGINT UNSIGNED NOT NULL,
  `level_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_level` (`discount_code_id`, `level_id`),
  KEY `discount_code_id` (`discount_code_id`),
  KEY `level_id` (`level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Discount Code Usage Audit Table
CREATE TABLE IF NOT EXISTS `khm_discount_codes_uses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `discount_code_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `order_id` BIGINT UNSIGNED DEFAULT NULL,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `session_id` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discount_code_id` (`discount_code_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
