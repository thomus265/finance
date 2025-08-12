<?php
/**
 * SMTP Configuration for Email Notifications
 *
 * This file provides centralized SMTP configuration management.
 * It supports environment variables for security and falls back to default values.
 *
 * SECURITY NOTE:
 * - Never commit actual credentials to version control
 * - Use environment variables for production
 * - Use .env files or server environment variables
 */

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = __DIR__ . '/../.env';
    if (is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
}

/**
 * Get SMTP configuration with environment variable support
 *
 * @return array SMTP configuration array
 */
function getSmtpConfig() {
    // Check if email notifications are disabled
    $email_disabled = getenv('DISABLE_EMAIL_NOTIFICATIONS') ?: $_ENV['DISABLE_EMAIL_NOTIFICATIONS'] ?? 'false';

    // Get configuration from environment variables with fallbacks
    $config = [
        'enabled' => !filter_var($email_disabled, FILTER_VALIDATE_BOOLEAN),
        'host' => getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'username' => getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME'] ?? 'thomus.lg@gmail.com',
        'password' => getenv('SMTP_PASSWORD') ?: $_ENV['SMTP_PASSWORD'] ?? 'wrlmoiewpitdhwdf',
        'port' => (int)(getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT'] ?? 587),
        'encryption' => getenv('SMTP_ENCRYPTION') ?: $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: $_ENV['SMTP_FROM_EMAIL'] ?? 'thomus.lg@gmail.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: $_ENV['SMTP_FROM_NAME'] ?? 'Finance Transaction System',
        'auth' => filter_var(
            getenv('SMTP_AUTH') ?: $_ENV['SMTP_AUTH'] ?? 'true',
            FILTER_VALIDATE_BOOLEAN
        ),
        'debug' => (int)(getenv('SMTP_DEBUG') ?: $_ENV['SMTP_DEBUG'] ?? 0),
        'timeout' => (int)(getenv('SMTP_TIMEOUT') ?: $_ENV['SMTP_TIMEOUT'] ?? 30),
    ];

    // Convert encryption string to PHPMailer constant
    switch (strtolower($config['encryption'])) {
        case 'ssl':
            $config['encryption'] = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            break;
        case 'tls':
        case 'starttls':
            $config['encryption'] = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            break;
        default:
            $config['encryption'] = '';
            break;
    }

    // Validate required configuration
    if (empty($config['username']) || empty($config['password'])) {
        error_log('SMTP Configuration Warning: Username or password not set. Email functionality may not work.');
    }

    if (empty($config['from_email'])) {
        error_log('SMTP Configuration Warning: From email not set. Using username as from email.');
        $config['from_email'] = $config['username'];
    }

    return $config;
}

/**
 * Validate SMTP configuration
 *
 * @param array $config SMTP configuration array
 * @return array Validation result with 'valid' boolean and 'errors' array
 */
function validateSmtpConfig($config) {
    $errors = [];

    if (empty($config['host'])) {
        $errors[] = 'SMTP host is required';
    }

    if (empty($config['username'])) {
        $errors[] = 'SMTP username is required';
    }

    if (empty($config['password'])) {
        $errors[] = 'SMTP password is required';
    }

    if (!is_numeric($config['port']) || $config['port'] < 1 || $config['port'] > 65535) {
        $errors[] = 'SMTP port must be a valid port number (1-65535)';
    }

    if (!filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'From email must be a valid email address';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// Return the configuration for backward compatibility
return getSmtpConfig();
