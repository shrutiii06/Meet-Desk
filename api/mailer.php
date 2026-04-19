<?php
// ===== EMAIL SENDING UTILITY =====
// Purpose: Send emails to users with registration confirmation and other notifications

require_once 'config/mail-config.php';

class MailSender {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_password;
    private $sender_email;
    private $sender_name;
    
    public function __construct() {
        $this->smtp_host = MAIL_CONFIG['smtp_host'];
        $this->smtp_port = MAIL_CONFIG['smtp_port'];
        $this->smtp_user = MAIL_CONFIG['smtp_user'];
        $this->smtp_password = MAIL_CONFIG['smtp_password'];
        $this->sender_email = MAIL_CONFIG['sender_email'];
        $this->sender_name = MAIL_CONFIG['sender_name'];
    }
    
    /**
     * ===== SEND REGISTRATION WELCOME EMAIL =====
     * Sends welcome email to newly registered user
     * 
     * RECEIVES: $recipientEmail, $recipientName
     * RETURNS: ['success' => true/false, 'message' => string]
     */
    public function sendRegistrationWelcome($recipientEmail, $recipientName) {
        $subject = "Welcome to Meet Desk! Registration Successful";
        
        $htmlBody = $this->getRegistrationTemplate($recipientName, $recipientEmail);
        
        return $this->sendEmail($recipientEmail, $recipientName, $subject, $htmlBody);
    }
    
    /**
     * ===== SEND PASSWORD RESET EMAIL =====
     * Sends password reset code to user email
     * 
     * RECEIVES: $recipientEmail, $recipientName, $resetCode
     * RETURNS: ['success' => true/false, 'message' => string]
     */
    public function sendPasswordReset($recipientEmail, $recipientName, $resetCode) {
        $subject = "Reset Your Meet Desk Password";
        $htmlBody = $this->getPasswordResetTemplate($recipientName, $resetCode);
        
        return $this->sendEmail($recipientEmail, $recipientName, $subject, $htmlBody);
    }
    
    /**
     * ===== SEND EMAIL FUNCTION =====
     * Core function to send emails via SMTP
     * 
     * RECEIVES: $toEmail, $toName, $subject, $htmlBody
     * RETURNS: ['success' => true/false, 'message' => string]
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlBody) {
        try {
            // ===== STEP 1: CREATE STREAM CONTEXT WITH TLS =====
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            // ===== STEP 2: CONNECT TO GMAIL SMTP SERVER =====
            $connection = @stream_socket_client(
                "tcp://{$this->smtp_host}:{$this->smtp_port}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$connection) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $errstr
                ];
            }
            
            // ===== STEP 3: SEND SMTP COMMANDS =====
            $response = fgets($connection, 512);
            error_log("SMTP Connect: " . trim($response));
            
            // EHLO command
            fputs($connection, "EHLO localhost\r\n");
            $response = fgets($connection, 512);
            while (substr($response, 3, 1) != ' ') {
                $response = fgets($connection, 512);
            }
            
            // STARTTLS for encryption
            fputs($connection, "STARTTLS\r\n");
            $response = fgets($connection, 512);
            error_log("STARTTLS: " . trim($response));
            
            stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO again after TLS
            fputs($connection, "EHLO localhost\r\n");
            $response = fgets($connection, 512);
            while (substr($response, 3, 1) != ' ') {
                $response = fgets($connection, 512);
            }
            
            // AUTH LOGIN
            fputs($connection, "AUTH LOGIN\r\n");
            fgets($connection, 512);
            
            // Send username (base64 encoded)
            fputs($connection, base64_encode($this->smtp_user) . "\r\n");
            fgets($connection, 512);
            
            // Send password (base64 encoded)
            fputs($connection, base64_encode($this->smtp_password) . "\r\n");
            $authResponse = fgets($connection, 512);
            error_log("AUTH: " . trim($authResponse));
            
            if (strpos($authResponse, '235') === false) {
                fclose($connection);
                error_log("Authentication failed");
                return [
                    'success' => false,
                    'message' => 'Authentication failed. Check app password.'
                ];
            }
            
            // ===== STEP 4: BUILD EMAIL MESSAGE =====
            $from = "{$this->sender_name} <{$this->sender_email}>";
            $to = "{$toName} <{$toEmail}>";
            $date = date('r');
            $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'meetdesk.local';
            $messageId = '<' . uniqid() . '@' . $serverName . '>';
            
            $headers = "From: {$from}\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "Date: {$date}\r\n";
            $headers .= "Message-ID: {$messageId}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            
            $body = "{$headers}\r\n{$htmlBody}";
            
            // ===== STEP 5: SEND EMAIL =====
            fputs($connection, "MAIL FROM: <{$this->sender_email}>\r\n");
            $response = fgets($connection, 512);
            error_log("MAIL FROM: " . trim($response));
            
            fputs($connection, "RCPT TO: <{$toEmail}>\r\n");
            $response = fgets($connection, 512);
            error_log("RCPT TO: " . trim($response));
            
            fputs($connection, "DATA\r\n");
            fgets($connection, 512);
            
            fputs($connection, $body . "\r\n.\r\n");
            $response = fgets($connection, 512);
            error_log("DATA: " . trim($response));
            
            fputs($connection, "QUIT\r\n");
            fclose($connection);
            
            if (strpos($response, '250') !== false) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Email Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ===== GET REGISTRATION EMAIL TEMPLATE =====
     * Generates HTML email template for registration
     * 
     * RECEIVES: $userName, $userEmail
     * RETURNS: HTML string
     */
    private function getRegistrationTemplate($userName, $userEmail) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $appUrl = $protocol . '://' . $host;
        $loginUrl = $appUrl . '/MD/login.html';
        $year = date('Y');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4285F4 0%, #3367D6 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .content h2 { color: #4285F4; font-size: 20px; margin-top: 0; }
        .content p { margin: 15px 0; line-height: 1.8; }
        .info-box { background: #e3f2fd; border-left: 4px solid #4285F4; padding: 15px; margin: 20px 0; }
        .button { display: inline-block; background: #4285F4; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Meet Desk</h1>
            <p>Video Conferencing Made Easy</p>
        </div>
        
        <div class="content">
            <h2>Hello {$userName}! 👋</h2>
            
            <p>Thank you for registering with <strong>Meet Desk</strong>. Your account has been successfully created!</p>
            
            <div class="info-box">
                <strong>Account Details:</strong><br>
                📧 Email: {$userEmail}<br>
                ✅ Status: Active
            </div>
            
            <p>You can now log in to your account and start scheduling meetings, joining video conferences, and collaborating with your team.</p>
            
            <p style="text-align: center;">
                <a href="{$loginUrl}" class="button">Login to Meet Desk</a>
            </p>
            
            <h3>What's Next?</h3>
            <ul>
                <li>✅ Complete Your Profile - Add a profile picture and update your information</li>
                <li>📅 Schedule Meetings - Use our calendar to schedule video meetings in advance</li>
                <li>👥 Start Meeting - Create new meetings or join existing ones</li>
                <li>⚙️ Customize Settings - Adjust notification and privacy preferences</li>
            </ul>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br><strong>Meet Desk Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {$year} Meet Desk. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this address.</p>
            <p>If you did not register for this account, please contact us immediately.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * ===== GET PASSWORD RESET EMAIL TEMPLATE =====
     * Generates HTML email template for password reset
     * 
     * RECEIVES: $userName, $resetCode
     * RETURNS: HTML string
     */
    private function getPasswordResetTemplate($userName, $resetCode) {
        $year = date('Y');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f44336 0%, #e53935 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .content h2 { color: #f44336; font-size: 20px; margin-top: 0; }
        .content p { margin: 15px 0; line-height: 1.8; }
        .code-box { background: #fff; border: 2px solid #f44336; padding: 20px; margin: 20px 0; text-align: center; font-family: monospace; }
        .code-box .label { font-size: 12px; color: #666; margin-bottom: 10px; }
        .code-box .code { font-size: 24px; font-weight: bold; color: #f44336; letter-spacing: 2px; }
        .warning { background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        
        <div class="content">
            <h2>Hello {$userName}! 👋</h2>
            
            <p>We received a request to reset your Meet Desk password. Use the code below to reset it.</p>
            
            <div class="code-box">
                <div class="label">Your Reset Code:</div>
                <div class="code">{$resetCode}</div>
            </div>
            
            <p>This code will expire in 30 minutes. If you didn't request this, you can safely ignore this email.</p>
            
            <div class="warning">
                ⚠️ <strong>Security Tip:</strong> Never share this code with anyone. Meet Desk staff will never ask for your reset code.
            </div>
            
            <h3>Steps to Reset Your Password:</h3>
            <ol>
                <li>Go back to the login page</li>
                <li>Click "Forgot Password"</li>
                <li>Enter the code above: <strong>{$resetCode}</strong></li>
                <li>Create your new password</li>
                <li>Log in with your new password</li>
            </ol>
            
            <p>If you need any assistance, please contact our support team.</p>
            
            <p>Best regards,<br><strong>Meet Desk Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {$year} Meet Desk. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this address.</p>
            <p>If you did not request a password reset, please secure your account immediately.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

/**
 * ===== HELPER FUNCTION =====
 * Global function to send emails from anywhere in the app
 */
function sendRegistrationEmail($email, $name) {
    $mailer = new MailSender();
    return $mailer->sendRegistrationWelcome($email, $name);
}

/**
 * ===== HELPER FUNCTION =====
 * Send password reset email with code
 */
function sendPasswordResetEmail($email, $name, $resetCode) {
    $mailer = new MailSender();
    return $mailer->sendPasswordReset($email, $name, $resetCode);
}

/**
 * ===== GENERIC EMAIL SENDER =====
 * Used by email-templates.php for meeting notifications
 */
function sendEmailViaMailer($toEmail, $subject, $htmlBody, $toName = '') {
    $mailer = new MailSender();
    return $mailer->sendEmail($toEmail, $toName, $subject, $htmlBody);
}