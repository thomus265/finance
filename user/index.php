<?php
$page = "User Dashboard";
require('../includes/top.php');
require('../includes/navbar.php');
// Get dashboard statistics
try {
    // Total transactions
    $total_transactions = $db->query("SELECT COUNT(*) as total FROM transactions")->fetchArray()['total'];

    // Pending transactions (state = 1 or 2)
    $pending_transactions = $db->query("SELECT COUNT(*) as total FROM transactions WHERE state IN ('1', '2')")->fetchArray()['total'];

    // Completed transactions (state = 3)
    $completed_transactions = $db->query("SELECT COUNT(*) as total FROM transactions WHERE state = '3'")->fetchArray()['total'];

    // Returned transactions (state = 4)
    $returned_transactions = $db->query("SELECT COUNT(*) as total FROM transactions WHERE state = '4'")->fetchArray()['total'];

    // Total amount of all transactions
    $total_amount = $db->query("SELECT SUM(amount) as total FROM transactions")->fetchArray()['total'] ?? 0;

    // Total amount of completed transactions
    $completed_amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE state = '3'")->fetchArray()['total'] ?? 0;

    // This month's transactions
    $this_month_transactions = $db->query("SELECT COUNT(*) as total FROM transactions WHERE MONTH(tr_date) = MONTH(CURRENT_DATE()) AND YEAR(tr_date) = YEAR(CURRENT_DATE())")->fetchArray()['total'];

    // Last month's transactions for comparison
    $last_month_transactions = $db->query("SELECT COUNT(*) as total FROM transactions WHERE MONTH(tr_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(tr_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")->fetchArray()['total'];

    // Calculate percentage change
    $user_transactionschange = $last_month_transactions > 0 ? (($this_month_transactions - $last_month_transactions) / $last_month_transactions) * 100 : 0;

    // Recent transactions for the activity feed
    $recent_transactions = $db->query("
        SELECT t.*, u.user_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.user_id
        ORDER BY t.tr_date DESC
        LIMIT 5
    ")->fetchAll();

    // Monthly data for chart (last 6 months)
    $monthly_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        $count = $db->query("SELECT COUNT(*) as total FROM transactions WHERE DATE_FORMAT(tr_date, '%Y-%m') = ?", $month)->fetchArray()['total'];
        $amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE DATE_FORMAT(tr_date, '%Y-%m') = ?", $month)->fetchArray()['total'] ?? 0;
        $monthly_data[] = [
            'month' => $month_name,
            'count' => $count,
            'amount' => $amount
        ];
    }

} catch (Exception $e) {
    // Set default values if database query fails
    $total_transactions = 0;
    $pending_transactions = 0;
    $completed_transactions = 0;
    $returned_transactions = 0;
    $total_amount = 0;
    $completed_amount = 0;
    $user_transactionschange = 0;
    $recent_transactions = [];
    $monthly_data = [];
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!-- Content -->

<div class="container-fluid flex-grow-1 container-p-y">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-primary mb-2">Welcome back, <?php echo $current_user['user_name'] ?></h4>
                    <p class="text-muted mb-0">Here's what's happening with your finance system today.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="user_transactions/" class="btn btn-primary w-100">
                                <i class="bx bx-plus me-2"></i>New Transaction
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="user_transactions/" class="btn btn-outline-primary w-100">
                                <i class="bx bx-list-ul me-2"></i>View All Transactions
                            </a>
                        </div>

                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-4">
                        <h6 class="mb-3">Today's Summary</h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h5 class="mb-0 text-primary">
                                        <?php
                                        $today_count = $db->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(tr_date) = CURDATE()")->fetchArray()['total'];
                                        echo $today_count;
                                        ?>
                                    </h5>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h5 class="mb-0 text-success">
                                        <?php
                                        $week_count = $db->query("SELECT COUNT(*) as total FROM transactions WHERE WEEK(tr_date) = WEEK(CURDATE()) AND YEAR(tr_date) = YEAR(CURDATE())")->fetchArray()['total'];
                                        echo $week_count;
                                        ?>
                                    </h5>
                                    <small class="text-muted">This Week</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h5 class="mb-0 text-info">
                                        <?php echo $this_month_transactions; ?>
                                    </h5>
                                    <small class="text-muted">This Month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Statistics Cards -->
    <div class="row mb-4">

        <!-- Total Transactions -->
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <img src="./public/assets/img/icons/unicons/chart-success.png" alt="chart success"
                                class="rounded" />
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                <a class="dropdown-item" href="user_transactions/">View Details</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Transactions</span>
                    <h3 class="card-title mb-2"><?php echo number_format($total_transactions); ?></h3>
                    <small class="<?php echo $user_transactionschange >= 0 ? 'text-success' : 'text-danger'; ?> fw-semibold">
                        <i class="bx bx-<?php echo $user_transactionschange >= 0 ? 'up' : 'down'; ?>-arrow-alt"></i>
                        <?php echo abs(round($user_transactionschange, 1)); ?>%
                    </small>
                </div>
            </div>
        </div>

        <!-- Pending Transactions -->
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <img src="./public/assets/img/icons/unicons/wallet-info.png" alt="wallet info"
                                class="rounded" />
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Pending Transactions</span>
                    <h3 class="card-title text-nowrap mb-1"><?php echo number_format($pending_transactions); ?></h3>
                    <small class="text-warning fw-semibold">
                        <i class="bx bx-time-five"></i> Awaiting Processing
                    </small>
                </div>
            </div>
        </div>

        <!-- Completed Transactions -->
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <img src="./public/assets/img/icons/unicons/paypal.png" alt="paypal" class="rounded" />
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Completed Transactions</span>
                    <h3 class="card-title text-nowrap mb-1"><?php echo number_format($completed_transactions); ?></h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-check-circle"></i> Successfully Paid
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <img src="./public/assets/img/icons/unicons/cc-primary.png" alt="Credit Card"
                                class="rounded" />
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Amount</span>
                    <h3 class="card-title text-nowrap mb-1">$<?php echo number_format($total_amount, 2); ?></h3>
                    <small class="text-info fw-semibold">
                        <i class="bx bx-dollar-circle"></i> All Transactions
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity Row 
    <div class="row">
        
        <div class="col-lg-8 col-12 order-2 order-md-3 order-lg-2 mb-4">
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
                                                    $peak_month = $data['month'];
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
                                    <span class="badge bg-label-success p-1 me-2"><i class="bx bx-check"></i></span>
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
                                    <span class="badge bg-label-warning p-1 me-2"><i class="bx bx-time"></i></span>
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
                                    <span class="badge bg-label-danger p-1 me-2"><i class="bx bx-x"></i></span>
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
        -->
        <!-- Recent Transactions -->

    </div>

    <!-- Department Breakdown and Quick Actions -->
    

</div>
<!-- / Content -->


<style>
/* Custom styles for the dashboard */
.chart-container {
    position: relative;
}

.chart-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
}

.card-footer.bg-light {
    background-color: #f8f9fa !important;
    border-top: 1px solid #dee2e6;
}

.apexcharts-toolbar {
    background: rgba(255, 255, 255, 0.9) !important;
    border-radius: 6px !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1) !important;
}

.apexcharts-menu {
    background: white !important;
    border: 1px solid #e3e6f0 !important;
    border-radius: 6px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.apexcharts-menu-item:hover {
    background: #f8f9fa !important;
}

/* Responsive chart adjustments */
@media (max-width: 768px) {
    .chart-container {
        margin: 0 -15px;
    }

    #transactionChart {
        min-height: 300px !important;
    }
}

/* Status badge improvements */
.badge.bg-label-success {
    background-color: rgba(113, 221, 55, 0.1) !important;
    color: #71dd37 !important;
}

.badge.bg-label-warning {
    background-color: rgba(255, 171, 0, 0.1) !important;
    color: #ffab00 !important;
}

.badge.bg-label-danger {
    background-color: rgba(255, 62, 29, 0.1) !important;
    color: #ff3e1d !important;
}

/* Animation for chart updates */
.chart-updating {
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.chart-updating::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Transaction Overview Chart
    const transactionChartEl = document.querySelector('#transactionChart');
    if (transactionChartEl) {
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        console.log('Monthly Data:', monthlyData); // Debug log

        const transactionChartOptions = {
            series: [{
                name: 'Transaction Count',
                type: 'column',
                data: monthlyData.map(item => parseInt(item.count) || 0)
            }, {
                name: 'Amount ($)',
                type: 'line',
                data: monthlyData.map(item => parseFloat(item.amount) || 0)
            }],
            chart: {
                height: 350,
                type: 'line',
                stacked: false,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: false,
                        reset: true
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%',
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    borderRadiusWhenStacked: 'last'
                }
            },
            colors: ['#696cff', '#71dd37'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: [0, 4],
                curve: 'smooth',
                lineCap: 'round'
            },
            xaxis: {
                categories: monthlyData.map(item => item.month),
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#a7acb2',
                        fontSize: '13px'
                    }
                }
            },
            yaxis: [{
                title: {
                    text: 'Number of Transactions',
                    style: {
                        color: '#696cff',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#696cff'
                    },
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Amount ($)',
                    style: {
                        color: '#71dd37',
                        fontSize: '14px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#71dd37'
                    },
                    formatter: function(val) {
                        return '$' + (val >= 1000 ? (val / 1000).toFixed(1) + 'K' : val.toFixed(
                            0));
                    }
                }
            }],
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: 40,
                markers: {
                    width: 12,
                    height: 12,
                    strokeWidth: 0,
                    strokeColor: '#fff',
                    fillColors: undefined,
                    radius: 12,
                    customHTML: undefined,
                    onClick: undefined,
                    offsetX: 0,
                    offsetY: 0
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: [{
                    formatter: function(y) {
                        if (typeof y !== "undefined") {
                            return y + " transactions";
                        }
                        return y;
                    }
                }, {
                    formatter: function(y) {
                        if (typeof y !== "undefined") {
                            return "$" + y.toLocaleString();
                        }
                        return y;
                    }
                }]
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 300
                    },
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }]
        };

        window.transactionChart = new ApexCharts(transactionChartEl, transactionChartOptions);
        window.transactionChart.render();

        // Add chart update functionality
        window.updateTransactionChart = function(period) {
            // This function can be called to update the chart with different time periods
            console.log('Updating chart for period:', period);
            // Implementation for dynamic chart updates can be added here
        };
    }

    // Chart period update function
    window.updateChartPeriod = function(period) {
        const button = document.getElementById('chartPeriod');
        let periodText = '';

        switch (period) {
            case '6months':
                periodText = 'Last 6 Months';
                break;
            case '12months':
                periodText = 'Last 12 Months';
                break;
            case 'ytd':
                periodText = 'Year to Date';
                break;
            default:
                periodText = 'Last 6 Months';
        }

        button.textContent = periodText;

        // Show loading state
        const chartContainer = document.getElementById('transactionChart');
        const originalContent = chartContainer.innerHTML;
        chartContainer.innerHTML =
            '<div class="d-flex justify-content-center align-items-center" style="height: 350px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        // Fetch new chart data
        fetch('model/getChartData.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    period: period
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const data = result.data;

                    // Restore chart container
                    chartContainer.innerHTML = '';

                    // Update chart with new data
                    if (window.transactionChart) {
                        window.transactionChart.updateSeries([{
                            name: 'Transaction Count',
                            data: data.counts
                        }, {
                            name: 'Amount ($)',
                            data: data.amounts
                        }]);

                        window.transactionChart.updateOptions({
                            xaxis: {
                                categories: data.months
                            }
                        });
                    } else {
                        // Recreate chart if it doesn't exist
                        createTransactionChart(data);
                    }

                    console.log('Chart updated successfully for period:', period);
                } else {
                    console.error('Error updating chart:', result.error);
                    // Restore original content on error
                    chartContainer.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                // Restore original content on error
                chartContainer.innerHTML = originalContent;
            });
    };

    // Function to create chart with new data
    function createTransactionChart(data) {
        const transactionChartEl = document.querySelector('#transactionChart');
        if (transactionChartEl) {
            const transactionChartOptions = {
                series: [{
                    name: 'Transaction Count',
                    type: 'column',
                    data: data.counts
                }, {
                    name: 'Amount ($)',
                    type: 'line',
                    data: data.amounts
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    stacked: false,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: false,
                            reset: true
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                        borderRadius: 8
                    }
                },
                colors: ['#696cff', '#71dd37'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [0, 4],
                    curve: 'smooth'
                },
                xaxis: {
                    categories: data.months
                },
                yaxis: [{
                    title: {
                        text: 'Number of Transactions'
                    }
                }, {
                    opposite: true,
                    title: {
                        text: 'Amount ($)'
                    }
                }],
                legend: {
                    position: 'top',
                    horizontalAlign: 'left'
                }
            };

            window.transactionChart = new ApexCharts(transactionChartEl, transactionChartOptions);
            window.transactionChart.render();
        }
    }

    // Growth Chart (Donut)
    const growthChartEl = document.querySelector('#growthChart');
    if (growthChartEl) {
        const completionRate = <?php echo $completion_rate; ?>;
        const pendingRate = 100 - completionRate;

        const growthChartOptions = {
            series: [completionRate, pendingRate],
            labels: ['Completed', 'Pending'],
            chart: {
                height: 240,
                type: 'donut'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            value: {
                                fontSize: '1.5rem',
                                fontFamily: 'Public Sans',
                                offsetY: -15,
                                formatter: function(val) {
                                    return parseInt(val) + '%';
                                }
                            },
                            name: {
                                offsetY: 20,
                                fontFamily: 'Public Sans'
                            },
                            total: {
                                show: true,
                                fontSize: '0.8125rem',
                                label: 'Completion',
                                formatter: function(w) {
                                    return completionRate + '%';
                                }
                            }
                        }
                    }
                }
            },
            colors: ['#71dd37', '#ffab00'],
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false
            }
        };

        const growthChart = new ApexCharts(growthChartEl, growthChartOptions);
        growthChart.render();
    }
});
</script>

<?php require('../includes/bottom.php'); ?>