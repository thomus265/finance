<?php
require_once("../controller/main.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'remove_attachment') {
    $attachment_id = $_POST['attachment_id'] ?? '';

    // Debug logging
    error_log("Remove attachment request - Attachment ID: $attachment_id");

    if (empty($attachment_id)) {
        echo json_encode(['success' => false, 'message' => 'Attachment ID is required']);
        exit;
    }
    
    try {
        // Get attachment details before deletion
        $attachment_details = $transaction->get_transaction_attachments_by_id($attachment_id);

        if (!$attachment_details) {
            echo json_encode(['success' => false, 'message' => 'Attachment not found']);
            exit;
        }

        // Get the transaction details to check if it's read-only
        $transaction_details = $transaction->get_transaction($attachment_details['transaction_id']);
        if (!$transaction_details) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit;
        }

        // Check if transaction is in a read-only state
        if ($transaction_details['state'] == '3') { // Paid
            echo json_encode(['success' => false, 'message' => 'Cannot delete files from paid transactions']);
            exit;
        }
        if ($transaction_details['state'] == '5') { // Completed (if you have this state)
            echo json_encode(['success' => false, 'message' => 'Cannot delete files from completed transactions']);
            exit;
        }
        
        // Remove from database
        $result = $transaction->remove_transaction_attachment($attachment_id);

        if ($result) {
            // Try to remove the physical file
            $file_path = $attachment_details['file_path'] ?? $attachment_details['attachment_path'];
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            
            echo json_encode(['success' => true, 'message' => 'Attachment removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove attachment']);
        }
    } catch (Exception $e) {
        error_log("Error removing attachment: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
