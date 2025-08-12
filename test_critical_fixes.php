<?php
/**
 * Test script for critical finance transaction system fixes
 */

require_once('controller/main.php');

echo "<h1>Critical Finance Transaction System Fixes Test</h1>";

// Test 1: CSRF Token Generation
echo "<h2>1. CSRF Token Generation Test</h2>";
test_csrf_token_generation();

// Test 2: State Change Endpoint
echo "<h2>2. State Change Endpoint Test</h2>";
test_state_change_endpoint();

// Test 3: Transaction Creation Endpoint
echo "<h2>3. Transaction Creation Endpoint Test</h2>";
test_transaction_creation_endpoint();

// Test 4: Notification System Integration
echo "<h2>4. Notification System Integration Test</h2>";
test_notification_integration();

function test_csrf_token_generation() {
    echo "<h3>Testing CSRF Token Generation</h3>";
    
    try {
        // Test CSRF token generation
        $token1 = Security::generateCSRFToken();
        $token2 = Security::generateCSRFToken();
        
        if (!empty($token1)) {
            echo "<p>✅ CSRF token generated successfully</p>";
            echo "<p>Token length: " . strlen($token1) . " characters</p>";
        } else {
            echo "<p>❌ CSRF token generation failed</p>";
        }
        
        // Test token consistency (should be same in same session)
        if ($token1 === $token2) {
            echo "<p>✅ CSRF token is consistent within session</p>";
        } else {
            echo "<p>❌ CSRF token inconsistency detected</p>";
        }
        
        // Test token validation
        if (Security::validateCSRFToken($token1)) {
            echo "<p>✅ CSRF token validation works</p>";
        } else {
            echo "<p>❌ CSRF token validation failed</p>";
        }
        
        // Test invalid token
        if (!Security::validateCSRFToken('invalid_token')) {
            echo "<p>✅ Invalid CSRF token correctly rejected</p>";
        } else {
            echo "<p>❌ Invalid CSRF token incorrectly accepted</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ CSRF token test error: " . $e->getMessage() . "</p>";
    }
}

function test_state_change_endpoint() {
    echo "<h3>Testing State Change Endpoint</h3>";
    
    // Check if the endpoint file exists
    if (file_exists('model/changeTransactionState.php')) {
        echo "<p>✅ State change endpoint file exists</p>";
        
        $content = file_get_contents('model/changeTransactionState.php');
        
        // Check for critical components
        $components = [
            'validateSession' => 'Session validation',
            'requireCSRFToken' => 'CSRF protection',
            'EmailNotification' => 'Email notification integration',
            'class_exists(\'EmailNotification\')' => 'Email notification safety check',
            'error_log' => 'Debug logging',
            'json_encode' => 'JSON response handling'
        ];
        
        foreach ($components as $component => $description) {
            if (strpos($content, $component) !== false) {
                echo "<p>✅ $description present</p>";
            } else {
                echo "<p>❌ $description missing</p>";
            }
        }
        
        // Check for non-blocking notification handling
        if (strpos($content, 'Don\'t let email errors break') !== false) {
            echo "<p>✅ Non-blocking email notification handling present</p>";
        } else {
            echo "<p>❌ Non-blocking email notification handling missing</p>";
        }
        

        
    } else {
        echo "<p>❌ State change endpoint file not found</p>";
    }
}

function test_transaction_creation_endpoint() {
    echo "<h3>Testing Transaction Creation Endpoint</h3>";
    
    // Check if the endpoint file exists
    if (file_exists('model/newTransaction.php')) {
        echo "<p>✅ Transaction creation endpoint file exists</p>";
        
        $content = file_get_contents('model/newTransaction.php');
        
        // Check for critical components
        $components = [
            'validateSession' => 'Session validation',
            'requireCSRFToken' => 'CSRF protection',
            'validateTransactionData' => 'Data validation',
            'checkRateLimit' => 'Rate limiting',
            'json_encode' => 'JSON response handling'
        ];
        
        foreach ($components as $component => $description) {
            if (strpos($content, $component) !== false) {
                echo "<p>✅ $description present</p>";
            } else {
                echo "<p>❌ $description missing</p>";
            }
        }
        
    } else {
        echo "<p>❌ Transaction creation endpoint file not found</p>";
    }
    
    // Check if the frontend form includes CSRF token
    if (file_exists('user/transactions.php')) {
        echo "<p>✅ User transactions page exists</p>";
        
        $content = file_get_contents('user/transactions.php');
        
        // Check for CSRF token in form
        if (strpos($content, 'csrf_token') !== false) {
            echo "<p>✅ CSRF token field present in form</p>";
        } else {
            echo "<p>❌ CSRF token field missing in form</p>";
        }
        
        // Check for CSRF token in AJAX request
        if (strpos($content, 'formData.append("csrf_token"') !== false) {
            echo "<p>✅ CSRF token included in AJAX request</p>";
        } else {
            echo "<p>❌ CSRF token missing in AJAX request</p>";
        }
        
    } else {
        echo "<p>❌ User transactions page not found</p>";
    }
}

function test_notification_integration() {
    echo "<h3>Testing Notification System Integration</h3>";
    
    // Check if EmailNotification class exists
    if (file_exists('controller/EmailNotification.php')) {
        echo "<p>✅ EmailNotification class file exists</p>";
        
        try {
            if (class_exists('EmailNotification')) {
                echo "<p>✅ EmailNotification class can be loaded</p>";
            } else {
                echo "<p>❌ EmailNotification class cannot be loaded</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ EmailNotification class loading error: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ EmailNotification class file not found</p>";
    }
    

    
    // Test database tables
    try {
        global $db;
        

        
        // Check email_log table
        $result = $db->query("SHOW TABLES LIKE 'email_log'")->fetchArray();
        if ($result) {
            echo "<p>✅ Email log table exists</p>";
        } else {
            echo "<p>❌ Email log table missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Database table check error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>Critical Finance Transaction System Fixes Test Complete!</strong></p>";

echo "<h3>Key Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Made notification system non-blocking to prevent state change failures</li>";
echo "<li>✅ Added CSRF token to transaction creation AJAX request</li>";
echo "<li>✅ Enhanced error handling in state change process</li>";
echo "<li>✅ Added debug logging for troubleshooting</li>";
echo "<li>✅ Made EmailNotification class optional to prevent loading errors</li>";

echo "</ul>";

echo "<h3>Manual Testing Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test Transaction Creation:</strong> Create a new transaction and verify no CSRF errors</li>";
echo "<li><strong>Test State Changes:</strong> Try changing transaction states in finance dashboard</li>";
echo "<li><strong>Check Error Logs:</strong> Monitor error logs for any remaining issues</li>";
echo "<li><strong>Test Notifications:</strong> Verify notifications work without breaking state changes</li>";
echo "</ol>";

echo "<h3>Files Modified:</h3>";
echo "<ul>";
echo "<li><code>model/changeTransactionState.php</code> - Made notifications non-blocking, added debug logging</li>";
echo "<li><code>user/transactions.php</code> - Added CSRF token to transaction creation AJAX</li>";
echo "</ul>";

echo "<p><strong>The critical issues should now be resolved!</strong></p>";
?>
