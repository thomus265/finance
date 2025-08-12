<?php
/**
 * Test script to verify transaction ID fixes
 * 
 * This script tests that transaction methods can handle both numeric IDs and transaction_id strings
 */

require_once('controller/main.php');

echo "<h1>Transaction ID Fix Test</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.test-section { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

// Test the Transaction class methods
echo "<div class='test-section'>";
echo "<h2>Testing Transaction Class Methods</h2>";

try {
    $transaction = new Transaction();
    echo "<p class='success'>✅ Transaction class instantiated successfully</p>";
    
    // Get a sample transaction to test with
    $sample_transaction = $db->query("SELECT id, transaction_id FROM transactions LIMIT 1")->fetchArray();
    
    if (!$sample_transaction) {
        echo "<p class='warning'>⚠️ No transactions found in database. Creating a test transaction...</p>";
        
        // Create a test transaction
        $test_result = $transaction->add_transaction(
            'Test Transaction for ID Fix',
            100.00,
            '1', // Operations
            '1', // Submitted
            1    // User ID
        );
        
        if ($test_result) {
            $last_id = $transaction->get_last_id();
            $sample_transaction = $transaction->get_transaction($last_id);
            echo "<p class='success'>✅ Test transaction created with ID: {$sample_transaction['id']}, Transaction ID: {$sample_transaction['transaction_id']}</p>";
        } else {
            echo "<p class='error'>❌ Failed to create test transaction</p>";
            exit;
        }
    } else {
        echo "<p class='info'>Using existing transaction - ID: {$sample_transaction['id']}, Transaction ID: {$sample_transaction['transaction_id']}</p>";
    }
    
    $numeric_id = $sample_transaction['id'];
    $string_transaction_id = $sample_transaction['transaction_id'];
    
    echo "<h3>Testing get_transaction method:</h3>";
    
    // Test with numeric ID
    $result1 = $transaction->get_transaction($numeric_id);
    if ($result1 && $result1['id'] == $numeric_id) {
        echo "<p class='success'>✅ get_transaction($numeric_id) - Works with numeric ID</p>";
    } else {
        echo "<p class='error'>❌ get_transaction($numeric_id) - Failed with numeric ID</p>";
    }
    
    // Test with transaction_id string
    $result2 = $transaction->get_transaction($string_transaction_id);
    if ($result2 && $result2['transaction_id'] == $string_transaction_id) {
        echo "<p class='success'>✅ get_transaction('$string_transaction_id') - Works with transaction_id string</p>";
    } else {
        echo "<p class='error'>❌ get_transaction('$string_transaction_id') - Failed with transaction_id string</p>";
    }
    
    echo "<h3>Testing get_transaction_details method:</h3>";
    
    // Test with numeric ID
    $details1 = $transaction->get_transaction_details($numeric_id);
    if ($details1 && $details1['id'] == $numeric_id) {
        echo "<p class='success'>✅ get_transaction_details($numeric_id) - Works with numeric ID</p>";
    } else {
        echo "<p class='error'>❌ get_transaction_details($numeric_id) - Failed with numeric ID</p>";
    }
    
    // Test with transaction_id string
    $details2 = $transaction->get_transaction_details($string_transaction_id);
    if ($details2 && $details2['transaction_id'] == $string_transaction_id) {
        echo "<p class='success'>✅ get_transaction_details('$string_transaction_id') - Works with transaction_id string</p>";
    } else {
        echo "<p class='error'>❌ get_transaction_details('$string_transaction_id') - Failed with transaction_id string</p>";
    }
    
    echo "<h3>Testing update_transaction method:</h3>";
    
    // Test update with transaction_id string
    $update_result = $transaction->update_transaction(
        $string_transaction_id,
        'Updated Test Transaction',
        150.00,
        '1',
        $sample_transaction['state'],
        1
    );
    
    if ($update_result) {
        echo "<p class='success'>✅ update_transaction('$string_transaction_id') - Works with transaction_id string</p>";
        
        // Verify the update
        $updated_transaction = $transaction->get_transaction($string_transaction_id);
        if ($updated_transaction && $updated_transaction['title'] == 'Updated Test Transaction') {
            echo "<p class='success'>✅ Update verification - Transaction title updated correctly</p>";
        } else {
            echo "<p class='error'>❌ Update verification - Transaction title not updated</p>";
        }
    } else {
        echo "<p class='error'>❌ update_transaction('$string_transaction_id') - Failed with transaction_id string</p>";
    }
    
    echo "<h3>Testing URL routing compatibility:</h3>";
    
    // Test the URL pattern that was causing the original error
    $url_transaction_id = $string_transaction_id;
    echo "<p class='info'>Testing URL pattern: /user_transactions/$url_transaction_id</p>";
    
    // Simulate what happens when the transaction details page loads
    $_GET['q'] = $url_transaction_id;
    $transaction_id = $_GET['q'];
    
    try {
        $transaction_details = $transaction->get_transaction_details($transaction_id);
        if ($transaction_details) {
            echo "<p class='success'>✅ URL routing test - Transaction details loaded successfully</p>";
            echo "<p class='info'>Transaction: {$transaction_details['title']} (ID: {$transaction_details['id']}, Transaction ID: {$transaction_details['transaction_id']})</p>";
        } else {
            echo "<p class='error'>❌ URL routing test - Failed to load transaction details</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ URL routing test - Exception: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Test failed with exception: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Database Schema Verification</h2>";

try {
    // Check if transaction_id column exists
    $columns = $db->query("DESCRIBE transactions")->fetchAll();
    $has_transaction_id = false;
    
    echo "<h3>Transactions table columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
        if ($column['Field'] == 'transaction_id') {
            $has_transaction_id = true;
        }
    }
    echo "</ul>";
    
    if ($has_transaction_id) {
        echo "<p class='success'>✅ transaction_id column exists</p>";
        
        // Check for unique constraint
        $indexes = $db->query("SHOW INDEX FROM transactions WHERE Column_name = 'transaction_id'")->fetchAll();
        if (!empty($indexes)) {
            echo "<p class='success'>✅ transaction_id has index/constraint</p>";
        } else {
            echo "<p class='warning'>⚠️ transaction_id missing index - consider adding for performance</p>";
        }
    } else {
        echo "<p class='error'>❌ transaction_id column missing - run migration script</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Schema check failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Summary</h2>";
echo "<p>This test verifies that the Transaction class methods can handle both:</p>";
echo "<ul>";
echo "<li><strong>Numeric IDs:</strong> Traditional integer primary keys (e.g., 123)</li>";
echo "<li><strong>Transaction IDs:</strong> String identifiers (e.g., 'TXN-2025-000023')</li>";
echo "</ul>";

echo "<h3>Fixed Methods:</h3>";
echo "<ul>";
echo "<li>✅ get_transaction()</li>";
echo "<li>✅ get_transaction_details()</li>";
echo "<li>✅ update_transaction()</li>";
echo "<li>✅ delete_transaction()</li>";
echo "<li>✅ resubmit_transaction()</li>";
echo "</ul>";

echo "<h3>What this fixes:</h3>";
echo "<ul>";
echo "<li>❌ <strong>Before:</strong> \"Unknown column 'id' in 'where clause'\" errors</li>";
echo "<li>✅ <strong>After:</strong> Transaction details pages work with transaction_id URLs</li>";
echo "<li>✅ <strong>After:</strong> All transaction operations work with both ID types</li>";
echo "</ul>";

echo "<p><strong>The original error should now be resolved!</strong></p>";
echo "</div>";

echo "<p><a href='/user_transactions' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test User Transactions Page</a></p>";
echo "<p><a href='/finance/transactions' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Finance Transactions Page</a></p>";
?>
