-- Affiliate Tracking System Database Tables
-- Migration: 2025_11_04_create_affiliate_system_tables.sql
-- Purpose: Add comprehensive affiliate tracking and URL generation for KHM membership system

-- Affiliate codes table - stores unique codes for each member
CREATE TABLE IF NOT EXISTS `khm_affiliate_codes` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `affiliate_code` varchar(20) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `total_clicks` int(11) NOT NULL DEFAULT 0,
    `total_conversions` int(11) NOT NULL DEFAULT 0,
    `total_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    UNIQUE KEY `affiliate_code` (`affiliate_code`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Unique affiliate codes for each member with summary stats';

-- Affiliate clicks table - tracks all clicks on affiliate links
CREATE TABLE IF NOT EXISTS `khm_affiliate_clicks` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `affiliate_code` varchar(20) NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `visitor_ip` varchar(45) NOT NULL,
    `user_agent` text NULL,
    `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `referrer` text NULL,
    `converted` tinyint(1) NOT NULL DEFAULT 0,
    `converted_at` datetime NULL,
    `conversion_value` decimal(10,2) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_affiliate_code` (`affiliate_code`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_clicked_at` (`clicked_at`),
    KEY `idx_converted` (`converted`),
    KEY `idx_visitor_tracking` (`visitor_ip`, `clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detailed tracking of all affiliate link clicks';

-- Affiliate conversions table - tracks successful conversions
CREATE TABLE IF NOT EXISTS `khm_affiliate_conversions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `affiliate_code` varchar(20) NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `conversion_type` varchar(50) NOT NULL COMMENT 'purchase, membership, download, etc.',
    `commission_amount` decimal(10,2) NOT NULL,
    `order_value` decimal(10,2) NULL COMMENT 'Total value of the order/action',
    `converted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, paid, cancelled',
    `paid_at` datetime NULL,
    `notes` text NULL,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_affiliate_code` (`affiliate_code`),
    KEY `idx_conversion_type` (`conversion_type`),
    KEY `idx_converted_at` (`converted_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Successful conversions and commission tracking';

-- Affiliate generations table - logs when affiliate URLs are created
CREATE TABLE IF NOT EXISTS `khm_affiliate_generations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `affiliate_url` text NOT NULL,
    `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text NULL,
    `platform` varchar(50) NULL COMMENT 'Social platform where URL will be shared',
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_generated_at` (`generated_at`),
    KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log of all affiliate URL generations for analytics';

-- Social shares table - tracks social media sharing activity
CREATE TABLE IF NOT EXISTS `khm_social_shares` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `platform` varchar(50) NOT NULL COMMENT 'facebook, twitter, linkedin, etc.',
    `has_affiliate` tinyint(1) NOT NULL DEFAULT 0,
    `has_custom_message` tinyint(1) NOT NULL DEFAULT 0,
    `hashtags_count` int(11) NOT NULL DEFAULT 0,
    `content_length` int(11) NOT NULL DEFAULT 0,
    `shared_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_platform` (`platform`),
    KEY `idx_shared_at` (`shared_at`),
    KEY `idx_has_affiliate` (`has_affiliate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Social media sharing analytics and tracking';

-- Commission rates table - configurable commission structure
CREATE TABLE IF NOT EXISTS `khm_commission_rates` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `conversion_type` varchar(50) NOT NULL,
    `membership_level_id` bigint(20) unsigned NULL COMMENT 'Specific level or NULL for all',
    `rate_type` enum('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `rate_value` decimal(10,4) NOT NULL COMMENT 'Percentage (0.05 = 5%) or fixed amount',
    `min_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
    `max_commission` decimal(10,2) NULL,
    `effective_from` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `effective_until` datetime NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_conversion_type` (`conversion_type`),
    KEY `idx_membership_level` (`membership_level_id`),
    KEY `idx_effective_dates` (`effective_from`, `effective_until`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurable commission rates for different conversion types';

-- Insert default commission rates
INSERT IGNORE INTO `khm_commission_rates` (`conversion_type`, `rate_type`, `rate_value`, `min_commission`) VALUES
('article_purchase', 'percentage', 0.1000, 0.50),
('membership_signup', 'percentage', 0.2000, 5.00),
('gift_purchase', 'percentage', 0.0800, 0.25),
('subscription_renewal', 'percentage', 0.0500, 1.00);