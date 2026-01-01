-- Update existing warehouses with default test and serviceBy values
-- This ensures backward compatibility for warehouses created before these columns were added

-- Set default values for warehouses that have NULL or empty test/serviceBy
UPDATE `warehouses` 
SET 
    `test` = 1,
    `serviceBy` = 'delhivery'
WHERE 
    (`test` IS NULL OR `test` = 0) 
    AND (`serviceBy` IS NULL OR `serviceBy` = '');

-- Verify the update
SELECT 
    COUNT(*) as total_warehouses,
    SUM(CASE WHEN `test` = 1 AND `serviceBy` = 'delhivery' THEN 1 ELSE 0 END) as updated_warehouses,
    SUM(CASE WHEN `test` IS NULL OR `serviceBy` IS NULL OR `serviceBy` = '' THEN 1 ELSE 0 END) as remaining_null
FROM `warehouses`;








