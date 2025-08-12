<?php
require_once("../controller/main.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate session
$user_id = Security::validateSession();

// CSRF protection
Security::requireCSRFToken();

$action = Security::sanitizeInput($_POST['action'] ?? '');

if ($action !== 'update_transaction') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Get form data
$transaction_id = Security::sanitizeInput($_POST['transaction_id'] ?? '');
$title = Security::sanitizeInput($_POST['title'] ?? '');
$amount = Security::sanitizeInput($_POST['amount'] ?? '');
$department = Security::sanitizeInput($_POST['department'] ?? '');
$state = Security::sanitizeInput($_POST['state'] ?? '');

// Rate limiting
if (!Security::checkRateLimit('transaction_update', $user_id, 15, 300)) {
    Security::logSecurityEvent('transaction_update_rate_limit_exceeded', $user_id, 'Transaction update rate limit exceeded');
    echo json_encode(['success' => false, 'message' => 'Too many update requests. Please try again later.']);
    exit;
}

// Validate transaction data
$validation_errors = Security::validateTransactionData($title, $amount, $department);
if (!empty($validation_errors)) {
    Security::logSecurityEvent('invalid_transaction_data', $user_id, "Transaction ID: $transaction_id, Errors: " . implode(', ', $validation_errors));
    echo json_encode(['success' => false, 'message' => 'Invalid transaction data', 'errors' => $validation_errors]);
    exit;
}

// Validate required fields
if (empty($transaction_id) || $state === '') {
    echo json_encode(['success' => false, 'message' => 'Transaction ID and state are required']);
    exit;
}

try {
    // Get current user
    $current_user = $user->get_current_user();
    if (!$current_user) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Get current transaction to preserve the state (users can't change state)
    $current_transaction = $transaction->get_transaction($transaction_id);
    if (!$current_transaction) {
        Security::logSecurityEvent('transaction_update_not_found', $user_id, "Transaction ID: $transaction_id");
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Validate transaction access
    $access_validation = Security::validateTransactionAccess($transaction_id, $user_id, $current_user['user_type']);
    if (!$access_validation['valid']) {
        Security::logSecurityEvent('unauthorized_transaction_update', $user_id, "Transaction ID: $transaction_id, Reason: " . $access_validation['message']);
        echo json_encode(['success' => false, 'message' => $access_validation['message']]);
        exit;
    }

    // Check if transaction is in an editable state using workflow
    $current_state = $current_transaction['state'];
    if (!$workflow->is_transaction_editable($current_state, $current_transaction['department_type'])) {
        Security::logSecurityEvent('transaction_update_readonly', $user_id, "Transaction ID: $transaction_id, State: $current_state");
        echo json_encode(['success' => false, 'message' => 'Cannot edit transactions in this state']);
        exit;
    }

    // Update the transaction (preserving the current state)
    $result = $transaction->update_transaction($transaction_id, $title, $amount, $department, $current_state, $user_id);

    if ($result) {
        Security::logSecurityEvent('transaction_updated', $user_id, "Transaction ID: $transaction_id");
        echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
    } else {
        Security::logSecurityEvent('transaction_update_failed', $user_id, "Transaction ID: $transaction_id");
        echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
    }
} catch (Exception $e) {
    error_log("Error updating transaction: " . $e->getMessage());
    Security::logSecurityEvent('transaction_update_error', $user_id, "Transaction ID: $transaction_id, Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
