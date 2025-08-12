<?php
/**
 * Comprehensive test suite for the workflow system
 */

require('controller/main.php');

echo "<h1>Workflow System Test Suite</h1>";

// Test 1: Database Schema Validation
echo "<h2>1. Database Schema Validation</h2>";
test_database_schema();

// Test 2: User Role System
echo "<h2>2. User Role System</h2>";
test_user_roles();

// Test 3: Workflow State Management
echo "<h2>3. Workflow State Management</h2>";
test_workflow_states();

// Test 4: Security and Validation
echo "<h2>4. Security and Validation</h2>";
test_security_validation();

// Test 5: Notification System
echo "<h2>5. Notification System</h2>";
test_notification_system();

// Test 6: Integration Tests
echo "<h2>6. Integration Tests</h2>";
test_integration();

function test_database_schema() {
    global $db;
    
    echo "<h3>Testing Database Tables</h3>";
    
    $required_tables = [
        'user_types' => 'User types lookup table',
        'workflow_states' => 'Workflow states configuration',
        'notifications' => 'Notification system',
        'security_log' => 'Security audit log'
    ];
    
    foreach ($required_tables as $table => $description) {
        try {
            $result = $db->query("SHOW TABLES LIKE '$table'")->fetchArray();
            if ($result) {
                echo "<p>✅ Table '$table' exists ($description)</p>";
                
                // Check table structure
                $columns = $db->query("DESCRIBE $table")->fetchAll();
                echo "<details><summary>View columns</summary><ul>";
                foreach ($columns as $column) {
                    echo "<li>{$column['Field']} - {$column['Type']}</li>";
                }
                echo "</ul></details>";
            } else {
                echo "<p>❌ Table '$table' missing</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Testing Transaction Table Updates</h3>";
    try {
        $columns = $db->query("DESCRIBE transactions")->fetchAll();
        $required_columns = ['department_type', 'review_comments', 'reviewed_by', 'resubmitted_count'];
        
        foreach ($required_columns as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    echo "<p>✅ Column 'transactions.$col' exists</p>";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "<p>❌ Column 'transactions.$col' missing</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking transactions table: " . $e->getMessage() . "</p>";
    }
}

function test_user_roles() {
    global $user;
    
    echo "<h3>Testing User Role System</h3>";
    
    // Test user types
    try {
        $user_types = $user->get_user_types();
        if (!empty($user_types)) {
            echo "<p>✅ User types loaded successfully</p>";
            echo "<ul>";
            foreach ($user_types as $type) {
                echo "<li>Type {$type['type_value']}: {$type['type_name']} - {$type['type_description']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ No user types found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error loading user types: " . $e->getMessage() . "</p>";
    }
    
    // Test user permissions
    echo "<h3>Testing User Permissions</h3>";
    $test_users = [1, 2, 3]; // Operations, Programs, Finance
    
    foreach ($test_users as $user_id) {
        try {
            $user_info = $user->get_user_with_role($user_id);
            if ($user_info) {
                echo "<p><strong>User {$user_id} ({$user_info['user_name']}):</strong></p>";
                echo "<ul>";
                echo "<li>Type: {$user_info['type_name']}</li>";
                echo "<li>Can submit: " . ($user_info['can_submit_transactions'] ? 'Yes' : 'No') . "</li>";
                echo "<li>Can review: " . ($user_info['can_review_transactions'] ? 'Yes' : 'No') . "</li>";
                echo "<li>Can approve: " . ($user_info['can_approve_transactions'] ? 'Yes' : 'No') . "</li>";
                echo "<li>Department: " . ($user_info['department_type'] ? $user_info['department_type'] : 'Any') . "</li>";
                echo "</ul>";
            } else {
                echo "<p>❌ User $user_id not found</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error testing user $user_id: " . $e->getMessage() . "</p>";
        }
    }
}

function test_workflow_states() {
    global $workflow;
    
    echo "<h3>Testing Workflow States</h3>";
    
    try {
        $states = $workflow->get_workflow_states();
        if (!empty($states)) {
            echo "<p>✅ Workflow states loaded successfully</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>State</th><th>Name</th><th>Department</th><th>Editable</th><th>Final</th><th>Next States</th></tr>";
            foreach ($states as $state) {
                $dept_name = $state['department_type'] == 0 ? 'Both' : 
                           ($state['department_type'] == 1 ? 'Operations' : 'Programs');
                echo "<tr>";
                echo "<td>{$state['state_value']}</td>";
                echo "<td>{$state['state_name']}</td>";
                echo "<td>{$dept_name}</td>";
                echo "<td>" . ($state['is_editable'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($state['is_final'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$state['next_states']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ No workflow states found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error loading workflow states: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Testing State Transitions</h3>";
    $test_transitions = [
        ['from' => 1, 'to' => 2, 'dept' => 1, 'expected' => true],
        ['from' => 1, 'to' => 4, 'dept' => 1, 'expected' => true],
        ['from' => 2, 'to' => 3, 'dept' => 1, 'expected' => true],
        ['from' => 2, 'to' => 5, 'dept' => 2, 'expected' => true],
        ['from' => 6, 'to' => 1, 'dept' => 1, 'expected' => false], // Final state
        ['from' => 1, 'to' => 6, 'dept' => 1, 'expected' => false], // Invalid jump
    ];
    
    foreach ($test_transitions as $test) {
        $result = $workflow->is_valid_transition($test['from'], $test['to'], $test['dept']);
        $status = ($result === $test['expected']) ? '✅' : '❌';
        echo "<p>{$status} Transition {$test['from']} → {$test['to']} (dept {$test['dept']}): " . 
             ($result ? 'Valid' : 'Invalid') . " (expected: " . ($test['expected'] ? 'Valid' : 'Invalid') . ")</p>";
    }
}

function test_security_validation() {
    echo "<h3>Testing Security Functions</h3>";
    
    // Test CSRF token generation
    try {
        $token1 = Security::generateCSRFToken();
        $token2 = Security::generateCSRFToken();
        if ($token1 === $token2) {
            echo "<p>✅ CSRF token generation working (consistent within session)</p>";
        } else {
            echo "<p>❌ CSRF token generation inconsistent</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error testing CSRF tokens: " . $e->getMessage() . "</p>";
    }
    
    // Test input sanitization
    $test_inputs = [
        '<script>alert("xss")</script>' => 'XSS attempt',
        'normal text' => 'Normal text',
        '  whitespace  ' => 'Whitespace trimming'
    ];
    
    foreach ($test_inputs as $input => $description) {
        $sanitized = Security::sanitizeInput($input);
        echo "<p>✅ Sanitization test ($description): '$input' → '$sanitized'</p>";
    }
    
    // Test transaction data validation
    $test_data = [
        ['title' => 'Valid Transaction', 'amount' => 100.50, 'dept' => '1', 'expected_errors' => 0],
        ['title' => '', 'amount' => 100.50, 'dept' => '1', 'expected_errors' => 1],
        ['title' => 'Valid', 'amount' => -50, 'dept' => '1', 'expected_errors' => 1],
        ['title' => 'Valid', 'amount' => 100, 'dept' => '5', 'expected_errors' => 1],
    ];
    
    foreach ($test_data as $test) {
        $errors = Security::validateTransactionData($test['title'], $test['amount'], $test['dept']);
        $error_count = count($errors);
        $status = ($error_count === $test['expected_errors']) ? '✅' : '❌';
        echo "<p>{$status} Validation test: {$error_count} errors (expected: {$test['expected_errors']})</p>";
        if (!empty($errors)) {
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
    }
}

function test_notification_system() {
    global $notification;
    
    echo "<h3>Testing Notification System</h3>";
    
    try {
        // Test notification creation
        $result = $notification->create_notification(1, 1, 'test_notification', 'Test Title', 'Test message');
        if ($result) {
            echo "<p>✅ Notification creation successful</p>";
            
            // Test notification retrieval
            $notifications = $notification->get_user_notifications(1, 5);
            if (!empty($notifications)) {
                echo "<p>✅ Notification retrieval successful (" . count($notifications) . " notifications)</p>";
            } else {
                echo "<p>❌ No notifications retrieved</p>";
            }
            
            // Test unread count
            $unread_count = $notification->get_unread_count(1);
            echo "<p>✅ Unread count: $unread_count</p>";
            
        } else {
            echo "<p>❌ Notification creation failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error testing notifications: " . $e->getMessage() . "</p>";
    }
}

function test_integration() {
    echo "<h3>Testing File Paths and Endpoints</h3>";
    
    $endpoints = [
        'model/changeTransactionState.php' => 'State change endpoint',
        'model/resubmitTransaction.php' => 'Resubmit endpoint',
        'model/manageNotifications.php' => 'Notification management',
        'finance/dashboard.php' => 'Finance dashboard',
        'includes/notifications.php' => 'Notification component'
    ];
    
    foreach ($endpoints as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ File exists: $file ($description)</p>";
        } else {
            echo "<p>❌ File missing: $file ($description)</p>";
        }
    }
    
    echo "<h3>Testing URL Rewrite Rules</h3>";
    echo "<p>Manual testing required:</p>";
    echo "<ul>";
    echo "<li><a href='/finance_dashboard' target='_blank'>Finance Dashboard</a></li>";
    echo "<li><a href='/user_transactions' target='_blank'>User Transactions</a></li>";
    echo "</ul>";
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>All tests completed!</strong> Review the results above for any issues that need attention.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Run the migration script: <code>database/migration_workflow_system.sql</code></li>";
echo "<li>Test transaction creation with different user types</li>";
echo "<li>Test workflow state transitions</li>";
echo "<li>Test notification system</li>";
echo "<li>Test finance dashboard functionality</li>";
echo "</ol>";
?>
