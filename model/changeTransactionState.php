<?php
require_once("../controller/main.php");
// Email notification is optional - only include if file exists
if (file_exists("../controller/EmailNotification.php")) {
    require_once("../controller/EmailNotification.php");
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate session
$user_id = Security::validateSession();

// CSRF protection
Security::requireCSRFToken();

$action = Security::sanitizeInput($_POST['action'] ?? 'change_state'); // Default to change_state
$transaction_id = Security::sanitizeInput($_POST['transaction_id'] ?? '');
$new_state = Security::sanitizeInput($_POST['new_state'] ?? '');
$review_comments = Security::sanitizeInput($_POST['review_comments'] ?? '');

// Debug logging
error_log("State change request - Action: $action, Transaction ID: $transaction_id, New State: $new_state");
error_log("POST data: " . json_encode($_POST));

if (empty($transaction_id) || $new_state === '') {
    Security::logSecurityEvent('invalid_state_change_request', $user_id, "Invalid parameters: action=$action, transaction_id=$transaction_id, new_state=$new_state");
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

// Rate limiting
if (!Security::checkRateLimit('state_change', $user_id, 20, 300)) {
    Security::logSecurityEvent('rate_limit_exceeded', $user_id, 'State change rate limit exceeded');
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

// Get current user
$current_user = $user->get_current_user();
if (!$current_user) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Get current transaction
    $current_transaction = $transaction->get_transaction($transaction_id);
    if (!$current_transaction) {
        Security::logSecurityEvent('transaction_not_found', $user_id, "Transaction ID: $transaction_id");
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    $current_state = $current_transaction['state'];
    $department_type = $current_transaction['department_type'];

    // Validate transaction access
    $access_validation = Security::validateTransactionAccess($transaction_id, $user_id, $current_user['user_type']);
    if (!$access_validation['valid']) {
        Security::logSecurityEvent('unauthorized_transaction_access', $user_id, "Transaction ID: $transaction_id, Reason: " . $access_validation['message']);
        echo json_encode(['success' => false, 'message' => $access_validation['message']]);
        exit;
    }

    // Validate state transition with security checks
    $transition_validation = Security::validateStateTransition($current_state, $new_state, $department_type, $current_user['user_type']);
    if (!$transition_validation['valid']) {
        Security::logSecurityEvent('invalid_state_transition', $user_id, "Transaction ID: $transaction_id, From: $current_state, To: $new_state, Reason: " . $transition_validation['message']);
        echo json_encode(['success' => false, 'message' => $transition_validation['message']]);
        exit;
    }

    // Prepare update data
    $update_data = [
        'state' => $new_state,
        'last_updated_by' => $current_user['user_id']
    ];

    // Add state-specific fields
    switch ($new_state) {
        case '2': // Under Review
            $update_data['reviewed_by'] = $current_user['user_id'];
            $update_data['reviewed_at'] = date('Y-m-d H:i:s');
            break;
        case '3': // Processing
            $update_data['processed_at'] = date('Y-m-d H:i:s');
            break;
        case '4': // Sent Back
            $update_data['review_comments'] = $review_comments;
            $update_data['reviewed_by'] = $current_user['user_id'];
            $update_data['reviewed_at'] = date('Y-m-d H:i:s');
            break;
        case '5': // Sent to Payroll
            $update_data['sent_to_payroll_at'] = date('Y-m-d H:i:s');
            break;
        case '6': // Paid
            $update_data['paid_at'] = date('Y-m-d H:i:s');
            break;
    }

    // Build update query
    $set_clauses = [];
    $params = [];
    foreach ($update_data as $field => $value) {
        $set_clauses[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $transaction_id;

    $sql = "UPDATE transactions SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    error_log("State change SQL: $sql");
    error_log("State change params: " . json_encode($params));

    $result = $db->query($sql, ...$params);
    error_log("State change result: " . ($result ? 'success' : 'failed'));

    if ($result) {
        // Log the state change in audit trail
        $workflow->log_state_change(
            $transaction_id,
            $current_state,
            $new_state,
            $current_user['user_id'],
            $review_comments
        );

        // Log security event
        Security::logSecurityEvent('transaction_state_changed', $user_id, "Transaction ID: $transaction_id, From: $current_state, To: $new_state");

        // Create notification for transaction owner if state changed by finance team
        if ($current_user['user_type'] == 3 && $current_transaction['user_id'] != $current_user['user_id']) {
            create_state_change_notification($current_transaction['user_id'], $transaction_id, $new_state, $current_transaction, $review_comments);
        }

        // Create notification for finance team if submitted by user
        if ($new_state == '1' && $current_user['user_type'] != 3) {
            notify_finance_team($transaction_id, 'transaction_submitted', $current_transaction);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transaction state updated successfully',
            'new_state' => $new_state,
            'state_info' => $workflow->get_workflow_display_info($new_state, $department_type)
        ]);
    } else {
        Security::logSecurityEvent('transaction_state_change_failed', $user_id, "Transaction ID: $transaction_id, From: $current_state, To: $new_state");
        echo json_encode(['success' => false, 'message' => 'Failed to update transaction state']);
    }

} catch (Exception $e) {
    error_log("Error changing transaction state: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

/**
 * Check if user can change state
 */
function can_change_state($user, $current_state, $new_state, $department_type) {
    // Finance team can change most states
    if ($user['user_type'] == 3) {
        return true;
    }

    // Users can only resubmit (change from 4 to 1)
    if ($current_state == '4' && $new_state == '1') {
        return true;
    }

    return false;
}

/**
 * Create notification for state change
 */
function create_state_change_notification($user_id, $transaction_id, $new_state, $transaction_data, $comments = '') {
    global $db, $user;

    $messages = [
        '2' => 'Your transaction is now under review',
        '3' => 'Your transaction has been approved and is being processed',
        '4' => 'Your transaction has been sent back for corrections',
        '5' => 'Your transaction has been sent to payroll',
        '6' => 'Your transaction has been paid'
    ];

    $message = $messages[$new_state] ?? 'Your transaction status has been updated';
    $title = "Transaction {$transaction_data['transaction_id']} - Status Update";

    // Create in-app notification
    $db->query("
        INSERT INTO notifications (user_id, transaction_id, type, title, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ", $user_id, $transaction_data['id'], 'transaction_status_change', $title, $message);

    // Send email notification (non-blocking)
    try {
        if (class_exists('EmailNotification') && file_exists("../controller/EmailNotification.php")) {
            $user_details = $user->get_user_with_role($user_id);

            if ($user_details && !empty($user_details['user_email'])) {
                $emailService = new EmailNotification($db);
                $emailService->sendTransactionStatusNotification(
                    $user_details['user_email'],
                    $user_details['user_name'],
                    $transaction_data,
                    $new_state,
                    $comments
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error sending email notification: " . $e->getMessage());
        // Don't let email errors break the state change - continue processing
    }


}

/**
 * Notify finance team of new submission
 */
function notify_finance_team($transaction_id, $type, $transaction_data) {
    global $db, $user;

    $finance_team = $user->get_finance_team();
    $title = "New Transaction Submitted";
    $message = "Transaction {$transaction_data['transaction_id']} has been submitted for review";

    foreach ($finance_team as $finance_user) {
        $db->query("
            INSERT INTO notifications (user_id, transaction_id, type, title, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", $finance_user['user_id'], $transaction_data['id'], $type, $title, $message);
    }
}




?>
