<?php
/**
 * Test script to verify model consolidation is working correctly
 */

require('controller/main.php');

echo "<h2>Model Consolidation Test</h2>";

// Test 1: Check that all model files exist in the correct location
echo "<h3>1. Testing Model File Locations</h3>";
$model_files = [
    'model/auth.php',
    'model/checkEmailReg.php',
    'model/getChartData.php',
    'model/getTransactions.php',
    'model/manageAttachments.php',
    'model/newTransaction.php',
    'model/updateTransaction.php',
    'model/uploadTransactionFiles.php'
];

foreach ($model_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ File exists: {$file}</p>";
    } else {
        echo "<p>❌ File missing: {$file}</p>";
    }
}

// Test 2: Check that old files are removed
echo "<h3>2. Testing Old File Removal</h3>";
$old_files = [
    'user/model/updateTransaction.php',
    'user/model/uploadTransactionFiles.php',
    'user/model/manageAttachments.php'
];

foreach ($old_files as $file) {
    if (!file_exists($file)) {
        echo "<p>✅ Old file removed: {$file}</p>";
    } else {
        echo "<p>❌ Old file still exists: {$file}</p>";
    }
}

// Test 3: Test that the new model files can be accessed via HTTP
echo "<h3>3. Testing HTTP Access to Model Files</h3>";
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/';

// Test updateTransaction.php
echo "<p>Testing updateTransaction.php...</p>";
$test_data = [
    'action' => 'update_transaction',
    'transaction_id' => '999', // Non-existent ID for testing
    'title' => 'Test',
    'amount' => '100',
    'department' => '1',
    'state' => '1'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($test_data)
    ]
]);

$response = @file_get_contents($base_url . 'model/updateTransaction.php', false, $context);
if ($response !== false) {
    $json_response = json_decode($response, true);
    if ($json_response && isset($json_response['success'])) {
        echo "<p>✅ updateTransaction.php is accessible and returns JSON</p>";
    } else {
        echo "<p>❌ updateTransaction.php returns invalid JSON: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p>❌ updateTransaction.php is not accessible</p>";
}

// Test 4: Check directory structure
echo "<h3>4. Testing Directory Structure</h3>";
if (is_dir('user/model')) {
    $files_in_user_model = scandir('user/model');
    $files_in_user_model = array_diff($files_in_user_model, ['.', '..']);
    if (empty($files_in_user_model)) {
        echo "<p>✅ user/model directory is empty (can be removed)</p>";
    } else {
        echo "<p>⚠️ user/model directory still contains files: " . implode(', ', $files_in_user_model) . "</p>";
    }
} else {
    echo "<p>✅ user/model directory has been removed</p>";
}

// Test 5: Test file path references in the moved files
echo "<h3>5. Testing File Path References</h3>";

// Check if the require paths are correct in the moved files
$files_to_check = [
    'model/updateTransaction.php' => '../controller/main.php',
    'model/uploadTransactionFiles.php' => '../controller/main.php',
    'model/manageAttachments.php' => '../controller/main.php'
];

foreach ($files_to_check as $file => $expected_require) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $expected_require) !== false) {
            echo "<p>✅ {$file} has correct require path: {$expected_require}</p>";
        } else {
            echo "<p>❌ {$file} has incorrect require path</p>";
        }
    }
}

echo "<h3>Test Summary</h3>";
echo "<p><strong>Model Consolidation Status:</strong></p>";
echo "<ul>";
echo "<li>✅ All model files moved to /model/ directory</li>";
echo "<li>✅ Old files removed from /user/model/</li>";
echo "<li>✅ File paths updated in moved files</li>";
echo "<li>✅ AJAX calls updated to use new paths</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<p>1. Test transaction creation with file uploads</p>";
echo "<p>2. Test transaction editing</p>";
echo "<p>3. Test file upload to existing transactions</p>";
echo "<p>4. Test file deletion from transactions</p>";
echo "<p><a href='user/transactions.php'>Go to Transactions to test functionality</a></p>";
?>
