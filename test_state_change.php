<?php
/**
 * Test script for state change functionality
 */

require_once('controller/main.php');

echo "<h1>State Change Test</h1>";
echo "<style>
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
</style>";

// Check if this is a POST request (AJAX test)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_state_change'])) {
    header('Content-Type: application/json');
    
    try {
        // Simulate the state change process
        $user_id = $_SESSION['user_id'] ?? null;
        $transaction_id = $_POST['transaction_id'] ?? '';
        $new_state = $_POST['new_state'] ?? '';
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            exit;
        }
        
        if (empty($transaction_id) || empty($new_state)) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit;
        }
        
        // Test database connection
        global $db;
        $transaction = $db->query("SELECT * FROM transactions WHERE id = ?", $transaction_id)->fetchArray();
        
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit;
        }
        
        // Test update
        $result = $db->query("UPDATE transactions SET state = ?, last_updated_at = NOW() WHERE id = ?", $new_state, $transaction_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'State change test successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Display test interface
?>

<h2>State Change Functionality Test</h2>

<div id="test-results"></div>

<h3>1. Session Check</h3>
<?php
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ User logged in: ID " . $_SESSION['user_id'] . "</p>";
    if (isset($_SESSION['user_name'])) {
        echo "<p class='info'>User: " . $_SESSION['user_name'] . "</p>";
    }
} else {
    echo "<p class='error'>❌ No user logged in</p>";
    echo "<p><a href='index.php'>Please log in first</a></p>";
}
?>

<h3>2. Available Transactions</h3>
<?php
try {
    global $db;
    $transactions = $db->query("SELECT id, transaction_id, name, state, amount FROM transactions ORDER BY id DESC LIMIT 5")->fetchAll();
    
    if ($transactions) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Transaction ID</th><th>Name</th><th>Current State</th><th>Amount</th><th>Test Action</th></tr>";
        
        foreach ($transactions as $transaction) {
            echo "<tr>";
            echo "<td>{$transaction['id']}</td>";
            echo "<td>{$transaction['transaction_id']}</td>";
            echo "<td>{$transaction['name']}</td>";
            echo "<td>{$transaction['state']}</td>";
            echo "<td>\${$transaction['amount']}</td>";
            echo "<td><button onclick='testStateChange({$transaction['id']}, \"2\")'>Test Change to State 2</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No transactions found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
}
?>

<h3>3. Direct State Change Test</h3>
<form id="direct-test-form">
    <p>
        <label>Transaction ID: <input type="number" id="test-transaction-id" required></label>
    </p>
    <p>
        <label>New State: 
            <select id="test-new-state" required>
                <option value="">Select State</option>
                <option value="1">1 - Submitted</option>
                <option value="2">2 - Under Review</option>
                <option value="3">3 - Processing</option>
                <option value="4">4 - Sent Back</option>
                <option value="5">5 - Sent to Payroll</option>
                <option value="6">6 - Paid</option>
            </select>
        </label>
    </p>
    <p>
        <button type="submit">Test State Change</button>
    </p>
</form>

<h3>4. Test Actual Endpoint</h3>
<button onclick="testActualEndpoint()">Test Real changeTransactionState.php</button>

<h3>5. CSRF Token Test</h3>
<p>Current CSRF Token: <code><?php echo Security::generateCSRFToken(); ?></code></p>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function testStateChange(transactionId, newState) {
    const formData = new FormData();
    formData.append('test_state_change', '1');
    formData.append('transaction_id', transactionId);
    formData.append('new_state', newState);
    
    fetch('test_state_change.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('test-results');
        if (data.success) {
            resultDiv.innerHTML = '<p class="success">✅ ' + data.message + '</p>';
        } else {
            resultDiv.innerHTML = '<p class="error">❌ ' + data.message + '</p>';
        }
    })
    .catch(error => {
        document.getElementById('test-results').innerHTML = '<p class="error">❌ Error: ' + error.message + '</p>';
    });
}

document.getElementById('direct-test-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const transactionId = document.getElementById('test-transaction-id').value;
    const newState = document.getElementById('test-new-state').value;
    
    testStateChange(transactionId, newState);
});

function testActualEndpoint() {
    const transactionId = document.getElementById('test-transaction-id').value || '1';
    const newState = document.getElementById('test-new-state').value || '2';
    
    const formData = new FormData();
    formData.append('csrf_token', '<?php echo Security::generateCSRFToken(); ?>');
    formData.append('action', 'change_state');
    formData.append('transaction_id', transactionId);
    formData.append('new_state', newState);
    formData.append('review_comments', 'Test state change');
    
    fetch('model/changeTransactionState.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            const resultDiv = document.getElementById('test-results');
            if (data.success) {
                resultDiv.innerHTML = '<p class="success">✅ Real endpoint: ' + data.message + '</p>';
            } else {
                resultDiv.innerHTML = '<p class="error">❌ Real endpoint: ' + data.message + '</p>';
            }
        } catch (e) {
            document.getElementById('test-results').innerHTML = '<p class="error">❌ Real endpoint response not JSON: ' + text.substring(0, 200) + '</p>';
        }
    })
    .catch(error => {
        document.getElementById('test-results').innerHTML = '<p class="error">❌ Real endpoint error: ' + error.message + '</p>';
    });
}

// Auto-test on page load if user is logged in
<?php if (isset($_SESSION['user_id'])): ?>
window.addEventListener('load', function() {
    console.log('User logged in, ready for testing');
});
<?php endif; ?>
</script>

<h3>6. Manual Testing Steps</h3>
<ol>
    <li><strong>Check Session:</strong> Ensure you're logged in as a finance user</li>
    <li><strong>Test Database:</strong> Use the table above to test state changes</li>
    <li><strong>Test Direct Form:</strong> Use the form to test specific state changes</li>
    <li><strong>Test Real Endpoint:</strong> Click the button to test the actual endpoint</li>
    <li><strong>Check Browser Console:</strong> Look for any JavaScript errors</li>
    <li><strong>Check Network Tab:</strong> Monitor AJAX requests and responses</li>
</ol>

<h3>7. Debugging Information</h3>
<p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
<p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
<p><strong>Script Path:</strong> <?php echo __FILE__; ?></p>
<p><strong>Working Directory:</strong> <?php echo getcwd(); ?></p>

<?php if (isset($_SESSION['user_id'])): ?>
<h3>8. Quick Finance Page Test</h3>
<p><a href="finance/transactions.php" target="_blank">Open Finance Transactions Page</a></p>
<p><a href="user/transactions.php" target="_blank">Open User Transactions Page</a></p>
<?php endif; ?>
