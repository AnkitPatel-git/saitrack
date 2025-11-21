-- Add lr_number column to booking table for Delhivery LR (Lorry Receipt) numbers
-- This column stores the primary tracking ID for Delhivery LTL shipments

ALTER TABLE `booking` 
ADD COLUMN `lr_number` VARCHAR(255) NULL COMMENT 'Delhivery LR (Lorry Receipt) number - primary tracking ID for LTL shipments' 
AFTER `waybills`;

-- Add index for faster lookups
CREATE INDEX `booking_lr_number_index` ON `booking` (`lr_number`);

