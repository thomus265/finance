<?php
/**
 * Email Configuration Status Component
 * 
 * Displays current email configuration status and provides quick access to configuration
 */

// Only show to admin users or when explicitly requested
$show_email_status = isset($_GET['show_email_status']) || 
                    (isset($user) && $user->user_type == 3); // Finance team

if (!$show_email_status) {
    return;
}

try {
    // Check if email notifications are disabled
    $email_disabled = getenv('DISABLE_EMAIL_NOTIFICATIONS') ?: $_ENV['DISABLE_EMAIL_NOTIFICATIONS'] ?? 'false';
    $emailNotificationsDisabled = filter_var($email_disabled, FILTER_VALIDATE_BOOLEAN);

    if ($emailNotificationsDisabled) {
        $statusClass = 'info';
        $statusIcon = 'info-circle';
        $statusText = 'Email Notifications Disabled';
        $statusMessage = 'System is in fallback mode (pre-email state)';
    } else {
        require_once(__DIR__ . '/../controller/SmtpConfigManager.php');
        $emailConfigSummary = SmtpConfigManager::getConfigSummary();
        $hasError = isset($emailConfigSummary['error']);

        if ($hasError) {
            $statusClass = 'danger';
            $statusIcon = 'exclamation-triangle';
            $statusText = 'Email Configuration Error';
            $statusMessage = $emailConfigSummary['error'];
        } else {
            $isConfigured = $emailConfigSummary['username_set'] && $emailConfigSummary['password_set'];

            if ($isConfigured) {
                $statusClass = 'success';
                $statusIcon = 'check-circle';
                $statusText = 'Email Configured';
                $statusMessage = "SMTP: {$emailConfigSummary['host']}:{$emailConfigSummary['port']}";
            } else {
                $statusClass = 'warning';
                $statusIcon = 'exclamation-circle';
                $statusText = 'Email Not Configured';
                $statusMessage = 'SMTP credentials not set';
            }
        }
    }
    
} catch (Exception $e) {
    $statusClass = 'danger';
    $statusIcon = 'exclamation-triangle';
    $statusText = 'Email System Error';
    $statusMessage = 'Unable to load email configuration';
}
?>

<div class="email-config-status mb-3">
    <div class="alert alert-<?php echo $statusClass; ?> alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-<?php echo $statusIcon; ?> me-2"></i>
            <div class="flex-grow-1">
                <strong><?php echo $statusText; ?></strong>
                <div class="small"><?php echo htmlspecialchars($statusMessage); ?></div>
                
                <?php if (!$hasError && isset($emailConfigSummary)): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            <strong>Configuration Details:</strong><br>
                            Host: <?php echo htmlspecialchars($emailConfigSummary['host']); ?><br>
                            Port: <?php echo htmlspecialchars($emailConfigSummary['port']); ?><br>
                            Encryption: <?php echo htmlspecialchars($emailConfigSummary['encryption'] ?: 'None'); ?><br>
                            From: <?php echo htmlspecialchars($emailConfigSummary['from_email']); ?><br>
                            Auth: <?php echo $emailConfigSummary['auth_enabled'] ? 'Enabled' : 'Disabled'; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ms-3">
                <div class="btn-group btn-group-sm" role="group">
                    <?php if ($emailNotificationsDisabled): ?>
                        <a href="/toggle_email_notifications.php" class="btn btn-outline-success">
                            <i class="bi bi-toggle-off"></i> Enable Email
                        </a>
                    <?php else: ?>
                        <a href="/test_smtp_config.php" class="btn btn-outline-<?php echo $statusClass; ?>" target="_blank">
                            <i class="bi bi-gear"></i> Test Config
                        </a>
                        <a href="/test_send_email.php" class="btn btn-outline-<?php echo $statusClass; ?>" target="_blank">
                            <i class="bi bi-envelope"></i> Test Email
                        </a>
                        <a href="/toggle_email_notifications.php" class="btn btn-outline-warning">
                            <i class="bi bi-toggle-on"></i> Disable Email
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    
    <?php if ($statusClass === 'warning' || $statusClass === 'danger'): ?>
        <div class="card border-<?php echo $statusClass; ?>">
            <div class="card-body">
                <h6 class="card-title text-<?php echo $statusClass; ?>">
                    <i class="bi bi-info-circle"></i> Email Configuration Setup
                </h6>
                <p class="card-text small">
                    Email notifications are currently not working properly. To fix this:
                </p>
                <ol class="small">
                    <li>Copy <code>.env.example</code> to <code>.env</code></li>
                    <li>Configure SMTP settings in the <code>.env</code> file</li>
                    <li>For Gmail: Enable 2FA and generate an app password</li>
                    <li>Test the configuration using the buttons above</li>
                </ol>
                
                <div class="mt-3">
                    <h6 class="text-<?php echo $statusClass; ?>">Quick Gmail Setup:</h6>
                    <div class="bg-light p-2 rounded">
                        <code class="small">
                            SMTP_HOST=smtp.gmail.com<br>
                            SMTP_PORT=587<br>
                            SMTP_ENCRYPTION=tls<br>
                            SMTP_USERNAME=your-email@gmail.com<br>
                            SMTP_PASSWORD=your-16-character-app-password<br>
                            SMTP_FROM_EMAIL=your-email@gmail.com<br>
                            SMTP_FROM_NAME=Finance Transaction System
                        </code>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.email-config-status .alert {
    border-left: 4px solid;
}

.email-config-status .alert-success {
    border-left-color: #198754;
}

.email-config-status .alert-warning {
    border-left-color: #ffc107;
}

.email-config-status .alert-danger {
    border-left-color: #dc3545;
}

.email-config-status code {
    font-size: 0.8em;
    line-height: 1.4;
}

.email-config-status .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

@media (max-width: 768px) {
    .email-config-status .btn-group {
        flex-direction: column;
    }
    
    .email-config-status .btn-group .btn {
        margin-bottom: 0.25rem;
    }
}
</style>

<script>
// Auto-hide success alerts after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlerts = document.querySelectorAll('.email-config-status .alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 10000);
    });
});

// Test email configuration
function testEmailConfig() {
    const button = event.target;
    setButtonLoading(button, 'Testing...');
    
    fetch('/test_smtp_config.php')
        .then(response => response.text())
        .then(html => {
            // Open test results in new window
            const testWindow = window.open('', '_blank');
            testWindow.document.write(html);
            testWindow.document.close();
        })
        .catch(error => {
            console.error('Test failed:', error);
            alert('Failed to run email configuration test');
        })
        .finally(() => {
            removeButtonLoading(button);
        });
}
</script>
