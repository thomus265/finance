<?php
/**
 * Test script to verify transaction editing restrictions work correctly
 */

require('controller/main.php');

echo "<h2>Transaction Editing Restrictions Test</h2>";

// Test 1: Check transaction states and their editing permissions
echo "<h3>1. Testing Transaction State Editing Rules</h3>";

$states = [
    '0' => ['name' => 'Draft', 'editable' => true],
    '1' => ['name' => 'Submitted', 'editable' => true],
    '2' => ['name' => 'In Progress', 'editable' => true],
    '3' => ['name' => 'Paid', 'editable' => false],
    '4' => ['name' => 'Sent Back', 'editable' => true],
    '5' => ['name' => 'Completed', 'editable' => false]
];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>State</th><th>Name</th><th>Editable</th><th>Status</th></tr>";

foreach ($states as $state_id => $state_info) {
    $editable_text = $state_info['editable'] ? 'Yes' : 'No';
    $status_icon = $state_info['editable'] ? '✅' : '🔒';
    
    echo "<tr>";
    echo "<td>{$state_id}</td>";
    echo "<td>{$state_info['name']}</td>";
    echo "<td>{$editable_text}</td>";
    echo "<td>{$status_icon}</td>";
    echo "</tr>";
}

echo "</table>";

// Test 2: Check existing transactions and their editing status
echo "<h3>2. Testing Existing Transactions</h3>";

try {
    $transactions = $db->query("
        SELECT id, transaction_id, title, state, amount 
        FROM transactions 
        ORDER BY id DESC 
        LIMIT 10
    ")->fetchAll();
    
    if (count($transactions) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Transaction ID</th><th>Title</th><th>State</th><th>Editing Status</th><th>Test Link</th></tr>";
        
        foreach ($transactions as $trans) {
            $state_name = '';
            $editable = true;
            
            switch($trans['state']) {
                case '0':
                    $state_name = 'Draft';
                    break;
                case '1':
                    $state_name = 'Submitted';
                    break;
                case '2':
                    $state_name = 'In Progress';
                    break;
                case '3':
                    $state_name = 'Paid';
                    $editable = false;
                    break;
                case '4':
                    $state_name = 'Sent Back';
                    break;
                case '5':
                    $state_name = 'Completed';
                    $editable = false;
                    break;
                default:
                    $state_name = 'Unknown';
            }
            
            $editing_status = $editable ? '✅ Editable' : '🔒 Read Only';
            $test_link = "<a href='/user_transactions/{$trans['id']}' target='_blank'>Test</a>";
            
            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['transaction_id']}</td>";
            echo "<td>" . htmlspecialchars($trans['title']) . "</td>";
            echo "<td>{$trans['state']} ({$state_name})</td>";
            echo "<td>{$editing_status}</td>";
            echo "<td>{$test_link}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No transactions found for testing.</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error fetching transactions: " . $e->getMessage() . "</p>";
}

// Test 3: Create test transactions with different states
echo "<h3>3. Creating Test Transactions</h3>";

try {
    // Create a paid transaction (read-only)
    $paid_transaction_id = $transaction->generate_transaction_id();
    $result = $db->query(
        "INSERT INTO transactions (transaction_id, title, amount, department, state, user_id, tr_date) VALUES (?, ?, ?, ?, ?, ?, current_timestamp())",
        $paid_transaction_id, 'Test Paid Transaction (Read Only)', 500.00, 1, 3, 1
    );
    
    if ($result) {
        $paid_id = $db->lastInsertID();
        echo "<p>✅ Created paid transaction (read-only): ID {$paid_id}, Transaction ID: {$paid_transaction_id}</p>";
        echo "<p><a href='/user_transactions/{$paid_id}' target='_blank'>Test Paid Transaction (Should be Read Only)</a></p>";
    }
    
    // Create a sent back transaction (editable)
    $sent_back_transaction_id = $transaction->generate_transaction_id();
    $result = $db->query(
        "INSERT INTO transactions (transaction_id, title, amount, department, state, user_id, tr_date) VALUES (?, ?, ?, ?, ?, ?, current_timestamp())",
        $sent_back_transaction_id, 'Test Sent Back Transaction (Editable)', 300.00, 1, 4, 1
    );
    
    if ($result) {
        $sent_back_id = $db->lastInsertID();
        echo "<p>✅ Created sent back transaction (editable): ID {$sent_back_id}, Transaction ID: {$sent_back_transaction_id}</p>";
        echo "<p><a href='/user_transactions/{$sent_back_id}' target='_blank'>Test Sent Back Transaction (Should be Editable)</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error creating test transactions: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Instructions</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>Manual Testing Steps:</h4>";
echo "<ol>";
echo "<li><strong>Test Read-Only Transactions (State = 3 'Paid'):</strong>";
echo "<ul>";
echo "<li>Click on a paid transaction link above</li>";
echo "<li>Verify 'Read Only' badge is shown in header</li>";
echo "<li>Verify 'Edit Transaction' button is replaced with 'Read Only' button</li>";
echo "<li>Verify alert message explains why editing is disabled</li>";
echo "<li>Verify 'Add Files' button is replaced with 'Read Only' badge</li>";
echo "<li>Verify delete buttons on attachments are disabled</li>";
echo "<li>Try clicking disabled buttons - should show appropriate messages</li>";
echo "</ul></li>";

echo "<li><strong>Test Editable Transactions (State = 4 'Sent Back'):</strong>";
echo "<ul>";
echo "<li>Click on a sent back transaction link above</li>";
echo "<li>Verify 'Edit Transaction' button is available</li>";
echo "<li>Verify you can click 'Edit Transaction' and modify fields</li>";
echo "<li>Verify 'Add Files' button is available</li>";
echo "<li>Verify delete buttons on attachments are enabled</li>";
echo "<li>Test file upload and deletion functionality</li>";
echo "</ul></li>";

echo "<li><strong>Test Transaction List View:</strong>";
echo "<ul>";
echo "<li>Go to <a href='user/transactions.php' target='_blank'>Transactions List</a></li>";
echo "<li>Verify paid transactions show lock icon in status column</li>";
echo "<li>Verify other transactions don't show lock icon</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<h3>Expected Behavior Summary</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Transaction State</th><th>Edit Button</th><th>File Upload</th><th>File Delete</th><th>Visual Indicators</th></tr>";
echo "<tr><td>Draft (0)</td><td>✅ Enabled</td><td>✅ Enabled</td><td>✅ Enabled</td><td>None</td></tr>";
echo "<tr><td>Submitted (1)</td><td>✅ Enabled</td><td>✅ Enabled</td><td>✅ Enabled</td><td>None</td></tr>";
echo "<tr><td>In Progress (2)</td><td>✅ Enabled</td><td>✅ Enabled</td><td>✅ Enabled</td><td>None</td></tr>";
echo "<tr><td>Paid (3)</td><td>🔒 Disabled</td><td>🔒 Disabled</td><td>🔒 Disabled</td><td>Lock icons, Read Only badges</td></tr>";
echo "<tr><td>Sent Back (4)</td><td>✅ Enabled</td><td>✅ Enabled</td><td>✅ Enabled</td><td>None</td></tr>";
echo "</table>";

echo "<p><strong>Note:</strong> All restrictions are enforced both in the UI (buttons disabled/hidden) and in JavaScript (with user-friendly messages).</p>";
?>
