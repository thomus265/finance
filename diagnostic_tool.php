<?php
/**
 * Diagnostic tool for finance transaction system issues
 */

require_once('controller/main.php');

echo "<h1>Finance Transaction System Diagnostic Tool</h1>";

// Check if this is a POST request for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_action'])) {
    handle_test_request();
    exit;
}

?>

<style>
.diagnostic-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.test-button { margin: 10px 5px; padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
</style>

<div class="diagnostic-section">
    <h2>1. System Status Check</h2>
    <?php check_system_status(); ?>
</div>

<div class="diagnostic-section">
    <h2>2. CSRF Token Test</h2>
    <p>Current CSRF Token: <code><?php echo Security::generateCSRFToken(); ?></code></p>
    <button class="test-button" onclick="testCSRF()">Test CSRF Validation</button>
    <div id="csrf-result"></div>
</div>

<div class="diagnostic-section">
    <h2>3. State Change Test</h2>
    <p>Test transaction state change functionality:</p>
    <button class="test-button" onclick="testStateChange()">Test State Change</button>
    <div id="state-change-result"></div>
</div>

<div class="diagnostic-section">
    <h2>4. Transaction Creation Test</h2>
    <p>Test transaction creation functionality:</p>
    <button class="test-button" onclick="testTransactionCreation()">Test Transaction Creation</button>
    <div id="transaction-creation-result"></div>
</div>

<div class="diagnostic-section">
    <h2>5. Error Log Monitor</h2>
    <p>Recent error log entries:</p>
    <button class="test-button" onclick="checkErrorLogs()">Check Error Logs</button>
    <div id="error-log-result"></div>
</div>

<script>
function testCSRF() {
    const resultDiv = document.getElementById('csrf-result');
    resultDiv.innerHTML = 'Testing...';
    
    fetch('diagnostic_tool.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'test_action=csrf&csrf_token=' + encodeURIComponent('<?php echo Security::generateCSRFToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = `<p class="${data.success ? 'success' : 'error'}">${data.message}</p>`;
    })
    .catch(error => {
        resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    });
}

function testStateChange() {
    const resultDiv = document.getElementById('state-change-result');
    resultDiv.innerHTML = 'Testing...';
    
    fetch('diagnostic_tool.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'test_action=state_change&csrf_token=' + encodeURIComponent('<?php echo Security::generateCSRFToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = `<p class="${data.success ? 'success' : 'error'}">${data.message}</p>`;
    })
    .catch(error => {
        resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    });
}

function testTransactionCreation() {
    const resultDiv = document.getElementById('transaction-creation-result');
    resultDiv.innerHTML = 'Testing...';
    
    fetch('diagnostic_tool.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'test_action=transaction_creation&csrf_token=' + encodeURIComponent('<?php echo Security::generateCSRFToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = `<p class="${data.success ? 'success' : 'error'}">${data.message}</p>`;
    })
    .catch(error => {
        resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    });
}

function checkErrorLogs() {
    const resultDiv = document.getElementById('error-log-result');
    resultDiv.innerHTML = 'Checking...';
    
    fetch('diagnostic_tool.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'test_action=error_logs&csrf_token=' + encodeURIComponent('<?php echo Security::generateCSRFToken(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = `<p class="${data.success ? 'success' : 'error'}">${data.message}</p>`;
        if (data.logs) {
            resultDiv.innerHTML += '<pre>' + data.logs + '</pre>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    });
}
</script>

<?php

function check_system_status() {
    echo "<h3>File Existence Check:</h3>";
    
    $critical_files = [
        'model/changeTransactionState.php' => 'State change endpoint',
        'model/newTransaction.php' => 'Transaction creation endpoint',
        'controller/Security.php' => 'Security class',
        'controller/Workflow.php' => 'Workflow class',
        'controller/Transaction.php' => 'Transaction class',
        'user/transactions.php' => 'User transactions page',
        'finance/transaction-details-content.php' => 'Finance transaction details'
    ];
    
    foreach ($critical_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ $description ($file)</p>";
        } else {
            echo "<p class='error'>❌ $description ($file) - MISSING</p>";
        }
    }
    
    echo "<h3>Class Availability Check:</h3>";
    
    $classes = ['Security', 'Workflow', 'Transaction', 'User', 'Notification'];
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<p class='success'>✅ $class class available</p>";
        } else {
            echo "<p class='error'>❌ $class class not available</p>";
        }
    }
    
    echo "<h3>Database Connection Check:</h3>";
    
    try {
        global $db;
        $result = $db->query("SELECT 1")->fetchArray();
        if ($result) {
            echo "<p class='success'>✅ Database connection working</p>";
        } else {
            echo "<p class='error'>❌ Database query failed</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    }
}

function handle_test_request() {
    header('Content-Type: application/json');
    
    $action = $_POST['test_action'] ?? '';
    
    try {
        switch ($action) {
            case 'csrf':
                $token = $_POST['csrf_token'] ?? '';
                if (Security::validateCSRFToken($token)) {
                    echo json_encode(['success' => true, 'message' => 'CSRF token validation successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
                }
                break;
                
            case 'state_change':
                // Test if the state change endpoint is accessible
                if (file_exists('model/changeTransactionState.php')) {
                    echo json_encode(['success' => true, 'message' => 'State change endpoint is accessible']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'State change endpoint not found']);
                }
                break;
                
            case 'transaction_creation':
                // Test if the transaction creation endpoint is accessible
                if (file_exists('model/newTransaction.php')) {
                    echo json_encode(['success' => true, 'message' => 'Transaction creation endpoint is accessible']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Transaction creation endpoint not found']);
                }
                break;
                
            case 'error_logs':
                $log_file = ini_get('error_log');
                if ($log_file && file_exists($log_file)) {
                    $logs = tail($log_file, 20);
                    echo json_encode(['success' => true, 'message' => 'Error logs retrieved', 'logs' => $logs]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error log file not found or not configured']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown test action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Test error: ' . $e->getMessage()]);
    }
}

function tail($file, $lines = 20) {
    $handle = fopen($file, "r");
    if (!$handle) {
        return "Could not open file";
    }
    
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    return implode("", array_reverse($text));
}

?>
