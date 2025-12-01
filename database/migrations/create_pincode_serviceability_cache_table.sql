-- Create pincode_serviceability_cache table
-- This table caches pincode serviceability results from delivery providers (Delhivery, BlueDart)
-- to avoid repeated API calls and improve performance

CREATE TABLE IF NOT EXISTS `pincode_serviceability_cache` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pincode` VARCHAR(10) NOT NULL,
    `weight` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    `test` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 for test, 0 for production',
    `serviceBy` VARCHAR(50) NOT NULL DEFAULT 'delhivery' COMMENT 'bluedart or delhivery',
    `is_serviceable` TINYINT(1) NOT NULL DEFAULT 0,
    `serviceability_data` JSON NULL,
    `full_response_data` JSON NULL,
    `cached_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pincode_weight_test_serviceBy_unique` (`pincode`, `weight`, `test`, `serviceBy`),
    KEY `idx_pincode` (`pincode`),
    KEY `idx_weight` (`weight`),
    KEY `idx_test` (`test`),
    KEY `idx_serviceBy` (`serviceBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

