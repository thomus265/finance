<?php
/**
 * Test script to verify transaction system functionality
 */

require('controller/main.php');

echo "<h2>Transaction System Test</h2>";

// Test 1: Check if users exist
echo "<h3>1. Testing User Existence</h3>";
try {
    $users = $db->query("SELECT user_id, user_name FROM users ORDER BY user_id")->fetchAll();
    echo "<p>✅ Found " . count($users) . " users:</p>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['user_id']} - Name: {$user['user_name']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Error fetching users: " . $e->getMessage() . "</p>";
}

// Test 2: Check transaction creation
echo "<h3>2. Testing Transaction Creation</h3>";
try {
    $transaction_id = $transaction->generate_transaction_id();
    echo "<p>✅ Generated transaction ID: {$transaction_id}</p>";
    
    // Test adding a transaction
    $result = $transaction->add_transaction("Test Transaction", 100.00, 1, 1, 1);
    if ($result) {
        $last_id = $transaction->get_last_id();
        echo "<p>✅ Transaction created successfully with ID: {$last_id}</p>";
        
        // Get the transaction details
        $details = $transaction->get_transaction_details($last_id);
        if ($details) {
            echo "<p>✅ Transaction details retrieved: {$details['transaction_id']}</p>";
        } else {
            echo "<p>❌ Failed to retrieve transaction details</p>";
        }
    } else {
        echo "<p>❌ Failed to create transaction</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error testing transaction creation: " . $e->getMessage() . "</p>";
}

// Test 3: Check database schema
echo "<h3>3. Testing Database Schema</h3>";
try {
    // Check if transaction_attachments table exists
    $tables = $db->query("SHOW TABLES LIKE 'transaction_attachments'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p>✅ transaction_attachments table exists</p>";
        
        // Check table structure
        $columns = $db->query("DESCRIBE transaction_attachments")->fetchAll();
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ transaction_attachments table does not exist</p>";
    }
    
    // Check if transactions table has transaction_id column
    $columns = $db->query("SHOW COLUMNS FROM transactions LIKE 'transaction_id'")->fetchArray();
    if ($columns) {
        echo "<p>✅ transactions table has transaction_id column</p>";
    } else {
        echo "<p>❌ transactions table missing transaction_id column</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking database schema: " . $e->getMessage() . "</p>";
}

// Test 4: Check file upload directory
echo "<h3>4. Testing File Upload Directory</h3>";
$upload_dir = 'public/attachments/' . date('Y-m-d') . '/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>✅ Created upload directory: {$upload_dir}</p>";
    } else {
        echo "<p>❌ Failed to create upload directory: {$upload_dir}</p>";
    }
} else {
    echo "<p>✅ Upload directory exists: {$upload_dir}</p>";
}

if (is_writable($upload_dir)) {
    echo "<p>✅ Upload directory is writable</p>";
} else {
    echo "<p>❌ Upload directory is not writable</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p><a href='user/transactions.php'>Go to Transactions</a></p>";
?>
