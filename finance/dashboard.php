<?php
// Include controllers first (no output)
require_once('../controller/main.php');

// Check if user is finance team member BEFORE any output
$current_user = $user->get_current_user();
if (!$current_user || !$user->is_finance_team($current_user['user_id'])) {
    header('Location: /user_transactions?error=access_denied');
    exit;
}

// Now safe to include files that output HTML
$page = "Finance Dashboard";
require('../includes/top.php');
require('../includes/navbar.php');

// Get pending transactions statistics
$pending_stats = $db->query("
    SELECT 
        COUNT(*) as total_pending,
        SUM(CASE WHEN state = 1 THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN state = 2 THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN state = 4 THEN 1 ELSE 0 END) as sent_back,
        SUM(amount) as total_amount
    FROM transactions 
    WHERE state IN (1, 2, 4)
")->fetchArray();

// Get recent transactions requiring attention
$recent_transactions = $db->query("
    SELECT t.*, u.user_name, u.user_email,
           ws.state_name, ws.state_description
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN workflow_states ws ON t.state = ws.state_value AND (ws.department_type = t.department_type OR ws.department_type = 0)
    WHERE t.state IN (1, 2, 4)
    ORDER BY 
        CASE 
            WHEN t.state = 1 THEN 1  -- Submitted (highest priority)
            WHEN t.state = 4 THEN 2  -- Sent back (medium priority)
            WHEN t.state = 2 THEN 3  -- Under review (lowest priority)
        END,
        t.tr_date ASC
    LIMIT 10
")->fetchAll();

// Get workflow statistics by department
$dept_stats = $db->query("
    SELECT
        department_type,
        COUNT(*) as count,
        AVG(CASE WHEN state = 6 THEN DATEDIFF(paid_at, submitted_at) ELSE NULL END) as avg_processing_days
    FROM transactions
    WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY department_type
")->fetchAll();

// Handle AJAX requests for chart period updates
$chart_period = $_GET['chart_period'] ?? '6months';
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Determine date range based on period
$date_condition = '';
switch ($chart_period) {
    case '12months':
        $date_condition = "WHERE tr_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        break;
    case 'ytd':
        $date_condition = "WHERE tr_date >= DATE_FORMAT(NOW(), '%Y-01-01')";
        break;
    case '6months':
    default:
        $date_condition = "WHERE tr_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        break;
}

// Get monthly data for charts
$monthly_data = $db->query("
    SELECT
        DATE_FORMAT(tr_date, '%Y-%m') as month,
        DATE_FORMAT(tr_date, '%M %Y') as month_name,
        COUNT(*) as count,
        SUM(amount) as amount
    FROM transactions
    $date_condition
    GROUP BY DATE_FORMAT(tr_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

// If this is an AJAX request, return JSON data
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'monthly_data' => $monthly_data,
        'chart_period' => $chart_period
    ]);
    exit;
}

// Calculate month-over-month change
$current_month_count = $db->query("
    SELECT COUNT(*) as count
    FROM transactions
    WHERE DATE_FORMAT(tr_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
")->fetchArray()['count'] ?? 0;

$last_month_count = $db->query("
    SELECT COUNT(*) as count
    FROM transactions
    WHERE DATE_FORMAT(tr_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')
")->fetchArray()['count'] ?? 0;

$user_transactionschange = $last_month_count > 0 ? (($current_month_count - $last_month_count) / $last_month_count) * 100 : 0;

// Get transaction counts by status
$total_transactions = $db->query("SELECT COUNT(*) as count FROM transactions")->fetchArray()['count'] ?? 0;
$completed_transactions = $db->query("SELECT COUNT(*) as count FROM transactions WHERE state = '6'")->fetchArray()['count'] ?? 0;
$pending_transactions = $db->query("SELECT COUNT(*) as count FROM transactions WHERE state IN ('1', '2')")->fetchArray()['count'] ?? 0;
$returned_transactions = $db->query("SELECT COUNT(*) as count FROM transactions WHERE state = '4'")->fetchArray()['count'] ?? 0;

// Get completed amount
$completed_amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE state = '6'")->fetchArray()['total'] ?? 0;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<div class="container-fluid flex-grow-1 container-p-y">
    <!-- Email Configuration Status -->
    <?php include('../includes/email-config-status.php'); ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold py-3 mb-2">
                        <i class="bi bi-speedometer2"></i> Finance Dashboard
                    </h4>
                    <p class="text-muted">Manage and review transaction workflows</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo $pending_stats['submitted']; ?></h3>
                            <p class="card-text">New Submissions</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-upload fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo $pending_stats['under_review']; ?></h3>
                            <p class="card-text">Under Review</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-eye fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo $pending_stats['sent_back']; ?></h3>
                            <p class="card-text">Sent Back</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-arrow-left fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title">$<?php echo number_format($pending_stats['total_amount'], 2); ?></h3>
                            <p class="card-text">Pending Amount</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-currency-dollar fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="#" onclick="filterTransactions('submitted')" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-list-check"></i> Review New Submissions
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" onclick="filterTransactions('under_review')" class="btn btn-outline-warning w-100 mb-2">
                                <i class="bi bi-eye"></i> View Under Review
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" onclick="filterTransactions('sent_back')" class="btn btn-outline-danger w-100 mb-2">
                                <i class="bi bi-arrow-left"></i> Check Sent Back
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/user_transactions" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="bi bi-table"></i> All Transactions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity Row -->
    <div class="row mb-4">
        <div class="col-lg-8 col-12 order-1 order-md-3 order-lg-2 mb-4">
            <div class="card">
                <div class="row row-bordered g-0">
                    <div class="col-md-8">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 me-2">Transaction Overview</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    id="chartPeriod" data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    Last 6 Months
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="chartPeriod">
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="updateChartPeriod('6months')">Last 6 Months</a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="updateChartPeriod('12months')">Last 12 Months</a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="updateChartPeriod('ytd')">Year to Date</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="transactionChart" style="min-height: 350px;"></div>
                        </div>

                        <div class="card-footer bg-light">
                            <div class="row text-center">
                                <div class="col-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">Avg/Month</small>
                                        <h6 class="mb-0 text-primary">
                                            <?php
                                            $avg_monthly = count($monthly_data) > 0 ? round(array_sum(array_column($monthly_data, 'count')) / count($monthly_data)) : 0;
                                            echo $avg_monthly;
                                            ?>
                                        </h6>
                                        <small class="text-muted">transactions</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">Peak Month</small>
                                        <h6 class="mb-0 text-info">
                                            <?php
                                            $peak_month = '';
                                            $peak_count = 0;
                                            foreach ($monthly_data as $data) {
                                                if ($data['count'] > $peak_count) {
                                                    $peak_count = $data['count'];
                                                    $peak_month = $data['month_name'];
                                                }
                                            }
                                            echo $peak_count ?: '0';
                                            ?>
                                        </h6>
                                        <small class="text-muted"><?php echo $peak_month ?: 'N/A'; ?></small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">Growth</small>
                                        <h6
                                            class="mb-0 <?php echo $user_transactionschange >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($user_transactionschange >= 0 ? '+' : '') . round($user_transactionschange, 1); ?>%
                                        </h6>
                                        <small class="text-muted">vs last month</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">Total Value</small>
                                        <h6 class="mb-0 text-success">
                                            $<?php
                                            $total_chart_amount = array_sum(array_column($monthly_data, 'amount'));
                                            echo $total_chart_amount >= 1000 ? number_format($total_chart_amount/1000, 1) . 'K' : number_format($total_chart_amount);
                                            ?>
                                        </h6>
                                        <small class="text-muted">6 months</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                        id="growthReportId" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        <?php echo date('Y'); ?>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="growthReportId">
                                        <a class="dropdown-item" href="javascript:void(0);"><?php echo date('Y'); ?></a>
                                        <a class="dropdown-item"
                                            href="javascript:void(0);"><?php echo date('Y') - 1; ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="growthChart"></div>
                        <div class="text-center fw-semibold pt-3 mb-3">
                            <?php
                            $completion_rate = $total_transactions > 0 ? round(($completed_transactions / $total_transactions) * 100) : 0;
                            echo $completion_rate;
                            ?>% Completion Rate
                        </div>

                        <div class="px-3 pb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success p-1 me-2"><i class="bi bi-check"></i></span>
                                    <small>Completed</small>
                                </div>
                                <div class="text-end">
                                    <small
                                        class="fw-semibold">$<?php echo number_format($completed_amount, 0); ?></small>
                                    <br><small class="text-muted"><?php echo $completed_transactions; ?> txns</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning p-1 me-2"><i class="bi bi-clock"></i></span>
                                    <small>Pending</small>
                                </div>
                                <div class="text-end">
                                    <small class="fw-semibold">
                                        $<?php
                                        $pending_amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE state IN ('1', '2')")->fetchArray()['total'] ?? 0;
                                        echo number_format($pending_amount, 0);
                                        ?>
                                    </small>
                                    <br><small class="text-muted"><?php echo $pending_transactions; ?> txns</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-danger p-1 me-2"><i class="bi bi-x"></i></span>
                                    <small>Returned</small>
                                </div>
                                <div class="text-end">
                                    <small class="fw-semibold">
                                        $<?php
                                        $returned_amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE state = '4'")->fetchArray()['total'] ?? 0;
                                        echo number_format($returned_amount, 0);
                                        ?>
                                    </small>
                                    <br><small class="text-muted"><?php echo $returned_transactions; ?> txns</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="col-lg-4 col-12 order-2 order-md-2 order-lg-1">
            
        </div>
    </div>

    <!-- Pending Transactions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock"></i> Transactions Requiring Attention
                    </h5>
                    <div>
                        <select id="status-filter" class="form-select form-select-sm" onchange="filterByStatus()">
                            <option value="">All Pending</option>
                            <option value="1">Submitted</option>
                            <option value="2">Under Review</option>
                            <option value="4">Sent Back</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="pending-transactions-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Title</th>
                                    <th>Amount</th>
                                    <th>Submitted By</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $trans): ?>
                                    <?php 
                                    $workflow_info = $workflow->get_workflow_display_info($trans['state'], $trans['department_type']);
                                    $dept_name = $workflow->get_department_name($trans['department_type']);
                                    ?>
                                    <tr data-status="<?php echo $trans['state']; ?>">
                                        <td>
                                            <a href="/user_transactions/<?php echo $trans['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($trans['transaction_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($trans['title']); ?></td>
                                        <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($trans['user_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $dept_name; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $workflow_info['class']; ?>">
                                                <i class="bi bi-<?php echo $workflow_info['icon']; ?>"></i>
                                                <?php echo htmlspecialchars($trans['state_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($trans['tr_date'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/user_transactions/<?php echo $trans['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($trans['state'] == '1'): // Submitted ?>
                                                    <button class="btn btn-outline-success btn-sm" onclick="quickApprove(<?php echo $trans['id']; ?>)">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="quickReject(<?php echo $trans['id']; ?>)">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load additional chart libraries -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Debug: Verify Chart.js is loaded
if (typeof Chart !== 'undefined') {
    console.log('✅ Chart.js loaded successfully, version:', Chart.version);
} else {
    console.error('❌ Chart.js failed to load');
}
</script>

<script>
function refreshDashboard() {
    location.reload();
}

function filterTransactions(status) {
    const statusMap = {
        'submitted': '1',
        'under_review': '2',
        'sent_back': '4'
    };
    
    document.getElementById('status-filter').value = statusMap[status] || '';
    filterByStatus();
}

function filterByStatus() {
    const filter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('#pending-transactions-table tbody tr');
    
    rows.forEach(row => {
        if (!filter || row.dataset.status === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function quickApprove(transactionId) {
    Swal.fire({
        title: 'Approve Transaction?',
        text: 'This will move the transaction to the next stage in the workflow',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Determine next state based on department type
            // This is a simplified version - in practice, you'd get the transaction details first
            changeTransactionStateQuick(transactionId, '2', 'Under Review');
        }
    });
}

function quickReject(transactionId) {
    Swal.fire({
        title: 'Send Back Transaction',
        html: `
            <p>Please provide comments for the user:</p>
            <textarea id="reject-comments" class="form-control" rows="4" placeholder="Enter review comments..."></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Send Back',
        confirmButtonColor: '#dc3545',
        preConfirm: () => {
            const comments = document.getElementById('reject-comments').value;
            if (!comments.trim()) {
                Swal.showValidationMessage('Please provide review comments');
                return false;
            }
            return comments;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            changeTransactionStateQuick(transactionId, '4', 'Sent Back', result.value);
        }
    });
}

function changeTransactionStateQuick(transactionId, newState, stateName, reviewComments = '') {
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
                location.reload();
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

// Chart functionality
let transactionChart;
let growthChart;

// Initialize charts when page loads and Chart.js is available
document.addEventListener('DOMContentLoaded', function() {
    // Use the chart loader to ensure Chart.js is ready
    window.chartLoader.whenReady(() => {
        console.log('🚀 Initializing dashboard charts...');
        initializeTransactionChart();
        initializeGrowthChart();
    });
});

// Listen for Chart.js loading events
document.addEventListener('chartjs:ready', function(event) {
    console.log('📊 Chart.js ready event received:', event.detail);
});

document.addEventListener('chartjs:error', function(event) {
    console.error('📊 Chart.js loading failed:', event.detail);
});

function initializeTransactionChart() {
    const ctx = document.getElementById('transactionChart');
    if (!ctx) {
        console.error('Transaction chart canvas not found');
        return;
    }

    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    // Prepare data from PHP
    const monthlyData = <?php echo json_encode($monthly_data); ?>;
    console.log('Monthly data:', monthlyData);

    if (!monthlyData || monthlyData.length === 0) {
        console.warn('No monthly data available for chart');
        ctx.innerHTML = '<div class="text-center p-4"><p class="text-muted">No data available for chart</p></div>';
        return;
    }

    const labels = monthlyData.map(item => item.month_name || item.month);
    const counts = monthlyData.map(item => parseInt(item.count) || 0);
    const amounts = monthlyData.map(item => parseFloat(item.amount) || 0);

    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Transaction Count',
                data: counts,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Transaction Amount ($)',
                data: amounts,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Transaction Count'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Amount ($)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            }
        }
    };

    // Use the new chart loader for safer initialization
    initChartWhenReady('transactionChart', config, (chart, error) => {
        if (chart) {
            transactionChart = chart;
        } else {
            console.error('Failed to initialize transaction chart:', error);
        }
    });
}

function initializeGrowthChart() {
    const ctx = document.getElementById('growthChart');
    if (!ctx) return;

    // Calculate completion rate data
    const completionRate = <?php echo $completion_rate; ?>;
    const pendingRate = <?php echo $total_transactions > 0 ? round((($pending_transactions + $returned_transactions) / $total_transactions) * 100) : 0; ?>;

    const config = {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending/Returned'],
            datasets: [{
                data: [completionRate, 100 - completionRate],
                backgroundColor: [
                    'rgb(40, 167, 69)',
                    'rgb(255, 193, 7)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    };

    // Use the new chart loader for safer initialization
    initChartWhenReady('growthChart', config, (chart, error) => {
        if (chart) {
            growthChart = chart;
        } else {
            console.error('Failed to initialize growth chart:', error);
        }
    });
}

function updateChartPeriod(period) {
    // Update button text
    const button = document.getElementById('chartPeriod');
    const periodNames = {
        '6months': 'Last 6 Months',
        '12months': 'Last 12 Months',
        'ytd': 'Year to Date'
    };
    button.textContent = periodNames[period];

    // Fetch new data and update chart
    fetch(`/finance/dashboard.php?chart_period=${period}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if (transactionChart && data.monthly_data) {
                const labels = data.monthly_data.map(item => item.month_name || item.month);
                const counts = data.monthly_data.map(item => parseInt(item.count));
                const amounts = data.monthly_data.map(item => parseFloat(item.amount));

                transactionChart.data.labels = labels;
                transactionChart.data.datasets[0].data = counts;
                transactionChart.data.datasets[1].data = amounts;
                transactionChart.update();

                // Update statistics
                updateChartStatistics(data);
            }
        })
        .catch(error => {
            console.error('Error updating chart:', error);
        });
}

function updateChartStatistics(data) {
    // Update the statistics in the footer
    // This would update the avg/month, peak month, growth, and total value
    // Implementation depends on the specific HTML structure
}
</script>

<?php require('../includes/bottom.php'); ?>
