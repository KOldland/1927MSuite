# KHM Marketing Suite - Database Setup Guide

## 🎯 Overview
This document provides the complete SQL commands needed to set up the KHM Marketing Suite database tables for:
- **Credit System**: Member credit allocations and usage tracking
- **Affiliate System**: URL generation, click tracking, and commission management
- **Social Sharing**: Enhanced tracking with affiliate integration

## 📊 Database Tables to Create

### 1. Credit System Tables

```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Affiliate System Tables

```sql
-- Affiliate codes table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate clicks table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate conversions table
CREATE TABLE IF NOT EXISTS `khm_affiliate_conversions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `affiliate_code` varchar(20) NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `conversion_type` varchar(50) NOT NULL,
    `commission_amount` decimal(10,2) NOT NULL,
    `order_value` decimal(10,2) NULL,
    `converted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` varchar(20) NOT NULL DEFAULT 'pending',
    `paid_at` datetime NULL,
    `notes` text NULL,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_affiliate_code` (`affiliate_code`),
    KEY `idx_conversion_type` (`conversion_type`),
    KEY `idx_converted_at` (`converted_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate generations table
CREATE TABLE IF NOT EXISTS `khm_affiliate_generations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `member_id` bigint(20) unsigned NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `affiliate_url` text NOT NULL,
    `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text NULL,
    `platform` varchar(50) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_generated_at` (`generated_at`),
    KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social shares table
CREATE TABLE IF NOT EXISTS `khm_social_shares` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `post_id` bigint(20) unsigned NOT NULL,
    `platform` varchar(50) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commission rates table
CREATE TABLE IF NOT EXISTS `khm_commission_rates` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `conversion_type` varchar(50) NOT NULL,
    `membership_level_id` bigint(20) unsigned NULL,
    `rate_type` enum('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `rate_value` decimal(10,4) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Default Commission Rates

```sql
-- Insert default commission rates
INSERT IGNORE INTO `khm_commission_rates` (`conversion_type`, `rate_type`, `rate_value`, `min_commission`) VALUES
('article_purchase', 'percentage', 0.1000, 0.50),
('membership_signup', 'percentage', 0.2000, 5.00),
('gift_purchase', 'percentage', 0.0800, 0.25),
('subscription_renewal', 'percentage', 0.0500, 1.00);
```

## 🚀 Implementation Status

### ✅ Completed Features
- **Affiliate URL Generation**: Dynamic member-specific URLs with tracking codes
- **Social Media Integration**: Enhanced sharing modal with platform optimization
- **Hashtag Generation**: Automatic hashtags from article categories/tags
- **Character Limits**: Platform-specific content optimization
- **AJAX Handlers**: Backend endpoints for affiliate URL generation
- **JavaScript Integration**: Frontend affiliate URL loading and user feedback

### 🔧 Core Services Architecture
- **AffiliateService.php**: Complete affiliate tracking and URL generation
- **CreditService.php**: Credit allocation and usage management
- **GiftService.php**: Gift creation and redemption system
- **ECommerceService.php**: Purchase and order management
- **LibraryService.php**: Member library functionality
- **PDFService.php**: PDF generation and download management

### 📱 Frontend Integration
- **Enhanced Social Modal**: Unified sharing and gifting interface
- **Real-time Preview**: Platform-specific content optimization
- **User Feedback**: Success/error notifications
- **Responsive Design**: Works across all devices

## 🎯 Next Steps

1. **Execute Database Creation**: Run the SQL commands above in your database
2. **Test Affiliate URLs**: Verify member-specific URL generation
3. **Test Credit System**: Verify credit allocation and usage tracking
4. **Admin Dashboard**: Build management interface for monitoring
5. **Analytics Integration**: Set up reporting and analytics

## 🔗 Key Integration Points

- **AJAX Endpoint**: `kss_get_affiliate_url` for dynamic URL generation
- **JavaScript Function**: `loadAffiliateUrl()` for frontend integration
- **Database Tables**: 8 tables for complete tracking and management
- **Commission System**: Configurable rates for different conversion types

Your KHM Marketing Suite is now ready for full deployment once the database tables are created!