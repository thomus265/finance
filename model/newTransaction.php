<?php
require_once("../controller/main.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'details' => []
    ]);
    exit;
}

try {
    // Validate session
    $user_id = Security::validateSession();

    // CSRF protection
    Security::requireCSRFToken();

    // Rate limiting for transaction creation
    if (!Security::checkRateLimit('transaction_create', $user_id, 5, 300)) {
        Security::logSecurityEvent('transaction_create_rate_limit_exceeded', $user_id, 'Transaction creation rate limit exceeded');
        echo json_encode([
            'success' => false,
            'message' => 'Too many transaction creation requests. Please try again later.',
            'details' => []
        ]);
        exit;
    }

    // Validate and sanitize input
    $name = Security::sanitizeInput($_POST['name'] ?? '');
    $amount = Security::sanitizeInput($_POST['amount'] ?? '');

    // Validate transaction data
    $validation_errors = Security::validateTransactionData($name, $amount, '1'); // Default to Operations department
    if (!empty($validation_errors)) {
        Security::logSecurityEvent('invalid_new_transaction_data', $user_id, "Errors: " . implode(', ', $validation_errors));
        echo json_encode([
            'success' => false,
            'message' => 'Invalid transaction data',
            'details' => $validation_errors
        ]);
        exit;
    }

    // Get current user to determine department type
    $current_user = $user->get_current_user();
    if (!$current_user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated',
            'details' => []
        ]);
        exit;
    }

    // Determine department based on user type
    $dept = $current_user['department_type'] ?? 1; // Default to Operations
    $state = 1; // Set to "Submitted" status when user creates a transaction

    $add = $transaction->add_transaction($name, $amount, $dept, $state, $user_id);
    if($add){
        //get last insert
        $lastId = $transaction->get_last_id();

        // Get the transaction details to get the transaction_id
        $transaction_details = $transaction->get_transaction($lastId);
        $transaction_id = $transaction_details['transaction_id'] ?? 'TXN-' . date('Y') . '-' . str_pad($lastId, 6, '0', STR_PAD_LEFT);

        $today = date('Y-m-d');
        $baseDir = '../public/attachments/' . $today . '/';
        $uploadDir = $baseDir . $transaction_id . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
   

    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'message/rfc822'
    ];

    $responses = [];

    foreach ($_FILES['files']['name'] as $i => $name) {
        $tmpName = $_FILES['files']['tmp_name'][$i];
        $type = $_FILES['files']['type'][$i];
        $error = $_FILES['files']['error'][$i];

        if ($error === UPLOAD_ERR_OK) {
            if (in_array($type, $allowedTypes)) {
                $safeName = time() . '_' . basename($name);
                $destination = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $destination)) {
                    try {
                        // Add to attachments table
                        $attachment_result = $attachment->add_attachment($destination, $type);

                        if ($attachment_result) {
                            $attachment_id = $attachment->get_last_id();

                            // Link to transaction
                            $link_result = $transaction->add_transaction_attachment(
                                $lastId,
                                $attachment_id,
                                $name,
                                $destination,
                                $_FILES['files']['size'][$i],
                                $type,
                                $user_id
                            );

                            if ($link_result) {
                                $responses[] = [
                                    'name' => $name,
                                    'status' => 'success',
                                    'path' => $destination
                                ];
                            } else {
                                // Remove the uploaded file if database linking failed
                                unlink($destination);
                                $responses[] = [
                                    'name' => $name,
                                    'status' => 'error',
                                    'message' => 'Failed to link file to transaction.'
                                ];
                            }
                        } else {
                            // Remove the uploaded file if database insert failed
                            unlink($destination);
                            $responses[] = [
                                'name' => $name,
                                'status' => 'error',
                                'message' => 'Failed to save file information.'
                            ];
                        }
                    } catch (Exception $e) {
                        // Remove the uploaded file if any error occurred
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                        $responses[] = [
                            'name' => $name,
                            'status' => 'error',
                            'message' => 'Database error: ' . $e->getMessage()
                        ];
                    }
                } else {
                    $responses[] = [
                        'name' => $name,
                        'status' => 'error',
                        'message' => 'Failed to move file.'
                    ];
                }
            } else {
                $responses[] = [
                    'name' => $name,
                    'status' => 'error',
                    'message' => 'Invalid file type.'
                ];
            }
        } else {
            $responses[] = [
                'name' => $name,
                'status' => 'error',
                'message' => 'Upload error code: ' . $error
            ];
        }
    }


    } else {
        //failed to add transaction
        Security::logSecurityEvent('transaction_create_failed', $user_id, "Name: $name, Amount: $amount");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create transaction',
            'details' => []
        ]);
        exit;
    }

    // Return success response with file upload results
    $success_count = 0;
    foreach ($responses as $response) {
        if ($response['status'] === 'success') {
            $success_count++;
        }
    }

    // Log successful transaction creation
    Security::logSecurityEvent('transaction_created', $user_id, "Transaction ID: $transaction_id, Name: $name, Amount: $amount, Files: $success_count");

    // Notify finance team of new submission
    $notification->notify_finance_team_submission($lastId, [
        'id' => $lastId,
        'transaction_id' => $transaction_id,
        'user_name' => $current_user['user_name']
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Transaction created successfully with {$success_count} file(s) uploaded",
        'details' => $responses
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error in newTransaction.php: " . $e->getMessage());
    Security::logSecurityEvent('transaction_create_error', $user_id, "Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the transaction: ' . $e->getMessage(),
        'details' => []
    ]);
    exit;
}
?>