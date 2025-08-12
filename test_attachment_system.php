<?php
/**
 * Test script to verify attachment system functionality
 */

require('controller/main.php');

echo "<h2>Attachment System Test</h2>";

// Test 1: Check upload directory
echo "<h3>1. Testing Upload Directory</h3>";
$base_dir = 'public/attachments/';
$today_dir = $base_dir . date('Y-m-d') . '/';

if (!is_dir($base_dir)) {
    if (mkdir($base_dir, 0755, true)) {
        echo "<p>✅ Created base attachments directory: {$base_dir}</p>";
    } else {
        echo "<p>❌ Failed to create base attachments directory: {$base_dir}</p>";
    }
} else {
    echo "<p>✅ Base attachments directory exists: {$base_dir}</p>";
}

if (!is_dir($today_dir)) {
    if (mkdir($today_dir, 0755, true)) {
        echo "<p>✅ Created today's directory: {$today_dir}</p>";
    } else {
        echo "<p>❌ Failed to create today's directory: {$today_dir}</p>";
    }
} else {
    echo "<p>✅ Today's directory exists: {$today_dir}</p>";
}

if (is_writable($today_dir)) {
    echo "<p>✅ Today's directory is writable</p>";
} else {
    echo "<p>❌ Today's directory is not writable</p>";
}

// Test 2: Check transaction_attachments table
echo "<h3>2. Testing Database Tables</h3>";
try {
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
} catch (Exception $e) {
    echo "<p>❌ Error checking transaction_attachments table: " . $e->getMessage() . "</p>";
}

// Test 3: Check if we can get transaction details
echo "<h3>3. Testing Transaction Details</h3>";
try {
    $transactions = $db->query("SELECT id, transaction_id, title FROM transactions LIMIT 1")->fetchAll();
    if (count($transactions) > 0) {
        $test_transaction = $transactions[0];
        echo "<p>✅ Found test transaction: ID {$test_transaction['id']}, Transaction ID: {$test_transaction['transaction_id']}</p>";
        
        // Try to get transaction details
        $details = $transaction->get_transaction_details($test_transaction['id']);
        if ($details) {
            echo "<p>✅ Successfully retrieved transaction details</p>";
            echo "<p>Attachments count: " . count($details['attachments']) . "</p>";
        } else {
            echo "<p>❌ Failed to retrieve transaction details</p>";
        }
    } else {
        echo "<p>❌ No transactions found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error testing transaction details: " . $e->getMessage() . "</p>";
}

// Test 4: Check file paths
echo "<h3>4. Testing File Paths</h3>";
$test_paths = [
    'model/uploadTransactionFiles.php',
    'model/manageAttachments.php',
    'model/updateTransaction.php'
];

foreach ($test_paths as $path) {
    if (file_exists($path)) {
        echo "<p>✅ File exists: {$path}</p>";
    } else {
        echo "<p>❌ File missing: {$path}</p>";
    }
}

echo "<h3>Test Complete</h3>";
echo "<p><a href='user/transactions.php'>Go to Transactions</a></p>";
echo "<p><strong>Note:</strong> Check the browser console and server error logs for detailed debugging information when testing attachment upload/delete functionality.</p>";
?>
