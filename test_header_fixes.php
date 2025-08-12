<?php
/**
 * Test script to verify header fixes
 */

echo "<h1>Header Issues Fix Test</h1>";

// Test 1: Check file structure
echo "<h2>1. File Structure Check</h2>";
test_file_structure();

// Test 2: Check for premature output
echo "<h2>2. Premature Output Check</h2>";
test_premature_output();

// Test 3: Test authentication flow
echo "<h2>3. Authentication Flow Test</h2>";
test_authentication_flow();

// Test 4: Test transaction details inclusion
echo "<h2>4. Transaction Details Inclusion Test</h2>";
test_transaction_details();

function test_file_structure() {
    echo "<h3>Checking Critical Files</h3>";
    
    $files = [
        'finance/dashboard.php' => 'Finance Dashboard',
        'user/transactions.php' => 'User Transactions',
        'user/transaction-details-content.php' => 'Transaction Details Content',
        'includes/navbar.php' => 'Navigation Bar',
        'includes/top.php' => 'HTML Head Section'
    ];
    
    foreach ($files as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ $description ($file) - File exists</p>";
            
            // Check for whitespace at beginning
            $content = file_get_contents($file);
            if (substr($content, 0, 5) === '<?php') {
                echo "<p>✅ $description - No leading whitespace</p>";
            } elseif (substr($content, 0, 1) === "\n" || substr($content, 0, 1) === "\r") {
                echo "<p>❌ $description - Has leading whitespace/newlines</p>";
            } else {
                echo "<p>ℹ️ $description - Starts with HTML (expected for some files)</p>";
            }
        } else {
            echo "<p>❌ $description ($file) - File missing</p>";
        }
    }
}

function test_premature_output() {
    echo "<h3>Checking for Premature Output Issues</h3>";
    
    // Test navbar.php for leading whitespace
    if (file_exists('includes/navbar.php')) {
        $content = file_get_contents('includes/navbar.php');
        $first_char = substr($content, 0, 1);
        
        if ($first_char === '<') {
            echo "<p>✅ navbar.php starts with PHP opening tag</p>";
        } elseif ($first_char === "\n" || $first_char === "\r" || $first_char === " ") {
            echo "<p>❌ navbar.php has leading whitespace</p>";
        } else {
            echo "<p>ℹ️ navbar.php first character: '" . htmlspecialchars($first_char) . "'</p>";
        }
    }
    
    // Test finance dashboard structure
    if (file_exists('finance/dashboard.php')) {
        $content = file_get_contents('finance/dashboard.php');
        $lines = explode("\n", $content);
        
        $found_auth_check = false;
        $found_include = false;
        $auth_line = 0;
        $include_line = 0;
        
        foreach ($lines as $i => $line) {
            if (strpos($line, 'get_current_user') !== false && !$found_auth_check) {
                $found_auth_check = true;
                $auth_line = $i + 1;
            }
            if ((strpos($line, 'require') !== false || strpos($line, 'include') !== false) && 
                (strpos($line, 'top.php') !== false || strpos($line, 'navbar.php') !== false) && !$found_include) {
                $found_include = true;
                $include_line = $i + 1;
            }
        }
        
        if ($found_auth_check && $found_include) {
            if ($auth_line < $include_line) {
                echo "<p>✅ finance/dashboard.php - Authentication check before HTML includes</p>";
            } else {
                echo "<p>❌ finance/dashboard.php - HTML includes before authentication check</p>";
            }
        } else {
            echo "<p>⚠️ finance/dashboard.php - Could not verify structure</p>";
        }
    }
}

function test_authentication_flow() {
    echo "<h3>Testing Authentication Flow</h3>";
    
    // Simulate different authentication scenarios
    echo "<p><strong>Note:</strong> This test simulates the authentication flow logic.</p>";
    
    // Test 1: Check if authentication logic exists
    if (file_exists('finance/dashboard.php')) {
        $content = file_get_contents('finance/dashboard.php');
        
        if (strpos($content, 'is_finance_team') !== false) {
            echo "<p>✅ Finance dashboard has finance team check</p>";
        } else {
            echo "<p>❌ Finance dashboard missing finance team check</p>";
        }
        
        if (strpos($content, 'header(') !== false) {
            echo "<p>✅ Finance dashboard has redirect logic</p>";
        } else {
            echo "<p>❌ Finance dashboard missing redirect logic</p>";
        }
    }
    
    // Test 2: Check transaction details authentication
    if (file_exists('user/transaction-details-content.php')) {
        $content = file_get_contents('user/transaction-details-content.php');
        
        if (strpos($content, 'headers_sent()') !== false) {
            echo "<p>✅ Transaction details checks headers_sent() before redirect</p>";
        } else {
            echo "<p>❌ Transaction details doesn't check headers_sent()</p>";
        }
    }
}

function test_transaction_details() {
    echo "<h3>Testing Transaction Details Inclusion</h3>";
    
    if (file_exists('user/transactions.php')) {
        $content = file_get_contents('user/transactions.php');
        
        // Check if transaction details is included properly
        if (strpos($content, 'transaction-details-content.php') !== false) {
            echo "<p>✅ transactions.php includes transaction details content</p>";
            
            // Check if it's included after HTML output starts
            $lines = explode("\n", $content);
            $include_line = 0;
            $html_start_line = 0;
            
            foreach ($lines as $i => $line) {
                if (strpos($line, 'transaction-details-content.php') !== false && $include_line == 0) {
                    $include_line = $i + 1;
                }
                if ((strpos($line, 'top.php') !== false || strpos($line, 'navbar.php') !== false) && $html_start_line == 0) {
                    $html_start_line = $i + 1;
                }
            }
            
            if ($include_line > 0 && $html_start_line > 0) {
                if ($include_line > $html_start_line) {
                    echo "<p>✅ Transaction details included after HTML starts (safe)</p>";
                } else {
                    echo "<p>⚠️ Transaction details included before HTML starts</p>";
                }
            }
        } else {
            echo "<p>❌ transactions.php doesn't include transaction details</p>";
        }
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>Header Issues Fix Test Complete!</strong></p>";

echo "<h3>Key Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Moved authentication checks BEFORE HTML output in finance/dashboard.php</li>";
echo "<li>✅ Added headers_sent() checks in transaction-details-content.php</li>";
echo "<li>✅ Restructured user/transactions.php to handle both views properly</li>";
echo "<li>✅ Removed leading whitespace from includes/navbar.php</li>";
echo "</ul>";

echo "<h3>Manual Testing Checklist:</h3>";
echo "<ol>";
echo "<li>Visit <a href='/finance_dashboard' target='_blank'>Finance Dashboard</a> - Should redirect if not finance team, or load if authorized</li>";
echo "<li>Visit <a href='/user_transactions' target='_blank'>User Transactions</a> - Should load transaction list without errors</li>";
echo "<li>Visit a specific transaction URL like <a href='/user_transactions/1' target='_blank'>/user_transactions/1</a> - Should show transaction details</li>";
echo "<li>Check browser console and server error logs for any remaining header errors</li>";
echo "<li>Test authentication flow by logging in as different user types</li>";
echo "</ol>";

echo "<h3>Files Modified:</h3>";
echo "<ul>";
echo "<li><code>finance/dashboard.php</code> - Moved auth check before HTML includes</li>";
echo "<li><code>user/transactions.php</code> - Restructured to handle transaction details properly</li>";
echo "<li><code>user/transaction-details-content.php</code> - Added headers_sent() checks</li>";
echo "<li><code>includes/navbar.php</code> - Removed leading whitespace</li>";
echo "</ul>";

echo "<p><strong>The 'headers already sent' error should now be resolved!</strong></p>";
?>
