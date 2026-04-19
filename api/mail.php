<?php
/**
 * EMAIL CONFIGURATION & HELPERS
 * 
 * This file contains email settings and helper functions
 * for sending emails from the application
 */

// ===== EMAIL SETTINGS =====
define('MAIL_FROM_EMAIL', 'noreply@meetdesk.com');
define('MAIL_FROM_NAME', 'Meet Desk');
define('MAIL_SUPPORT_EMAIL', 'support@meetdesk.com');

/**
 * sendEmail()
 * Sends HTML email to specified recipient
 * 
 * Parameters:
 * - $to: Recipient email address
 * - $subject: Email subject
 * - $message: HTML email message
 * 
 * Returns: true/false based on whether email was sent
 */
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . MAIL_SUPPORT_EMAIL . "\r\n";
    
    // Send email (suppress errors with @)
    return @mail($to, $subject, $message, $headers);
}

/**
 * generateWelcomeEmail()
 * Creates HTML content for welcome email
 * 
 * Parameters:
 * - $name: User's full name
 * - $email: User's email address
 * 
 * Returns: HTML email content
 */
function generateWelcomeEmail($name, $email) {
    $appUrl = 'http://localhost/MD/';
    $registeredDate = date('F d, Y');
    
    return "
    <html>
      <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto;'>
          <!-- Header -->
          <div style='background-color: #4285F4; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='margin: 0; font-size: 28px;'>Meet Desk</h1>
            <p style='margin: 5px 0 0 0; font-size: 14px;'>Video Conferencing Platform</p>
          </div>
          
          <!-- Body -->
          <div style='background-color: #f5f5f5; padding: 30px; border-radius: 0 0 8px 8px;'>
            <h2 style='color: #4285F4; margin-top: 0;'>Welcome, $name!</h2>
            
            <p style='font-size: 16px; line-height: 1.6;'>
              Thank you for registering with <strong>Meet Desk</strong>. We're excited to have you on board!
            </p>
            
            <!-- Account Details Box -->
            <div style='background-color: white; padding: 15px 20px; border-left: 4px solid #4285F4; border-radius: 4px; margin: 20px 0;'>
              <p style='margin-top: 0; font-weight: bold;'>Your Account Details:</p>
              <ul style='margin: 10px 0; padding-left: 20px;'>
                <li><strong>Name:</strong> $name</li>
                <li><strong>Email:</strong> $email</li>
                <li><strong>Registered:</strong> $registeredDate</li>
              </ul>
            </div>
            
            <!-- What You Can Do -->
            <h3 style='color: #333; margin-top: 25px;'>What You Can Do Now:</h3>
            <ul style='line-height: 1.8;'>
              <li>📅 Schedule meetings with your team</li>
              <li>🔗 Join existing meetings using meeting codes</li>
              <li>👤 Manage your profile and settings</li>
              <li>📊 View your meeting history</li>
            </ul>
            
            <!-- Getting Started -->
            <h3 style='color: #333;'>Getting Started:</h3>
            <ol style='line-height: 1.8; background-color: white; padding: 15px 20px; border-radius: 4px;'>
              <li>Login to your account: <a href='$appUrl' style='color: #4285F4; text-decoration: none;'>$appUrl</a></li>
              <li>Go to Dashboard to schedule your first meeting</li>
              <li>Invite others to join your meetings</li>
            </ol>
            
            <!-- Footer -->
            <p style='color: #666; font-size: 13px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; line-height: 1.6;'>
              If you have any questions or need assistance, please don't hesitate to contact our support team at <a href='mailto:" . MAIL_SUPPORT_EMAIL . "' style='color: #4285F4; text-decoration: none;'>" . MAIL_SUPPORT_EMAIL . "</a>
            </p>
            
            <p style='color: #999; font-size: 12px; margin-top: 15px; text-align: center;'>
              © 2026 Meet Desk. All rights reserved.
            </p>
          </div>
        </div>
      </body>
    </html>
    ";
}
?>