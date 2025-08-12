<?php
// Include controllers first (no output)
require_once('../controller/main.php');

// Check if user is finance team member BEFORE any output
$current_user = $user->get_current_user();
if (!$current_user || !$user->is_finance_team($current_user['user_id'])) {
    header('Location: /user_transactions?error=access_denied');
    exit;
}

// Check if we have a transaction ID in the URL (for transaction details view)
$transaction_id = $_GET['q'] ?? null;

if ($transaction_id) {
    // For transaction details, include the content that handles redirects first
    $page = "Finance - Transaction Details";
    require('../includes/top.php');
    require('../includes/navbar.php');
    
    // Include the transaction details content (finance version)
    include 'transaction-details-content.php';
    
    // Include bottom and exit
    require('../includes/bottom.php');
    exit;
}

// If no transaction ID, show the finance transactions list
$page = "Finance - Transactions";
require('../includes/top.php');
require('../includes/navbar.php');

// Get all transactions for finance team with enhanced filtering and pagination
$filter_status = $_GET['status'] ?? '';
$filter_department = $_GET['department'] ?? '';
$filter_user = $_GET['user'] ?? '';
$search_query = $_GET['search'] ?? '';
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 20; // Transactions per page
$offset = ($page_num - 1) * $per_page;

// Build the WHERE clause for filtering
$where_conditions = [];
$params = [];

if (!empty($filter_status)) {
    // Handle multiple statuses (comma-separated)
    $statuses = explode(',', $filter_status);
    $status_placeholders = str_repeat('?,', count($statuses) - 1) . '?';
    $where_conditions[] = "t.state IN ($status_placeholders)";
    $params = array_merge($params, $statuses);
}

if (!empty($filter_department)) {
    $where_conditions[] = "t.department_type = ?";
    $params[] = $filter_department;
}

if (!empty($filter_user)) {
    $where_conditions[] = "t.user_id = ?";
    $params[] = $filter_user;
}

if (!empty($search_query)) {
    $where_conditions[] = "(t.title LIKE ? OR t.transaction_id LIKE ? OR u.user_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    $where_clause
";
$total_transactions_filtered = $db->query($count_query, ...$params)->fetchArray()['total'] ?? 0;
$total_pages = ceil($total_transactions_filtered / $per_page);

// Get transactions with user and workflow information
$transactions_query = "
    SELECT t.*, u.user_name, u.user_email,
           ws.state_name,
           CASE
               WHEN t.department_type = 1 THEN 'Operations'
               WHEN t.department_type = 2 THEN 'Programs'
               ELSE 'Unknown'
           END as department_name
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN workflow_states ws ON t.state = ws.state_value
        AND (ws.department_type = t.department_type OR ws.department_type = 0)
    $where_clause
    ORDER BY t.tr_date DESC
";

// Define state colors mapping
function getStateColor($state) {
    $colors = [
        '1' => 'warning',    // Submitted
        '2' => 'info',       // Under Review
        '3' => 'primary',    // Processing
        '4' => 'danger',     // Sent Back
        '5' => 'secondary',  // Sent to Payroll
        '6' => 'success'     // Paid
    ];
    return $colors[$state] ?? 'secondary';
}

// Add pagination to the query
$transactions_query .= " LIMIT ? OFFSET ?";
$pagination_params = array_merge($params, [$per_page, $offset]);
$transactions = $db->query($transactions_query, ...$pagination_params)->fetchAll();

// Get summary statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN t.state = 1 THEN 1 ELSE 0 END) as pending_review,
        SUM(CASE WHEN t.state = 2 THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN t.state = 4 THEN 1 ELSE 0 END) as sent_back,
        SUM(CASE WHEN t.state = 5 THEN 1 ELSE 0 END) as in_payroll,
        SUM(CASE WHEN t.state = 6 THEN 1 ELSE 0 END) as paid,
        SUM(t.amount) as total_amount
    FROM transactions t
    $where_clause
";

$stats = $db->query($stats_query, ...$params)->fetchArray();

// Get users for filter dropdown
$users = $db->query("
    SELECT DISTINCT u.user_id, u.user_name 
    FROM users u 
    INNER JOIN transactions t ON u.user_id = t.user_id 
    ORDER BY u.user_name
")->fetchAll();

// Get workflow states for filter dropdown
$workflow_states = $workflow->get_workflow_states();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Finance Team - Transaction Management</h4>
                    <div class="d-flex gap-2">
                        <a href="/finance_dashboard" class="btn btn-outline-primary">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <button class="btn btn-primary" onclick="refreshTransactions()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['total_transactions']; ?></h5>
                                    <small>Total Transactions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['pending_review']; ?></h5>
                                    <small>Pending Review</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['under_review']; ?></h5>
                                    <small>Under Review</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['sent_back']; ?></h5>
                                    <small>Sent Back</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['in_payroll']; ?></h5>
                                    <small>In Payroll</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5><?php echo $stats['paid']; ?></h5>
                                    <small>Paid</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($workflow_states as $state): ?>
                                            <option value="<?php echo $state['state_value']; ?>" 
                                                    <?php echo $filter_status == $state['state_value'] ? 'selected' : ''; ?>>
                                                <?php echo $state['state_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="department" class="form-select">
                                        <option value="">All Departments</option>
                                        <option value="1" <?php echo $filter_department == '1' ? 'selected' : ''; ?>>Operations</option>
                                        <option value="2" <?php echo $filter_department == '2' ? 'selected' : ''; ?>>Programs</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="user" class="form-select">
                                        <option value="">All Users</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?php echo $u['user_id']; ?>" 
                                                    <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo $u['user_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search transactions..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="/finance_transactions" class="btn btn-outline-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Transactions Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Title</th>
                                    <th>User</th>
                                    <th>Department</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No transactions found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td>
                                                <a href="/finance_transactions/<?php echo $transaction['id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo $transaction['transaction_id']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $transaction['department_type'] == 1 ? 'primary' : 'info'; ?>">
                                                    <?php echo $transaction['department_name']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStateColor($transaction['state']); ?>">
                                                    <?php echo $transaction['state_name'] ?? 'Unknown'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['tr_date'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="/finance_transactions/<?php echo $transaction['id']; ?>"
                                                       class="btn btn-outline-primary btn-sm"
                                                       title="View Transaction Details">
                                                        <i class="bi bi-eye me-1"></i>View
                                                    </a>

                                                    <?php if ($transaction['state'] == '1'): // Submitted ?>
                                                        <button class="btn btn-outline-success btn-sm"
                                                                onclick="quickApprove(<?php echo $transaction['id']; ?>)"
                                                                title="Start Review Process">
                                                            <i class="bi bi-play-circle me-1"></i>Review
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm"
                                                                onclick="sendBack(<?php echo $transaction['id']; ?>)"
                                                                title="Send Back for Corrections">
                                                            <i class="bi bi-arrow-left-circle me-1"></i>Send Back
                                                        </button>
                                                    <?php elseif ($transaction['state'] == '2'): // Under Review ?>
                                                        <button class="btn btn-outline-success btn-sm"
                                                                onclick="quickApprove(<?php echo $transaction['id']; ?>)"
                                                                title="Approve Transaction">
                                                            <i class="bi bi-check-circle me-1"></i>Approve
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm"
                                                                onclick="sendBack(<?php echo $transaction['id']; ?>)"
                                                                title="Send Back for Corrections">
                                                            <i class="bi bi-arrow-left-circle me-1"></i>Send Back
                                                        </button>
                                                    <?php elseif ($transaction['state'] == '5'): // In Payroll ?>
                                                        <button class="btn btn-outline-info btn-sm"
                                                                onclick="markPaid(<?php echo $transaction['id']; ?>)"
                                                                title="Mark as Paid">
                                                            <i class="bi bi-cash me-1"></i>Mark Paid
                                                        </button>
                                                    <?php elseif ($transaction['state'] == '6'): // Paid ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>Completed
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <p class="text-muted mb-0">
                                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_transactions_filtered); ?>
                                        of <?php echo $total_transactions_filtered; ?> transactions
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="Transaction pagination">
                                        <ul class="pagination justify-content-end mb-0">
                                            <!-- Previous Page -->
                                            <li class="page-item <?php echo $page_num <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($page_num - 1); ?>"
                                                   aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>

                                            <!-- Page Numbers -->
                                            <?php
                                            $start_page = max(1, $page_num - 2);
                                            $end_page = min($total_pages, $page_num + 2);

                                            for ($i = $start_page; $i <= $end_page; $i++):
                                            ?>
                                                <li class="page-item <?php echo $i == $page_num ? 'active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <!-- Next Page -->
                                            <li class="page-item <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($page_num + 1); ?>"
                                                   aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '/finance_transactions?' . http_build_query($params);
}
?>

<script>
function refreshTransactions() {
    location.reload();
}

function quickApprove(transactionId) {
    if (confirm('Are you sure you want to approve this transaction?')) {
        // Implementation for quick approve
        changeTransactionState(transactionId, getNextApprovalState(transactionId), 'Quick approval');
    }
}

function sendBack(transactionId) {
    const reason = prompt('Please provide a reason for sending back this transaction:');
    if (reason) {
        changeTransactionState(transactionId, 4, reason); // State 4 = Sent Back
    }
}

function changeTransactionState(transactionId, newState, comments) {
    // This function will be implemented to handle state changes
    console.log('Changing transaction', transactionId, 'to state', newState, 'with comments:', comments);
    // TODO: Implement AJAX call to change state
}

function getNextApprovalState(transactionId) {
    // This would determine the next state based on current state and department
    // For now, return a default next state
    return 5; // Sent to Payroll
}

function markPaid(transactionId) {
    if (confirm('Are you sure you want to mark this transaction as paid?')) {
        changeTransactionState(transactionId, 6, 'Transaction marked as paid');
    }
}

// Enhanced filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterForm = document.querySelector('form[method="GET"]');
    const filterInputs = filterForm.querySelectorAll('select, input[name="search"]');

    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.name !== 'search') {
                filterForm.submit();
            }
        });
    });

    // Add search delay for search input
    let searchTimeout;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500); // 500ms delay
        });
    }

    // Add loading states
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.name !== 'search') {
                showLoadingState();
            }
        });
    });
});

function showLoadingState() {
    const tableBody = document.querySelector('tbody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading transactions...</p>
                </td>
            </tr>
        `;
    }
}

// Quick filter buttons
function applyQuickFilter(filterType) {
    const form = document.querySelector('form[method="GET"]');
    const statusSelect = form.querySelector('select[name="status"]');

    switch(filterType) {
        case 'pending':
            statusSelect.value = '1,2';
            break;
        case 'completed':
            statusSelect.value = '6';
            break;
        case 'returned':
            statusSelect.value = '4';
            break;
        case 'all':
            statusSelect.value = '';
            break;
    }

    form.submit();
}

// Add quick filter buttons to the page
function addQuickFilterButtons() {
    const filterRow = document.querySelector('.row.mb-3');
    if (filterRow) {
        const quickFiltersHtml = `
            <div class="col-12 mb-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Quick filters">
                    <button type="button" class="btn btn-outline-secondary" onclick="applyQuickFilter('all')">
                        All Transactions
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="applyQuickFilter('pending')">
                        Pending Review
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="applyQuickFilter('completed')">
                        Completed
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="applyQuickFilter('returned')">
                        Returned
                    </button>
                </div>
            </div>
        `;
        filterRow.insertAdjacentHTML('afterbegin', quickFiltersHtml);
    }
}

// Initialize quick filter buttons when page loads
document.addEventListener('DOMContentLoaded', addQuickFilterButtons);
</script>

<?php require('../includes/bottom.php'); ?>
