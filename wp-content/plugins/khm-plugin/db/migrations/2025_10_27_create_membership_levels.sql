-- 2025_10_27_create_membership_levels.sql
-- Create dedicated KHM membership level tables (levels + meta).

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
