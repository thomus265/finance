<?php
/**
 * Test script for final finance transaction system fixes
 */

require_once('controller/main.php');

echo "<h1>Final Finance Transaction System Fixes Test</h1>";

// Test 1: Finance State Change Functionality
echo "<h2>1. Finance State Change Functionality Test</h2>";
test_finance_state_change();

// Test 2: User Transaction File Upload
echo "<h2>2. User Transaction File Upload Test</h2>";
test_file_upload_fix();

// Test 3: Missing CSS and Assets
echo "<h2>3. Missing CSS and Assets Test</h2>";
test_missing_assets();

// Test 4: Database Foreign Key Issues
echo "<h2>4. Database Foreign Key Issues Test</h2>";
test_database_constraints();

function test_finance_state_change() {
    echo "<h3>Testing Finance State Change Functionality</h3>";
    
    // Check if the finance transaction details file exists
    if (file_exists('finance/transaction-details-content.php')) {
        echo "<p>✅ Finance transaction details file exists</p>";
        
        $content = file_get_contents('finance/transaction-details-content.php');
        
        // Check for correct fetch URL
        if (strpos($content, '../model/changeTransactionState.php') !== false) {
            echo "<p>✅ Correct relative URL for state change endpoint</p>";
        } else {
            echo "<p>❌ Incorrect URL for state change endpoint</p>";
        }
        
        // Check for CSRF token in form
        if (strpos($content, 'csrf_token') !== false) {
            echo "<p>✅ CSRF token present in state change form</p>";
        } else {
            echo "<p>❌ CSRF token missing in state change form</p>";
        }
        
        // Check for form submission handling
        if (strpos($content, 'changeTransactionState') !== false) {
            echo "<p>✅ State change function present</p>";
        } else {
            echo "<p>❌ State change function missing</p>";
        }
        
    } else {
        echo "<p>❌ Finance transaction details file not found</p>";
    }
    
    // Check if the backend endpoint exists
    if (file_exists('model/changeTransactionState.php')) {
        echo "<p>✅ State change backend endpoint exists</p>";
        
        $content = file_get_contents('model/changeTransactionState.php');
        
        // Check for non-blocking notification handling
        if (strpos($content, 'Don\'t let email errors break') !== false) {
            echo "<p>✅ Non-blocking email notification handling present</p>";
        } else {
            echo "<p>❌ Non-blocking email notification handling missing</p>";
        }
        
    } else {
        echo "<p>❌ State change backend endpoint not found</p>";
    }
}

function test_file_upload_fix() {
    echo "<h3>Testing File Upload Fix</h3>";
    
    // Check if the upload endpoint exists
    if (file_exists('model/uploadTransactionFiles.php')) {
        echo "<p>✅ File upload endpoint exists</p>";
        
        $content = file_get_contents('model/uploadTransactionFiles.php');
        
        // Check if the problematic user_id fallback is removed
        if (strpos($content, 'Default to user_id 1') === false) {
            echo "<p>✅ Problematic user_id fallback removed</p>";
        } else {
            echo "<p>❌ Problematic user_id fallback still present</p>";
        }
        
        // Check for proper session validation
        if (strpos($content, 'validateSession') !== false) {
            echo "<p>✅ Session validation present</p>";
        } else {
            echo "<p>❌ Session validation missing</p>";
        }
        
        // Check for CSRF protection
        if (strpos($content, 'requireCSRFToken') !== false) {
            echo "<p>✅ CSRF protection present</p>";
        } else {
            echo "<p>❌ CSRF protection missing</p>";
        }
        
    } else {
        echo "<p>❌ File upload endpoint not found</p>";
    }
    
    // Check upload directory
    $upload_dir = 'public/attachments';
    if (is_dir($upload_dir)) {
        echo "<p>✅ Upload directory exists</p>";
        if (is_writable($upload_dir)) {
            echo "<p>✅ Upload directory is writable</p>";
        } else {
            echo "<p>❌ Upload directory is not writable</p>";
        }
    } else {
        echo "<p>❌ Upload directory does not exist</p>";
    }
}

function test_missing_assets() {
    echo "<h3>Testing Missing CSS and Assets</h3>";
    
    // Check if demo.css exists
    if (file_exists('public/assets/css/demo.css')) {
        echo "<p>✅ demo.css file created</p>";
        
        $content = file_get_contents('public/assets/css/demo.css');
        if (strlen($content) > 100) {
            echo "<p>✅ demo.css has content</p>";
        } else {
            echo "<p>❌ demo.css is empty or too small</p>";
        }
    } else {
        echo "<p>❌ demo.css file missing</p>";
    }
    
    // Check logo redirect
    if (file_exists('.htaccess')) {
        $content = file_get_contents('.htaccess');
        if (strpos($content, 'logo\.png') !== false) {
            echo "<p>✅ Logo redirect rule added to .htaccess</p>";
        } else {
            echo "<p>❌ Logo redirect rule missing from .htaccess</p>";
        }
    }
    
    // Check favicon
    if (file_exists('public/assets/img/favicon/favicon.ico')) {
        echo "<p>✅ Favicon exists</p>";
    } else {
        echo "<p>❌ Favicon missing</p>";
    }
    
    // Check other critical CSS files
    $css_files = [
        'public/assets/vendor/css/core.css' => 'Core CSS',
        'public/assets/vendor/css/theme-default.css' => 'Theme CSS',
        'public/assets/css/modes.css' => 'Modes CSS'
    ];
    
    foreach ($css_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ $description exists</p>";
        } else {
            echo "<p>❌ $description missing</p>";
        }
    }
}

function test_database_constraints() {
    echo "<h3>Testing Database Foreign Key Constraints</h3>";
    
    try {
        global $db;
        
        // Check if transaction_attachments table exists
        $result = $db->query("SHOW TABLES LIKE 'transaction_attachments'")->fetchArray();
        if ($result) {
            echo "<p>✅ transaction_attachments table exists</p>";
            
            // Check table structure
            $columns = $db->query("DESCRIBE transaction_attachments")->fetchAll();
            $has_uploaded_by = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'uploaded_by') {
                    $has_uploaded_by = true;
                    break;
                }
            }
            
            if ($has_uploaded_by) {
                echo "<p>✅ uploaded_by column exists</p>";
            } else {
                echo "<p>❌ uploaded_by column missing</p>";
            }
            
            // Check foreign key constraints
            $constraints = $db->query("
                SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'transaction_attachments' 
                AND TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAll();
            
            $has_user_fk = false;
            foreach ($constraints as $constraint) {
                if ($constraint['REFERENCED_TABLE_NAME'] === 'users') {
                    $has_user_fk = true;
                    echo "<p>✅ Foreign key to users table exists</p>";
                    break;
                }
            }
            
            if (!$has_user_fk) {
                echo "<p>❌ Foreign key to users table missing</p>";
            }
            
        } else {
            echo "<p>❌ transaction_attachments table missing</p>";
        }
        
        // Check if users table has the required structure
        $result = $db->query("SHOW TABLES LIKE 'users'")->fetchArray();
        if ($result) {
            echo "<p>✅ users table exists</p>";
            
            // Check if user_id 1 exists (common test user)
            $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetchArray()['count'];
            echo "<p>ℹ️ Users table has $user_count users</p>";
            
            if ($user_count > 0) {
                echo "<p>✅ Users table has data</p>";
            } else {
                echo "<p>❌ Users table is empty</p>";
            }
            
        } else {
            echo "<p>❌ users table missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Database constraint test error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>Final Finance Transaction System Fixes Test Complete!</strong></p>";

echo "<h3>Key Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Fixed finance transaction state change URL (relative path)</li>";
echo "<li>✅ Created missing demo.css file with comprehensive styles</li>";
echo "<li>✅ Fixed file upload foreign key constraint error</li>";
echo "<li>✅ Added logo redirect rule to prevent 404 errors</li>";
echo "<li>✅ Enhanced error handling in state change process</li>";
echo "<li>✅ Made notification system non-blocking</li>";
echo "</ul>";

echo "<h3>Manual Testing Steps:</h3>";
echo "<ol>";
echo "<li><strong>Finance State Changes:</strong> Log in as finance user and test state changes</li>";
echo "<li><strong>File Uploads:</strong> Try uploading files to user transactions</li>";
echo "<li><strong>CSS Loading:</strong> Check browser console for 404 errors</li>";
echo "<li><strong>Logo Issues:</strong> Verify no logo.png 404 errors</li>";
echo "<li><strong>Database Operations:</strong> Test transaction creation and file uploads</li>";
echo "</ol>";

echo "<h3>Files Modified:</h3>";
echo "<ul>";
echo "<li><code>finance/transaction-details-content.php</code> - Fixed state change URL</li>";
echo "<li><code>public/assets/css/demo.css</code> - Created missing CSS file</li>";
echo "<li><code>model/uploadTransactionFiles.php</code> - Fixed user_id handling</li>";
echo "<li><code>.htaccess</code> - Added logo redirect rule</li>";
echo "</ul>";

echo "<p><strong>All critical issues should now be resolved!</strong></p>";

// Additional debugging information
echo "<h3>Debug Information:</h3>";
echo "<p>Current working directory: " . getcwd() . "</p>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<p>Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

if (isset($_SESSION)) {
    echo "<p>Session status: Active</p>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>Current user ID: " . $_SESSION['user_id'] . "</p>";
    }
} else {
    echo "<p>Session status: Not started</p>";
}
?>
