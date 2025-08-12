-- Migration script to add workflow system to existing finance application
-- Run this script on existing databases to add workflow functionality

-- Add new fields to transactions table
ALTER TABLE `transactions` 
ADD COLUMN `department_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Operations, 2=Programs' AFTER `user_id`,
ADD COLUMN `review_comments` text DEFAULT NULL AFTER `rejection_reason`,
ADD COLUMN `reviewed_by` int(11) DEFAULT NULL AFTER `review_comments`,
ADD COLUMN `resubmitted_at` datetime DEFAULT NULL AFTER `last_updated_by`,
ADD COLUMN `resubmitted_count` int(11) DEFAULT 0 AFTER `resubmitted_at`;

-- Update default state to 1 (submitted) instead of 0 (draft)
ALTER TABLE `transactions` MODIFY `state` tinyint(1) NOT NULL DEFAULT 1;

-- Create user_types lookup table
CREATE TABLE `user_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_value` tinyint(1) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `type_description` text DEFAULT NULL,
  `can_submit_transactions` tinyint(1) DEFAULT 1,
  `can_review_transactions` tinyint(1) DEFAULT 0,
  `can_approve_transactions` tinyint(1) DEFAULT 0,
  `department_type` tinyint(1) DEFAULT NULL COMMENT '1=Operations, 2=Programs, NULL=Any',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type_value` (`type_value`),
  KEY `idx_department_type` (`department_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert user types
INSERT INTO `user_types` (`type_value`, `type_name`, `type_description`, `can_submit_transactions`, `can_review_transactions`, `can_approve_transactions`, `department_type`) VALUES
(1, 'Operations User', 'Can submit transactions following Operations workflow', 1, 0, 0, 1),
(2, 'Programs User', 'Can submit transactions following Programs workflow', 1, 0, 0, 2),
(3, 'Finance Team', 'Can review, approve, and manage all transactions', 1, 1, 1, NULL);

-- Create workflow_states lookup table
CREATE TABLE `workflow_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_value` tinyint(1) NOT NULL,
  `state_name` varchar(50) NOT NULL,
  `state_description` text DEFAULT NULL,
  `department_type` tinyint(1) NOT NULL COMMENT '1=Operations, 2=Programs, 0=Both',
  `is_editable` tinyint(1) DEFAULT 0 COMMENT '1=User can edit transaction, 0=Read only',
  `is_final` tinyint(1) DEFAULT 0 COMMENT '1=Final state, 0=Can transition',
  `next_states` varchar(100) DEFAULT NULL COMMENT 'Comma-separated list of valid next states',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_state_dept` (`state_value`, `department_type`),
  KEY `idx_department_type` (`department_type`),
  KEY `idx_state_value` (`state_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert workflow states
INSERT INTO `workflow_states` (`state_value`, `state_name`, `state_description`, `department_type`, `is_editable`, `is_final`, `next_states`) VALUES
(1, 'Submitted for Review', 'Transaction submitted and waiting for finance team review', 0, 0, 0, '2,4'),
(2, 'Under Review', 'Finance team is reviewing the transaction', 0, 0, 0, '3,4,5'),
(3, 'Processing', 'Transaction approved and being processed (Operations only)', 1, 0, 0, '5'),
(4, 'Sent Back', 'Transaction sent back to user for corrections', 0, 1, 0, '1'),
(5, 'Sent to Payroll', 'Transaction approved and sent to payroll department', 0, 0, 0, '6'),
(6, 'Paid', 'Transaction has been paid - final status', 0, 0, 1, '');

-- Create notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'transaction_submitted, transaction_approved, transaction_sent_back, transaction_paid, etc.',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_notifications` (`user_id`,`is_read`),
  KEY `idx_notification_date` (`created_at`),
  KEY `idx_notification_type` (`type`),
  KEY `fk_notification_transaction` (`transaction_id`),
  CONSTRAINT `fk_notification_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add new indexes to transactions table
ALTER TABLE `transactions` 
ADD KEY `idx_state` (`state`),
ADD KEY `idx_department_type` (`department_type`),
ADD KEY `idx_reviewed_by` (`reviewed_by`);

-- Add foreign key constraints for new fields
ALTER TABLE `transactions`
ADD CONSTRAINT `fk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- Update existing transactions to have proper department_type based on user_type
UPDATE transactions t 
JOIN users u ON t.user_id = u.user_id 
SET t.department_type = CASE 
    WHEN u.user_type = 1 THEN 1  -- Operations
    WHEN u.user_type = 2 THEN 2  -- Programs
    ELSE 1  -- Default to Operations
END;

-- Update existing user types to match new system
UPDATE users SET user_type = 1 WHERE user_type = 3;  -- Convert old user_type 3 to Operations User
UPDATE users SET user_type = 3 WHERE user_type = 4;  -- Convert old user_type 4 to Finance Team

-- Add sample users if they don't exist
INSERT IGNORE INTO `users` (`user_id`, `user_name`, `user_email`, `user_type`, `email_verified`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 'Operations User', 'operations.user@undp.org', 1, 1, NOW(), NOW(), NOW()),
(2, 'Programs User', 'programs.user@undp.org', 2, 1, NOW(), NOW(), NOW());

-- Create security log table
CREATE TABLE IF NOT EXISTS `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_security_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Create email log table
CREATE TABLE `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed','bounced') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_recipient` (`recipient_email`),
  KEY `idx_email_transaction` (`transaction_id`),
  KEY `idx_email_sent_at` (`sent_at`),
  CONSTRAINT `fk_email_log_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
