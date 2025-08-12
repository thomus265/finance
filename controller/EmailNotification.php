<?php
require_once('../vendor/autoload.php');
require_once('SmtpConfigManager.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotification {
    private $mailer;
    private $db;
    private $emailEnabled;

    public function __construct($database) {
        $this->db = $database;
        $this->checkEmailEnabled();

        if ($this->emailEnabled) {
            $this->initializeMailer();
        } else {
            error_log("Email notifications are disabled - skipping mailer initialization");
        }
    }

    /**
     * Check if email notifications are enabled
     */
    private function checkEmailEnabled() {
        try {
            require_once(__DIR__ . '/smtp_config.php');
            $config = getSmtpConfig();
            $this->emailEnabled = $config['enabled'] ?? true;
        } catch (Exception $e) {
            error_log("Error checking email configuration: " . $e->getMessage());
            $this->emailEnabled = false; // Disable email on configuration errors
        }
    }

    /**
     * Initialize PHPMailer using SmtpConfigManager
     */
    private function initializeMailer() {
        try {
            // Use SmtpConfigManager to create a configured PHPMailer instance
            $this->mailer = SmtpConfigManager::createMailer();

            error_log("EmailNotification: PHPMailer initialized successfully using SmtpConfigManager");

        } catch (Exception $e) {
            error_log("EmailNotification: Failed to initialize PHPMailer: " . $e->getMessage());
            throw new Exception("Failed to initialize email system: " . $e->getMessage());
        }
    }
    
    /**
     * Send transaction status change notification
     */
    public function sendTransactionStatusNotification($user_email, $user_name, $transaction_data, $new_status, $comments = '') {
        // Check if email notifications are enabled
        if (!$this->emailEnabled) {
            error_log("Email notification skipped - email notifications are disabled");
            return true; // Return true to not break the workflow
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user_email, $user_name);
            
            $subject = $this->getEmailSubject($new_status, $transaction_data['transaction_id']);
            $body = $this->getEmailBody($user_name, $transaction_data, $new_status, $comments);
            
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            $result = $this->mailer->send();
            
            if ($result) {
                // Log email sent
                $this->logEmailSent($user_email, $subject, $transaction_data['id']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send bulk notifications to finance team
     */
    public function sendFinanceTeamNotification($transaction_data, $notification_type, $user_name) {
        try {
            // Get finance team emails
            $finance_team = $this->db->query("
                SELECT user_email, user_name 
                FROM users 
                WHERE user_type = 'finance' AND user_email IS NOT NULL
            ")->fetchAll();
            
            if (empty($finance_team)) {
                return false;
            }
            
            $subject = $this->getFinanceEmailSubject($notification_type, $transaction_data['transaction_id']);
            $body = $this->getFinanceEmailBody($transaction_data, $notification_type, $user_name);
            
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            $sent_count = 0;
            foreach ($finance_team as $finance_user) {
                try {
                    $this->mailer->clearAddresses();
                    $this->mailer->addAddress($finance_user['user_email'], $finance_user['user_name']);
                    
                    if ($this->mailer->send()) {
                        $sent_count++;
                        $this->logEmailSent($finance_user['user_email'], $subject, $transaction_data['id']);
                    }
                } catch (Exception $e) {
                    error_log("Error sending to finance user {$finance_user['user_email']}: " . $e->getMessage());
                }
            }
            
            return $sent_count > 0;
            
        } catch (Exception $e) {
            error_log("Finance team notification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEmailSubject($status, $transaction_id) {
        $subjects = [
            '1' => "Transaction $transaction_id - Submitted for Review",
            '2' => "Transaction $transaction_id - Under Review",
            '3' => "Transaction $transaction_id - Processing",
            '4' => "Transaction $transaction_id - Sent Back for Corrections",
            '5' => "Transaction $transaction_id - Sent to Payroll",
            '6' => "Transaction $transaction_id - Payment Completed"
        ];
        
        return $subjects[$status] ?? "Transaction $transaction_id - Status Update";
    }
    
    private function getFinanceEmailSubject($type, $transaction_id) {
        $subjects = [
            'new_submission' => "New Transaction Submitted - $transaction_id",
            'resubmission' => "Transaction Resubmitted - $transaction_id",
            'requires_attention' => "Transaction Requires Attention - $transaction_id"
        ];
        
        return $subjects[$type] ?? "Transaction Update - $transaction_id";
    }
    
    private function getEmailBody($user_name, $transaction_data, $new_status, $comments = '') {
        $status_messages = [
            '1' => 'Your transaction has been submitted and is awaiting review by our finance team.',
            '2' => 'Your transaction is currently under review by our finance team.',
            '3' => 'Your transaction has been approved and is being processed.',
            '4' => 'Your transaction has been sent back for corrections. Please review the comments and resubmit.',
            '5' => 'Your transaction has been approved and sent to payroll for processing.',
            '6' => 'Your transaction has been completed and payment has been processed.'
        ];
        
        $status_message = $status_messages[$new_status] ?? 'Your transaction status has been updated.';
        
        $next_steps = '';
        if ($new_status == '4') {
            $next_steps = '<p><strong>Next Steps:</strong> Please log in to your account, review the comments, make necessary corrections, and resubmit your transaction.</p>';
        } elseif ($new_status == '6') {
            $next_steps = '<p><strong>Next Steps:</strong> No further action is required. Your payment should be processed according to your organization\'s payment schedule.</p>';
        }
        
        $comments_section = '';
        if (!empty($comments)) {
            $comments_section = "
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>
                    <h4 style='color: #495057; margin-top: 0;'>Comments:</h4>
                    <p style='margin-bottom: 0;'>" . nl2br(htmlspecialchars($comments)) . "</p>
                </div>
            ";
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #ffffff; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #6c757d; }
                .transaction-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Transaction Status Update</h1>
                </div>
                <div class='content'>
                    <p>Dear $user_name,</p>
                    
                    <p>$status_message</p>
                    
                    <div class='transaction-details'>
                        <h3>Transaction Details:</h3>
                        <p><strong>Transaction ID:</strong> {$transaction_data['transaction_id']}</p>
                        <p><strong>Title:</strong> " . htmlspecialchars($transaction_data['title']) . "</p>
                        <p><strong>Amount:</strong> $" . number_format($transaction_data['amount'], 2) . "</p>
                        <p><strong>Status:</strong> " . $this->getStatusName($new_status) . "</p>
                    </div>
                    
                    $comments_section
                    
                    $next_steps
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . $this->getBaseUrl() . "/user_transactions/{$transaction_data['id']}' class='btn'>View Transaction Details</a>
                    </p>
                    
                    <p>If you have any questions, please contact our finance team.</p>
                    
                    <p>Best regards,<br>Finance Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>© " . date('Y') . " Finance Transaction System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getFinanceEmailBody($transaction_data, $type, $user_name) {
        $type_messages = [
            'new_submission' => "A new transaction has been submitted by $user_name and requires your review.",
            'resubmission' => "A transaction has been resubmitted by $user_name after corrections.",
            'requires_attention' => "A transaction requires your immediate attention."
        ];
        
        $message = $type_messages[$type] ?? "A transaction update requires your attention.";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #ffffff; }
                .transaction-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Finance Team Notification</h1>
                </div>
                <div class='content'>
                    <p>Dear Finance Team,</p>
                    
                    <p>$message</p>
                    
                    <div class='transaction-details'>
                        <h3>Transaction Details:</h3>
                        <p><strong>Transaction ID:</strong> {$transaction_data['transaction_id']}</p>
                        <p><strong>Title:</strong> " . htmlspecialchars($transaction_data['title']) . "</p>
                        <p><strong>Amount:</strong> $" . number_format($transaction_data['amount'], 2) . "</p>
                        <p><strong>Submitted by:</strong> $user_name</p>
                        <p><strong>Submitted on:</strong> " . date('M j, Y g:i A', strtotime($transaction_data['tr_date'])) . "</p>
                    </div>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . $this->getBaseUrl() . "/finance_transactions/{$transaction_data['id']}' class='btn'>Review Transaction</a>
                    </p>
                    
                    <p>Please review this transaction at your earliest convenience.</p>
                    
                    <p>Best regards,<br>Finance Transaction System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getStatusName($status) {
        $names = [
            '1' => 'Submitted for Review',
            '2' => 'Under Review',
            '3' => 'Processing',
            '4' => 'Sent Back',
            '5' => 'Sent to Payroll',
            '6' => 'Completed'
        ];
        
        return $names[$status] ?? 'Unknown';
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host";
    }
    
    /**
     * Send custom notification email
     */
    public function sendCustomNotification($to_email, $to_name, $subject, $message) {
        // Check if email notifications are enabled
        if (!$this->emailEnabled) {
            error_log("Custom email notification skipped - email notifications are disabled");
            return true; // Return true to not break the workflow
        }

        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();

            // Set recipient
            $this->mailer->addAddress($to_email, $to_name);

            // Set subject
            $this->mailer->Subject = $subject;

            // Create email body
            $email_body = $this->createCustomEmailTemplate($subject, $message);
            $this->mailer->Body = $email_body;

            // Send email
            $result = $this->mailer->send();

            // Log the email
            $this->logEmailSent($to_email, $subject, 'CUSTOM');

            return $result;

        } catch (Exception $e) {
            error_log("Custom notification email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create custom email template
     */
    private function createCustomEmailTemplate($subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$subject</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Finance Transaction System</h1>
                </div>
                <div class='content'>
                    <h2>$subject</h2>
                    <p>$message</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Finance Transaction System.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function logEmailSent($email, $subject, $transaction_id) {
        try {
            // Check if email_log table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'email_log'")->fetchArray();

            if (!$tableExists) {
                error_log("Email logging skipped: email_log table does not exist. Run database/create_email_log_table.php to create it.");
                return;
            }

            $this->db->query("
                INSERT INTO email_log (recipient_email, subject, transaction_id, sent_at, status)
                VALUES (?, ?, ?, NOW(), 'sent')
            ", $email, $subject, $transaction_id);

        } catch (Exception $e) {
            error_log("Error logging email: " . $e->getMessage());

            // If it's a table doesn't exist error, provide helpful message
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log("SOLUTION: Run database/create_email_log_table.php to create the missing email_log table");
            }
        }
    }
}
