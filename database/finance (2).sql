-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 08:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `file_path` text NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_assignments`
--

CREATE TABLE `finance_assignments` (
  `assignment_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `assignment_type` enum('reviewer','processor') NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `magic_links`
--

CREATE TABLE `magic_links` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `magic_links`
--

INSERT INTO `magic_links` (`id`, `user_id`, `token`, `expires_at`, `used`, `stamp`) VALUES
(1, 1, '3e606c336160f21f2bcedb3a1549ea7666058e47f52830b6170cc4f679a773d3', '2025-07-21 14:14:26', 0, '2025-07-21 12:04:26'),
(2, 1, '0692ce7318e6af192f5498ab422c3ceb1ff51b2f948cb8afa3d1238ef3c7e7b0', '2025-07-21 14:26:48', 0, '2025-07-21 12:16:48'),
(3, 1, 'f3f09eb402cf755389636f8a1e3c2aecf0a6fbf7f106b7713032d2d1affe9908', '2025-07-21 14:27:19', 0, '2025-07-21 12:17:19'),
(4, 1, '30640efabcd4c4fff567209609ecd5bd54a1f5db35fc4378e7bdde9844c74186', '2025-07-21 14:28:00', 0, '2025-07-21 12:18:00'),
(5, 1, 'ebc93d38bd2ca57f34a8f35312fc708605f3f4c8ca42aab9e2bdc55eff229f88', '2025-07-21 14:28:23', 0, '2025-07-21 12:18:23'),
(6, 1, '283f6f25896f9e01fa89a905baf5c6515541643936b03c2ac09159f4289f1b03', '2025-07-21 14:28:39', 0, '2025-07-21 12:18:39'),
(7, 1, '74d42df5082dc58f591c5d94039872582d8573e305cd825440ef3144db4965b5', '2025-07-21 14:29:06', 1, '2025-07-21 12:19:06'),
(8, 1, 'c59d8ee1ce45f784f985426b4e2e52c4799ebe816d83a0108b9d57a1d8c73b90', '2025-07-21 15:16:10', 1, '2025-07-21 13:06:10'),
(9, 1, '54ba28dceb28195ec7b008f8bc44b369cb58b8c252ec197eb524ee1ceb17b917', '2025-07-21 15:36:46', 1, '2025-07-21 13:26:46'),
(10, 1, 'f78bca8747efb1682bf0ccb7ef0eaa75e12351800193f3c5212befd81e2ceac4', '2025-07-30 09:20:58', 0, '2025-07-30 07:10:58'),
(11, 1, '83f7569705f7c85e1a04479bb7cf86be8ccbfede79f1dbd63f6bf99bc8d20d32', '2025-07-30 09:21:15', 0, '2025-07-30 07:11:15'),
(12, 1, '79bb62d3742d3f79b5df35e94cad6c1cbf4f1a957fa01e889c2f8c40f702c234', '2025-07-30 09:21:40', 1, '2025-07-30 07:11:40'),
(13, 1, '465076445edc3fa4e6f53d58f797744a3c1421a6d80ef0cec427e6ab86420fe3', '2025-07-30 09:23:39', 0, '2025-07-30 07:13:39'),
(14, 1, 'cbd1a3be30fec5f23d3da233b123c16fc8ee895065e10832c0ab28dcffea9d9a', '2025-07-30 09:25:10', 1, '2025-07-30 07:15:10'),
(15, 1, '04eb752b0f181dd332e728c97ecb73afb0dfd156aecd7190c4084e110513544b', '2025-07-30 14:42:35', 1, '2025-07-30 12:32:35'),
(16, 3, '854b5cc96bcc379d1076e6ea5d4adab2e0f1b9fe9dec68dda17cd3713539bfae', '2025-07-30 16:00:30', 1, '2025-07-30 13:50:30'),
(17, 3, 'b6c76f00ac67f54c97ca876db5017d8177dee08e1da8dcafe66f924961648638', '2025-07-30 16:17:04', 1, '2025-07-30 14:07:04'),
(18, 3, 'c8041634db1176792991a94c57b1061ceed0b61678051d6ed0797ad7816c31aa', '2025-07-30 16:23:12', 1, '2025-07-30 14:13:12'),
(19, 3, '9701016112b91408b32e2f65e804c0202a901c2d9beaf51c709e9d2676c6053d', '2025-07-30 16:23:57', 1, '2025-07-30 14:13:57'),
(20, 3, '88f1fba729cd8adec4e4aeae24875eb45c1e23910bc15d7c4b9d08d874f450c7', '2025-07-30 16:32:10', 1, '2025-07-30 14:22:10'),
(21, 3, '78a95a143a55641101962b5d2b5f0a589231df2236d12ab6d03c547e2da0204a', '2025-07-31 14:21:41', 1, '2025-07-31 12:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `transaction_id`, `type`, `title`, `message`, `is_read`, `created_at`, `read_at`) VALUES
(1, 3, 2, 'new_submission', 'Transaction Submitted', 'Your transaction \"Elite business Invoice 33456\" has been submitted for review.', 0, '2025-07-31 09:12:09', NULL),
(2, 4, 6, 'new_submission', 'Transaction Submitted', 'Your transaction \"Chair\" has been submitted for review.', 0, '2025-07-31 09:12:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registration_attempts`
--

CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_attempts`
--

INSERT INTO `registration_attempts` (`id`, `ip_address`, `email`, `created_at`) VALUES
(1, '::1', 'thomson.thom@undp.org', '2025-07-30 15:32:01'),
(2, '::1', 'thomson.thom@undp.org', '2025-07-30 15:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_description`, `updated_by`, `updated_at`) VALUES
(1, 'workflow_enabled', 'true', 'Enable/disable the workflow system', NULL, '2025-07-31 09:10:12'),
(2, 'notification_enabled', 'true', 'Enable/disable notifications', NULL, '2025-07-31 09:10:12'),
(3, 'auto_assign_reviewers', 'false', 'Automatically assign reviewers to new transactions', NULL, '2025-07-31 09:10:12'),
(4, 'separation_of_duties', 'true', 'Enforce separation of duties (reviewer cannot be processor)', NULL, '2025-07-31 09:10:12'),
(5, 'max_pending_per_user', '10', 'Maximum pending transactions per finance user', NULL, '2025-07-31 09:10:12'),
(6, 'email_notifications', 'false', 'Enable email notifications', NULL, '2025-07-31 09:10:12'),
(7, 'dashboard_refresh_interval', '300', 'Dashboard auto-refresh interval in seconds', NULL, '2025-07-31 09:10:12'),
(8, 'audit_retention_days', '365', 'Number of days to retain audit logs', NULL, '2025-07-31 09:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(20) NOT NULL UNIQUE,
  `title` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `tr_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `department` varchar(50) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `department_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Operations, 2=Programs',
  `workflow_status` varchar(50) DEFAULT 'submitted',
  `department_workflow` varchar(20) DEFAULT 'operations',
  `reviewer_id` int(11) DEFAULT NULL,
  `processor_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `sent_to_payroll_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `last_updated_by` int(11) DEFAULT NULL,
  `resubmitted_at` datetime DEFAULT NULL,
  `resubmitted_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_id`, `title`, `amount`, `tr_date`, `department`, `state`, `user_id`, `workflow_status`, `department_workflow`, `reviewer_id`, `processor_id`, `rejection_reason`, `submitted_at`, `reviewed_at`, `processed_at`, `sent_to_payroll_at`, `paid_at`, `last_updated_by`) VALUES
(2, 'TXN-2025-000002', 'Elite business Invoice 33456', 5000.00, '2025-07-24 12:28:38', '1', 0, 3, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(3, 'TXN-2025-000003', 'Thomson Thom', 59000.00, '2025-07-24 12:32:28', '1', 1, 3, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(4, 'TXN-2025-000004', 'Thomson Thom', 59000.00, '2025-07-24 12:32:56', '1', 2, 3, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(5, 'TXN-2025-000005', 'EliteInvoice 1234', 59000.00, '2025-07-24 12:34:01', '1', 0, 3, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(6, 'TXN-2025-000006', 'Chair', 45000.00, '2025-07-30 07:19:05', '1', 0, 3, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(7, 'TXN-2025-000007', 'Chair', 45000.00, '2025-07-30 07:19:09', '1', 0, 4, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 10:47:38', NULL, NULL, NULL, NULL, NULL),
(8, 'TXN-2025-000008', 'test', 5600.00, '2025-07-31 17:52:15', '1', 0, 1, 'submitted', 'operations', NULL, NULL, NULL, '2025-07-31 19:52:15', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_attachments`
--

CREATE TABLE `transaction_attachments` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'transaction_submitted, transaction_approved, transaction_sent_back, transaction_paid, etc.',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_states`
--

CREATE TABLE `workflow_states` (
  `id` int(11) NOT NULL,
  `state_value` tinyint(1) NOT NULL,
  `state_name` varchar(50) NOT NULL,
  `state_description` text DEFAULT NULL,
  `department_type` tinyint(1) NOT NULL COMMENT '1=Operations, 2=Programs, 0=Both',
  `is_editable` tinyint(1) DEFAULT 0 COMMENT '1=User can edit transaction, 0=Read only',
  `is_final` tinyint(1) DEFAULT 0 COMMENT '1=Final state, 0=Can transition',
  `next_states` varchar(100) DEFAULT NULL COMMENT 'Comma-separated list of valid next states',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workflow_states`
--

INSERT INTO `workflow_states` (`id`, `state_value`, `state_name`, `state_description`, `department_type`, `is_editable`, `is_final`, `next_states`, `created_at`) VALUES
(1, 1, 'Submitted for Review', 'Transaction submitted and waiting for finance team review', 0, 0, 0, '2,4', NOW()),
(2, 2, 'Under Review', 'Finance team is reviewing the transaction', 0, 0, 0, '3,4,5', NOW()),
(3, 3, 'Processing', 'Transaction approved and being processed (Operations only)', 1, 0, 0, '5', NOW()),
(4, 4, 'Sent Back', 'Transaction sent back to user for corrections', 0, 1, 0, '1', NOW()),
(5, 5, 'Sent to Payroll', 'Transaction approved and sent to payroll department', 0, 0, 0, '6', NOW()),
(6, 6, 'Paid', 'Transaction has been paid - final status', 0, 0, 1, '', NOW());

-- --------------------------------------------------------

--
-- Table structure for table `transaction_audit_log`
--

CREATE TABLE `transaction_audit_log` (
  `log_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `transaction_workflow_view`
-- (See below for the actual view)
--
CREATE TABLE `transaction_workflow_view` (
`transaction_id` int(11)
,`title` varchar(50)
,`amount` decimal(12,2)
,`tr_date` timestamp
,`department` varchar(50)
,`legacy_state` tinyint(1)
,`workflow_status` varchar(50)
,`department_workflow` varchar(20)
,`submitted_at` datetime
,`reviewed_at` datetime
,`processed_at` datetime
,`sent_to_payroll_at` datetime
,`paid_at` datetime
,`rejection_reason` text
,`submitter_name` varchar(50)
,`submitter_email` varchar(100)
,`submitter_type` tinyint(4)
,`reviewer_name` varchar(50)
,`processor_name` varchar(50)
,`last_updated_by_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `type_value` tinyint(1) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `type_description` text DEFAULT NULL,
  `can_submit_transactions` tinyint(1) DEFAULT 1,
  `can_review_transactions` tinyint(1) DEFAULT 0,
  `can_approve_transactions` tinyint(1) DEFAULT 0,
  `department_type` tinyint(1) DEFAULT NULL COMMENT '1=Operations, 2=Programs, NULL=Any',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `type_value`, `type_name`, `type_description`, `can_submit_transactions`, `can_review_transactions`, `can_approve_transactions`, `department_type`, `created_at`) VALUES
(1, 1, 'Operations User', 'Can submit transactions following Operations workflow', 1, 0, 0, 1, NOW()),
(2, 2, 'Programs User', 'Can submit transactions following Programs workflow', 1, 0, 0, 2, NOW()),
(3, 3, 'Finance Team', 'Can review, approve, and manage all transactions', 1, 1, 1, NULL, NOW());

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_type` tinyint(4) NOT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_type`, `verification_token`, `verification_expires`, `email_verified`, `verified_at`, `created_at`, `profile_image`, `updated_at`) VALUES
(1, 'Operations User', 'operations.user@undp.org', 1, NULL, NULL, 1, '2025-08-01 10:00:00', '2025-08-01 10:00:00', NULL, '2025-08-01 10:00:00'),
(2, 'Programs User', 'programs.user@undp.org', 2, NULL, NULL, 1, '2025-08-01 10:00:00', '2025-08-01 10:00:00', NULL, '2025-08-01 10:00:00'),
(3, 'Thomson Thom', 'thomson.thom@undp.org', 1, NULL, NULL, 1, '2025-07-30 15:48:12', '2025-07-30 15:42:51', NULL, '2025-07-31 09:10:12'),
(4, 'Finance Manager', 'finance.manager@undp.org', 3, NULL, NULL, 1, '2025-07-31 09:10:12', '2025-07-31 09:10:12', NULL, '2025-07-31 09:10:12'),
(5, 'Finance Reviewer', 'finance.reviewer@undp.org', 3, NULL, NULL, 1, '2025-07-31 09:10:12', '2025-07-31 09:10:12', NULL, '2025-07-31 09:10:12'),
(6, 'Finance Processor', 'finance.processor@undp.org', 3, NULL, NULL, 1, '2025-07-31 09:10:12', '2025-07-31 09:10:12', NULL, '2025-07-31 09:10:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_dashboard_view`
-- (See below for the actual view)
--
CREATE TABLE `user_dashboard_view` (
`user_id` int(11)
,`user_name` varchar(50)
,`user_email` varchar(100)
,`user_type` tinyint(4)
,`role_name` varchar(10)
,`total_transactions` bigint(21)
,`pending_transactions` bigint(21)
,`completed_transactions` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `workflow_config`
--

CREATE TABLE `workflow_config` (
  `config_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `status_order` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `status_description` text DEFAULT NULL,
  `required_role` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workflow_config`
--

INSERT INTO `workflow_config` (`config_id`, `department`, `status_order`, `status_name`, `status_description`, `required_role`, `is_active`, `created_at`) VALUES
(1, 'operations', 1, 'submitted_for_review', 'Transaction submitted by user for review', 'user', 1, '2025-07-31 09:10:12'),
(2, 'operations', 2, 'under_review', 'Transaction being reviewed by finance team', 'finance', 1, '2025-07-31 09:10:12'),
(3, 'operations', 3, 'processing', 'Transaction approved and being processed', 'finance', 1, '2025-07-31 09:10:12'),
(4, 'operations', 4, 'sent_for_payroll', 'Transaction sent to payroll system', 'finance', 1, '2025-07-31 09:10:12'),
(5, 'operations', 5, 'paid', 'Payment completed', 'finance', 1, '2025-07-31 09:10:12'),
(6, 'programs', 1, 'submitted', 'Transaction submitted by user', 'user', 1, '2025-07-31 09:10:12'),
(7, 'programs', 2, 'under_review', 'Transaction being reviewed by finance team', 'finance', 1, '2025-07-31 09:10:12'),
(8, 'programs', 3, 'sent_for_payroll', 'Transaction approved and sent to payroll', 'finance', 1, '2025-07-31 09:10:12'),
(9, 'programs', 4, 'paid', 'Payment completed', 'finance', 1, '2025-07-31 09:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_metrics`
--

CREATE TABLE `workflow_metrics` (
  `metric_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `stage_name` varchar(50) NOT NULL,
  `stage_duration_minutes` int(11) DEFAULT NULL,
  `assigned_user` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `transaction_workflow_view`
--
DROP TABLE IF EXISTS `transaction_workflow_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `transaction_workflow_view`  AS SELECT `t`.`id` AS `transaction_id`, `t`.`title` AS `title`, `t`.`amount` AS `amount`, `t`.`tr_date` AS `tr_date`, `t`.`department` AS `department`, `t`.`state` AS `legacy_state`, `t`.`workflow_status` AS `workflow_status`, `t`.`department_workflow` AS `department_workflow`, `t`.`submitted_at` AS `submitted_at`, `t`.`reviewed_at` AS `reviewed_at`, `t`.`processed_at` AS `processed_at`, `t`.`sent_to_payroll_at` AS `sent_to_payroll_at`, `t`.`paid_at` AS `paid_at`, `t`.`rejection_reason` AS `rejection_reason`, `u`.`user_name` AS `submitter_name`, `u`.`user_email` AS `submitter_email`, `u`.`user_type` AS `submitter_type`, `r`.`user_name` AS `reviewer_name`, `p`.`user_name` AS `processor_name`, `lu`.`user_name` AS `last_updated_by_name` FROM ((((`transactions` `t` left join `users` `u` on(`t`.`user_id` = `u`.`user_id`)) left join `users` `r` on(`t`.`reviewer_id` = `r`.`user_id`)) left join `users` `p` on(`t`.`processor_id` = `p`.`user_id`)) left join `users` `lu` on(`t`.`last_updated_by` = `lu`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `user_dashboard_view`
--
DROP TABLE IF EXISTS `user_dashboard_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_dashboard_view`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`user_name` AS `user_name`, `u`.`user_email` AS `user_email`, `u`.`user_type` AS `user_type`, CASE WHEN `u`.`user_type` = 1 THEN 'Admin' WHEN `u`.`user_type` = 2 THEN 'Operations' WHEN `u`.`user_type` = 3 THEN 'Programs' WHEN `u`.`user_type` = 4 THEN 'Finance' ELSE 'Unknown' END AS `role_name`, count(`t`.`id`) AS `total_transactions`, count(case when `t`.`workflow_status` = 'submitted_for_review' then 1 end) AS `pending_transactions`, count(case when `t`.`workflow_status` = 'paid' then 1 end) AS `completed_transactions` FROM (`users` `u` left join `transactions` `t` on(`u`.`user_id` = `t`.`user_id`)) GROUP BY `u`.`user_id`, `u`.`user_name`, `u`.`user_email`, `u`.`user_type` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_assignments`
--
ALTER TABLE `finance_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_assignment_transaction` (`transaction_id`),
  ADD KEY `idx_assignment_user` (`assigned_to`),
  ADD KEY `idx_assignment_type` (`assignment_type`),
  ADD KEY `fk_assignment_assigner` (`assigned_by`);

--
-- Indexes for table `magic_links`
--
ALTER TABLE `magic_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_notifications` (`user_id`,`is_read`),
  ADD KEY `idx_notification_date` (`created_at`),
  ADD KEY `idx_notification_type` (`type`),
  ADD KEY `fk_notification_transaction` (`transaction_id`);

--
-- Indexes for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_created` (`ip_address`,`created_at`),
  ADD KEY `idx_email_created` (`email`,`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_settings_user` (`updated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_transaction_id` (`transaction_id`),
  ADD KEY `idx_workflow_status` (`workflow_status`),
  ADD KEY `idx_department_workflow` (`department_workflow`),
  ADD KEY `idx_reviewer` (`reviewer_id`),
  ADD KEY `idx_processor` (`processor_id`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_state` (`state`),
  ADD KEY `idx_department_type` (`department_type`),
  ADD KEY `idx_reviewed_by` (`reviewed_by`),
  ADD KEY `fk_transactions_last_updated` (`last_updated_by`);

--
-- Indexes for table `transaction_attachments`
--
ALTER TABLE `transaction_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_attachments` (`transaction_id`),
  ADD KEY `idx_attachment_id` (`attachment_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `transaction_audit_log`
--
ALTER TABLE `transaction_audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_transaction_audit` (`transaction_id`),
  ADD KEY `idx_audit_date` (`created_at`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_value` (`type_value`),
  ADD KEY `idx_department_type` (`department_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_email_verified` (`email_verified`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `workflow_states`
--
ALTER TABLE `workflow_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_state_dept` (`state_value`, `department_type`),
  ADD KEY `idx_department_type` (`department_type`),
  ADD KEY `idx_state_value` (`state_value`);

--
-- Indexes for table `workflow_config`
--
ALTER TABLE `workflow_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `unique_dept_status` (`department`,`status_name`),
  ADD KEY `idx_dept_order` (`department`,`status_order`);

--
-- Indexes for table `workflow_metrics`
--
ALTER TABLE `workflow_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `idx_metrics_transaction` (`transaction_id`),
  ADD KEY `idx_metrics_stage` (`stage_name`),
  ADD KEY `idx_metrics_date` (`completed_at`),
  ADD KEY `fk_metrics_user` (`assigned_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_assignments`
--
ALTER TABLE `finance_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `magic_links`
--
ALTER TABLE `magic_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration_attempts`
--
ALTER TABLE `registration_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transaction_attachments`
--
ALTER TABLE `transaction_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_audit_log`
--
ALTER TABLE `transaction_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workflow_states`
--
ALTER TABLE `workflow_states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `workflow_config`
--
ALTER TABLE `workflow_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `workflow_metrics`
--
ALTER TABLE `workflow_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `finance_assignments`
--
ALTER TABLE `finance_assignments`
  ADD CONSTRAINT `fk_assignment_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transaction_attachments`
--
ALTER TABLE `transaction_attachments`
  ADD CONSTRAINT `fk_transaction_attachments_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_attachments_attachment` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_last_updated_by` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_processor` FOREIGN KEY (`processor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_last_updated` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_processor` FOREIGN KEY (`processor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transaction_audit_log`
--
ALTER TABLE `transaction_audit_log`
  ADD CONSTRAINT `fk_audit_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `workflow_metrics`
--
ALTER TABLE `workflow_metrics`
  ADD CONSTRAINT `fk_metrics_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_metrics_user` FOREIGN KEY (`assigned_user`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
