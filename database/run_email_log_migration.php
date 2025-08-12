<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3>Email Log Table Migration</h3>
                    </div>
                    <div class="card-body">
                        <p>This migration will:</p>
                        <ul>
                            <li>Create the <code>email_log</code> table for tracking email notifications</li>
                            <li>Add proper indexes for performance</li>
                            <li>Set up the table structure for email logging</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <strong>Note:</strong> This migration is safe to run multiple times. It will only create the table if it doesn't exist.
                        </div>

                        <?php if (!isset($_POST['run_migration'])): ?>
                            <form method="post">
                                <button type="submit" name="run_migration" class="btn btn-primary">
                                    Run Email Log Migration
                                </button>
                                <a href="../finance/dashboard.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5>Migration Output:</h5>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">
<?php
// Run the migration
echo "<h4>Email Log Table Migration</h4>\n";
echo "<p class='info'>Starting migration process...</p>\n";

try {
    // Include database connection
    require_once('../controller/Database.php');
    $db = new Database();
    
    echo "<p class='info'>✓ Database connection established</p>\n";
    
    // Check if table already exists
    $tableExists = $db->query("SHOW TABLES LIKE 'email_log'")->fetchArray();
    
    if ($tableExists) {
        echo "<p class='warning'>⚠️ email_log table already exists</p>\n";
        
        // Check table structure
        $columns = $db->query("DESCRIBE email_log")->fetchAll();
        echo "<p class='info'>Current table structure:</p>\n";
        echo "<ul>\n";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>\n";
        }
        echo "</ul>\n";
        
    } else {
        echo "<p class='info'>Creating email_log table...</p>\n";
        
        // Read and execute the migration SQL
        $migrationSQL = file_get_contents('migration_email_log.sql');
        
        if ($migrationSQL === false) {
            throw new Exception("Could not read migration_email_log.sql file");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip empty statements and comments
            }
            
            try {
                $db->query($statement);
                $successCount++;
                echo "<p class='success'>✓ Executed: " . substr($statement, 0, 50) . "...</p>\n";
            } catch (Exception $e) {
                $errorCount++;
                echo "<p class='error'>✗ Error executing: " . substr($statement, 0, 50) . "...</p>\n";
                echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
            }
        }
        
        echo "<p class='info'>Migration completed: $successCount successful, $errorCount errors</p>\n";
        
        if ($errorCount == 0) {
            echo "<p class='success'>✅ email_log table created successfully!</p>\n";
        }
    }
    
    // Test the table
    echo "<h5>Testing email_log table...</h5>\n";
    
    try {
        // Test insert
        $testResult = $db->query("
            INSERT INTO email_log (recipient_email, subject, transaction_id, status, email_type) 
            VALUES (?, ?, ?, ?, ?)
        ", 'test@example.com', 'Migration Test Email', 'TEST-001', 'sent', 'test');
        
        echo "<p class='success'>✓ Test insert successful</p>\n";
        
        // Test select
        $testRecord = $db->query("
            SELECT * FROM email_log WHERE recipient_email = ? ORDER BY id DESC LIMIT 1
        ", 'test@example.com')->fetchArray();
        
        if ($testRecord) {
            echo "<p class='success'>✓ Test select successful</p>\n";
            echo "<p class='info'>Test record ID: {$testRecord['id']}</p>\n";
            
            // Clean up test record
            $db->query("DELETE FROM email_log WHERE id = ?", $testRecord['id']);
            echo "<p class='info'>✓ Test record cleaned up</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Table test failed: " . $e->getMessage() . "</p>\n";
    }
    
    // Show final table info
    echo "<h5>Final Table Information:</h5>\n";
    $tableInfo = $db->query("
        SELECT 
            COUNT(*) as record_count,
            MAX(created_at) as latest_record
        FROM email_log
    ")->fetchArray();
    
    echo "<p class='info'>Records in table: {$tableInfo['record_count']}</p>\n";
    echo "<p class='info'>Latest record: " . ($tableInfo['latest_record'] ?: 'None') . "</p>\n";
    
    echo "<h5>Next Steps:</h5>\n";
    echo "<ul>\n";
    echo "<li>✅ Email notifications will now be logged to the database</li>\n";
    echo "<li>✅ You can view email logs in the admin panel</li>\n";
    echo "<li>✅ Email delivery status will be tracked</li>\n";
    echo "<li>✅ Failed emails will be logged for troubleshooting</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Migration failed: " . $e->getMessage() . "</p>\n";
    echo "<p class='error'>Please check your database connection and try again.</p>\n";
}
?>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="../finance/dashboard.php" class="btn btn-success">
                                    Go to Dashboard
                                </a>
                                <a href="../test_smtp_config.php" class="btn btn-info" target="_blank">
                                    Test Email Configuration
                                </a>
                                <a href="run_migration.php" class="btn btn-secondary">
                                    Other Migrations
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($_POST['run_migration'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Email Log Table Schema</h5>
                    </div>
                    <div class="card-body">
                        <p>The email_log table includes the following columns:</p>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>id</td><td>int(11)</td><td>Primary key, auto-increment</td></tr>
                                <tr><td>recipient_email</td><td>varchar(255)</td><td>Email address of recipient</td></tr>
                                <tr><td>subject</td><td>varchar(500)</td><td>Email subject line</td></tr>
                                <tr><td>transaction_id</td><td>varchar(50)</td><td>Related transaction ID (optional)</td></tr>
                                <tr><td>sent_at</td><td>timestamp</td><td>When email was sent</td></tr>
                                <tr><td>status</td><td>enum</td><td>sent, failed, or pending</td></tr>
                                <tr><td>error_message</td><td>text</td><td>Error details if failed</td></tr>
                                <tr><td>email_type</td><td>varchar(100)</td><td>Type of email (notification, system, etc.)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
