<?php
/**
 * Debug script for foreign key constraint issue
 */

require_once('controller/main.php');

echo "<h1>Foreign Key Constraint Debug</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// Check current session
echo "<h2>1. Current Session Status</h2>";
debug_session();

// Check users table
echo "<h2>2. Users Table Analysis</h2>";
debug_users_table();

// Check transaction_attachments table
echo "<h2>3. Transaction Attachments Table Analysis</h2>";
debug_attachments_table();

// Check foreign key constraints
echo "<h2>4. Foreign Key Constraints</h2>";
debug_foreign_keys();

// Test user ID resolution
echo "<h2>5. User ID Resolution Test</h2>";
test_user_id_resolution();

function debug_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p class='success'>✅ Session is active</p>";
        echo "<p class='info'>Session ID: " . session_id() . "</p>";
        
        if (isset($_SESSION['user_id'])) {
            echo "<p class='success'>✅ User logged in: ID " . $_SESSION['user_id'] . "</p>";
            if (isset($_SESSION['user_name'])) {
                echo "<p class='info'>User name: " . $_SESSION['user_name'] . "</p>";
            }
            if (isset($_SESSION['user_type'])) {
                echo "<p class='info'>User type: " . $_SESSION['user_type'] . "</p>";
            }
        } else {
            echo "<p class='error'>❌ No user logged in</p>";
        }
    } else {
        echo "<p class='error'>❌ Session not active</p>";
    }
}

function debug_users_table() {
    global $db;
    
    try {
        // Check if users table exists
        $result = $db->query("SHOW TABLES LIKE 'users'")->fetchArray();
        if (!$result) {
            echo "<p class='error'>❌ Users table does not exist</p>";
            return;
        }
        
        echo "<p class='success'>✅ Users table exists</p>";
        
        // Get table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $db->query("DESCRIBE users")->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get user count and sample data
        $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetchArray()['count'];
        echo "<p class='info'>Total users: $user_count</p>";
        
        if ($user_count > 0) {
            echo "<h3>Sample Users:</h3>";
            $users = $db->query("SELECT user_id, user_name, user_email, user_type FROM users ORDER BY user_id LIMIT 10")->fetchAll();
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Type</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['user_id']}</td>";
                echo "<td>{$user['user_name']}</td>";
                echo "<td>{$user['user_email']}</td>";
                echo "<td>{$user['user_type']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check if current session user exists
            if (isset($_SESSION['user_id'])) {
                $session_user = $db->query("SELECT * FROM users WHERE user_id = ?", $_SESSION['user_id'])->fetchArray();
                if ($session_user) {
                    echo "<p class='success'>✅ Current session user exists in database</p>";
                } else {
                    echo "<p class='error'>❌ Current session user ID {$_SESSION['user_id']} does NOT exist in database</p>";
                }
            }
        } else {
            echo "<p class='warning'>⚠️ No users found in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking users table: " . $e->getMessage() . "</p>";
    }
}

function debug_attachments_table() {
    global $db;
    
    try {
        // Check if transaction_attachments table exists
        $result = $db->query("SHOW TABLES LIKE 'transaction_attachments'")->fetchArray();
        if (!$result) {
            echo "<p class='error'>❌ Transaction attachments table does not exist</p>";
            return;
        }
        
        echo "<p class='success'>✅ Transaction attachments table exists</p>";
        
        // Get table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $db->query("DESCRIBE transaction_attachments")->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get attachment count
        $attachment_count = $db->query("SELECT COUNT(*) as count FROM transaction_attachments")->fetchArray()['count'];
        echo "<p class='info'>Total attachments: $attachment_count</p>";
        
        if ($attachment_count > 0) {
            echo "<h3>Sample Attachments:</h3>";
            $attachments = $db->query("SELECT id, transaction_id, uploaded_by, file_name FROM transaction_attachments ORDER BY id DESC LIMIT 5")->fetchAll();
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Transaction ID</th><th>Uploaded By</th><th>File Name</th></tr>";
            foreach ($attachments as $attachment) {
                echo "<tr>";
                echo "<td>{$attachment['id']}</td>";
                echo "<td>{$attachment['transaction_id']}</td>";
                echo "<td>{$attachment['uploaded_by']}</td>";
                echo "<td>{$attachment['file_name']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking transaction_attachments table: " . $e->getMessage() . "</p>";
    }
}

function debug_foreign_keys() {
    global $db;
    
    try {
        echo "<h3>Foreign Key Constraints for transaction_attachments:</h3>";
        $constraints = $db->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                DELETE_RULE,
                UPDATE_RULE
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'transaction_attachments' 
            AND TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll();
        
        if ($constraints) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Constraint</th><th>Column</th><th>References</th><th>Delete Rule</th><th>Update Rule</th></tr>";
            foreach ($constraints as $constraint) {
                echo "<tr>";
                echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
                echo "<td>{$constraint['COLUMN_NAME']}</td>";
                echo "<td>{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</td>";
                echo "<td>{$constraint['DELETE_RULE']}</td>";
                echo "<td>{$constraint['UPDATE_RULE']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No foreign key constraints found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking foreign keys: " . $e->getMessage() . "</p>";
    }
}

function test_user_id_resolution() {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        echo "<p class='warning'>⚠️ No user logged in - cannot test user ID resolution</p>";
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    echo "<p class='info'>Testing with session user ID: $user_id</p>";
    
    try {
        // Test if user exists
        $user = $db->query("SELECT user_id, user_name FROM users WHERE user_id = ?", $user_id)->fetchArray();
        if ($user) {
            echo "<p class='success'>✅ User ID $user_id exists: {$user['user_name']}</p>";
        } else {
            echo "<p class='error'>❌ User ID $user_id does NOT exist in users table</p>";
            
            // Show available user IDs
            $available_users = $db->query("SELECT user_id, user_name FROM users ORDER BY user_id LIMIT 5")->fetchAll();
            echo "<p class='info'>Available user IDs:</p>";
            echo "<ul>";
            foreach ($available_users as $available_user) {
                echo "<li>ID {$available_user['user_id']}: {$available_user['user_name']}</li>";
            }
            echo "</ul>";
        }
        
        // Test foreign key constraint
        echo "<h3>Foreign Key Constraint Test:</h3>";
        try {
            // Try to insert a test record (we'll roll it back)
            $db->query("START TRANSACTION");
            
            $test_result = $db->query("
                INSERT INTO transaction_attachments 
                (transaction_id, uploaded_by, file_name, file_path, file_size, file_type, uploaded_at) 
                VALUES (999999, ?, 'test.txt', '/test/path', 100, 'text/plain', NOW())
            ", $user_id);
            
            if ($test_result) {
                echo "<p class='success'>✅ Foreign key constraint test passed</p>";
                $db->query("ROLLBACK"); // Roll back the test insert
            } else {
                echo "<p class='error'>❌ Foreign key constraint test failed</p>";
                $db->query("ROLLBACK");
            }
            
        } catch (Exception $e) {
            $db->query("ROLLBACK");
            echo "<p class='error'>❌ Foreign key constraint test error: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error testing user ID resolution: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>6. Recommended Actions</h2>";
echo "<h3>If Current User ID Doesn't Exist:</h3>";
echo "<ol>";
echo "<li>Create the missing user in the database</li>";
echo "<li>Or update the session to use an existing user ID</li>";
echo "<li>Or fix the user registration/login process</li>";
echo "</ol>";

echo "<h3>If Foreign Key Constraint is Too Strict:</h3>";
echo "<ol>";
echo "<li>Consider making uploaded_by nullable</li>";
echo "<li>Or add a default/system user for orphaned records</li>";
echo "<li>Or temporarily disable foreign key checks during development</li>";
echo "</ol>";

echo "<p><strong>Debug completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
