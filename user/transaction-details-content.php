<?php
// This file contains the transaction details content that can be included
// Get transaction ID from URL (using 'q' parameter from .htaccess routing)
$transaction_id = $_GET['q'] ?? null;

if (!$transaction_id) {
    // If headers haven't been sent, redirect. Otherwise, show error message.
    if (!headers_sent()) {
        header('Location: /user_transactions');
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
            header('Location: /user_transactions?error=Transaction not found');
            exit;
        } else {
            echo '<div class="alert alert-danger">Transaction not found.</div>';
            return;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching transaction details: " . $e->getMessage());
    if (!headers_sent()) {
        header('Location: /user_transactions?error=Database error');
        exit;
    } else {
        echo '<div class="alert alert-danger">Database error occurred.</div>';
        return;
    }
}

// Get current user information
$current_user = $user->get_current_user();
$is_finance_team = $current_user && $user->is_finance_team($current_user['user_id']);
$is_transaction_owner = $current_user && $current_user['user_id'] == $transaction_details['user_id'];

// Map department values
$department_name = '';
switch($transaction_details['department']) {
    case '1':
        $department_name = 'Operations';
        break;
    case '2':
        $department_name = 'Programs';
        break;
    case '3':
        $department_name = 'Other';
        break;
    default:
        $department_name = $transaction_details['department'];
}

// Get workflow information
$workflow_info = $transaction_details['workflow_info'];
$next_states = $transaction_details['next_states'];
$workflow_progress = $transaction_details['workflow_progress'];
$department_type_name = $workflow->get_department_name($transaction_details['department_type']);

// Use workflow info for state display
$state_name = $workflow_info['name'];
$state_class = $workflow_info['class'];

// Determine if editing is allowed based on workflow
$allow_editing = $workflow_info['is_editable'];
$editing_disabled_reason = '';

if (!$allow_editing) {
    if ($transaction_details['state'] == '6') { // Paid
        $editing_disabled_reason = 'This transaction has been paid and cannot be edited.';
    } else {
        $editing_disabled_reason = 'This transaction cannot be edited in its current state.';
    }
}

// Check if user can edit (only transaction owner can edit when in editable state)
$user_can_edit = $allow_editing && $is_transaction_owner;

// Check if user can resubmit (state 4 and is owner)
$can_resubmit = $transaction_details['state'] == '4' && $is_transaction_owner;

// Check if finance team can change state
$finance_can_change_state = $is_finance_team && !empty($next_states);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Transaction Details</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/user_transactions">Transactions</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($transaction_details['transaction_id']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($user_can_edit): ?>
                        <button type="button" class="btn btn-primary" onclick="toggleEditMode()">
                            <i class="bi bi-pencil"></i> Edit Transaction
                        </button>
                    <?php endif; ?>

                    <?php if ($can_resubmit): ?>
                        <button type="button" class="btn btn-success" onclick="resubmitTransaction()">
                            <i class="bi bi-arrow-up-circle"></i> Re-submit
                        </button>
                    <?php endif; ?>

                    <?php if ($finance_can_change_state): ?>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Change Status
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach ($next_states as $next_state): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="changeTransactionState('<?php echo $next_state['state_value']; ?>', '<?php echo htmlspecialchars($next_state['state_name']); ?>')">
                                            <i class="bi bi-<?php echo $workflow->get_workflow_display_info($next_state['state_value'], $transaction_details['department_type'])['icon']; ?>"></i>
                                            <?php echo htmlspecialchars($next_state['state_name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!$user_can_edit && !$can_resubmit): ?>
                        <button type="button" class="btn btn-secondary" disabled title="<?php echo htmlspecialchars($editing_disabled_reason); ?>">
                            <i class="bi bi-lock"></i> Read Only
                        </button>
                    <?php endif; ?>

                    <a href="/user_transactions/" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Information Card -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-receipt"></i> Transaction Information
                        <span class="badge bg-<?php echo $state_class; ?> ms-2">
                            <i class="bi bi-<?php echo $workflow_info['icon']; ?>"></i>
                            <?php echo htmlspecialchars($state_name); ?>
                        </span>
                        <span class="badge bg-info ms-2">
                            <?php echo htmlspecialchars($department_type_name); ?> Workflow
                        </span>
                        <?php if (!$allow_editing): ?>
                            <span class="badge bg-secondary ms-2">
                                <i class="bi bi-lock"></i> Read Only
                            </span>
                        <?php endif; ?>
                    </h5>

                    <!-- Workflow Progress Bar -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Workflow Progress</small>
                            <small class="text-muted"><?php echo $workflow_progress; ?>%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $state_class; ?>" role="progressbar"
                                 style="width: <?php echo $workflow_progress; ?>%"
                                 aria-valuenow="<?php echo $workflow_progress; ?>"
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$allow_editing): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Read Only:</strong> <?php echo htmlspecialchars($editing_disabled_reason); ?>
                        </div>
                    <?php endif; ?>

                    <!-- View Mode -->
                    <div id="view-mode">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Transaction ID</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($transaction_details['transaction_id']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Title</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($transaction_details['title']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Amount</label>
                                    <p class="form-control-plaintext">$<?php echo number_format($transaction_details['amount'], 2); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Department</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($department_name); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Submitted By</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($transaction_details['user_name'] ?? 'Unknown User'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Date Submitted</label>
                                    <p class="form-control-plaintext"><?php echo date('F j, Y g:i A', strtotime($transaction_details['tr_date'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Workflow Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php if ($transaction_details['reviewed_by'] && $transaction_details['reviewer_name']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reviewed By</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($transaction_details['reviewer_name']); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ($transaction_details['reviewed_at']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Review Date</label>
                                    <p class="form-control-plaintext"><?php echo date('F j, Y g:i A', strtotime($transaction_details['reviewed_at'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($transaction_details['resubmitted_count'] > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Resubmission Count</label>
                                    <p class="form-control-plaintext"><?php echo $transaction_details['resubmitted_count']; ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ($transaction_details['paid_at']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Payment Date</label>
                                    <p class="form-control-plaintext"><?php echo date('F j, Y g:i A', strtotime($transaction_details['paid_at'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Review Comments -->
                        <?php if (!empty($transaction_details['review_comments'])): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Review Comments</label>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <?php echo nl2br(htmlspecialchars($transaction_details['review_comments'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Edit Mode (Hidden by default) -->
                    <div id="edit-mode" style="display: none;">
                        <form id="edit-transaction-form">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                            <input type="hidden" id="edit-transaction-id" value="<?php echo $transaction_details['id']; ?>">
                            <input type="hidden" id="edit-state" value="<?php echo $transaction_details['state']; ?>">
                            <div data-current-state="<?php echo $transaction_details['state']; ?>"></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Transaction ID</label>
                                        <p class="form-control-plaintext text-muted"><?php echo htmlspecialchars($transaction_details['transaction_id']); ?> (Read-only)</p>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-title" class="form-label fw-bold">Title</label>
                                        <input type="text" class="form-control" id="edit-title" value="<?php echo htmlspecialchars($transaction_details['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-amount" class="form-label fw-bold">Amount</label>
                                        <input type="number" step="0.01" class="form-control" id="edit-amount" value="<?php echo $transaction_details['amount']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit-department" class="form-label fw-bold">Department</label>
                                        <select class="form-control" id="edit-department" required>
                                            <option value="1" <?php echo $transaction_details['department'] == '1' ? 'selected' : ''; ?>>Operations</option>
                                            <option value="2" <?php echo $transaction_details['department'] == '2' ? 'selected' : ''; ?>>Programs</option>
                                            <option value="3" <?php echo $transaction_details['department'] == '3' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-<?php echo $state_class; ?>"><?php echo htmlspecialchars($state_name); ?></span>
                                            <small class="text-muted d-block mt-1">Status can only be updated by the finance team</small>
                                        </p>
                                        <input type="hidden" id="edit-state" value="<?php echo $transaction_details['state']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success me-2">
                                        <i class="bi bi-check-lg"></i> Save Changes
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">
                                        <i class="bi bi-x-lg"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-paperclip"></i> Attachments
                        <?php if ($allow_editing): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="showUploadModal()">
                                <i class="bi bi-plus"></i> Add Files
                            </button>
                        <?php else: ?>
                            <span class="badge bg-secondary float-end">
                                <i class="bi bi-lock"></i> Read Only
                            </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="attachments-list">
                        <?php if (!empty($transaction_details['attachments'])): ?>
                            <?php foreach ($transaction_details['attachments'] as $attachment): ?>
                                <div class="attachment-item mb-2 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-file-earmark"></i>
                                            <span class="ms-1"><?php echo htmlspecialchars($attachment['original_filename']); ?></span>
                                            <?php if ($attachment['file_size']): ?>
                                                <small class="text-muted d-block"><?php echo formatFileSize($attachment['file_size']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($allow_editing): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAttachment(<?php echo $attachment['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete files from read-only transactions">
                                                    <i class="bi bi-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No attachments found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
