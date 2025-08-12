<?php
class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require CSRF token validation
     */
    public static function requireCSRFToken() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!self::validateCSRFToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
    }
    
    /**
     * Validate state transition
     */
    public static function validateStateTransition($current_state, $new_state, $department_type, $user_role) {
        global $workflow;
        
        // Check if transition is valid according to workflow
        if (!$workflow->is_valid_transition($current_state, $new_state, $department_type)) {
            return ['valid' => false, 'message' => 'Invalid state transition according to workflow rules'];
        }
        
        // Check user permissions for this transition
        switch ($user_role) {
            case 3: // Finance team
                // Finance team can make most transitions
                $allowed_transitions = [
                    '1' => ['2', '4'], // Submitted -> Under Review or Sent Back
                    '2' => ['3', '4', '5'], // Under Review -> Processing, Sent Back, or Sent to Payroll
                    '3' => ['5'], // Processing -> Sent to Payroll
                    '5' => ['6'], // Sent to Payroll -> Paid
                ];
                break;
                
            case 1: // Operations user
            case 2: // Programs user
                // Regular users can only resubmit
                $allowed_transitions = [
                    '4' => ['1'] // Sent Back -> Submitted
                ];
                break;
                
            default:
                return ['valid' => false, 'message' => 'Insufficient permissions'];
        }
        
        if (!isset($allowed_transitions[$current_state]) || 
            !in_array($new_state, $allowed_transitions[$current_state])) {
            return ['valid' => false, 'message' => 'You do not have permission to make this state transition'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate transaction ownership or finance team access
     */
    public static function validateTransactionAccess($transaction_id, $user_id, $user_role) {
        global $db;
        
        $transaction = $db->query("SELECT user_id FROM transactions WHERE id = ?", $transaction_id)->fetchArray();
        
        if (!$transaction) {
            return ['valid' => false, 'message' => 'Transaction not found'];
        }
        
        // Finance team can access all transactions
        if ($user_role == 3) {
            return ['valid' => true];
        }
        
        // Regular users can only access their own transactions
        if ($transaction['user_id'] == $user_id) {
            return ['valid' => true];
        }
        
        return ['valid' => false, 'message' => 'You can only access your own transactions'];
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file) {
        $allowed_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'message/rfc822'
        ];
        
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error: ' . $file['error']];
        }
        
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'message' => 'File size exceeds 10MB limit'];
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        // Additional security: check file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($detected_type, $allowed_types)) {
            return ['valid' => false, 'message' => 'File content does not match file type'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate transaction data
     */
    public static function validateTransactionData($title, $amount, $department) {
        $errors = [];
        
        if (empty(trim($title))) {
            $errors[] = 'Transaction title is required';
        } elseif (strlen(trim($title)) > 255) {
            $errors[] = 'Transaction title must be less than 255 characters';
        }
        
        if (!is_numeric($amount) || $amount <= 0) {
            $errors[] = 'Amount must be a positive number';
        } elseif ($amount > 999999.99) {
            $errors[] = 'Amount cannot exceed $999,999.99';
        }
        
        if (!in_array($department, ['1', '2', '3'])) {
            $errors[] = 'Invalid department selection';
        }
        
        return $errors;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event_type, $user_id, $details = '') {
        global $db;
        
        try {
            $db->query("
                INSERT INTO security_log (event_type, user_id, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", $event_type, $user_id, $details, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($action, $user_id, $limit = 10, $window = 300) {
        global $db;
        
        try {
            $count = $db->query("
                SELECT COUNT(*) as count 
                FROM security_log 
                WHERE event_type = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ", $action, $user_id, $window)->fetchArray();
            
            return $count['count'] < $limit;
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    /**
     * Validate session and user permissions
     */
    public static function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        // Check session timeout (optional)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            session_destroy();
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired']);
            exit;
        }
        
        $_SESSION['last_activity'] = time();
        return $_SESSION['user_id'];
    }
}
