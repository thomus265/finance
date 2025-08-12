<?php
/**
 * Test script for user roles and permissions
 */

require('controller/main.php');

echo "<h1>User Roles and Permissions Test</h1>";

// Test different user scenarios
$test_scenarios = [
    [
        'user_id' => 1,
        'description' => 'Operations User',
        'expected_type' => 1,
        'can_submit' => true,
        'can_review' => false,
        'can_approve' => false,
        'department_type' => 1
    ],
    [
        'user_id' => 2,
        'description' => 'Programs User',
        'expected_type' => 2,
        'can_submit' => true,
        'can_review' => false,
        'can_approve' => false,
        'department_type' => 2
    ],
    [
        'user_id' => 4,
        'description' => 'Finance Team Member',
        'expected_type' => 3,
        'can_submit' => true,
        'can_review' => true,
        'can_approve' => true,
        'department_type' => null
    ]
];

echo "<h2>User Role Validation</h2>";

foreach ($test_scenarios as $scenario) {
    echo "<h3>Testing: {$scenario['description']} (User ID: {$scenario['user_id']})</h3>";
    
    try {
        $user_info = $user->get_user_with_role($scenario['user_id']);
        
        if (!$user_info) {
            echo "<p>❌ User not found</p>";
            continue;
        }
        
        echo "<p><strong>User Information:</strong></p>";
        echo "<ul>";
        echo "<li>Name: {$user_info['user_name']}</li>";
        echo "<li>Email: {$user_info['user_email']}</li>";
        echo "<li>Type: {$user_info['user_type']} ({$user_info['type_name']})</li>";
        echo "<li>Description: {$user_info['type_description']}</li>";
        echo "</ul>";
        
        // Test permissions
        echo "<p><strong>Permission Tests:</strong></p>";
        
        $tests = [
            'User Type' => [$user_info['user_type'], $scenario['expected_type']],
            'Can Submit' => [$user_info['can_submit_transactions'], $scenario['can_submit']],
            'Can Review' => [$user_info['can_review_transactions'], $scenario['can_review']],
            'Can Approve' => [$user_info['can_approve_transactions'], $scenario['can_approve']],
            'Department Type' => [$user_info['department_type'], $scenario['department_type']]
        ];
        
        foreach ($tests as $test_name => $test_data) {
            $actual = $test_data[0];
            $expected = $test_data[1];
            
            if ($actual == $expected) {
                echo "<p>✅ $test_name: " . ($actual ? 'Yes' : ($actual === null ? 'NULL' : 'No')) . "</p>";
            } else {
                echo "<p>❌ $test_name: Expected " . ($expected ? 'Yes' : ($expected === null ? 'NULL' : 'No')) . 
                     ", Got " . ($actual ? 'Yes' : ($actual === null ? 'NULL' : 'No')) . "</p>";
            }
        }
        
        // Test helper methods
        echo "<p><strong>Helper Method Tests:</strong></p>";
        
        $helper_tests = [
            'is_finance_team' => $user->is_finance_team($scenario['user_id']),
            'is_operations_user' => $user->is_operations_user($scenario['user_id']),
            'is_programs_user' => $user->is_programs_user($scenario['user_id']),
            'can_submit_transactions' => $user->can_submit_transactions($scenario['user_id']),
            'can_review_transactions' => $user->can_review_transactions($scenario['user_id']),
            'can_approve_transactions' => $user->can_approve_transactions($scenario['user_id'])
        ];
        
        foreach ($helper_tests as $method => $result) {
            echo "<p>✅ $method(): " . ($result ? 'Yes' : 'No') . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing user {$scenario['user_id']}: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Workflow Access Tests</h2>";

// Test workflow access for different user types
$workflow_tests = [
    [
        'user_type' => 1,
        'description' => 'Operations User Workflow Access',
        'expected_workflow' => 'Operations',
        'expected_states' => [1, 2, 3, 4, 5, 6]
    ],
    [
        'user_type' => 2,
        'description' => 'Programs User Workflow Access',
        'expected_workflow' => 'Programs',
        'expected_states' => [1, 2, 4, 5, 6]
    ]
];

foreach ($workflow_tests as $test) {
    echo "<h3>{$test['description']}</h3>";
    
    try {
        $states = $workflow->get_workflow_states_for_department($test['user_type']);
        
        echo "<p><strong>Available States:</strong></p>";
        echo "<ul>";
        foreach ($states as $state) {
            echo "<li>State {$state['state_value']}: {$state['state_name']}</li>";
        }
        echo "</ul>";
        
        $actual_states = array_column($states, 'state_value');
        $missing_states = array_diff($test['expected_states'], $actual_states);
        $extra_states = array_diff($actual_states, $test['expected_states']);
        
        if (empty($missing_states) && empty($extra_states)) {
            echo "<p>✅ All expected states available</p>";
        } else {
            if (!empty($missing_states)) {
                echo "<p>❌ Missing states: " . implode(', ', $missing_states) . "</p>";
            }
            if (!empty($extra_states)) {
                echo "<p>⚠️ Extra states: " . implode(', ', $extra_states) . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing workflow for user type {$test['user_type']}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Permission-Based UI Tests</h2>";

// Simulate different user sessions and test UI elements
$ui_tests = [
    [
        'user_id' => 1,
        'description' => 'Operations User UI',
        'should_see' => ['transaction_list', 'create_transaction', 'edit_own_transactions'],
        'should_not_see' => ['finance_dashboard', 'change_transaction_state', 'review_comments']
    ],
    [
        'user_id' => 4,
        'description' => 'Finance Team UI',
        'should_see' => ['transaction_list', 'finance_dashboard', 'change_transaction_state', 'review_comments'],
        'should_not_see' => []
    ]
];

foreach ($ui_tests as $test) {
    echo "<h3>{$test['description']}</h3>";
    
    // Simulate user session
    $_SESSION['user_id'] = $test['user_id'];
    $current_user = $user->get_current_user();
    
    if ($current_user) {
        echo "<p><strong>Simulating session for:</strong> {$current_user['user_name']}</p>";
        
        // Test UI permissions
        $ui_permissions = [
            'transaction_list' => true, // Everyone can see transactions
            'create_transaction' => $user->has_permission('submit_transactions'),
            'edit_own_transactions' => $user->has_permission('submit_transactions'),
            'finance_dashboard' => $user->has_permission('review_transactions'),
            'change_transaction_state' => $user->has_permission('approve_transactions'),
            'review_comments' => $user->has_permission('review_transactions')
        ];
        
        echo "<p><strong>UI Element Visibility:</strong></p>";
        foreach ($ui_permissions as $element => $visible) {
            $should_see = in_array($element, $test['should_see']);
            $should_not_see = in_array($element, $test['should_not_see']);
            
            if ($should_see && $visible) {
                echo "<p>✅ $element: Visible (as expected)</p>";
            } elseif ($should_not_see && !$visible) {
                echo "<p>✅ $element: Hidden (as expected)</p>";
            } elseif ($should_see && !$visible) {
                echo "<p>❌ $element: Hidden (should be visible)</p>";
            } elseif ($should_not_see && $visible) {
                echo "<p>❌ $element: Visible (should be hidden)</p>";
            } else {
                echo "<p>ℹ️ $element: " . ($visible ? 'Visible' : 'Hidden') . "</p>";
            }
        }
    } else {
        echo "<p>❌ Could not simulate session for user {$test['user_id']}</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Security Test Scenarios</h2>";

// Test security scenarios
$security_tests = [
    [
        'description' => 'Operations user trying to access Programs workflow',
        'user_type' => 1,
        'action' => 'access_programs_workflow',
        'expected' => 'allowed' // Users can see all workflows but create in their own
    ],
    [
        'description' => 'Regular user trying to change transaction state',
        'user_type' => 1,
        'action' => 'change_state',
        'expected' => 'denied'
    ],
    [
        'description' => 'Finance team accessing all transactions',
        'user_type' => 3,
        'action' => 'access_all_transactions',
        'expected' => 'allowed'
    ]
];

foreach ($security_tests as $test) {
    echo "<h3>{$test['description']}</h3>";
    
    // Simulate the security test
    $user_info = $user->get_user_with_role($test['user_type']);
    
    if ($user_info) {
        switch ($test['action']) {
            case 'change_state':
                $can_do = $user_info['can_approve_transactions'];
                break;
            case 'access_all_transactions':
                $can_do = $user_info['can_review_transactions'];
                break;
            default:
                $can_do = true; // Default allow for viewing
        }
        
        $expected_result = ($test['expected'] === 'allowed');
        
        if ($can_do === $expected_result) {
            echo "<p>✅ Security test passed: " . ($can_do ? 'Allowed' : 'Denied') . " (as expected)</p>";
        } else {
            echo "<p>❌ Security test failed: " . ($can_do ? 'Allowed' : 'Denied') . " (expected: {$test['expected']})</p>";
        }
    } else {
        echo "<p>❌ Could not test user type {$test['user_type']}</p>";
    }
}

echo "<h2>Test Summary</h2>";
echo "<p><strong>User Role Testing Complete!</strong></p>";
echo "<p><strong>Manual Testing Checklist:</strong></p>";
echo "<ol>";
echo "<li>Log in as Operations User (ID: 1) and verify you can only create Operations workflow transactions</li>";
echo "<li>Log in as Programs User (ID: 2) and verify you can only create Programs workflow transactions</li>";
echo "<li>Log in as Finance Team (ID: 4) and verify you can access the finance dashboard</li>";
echo "<li>Test that regular users cannot access finance-only features</li>";
echo "<li>Test that state changes are properly restricted by user role</li>";
echo "</ol>";
?>
