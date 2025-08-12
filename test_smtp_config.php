<?php
/**
 * SMTP Configuration Test Script
 * 
 * This script tests the SMTP configuration and email functionality
 */

require_once('controller/main.php');

echo "<h1>SMTP Configuration Test</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
.config-box { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
</style>";

// Test 1: Configuration Loading
echo "<h2>1. Configuration Loading Test</h2>";
test_config_loading();

// Test 2: Configuration Validation
echo "<h2>2. Configuration Validation Test</h2>";
test_config_validation();

// Test 3: SMTP Connection Test
echo "<h2>3. SMTP Connection Test</h2>";
test_smtp_connection();

// Test 4: Email Notification Class Test
echo "<h2>4. Email Notification Class Test</h2>";
test_email_notification_class();

// Test 5: Environment Variables Test
echo "<h2>5. Environment Variables Test</h2>";
test_environment_variables();

function test_config_loading() {
    try {
        require_once('controller/SmtpConfigManager.php');
        
        echo "<p class='info'>Testing SmtpConfigManager...</p>";
        
        $config = SmtpConfigManager::getConfig();
        echo "<p class='success'>✅ Configuration loaded successfully</p>";
        
        $summary = SmtpConfigManager::getConfigSummary();
        echo "<div class='config-box'>";
        echo "<h3>Configuration Summary:</h3>";
        echo "<ul>";
        echo "<li><strong>Host:</strong> " . ($summary['host'] ?? 'Not set') . "</li>";
        echo "<li><strong>Port:</strong> " . ($summary['port'] ?? 'Not set') . "</li>";
        echo "<li><strong>Encryption:</strong> " . ($summary['encryption'] ?? 'None') . "</li>";
        echo "<li><strong>From Email:</strong> " . ($summary['from_email'] ?? 'Not set') . "</li>";
        echo "<li><strong>From Name:</strong> " . ($summary['from_name'] ?? 'Not set') . "</li>";
        echo "<li><strong>Auth Enabled:</strong> " . ($summary['auth_enabled'] ? 'Yes' : 'No') . "</li>";
        echo "<li><strong>Username Set:</strong> " . ($summary['username_set'] ? 'Yes' : 'No') . "</li>";
        echo "<li><strong>Password Set:</strong> " . ($summary['password_set'] ? 'Yes' : 'No') . "</li>";
        echo "<li><strong>Config File:</strong> " . ($summary['config_file'] ?? 'Unknown') . "</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Configuration loading failed: " . $e->getMessage() . "</p>";
    }
}

function test_config_validation() {
    try {
        $config = SmtpConfigManager::getConfig();
        $validation = SmtpConfigManager::validateConfig($config);
        
        if ($validation['valid']) {
            echo "<p class='success'>✅ Configuration validation passed</p>";
        } else {
            echo "<p class='error'>❌ Configuration validation failed:</p>";
            echo "<ul>";
            foreach ($validation['errors'] as $error) {
                echo "<li class='error'>$error</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Configuration validation error: " . $e->getMessage() . "</p>";
    }
}

function test_smtp_connection() {
    try {
        echo "<p class='info'>Testing SMTP connection...</p>";
        
        $result = SmtpConfigManager::testConnection();
        
        if ($result['success']) {
            echo "<p class='success'>✅ " . $result['message'] . "</p>";
        } else {
            echo "<p class='error'>❌ " . $result['message'] . "</p>";
            echo "<p class='warning'>⚠️ This may be due to incorrect credentials or network issues</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ SMTP connection test error: " . $e->getMessage() . "</p>";
    }
}

function test_email_notification_class() {
    try {
        echo "<p class='info'>Testing EmailNotification class...</p>";
        
        global $db;
        $emailNotification = new EmailNotification($db);
        echo "<p class='success'>✅ EmailNotification class instantiated successfully</p>";
        
        // Test if we can get the mailer instance (reflection)
        $reflection = new ReflectionClass($emailNotification);
        $mailerProperty = $reflection->getProperty('mailer');
        $mailerProperty->setAccessible(true);
        $mailer = $mailerProperty->getValue($emailNotification);
        
        if ($mailer instanceof PHPMailer\PHPMailer\PHPMailer) {
            echo "<p class='success'>✅ PHPMailer instance created successfully</p>";
            echo "<p class='info'>Mailer Host: " . $mailer->Host . "</p>";
            echo "<p class='info'>Mailer Port: " . $mailer->Port . "</p>";
        } else {
            echo "<p class='error'>❌ PHPMailer instance not created properly</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ EmailNotification class test failed: " . $e->getMessage() . "</p>";
    }
}

function test_environment_variables() {
    echo "<p class='info'>Checking environment variables...</p>";
    
    $envVars = [
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_USERNAME',
        'SMTP_PASSWORD',
        'SMTP_FROM_EMAIL',
        'SMTP_FROM_NAME',
        'SMTP_ENCRYPTION'
    ];
    
    $envFound = false;
    echo "<ul>";
    foreach ($envVars as $var) {
        $value = getenv($var) ?: $_ENV[$var] ?? null;
        if ($value !== null) {
            $envFound = true;
            if (strpos($var, 'PASSWORD') !== false) {
                echo "<li class='success'>$var: [HIDDEN]</li>";
            } else {
                echo "<li class='success'>$var: $value</li>";
            }
        } else {
            echo "<li class='warning'>$var: Not set</li>";
        }
    }
    echo "</ul>";
    
    if ($envFound) {
        echo "<p class='success'>✅ Environment variables are being used</p>";
    } else {
        echo "<p class='warning'>⚠️ No environment variables found - using default configuration</p>";
    }
    
    // Check for .env file
    if (file_exists('.env')) {
        echo "<p class='success'>✅ .env file found</p>";
    } else {
        echo "<p class='warning'>⚠️ .env file not found - copy .env.example to .env and configure</p>";
    }
}

echo "<h2>6. Setup Instructions</h2>";
echo "<div class='config-box'>";
echo "<h3>To configure SMTP settings:</h3>";
echo "<ol>";
echo "<li><strong>Copy environment file:</strong> <code>cp .env.example .env</code></li>";
echo "<li><strong>Edit .env file:</strong> Update SMTP settings with your email provider details</li>";
echo "<li><strong>For Gmail:</strong> Use app passwords instead of regular passwords</li>";
echo "<li><strong>Test configuration:</strong> Run this script to verify settings</li>";
echo "</ol>";

echo "<h3>Gmail Setup (Recommended for development):</h3>";
echo "<ol>";
echo "<li>Enable 2-factor authentication on your Gmail account</li>";
echo "<li>Generate an app password: Google Account → Security → App passwords</li>";
echo "<li>Use the app password in SMTP_PASSWORD (not your regular password)</li>";
echo "<li>Set SMTP_HOST=smtp.gmail.com, SMTP_PORT=587, SMTP_ENCRYPTION=tls</li>";
echo "</ol>";

echo "<h3>Alternative: Direct configuration:</h3>";
echo "<p>Edit <code>controller/smtp_config.php</code> directly (not recommended for production)</p>";
echo "</div>";

echo "<h2>7. Security Notes</h2>";
echo "<div class='config-box'>";
echo "<ul>";
echo "<li class='warning'>⚠️ Never commit .env file to version control</li>";
echo "<li class='warning'>⚠️ Use environment variables in production</li>";
echo "<li class='warning'>⚠️ Use app passwords for Gmail, not regular passwords</li>";
echo "<li class='success'>✅ Configuration is loaded from centralized smtp_config.php</li>";
echo "<li class='success'>✅ Sensitive data can be stored in environment variables</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>SMTP configuration test completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
