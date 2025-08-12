<?php
class Transaction {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Generate a unique transaction ID
     */
    public function generate_transaction_id() {
        $year = date('Y');
        $prefix = "TXN-{$year}-";

        // Get the highest existing transaction ID for this year
        $result = $this->db->query(
            "SELECT transaction_id FROM transactions WHERE transaction_id LIKE ? ORDER BY transaction_id DESC LIMIT 1",
            $prefix . '%'
        )->fetchArray();

        if ($result && $result['transaction_id']) {
            // Extract the number part and increment
            $lastNumber = intval(substr($result['transaction_id'], -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function add_transaction($title, $amount, $department, $state, $user_id, $department_type = null) {
        $transaction_id = $this->generate_transaction_id();

        // Determine department_type based on user if not provided
        if ($department_type === null) {
            global $user;
            $user_info = $user->get_user_with_role($user_id);
            $department_type = $user_info['department_type'] ?? 1; // Default to Operations
        }

        return $this->db->query(
            "INSERT INTO transactions (transaction_id, title, amount, department, state, user_id, department_type, tr_date, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, current_timestamp(), current_timestamp())",
            $transaction_id, $title, $amount, $department, $state, $user_id, $department_type
        );
    }

    public function get_last_id() {
        return $this->db->lastInsertID();
    }

    public function get_transactions() {
        return $this->db->query("SELECT * FROM transactions")->fetchAll();
    }
    public function get_table($search = '', $sort_by = 'id', $sort_order = 'asc', $limit = 5, $offset = 0) {
        $where = '';
        $params = [];

        if (!empty($search)) {
            $where = "WHERE title LIKE ? OR department LIKE ? OR transaction_id LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $allowed = ['id', 'title', 'amount', 'department', 'tr_date', 'transaction_id'];
        $sort_by = in_array($sort_by, $allowed) ? $sort_by : 'id';
        $sort_order = $sort_order === 'desc' ? 'desc' : 'asc';

        $sql = "SELECT * FROM transactions $where ORDER BY $sort_by $sort_order LIMIT $limit OFFSET $offset";
        return $this->db->query($sql, ...$params)->fetchAll();
    }
    
    public function count_transactions($search = '') {
        $where = '';
        $params = [];
    
        if (!empty($search)) {
            $where = "WHERE title LIKE ? OR department LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
    
        $sql = "SELECT COUNT(*) as total FROM transactions $where";
        return $this->db->query($sql, ...$params)->fetchAll()['total'];
    }
    

    public function get_transaction($identifier) {
        // Determine if identifier is numeric (id) or string (transaction_id)
        $where_clause = is_numeric($identifier) ? "id = ?" : "transaction_id = ?";
        return $this->db->query("SELECT * FROM transactions WHERE $where_clause", $identifier)->fetchArray();
    }

    /**
     * Get transaction by unique transaction ID
     */
    public function get_transaction_by_id($transaction_id) {
        return $this->db->query("SELECT * FROM transactions WHERE transaction_id = ?", $transaction_id)->fetchArray();
    }

    /**
     * Get transaction with all details including attachments and workflow info
     */
    public function get_transaction_details($identifier) {
        // Determine if identifier is numeric (id) or string (transaction_id)
        $where_clause = is_numeric($identifier) ? "t.id = ?" : "t.transaction_id = ?";

        // Get transaction details with user and reviewer information
        $transaction = $this->db->query("
            SELECT t.*,
                   u.user_name, u.user_email, u.user_type,
                   reviewer.user_name as reviewer_name,
                   processor.user_name as processor_name,
                   last_updater.user_name as last_updated_by_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users reviewer ON t.reviewed_by = reviewer.user_id
            LEFT JOIN users processor ON t.processor_id = processor.user_id
            LEFT JOIN users last_updater ON t.last_updated_by = last_updater.user_id
            WHERE $where_clause
        ", $identifier)->fetchArray();

        if (!$transaction) {
            return null;
        }

        // Get attachments for this transaction
        // Use the transaction_id from the fetched transaction record
        $attachments = $this->db->query("
            SELECT ta.*, a.file_path as attachment_path, a.file_type
            FROM transaction_attachments ta
            LEFT JOIN attachments a ON ta.attachment_id = a.id
            WHERE ta.transaction_id = ?
            ORDER BY ta.uploaded_at DESC
        ", $transaction['transaction_id'])->fetchAll();

        $transaction['attachments'] = $attachments;

        // Get workflow information
        global $workflow;
        $transaction['workflow_info'] = $workflow->get_workflow_display_info($transaction['state'], $transaction['department_type']);
        $transaction['next_states'] = $workflow->get_next_states($transaction['state'], $transaction['department_type']);
        $transaction['workflow_progress'] = $workflow->get_workflow_progress($transaction['state'], $transaction['department_type']);

        return $transaction;
    }

    public function update_transaction($identifier, $title, $amount, $department, $state, $user_id) {
        // Determine if identifier is numeric (id) or string (transaction_id)
        $where_clause = is_numeric($identifier) ? "id = ?" : "transaction_id = ?";

        return $this->db->query(
            "UPDATE transactions SET title = ?, amount = ?, department = ?, state = ?, user_id = ?, last_updated_by = ? WHERE $where_clause",
            $title, $amount, $department, $state, $user_id, $user_id, $identifier
        );
    }

    public function delete_transaction($identifier) {
        // Determine if identifier is numeric (id) or string (transaction_id)
        $where_clause = is_numeric($identifier) ? "id = ?" : "transaction_id = ?";
        return $this->db->query("DELETE FROM transactions WHERE $where_clause", $identifier);
    }

    /**
     * Resubmit transaction (change from state 4 to 1)
     */
    public function resubmit_transaction($identifier, $user_id) {
        // Determine if identifier is numeric (id) or string (transaction_id)
        $where_clause = is_numeric($identifier) ? "id = ?" : "transaction_id = ?";
        return $this->db->query(
            "UPDATE transactions SET state = 1, resubmitted_at = NOW(), resubmitted_count = resubmitted_count + 1, last_updated_by = ? WHERE $where_clause AND state = 4",
            $user_id, $identifier
        );
    }

    /**
     * Search transactions by various criteria
     */
    public function search_transactions($search_term) {
        return $this->db->query("
            SELECT t.*, u.user_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE t.transaction_id LIKE ?
               OR t.title LIKE ?
               OR t.amount LIKE ?
               OR u.user_name LIKE ?
            ORDER BY t.tr_date DESC
        ", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%")->fetchAll();
    }

    /**
     * Add attachment to transaction
     */
    public function add_transaction_attachment($transaction_id, $attachment_id, $original_filename, $file_path, $file_size, $mime_type, $uploaded_by) {
        // Validate that the user exists before inserting
        $user_exists = $this->db->query("SELECT user_id FROM users WHERE user_id = ?", $uploaded_by)->fetchArray();

        if (!$user_exists) {
            error_log("Foreign key constraint error: User ID $uploaded_by does not exist in users table");

            // Try to find a valid user ID to use as fallback
            $fallback_user = $this->db->query("SELECT user_id FROM users ORDER BY user_id LIMIT 1")->fetchArray();

            if ($fallback_user) {
                $uploaded_by = $fallback_user['user_id'];
                error_log("Using fallback user ID: $uploaded_by");
            } else {
                error_log("No users found in database - cannot insert attachment");
                return false;
            }
        }

        return $this->db->query(
            "INSERT INTO transaction_attachments (transaction_id, attachment_id, original_filename, file_path, file_size, mime_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
            $transaction_id, $attachment_id, $original_filename, $file_path, $file_size, $mime_type, $uploaded_by
        );
    }

    /**
     * Remove attachment from transaction
     */
    public function remove_transaction_attachment($attachment_id) {
        return $this->db->query("DELETE FROM transaction_attachments WHERE id = ?", $attachment_id);
    }

    /**
     * Get attachments for a transaction
     */
    public function get_transaction_attachments($transaction_id) {
        return $this->db->query("
            SELECT ta.*, a.file_path as attachment_path, a.file_type
            FROM transaction_attachments ta
            LEFT JOIN attachments a ON ta.attachment_id = a.id
            WHERE ta.transaction_id = ?
            ORDER BY ta.uploaded_at DESC
        ", $transaction_id)->fetchAll();
    }

    /**
     * Get attachment details by attachment ID
     */
    public function get_transaction_attachments_by_id($attachment_id) {
        return $this->db->query("
            SELECT ta.*, a.file_path as attachment_path, a.file_type
            FROM transaction_attachments ta
            LEFT JOIN attachments a ON ta.attachment_id = a.id
            WHERE ta.id = ?
        ", $attachment_id)->fetchArray();
    }
}
