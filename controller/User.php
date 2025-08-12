<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    public function add_user($name, $email, $type) {
        return $this->db->query(
            "INSERT INTO users (user_name, user_email, user_type) VALUES (?, ?, ?)",
            $name, $email, $type
        );
    }

    public function get_users() {
        return $this->db->query("SELECT * FROM users")->fetchAll();
    }

    public function get_user($id) {
        return $this->db->query("SELECT * FROM users WHERE user_id = ?", $id)->fetchArray();
    }

    public function update_user($id, $name, $email, $type) {
        return $this->db->query(
            "UPDATE users SET user_name = ?, user_email = ?, user_type = ? WHERE user_id = ?",
            $name, $email, $type, $id
        );
    }

    public function delete_user($id) {
        return $this->db->query("DELETE FROM users WHERE user_id = ?", $id);
    }

    /**
     * Get user by ID with role information
     */
    public function get_user_with_role($user_id) {
        return $this->db->query("
            SELECT u.*, ut.type_name, ut.type_description, ut.can_submit_transactions,
                   ut.can_review_transactions, ut.can_approve_transactions, ut.department_type
            FROM users u
            LEFT JOIN user_types ut ON u.user_type = ut.type_value
            WHERE u.user_id = ?
        ", $user_id)->fetchArray();
    }

    /**
     * Get all users by type
     */
    public function get_users_by_type($user_type) {
        return $this->db->query("
            SELECT u.*, ut.type_name
            FROM users u
            LEFT JOIN user_types ut ON u.user_type = ut.type_value
            WHERE u.user_type = ?
            ORDER BY u.user_name
        ", $user_type)->fetchAll();
    }

    /**
     * Get finance team members
     */
    public function get_finance_team() {
        return $this->get_users_by_type(3);
    }

    /**
     * Check if user can submit transactions
     */
    public function can_submit_transactions($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['can_submit_transactions'] == 1;
    }

    /**
     * Check if user can review transactions
     */
    public function can_review_transactions($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['can_review_transactions'] == 1;
    }

    /**
     * Check if user can approve transactions
     */
    public function can_approve_transactions($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['can_approve_transactions'] == 1;
    }

    /**
     * Get user's department type
     */
    public function get_user_department_type($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user ? $user['department_type'] : null;
    }

    /**
     * Check if user is finance team member
     */
    public function is_finance_team($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['user_type'] == 3;
    }

    /**
     * Check if user is operations user
     */
    public function is_operations_user($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['user_type'] == 1;
    }

    /**
     * Check if user is programs user
     */
    public function is_programs_user($user_id) {
        $user = $this->get_user_with_role($user_id);
        return $user && $user['user_type'] == 2;
    }

    /**
     * Get all user types
     */
    public function get_user_types() {
        return $this->db->query("
            SELECT * FROM user_types
            ORDER BY type_value
        ")->fetchAll();
    }

    /**
     * Get current user ID from session
     */
    public function get_current_user_id() {
        return $_SESSION['user_id'] ?? 1; // Default to user 1 for testing
    }

    /**
     * Get current user with role information
     */
    public function get_current_user() {
        $user_id = $this->get_current_user_id();
        return $this->get_user_with_role($user_id);
    }

    /**
     * Check if current user has permission
     */
    public function has_permission($permission) {
        $user = $this->get_current_user();
        if (!$user) return false;

        switch ($permission) {
            case 'submit_transactions':
                return $user['can_submit_transactions'] == 1;
            case 'review_transactions':
                return $user['can_review_transactions'] == 1;
            case 'approve_transactions':
                return $user['can_approve_transactions'] == 1;
            default:
                return false;
        }
    }

    /**
     * Require permission or redirect
     */
    public function require_permission($permission, $redirect_url = '/user_transactions') {
        if (!$this->has_permission($permission)) {
            header("Location: $redirect_url?error=insufficient_permissions");
            exit;
        }
    }
}
