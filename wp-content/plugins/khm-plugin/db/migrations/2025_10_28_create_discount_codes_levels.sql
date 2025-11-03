-- Discount Code to Level Mapping Table
-- Created: 2025-10-28

CREATE TABLE IF NOT EXISTS khm_discount_codes_levels (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    discount_code_id BIGINT UNSIGNED NOT NULL,
    level_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY code_level (discount_code_id, level_id),
    FOREIGN KEY (discount_code_id) REFERENCES khm_discount_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES khm_membership_levels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
