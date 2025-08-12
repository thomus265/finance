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
$transaction_id = Security::sanitizeInput($_POST['transaction_id'] ?? '');

// Debug logging
error_log("Upload request - Action: $action, Transaction ID: $transaction_id, User ID: $user_id");

// Validate that user exists in database
try {
    $user_check = $db->query("SELECT user_id FROM users WHERE user_id = ?", $user_id)->fetchArray();
    if (!$user_check) {
        error_log("Upload error: User ID $user_id does not exist in database");
        Security::logSecurityEvent('invalid_user_upload_request', $user_id, "User ID does not exist in database");
        echo json_encode(['success' => false, 'message' => 'User validation failed']);
        exit;
    }
} catch (Exception $e) {
    error_log("Upload error: Database error during user validation: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during user validation']);
    exit;
}

if ($action !== 'upload_files' || empty($transaction_id)) {
    Security::logSecurityEvent('invalid_file_upload_request', $user_id, "Invalid parameters: action=$action, transaction_id=$transaction_id");
    echo json_encode(['success' => false, 'message' => 'Invalid request', 'debug' => ['action' => $action, 'transaction_id' => $transaction_id]]);
    exit;
}

// Rate limiting for file uploads
if (!Security::checkRateLimit('file_upload', $user_id, 10, 300)) {
    Security::logSecurityEvent('file_upload_rate_limit_exceeded', $user_id, 'File upload rate limit exceeded');
    echo json_encode(['success' => false, 'message' => 'Too many file uploads. Please try again later.']);
    exit;
}

// Validate transaction exists and access
try {
    $transaction_details = $transaction->get_transaction($transaction_id);
    if (!$transaction_details) {
        Security::logSecurityEvent('file_upload_transaction_not_found', $user_id, "Transaction ID: $transaction_id");
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Get current user for access validation
    $current_user = $user->get_current_user();

    // Validate transaction access
    $access_validation = Security::validateTransactionAccess($transaction_id, $user_id, $current_user['user_type']);
    if (!$access_validation['valid']) {
        Security::logSecurityEvent('unauthorized_file_upload', $user_id, "Transaction ID: $transaction_id, Reason: " . $access_validation['message']);
        echo json_encode(['success' => false, 'message' => $access_validation['message']]);
        exit;
    }

    // Check if transaction is in a read-only state using workflow
    if (!$workflow->is_transaction_editable($transaction_details['state'], $transaction_details['department_type'])) {
        Security::logSecurityEvent('file_upload_readonly_transaction', $user_id, "Transaction ID: $transaction_id, State: " . $transaction_details['state']);
        echo json_encode(['success' => false, 'message' => 'Cannot upload files to transactions in this state']);
        exit;
    }
} catch (Exception $e) {
    Security::logSecurityEvent('file_upload_database_error', $user_id, "Transaction ID: $transaction_id, Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

$allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv',
    'message/rfc822'
];

$uploadDir = '../public/attachments/' . date('Y-m-d') . '/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        error_log("Failed to create upload directory: $uploadDir");
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

$responses = [];
$successCount = 0;
// $user_id is already set from Security::validateSession() above

foreach ($_FILES['files']['name'] as $i => $originalName) {
    $tmpName = $_FILES['files']['tmp_name'][$i];
    $type = $_FILES['files']['type'][$i];
    $error = $_FILES['files']['error'][$i];
    $size = $_FILES['files']['size'][$i];

    // Validate file using security class
    $file_data = [
        'name' => $originalName,
        'tmp_name' => $tmpName,
        'type' => $type,
        'error' => $error,
        'size' => $size
    ];

    $file_validation = Security::validateFileUpload($file_data);
    if (!$file_validation['valid']) {
        Security::logSecurityEvent('invalid_file_upload', $user_id, "File: $originalName, Reason: " . $file_validation['message']);
        $responses[] = [
            'name' => $originalName,
            'status' => 'error',
            'message' => $file_validation['message']
        ];
        continue;
    }

    if ($error === UPLOAD_ERR_OK) {
            $safeName = time() . '_' . $i . '_' . basename($originalName);
            $destination = $uploadDir . $safeName;

            if (move_uploaded_file($tmpName, $destination)) {
                try {
                    // Add to attachments table first
                    $attachment_result = $attachment->add_attachment($destination, $type);
                    
                    if ($attachment_result) {
                        $attachment_id = $attachment->get_last_id();
                        
                        // Link to transaction
                        $link_result = $transaction->add_transaction_attachment(
                            $transaction_id,
                            $attachment_id,
                            $originalName,
                            $destination,
                            $size,
                            $type,
                            $user_id
                        );
                        
                        if ($link_result) {
                            $successCount++;
                            $responses[] = [
                                'name' => $originalName,
                                'status' => 'success',
                                'path' => $destination
                            ];
                        } else {
                            // Remove the uploaded file if database linking failed
                            unlink($destination);
                            $responses[] = [
                                'name' => $originalName,
                                'status' => 'error',
                                'message' => 'Failed to link file to transaction'
                            ];
                        }
                    } else {
                        // Remove the uploaded file if database insert failed
                        unlink($destination);
                        $responses[] = [
                            'name' => $originalName,
                            'status' => 'error',
                            'message' => 'Failed to save file information'
                        ];
                    }
                } catch (Exception $e) {
                    // Remove the uploaded file if any error occurred
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                    $responses[] = [
                        'name' => $originalName,
                        'status' => 'error',
                        'message' => 'Database error: ' . $e->getMessage()
                    ];
                }
            } else {
                $responses[] = [
                    'name' => $originalName,
                    'status' => 'error',
                    'message' => 'Failed to move uploaded file'
                ];
            }
        } else {
            $responses[] = [
                'name' => $originalName,
                'status' => 'error',
                'message' => 'Invalid file type'
            ];
        }
    } else {
        $responses[] = [
            'name' => $originalName,
            'status' => 'error',
            'message' => 'Upload error code: ' . $error
        ];
    }
}

if ($successCount > 0) {
    echo json_encode([
        'success' => true,
        'message' => "$successCount file(s) uploaded successfully",
        'details' => $responses
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No files were uploaded successfully',
        'details' => $responses
    ]);
}
?>
