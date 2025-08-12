<?php
// This file contains the finance team transaction details content
// Get transaction ID from URL (using 'q' parameter from .htaccess routing)
$transaction_id = $_GET['q'] ?? null;

if (!$transaction_id) {
    // If headers haven't been sent, redirect. Otherwise, show error message.
    if (!headers_sent()) {
        header('Location: /finance_transactions');
        exit;
    } else {
        echo '<div class="alert alert-danger">No transaction ID provided.</div>';
        return;
    }
}

// Get transaction details
try {
    $transaction_details = $transaction->get_transaction_details($transaction_id);

    if (!$transaction_details) {
        if (!headers_sent()) {
            header('Location: /finance_transactions?error=Transaction not found');
            exit;
        } else {
            echo '<div class="alert alert-danger">Transaction not found.</div>';
            return;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching transaction details: " . $e->getMessage());
    if (!headers_sent()) {
        header('Location: /finance_transactions?error=Database error');
        exit;
    } else {
        echo '<div class="alert alert-danger">Database error occurred.</div>';
        return;
    }
}

// Get workflow information
$workflow_info = $workflow->get_workflow_display_info($transaction_details['state'], $transaction_details['department_type']);
$workflow_states = $workflow->get_workflow_states_for_department($transaction_details['department_type']);

// Get transaction history/audit trail
$history = $workflow->get_transaction_history($transaction_id);

// Get attachments
$attachments = $transaction->get_transaction_attachments($transaction_id);

// Get user information
$transaction_user = $user->get_user_with_role($transaction_details['user_id']);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Transaction Details - Finance View</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/finance_dashboard">Finance Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="/finance_transactions">Transactions</a></li>
                            <li class="breadcrumb-item active"><?php echo $transaction_details['transaction_id']; ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/finance_transactions" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Transaction Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Transaction Information</h5>
                            <span class="badge bg-<?php echo $workflow_info['color']; ?> fs-6">
                                <?php echo $workflow_info['name']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Transaction ID:</strong></td>
                                            <td><?php echo $transaction_details['transaction_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Title:</strong></td>
                                            <td><?php echo htmlspecialchars($transaction_details['title']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount:</strong></td>
                                            <td class="fs-5 text-success">$<?php echo number_format($transaction_details['amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Department:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $transaction_details['department_type'] == 1 ? 'primary' : 'info'; ?>">
                                                    <?php echo $transaction_details['department_type'] == 1 ? 'Operations' : 'Programs'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Submitted By:</strong></td>
                                            <td><?php echo htmlspecialchars($transaction_user['user_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($transaction_user['user_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Submitted Date:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($transaction_details['tr_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td><?php echo $transaction_details['reviewed_at'] ? date('M j, Y g:i A', strtotime($transaction_details['reviewed_at'])) : 'Never'; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if (!empty($transaction_details['review_comments'])): ?>
                                <div class="mt-3">
                                    <h6>Review Comments:</h6>
                                    <div class="alert alert-info">
                                        <?php echo nl2br(htmlspecialchars($transaction_details['review_comments'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Attachments (<?php echo count($attachments); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attachments)): ?>
                                <p class="text-muted">No attachments uploaded.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark text-primary me-2"></i>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($attachment['file_name']); ?></h6>
                                                            <small class="text-muted">
                                                                Uploaded: <?php echo date('M j, Y', strtotime($attachment['uploaded_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <a href="<?php echo $attachment['file_path']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           target="_blank">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Transaction History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($history)): ?>
                                <p class="text-muted">No history available.</p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($history as $entry): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="bi bi-clock text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        State changed from <?php echo $entry['from_state']; ?> to <?php echo $entry['to_state']; ?>
                                                    </h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($entry['comments']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($entry['changed_at'])); ?>
                                                        by <?php echo htmlspecialchars($entry['changed_by_name']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions Panel -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Finance Actions</h5>
                        </div>
                        <div class="card-body">
                            <!-- State Change Form -->
                            <form id="state-change-form">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                <input type="hidden" name="transaction_id" value="<?php echo $transaction_details['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="new_state" class="form-label">Change Status To:</label>
                                    <select name="new_state" id="new_state" class="form-select">
                                        <option value="">Select new status...</option>
                                        <?php foreach ($workflow_states as $state): ?>
                                            <?php if ($state['state_value'] != $transaction_details['state']): ?>
                                                <option value="<?php echo $state['state_value']; ?>">
                                                    <?php echo $state['state_name']; ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review_comments" class="form-label">Comments:</label>
                                    <textarea name="review_comments" id="review_comments" 
                                              class="form-control" rows="3" 
                                              placeholder="Add comments about this status change..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100" data-loading="Updating Status">
                                    <i class="bi bi-check-circle"></i> <span class="btn-text">Update Status</span>
                                </button>
                            </form>
                            
                            <hr>
                            
                            <!-- Quick Actions -->
                            <div class="d-grid gap-2">
                                <?php if ($transaction_details['state'] == '1'): // Submitted ?>
                                    <button class="btn btn-success action-btn" onclick="quickAction('approve', this)" data-loading="Approving">
                                        <i class="bi bi-check-circle"></i> <span class="btn-text">Quick Approve</span>
                                    </button>
                                    <button class="btn btn-warning action-btn" onclick="quickAction('send_back', this)" data-loading="Sending Back">
                                        <i class="bi bi-arrow-left-circle"></i> <span class="btn-text">Send Back</span>
                                    </button>
                                <?php elseif ($transaction_details['state'] == '2'): // Under Review ?>
                                    <button class="btn btn-success action-btn" onclick="quickAction('approve_to_payroll', this)" data-loading="Sending to Payroll">
                                        <i class="bi bi-check-circle"></i> <span class="btn-text">Approve to Payroll</span>
                                    </button>
                                    <?php if ($transaction_details['department_type'] == 1): // Operations only ?>
                                        <button class="btn btn-info action-btn" onclick="quickAction('approve_to_processing', this)" data-loading="Processing">
                                            <i class="bi bi-gear"></i> <span class="btn-text">Approve to Processing</span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-warning action-btn" onclick="quickAction('send_back', this)" data-loading="Sending Back">
                                        <i class="bi bi-arrow-left-circle"></i> <span class="btn-text">Send Back</span>
                                    </button>
                                <?php elseif ($transaction_details['state'] == '5'): // In Payroll ?>
                                    <button class="btn btn-success action-btn" onclick="quickAction('mark_paid', this)" data-loading="Marking as Paid">
                                        <i class="bi bi-cash"></i> <span class="btn-text">Mark as Paid</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle state change form submission
document.getElementById('state-change-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const newState = formData.get('new_state');
    const comments = formData.get('review_comments');
    
    if (!newState) {
        alert('Please select a new status.');
        return;
    }
    
    changeTransactionState(formData);
});

// Quick action handlers
function quickAction(action, buttonElement) {
    const transactionId = <?php echo $transaction_details['id']; ?>;
    const formData = new FormData();
    formData.append('csrf_token', '<?php echo Security::generateCSRFToken(); ?>');
    formData.append('transaction_id', transactionId);

    let actionText = '';
    let confirmMessage = '';

    switch(action) {
        case 'approve':
            formData.append('new_state', '2');
            formData.append('review_comments', 'Quick approval - under review');
            actionText = 'Approving';
            confirmMessage = 'Are you sure you want to approve this transaction?';
            break;
        case 'send_back':
            const reason = prompt('Please provide a reason for sending back:');
            if (!reason) return;
            formData.append('new_state', '4');
            formData.append('review_comments', reason);
            actionText = 'Sending Back';
            confirmMessage = 'Are you sure you want to send back this transaction?';
            break;
        case 'approve_to_payroll':
            formData.append('new_state', '5');
            formData.append('review_comments', 'Approved and sent to payroll');
            actionText = 'Sending to Payroll';
            confirmMessage = 'Are you sure you want to send this to payroll?';
            break;
        case 'approve_to_processing':
            formData.append('new_state', '3');
            formData.append('review_comments', 'Approved for processing');
            actionText = 'Processing';
            confirmMessage = 'Are you sure you want to approve for processing?';
            break;
        case 'mark_paid':
            formData.append('new_state', '6');
            formData.append('review_comments', 'Transaction marked as paid');
            actionText = 'Marking as Paid';
            confirmMessage = 'Are you sure you want to mark this transaction as paid?';
            break;
        default:
            return;
    }

    // Show confirmation dialog
    if (!confirm(confirmMessage)) return;

    // Set loading state on the clicked button
    if (buttonElement) {
        setButtonLoading(buttonElement, actionText + '...');
    }

    changeTransactionState(formData, buttonElement);
}

// AJAX function to change transaction state
function changeTransactionState(formData, buttonElement = null) {
    // Ensure action parameter is set
    if (!formData.has('action')) {
        formData.append('action', 'change_state');
    }

    console.log('Sending state change request...');
    console.log('FormData contents:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }

    fetch('../model/changeTransactionState.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);

        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON:', data);

            if (data.success) {
                alert('Transaction status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                console.error('Server error:', data);
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Raw response was:', text);
            alert('Server response was not valid JSON. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Network error occurred: ' + error.message);
    })
    .finally(() => {
        // Remove loading state from button
        if (buttonElement) {
            removeButtonLoading(buttonElement);
        }
    });
}
</script>
