-- Discount Codes Table for KHM Membership Plugin
-- Created: 2025-10-28

CREATE TABLE IF NOT EXISTS khm_discount_codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    type ENUM('percent','amount') NOT NULL DEFAULT 'amount',
    value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    usage_limit INT UNSIGNED DEFAULT NULL, -- total uses allowed
    per_user_limit INT UNSIGNED DEFAULT NULL, -- uses per user
    levels VARCHAR(255) DEFAULT NULL, -- comma-separated level IDs
    status ENUM('active','expired','inactive') NOT NULL DEFAULT 'active',
    times_used INT UNSIGNED NOT NULL DEFAULT 0,
    trial_days INT UNSIGNED DEFAULT NULL, -- free trial days
    trial_amount DECIMAL(10,2) DEFAULT NULL, -- free trial amount
    first_payment_only TINYINT(1) NOT NULL DEFAULT 0, -- discount applies only to first payment
    recurring_discount_type ENUM('percent','amount') DEFAULT NULL, -- type for recurring discount
    recurring_discount_amount DECIMAL(10,2) DEFAULT NULL, -- value for recurring discount
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for fast lookups
CREATE INDEX idx_code_status ON khm_discount_codes (code, status);
CREATE INDEX idx_start_end ON khm_discount_codes (start_date, end_date);
