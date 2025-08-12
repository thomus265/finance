<?php
/**
 * Email Notifications Toggle
 * 
 * Simple script to enable/disable email notifications system-wide
 */

require_once('controller/main.php');

// Check if user has permission (admin/finance only)
if (!isset($user) || $user->user_type < 2) {
    die('Access denied. Admin or Finance permissions required.');
}

echo "<h1>Email Notifications Toggle</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.status-box { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 15px 0; }
.toggle-box { background: #e9ecef; padding: 20px; border-radius: 5px; margin: 15px 0; }
</style>";

// Handle toggle action
if (isset($_POST['toggle_email'])) {
    $action = $_POST['action'];
    handleToggleAction($action);
}

// Get current status
$currentStatus = getCurrentEmailStatus();

echo "<div class='status-box'>";
echo "<h2>Current Email Notification Status</h2>";

if ($currentStatus['enabled']) {
    echo "<p class='success'>✅ Email notifications are ENABLED</p>";
    echo "<p class='info'>The system will send email notifications for:</p>";
    echo "<ul>";
    echo "<li>Transaction status changes</li>";
    echo "<li>New transaction submissions</li>";
    echo "<li>System notifications</li>";
    echo "</ul>";
} else {
    echo "<p class='warning'>⚠️ Email notifications are DISABLED</p>";
    echo "<p class='info'>The system is in fallback mode:</p>";
    echo "<ul>";
    echo "<li>No emails will be sent</li>";
    echo "<li>Notifications are logged but not emailed</li>";
    echo "<li>System functions normally otherwise</li>";
    echo "</ul>";
}

echo "<p><strong>Configuration source:</strong> {$currentStatus['source']}</p>";
echo "</div>";

echo "<div class='toggle-box'>";
echo "<h2>Toggle Email Notifications</h2>";

if ($currentStatus['enabled']) {
    echo "<p>Click below to <strong>disable</strong> email notifications:</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='disable'>";
    echo "<button type='submit' name='toggle_email' class='btn btn-warning' style='background: #ffc107; color: #000; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🔇 Disable Email Notifications";
    echo "</button>";
    echo "</form>";
    echo "<p class='info'><small>This will fall back to the pre-email notification state</small></p>";
} else {
    echo "<p>Click below to <strong>enable</strong> email notifications:</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='enable'>";
    echo "<button type='submit' name='toggle_email' class='btn btn-success' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🔔 Enable Email Notifications";
    echo "</button>";
    echo "</form>";
    echo "<p class='info'><small>Make sure SMTP settings are configured first</small></p>";
}

echo "</div>";

// Show configuration help
echo "<div class='status-box'>";
echo "<h2>Configuration Methods</h2>";
echo "<p>You can control email notifications in several ways:</p>";

echo "<h3>1. Environment Variable (Recommended)</h3>";
echo "<p>Set in your <code>.env</code> file:</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "# To disable email notifications\n";
echo "DISABLE_EMAIL_NOTIFICATIONS=true\n\n";
echo "# To enable email notifications\n";
echo "DISABLE_EMAIL_NOTIFICATIONS=false";
echo "</pre>";

echo "<h3>2. Server Environment Variable</h3>";
echo "<p>Set on your server:</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "export DISABLE_EMAIL_NOTIFICATIONS=true";
echo "</pre>";

echo "<h3>3. This Toggle Script</h3>";
echo "<p>Use this page to quickly enable/disable notifications.</p>";

echo "</div>";

// Show testing options
echo "<div class='status-box'>";
echo "<h2>Testing & Troubleshooting</h2>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='test_smtp_config.php' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>Test SMTP Config</a>";
echo "<a href='test_send_email.php' style='background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>Test Email Sending</a>";
echo "<a href='finance/dashboard.php' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Back to Dashboard</a>";
echo "</div>";
echo "</div>";

function getCurrentEmailStatus() {
    // Check environment variable
    $envDisabled = getenv('DISABLE_EMAIL_NOTIFICATIONS') ?: $_ENV['DISABLE_EMAIL_NOTIFICATIONS'] ?? 'false';
    $isDisabled = filter_var($envDisabled, FILTER_VALIDATE_BOOLEAN);
    
    $source = 'Default (enabled)';
    if (getenv('DISABLE_EMAIL_NOTIFICATIONS') !== false) {
        $source = 'Server environment variable';
    } elseif (isset($_ENV['DISABLE_EMAIL_NOTIFICATIONS'])) {
        $source = '.env file';
    }
    
    return [
        'enabled' => !$isDisabled,
        'source' => $source,
        'env_value' => $envDisabled
    ];
}

function handleToggleAction($action) {
    $envFile = '.env';
    
    // Read current .env file
    $envContent = '';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
    } elseif (file_exists('.env.example')) {
        // Copy from example if .env doesn't exist
        $envContent = file_get_contents('.env.example');
        echo "<p class='info'>Created .env file from .env.example</p>";
    }
    
    $newValue = ($action === 'disable') ? 'true' : 'false';
    
    // Update or add the DISABLE_EMAIL_NOTIFICATIONS setting
    if (strpos($envContent, 'DISABLE_EMAIL_NOTIFICATIONS=') !== false) {
        // Replace existing value
        $envContent = preg_replace(
            '/^DISABLE_EMAIL_NOTIFICATIONS=.*$/m',
            "DISABLE_EMAIL_NOTIFICATIONS=$newValue",
            $envContent
        );
        echo "<p class='success'>✅ Updated DISABLE_EMAIL_NOTIFICATIONS to $newValue</p>";
    } else {
        // Add new setting
        $envContent .= "\n# Email notifications toggle\nDISABLE_EMAIL_NOTIFICATIONS=$newValue\n";
        echo "<p class='success'>✅ Added DISABLE_EMAIL_NOTIFICATIONS=$newValue to .env file</p>";
    }
    
    // Write back to .env file
    if (file_put_contents($envFile, $envContent) !== false) {
        echo "<p class='success'>✅ .env file updated successfully</p>";
        
        // Update the current environment
        $_ENV['DISABLE_EMAIL_NOTIFICATIONS'] = $newValue;
        putenv("DISABLE_EMAIL_NOTIFICATIONS=$newValue");
        
        if ($action === 'disable') {
            echo "<p class='warning'>📧 Email notifications are now DISABLED</p>";
            echo "<p class='info'>The system has fallen back to pre-email notification state</p>";
        } else {
            echo "<p class='success'>📧 Email notifications are now ENABLED</p>";
            echo "<p class='info'>Make sure your SMTP settings are configured properly</p>";
        }
        
        echo "<p><a href='?' style='background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Refresh Status</a></p>";
        
    } else {
        echo "<p class='error'>❌ Failed to write to .env file. Check file permissions.</p>";
    }
}

echo "<p><strong>Email notification toggle completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
