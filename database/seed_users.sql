-- Seed script to create default users for the finance transaction system
-- Run this if you have no users in your database

-- Insert default users (only if they don't already exist)
INSERT IGNORE INTO `users` (`user_id`, `user_name`, `user_email`, `user_password`, `user_type`, `email_verified`, `verified_at`, `created_at`) VALUES
(1, 'Operations User', 'operations@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW(), NOW()),
(2, 'Programs User', 'programs@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, NOW(), NOW()),
(3, 'Finance User', 'finance@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, NOW(), NOW());

-- Note: The password hash above is for 'password' - change these in production!
-- User types: 1 = Operations, 2 = Programs, 3 = Finance

-- Verify the users were created
SELECT user_id, user_name, user_email, user_type FROM users;
