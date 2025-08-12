-- Migration: Create email_log table for tracking email notifications
-- Created: 2025-08-04
-- Purpose: Track all email notifications sent by the system

-- Create email_log table
CREATE TABLE IF NOT EXISTS `email_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `recipient_email` varchar(255) NOT NULL,
    `subject` varchar(500) NOT NULL,
    `transaction_id` varchar(50) DEFAULT NULL,
    `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` enum('sent','failed','pending') NOT NULL DEFAULT 'sent',
    `error_message` text DEFAULT NULL,
    `email_type` varchar(100) DEFAULT 'notification',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recipient_email` (`recipient_email`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_sent_at` (`sent_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add some sample data for testing (optional)
-- INSERT INTO `email_log` (`recipient_email`, `subject`, `transaction_id`, `status`, `email_type`) 
-- VALUES 
-- ('test@example.com', 'Test Email', 'TXN-2025-000001', 'sent', 'test'),
-- ('admin@example.com', 'System Notification', NULL, 'sent', 'system');

-- Create index for better performance on common queries
CREATE INDEX IF NOT EXISTS `idx_email_log_composite` ON `email_log` (`recipient_email`, `sent_at`, `status`);

-- Add foreign key constraint if transactions table exists
-- Note: This is commented out because transaction_id might be a string reference
-- ALTER TABLE `email_log` 
-- ADD CONSTRAINT `fk_email_log_transaction` 
-- FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) 
-- ON DELETE SET NULL ON UPDATE CASCADE;
