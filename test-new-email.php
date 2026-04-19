<?php
/**
 * Test New MeetDesk Email Configuration
 */

require_once __DIR__ . '/api/config/mail-config.php';
require_once __DIR__ . '/api/mailer.php';

echo "<!DOCTYPE html><html><head><title>Test MeetDesk Email</title></head><body>";
echo "<h2>Testing MeetDesk Email Configuration</h2>";

// Display configuration
echo "<h3>Current Configuration:</h3>";
echo "<pre>";
echo "SMTP Host: " . MAIL_CONFIG['smtp_host'] . "\n";
echo "SMTP Port: " . MAIL_CONFIG['smtp_port'] . "\n";
echo "SMTP User: " . MAIL_CONFIG['smtp_user'] . "\n";
echo "Sender Email: " . MAIL_CONFIG['sender_email'] . "\n";
echo "Sender Name: " . MAIL_CONFIG['sender_name'] . "\n";
echo "</pre>";

// Test SMTP connection
echo "<h3>Testing SMTP Connection...</h3>";

try {
    $mailer = new MailSender();
    
    // Send test email
    $testEmail = 'bhavnavaishnani13@gmail.com'; // Your test email
    
    $htmlBody = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #4285F4;'>MeetDesk Email Test</h2>
        <p>This is a test email from your new MeetDesk professional email address.</p>
        <p><strong>Email:</strong> meetdesk26@gmail.com</p>
        <p><strong>Sender Name:</strong> MeetDesk</p>
        <hr>
        <p style='color: #666; font-size: 12px;'>If you received this email, your MeetDesk email configuration is working correctly!</p>
    </body>
    </html>
    ";
    
    echo "<p>Sending test email to: <strong>$testEmail</strong></p>";
    
    $result = $mailer->sendEmail(
        $testEmail,
        '',
        'MeetDesk Email Configuration Test',
        $htmlBody
    );
    
    echo "<h3>Result:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green; font-weight: bold;'>✓ Email sent successfully!</p>";
        echo "<p>Check your inbox at: <strong>$testEmail</strong></p>";
        echo "<p><strong>Important:</strong> Check your SPAM/Junk folder if you don't see it in inbox.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Failed to send email</p>";
        echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
