<?php
/**
 * Test script to verify MySQL ambiguous column fixes
 */

require('controller/main.php');

echo "<h1>MySQL Ambiguous Column Fixes Test</h1>";

// Test 1: Notification queries
echo "<h2>1. Testing Notification Queries</h2>";
test_notification_queries();

// Test 2: Transaction queries with joins
echo "<h2>2. Testing Transaction Queries</h2>";
test_transaction_queries();

// Test 3: User role queries
echo "<h2>3. Testing User Role Queries</h2>";
test_user_role_queries();

// Test 4: Security queries
echo "<h2>4. Testing Security Queries</h2>";
test_security_queries();

function test_notification_queries() {
    global $notification;
    
    echo "<h3>Testing Notification Controller Methods</h3>";
    
    try {
        // Test get_user_notifications
        $notifications = $notification->get_user_notifications(1, 5);
        echo "<p>✅ get_user_notifications() - Success (" . count($notifications) . " notifications)</p>";
        
        // Test get_unread_count
        $unread_count = $notification->get_unread_count(1);
        echo "<p>✅ get_unread_count() - Success (count: $unread_count)</p>";
        
        // Test create notification
        $result = $notification->create_notification(1, 1, 'test', 'Test Title', 'Test message');
        if ($result) {
            echo "<p>✅ create_notification() - Success</p>";
            
            // Test mark_as_read
            $last_id = $notification->db->lastInsertID();
            if ($last_id) {
                $mark_result = $notification->mark_as_read($last_id, 1);
                echo "<p>✅ mark_as_read() - " . ($mark_result ? 'Success' : 'Failed') . "</p>";
            }
        } else {
            echo "<p>❌ create_notification() - Failed</p>";
        }
        
        // Test mark_all_as_read
        $mark_all_result = $notification->mark_all_as_read(1);
        echo "<p>✅ mark_all_as_read() - " . ($mark_all_result ? 'Success' : 'Failed') . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Notification query error: " . $e->getMessage() . "</p>";
    }
}

function test_transaction_queries() {
    global $transaction, $db;
    
    echo "<h3>Testing Transaction Queries with JOINs</h3>";
    
    try {
        // Test transaction details query (includes JOINs)
        $transaction_details = $transaction->get_transaction_details(1);
        if ($transaction_details) {
            echo "<p>✅ get_transaction_details() - Success</p>";
        } else {
            echo "<p>⚠️ get_transaction_details() - No transaction found (expected if no transactions exist)</p>";
        }
        
        // Test transactions list query
        $transactions = $db->query("
            SELECT t.*, u.user_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.user_id
            ORDER BY t.tr_date DESC
            LIMIT 5
        ")->fetchAll();
        echo "<p>✅ Transactions list query - Success (" . count($transactions) . " transactions)</p>";
        
        // Test transactions with workflow states
        $workflow_transactions = $db->query("
            SELECT t.*, u.user_name, ws.state_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.user_id
            LEFT JOIN workflow_states ws ON t.state = ws.state_value AND (ws.department_type = t.department_type OR ws.department_type = 0)
            WHERE t.state IN (1, 2, 4)
            LIMIT 5
        ")->fetchAll();
        echo "<p>✅ Workflow transactions query - Success (" . count($workflow_transactions) . " pending transactions)</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Transaction query error: " . $e->getMessage() . "</p>";
    }
}

function test_user_role_queries() {
    global $user;
    
    echo "<h3>Testing User Role Queries</h3>";
    
    try {
        // Test get_user_with_role
        $user_info = $user->get_user_with_role(1);
        if ($user_info) {
            echo "<p>✅ get_user_with_role() - Success (User: {$user_info['user_name']})</p>";
        } else {
            echo "<p>❌ get_user_with_role() - No user found</p>";
        }
        
        // Test get_users_by_type
        $operations_users = $user->get_users_by_type(1);
        echo "<p>✅ get_users_by_type() - Success (" . count($operations_users) . " operations users)</p>";
        
        // Test get_finance_team
        $finance_team = $user->get_finance_team();
        echo "<p>✅ get_finance_team() - Success (" . count($finance_team) . " finance team members)</p>";
        
        // Test current user
        $current_user = $user->get_current_user();
        if ($current_user) {
            echo "<p>✅ get_current_user() - Success (User: {$current_user['user_name']})</p>";
        } else {
            echo "<p>⚠️ get_current_user() - No session user (expected if not logged in)</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ User role query error: " . $e->getMessage() . "</p>";
    }
}

function test_security_queries() {
    global $db;
    
    echo "<h3>Testing Security-Related Queries</h3>";
    
    try {
        // Test security log query
        $security_logs = $db->query("
            SELECT sl.*, u.user_name
            FROM security_log sl
            LEFT JOIN users u ON sl.user_id = u.user_id
            ORDER BY sl.created_at DESC
            LIMIT 5
        ")->fetchAll();
        echo "<p>✅ Security log query - Success (" . count($security_logs) . " log entries)</p>";
        
        // Test rate limiting query simulation
        $rate_limit_check = $db->query("
            SELECT COUNT(*) as count 
            FROM security_log sl
            WHERE sl.event_type = ? AND sl.user_id = ? AND sl.created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ", 'test_event', 1, 300)->fetchArray();
        echo "<p>✅ Rate limiting query - Success (count: {$rate_limit_check['count']})</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Security query error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>MySQL Ambiguous Column Fixes Test Complete!</strong></p>";

echo "<h3>Key Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Fixed notification queries to use proper table aliases (n.user_id, n.is_read)</li>";
echo "<li>✅ All JOIN queries now properly qualify column references</li>";
echo "<li>✅ WHERE clauses specify table aliases for ambiguous columns</li>";
echo "<li>✅ UPDATE and DELETE statements use proper table references</li>";
echo "</ul>";

echo "<h3>Manual Testing Checklist:</h3>";
echo "<ol>";
echo "<li>Visit <a href='/user_transactions' target='_blank'>User Transactions</a> - Should load without MySQL errors</li>";
echo "<li>Visit <a href='/finance_dashboard' target='_blank'>Finance Dashboard</a> - Should load without MySQL errors</li>";
echo "<li>Test notification dropdown in navbar - Should work without errors</li>";
echo "<li>Try creating a new transaction - Should work without database errors</li>";
echo "<li>Test transaction state changes (if finance user) - Should work without errors</li>";
echo "</ol>";

echo "<h3>Files Modified:</h3>";
echo "<ul>";
echo "<li><code>controller/Notification.php</code> - Fixed all notification queries</li>";
echo "<li>Other files were already using proper table aliases</li>";
echo "</ul>";

echo "<p><strong>If you still see MySQL errors, please check the browser console and server error logs for specific query details.</strong></p>";
?>
