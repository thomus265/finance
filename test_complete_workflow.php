<?php
/**
 * Complete workflow integration test
 * This script simulates a full transaction lifecycle
 */

require('controller/main.php');

echo "<h1>Complete Workflow Integration Test</h1>";

// Test the complete workflow for both Operations and Programs departments
test_operations_workflow();
test_programs_workflow();
test_notification_flow();
test_security_scenarios();

function test_operations_workflow() {
    global $db, $transaction, $workflow, $notification, $user;
    
    echo "<h2>Operations Workflow Test</h2>";
    
    try {
        // Step 1: Create a new transaction (Operations user)
        echo "<h3>Step 1: Create Transaction (Operations User)</h3>";
        
        $transaction_id = $transaction->generate_transaction_id();
        $result = $db->query(
            "INSERT INTO transactions (transaction_id, title, amount, department, state, user_id, department_type, tr_date, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            $transaction_id, 'Test Operations Transaction', 500.00, 1, 1, 1, 1
        );
        
        if ($result) {
            $new_transaction_id = $db->lastInsertID();
            echo "<p>✅ Transaction created: ID $new_transaction_id, Transaction ID: $transaction_id</p>";
            
            // Step 2: Finance team reviews (State 1 → 2)
            echo "<h3>Step 2: Finance Review (State 1 → 2)</h3>";
            test_state_transition($new_transaction_id, 1, 2, 'Finance team starts review');
            
            // Step 3: Approve for processing (State 2 → 3) - Operations only
            echo "<h3>Step 3: Approve for Processing (State 2 → 3)</h3>";
            test_state_transition($new_transaction_id, 2, 3, 'Approved for processing');
            
            // Step 4: Send to payroll (State 3 → 5)
            echo "<h3>Step 4: Send to Payroll (State 3 → 5)</h3>";
            test_state_transition($new_transaction_id, 3, 5, 'Sent to payroll');
            
            // Step 5: Mark as paid (State 5 → 6)
            echo "<h3>Step 5: Mark as Paid (State 5 → 6)</h3>";
            test_state_transition($new_transaction_id, 5, 6, 'Transaction paid');
            
            // Verify final state
            $final_transaction = $transaction->get_transaction($new_transaction_id);
            if ($final_transaction && $final_transaction['state'] == '6') {
                echo "<p>✅ Operations workflow completed successfully</p>";
            } else {
                echo "<p>❌ Operations workflow did not complete properly</p>";
            }
            
        } else {
            echo "<p>❌ Failed to create test transaction</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in Operations workflow test: " . $e->getMessage() . "</p>";
    }
}

function test_programs_workflow() {
    global $db, $transaction, $workflow, $notification, $user;
    
    echo "<h2>Programs Workflow Test</h2>";
    
    try {
        // Step 1: Create a new transaction (Programs user)
        echo "<h3>Step 1: Create Transaction (Programs User)</h3>";
        
        $transaction_id = $transaction->generate_transaction_id();
        $result = $db->query(
            "INSERT INTO transactions (transaction_id, title, amount, department, state, user_id, department_type, tr_date, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            $transaction_id, 'Test Programs Transaction', 300.00, 2, 1, 2, 2
        );
        
        if ($result) {
            $new_transaction_id = $db->lastInsertID();
            echo "<p>✅ Transaction created: ID $new_transaction_id, Transaction ID: $transaction_id</p>";
            
            // Step 2: Finance team reviews (State 1 → 2)
            echo "<h3>Step 2: Finance Review (State 1 → 2)</h3>";
            test_state_transition($new_transaction_id, 1, 2, 'Finance team starts review');
            
            // Step 3: Approve directly to payroll (State 2 → 5) - Programs workflow
            echo "<h3>Step 3: Approve to Payroll (State 2 → 5)</h3>";
            test_state_transition($new_transaction_id, 2, 5, 'Approved and sent to payroll');
            
            // Step 4: Mark as paid (State 5 → 6)
            echo "<h3>Step 4: Mark as Paid (State 5 → 6)</h3>";
            test_state_transition($new_transaction_id, 5, 6, 'Transaction paid');
            
            // Verify final state
            $final_transaction = $transaction->get_transaction($new_transaction_id);
            if ($final_transaction && $final_transaction['state'] == '6') {
                echo "<p>✅ Programs workflow completed successfully</p>";
            } else {
                echo "<p>❌ Programs workflow did not complete properly</p>";
            }
            
        } else {
            echo "<p>❌ Failed to create test transaction</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in Programs workflow test: " . $e->getMessage() . "</p>";
    }
}

function test_state_transition($transaction_id, $from_state, $to_state, $description) {
    global $db, $workflow;
    
    try {
        // Update the transaction state
        $result = $db->query(
            "UPDATE transactions SET state = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?",
            $to_state, 4, $transaction_id // Finance user ID 4
        );
        
        if ($result) {
            // Log the transition
            $workflow->log_state_change($transaction_id, $from_state, $to_state, 4, $description);
            
            // Get workflow info
            $current_transaction = $db->query("SELECT * FROM transactions WHERE id = ?", $transaction_id)->fetchArray();
            $workflow_info = $workflow->get_workflow_display_info($to_state, $current_transaction['department_type']);
            
            echo "<p>✅ State transition successful: $from_state → $to_state ({$workflow_info['name']})</p>";
            echo "<p>   Description: $description</p>";
            
            return true;
        } else {
            echo "<p>❌ State transition failed: $from_state → $to_state</p>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in state transition: " . $e->getMessage() . "</p>";
        return false;
    }
}

function test_notification_flow() {
    global $notification, $user;
    
    echo "<h2>Notification System Test</h2>";
    
    try {
        // Test creating notifications for different scenarios
        echo "<h3>Testing Notification Creation</h3>";
        
        $test_notifications = [
            [
                'user_id' => 1,
                'type' => 'transaction_submitted',
                'title' => 'Test Submission Notification',
                'message' => 'Your transaction has been submitted for review'
            ],
            [
                'user_id' => 4,
                'type' => 'transaction_approved',
                'title' => 'Test Approval Notification',
                'message' => 'Transaction has been approved'
            ]
        ];
        
        foreach ($test_notifications as $notif) {
            $result = $notification->create_notification(
                $notif['user_id'], 
                1, // Test transaction ID
                $notif['type'], 
                $notif['title'], 
                $notif['message']
            );
            
            if ($result) {
                echo "<p>✅ Notification created for user {$notif['user_id']}: {$notif['title']}</p>";
            } else {
                echo "<p>❌ Failed to create notification for user {$notif['user_id']}</p>";
            }
        }
        
        // Test notification retrieval
        echo "<h3>Testing Notification Retrieval</h3>";
        
        $users_to_test = [1, 4]; // Operations user and Finance user
        
        foreach ($users_to_test as $user_id) {
            $notifications = $notification->get_user_notifications($user_id, 5);
            $unread_count = $notification->get_unread_count($user_id);
            
            echo "<p>✅ User $user_id has " . count($notifications) . " notifications ($unread_count unread)</p>";
            
            if (!empty($notifications)) {
                echo "<ul>";
                foreach ($notifications as $notif) {
                    $read_status = $notif['is_read'] ? 'Read' : 'Unread';
                    echo "<li>{$notif['title']} - $read_status</li>";
                }
                echo "</ul>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in notification test: " . $e->getMessage() . "</p>";
    }
}

function test_security_scenarios() {
    echo "<h2>Security Scenarios Test</h2>";
    
    // Test 1: Unauthorized state change
    echo "<h3>Test 1: Unauthorized State Change</h3>";
    test_unauthorized_access();
    
    // Test 2: Invalid state transitions
    echo "<h3>Test 2: Invalid State Transitions</h3>";
    test_invalid_transitions();
    
    // Test 3: Transaction access control
    echo "<h3>Test 3: Transaction Access Control</h3>";
    test_access_control();
}

function test_unauthorized_access() {
    global $workflow;
    
    // Test if regular user can make finance-only transitions
    $unauthorized_tests = [
        ['user_type' => 1, 'from' => 1, 'to' => 2, 'should_fail' => true],
        ['user_type' => 2, 'from' => 2, 'to' => 5, 'should_fail' => true],
        ['user_type' => 3, 'from' => 1, 'to' => 2, 'should_fail' => false]
    ];
    
    foreach ($unauthorized_tests as $test) {
        $validation = Security::validateStateTransition($test['from'], $test['to'], 1, $test['user_type']);
        
        if ($test['should_fail'] && !$validation['valid']) {
            echo "<p>✅ Unauthorized access properly blocked for user type {$test['user_type']}</p>";
        } elseif (!$test['should_fail'] && $validation['valid']) {
            echo "<p>✅ Authorized access allowed for user type {$test['user_type']}</p>";
        } else {
            echo "<p>❌ Security test failed for user type {$test['user_type']}: " . $validation['message'] . "</p>";
        }
    }
}

function test_invalid_transitions() {
    global $workflow;
    
    $invalid_transitions = [
        ['from' => 6, 'to' => 1, 'dept' => 1], // From final state
        ['from' => 1, 'to' => 6, 'dept' => 1], // Skip states
        ['from' => 2, 'to' => 3, 'dept' => 2], // Operations-only state in Programs workflow
    ];
    
    foreach ($invalid_transitions as $test) {
        $is_valid = $workflow->is_valid_transition($test['from'], $test['to'], $test['dept']);
        
        if (!$is_valid) {
            echo "<p>✅ Invalid transition properly blocked: {$test['from']} → {$test['to']}</p>";
        } else {
            echo "<p>❌ Invalid transition allowed: {$test['from']} → {$test['to']}</p>";
        }
    }
}

function test_access_control() {
    $access_tests = [
        ['transaction_id' => 1, 'user_id' => 1, 'user_type' => 1, 'should_allow' => true], // Own transaction
        ['transaction_id' => 1, 'user_id' => 2, 'user_type' => 2, 'should_allow' => false], // Other's transaction
        ['transaction_id' => 1, 'user_id' => 4, 'user_type' => 3, 'should_allow' => true], // Finance team
    ];
    
    foreach ($access_tests as $test) {
        $validation = Security::validateTransactionAccess($test['transaction_id'], $test['user_id'], $test['user_type']);
        
        if ($test['should_allow'] && $validation['valid']) {
            echo "<p>✅ Access properly allowed for user {$test['user_id']}</p>";
        } elseif (!$test['should_allow'] && !$validation['valid']) {
            echo "<p>✅ Access properly denied for user {$test['user_id']}</p>";
        } else {
            echo "<p>❌ Access control failed for user {$test['user_id']}: " . $validation['message'] . "</p>";
        }
    }
}

echo "<h2>Integration Test Summary</h2>";
echo "<p><strong>Complete workflow testing finished!</strong></p>";
echo "<p><strong>Manual Testing Checklist:</strong></p>";
echo "<ol>";
echo "<li>Create transactions as different user types and verify correct workflow assignment</li>";
echo "<li>Test state transitions through the finance dashboard</li>";
echo "<li>Verify notifications are sent and received correctly</li>";
echo "<li>Test the 'sent back' and 'resubmit' functionality</li>";
echo "<li>Verify read-only enforcement for paid transactions</li>";
echo "<li>Test file upload and deletion restrictions</li>";
echo "<li>Verify finance dashboard shows correct pending transactions</li>";
echo "<li>Test search and filtering functionality</li>";
echo "</ol>";

echo "<p><strong>Key URLs to Test:</strong></p>";
echo "<ul>";
echo "<li><a href='/user_transactions' target='_blank'>User Transactions</a></li>";
echo "<li><a href='/finance_dashboard' target='_blank'>Finance Dashboard</a></li>";
echo "<li><a href='/test_workflow_system.php' target='_blank'>Workflow System Test</a></li>";
echo "<li><a href='/test_user_roles.php' target='_blank'>User Roles Test</a></li>";
echo "</ul>";
?>
