<?php
/**
 * Quick Fix: Create email_log table
 * 
 * This script creates the missing email_log table that's causing the error.
 * Run this script directly to fix the immediate issue.
 */

// Set content type for proper output
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Creating email_log table...</h2>";

try {
    // Include database connection
    require_once('../controller/Database.php');
    $db = new Database();
    
    echo "<p style='color: blue;'>✓ Database connection established</p>";
    
    // Check if table already exists
    $tableExists = $db->query("SHOW TABLES LIKE 'email_log'")->fetchArray();
    
    if ($tableExists) {
        echo "<p style='color: orange;'>⚠️ email_log table already exists</p>";
        echo "<p>No action needed. The table is already in your database.</p>";
    } else {
        echo "<p style='color: blue;'>Creating email_log table...</p>";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE `email_log` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        $db->query($createTableSQL);
        echo "<p style='color: green;'>✅ email_log table created successfully!</p>";
        
        // Create additional index for performance
        $indexSQL = "CREATE INDEX `idx_email_log_composite` ON `email_log` (`recipient_email`, `sent_at`, `status`)";
        try {
            $db->query($indexSQL);
            echo "<p style='color: green;'>✓ Performance index created</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Index creation skipped (may already exist)</p>";
        }
        
        // Test the table
        echo "<p style='color: blue;'>Testing table...</p>";
        
        // Test insert
        $db->query("
            INSERT INTO email_log (recipient_email, subject, transaction_id, status, email_type) 
            VALUES (?, ?, ?, ?, ?)
        ", 'test@example.com', 'Table Creation Test', 'TEST-001', 'sent', 'test');
        
        echo "<p style='color: green;'>✓ Test insert successful</p>";
        
        // Test select and cleanup
        $testRecord = $db->query("
            SELECT * FROM email_log WHERE recipient_email = ? ORDER BY id DESC LIMIT 1
        ", 'test@example.com')->fetchArray();
        
        if ($testRecord) {
            echo "<p style='color: green;'>✓ Test select successful</p>";
            
            // Clean up test record
            $db->query("DELETE FROM email_log WHERE id = ?", $testRecord['id']);
            echo "<p style='color: blue;'>✓ Test record cleaned up</p>";
        }
    }
    
    echo "<h3 style='color: green;'>Success!</h3>";
    echo "<p>The email_log table is now ready. Email notifications should work without errors.</p>";
    
    echo "<h4>What this table does:</h4>";
    echo "<ul>";
    echo "<li>Tracks all email notifications sent by the system</li>";
    echo "<li>Logs successful and failed email attempts</li>";
    echo "<li>Stores error messages for troubleshooting</li>";
    echo "<li>Links emails to specific transactions when applicable</li>";
    echo "</ul>";
    
    echo "<h4>Next steps:</h4>";
    echo "<ul>";
    echo "<li>✅ Email notifications will now work without database errors</li>";
    echo "<li>✅ You can monitor email delivery in the admin panel</li>";
    echo "<li>✅ Failed emails will be logged for troubleshooting</li>";
    echo "</ul>";
    
    echo "<p><a href='../finance/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Please check your database connection and try again.</p>";
    
    echo "<h4>Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li>Make sure MySQL is running in XAMPP</li>";
    echo "<li>Verify the 'finance' database exists</li>";
    echo "<li>Check database credentials in Database.php</li>";
    echo "</ul>";
}
?>
