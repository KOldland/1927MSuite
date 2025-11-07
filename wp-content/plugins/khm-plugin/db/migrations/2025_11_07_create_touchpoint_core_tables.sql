-- TouchPoint Marketing Suite Core Tables Migration
-- Migration: 2025_11_07_create_touchpoint_core_tables.sql
-- Purpose: Create missing core tables for eCommerce, Library, Gifts, and Email systems

-- Article Products Table (for eCommerce functionality)
CREATE TABLE IF NOT EXISTS `khm_article_products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `regular_price` decimal(10,2) NOT NULL DEFAULT 5.99,
    `member_price` decimal(10,2) DEFAULT NULL,
    `member_discount_percent` int(11) DEFAULT 20,
    `is_purchasable` tinyint(1) DEFAULT 1,
    `purchase_gives_pdf` tinyint(1) DEFAULT 1,
    `purchase_saves_to_library` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_post` (`post_id`),
    KEY `idx_price` (`regular_price`),
    KEY `idx_purchasable` (`is_purchasable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Product configuration for individual articles/posts';

-- Shopping Cart Table
CREATE TABLE IF NOT EXISTS `khm_shopping_cart` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `post_id` int(11) NOT NULL,
    `quantity` int(11) DEFAULT 1,
    `price` decimal(10,2) NOT NULL,
    `member_price` decimal(10,2) DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_post` (`user_id`, `post_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Temporary shopping cart for guest and member users';

-- Purchases Table
CREATE TABLE IF NOT EXISTS `khm_purchases` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `post_id` int(11) NOT NULL,
    `order_id` int(11) DEFAULT NULL,
    `purchase_price` decimal(10,2) NOT NULL,
    `member_discount` decimal(10,2) DEFAULT 0.00,
    `payment_method` varchar(50) DEFAULT 'stripe',
    `transaction_id` varchar(255) DEFAULT NULL,
    `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
    `pdf_downloaded` tinyint(1) DEFAULT 0,
    `saved_to_library` tinyint(1) DEFAULT 0,
    `purchase_data` text,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_status` (`status`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual article purchases and transaction records';

-- Member Library Table (for saved articles)
CREATE TABLE IF NOT EXISTS `khm_member_library` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `post_id` int(11) NOT NULL,
    `category_id` int(11) DEFAULT NULL,
    `saved_via` enum('purchase','credit','manual','gift') DEFAULT 'manual',
    `notes` text DEFAULT NULL,
    `tags` text DEFAULT NULL,
    `is_favorite` tinyint(1) DEFAULT 0,
    `last_accessed` datetime DEFAULT NULL,
    `access_count` int(11) DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_post` (`user_id`, `post_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_saved_via` (`saved_via`),
    KEY `idx_is_favorite` (`is_favorite`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Member personal library for saved articles';

-- Library Categories Table
CREATE TABLE IF NOT EXISTS `khm_library_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `color` varchar(7) DEFAULT '#0073aa',
    `sort_order` int(11) DEFAULT 0,
    `is_default` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User-defined categories for organizing library items';

-- Gift System Tables
CREATE TABLE IF NOT EXISTS `khm_gifts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `recipient_email` varchar(255) NOT NULL,
    `recipient_name` varchar(255) DEFAULT NULL,
    `post_id` int(11) NOT NULL,
    `message` text DEFAULT NULL,
    `gift_price` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) DEFAULT 'stripe',
    `transaction_id` varchar(255) DEFAULT NULL,
    `gift_code` varchar(100) NOT NULL,
    `status` enum('pending','sent','redeemed','expired','cancelled') DEFAULT 'pending',
    `expires_at` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_gift_code` (`gift_code`),
    KEY `idx_sender_id` (`sender_id`),
    KEY `idx_recipient_email` (`recipient_email`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_status` (`status`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Gift transactions for sharing articles with others';

-- Gift Redemptions Table
CREATE TABLE IF NOT EXISTS `khm_gift_redemptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `gift_id` int(11) NOT NULL,
    `redeemer_id` int(11) DEFAULT NULL,
    `redeemer_email` varchar(255) DEFAULT NULL,
    `redemption_method` enum('existing_user','new_signup','guest') DEFAULT 'guest',
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `redeemed_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gift_id` (`gift_id`),
    KEY `idx_redeemer_id` (`redeemer_id`),
    KEY `idx_redeemer_email` (`redeemer_email`),
    KEY `idx_redeemed_at` (`redeemed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Record of gift redemptions and who redeemed them';

-- Email Queue Table (for enhanced email system)
CREATE TABLE IF NOT EXISTS `khm_email_queue` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `to_email` varchar(255) NOT NULL,
    `to_name` varchar(255) DEFAULT NULL,
    `from_email` varchar(255) DEFAULT NULL,
    `from_name` varchar(255) DEFAULT NULL,
    `subject` text NOT NULL,
    `message` longtext NOT NULL,
    `headers` text DEFAULT NULL,
    `attachments` text DEFAULT NULL,
    `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
    `email_type` varchar(50) DEFAULT NULL,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `related_id` bigint(20) unsigned DEFAULT NULL,
    `status` enum('queued','processing','sent','failed','cancelled') DEFAULT 'queued',
    `attempts` int(11) DEFAULT 0,
    `max_attempts` int(11) DEFAULT 3,
    `scheduled_at` datetime DEFAULT NULL,
    `sent_at` datetime DEFAULT NULL,
    `failed_at` datetime DEFAULT NULL,
    `error_message` text DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_scheduled_at` (`scheduled_at`),
    KEY `idx_email_type` (`email_type`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_attempts` (`attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Queue for outgoing emails with priority and retry logic';

-- Email Logs Table
CREATE TABLE IF NOT EXISTS `khm_email_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `queue_id` bigint(20) unsigned DEFAULT NULL,
    `to_email` varchar(255) NOT NULL,
    `subject` text NOT NULL,
    `email_type` varchar(50) DEFAULT NULL,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `status` enum('sent','bounced','complained','delivered','opened','clicked') DEFAULT 'sent',
    `provider` varchar(50) DEFAULT NULL,
    `provider_id` varchar(255) DEFAULT NULL,
    `response_data` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_queue_id` (`queue_id`),
    KEY `idx_to_email` (`to_email`),
    KEY `idx_status` (`status`),
    KEY `idx_email_type` (`email_type`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log of all email activity for tracking and analytics';

-- Foreign Key Constraints
ALTER TABLE `khm_member_library` 
ADD CONSTRAINT `fk_library_category` 
FOREIGN KEY (`category_id`) REFERENCES `khm_library_categories` (`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `khm_gift_redemptions` 
ADD CONSTRAINT `fk_redemption_gift` 
FOREIGN KEY (`gift_id`) REFERENCES `khm_gifts` (`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `khm_email_logs` 
ADD CONSTRAINT `fk_log_queue` 
FOREIGN KEY (`queue_id`) REFERENCES `khm_email_queue` (`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;