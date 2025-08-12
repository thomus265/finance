<?php
/**
 * Test script to verify transaction creation fix
 */

require_once('controller/main.php');

echo "<h1>Transaction Creation Fix Test</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// Test 1: Check session and user
echo "<h2>1. Session and User Validation</h2>";
test_session_user();

// Test 2: Check Transaction class methods
echo "<h2>2. Transaction Class Methods</h2>";
test_transaction_methods();

// Test 3: Test parameter types
echo "<h2>3. Parameter Type Validation</h2>";
test_parameter_types();

function test_session_user() {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        echo "<p class='success'>✅ User logged in: ID $user_id</p>";
        
        // Check if user exists in database
        global $db;
        try {
            $user_exists = $db->query("SELECT user_id, user_name FROM users WHERE user_id = ?", $user_id)->fetchArray();
            if ($user_exists) {
                echo "<p class='success'>✅ User exists in database: {$user_exists['user_name']}</p>";
            } else {
                echo "<p class='error'>❌ User ID $user_id does not exist in database</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ No user logged in</p>";
    }
    
    // Check if User object is available
    global $user;
    if (isset($user) && is_object($user)) {
        echo "<p class='success'>✅ User object is available</p>";
        echo "<p class='info'>User object class: " . get_class($user) . "</p>";
    } else {
        echo "<p class='error'>❌ User object not available</p>";
    }
}

function test_transaction_methods() {
    global $transaction;
    
    if (isset($transaction) && is_object($transaction)) {
        echo "<p class='success'>✅ Transaction object is available</p>";
        echo "<p class='info'>Transaction object class: " . get_class($transaction) . "</p>";
        
        // Check if methods exist
        if (method_exists($transaction, 'add_transaction')) {
            echo "<p class='success'>✅ add_transaction method exists</p>";
            
            // Get method reflection to check parameters
            $reflection = new ReflectionMethod($transaction, 'add_transaction');
            $parameters = $reflection->getParameters();
            
            echo "<p class='info'>add_transaction parameters:</p>";
            echo "<ul>";
            foreach ($parameters as $param) {
                $type = $param->getType() ? $param->getType()->getName() : 'mixed';
                echo "<li>{$param->getName()} ({$type})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>❌ add_transaction method missing</p>";
        }
        
        if (method_exists($transaction, 'add_transaction_attachment')) {
            echo "<p class='success'>✅ add_transaction_attachment method exists</p>";
            
            // Get method reflection to check parameters
            $reflection = new ReflectionMethod($transaction, 'add_transaction_attachment');
            $parameters = $reflection->getParameters();
            
            echo "<p class='info'>add_transaction_attachment parameters:</p>";
            echo "<ul>";
            foreach ($parameters as $param) {
                $type = $param->getType() ? $param->getType()->getName() : 'mixed';
                echo "<li>{$param->getName()} ({$type})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>❌ add_transaction_attachment method missing</p>";
        }
    } else {
        echo "<p class='error'>❌ Transaction object not available</p>";
    }
}

function test_parameter_types() {
    if (!isset($_SESSION['user_id'])) {
        echo "<p class='warning'>⚠️ Cannot test parameter types without logged in user</p>";
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    global $user, $transaction;
    
    echo "<h3>Type Testing:</h3>";
    
    // Test user_id type
    echo "<p class='info'>user_id type: " . gettype($user_id) . " (value: $user_id)</p>";
    
    // Test user object type
    if (isset($user)) {
        echo "<p class='info'>user object type: " . gettype($user) . " (class: " . get_class($user) . ")</p>";
    }
    
    // Test what happens when we pass different types
    echo "<h3>Parameter Validation:</h3>";
    
    // Test with correct user_id (integer/string)
    if (is_numeric($user_id)) {
        echo "<p class='success'>✅ user_id is numeric - correct for database operations</p>";
    } else {
        echo "<p class='warning'>⚠️ user_id is not numeric - may cause issues</p>";
    }
    
    // Test with user object (should fail)
    if (is_object($user)) {
        echo "<p class='error'>❌ Passing user object instead of user_id would cause: 'Object of class User could not be converted to string'</p>";
    }
    
    // Show the fix
    echo "<h3>The Fix:</h3>";
    echo "<p class='success'>✅ Changed from: <code>\$user</code> (object)</p>";
    echo "<p class='success'>✅ Changed to: <code>\$user_id</code> (integer/string)</p>";
    
    echo "<h3>Files Fixed:</h3>";
    echo "<ul>";
    echo "<li><code>model/newTransaction.php</code> line 63: add_transaction() call</li>";
    echo "<li><code>model/newTransaction.php</code> line 118: add_transaction_attachment() call</li>";
    echo "</ul>";
}

echo "<h2>4. Manual Testing Steps</h2>";
echo "<ol>";
echo "<li><strong>Test Transaction Creation:</strong> Try creating a new transaction with file attachments</li>";
echo "<li><strong>Check Browser Console:</strong> Should see no 'Object could not be converted to string' errors</li>";
echo "<li><strong>Check Server Logs:</strong> Should see no fatal errors in PHP error logs</li>";
echo "<li><strong>Verify Database:</strong> Check that transactions and attachments are created properly</li>";
echo "</ol>";

echo "<h2>5. Expected Results</h2>";
echo "<ul>";
echo "<li>✅ Transaction creation should work without fatal errors</li>";
echo "<li>✅ File attachments should be linked properly</li>";
echo "<li>✅ JSON responses should be valid (no HTML error messages)</li>";
echo "<li>✅ Database foreign key constraints should be satisfied</li>";
echo "</ul>";

echo "<h2>6. If Issues Persist</h2>";
echo "<ul>";
echo "<li>Check that users exist in the database</li>";
echo "<li>Verify session is properly established</li>";
echo "<li>Check PHP error logs for detailed error messages</li>";
echo "<li>Run the foreign key debug script: <a href='debug_foreign_key_issue.php'>debug_foreign_key_issue.php</a></li>";
echo "</ul>";

echo "<p><strong>Transaction creation fix test completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
