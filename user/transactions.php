<?php
// Include controllers first (no output)
require_once('../controller/main.php');

// Check if we have a transaction ID in the URL (for transaction details view)
$transaction_id = $_GET['q'] ?? null;

if ($transaction_id) {
    // For transaction details, we need to include the content that handles redirects first
    // Then include the HTML structure
    $page = "Transaction Details";
    require('../includes/top.php');
    require('../includes/navbar.php');

    // Include the transaction details content
    include 'transaction-details-content.php';

    // Include bottom and exit
    require('../includes/bottom.php');
    exit;
}

// If no transaction ID, show the transactions list
$page = "User Transactions";
require('../includes/top.php');
require('../includes/navbar.php');

    // Add the JavaScript and modal for transaction details
    ?>
    <!-- File Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="upload-files-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file-upload" class="form-label">Select Files</label>
                            <input type="file" class="form-control" id="file-upload" name="files[]" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.eml">
                            <div class="form-text">Allowed file types: PDF, DOC, DOCX, XLS, XLSX, CSV, EML</div>
                        </div>
                        <div id="file-preview"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="uploadFiles(this)" data-loading="Uploading">
                        <span class="btn-text">Upload Files</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function toggleEditMode() {
        // Check if editing is allowed (read-only transactions)
        const readOnlyAlert = document.querySelector('.alert-info');
        if (readOnlyAlert) {
            Swal.fire({
                icon: 'info',
                title: 'Read Only Transaction',
                text: 'This transaction cannot be edited due to its current status.',
                confirmButtonText: 'OK'
            });
            return;
        }

        const viewMode = document.getElementById('view-mode');
        const editMode = document.getElementById('edit-mode');

        if (viewMode.style.display === 'none') {
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
        } else {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
        }
    }

    function showUploadModal() {
        // Check if editing is allowed (read-only transactions)
        const readOnlyAlert = document.querySelector('.alert-info');
        if (readOnlyAlert) {
            Swal.fire({
                icon: 'info',
                title: 'Read Only Transaction',
                text: 'Files cannot be uploaded to read-only transactions.',
                confirmButtonText: 'OK'
            });
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
        modal.show();
    }

    // Handle edit form submission
    // Wait for DOM to load before attaching event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const editForm = document.getElementById('edit-transaction-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Add CSRF token
                const formData = new FormData();
                const csrfToken = document.querySelector('input[name="csrf_token"]');
                if (csrfToken) {
                    formData.append('csrf_token', csrfToken.value);
                }

                formData.append('action', 'update_transaction');
                formData.append('transaction_id', document.getElementById('edit-transaction-id').value);
                formData.append('title', document.getElementById('edit-title').value);
                formData.append('amount', document.getElementById('edit-amount').value);
                formData.append('department', document.getElementById('edit-department').value);

                // Get current state from data attribute or hidden field
                const currentState = document.querySelector('[data-current-state]')?.getAttribute('data-current-state') ||
                                   document.getElementById('edit-state')?.value || '1';
                formData.append('state', currentState);

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                    submitBtn.disabled = true;
                }

                fetch('../model/updateTransaction.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Transaction updated successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update transaction'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred'
                    });
                })
                .finally(() => {
                    // Restore button state
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                });
            });
        }
    });

    // Handle file upload
    function uploadFiles(buttonElement) {
        const fileInput = document.getElementById('file-upload');
        const files = fileInput.files;

        if (files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Files Selected',
                text: 'Please select at least one file to upload'
            });
            return;
        }

        // Set loading state on button
        if (buttonElement) {
            setButtonLoading(buttonElement, 'Uploading...');
        }

        const formData = new FormData();

        // Add CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken.value);
        }

        formData.append('action', 'upload_files');

        // Get transaction ID from the edit form
        const transactionIdElement = document.getElementById('edit-transaction-id');
        if (!transactionIdElement) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Transaction ID not found. Please refresh the page.'
            });
            return;
        }

        const transactionId = transactionIdElement.value;
        if (!transactionId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid transaction ID. Please refresh the page.'
            });
            return;
        }

        formData.append('transaction_id', transactionId);

        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        fetch('../model/uploadTransactionFiles.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Upload response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Upload response data:', data);
            if (data.success) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                if (modal) {
                    modal.hide();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Files uploaded successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Reset the form
                    document.getElementById('upload-files-form').reset();
                    document.getElementById('file-preview').innerHTML = '';
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to upload files'
                });
            }
        })
        .catch(error => {
            console.error('Upload Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Upload Error',
                text: 'An unexpected error occurred while uploading files: ' + error.message
            });
        })
        .finally(() => {
            // Remove loading state from button
            if (buttonElement) {
                removeButtonLoading(buttonElement);
            }
        });
    }

    function removeAttachment(attachmentId) {
        // Check if editing is allowed (read-only transactions)
        const readOnlyAlert = document.querySelector('.alert-info');
        if (readOnlyAlert) {
            Swal.fire({
                icon: 'info',
                title: 'Read Only Transaction',
                text: 'Files cannot be deleted from read-only transactions.',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete the attachment',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'remove_attachment');
                formData.append('attachment_id', attachmentId);

                fetch('../model/manageAttachments.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Delete response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete response data:', data);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Attachment has been deleted',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete attachment'
                        });
                    }
                })
                .catch(error => {
                    console.error('Delete Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Error',
                        text: 'An unexpected error occurred while deleting attachment: ' + error.message
                    });
                });
            }
        });
    }

    // File preview functionality
    document.getElementById('file-upload').addEventListener('change', function(e) {
        const files = e.target.files;
        const preview = document.getElementById('file-preview');
        preview.innerHTML = '';

        if (files.length > 0) {
            const list = document.createElement('ul');
            list.className = 'list-group';

            for (let i = 0; i < files.length; i++) {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                item.innerHTML = `
                    <span><i class="bi bi-file-earmark"></i> ${files[i].name}</span>
                    <small class="text-muted">${formatFileSize(files[i].size)}</small>
                `;
                list.appendChild(item);
            }

            preview.appendChild(list);
        }
    });

    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    // Workflow functions
    function resubmitTransaction() {
        const transactionId = document.getElementById('edit-transaction-id').value;

        Swal.fire({
            title: 'Resubmit Transaction?',
            text: 'This will change the status back to "Submitted for Review"',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, resubmit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'resubmit_transaction');
                formData.append('transaction_id', transactionId);

                fetch('../model/resubmitTransaction.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message
                        }).then(() => {
                            location.reload(); // Refresh to show updated status
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to resubmit transaction'
                        });
                    }
                })
                .catch(error => {
                    console.error('Resubmit Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred'
                    });
                });
            }
        });
    }

    function changeTransactionState(newState, stateName) {
        const transactionId = document.getElementById('edit-transaction-id').value;

        // Show input for review comments if sending back
        if (newState === '4') {
            Swal.fire({
                title: 'Send Back Transaction',
                html: `
                    <p>Please provide comments for the user:</p>
                    <textarea id="review-comments" class="form-control" rows="4" placeholder="Enter review comments..."></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Send Back',
                confirmButtonColor: '#dc3545',
                preConfirm: () => {
                    const comments = document.getElementById('review-comments').value;
                    if (!comments.trim()) {
                        Swal.showValidationMessage('Please provide review comments');
                        return false;
                    }
                    return comments;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    performStateChange(transactionId, newState, stateName, result.value);
                }
            });
        } else {
            Swal.fire({
                title: `Change Status to "${stateName}"?`,
                text: 'This action will update the transaction status',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performStateChange(transactionId, newState, stateName);
                }
            });
        }
    }

    function performStateChange(transactionId, newState, stateName, reviewComments = '') {
        const formData = new FormData();
        formData.append('action', 'change_state');
        formData.append('transaction_id', transactionId);
        formData.append('new_state', newState);
        if (reviewComments) {
            formData.append('review_comments', reviewComments);
        }

        fetch('../model/changeTransactionState.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Transaction status changed to "${stateName}"`
                }).then(() => {
                    location.reload(); // Refresh to show updated status
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to change transaction status'
                });
            }
        })
        .catch(error => {
            console.error('State Change Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred'
            });
        });
    }
    </script>


<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

<style>
.transaction-row:hover {
    background-color: #f8f9fa !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.table-hover-effect {
    background-color: #e3f2fd !important;
}

.transaction-row td {
    vertical-align: middle;
}
</style>

<div class="container-fluid flex-grow-1 container-p-y">
    <h1></h1>
    <div class="row">


    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-4">
                            <h2 class="mb-0">Transactions</h2>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="transaction-search"
                                       placeholder="Search by Transaction ID, Title, or Amount...">
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#basicModal">
                                <i class="bi bi-plus"></i> New Transaction
                            </button>
                        </div>
                    </div>

                    <!-- Workflow Filters -->
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="status-filter" onchange="filterTransactions()">
                                <option value="">All Statuses</option>
                                <option value="1">Submitted for Review</option>
                                <option value="2">Under Review</option>
                                <option value="3">Processing</option>
                                <option value="4">Sent Back</option>
                                <option value="5">Sent to Payroll</option>
                                <option value="6">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="department-filter" onchange="filterTransactions()">
                                <option value="">All Departments</option>
                                <option value="1">Operations</option>
                                <option value="2">Programs</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="user-filter" onchange="filterTransactions()">
                                <option value="">All Users</option>
                                <?php
                                $current_user_info = $user->get_current_user();
                                if ($current_user_info && $user->is_finance_team($current_user_info['user_id'])) {
                                    // Finance team can see all users
                                    $all_users = $db->query("SELECT DISTINCT user_id, user_name FROM users WHERE user_name IS NOT NULL ORDER BY user_name")->fetchAll();
                                    foreach ($all_users as $u) {
                                        echo "<option value='{$u['user_id']}'>" . htmlspecialchars($u['user_name']) . "</option>";
                                    }
                                } else {
                                    // Regular users only see themselves
                                    echo "<option value='{$current_user_info['user_id']}' selected>My Transactions</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Title</th>
                                <th>Amount</th>
                                <th>Department</th>
                                <th>State</th>
                                <th>User</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get transactions with user information
                            try {
                                $all_transactions = $db->query("
                                    SELECT t.*, u.user_name
                                    FROM transactions t
                                    LEFT JOIN users u ON t.user_id = u.user_id
                                    ORDER BY t.tr_date DESC
                                ")->fetchAll();
                            } catch (Exception $e) {
                                $all_transactions = [];
                                error_log("Database error in transactions.php: " . $e->getMessage());
                            }

                            if (!empty($all_transactions)) {
                                foreach ($all_transactions as $trans) {
                                    // Map department values
                                    $department_name = '';
                                    switch($trans['department']) {
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
                                            $department_name = $trans['department'];
                                    }

                                    // Get workflow information
                                    $workflow_info = $workflow->get_workflow_display_info($trans['state'], $trans['department_type'] ?? 1);
                                    $department_type_name = $workflow->get_department_name($trans['department_type'] ?? 1);
                                    $state_name = $workflow_info['name'];
                                    ?>
                                    <tr class="transaction-row" data-transaction-id="<?php echo $trans['id']; ?>" style="cursor: pointer;">
                                        <td>
                                            <strong><?php echo htmlspecialchars($trans['transaction_id'] ?? 'TXN-' . date('Y') . '-' . str_pad($trans['id'], 6, '0', STR_PAD_LEFT)); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($trans['title']); ?></td>
                                        <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($department_name); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $workflow_info['class']; ?>">
                                                <i class="bi bi-<?php echo $workflow_info['icon']; ?>"></i>
                                                <?php echo htmlspecialchars($state_name); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted"><?php echo $department_type_name; ?> Workflow</small>
                                            <?php if ($workflow_info['is_final'] || $trans['state'] == '6'): // Final states are read-only ?>
                                                <br>
                                                <span class="badge bg-secondary ms-1" title="Read-only transaction">
                                                    <i class="bi bi-lock"></i> Read Only
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($trans['user_name'] ?? 'Unknown User'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($trans['tr_date'])); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <div class="pagination" id="pagination"></div>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog custom-dropdown-bg" role="document">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Create New transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="upload-form" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <div class="row">
                            <div class="col mb-4">
                                <label for="name" class="form-label">Transaction name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Enter transaction Name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-4">
                                <label for="amount" class="form-label">Transaction Amount</label>
                                <input type="number" id="amount" step=0.01 name="amount" class="form-control"
                                    placeholder="Enter Transaction amount">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-4">
                                <label for="nameBasic" class="form-label">Upload Attachments</label>
                                <div id="drop-area" class="drop-area">
                                    <p>📁 Drag & drop files here or click to browse</p>
                                    <input type="file" id="file-input" name="files[]" multiple hidden
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.eml" />
                                    <ul id="file-list" class="file-list"></ul>
                                </div>
                                <button type="submit" class="btn btn-primary" data-loading="Creating Transaction">
                                    <span class="btn-text">Add transaction</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

</div>
<!-- / Content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable with search functionality
    const table = new DataTable('#myTable', {
        responsive: true,
        scrollY: 200,
        deferRender: true,
        scroller: true,
        columnDefs: [
            { targets: 0, width: '15%' }, // Transaction ID column
            { targets: 1, width: '25%' }, // Title column
            { targets: 2, width: '12%' }, // Amount column
            { targets: 3, width: '15%' }, // Department column
            { targets: 4, width: '12%' }, // State column
            { targets: 5, width: '15%' }, // User column
            { targets: 6, width: '12%' }  // Date column
        ]
    });

    // Add search functionality for transaction ID
    $('#transaction-search').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Make table rows clickable
    $('#myTable tbody').on('click', 'tr.transaction-row', function() {
        const transactionId = $(this).data('transaction-id');
        if (transactionId) {
            window.location.href = '/user_transactions/' + transactionId;
        }
    });

    // Add hover effect for clickable rows
    $('#myTable tbody').on('mouseenter', 'tr.transaction-row', function() {
        $(this).addClass('table-hover-effect');
    }).on('mouseleave', 'tr.transaction-row', function() {
        $(this).removeClass('table-hover-effect');
    });
});
</script>
<script>
$(document).ready(function() {
    let allFiles = [];

    function getFileIcon(ext) {
        ext = ext.toLowerCase();
        if (["pdf"].includes(ext)) return "📄";
        if (["doc", "docx"].includes(ext)) return "📝";
        if (["xls", "xlsx", "csv"].includes(ext)) return "📊";
        if (ext === "eml") return "✉️";
        return "📁";
    }

    function isValidFile(file) {
        const allowed = ["pdf", "doc", "docx", "xls", "xlsx", "csv", "eml"];
        const ext = file.name.split(".").pop().toLowerCase();
        return allowed.includes(ext);
    }

    function addFiles(newFiles) {
        for (let i = 0; i < newFiles.length; i++) {
            const file = newFiles[i];
            if (isValidFile(file)) {
                allFiles.push(file);
                const ext = file.name.split(".").pop();
                const icon = getFileIcon(ext);
                $("#file-list").append(
                    `<li><span class="file-icon">${icon}</span>${file.name}</li>`
                );
            }
        }
    }

    $("#drop-area").on("click", function(e) {
        // prevent recursion by excluding the file input itself
        if (e.target.id !== "file-input") {
            $("#file-input").click();
        }
    });

    $("#drop-area").on("dragover", function(e) {
        e.preventDefault();
        $(this).addClass("dragover");
    });

    $("#drop-area").on("dragleave", function() {
        $(this).removeClass("dragover");
    });

    $("#drop-area").on("drop", function(e) {
        e.preventDefault();
        $(this).removeClass("dragover");
        const dropped = e.originalEvent.dataTransfer.files;
        addFiles(dropped);
    });

    $("#file-input").on("change", function() {
        const selected = this.files;
        addFiles(selected);
        $(this).val(""); // reset
    });

    $("#upload-form").on("submit", function(e) {
        e.preventDefault();

        // Validate required fields
        const name = $("#name").val().trim();
        const amount = $("#amount").val().trim();

        if (!name) {
            Swal.fire({
                icon: "warning",
                title: "Missing Information",
                text: "Please enter a transaction name."
            });
            return;
        }

        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Invalid Amount",
                text: "Please enter a valid amount greater than 0."
            });
            return;
        }

        if (allFiles.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Files Selected",
                text: "Please select at least one valid file."
            });
            return;
        }

        const formData = new FormData();

        // Add CSRF token
        const csrfToken = $('input[name="csrf_token"]').val();
        if (csrfToken) {
            formData.append("csrf_token", csrfToken);
        }

        $.each(allFiles, function(i, file) {
            formData.append("files[]", file);
        });
        formData.append("name", name);
        formData.append("amount", amount);



        $.ajax({
            url: "../model/newTransaction.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                console.log('Success response:', res);
                console.log('Response type:', typeof res);

                // Handle the new response format
                if (res.success === true) {
                    Swal.fire({
                        icon: "success",
                        title: "Transaction Created Successfully",
                        text: res.message || "Transaction created successfully.",
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reset form and close modal
                        $("#upload-form")[0].reset();
                        $("#name").val('');
                        $("#amount").val('');
                        $("#basicModal").modal('hide');
                        // Refresh the page to show the new transaction
                        location.reload();
                    });
                } else {
                    // Handle error response
                    Swal.fire({
                        icon: "error",
                        title: "Transaction Creation Failed",
                        text: res.message || "Failed to create transaction",
                        confirmButtonText: "OK"
                    });
                }

                allFiles = [];
                $("#file-list").empty();
                $("#file-input").val('');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:');
                console.error('XHR:', xhr);
                console.error('Response Text:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Ready State:', xhr.readyState);
                console.error('HTTP Status:', xhr.status);

                let errorMessage = "An unexpected error occurred while uploading.";
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.message || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.responseText;
                    }
                }

                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: errorMessage,
                    confirmButtonText: "Retry"
                });
            }
        });
    });
});

// Workflow filter functions
function filterTransactions() {
    const statusFilter = document.getElementById('status-filter').value;
    const departmentFilter = document.getElementById('department-filter').value;
    const userFilter = document.getElementById('user-filter').value;
    const searchFilter = document.getElementById('transaction-search').value.toLowerCase();

    const rows = document.querySelectorAll('#myTable tbody tr.transaction-row');

    rows.forEach(row => {
        let showRow = true;

        // Status filter
        if (statusFilter) {
            const statusBadge = row.querySelector('.badge');
            const rowStatus = getStatusFromBadge(statusBadge);
            if (rowStatus !== statusFilter) {
                showRow = false;
            }
        }

        // Department filter
        if (departmentFilter && showRow) {
            const departmentText = row.cells[3].textContent.trim();
            const departmentMatch = (departmentFilter === '1' && departmentText.includes('Operations')) ||
                                  (departmentFilter === '2' && departmentText.includes('Programs'));
            if (!departmentMatch) {
                showRow = false;
            }
        }

        // User filter
        if (userFilter && showRow) {
            const userCell = row.cells[5];
            const userId = userCell.dataset.userId || '';
            if (userId !== userFilter) {
                showRow = false;
            }
        }

        // Search filter
        if (searchFilter && showRow) {
            const rowText = row.textContent.toLowerCase();
            if (!rowText.includes(searchFilter)) {
                showRow = false;
            }
        }

        row.style.display = showRow ? '' : 'none';
    });

    updateFilteredCount();
}

function getStatusFromBadge(badge) {
    if (!badge) return '';

    const text = badge.textContent.trim();
    if (text.includes('Submitted for Review')) return '1';
    if (text.includes('Under Review')) return '2';
    if (text.includes('Processing')) return '3';
    if (text.includes('Sent Back')) return '4';
    if (text.includes('Sent to Payroll')) return '5';
    if (text.includes('Paid')) return '6';
    return '';
}

function clearFilters() {
    document.getElementById('status-filter').value = '';
    document.getElementById('department-filter').value = '';
    document.getElementById('user-filter').value = '';
    document.getElementById('transaction-search').value = '';
    filterTransactions();
}

function updateFilteredCount() {
    const allRows = document.querySelectorAll('#myTable tbody tr.transaction-row');
    const visibleRows = document.querySelectorAll('#myTable tbody tr.transaction-row[style=""]');
    const hiddenRows = allRows.length - visibleRows.length;

    // Update count display if needed
    console.log(`Showing ${visibleRows.length} of ${allRows.length} transactions`);
}

// Add search functionality
document.getElementById('transaction-search').addEventListener('input', filterTransactions);
</script>
<?php  require('../includes/bottom.php'); ?>