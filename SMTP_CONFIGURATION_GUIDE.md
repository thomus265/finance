# SMTP Configuration Guide

## Overview

This guide explains how to configure SMTP settings for the Finance Transaction System's email notifications. The system uses a centralized configuration approach with support for environment variables and secure credential management.

## Architecture

### Configuration Files

1. **`controller/smtp_config.php`** - Main configuration file with environment variable support
2. **`controller/SmtpConfigManager.php`** - Configuration management utility class
3. **`controller/EmailNotification.php`** - Email notification service
4. **`.env`** - Environment variables file (not in version control)
5. **`.env.example`** - Template for environment variables

### Security Features

- ✅ Environment variable support
- ✅ Centralized configuration management
- ✅ Credential validation
- ✅ Configuration file excluded from version control
- ✅ Secure fallback mechanisms

## Quick Setup

### 1. Copy Environment File
```bash
cp .env.example .env
```

### 2. Configure SMTP Settings
Edit the `.env` file with your email provider settings:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=Finance Transaction System
```

### 3. Test Configuration
```bash
# Open in browser
http://your-domain/test_smtp_config.php
```

## Email Provider Settings

### Gmail (Recommended for Development)

**Requirements:**
- 2-factor authentication enabled
- App password generated (not regular password)

**Settings:**
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-character-app-password
```

**Setup Steps:**
1. Enable 2FA on your Google account
2. Go to Google Account → Security → App passwords
3. Generate app password for "Mail"
4. Use the 16-character password (remove spaces)

### Outlook/Hotmail

```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
```

### Yahoo Mail

```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@yahoo.com
SMTP_PASSWORD=your-app-password
```

### Custom SMTP Server

```env
SMTP_HOST=mail.yourdomain.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@yourdomain.com
SMTP_PASSWORD=your-password
```

## Configuration Options

### Required Settings

| Variable | Description | Example |
|----------|-------------|---------|
| `SMTP_HOST` | SMTP server hostname | `smtp.gmail.com` |
| `SMTP_PORT` | SMTP server port | `587` |
| `SMTP_USERNAME` | SMTP username (usually email) | `user@gmail.com` |
| `SMTP_PASSWORD` | SMTP password or app password | `abcd1234efgh5678` |
| `SMTP_FROM_EMAIL` | Default sender email | `noreply@company.com` |

### Optional Settings

| Variable | Description | Default | Options |
|----------|-------------|---------|---------|
| `SMTP_ENCRYPTION` | Encryption method | `tls` | `tls`, `ssl`, `none` |
| `SMTP_AUTH` | Enable authentication | `true` | `true`, `false` |
| `SMTP_FROM_NAME` | Default sender name | `Finance Transaction System` | Any string |
| `SMTP_DEBUG` | Debug level | `0` | `0-4` |
| `SMTP_TIMEOUT` | Connection timeout | `30` | Seconds |

## Usage in Code

### Using EmailNotification Class

```php
// Initialize with database connection
$emailNotification = new EmailNotification($db);

// Send transaction notification
$result = $emailNotification->sendTransactionStatusNotification(
    $user_email,
    $user_name,
    $transaction_data,
    $new_status,
    $comments
);
```

### Using SmtpConfigManager Directly

```php
// Get configuration
$config = SmtpConfigManager::getConfig();

// Test connection
$result = SmtpConfigManager::testConnection();

// Create configured PHPMailer instance
$mailer = SmtpConfigManager::createMailer();
```

## Testing

### Configuration Test
```bash
http://your-domain/test_smtp_config.php
```

### Email Sending Test
```bash
http://your-domain/test_send_email.php
```

### Manual Testing
```php
// Test configuration loading
$config = SmtpConfigManager::getConfig();

// Test connection
$result = SmtpConfigManager::testConnection();
if ($result['success']) {
    echo "SMTP connection successful";
} else {
    echo "Error: " . $result['message'];
}
```

## Troubleshooting

### Common Issues

#### 1. Authentication Failed
- **Gmail:** Use app password, not regular password
- **2FA:** Enable 2-factor authentication first
- **Credentials:** Verify username and password are correct

#### 2. Connection Timeout
- **Firewall:** Check if SMTP ports are blocked
- **Host:** Verify SMTP host is correct
- **Port:** Try alternative ports (465 for SSL, 25 for unencrypted)

#### 3. SSL/TLS Errors
- **Encryption:** Try different encryption settings
- **Port:** Match encryption with correct port
- **Certificates:** Check SSL certificate validity

### Debug Mode

Enable debug mode for detailed SMTP logs:

```env
SMTP_DEBUG=2
```

Debug levels:
- `0` - No debug output
- `1` - Client messages
- `2` - Client and server messages
- `3` - Client, server, and connection status
- `4` - Low-level data output

### Log Files

Check these locations for error messages:
- PHP error log
- Web server error log
- Application logs in `error_log()` calls

## Security Best Practices

### 1. Environment Variables
- ✅ Use `.env` file for local development
- ✅ Use server environment variables for production
- ✅ Never commit `.env` to version control

### 2. Credentials
- ✅ Use app passwords instead of regular passwords
- ✅ Enable 2-factor authentication
- ✅ Rotate passwords regularly
- ✅ Use dedicated email accounts for applications

### 3. Configuration
- ✅ Validate all configuration values
- ✅ Use secure encryption (TLS/SSL)
- ✅ Set appropriate timeouts
- ✅ Log configuration errors

## Production Deployment

### 1. Environment Variables
Set environment variables on your server:

```bash
export SMTP_HOST=smtp.gmail.com
export SMTP_PORT=587
export SMTP_USERNAME=app@company.com
export SMTP_PASSWORD=secure-app-password
```

### 2. Configuration Validation
Ensure configuration is valid before deployment:

```php
$validation = SmtpConfigManager::validateConfig($config);
if (!$validation['valid']) {
    throw new Exception('Invalid SMTP configuration');
}
```

### 3. Connection Testing
Test SMTP connection during deployment:

```php
$result = SmtpConfigManager::testConnection();
if (!$result['success']) {
    error_log('SMTP connection failed: ' . $result['message']);
}
```

## Support

### Files to Check
1. `controller/smtp_config.php` - Main configuration
2. `controller/SmtpConfigManager.php` - Configuration manager
3. `controller/EmailNotification.php` - Email service
4. `.env` - Environment variables
5. Error logs - For debugging

### Test Scripts
1. `test_smtp_config.php` - Configuration testing
2. `test_send_email.php` - Email sending testing

### Common Commands
```bash
# Copy environment file
cp .env.example .env

# Check configuration
php -r "require 'controller/SmtpConfigManager.php'; var_dump(SmtpConfigManager::getConfig());"

# Test connection
php -r "require 'controller/SmtpConfigManager.php'; var_dump(SmtpConfigManager::testConnection());"
```
