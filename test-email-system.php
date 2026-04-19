<?php
/**
 * EMAIL SYSTEM TEST SCRIPT
 * Tests all email functionality to verify SMTP is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email System Test - MeetDesk</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #4285F4; }
        .test-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #fff; padding: 10px; border-left: 3px solid #4285F4; overflow-x: auto; }
        .button { background: #4285F4; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #3367D6; }
    </style>
</head>
<body>
    <h1>📧 MeetDesk Email System Test</h1>
    <p>This script tests all email functionality to ensure SMTP is configured correctly.</p>
";

// Test 1: Check if mail configuration exists
echo "<div class='test-section'>";
echo "<h2>Test 1: Mail Configuration Check</h2>";

if (file_exists(__DIR__ . '/api/config/mail-config.php')) {
    require_once __DIR__ . '/api/config/mail-config.php';
    echo "<p class='success'>✓ Mail configuration file found</p>";
    echo "<pre>";
    echo "SMTP Host: " . MAIL_CONFIG['smtp_host'] . "\n";
    echo "SMTP Port: " . MAIL_CONFIG['smtp_port'] . "\n";
    echo "SMTP User: " . MAIL_CONFIG['smtp_user'] . "\n";
    echo "Sender Email: " . MAIL_CONFIG['sender_email'] . "\n";
    echo "Sender Name: " . MAIL_CONFIG['sender_name'] . "\n";
    echo "Password Set: " . (empty(MAIL_CONFIG['smtp_password']) ? 'NO ✗' : 'YES ✓') . "\n";
    echo "</pre>";
} else {
    echo "<p class='error'>✗ Mail configuration file not found!</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Test 2: Check if mailer.php exists
echo "<div class='test-section'>";
echo "<h2>Test 2: Mailer Class Check</h2>";

if (file_exists(__DIR__ . '/api/mailer.php')) {
    require_once __DIR__ . '/api/mailer.php';
    echo "<p class='success'>✓ Mailer class file found</p>";
    
    if (class_exists('MailSender')) {
        echo "<p class='success'>✓ MailSender class loaded successfully</p>";
    } else {
        echo "<p class='error'>✗ MailSender class not found in mailer.php</p>";
    }
} else {
    echo "<p class='error'>✗ Mailer file not found!</p>";
}
echo "</div>";

// Test 3: Check email templates
echo "<div class='test-section'>";
echo "<h2>Test 3: Email Templates Check</h2>";

if (file_exists(__DIR__ . '/api/email-templates.php')) {
    require_once __DIR__ . '/api/email-templates.php';
    echo "<p class='success'>✓ Email templates file found</p>";
    
    $functions = [
        'sendMeetingInvitationEmail',
        'sendMeetingReminderEmail',
        'sendMeetingChangedEmail',
        'sendEmailVerificationEmail',
        'sendPasswordResetEmailEnhanced'
    ];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "<p class='success'>✓ Function exists: {$func}</p>";
        } else {
            echo "<p class='error'>✗ Function missing: {$func}</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Email templates file not found!</p>";
}
echo "</div>";

// Test 4: SMTP Connection Test
echo "<div class='test-section'>";
echo "<h2>Test 4: SMTP Connection Test</h2>";
echo "<p class='info'>Testing connection to " . MAIL_CONFIG['smtp_host'] . ":" . MAIL_CONFIG['smtp_port'] . "</p>";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$connection = @stream_socket_client(
    "tcp://" . MAIL_CONFIG['smtp_host'] . ":" . MAIL_CONFIG['smtp_port'],
    $errno,
    $errstr,
    10,
    STREAM_CLIENT_CONNECT,
    $context
);

if ($connection) {
    echo "<p class='success'>✓ Successfully connected to SMTP server</p>";
    $response = fgets($connection, 512);
    echo "<pre>Server Response: " . htmlspecialchars(trim($response)) . "</pre>";
    fclose($connection);
} else {
    echo "<p class='error'>✗ Failed to connect to SMTP server</p>";
    echo "<pre>Error: {$errstr} ({$errno})</pre>";
}
echo "</div>";

// Test 5: Send Test Email (only if form submitted)
echo "<div class='test-section'>";
echo "<h2>Test 5: Send Test Email</h2>";

if (isset($_POST['send_test_email'])) {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "<p class='error'>✗ Invalid email address provided</p>";
    } else {
        echo "<p class='info'>Sending test email to: {$testEmail}</p>";
        
        try {
            $mailer = new MailSender();
            $result = $mailer->sendRegistrationWelcome($testEmail, 'Test User');
            
            if ($result['success']) {
                echo "<p class='success'>✓ Test email sent successfully!</p>";
                echo "<pre>" . htmlspecialchars($result['message']) . "</pre>";
                echo "<p class='info'>Check your inbox (and spam folder) for the test email.</p>";
            } else {
                echo "<p class='error'>✗ Failed to send test email</p>";
                echo "<pre>" . htmlspecialchars($result['message']) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Exception occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<form method='POST' action=''>";
    echo "<p>Enter your email address to receive a test email:</p>";
    echo "<input type='email' name='test_email' placeholder='your@email.com' required style='padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<button type='submit' name='send_test_email' class='button'>Send Test Email</button>";
    echo "</form>";
}
echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📊 Summary</h2>";
echo "<p><strong>Configuration Status:</strong></p>";
echo "<ul>";
echo "<li>Mail Config: " . (defined('MAIL_CONFIG') ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>') . "</li>";
echo "<li>Mailer Class: " . (class_exists('MailSender') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</li>";
echo "<li>Email Templates: " . (function_exists('sendMeetingInvitationEmail') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</li>";
echo "<li>SMTP Connection: " . (isset($connection) && $connection ? '<span class="success">✓ Working</span>' : '<span class="error">✗ Failed</span>') . "</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If SMTP connection failed, verify your Gmail app password is correct</li>";
echo "<li>If test email fails, check error logs in browser console or PHP error log</li>";
echo "<li>Make sure 2-Step Verification is enabled on your Gmail account</li>";
echo "<li>Use App Password (not regular Gmail password) for SMTP authentication</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='dashboard.html' class='button'>Back to Dashboard</a>";
echo "<a href='test-email-system.php' class='button' style='background: #6c757d;'>Refresh Tests</a>";
echo "</div>";

echo "</body></html>";
?>
