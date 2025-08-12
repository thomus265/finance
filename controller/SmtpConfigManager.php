<?php
/**
 * SMTP Configuration Manager
 * 
 * Provides centralized management of SMTP configuration with security best practices
 */

class SmtpConfigManager {
    private static $config = null;
    private static $configFile = null;
    
    /**
     * Get SMTP configuration
     * 
     * @return array SMTP configuration array
     * @throws Exception if configuration is invalid
     */
    public static function getConfig() {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        return self::$config;
    }
    
    /**
     * Load SMTP configuration from file
     * 
     * @throws Exception if configuration file not found or invalid
     */
    private static function loadConfig() {
        self::$configFile = __DIR__ . '/smtp_config.php';
        
        if (!file_exists(self::$configFile)) {
            throw new Exception("SMTP configuration file not found: " . self::$configFile);
        }
        
        // Load configuration
        self::$config = require self::$configFile;
        
        // Validate configuration
        $validation = self::validateConfig(self::$config);
        if (!$validation['valid']) {
            $errors = implode(', ', $validation['errors']);
            throw new Exception("Invalid SMTP configuration: $errors");
        }
    }
    
    /**
     * Validate SMTP configuration
     * 
     * @param array $config Configuration array to validate
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public static function validateConfig($config) {
        $errors = [];
        
        // Required fields
        $requiredFields = ['host', 'username', 'password', 'port', 'from_email'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "SMTP $field is required";
            }
        }
        
        // Validate email format
        if (!empty($config['from_email']) && !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'From email must be a valid email address';
        }
        
        // Validate port
        if (!empty($config['port']) && (!is_numeric($config['port']) || $config['port'] < 1 || $config['port'] > 65535)) {
            $errors[] = 'SMTP port must be a valid port number (1-65535)';
        }
        
        // Validate encryption
        if (!empty($config['encryption']) && !in_array($config['encryption'], [
            PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
            PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
            ''
        ])) {
            $errors[] = 'Invalid SMTP encryption type';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Test SMTP connection
     * 
     * @return array Test result with 'success' boolean and 'message' string
     */
    public static function testConnection() {
        try {
            $config = self::getConfig();
            
            // Create a test PHPMailer instance
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configure SMTP
            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->SMTPAuth = $config['auth'] ?? true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->SMTPSecure = $config['encryption'];
            $mailer->Port = $config['port'];
            $mailer->Timeout = $config['timeout'] ?? 30;
            
            // Test connection
            $mailer->smtpConnect();
            $mailer->smtpClose();
            
            return [
                'success' => true,
                'message' => 'SMTP connection successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get configuration summary (without sensitive data)
     * 
     * @return array Configuration summary
     */
    public static function getConfigSummary() {
        try {
            $config = self::getConfig();
            
            return [
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'from_email' => $config['from_email'],
                'from_name' => $config['from_name'],
                'auth_enabled' => $config['auth'] ?? true,
                'username_set' => !empty($config['username']),
                'password_set' => !empty($config['password']),
                'config_file' => self::$configFile
            ];
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create PHPMailer instance with current configuration
     * 
     * @return PHPMailer\PHPMailer\PHPMailer Configured PHPMailer instance
     * @throws Exception if configuration is invalid
     */
    public static function createMailer() {
        $config = self::getConfig();
        
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configure SMTP
        $mailer->isSMTP();
        $mailer->Host = $config['host'];
        $mailer->SMTPAuth = $config['auth'] ?? true;
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
        $mailer->SMTPSecure = $config['encryption'];
        $mailer->Port = $config['port'];
        $mailer->Timeout = $config['timeout'] ?? 30;
        
        // Debug settings
        if (!empty($config['debug']) && $config['debug'] > 0) {
            $mailer->SMTPDebug = $config['debug'];
            $mailer->Debugoutput = function($str, $level) {
                error_log("SMTP Debug [$level]: $str");
            };
        }
        
        // Default sender
        $mailer->setFrom($config['from_email'], $config['from_name']);
        
        // Additional settings
        $mailer->isHTML(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->Encoding = 'base64';
        
        return $mailer;
    }
    
    /**
     * Reset cached configuration (useful for testing)
     */
    public static function resetConfig() {
        self::$config = null;
        self::$configFile = null;
    }
    
    /**
     * Check if configuration is loaded
     * 
     * @return bool True if configuration is loaded
     */
    public static function isConfigLoaded() {
        return self::$config !== null;
    }
}
