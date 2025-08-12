<?php
require_once("../controller/main.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$transaction_id = $_POST['transaction_id'] ?? '';

// Debug logging
error_log("Resubmit request - Action: $action, Transaction ID: $transaction_id");

if ($action !== 'resubmit_transaction' || empty($transaction_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
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
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Check if user owns this transaction
    if ($current_transaction['user_id'] != $current_user['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You can only resubmit your own transactions']);
        exit;
    }

    // Check if transaction is in "sent back" state
    if ($current_transaction['state'] != '4') {
        echo json_encode(['success' => false, 'message' => 'Transaction can only be resubmitted when it has been sent back']);
        exit;
    }

    // Resubmit the transaction
    $result = $transaction->resubmit_transaction($transaction_id, $current_user['user_id']);

    if ($result) {
        // Log the state change
        $workflow->log_state_change(
            $transaction_id, 
            '4', // From sent back
            '1', // To submitted
            $current_user['user_id'],
            'Transaction resubmitted by user'
        );

        // Notify finance team of resubmission
        $finance_team = $user->get_finance_team();
        $title = "Transaction Resubmitted";
        $message = "Transaction {$current_transaction['transaction_id']} has been resubmitted for review";

        foreach ($finance_team as $finance_user) {
            $db->query("
                INSERT INTO notifications (user_id, transaction_id, type, title, message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", $finance_user['user_id'], $current_transaction['id'], 'transaction_resubmitted', $title, $message);
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Transaction resubmitted successfully',
            'new_state' => '1',
            'state_info' => $workflow->get_workflow_display_info('1', $current_transaction['department_type'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to resubmit transaction']);
    }

} catch (Exception $e) {
    error_log("Error resubmitting transaction: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
