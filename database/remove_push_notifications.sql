-- Migration to remove push notification functionality
-- Run this script to remove all push notification related database tables and data

-- Drop push_subscriptions table if it exists
DROP TABLE IF EXISTS `push_subscriptions`;

-- Remove any push notification related data from notifications table
-- (Optional: only if you want to remove existing push notification records)
-- DELETE FROM notifications WHERE type LIKE '%push%';

-- Remove any push notification related settings
-- DELETE FROM system_settings WHERE setting_key LIKE '%push%';

-- Note: This migration removes the push_subscriptions table and related data
-- Make sure to backup your database before running this migration
