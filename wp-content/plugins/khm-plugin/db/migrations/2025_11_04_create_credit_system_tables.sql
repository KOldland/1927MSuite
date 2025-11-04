-- Credit System Database Tables
-- Migration: 2025_11_04_create_credit_system_tables.sql
-- Purpose: Add comprehensive credit management for KHM membership system

-- Credits allocation and balance table
CREATE TABLE IF NOT EXISTS `khm_user_credits` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `membership_level_id` bigint(20) unsigned NOT NULL,
    `allocation_month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
    `allocated_credits` int(11) NOT NULL DEFAULT 0 COMMENT 'Credits allocated for this month',
    `current_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Current available credits',
    `total_used` int(11) NOT NULL DEFAULT 0 COMMENT 'Total credits used this month',
    `bonus_credits` int(11) NOT NULL DEFAULT 0 COMMENT 'Bonus credits added manually',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_month` (`user_id`, `allocation_month`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_allocation_month` (`allocation_month`),
    KEY `idx_membership_level` (`membership_level_id`),
    KEY `idx_current_balance` (`current_balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Monthly credit allocations and balances for KHM members';

-- Credit usage history table
CREATE TABLE IF NOT EXISTS `khm_credit_usage` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `credits_used` int(11) NOT NULL COMMENT 'Number of credits consumed',
    `purpose` varchar(50) NOT NULL COMMENT 'What credits were used for (download, purchase, etc.)',
    `object_id` bigint(20) unsigned NULL COMMENT 'Related object ID (post_id, order_id, etc.)',
    `balance_before` int(11) NOT NULL COMMENT 'Credit balance before transaction',
    `balance_after` int(11) NOT NULL COMMENT 'Credit balance after transaction',
    `ip_address` varchar(45) NULL COMMENT 'User IP address for tracking',
    `user_agent` text NULL COMMENT 'User agent string',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_purpose` (`purpose`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_object_id` (`object_id`),
    KEY `idx_user_purpose` (`user_id`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detailed history of credit usage for audit and analytics';

-- Add monthly_credits column to membership levels if it doesn't exist
ALTER TABLE `khm_membership_levels` 
ADD COLUMN `monthly_credits` int(11) NOT NULL DEFAULT 0 COMMENT 'Credits allocated per month for this level'
ON DUPLICATE KEY UPDATE `monthly_credits` = `monthly_credits`;

-- Insert default credit allocations for existing levels
UPDATE `khm_membership_levels` 
SET `monthly_credits` = CASE 
    WHEN `name` LIKE '%basic%' OR `name` LIKE '%starter%' THEN 5
    WHEN `name` LIKE '%premium%' OR `name` LIKE '%pro%' THEN 15
    WHEN `name` LIKE '%enterprise%' OR `name` LIKE '%unlimited%' THEN 50
    ELSE 10
END 
WHERE `monthly_credits` = 0;