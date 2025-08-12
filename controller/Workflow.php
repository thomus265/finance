<?php
class Workflow {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get all workflow states
     */
    public function get_workflow_states() {
        return $this->db->query("
            SELECT * FROM workflow_states 
            ORDER BY state_value
        ")->fetchAll();
    }

    /**
     * Get workflow states for specific department
     */
    public function get_workflow_states_for_department($department_type) {
        return $this->db->query("
            SELECT * FROM workflow_states 
            WHERE department_type = ? OR department_type = 0
            ORDER BY state_value
        ", $department_type)->fetchAll();
    }

    /**
     * Get workflow state by value and department
     */
    public function get_workflow_state($state_value, $department_type = 0) {
        return $this->db->query("
            SELECT * FROM workflow_states 
            WHERE state_value = ? AND (department_type = ? OR department_type = 0)
            ORDER BY department_type DESC
            LIMIT 1
        ", $state_value, $department_type)->fetchArray();
    }

    /**
     * Check if state transition is valid
     */
    public function is_valid_transition($current_state, $new_state, $department_type) {
        $state_info = $this->get_workflow_state($current_state, $department_type);
        
        if (!$state_info) {
            return false;
        }

        // If it's a final state, no transitions allowed
        if ($state_info['is_final'] == 1) {
            return false;
        }

        // Check if new state is in the allowed next states
        $next_states = explode(',', $state_info['next_states']);
        return in_array($new_state, $next_states);
    }

    /**
     * Check if transaction is editable in current state
     */
    public function is_transaction_editable($state_value, $department_type) {
        $state_info = $this->get_workflow_state($state_value, $department_type);
        return $state_info && $state_info['is_editable'] == 1;
    }

    /**
     * Get next possible states for current state
     */
    public function get_next_states($current_state, $department_type) {
        $state_info = $this->get_workflow_state($current_state, $department_type);
        
        if (!$state_info || empty($state_info['next_states'])) {
            return [];
        }

        $next_state_values = explode(',', $state_info['next_states']);
        $next_states = [];

        foreach ($next_state_values as $state_value) {
            $state_value = trim($state_value);
            $state = $this->get_workflow_state($state_value, $department_type);
            if ($state) {
                $next_states[] = $state;
            }
        }

        return $next_states;
    }

    /**
     * Get workflow progress percentage
     */
    public function get_workflow_progress($current_state, $department_type) {
        $all_states = $this->get_workflow_states_for_department($department_type);
        $total_states = count($all_states);
        
        if ($total_states == 0) {
            return 0;
        }

        // Find current state position
        $current_position = 0;
        foreach ($all_states as $index => $state) {
            if ($state['state_value'] == $current_state) {
                $current_position = $index + 1;
                break;
            }
        }

        return round(($current_position / $total_states) * 100);
    }

    /**
     * Get workflow display information
     */
    public function get_workflow_display_info($state_value, $department_type) {
        $state = $this->get_workflow_state($state_value, $department_type);
        
        if (!$state) {
            return [
                'name' => 'Unknown',
                'description' => 'Unknown state',
                'class' => 'secondary',
                'icon' => 'question-circle'
            ];
        }

        // Determine CSS class and icon based on state
        $class = 'secondary';
        $icon = 'circle';

        switch ($state_value) {
            case 1: // Submitted
                $class = 'primary';
                $icon = 'upload';
                break;
            case 2: // Under Review
                $class = 'warning';
                $icon = 'eye';
                break;
            case 3: // Processing
                $class = 'info';
                $icon = 'gear';
                break;
            case 4: // Sent Back
                $class = 'danger';
                $icon = 'arrow-left';
                break;
            case 5: // Sent to Payroll
                $class = 'success';
                $icon = 'send';
                break;
            case 6: // Paid
                $class = 'success';
                $icon = 'check-circle';
                break;
        }

        return [
            'name' => $state['state_name'],
            'description' => $state['state_description'],
            'class' => $class,
            'icon' => $icon,
            'is_editable' => $state['is_editable'],
            'is_final' => $state['is_final']
        ];
    }

    /**
     * Get department workflow type name
     */
    public function get_department_name($department_type) {
        switch ($department_type) {
            case 1:
                return 'Operations';
            case 2:
                return 'Programs';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get workflow steps for department
     */
    public function get_workflow_steps($department_type) {
        $states = $this->get_workflow_states_for_department($department_type);
        $steps = [];

        foreach ($states as $state) {
            $display_info = $this->get_workflow_display_info($state['state_value'], $department_type);
            $steps[] = [
                'state_value' => $state['state_value'],
                'name' => $state['state_name'],
                'description' => $state['state_description'],
                'class' => $display_info['class'],
                'icon' => $display_info['icon']
            ];
        }

        return $steps;
    }

    /**
     * Log workflow state change
     */
    public function log_state_change($transaction_id, $old_state, $new_state, $changed_by, $reason = null) {
        return $this->db->query("
            INSERT INTO transaction_audit_log (transaction_id, old_status, new_status, changed_by, change_reason, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", $transaction_id, $old_state, $new_state, $changed_by, $reason);
    }

    /**
     * Get transaction history/audit trail
     */
    public function get_transaction_history($transaction_id) {
        // First try the transaction_audit_log table
        $history = $this->db->query("
            SELECT
                tal.*,
                u.user_name as changed_by_name,
                ws_from.state_name as from_state_name,
                ws_to.state_name as to_state_name,
                tal.created_at as changed_at,
                tal.old_status as from_state,
                tal.new_status as to_state,
                tal.change_reason as comments
            FROM transaction_audit_log tal
            LEFT JOIN users u ON tal.changed_by = u.user_id
            LEFT JOIN workflow_states ws_from ON tal.old_status = ws_from.state_value
            LEFT JOIN workflow_states ws_to ON tal.new_status = ws_to.state_value
            WHERE tal.transaction_id = ?
            ORDER BY tal.created_at DESC
        ", $transaction_id)->fetchAll();

        // If no results, try alternative table structure
        if (empty($history)) {
            $history = $this->db->query("
                SELECT
                    'State Change' as type,
                    'System' as changed_by_name,
                    '' as from_state,
                    '' as to_state,
                    '' as from_state_name,
                    '' as to_state_name,
                    'Transaction created' as comments,
                    tr_date as changed_at
                FROM transactions
                WHERE id = ?
                ORDER BY tr_date DESC
            ", $transaction_id)->fetchAll();
        }

        return $history;
    }
}
