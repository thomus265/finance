<?php
require('../../../controller/main.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $period = $input['period'] ?? '6months';
    
    try {
        $monthly_data = [];
        
        switch ($period) {
            case '12months':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
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
                break;
                
            case 'ytd':
                // Year to date (current year by month)
                $current_year = date('Y');
                $current_month = date('n');
                
                for ($i = 1; $i <= $current_month; $i++) {
                    $month = sprintf('%s-%02d', $current_year, $i);
                    $month_name = date('M Y', strtotime($month . '-01'));
                    $count = $db->query("SELECT COUNT(*) as total FROM transactions WHERE DATE_FORMAT(tr_date, '%Y-%m') = ?", $month)->fetchArray()['total'];
                    $amount = $db->query("SELECT SUM(amount) as total FROM transactions WHERE DATE_FORMAT(tr_date, '%Y-%m') = ?", $month)->fetchArray()['total'] ?? 0;
                    $monthly_data[] = [
                        'month' => $month_name,
                        'count' => $count,
                        'amount' => $amount
                    ];
                }
                break;
                
            default: // '6months'
                // Last 6 months
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
                break;
        }
        
        // Prepare response data
        $response = [
            'success' => true,
            'data' => [
                'months' => array_column($monthly_data, 'month'),
                'counts' => array_map('intval', array_column($monthly_data, 'count')),
                'amounts' => array_map('floatval', array_column($monthly_data, 'amount')),
                'period' => $period
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?>
