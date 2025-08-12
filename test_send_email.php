<?php
/**
 * Email Sending Test Script
 * 
 * This script tests actual email sending functionality
 */

require_once('controller/main.php');

echo "<h1>Email Sending Test</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.form-box { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 10px 0; }
</style>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_email'])) {
    send_test_email();
}

// Display test form
display_test_form();

function send_test_email() {
    try {
        $to_email = $_POST['to_email'] ?? '';
        $subject = $_POST['subject'] ?? 'Test Email from Finance System';
        $message = $_POST['message'] ?? 'This is a test email to verify SMTP configuration.';
        
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please provide a valid email address");
        }
        
        echo "<h2>Sending Test Email...</h2>";
        echo "<p class='info'>To: $to_email</p>";
        echo "<p class='info'>Subject: $subject</p>";
        
        // Test using EmailNotification class
        global $db;
        $emailNotification = new EmailNotification($db);
        
        // Create a test transaction data array
        $test_transaction = [
            'id' => 'TEST-001',
            'transaction_id' => 'TEST-001',
            'name' => 'Test Transaction',
            'amount' => 100.00,
            'state' => 2
        ];
        
        $result = $emailNotification->sendTransactionStatusNotification(
            $to_email,
            'Test User',
            $test_transaction,
            2, // Under Review status
            'This is a test email to verify SMTP configuration.'
        );
        
        if ($result) {
            echo "<p class='success'>✅ Test email sent successfully!</p>";
            echo "<p class='info'>Check your inbox (and spam folder) for the test email.</p>";
        } else {
            echo "<p class='error'>❌ Failed to send test email</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Email sending failed: " . $e->getMessage() . "</p>";
    }
}

function display_test_form() {
    echo "<h2>Send Test Email</h2>";
    echo "<div class='form-box'>";
    echo "<form method='POST'>";
    echo "<p>";
    echo "<label for='to_email'><strong>To Email Address:</strong></label><br>";
    echo "<input type='email' id='to_email' name='to_email' required style='width: 300px; padding: 5px;' placeholder='test@example.com'>";
    echo "</p>";
    
    echo "<p>";
    echo "<label for='subject'><strong>Subject:</strong></label><br>";
    echo "<input type='text' id='subject' name='subject' value='Test Email from Finance System' style='width: 400px; padding: 5px;'>";
    echo "</p>";
    
    echo "<p>";
    echo "<label for='message'><strong>Additional Message:</strong></label><br>";
    echo "<textarea id='message' name='message' rows='4' style='width: 400px; padding: 5px;'>This is a test email to verify SMTP configuration.</textarea>";
    echo "</p>";
    
    echo "<p>";
    echo "<button type='submit' name='send_test_email' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;'>Send Test Email</button>";
    echo "</p>";
    echo "</form>";
    echo "</div>";
}

echo "<h2>Pre-Send Checks</h2>";

// Check SMTP configuration
try {
    require_once('controller/SmtpConfigManager.php');
    $summary = SmtpConfigManager::getConfigSummary();
    
    echo "<h3>SMTP Configuration Status:</h3>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . ($summary['host'] ?? 'Not set') . "</li>";
    echo "<li><strong>Port:</strong> " . ($summary['port'] ?? 'Not set') . "</li>";
    echo "<li><strong>From Email:</strong> " . ($summary['from_email'] ?? 'Not set') . "</li>";
    echo "<li><strong>Username Set:</strong> " . ($summary['username_set'] ? '✅ Yes' : '❌ No') . "</li>";
    echo "<li><strong>Password Set:</strong> " . ($summary['password_set'] ? '✅ Yes' : '❌ No') . "</li>";
    echo "</ul>";
    
    if (!$summary['username_set'] || !$summary['password_set']) {
        echo "<p class='error'>❌ SMTP credentials not configured. Please set up your .env file or update smtp_config.php</p>";
    } else {
        echo "<p class='success'>✅ SMTP configuration appears to be set up</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Configuration error: " . $e->getMessage() . "</p>";
}

// Check EmailNotification class
try {
    global $db;
    $emailNotification = new EmailNotification($db);
    echo "<p class='success'>✅ EmailNotification class loaded successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ EmailNotification class error: " . $e->getMessage() . "</p>";
}

echo "<h2>Troubleshooting Tips</h2>";
echo "<div class='form-box'>";
echo "<h3>If email sending fails:</h3>";
echo "<ul>";
echo "<li><strong>Gmail users:</strong> Make sure you're using an app password, not your regular password</li>";
echo "<li><strong>2FA required:</strong> Most email providers require 2-factor authentication for app passwords</li>";
echo "<li><strong>Firewall:</strong> Check if your server can connect to SMTP ports (587, 465, 25)</li>";
echo "<li><strong>Credentials:</strong> Verify username and password are correct</li>";
echo "<li><strong>Host/Port:</strong> Confirm SMTP host and port settings for your email provider</li>";
echo "</ul>";

echo "<h3>Common SMTP Settings:</h3>";
echo "<ul>";
echo "<li><strong>Gmail:</strong> smtp.gmail.com:587 (TLS) or smtp.gmail.com:465 (SSL)</li>";
echo "<li><strong>Outlook:</strong> smtp-mail.outlook.com:587 (TLS)</li>";
echo "<li><strong>Yahoo:</strong> smtp.mail.yahoo.com:587 (TLS)</li>";
echo "<li><strong>Custom:</strong> Check with your email provider for SMTP settings</li>";
echo "</ul>";

echo "<h3>Debug Steps:</h3>";
echo "<ol>";
echo "<li>Run <a href='test_smtp_config.php'>SMTP Configuration Test</a> first</li>";
echo "<li>Check server error logs for detailed error messages</li>";
echo "<li>Enable SMTP debug mode by setting SMTP_DEBUG=2 in .env</li>";
echo "<li>Test with a simple email client to verify credentials</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Email sending test interface loaded at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
