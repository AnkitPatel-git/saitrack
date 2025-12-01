-- Add test and serviceBy columns to warehouses table
-- This allows warehouses to be associated with specific providers (Delhivery, BlueDart)
-- and test/production environments

ALTER TABLE `warehouses` 
ADD COLUMN `test` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 for test, 0 for production' AFTER `is_active`,
ADD COLUMN `serviceBy` VARCHAR(50) NOT NULL DEFAULT 'delhivery' COMMENT 'bluedart or delhivery' AFTER `test`;

-- Add indexes for faster lookups
ALTER TABLE `warehouses`
ADD KEY `idx_test` (`test`),
ADD KEY `idx_serviceBy` (`serviceBy`);

-- Add composite index for common queries (pincode + test + serviceBy + is_active)
ALTER TABLE `warehouses`
ADD KEY `idx_pincode_test_serviceBy_active` (`pin_code`, `test`, `serviceBy`, `is_active`);

