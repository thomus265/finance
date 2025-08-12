<?php
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Create a new notification
     */
    public function create_notification($user_id, $transaction_id, $type, $title, $message) {
        return $this->db->query("
            INSERT INTO notifications (user_id, transaction_id, type, title, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", $user_id, $transaction_id, $type, $title, $message);
    }

    /**
     * Get notifications for a user
     */
    public function get_user_notifications($user_id, $limit = 10, $unread_only = false) {
        $where_clause = "WHERE n.user_id = ?";
        $params = [$user_id];

        if ($unread_only) {
            $where_clause .= " AND n.is_read = 0";
        }

        $params[] = $limit;

        return $this->db->query("
            SELECT n.*, t.transaction_id as transaction_number
            FROM notifications n
            LEFT JOIN transactions t ON n.transaction_id = t.id
            $where_clause
            ORDER BY n.created_at DESC
            LIMIT ?
        ", ...$params)->fetchAll();
    }

    /**
     * Get unread notification count for user
     */
    public function get_unread_count($user_id) {
        $result = $this->db->query("
            SELECT COUNT(*) as count
            FROM notifications n
            WHERE n.user_id = ? AND n.is_read = 0
        ", $user_id)->fetchArray();

        return $result ? $result['count'] : 0;
    }

    /**
     * Mark notification as read
     */
    public function mark_as_read($notification_id, $user_id) {
        return $this->db->query("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ", $notification_id, $user_id);
    }

    /**
     * Mark all notifications as read for user
     */
    public function mark_all_as_read($user_id) {
        return $this->db->query("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ", $user_id);
    }

    /**
     * Delete notification
     */
    public function delete_notification($notification_id, $user_id) {
        return $this->db->query("
            DELETE FROM notifications
            WHERE id = ? AND user_id = ?
        ", $notification_id, $user_id);
    }

    /**
     * Send email notification
     */
    public function send_email_notification($user_email, $subject, $message, $notification_id = null) {
        // Check if email notifications are disabled
        $email_disabled = getenv('DISABLE_EMAIL_NOTIFICATIONS') ?: $_ENV['DISABLE_EMAIL_NOTIFICATIONS'] ?? 'false';
        if (filter_var($email_disabled, FILTER_VALIDATE_BOOLEAN)) {
            error_log("Email notification skipped - email notifications are disabled globally");

            // Update notification as "sent" even though we didn't send it
            if ($notification_id) {
                $this->db->query("
                    UPDATE notifications
                    SET email_sent = 1, email_sent_at = NOW()
                    WHERE id = ?
                ", $notification_id);
            }

            return true; // Return true to not break the workflow
        }

        try {
            // Use the centralized SMTP configuration instead of basic mail()
            require_once(__DIR__ . '/EmailNotification.php');

            $emailNotification = new EmailNotification($this->db);

            // Create a dummy transaction data for the email template
            $transaction_data = [
                'id' => 'NOTIFICATION',
                'transaction_id' => 'NOTIFICATION',
                'name' => $subject,
                'amount' => 0,
                'state' => 1
            ];

            // Send using the proper SMTP configuration
            $result = $emailNotification->sendCustomNotification(
                $user_email,
                'System User',
                $subject,
                $message
            );

            // Update notification as email sent
            if ($result && $notification_id) {
                $this->db->query("
                    UPDATE notifications
                    SET email_sent = 1, email_sent_at = NOW()
                    WHERE id = ?
                ", $notification_id);
            }

            return $result;

        } catch (Exception $e) {
            error_log("Notification email error: " . $e->getMessage());

            // Fallback to basic mail() if SMTP fails (not recommended for production)
            $headers = "From: finance-system@undp.org\r\n";
            $headers .= "Reply-To: finance-system@undp.org\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $email_body = $this->get_email_template($subject, $message);

            $result = mail($user_email, $subject, $email_body, $headers);

            // Update notification as email sent
            if ($result && $notification_id) {
                $this->db->query("
                    UPDATE notifications
                    SET email_sent = 1, email_sent_at = NOW()
                    WHERE id = ?
                ", $notification_id);
            }

            return $result;
        }
    }

    /**
     * Get email template
     */
    private function get_email_template($subject, $message) {
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
                .content { padding: 20px; background: #f8f9fa; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>UNDP Finance System</h1>
                </div>
                <div class='content'>
                    <h2>$subject</h2>
                    <p>$message</p>
                    <p>Please log in to the finance system to view more details.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the UNDP Finance System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Create transaction status change notification
     */
    public function notify_transaction_status_change($user_id, $transaction_id, $new_state, $transaction_data, $send_email = true) {
        $messages = [
            '1' => 'Your transaction has been submitted for review',
            '2' => 'Your transaction is now under review',
            '3' => 'Your transaction has been approved and is being processed',
            '4' => 'Your transaction has been sent back for corrections',
            '5' => 'Your transaction has been sent to payroll',
            '6' => 'Your transaction has been paid'
        ];

        $message = $messages[$new_state] ?? 'Your transaction status has been updated';
        $title = "Transaction {$transaction_data['transaction_id']} - Status Update";

        // Create in-system notification
        $notification_id = $this->create_notification($user_id, $transaction_data['id'], 'transaction_status_change', $title, $message);
        
        // Send email if requested
        if ($send_email && $notification_id) {
            global $user;
            $user_info = $user->get_user($user_id);
            if ($user_info && $user_info['user_email']) {
                $this->send_email_notification($user_info['user_email'], $title, $message, $this->db->lastInsertID());
            }
        }
        
        return $notification_id;
    }

    /**
     * Notify finance team of new submission
     */
    public function notify_finance_team_submission($transaction_id, $transaction_data, $send_email = true) {
        global $user;
        $finance_team = $user->get_finance_team();
        
        $title = "New Transaction Submitted";
        $message = "Transaction {$transaction_data['transaction_id']} has been submitted for review by {$transaction_data['user_name']}";

        foreach ($finance_team as $finance_user) {
            // Create in-system notification
            $notification_id = $this->create_notification(
                $finance_user['user_id'], 
                $transaction_data['id'], 
                'transaction_submitted', 
                $title, 
                $message
            );
            
            // Send email if requested
            if ($send_email && $notification_id) {
                $this->send_email_notification($finance_user['user_email'], $title, $message, $this->db->lastInsertID());
            }
        }
    }

    /**
     * Get notification icon based on type
     */
    public function get_notification_icon($type) {
        $icons = [
            'transaction_submitted' => 'upload',
            'transaction_status_change' => 'info-circle',
            'transaction_approved' => 'check-circle',
            'transaction_sent_back' => 'arrow-left',
            'transaction_paid' => 'credit-card',
            'transaction_resubmitted' => 'refresh'
        ];
        
        return $icons[$type] ?? 'bell';
    }

    /**
     * Get notification color based on type
     */
    public function get_notification_color($type) {
        $colors = [
            'transaction_submitted' => 'primary',
            'transaction_status_change' => 'info',
            'transaction_approved' => 'success',
            'transaction_sent_back' => 'danger',
            'transaction_paid' => 'success',
            'transaction_resubmitted' => 'warning'
        ];
        
        return $colors[$type] ?? 'secondary';
    }
}
