-- 4A Intent Scoring Core Tables
-- Migration: 2025_11_12_create_cp_tables.sql
-- Purpose: Create canonical prospect intelligence tables + indexes/partitions

CREATE TABLE IF NOT EXISTS `cp_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id` CHAR(36) NOT NULL,
    `occurred_at` DATETIME NOT NULL,
    `ingested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actor_email` VARCHAR(255) DEFAULT NULL,
    `actor_name` VARCHAR(255) DEFAULT NULL,
    `company_domain` VARCHAR(255) DEFAULT NULL,
    `source` VARCHAR(40) NOT NULL,
    `touchpoint` VARCHAR(60) NOT NULL,
    `stage_hint` VARCHAR(30) DEFAULT NULL,
    `depth_scroll` DECIMAL(5,2) DEFAULT NULL,
    `depth_dwell_sec` DECIMAL(10,2) DEFAULT NULL,
    `depth_pct_complete` DECIMAL(5,2) DEFAULT NULL,
    `topic_tax` JSON DEFAULT NULL,
    `rep_involved` VARCHAR(255) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_id` (`event_id`),
    KEY `idx_actor_email` (`actor_email`),
    KEY `idx_company_domain` (`company_domain`),
    KEY `idx_touchpoint_occurred` (`touchpoint`, `occurred_at`),
    KEY `idx_stage_hint` (`stage_hint`),
    KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (TO_DAYS(`occurred_at`)) (
    PARTITION p2024_q4 VALUES LESS THAN (TO_DAYS('2025-01-01')),
    PARTITION p2025_q1 VALUES LESS THAN (TO_DAYS('2025-04-01')),
    PARTITION p2025_q2 VALUES LESS THAN (TO_DAYS('2025-07-01')),
    PARTITION p2025_q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
    PARTITION p2025_q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
    PARTITION p2026_q1 VALUES LESS THAN (TO_DAYS('2026-04-01')),
    PARTITION p2026_q2 VALUES LESS THAN (TO_DAYS('2026-07-01')),
    PARTITION p2026_q3 VALUES LESS THAN (TO_DAYS('2026-10-01')),
    PARTITION p2026_q4 VALUES LESS THAN (TO_DAYS('2027-01-01')),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);

CREATE TABLE IF NOT EXISTS `cp_scores_person` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_email` VARCHAR(255) NOT NULL,
    `score_date` DATE NOT NULL,
    `person_score` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stage` VARCHAR(30) DEFAULT NULL,
    `last_touch` VARCHAR(60) DEFAULT NULL,
    `last_touch_at` DATETIME DEFAULT NULL,
    `mql_flag` TINYINT(1) NOT NULL DEFAULT 0,
    `sql_flag` TINYINT(1) NOT NULL DEFAULT 0,
    `nba_recommendation` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_person_date` (`actor_email`, `score_date`),
    KEY `idx_person_stage` (`stage`),
    KEY `idx_person_mql` (`mql_flag`),
    KEY `idx_person_sql` (`sql_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cp_scores_company` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_domain` VARCHAR(255) NOT NULL,
    `score_date` DATE NOT NULL,
    `company_score` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stage_mode` VARCHAR(30) DEFAULT NULL,
    `engaged_contacts` INT UNSIGNED NOT NULL DEFAULT 0,
    `hot_flag` TINYINT(1) NOT NULL DEFAULT 0,
    `hot_since` DATE DEFAULT NULL,
    `nba_recommendation` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_company_date` (`company_domain`, `score_date`),
    KEY `idx_company_hot` (`hot_flag`),
    KEY `idx_company_stage` (`stage_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cp_weights` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `touchpoint` VARCHAR(80) NOT NULL,
    `stage_default` VARCHAR(30) NOT NULL,
    `category` ENUM('Low', 'Medium', 'High', 'PoS') NOT NULL DEFAULT 'Low',
    `base_weight` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_touchpoint` (`touchpoint`),
    KEY `idx_weight_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Base touchpoint weights for deterministic scoring';

CREATE TABLE IF NOT EXISTS `cp_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stage` VARCHAR(30) NOT NULL,
    `topic_taxonomy` JSON DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body_markdown` TEXT DEFAULT NULL,
    `asset_url` VARCHAR(255) DEFAULT NULL,
    `cta_type` VARCHAR(60) DEFAULT NULL,
    `priority` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_actions_stage_priority` (`stage`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Next Best Action templates grouped by stage/topic';

CREATE TABLE IF NOT EXISTS `cp_ingestion_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source` VARCHAR(40) NOT NULL,
    `last_success` DATETIME DEFAULT NULL,
    `last_error` DATETIME DEFAULT NULL,
    `error_payload` JSON DEFAULT NULL,
    `event_count_24h` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Connector heartbeat + throughput logging';

CREATE TABLE IF NOT EXISTS `cp_usage_audit` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `action` ENUM('view', 'export', 'webhook') NOT NULL,
    `target_type` ENUM('person', 'company', 'account') NOT NULL DEFAULT 'person',
    `target_identifier` VARCHAR(255) DEFAULT NULL,
    `performed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `metadata` JSON DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_usage_role_action` (`role`, `action`),
    KEY `idx_usage_performed_at` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Compliance log for data access + exports';
