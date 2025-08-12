<?php
class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function send_magic_link($email) {
        $user = $this->db->query(
            "SELECT * FROM users WHERE user_email = ?", $email
        )->fetchArray();
    
        if (!$user) {
            return "User not found.";
        }
    
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 600);
    
        $this->db->query(
            "INSERT INTO magic_links (user_id, token, expires_at) VALUES (?, ?, ?)",
            $user['user_id'], $token, $expires
        );
    
        $link = "http://localhost/auth.php?token=$token";
    
        require_once '../vendor/autoload.php';
        $config = require __DIR__ . '/smtp_config.php';
    
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port       = $config['port'];
    
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($email, $user['user_name']);
            $mail->isHTML(true);
            $mail->Subject = '🔐 Your Magic Login Link';
    
            $mail->Body = <<<HTML
            <!-- (same HTML as earlier, omitted for brevity) -->
            <a href="{$link}">{$link}</a>
            HTML;
    
            $mail->AltBody = "Hi {$user['user_name']},\nLogin using this link: $link";
    
            $mail->send();
            return "Magic link sent.";
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return "Mailer Error: " . $mail->ErrorInfo;
        }
    }
    

    public function validate_magic_link($token) {
        $magic = $this->db->query(
            "SELECT * FROM magic_links WHERE token = ? AND used = 0", $token
        )->fetchArray();

        if ($magic && strtotime($magic['expires_at']) > time()) {
            $this->db->query("UPDATE magic_links SET used = 1 WHERE id = ?", $magic['id']);
            return $magic['user_id'];
        }

        return false;
    }
}
