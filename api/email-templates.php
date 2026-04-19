<?php
/**
 * EMAIL TEMPLATES & FUNCTIONS
 * 
 * 3 Email Scenarios:
 * 1. Meeting Scheduled - Immediate after scheduling
 * 2. Meeting Reminder - 30 minutes before meeting
 * 3. Meeting Changed - When edited/cancelled (email only, no toast)
 */

/**
 * EMAIL 1: MEETING SCHEDULED
 * Sent immediately when meeting is created
 */
function sendMeetingScheduledEmail($attendeeEmail, $attendeeName, $meeting) {
    $topic = htmlspecialchars($meeting['topic']);
    $date = date('F d, Y', strtotime($meeting['date']));
    $time = date('h:i A', strtotime($meeting['time']));
    $duration = $meeting['duration'];
    $timezone = $meeting['timezone'];
    $meetingId = $meeting['meetingId'];
    $password = $meeting['password'];
    $joinLink = "https://meetdesk.com/join/" . $meetingId;
    $hostName = htmlspecialchars($meeting['userName']);
    $description = isset($meeting['description']) && !empty($meeting['description']) 
        ? "<div style='background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #4285F4;'><strong>Description:</strong><br>" . nl2br(htmlspecialchars($meeting['description'])) . "</div>"
        : '';
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #4285F4 0%, #3367D6 100%); color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .meeting-details { background: #e3f2fd; padding: 20px; border-left: 5px solid #4285F4; margin: 20px 0; border-radius: 5px; }
            .join-section { background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .code-box { background: white; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; margin: 10px 0; }
            .button { display: inline-block; padding: 12px 35px; background: #4285F4; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; }
            .button:hover { background: #3367D6; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📅 You're Invited to a Meeting!</h1>
            </div>
            
            <div class='content'>
                <p>Hi <strong>{$attendeeName}</strong>,</p>
                
                <p><strong>{$hostName}</strong> has scheduled a meeting with you.</p>
                
                <div class='meeting-details'>
                    <h3 style='margin-top: 0;'>Meeting Details</h3>
                    <p style='margin: 8px 0;'>
                        <strong>📌 Topic:</strong> {$topic}<br>
                        <strong>📅 Date:</strong> {$date}<br>
                        <strong>🕐 Time:</strong> {$time}<br>
                        <strong>⏱️ Duration:</strong> {$duration} minutes<br>
                        <strong>🌍 Timezone:</strong> {$timezone}
                    </p>
                </div>
                
                {$description}
                
                <div class='join-section'>
                    <h3>How to Join</h3>
                    <p>Use these credentials to join the meeting:</p>
                    <p><strong>Meeting ID:</strong></p>
                    <div class='code-box'>{$meetingId}</div>
                    <p><strong>Password:</strong></p>
                    <div class='code-box'>{$password}</div>
                    <p><a href='{$joinLink}' class='button'>Join Meeting</a></p>
                    <p style='font-size: 12px; color: #666; margin-top: 15px;'>Or use the link: <br><a href='{$joinLink}' style='color: #4285F4;'>{$joinLink}</a></p>
                </div>
                
                <p style='color: #666; font-size: 12px; margin-top: 20px;'>
                    Save these credentials in case you need them later.
                </p>
            </div>
            
            <div class='footer'>
                <p>© 2026 Meet Desk. All rights reserved.</p>
                <p>This email was sent to {$attendeeEmail}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    sendEmail(
        $attendeeEmail,
        "Meeting Scheduled: {$topic}",
        $htmlBody
    );
}

/**
 * EMAIL 2: MEETING REMINDER
 * Sent 30 minutes before meeting starts
 */
function sendMeetingReminderEmail($attendeeEmail, $meeting) {
    $topic = htmlspecialchars($meeting['topic']);
    $time = date('h:i A', strtotime($meeting['time']));
    $timezone = $meeting['timezone'];
    $meetingId = $meeting['meetingId'];
    $password = $meeting['password'];
    $joinLink = "https://meetdesk.com/join/" . $meetingId;
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .alert { background: #fff3cd; padding: 20px; border-left: 5px solid #ff9800; margin: 20px 0; border-radius: 5px; }
            .join-section { background: #e8f5e9; padding: 25px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .code-box { background: white; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 20px; font-weight: bold; margin: 10px 0; letter-spacing: 2px; }
            .button { display: inline-block; padding: 12px 40px; background: #ff9800; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; font-size: 16px; }
            .button:hover { background: #f57c00; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⏰ Meeting Starting in 30 Minutes!</h1>
            </div>
            
            <div class='content'>
                <div class='alert'>
                    <h2 style='margin-top: 0;'>Your meeting <strong>{$topic}</strong> is starting soon!</h2>
                    <p style='margin-bottom: 0;'>Join now or in a few minutes.</p>
                </div>
                
                <div class='join-section'>
                    <p>Ready to join? Here are your meeting details:</p>
                    <p><strong>Meeting ID:</strong></p>
                    <div class='code-box'>{$meetingId}</div>
                    <p><strong>Password:</strong></p>
                    <div class='code-box'>{$password}</div>
                    <p style='font-size: 12px; color: #666; margin: 15px 0;'>
                        <strong>Start time:</strong> {$time} {$timezone}
                    </p>
                    <a href='{$joinLink}' class='button'>Click Here to Join Now</a>
                </div>
                
                <p style='color: #666; font-size: 12px;'>
                    If you can't join right now, remember these credentials and join later using them.
                </p>
            </div>
            
            <div class='footer'>
                <p>© 2026 Meet Desk. All rights reserved.</p>
                <p>This is an automated reminder from Meet Desk</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    require_once __DIR__ . '/mailer.php';
    $mailer = new MailSender();
    $result = $mailer->sendEmail(
        $attendeeEmail,
        '',
        "Reminder: Meeting Starting in 30 Minutes!",
        $htmlBody
    );
    
    return $result['success'] ?? false;
}

/**
 * EMAIL 3: MEETING CHANGED/CANCELLED
 * Sent only when meeting is edited or deleted (EMAIL ONLY, no in-app toast)
 */
function sendMeetingChangedEmail($attendeeEmail, $meeting, $changeType, $changeDetails) {
    $topic = htmlspecialchars($meeting['topic']);
    $hostName = htmlspecialchars($meeting['userName']);
    $hostEmail = $meeting['userEmail'];
    
    $changeMessages = array(
        'cancelled' => array(
            'title' => '🚫 Meeting Cancelled',
            'message' => 'The meeting has been cancelled.',
            'color' => '#f44336'
        ),
        'postponed' => array(
            'title' => '⏸️ Meeting Postponed',
            'message' => 'The meeting has been postponed to a later date/time.',
            'color' => '#ff9800'
        ),
        'preponed' => array(
            'title' => '⬆️ Meeting Moved Up',
            'message' => 'The meeting has been moved to an earlier date/time.',
            'color' => '#ff9800'
        ),
        'modified' => array(
            'title' => '✏️ Meeting Updated',
            'message' => 'The meeting details have been updated.',
            'color' => '#2196F3'
        )
    );
    
    $info = $changeMessages[$changeType] ?? $changeMessages['modified'];
    $color = $info['color'];
    
    $changeDetailsHtml = '';
    if (!empty($changeDetails)) {
        $changeDetailsHtml = "<div style='background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        foreach ($changeDetails as $detail) {
            $changeDetailsHtml .= "<p style='margin: 8px 0;'><strong>{$detail['label']}:</strong> {$detail['value']}</p>";
        }
        $changeDetailsHtml .= "</div>";
    }
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, {$color} 0%, rgba({$color},0.8) 100%); color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .alert { background: #ffebee; padding: 20px; border-left: 5px solid {$color}; margin: 20px 0; border-radius: 5px; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$info['title']}</h1>
            </div>
            
            <div class='content'>
                <div class='alert'>
                    <h2 style='margin-top: 0;'>{$info['message']}</h2>
                    <p><strong>Meeting:</strong> {$topic}</p>
                </div>
                
                {$changeDetailsHtml}
                
                <p>If you have any questions, please contact:</p>
                <p style='background: #f9f9f9; padding: 10px; border-radius: 5px;'>
                    <strong>{$hostName}</strong><br>
                    <a href='mailto:{$hostEmail}'>{$hostEmail}</a>
                </p>
            </div>
            
            <div class='footer'>
                <p>© 2026 Meet Desk. All rights reserved.</p>
                <p>This notification was sent by Meet Desk</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $subject = match($changeType) {
        'cancelled' => "Meeting Cancelled: {$topic}",
        'postponed' => "Meeting Postponed: {$topic}",
        'preponed' => "Meeting Moved Up: {$topic}",
        'modified' => "Meeting Updated: {$topic}",
        default => "Meeting Changed: {$topic}"
    };
    
    sendEmail($attendeeEmail, $subject, $htmlBody);
}

/**
 * SEND EMAIL HELPER FUNCTION
 * Uses the existing mailer setup
 */
function sendEmail($toEmail, $subject, $htmlBody) {
    // Get mail configuration from existing mailer
    if (!function_exists('sendEmailViaMailer')) {
        // Fallback if using existing mailer
        require_once __DIR__ . '/mailer.php';
    }
    
    // Use existing email infrastructure
    return sendEmailViaMailer($toEmail, $subject, $htmlBody);
}

/**
 * BULK EMAIL SENDER
 * Send email to multiple attendees
 */
function sendEmailToAttendees($attendeeEmails, $subject, $function, $params) {
    $results = array();
    
    foreach ($attendeeEmails as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                call_user_func_array($function, array_merge($params, [$email]));
                $results[$email] = array('success' => true);
            } catch (Exception $e) {
                error_log("Failed to send email to {$email}: " . $e->getMessage());
                $results[$email] = array('success' => false, 'error' => $e->getMessage());
            }
        } else {
            error_log("Invalid email format: {$email}");
            $results[$email] = array('success' => false, 'error' => 'Invalid email format');
        }
    }
    
    return $results;
}

/**
 * EMAIL VERIFICATION
 * Sent after registration to verify email address
 */
function sendEmailVerificationEmail($userEmail, $userName, $verificationLink) {
    $userName = htmlspecialchars($userName);
    $userEmail = htmlspecialchars($userEmail);
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #4285F4 0%, #3367D6 100%); color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .verification-box { background: #e3f2fd; padding: 20px; border-left: 5px solid #4285F4; margin: 20px 0; border-radius: 5px; text-align: center; }
            .button { display: inline-block; padding: 12px 35px; background: #4285F4; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; font-size: 16px; }
            .button:hover { background: #3367D6; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ddd; }
            .warning { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; border-radius: 3px; margin: 15px 0; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✉️ Verify Your Email</h1>
            </div>
            
            <div class='content'>
                <p>Hi {$userName},</p>
                
                <p>Welcome to <strong>Meet Desk!</strong> To complete your registration and start using our service, please verify your email address.</p>
                
                <div class='verification-box'>
                    <p><strong>Click the button below to verify your email:</strong></p>
                    <a href='{$verificationLink}' class='button'>Verify Email Address</a>
                    <p style='margin-top: 15px; font-size: 12px; color: #666;'>
                        This link will expire in 24 hours
                    </p>
                </div>
                
                <div class='warning'>
                    <strong>Didn't register?</strong> If you didn't create this account, you can safely ignore this email or click <a href='http://localhost/MD'>here</a> to report abuse.
                </div>
                
                <p style='margin-top: 30px;'>Best regards,<br><strong>Meet Desk Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>Meet Desk - Video Conferencing Made Easy</p>
                <p style='margin: 5px 0;'>© 2026 Meet Desk. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($userEmail, '✉️ Verify Your Email - Meet Desk', $htmlBody);
}

/**
 * EMAIL 4: ENHANCED PASSWORD RESET
 * Sent when user requests password reset
 */
function sendPasswordResetEmailEnhanced($userEmail, $userName, $resetCode) {
    $expiryTime = date('g:i A', time() + 1800);
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #f44336 0%, #e53935 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: white; padding: 30px; }
            .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
            .alert-box { background: #ffebee; border-left: 5px solid #f44336; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .alert-box strong { color: #f44336; }
            .code-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .code-label { font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
            .reset-code { font-size: 32px; font-weight: bold; color: #f44336; letter-spacing: 4px; font-family: 'Courier New', monospace; }
            .expiry-info { background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 13px; }
            .steps-section { margin: 25px 0; }
            .steps-section h3 { color: #f44336; margin-bottom: 15px; }
            .steps-section ol { padding-left: 20px; }
            .steps-section li { margin: 10px 0; line-height: 1.8; }
            .security-tips { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 13px; }
            .security-tips strong { color: #2e7d32; }
            .suspicious-activity { background: #f3e5f5; border-left: 4px solid #9c27b0; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 12px; }
            .suspicious-activity a { color: #7b1fa2; text-decoration: underline; }
            .divider { border-top: 1px solid #ddd; margin: 20px 0; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ddd; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Hi <strong>{$userName}</strong>,
                </div>
                
                <p>We received a request to reset your Meet Desk password. Use the code below to create a new password.</p>
                
                <div class='alert-box'>
                    <strong>⏰ Important:</strong> This reset code expires in 30 minutes. If you didn't request this, please ignore this email.
                </div>
                
                <div class='code-section'>
                    <div class='code-label'>Your Reset Code</div>
                    <div class='reset-code'>{$resetCode}</div>
                </div>
                
                <div class='expiry-info'>
                    ⏱️ This code will expire at <strong>{$expiryTime}</strong> today (30 minutes from now)
                </div>
                
                <div class='steps-section'>
                    <h3>📋 How to Reset Your Password:</h3>
                    <ol>
                        <li>Go to the <strong>Meet Desk login page</strong></li>
                        <li>Click on <strong>&quot;Forgot Password?&quot;</strong></li>
                        <li>Enter your email address</li>
                        <li>Enter the reset code: <strong style='color: #f44336;'>{$resetCode}</strong></li>
                        <li>Create a <strong>strong, new password</strong></li>
                        <li>Log in with your new password</li>
                    </ol>
                </div>
                
                <div class='security-tips'>
                    <strong>🛡️ Security Tips:</strong>
                    <ul>
                        <li>Use a password with at least 8 characters</li>
                        <li>Mix uppercase, lowercase, numbers, and symbols</li>
                        <li>Never share your reset code with anyone</li>
                        <li>Meet Desk staff will never ask for your password or reset code</li>
                    </ul>
                </div>
                
                <div class='suspicious-activity'>
                    <strong>🚨 Suspicious Activity?</strong><br>
                    If you didn't request this password reset or don't recognize this activity, <a href='mailto:support@meetdesk.com'>contact our support team immediately</a>. We'll help secure your account.
                </div>
                
                <div class='divider'></div>
                
                <p>Best regards,<br><strong>Meet Desk Security Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>Meet Desk - Video Conferencing Made Easy</p>
                <p style='margin: 5px 0;'>© 2026 Meet Desk. All rights reserved.</p>
                <p style='margin: 10px 0; font-size: 11px; color: #bbb;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($userEmail, '🔐 Reset Your Meet Desk Password', $htmlBody);
}

?>

?"