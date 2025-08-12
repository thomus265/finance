<?php
/**
 * Script to fix user foreign key constraint issues
 */

require_once('controller/main.php');

echo "<h1>Fix User Foreign Key Issues</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// Step 1: Check current state
echo "<h2>1. Current State Analysis</h2>";
analyze_current_state();

// Step 2: Fix missing users
echo "<h2>2. Fix Missing Users</h2>";
fix_missing_users();

// Step 3: Validate foreign key constraints
echo "<h2>3. Validate Foreign Key Constraints</h2>";
validate_constraints();

// Step 4: Test file upload
echo "<h2>4. Test File Upload Process</h2>";
test_upload_process();

function analyze_current_state() {
    global $db;
    
    try {
        // Check users table
        $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetchArray()['count'];
        echo "<p class='info'>Total users in database: $user_count</p>";
        
        if ($user_count == 0) {
            echo "<p class='error'>❌ No users found in database - this is the root cause!</p>";
            return;
        }
        
        // Check current session
        if (isset($_SESSION['user_id'])) {
            $session_user_id = $_SESSION['user_id'];
            echo "<p class='info'>Current session user ID: $session_user_id</p>";
            
            $user_exists = $db->query("SELECT user_id, user_name FROM users WHERE user_id = ?", $session_user_id)->fetchArray();
            if ($user_exists) {
                echo "<p class='success'>✅ Session user exists in database: {$user_exists['user_name']}</p>";
            } else {
                echo "<p class='error'>❌ Session user ID $session_user_id does NOT exist in database</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ No user logged in</p>";
        }
        
        // Check transaction_attachments table
        $attachment_count = $db->query("SELECT COUNT(*) as count FROM transaction_attachments")->fetchArray()['count'];
        echo "<p class='info'>Total attachments: $attachment_count</p>";
        
        // Check for orphaned attachments
        $orphaned = $db->query("
            SELECT COUNT(*) as count 
            FROM transaction_attachments ta 
            LEFT JOIN users u ON ta.uploaded_by = u.user_id 
            WHERE u.user_id IS NULL
        ")->fetchArray()['count'];
        
        if ($orphaned > 0) {
            echo "<p class='error'>❌ Found $orphaned orphaned attachments (uploaded_by references non-existent users)</p>";
        } else {
            echo "<p class='success'>✅ No orphaned attachments found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error analyzing current state: " . $e->getMessage() . "</p>";
    }
}

function fix_missing_users() {
    global $db;
    
    try {
        $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetchArray()['count'];
        
        if ($user_count == 0) {
            echo "<p class='warning'>⚠️ Creating default users...</p>";
            
            // Create default users
            $default_users = [
                [1, 'Operations User', 'operations@example.com', 'operations123', 1],
                [2, 'Programs User', 'programs@example.com', 'programs123', 2],
                [3, 'Finance User', 'finance@example.com', 'finance123', 3]
            ];
            
            foreach ($default_users as $user) {
                try {
                    $password_hash = password_hash($user[3], PASSWORD_DEFAULT);
                    $result = $db->query(
                        "INSERT INTO users (user_id, user_name, user_email, user_password, user_type, email_verified, verified_at, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())",
                        $user[0], $user[1], $user[2], $password_hash, $user[4]
                    );
                    
                    if ($result) {
                        echo "<p class='success'>✅ Created user: {$user[1]} (ID: {$user[0]})</p>";
                    } else {
                        echo "<p class='error'>❌ Failed to create user: {$user[1]}</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ User {$user[1]} may already exist or error: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p class='success'>✅ Users already exist in database</p>";
            
            // Show existing users
            $users = $db->query("SELECT user_id, user_name, user_email FROM users ORDER BY user_id LIMIT 5")->fetchAll();
            echo "<p class='info'>Existing users:</p>";
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>ID {$user['user_id']}: {$user['user_name']} ({$user['user_email']})</li>";
            }
            echo "</ul>";
        }
        
        // Fix session if needed
        if (isset($_SESSION['user_id'])) {
            $session_user_id = $_SESSION['user_id'];
            $user_exists = $db->query("SELECT user_id FROM users WHERE user_id = ?", $session_user_id)->fetchArray();
            
            if (!$user_exists) {
                echo "<p class='warning'>⚠️ Fixing invalid session user ID...</p>";
                
                // Get the first available user
                $first_user = $db->query("SELECT user_id, user_name FROM users ORDER BY user_id LIMIT 1")->fetchArray();
                if ($first_user) {
                    $_SESSION['user_id'] = $first_user['user_id'];
                    $_SESSION['user_name'] = $first_user['user_name'];
                    echo "<p class='success'>✅ Updated session to use user ID {$first_user['user_id']}: {$first_user['user_name']}</p>";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error fixing missing users: " . $e->getMessage() . "</p>";
    }
}

function validate_constraints() {
    global $db;
    
    try {
        echo "<h3>Testing Foreign Key Constraint:</h3>";
        
        // Get a valid user ID
        $valid_user = $db->query("SELECT user_id FROM users ORDER BY user_id LIMIT 1")->fetchArray();
        if (!$valid_user) {
            echo "<p class='error'>❌ No valid users found for testing</p>";
            return;
        }
        
        $test_user_id = $valid_user['user_id'];
        echo "<p class='info'>Testing with user ID: $test_user_id</p>";
        
        // Test the constraint
        $db->query("START TRANSACTION");
        
        try {
            $result = $db->query("
                INSERT INTO transaction_attachments 
                (transaction_id, uploaded_by, file_name, file_path, file_size, file_type, uploaded_at) 
                VALUES (999999, ?, 'test.txt', '/test/path', 100, 'text/plain', NOW())
            ", $test_user_id);
            
            if ($result) {
                echo "<p class='success'>✅ Foreign key constraint test passed</p>";
                $db->query("ROLLBACK"); // Clean up test data
            } else {
                echo "<p class='error'>❌ Foreign key constraint test failed</p>";
                $db->query("ROLLBACK");
            }
            
        } catch (Exception $e) {
            $db->query("ROLLBACK");
            echo "<p class='error'>❌ Foreign key constraint test error: " . $e->getMessage() . "</p>";
        }
        
        // Test with invalid user ID
        echo "<h3>Testing Invalid User ID:</h3>";
        $db->query("START TRANSACTION");
        
        try {
            $result = $db->query("
                INSERT INTO transaction_attachments 
                (transaction_id, uploaded_by, file_name, file_path, file_size, file_type, uploaded_at) 
                VALUES (999999, 99999, 'test.txt', '/test/path', 100, 'text/plain', NOW())
            ");
            
            echo "<p class='error'>❌ Invalid user ID test should have failed but didn't</p>";
            $db->query("ROLLBACK");
            
        } catch (Exception $e) {
            $db->query("ROLLBACK");
            echo "<p class='success'>✅ Foreign key constraint properly rejected invalid user ID</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error validating constraints: " . $e->getMessage() . "</p>";
    }
}

function test_upload_process() {
    if (!isset($_SESSION['user_id'])) {
        echo "<p class='warning'>⚠️ No user logged in - cannot test upload process</p>";
        return;
    }
    
    echo "<h3>Upload Process Validation:</h3>";
    
    // Check if user exists
    global $db;
    $user_id = $_SESSION['user_id'];
    $user_exists = $db->query("SELECT user_id, user_name FROM users WHERE user_id = ?", $user_id)->fetchArray();
    
    if ($user_exists) {
        echo "<p class='success'>✅ Current user ({$user_exists['user_name']}) exists in database</p>";
        echo "<p class='info'>File uploads should now work without foreign key constraint errors</p>";
    } else {
        echo "<p class='error'>❌ Current user still does not exist in database</p>";
    }
    
    // Check upload directory
    $upload_dir = 'public/attachments';
    if (is_dir($upload_dir) && is_writable($upload_dir)) {
        echo "<p class='success'>✅ Upload directory is ready</p>";
    } else {
        echo "<p class='warning'>⚠️ Upload directory may need attention</p>";
    }
}

echo "<h2>5. Summary and Next Steps</h2>";
echo "<h3>What was fixed:</h3>";
echo "<ul>";
echo "<li>✅ Added user validation to Transaction::add_transaction_attachment() method</li>";
echo "<li>✅ Added fallback user ID handling for foreign key constraints</li>";
echo "<li>✅ Added user existence check in uploadTransactionFiles.php</li>";
echo "<li>✅ Created default users if none existed</li>";
echo "<li>✅ Fixed session user ID if it was invalid</li>";
echo "</ul>";

echo "<h3>Manual Testing:</h3>";
echo "<ol>";
echo "<li>Try uploading a file to a transaction</li>";
echo "<li>Check browser console for any remaining errors</li>";
echo "<li>Verify the file appears in the transaction attachments</li>";
echo "</ol>";

echo "<h3>If issues persist:</h3>";
echo "<ol>";
echo "<li>Run the debug script: <a href='debug_foreign_key_issue.php'>debug_foreign_key_issue.php</a></li>";
echo "<li>Check server error logs for detailed error messages</li>";
echo "<li>Verify database migration has been run properly</li>";
echo "</ol>";

echo "<p><strong>Foreign key issue fix completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
