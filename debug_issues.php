<?php
/**
 * Comprehensive debugging script for finance transaction system issues
 */

require_once('controller/main.php');

echo "<h1>Finance Transaction System Debug Report</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Debug 1: File Structure and Paths
echo "<div class='debug-section'>";
echo "<h2>1. File Structure and Asset Paths</h2>";
debug_file_structure();
echo "</div>";

// Debug 2: Database Structure
echo "<div class='debug-section'>";
echo "<h2>2. Database Structure</h2>";
debug_database_structure();
echo "</div>";

// Debug 3: Session and Security
echo "<div class='debug-section'>";
echo "<h2>3. Session and Security</h2>";
debug_session_security();
echo "</div>";

// Debug 4: State Change Endpoint
echo "<div class='debug-section'>";
echo "<h2>4. State Change Endpoint</h2>";
debug_state_change_endpoint();
echo "</div>";

// Debug 5: File Upload System
echo "<div class='debug-section'>";
echo "<h2>5. File Upload System</h2>";
debug_file_upload_system();
echo "</div>";

function debug_file_structure() {
    echo "<h3>Asset File Existence Check:</h3>";
    
    $assets = [
        'public/assets/css/demo.css' => 'Demo CSS',
        'public/assets/vendor/css/core.css' => 'Core CSS',
        'public/assets/vendor/css/theme-default.css' => 'Theme CSS',
        'public/assets/css/modes.css' => 'Modes CSS',
        'public/assets/js/config.js' => 'Config JS',
        'public/assets/js/main.js' => 'Main JS',
        'public/assets/vendor/js/bootstrap.js' => 'Bootstrap JS'
    ];
    
    foreach ($assets as $file => $description) {
        if (file_exists($file)) {
            $size = filesize($file);
            echo "<p class='success'>✅ $description ($file) - Size: " . number_format($size) . " bytes</p>";
        } else {
            echo "<p class='error'>❌ $description ($file) - MISSING</p>";
        }
    }
    
    echo "<h3>URL Path Testing:</h3>";
    $current_dir = getcwd();
    echo "<p class='info'>Current working directory: $current_dir</p>";
    
    // Test relative vs absolute paths
    echo "<p class='info'>From root directory:</p>";
    echo "<ul>";
    echo "<li>Relative: ./public/assets/css/demo.css</li>";
    echo "<li>Absolute: /public/assets/css/demo.css</li>";
    echo "</ul>";
    
    echo "<p class='info'>From user/ directory:</p>";
    echo "<ul>";
    echo "<li>Relative should be: ../public/assets/css/demo.css</li>";
    echo "<li>Absolute: /public/assets/css/demo.css (recommended)</li>";
    echo "</ul>";
    
    // Check .htaccess
    if (file_exists('.htaccess')) {
        echo "<p class='success'>✅ .htaccess exists</p>";
        $htaccess_content = file_get_contents('.htaccess');
        if (strpos($htaccess_content, 'logo\.png') !== false) {
            echo "<p class='success'>✅ Logo redirect rule present</p>";
        } else {
            echo "<p class='warning'>⚠️ Logo redirect rule missing</p>";
        }
    } else {
        echo "<p class='error'>❌ .htaccess missing</p>";
    }
}

function debug_database_structure() {
    global $db;
    
    try {
        echo "<h3>Database Connection:</h3>";
        $result = $db->query("SELECT DATABASE() as db_name")->fetchArray();
        echo "<p class='success'>✅ Connected to database: " . $result['db_name'] . "</p>";
        
        echo "<h3>Required Tables:</h3>";
        $required_tables = [
            'users' => 'Users table',
            'transactions' => 'Transactions table',
            'transaction_attachments' => 'Transaction attachments table',
            'workflow_states' => 'Workflow states table',
            'notifications' => 'Notifications table',
            'email_log' => 'Email log table'
        ];
        
        foreach ($required_tables as $table => $description) {
            $result = $db->query("SHOW TABLES LIKE '$table'")->fetchArray();
            if ($result) {
                echo "<p class='success'>✅ $description exists</p>";
                
                // Check specific table structures
                if ($table === 'transaction_attachments') {
                    $columns = $db->query("DESCRIBE $table")->fetchAll();
                    $has_uploaded_by = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === 'uploaded_by') {
                            $has_uploaded_by = true;
                            echo "<p class='info'>   - uploaded_by column: " . $column['Type'] . "</p>";
                            break;
                        }
                    }
                    if (!$has_uploaded_by) {
                        echo "<p class='error'>   ❌ uploaded_by column missing</p>";
                    }
                    
                    // Check foreign key constraints
                    $fks = $db->query("
                        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = '$table' 
                        AND TABLE_SCHEMA = DATABASE()
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ")->fetchAll();
                    
                    foreach ($fks as $fk) {
                        echo "<p class='info'>   - FK: {$fk['CONSTRAINT_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</p>";
                    }
                }
                
                if ($table === 'users') {
                    $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetchArray()['count'];
                    echo "<p class='info'>   - User count: $user_count</p>";
                    
                    if ($user_count > 0) {
                        $users = $db->query("SELECT user_id, user_name, user_type FROM users LIMIT 5")->fetchAll();
                        foreach ($users as $user) {
                            echo "<p class='info'>   - User ID {$user['user_id']}: {$user['user_name']} (Type: {$user['user_type']})</p>";
                        }
                    }
                }
            } else {
                echo "<p class='error'>❌ $description missing</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    }
}

function debug_session_security() {
    echo "<h3>Session Status:</h3>";
    
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
            echo "<p class='warning'>⚠️ No user logged in</p>";
        }
    } else {
        echo "<p class='error'>❌ Session not active</p>";
    }
    
    echo "<h3>CSRF Token:</h3>";
    try {
        $token = Security::generateCSRFToken();
        echo "<p class='success'>✅ CSRF token generated: " . substr($token, 0, 20) . "...</p>";
        
        if (Security::validateCSRFToken($token)) {
            echo "<p class='success'>✅ CSRF token validation works</p>";
        } else {
            echo "<p class='error'>❌ CSRF token validation failed</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ CSRF token error: " . $e->getMessage() . "</p>";
    }
}

function debug_state_change_endpoint() {
    echo "<h3>State Change Endpoint:</h3>";
    
    if (file_exists('model/changeTransactionState.php')) {
        echo "<p class='success'>✅ State change endpoint exists</p>";
        
        // Check if it's accessible
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/model/changeTransactionState.php';
        echo "<p class='info'>Endpoint URL: $url</p>";
        
        // Test with a simple request (this won't work due to CSRF, but we can see if the file loads)
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => 'test=1',
                'timeout' => 5
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        if ($result !== false) {
            echo "<p class='success'>✅ Endpoint is accessible</p>";
            if (strpos($result, 'json') !== false) {
                echo "<p class='info'>Response appears to be JSON</p>";
            }
        } else {
            echo "<p class='error'>❌ Endpoint not accessible</p>";
        }
        
    } else {
        echo "<p class='error'>❌ State change endpoint missing</p>";
    }
    
    echo "<h3>Finance Transaction Details:</h3>";
    if (file_exists('finance/transaction-details-content.php')) {
        echo "<p class='success'>✅ Finance transaction details exists</p>";
        
        $content = file_get_contents('finance/transaction-details-content.php');
        
        // Check for correct URL
        if (strpos($content, '../model/changeTransactionState.php') !== false) {
            echo "<p class='success'>✅ Correct relative URL found</p>";
        } else if (strpos($content, '/model/changeTransactionState.php') !== false) {
            echo "<p class='warning'>⚠️ Absolute URL found (may cause issues)</p>";
        } else {
            echo "<p class='error'>❌ State change URL not found</p>";
        }
        
        // Check for JavaScript functions
        if (strpos($content, 'quickAction') !== false) {
            echo "<p class='success'>✅ quickAction function found</p>";
        } else {
            echo "<p class='error'>❌ quickAction function missing</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Finance transaction details missing</p>";
    }
}

function debug_file_upload_system() {
    echo "<h3>File Upload System:</h3>";
    
    if (file_exists('model/uploadTransactionFiles.php')) {
        echo "<p class='success'>✅ Upload endpoint exists</p>";
        
        $content = file_get_contents('model/uploadTransactionFiles.php');
        
        // Check for problematic user_id fallback
        if (strpos($content, 'Default to user_id 1') !== false) {
            echo "<p class='error'>❌ Problematic user_id fallback still present</p>";
        } else {
            echo "<p class='success'>✅ User_id fallback removed</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Upload endpoint missing</p>";
    }
    
    // Check upload directory
    $upload_dir = 'public/attachments';
    if (is_dir($upload_dir)) {
        echo "<p class='success'>✅ Upload directory exists</p>";
        if (is_writable($upload_dir)) {
            echo "<p class='success'>✅ Upload directory is writable</p>";
        } else {
            echo "<p class='error'>❌ Upload directory not writable</p>";
        }
    } else {
        echo "<p class='error'>❌ Upload directory missing</p>";
        // Try to create it
        if (mkdir($upload_dir, 0755, true)) {
            echo "<p class='success'>✅ Upload directory created</p>";
        } else {
            echo "<p class='error'>❌ Failed to create upload directory</p>";
        }
    }
}

echo "<div class='debug-section'>";
echo "<h2>6. Recommended Actions</h2>";
echo "<h3>To Fix CSS 404 Errors:</h3>";
echo "<ol>";
echo "<li>Ensure all CSS/JS paths in includes/top.php and includes/bottom.php use absolute paths (/public/assets/...)</li>";
echo "<li>Clear browser cache and reload the page</li>";
echo "<li>Check browser developer tools Network tab for actual 404 errors</li>";
echo "</ol>";

echo "<h3>To Fix Finance State Changes:</h3>";
echo "<ol>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Verify CSRF tokens are being generated correctly</li>";
echo "<li>Check server error logs for backend errors</li>";
echo "<li>Test the endpoint directly with proper parameters</li>";
echo "</ol>";

echo "<h3>To Fix File Upload Foreign Key Errors:</h3>";
echo "<ol>";
echo "<li>Ensure user is properly logged in before uploading</li>";
echo "<li>Check that the user_id exists in the users table</li>";
echo "<li>Verify the transaction_attachments table structure</li>";
echo "<li>Run the database migration if needed</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Debug report completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
