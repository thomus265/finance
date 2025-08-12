-- Migration script to add unique transaction ID and transaction_attachments table
-- Run this script on existing databases to add the new features

-- Add transaction_id column to transactions table
ALTER TABLE `transactions` ADD COLUMN `transaction_id` varchar(20) NOT NULL UNIQUE AFTER `id`;

-- Create transaction_attachments table
CREATE TABLE `transaction_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_attachments` (`transaction_id`),
  KEY `idx_attachment_id` (`attachment_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_uploaded_at` (`uploaded_at`),
  CONSTRAINT `fk_transaction_attachments_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_attachments_attachment` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Generate unique transaction IDs for existing transactions
UPDATE transactions 
SET transaction_id = CONCAT('TXN-', YEAR(tr_date), '-', LPAD(id, 6, '0'))
WHERE transaction_id IS NULL OR transaction_id = '';

-- Add unique index for transaction_id
ALTER TABLE `transactions` ADD UNIQUE KEY `unique_transaction_id` (`transaction_id`);
