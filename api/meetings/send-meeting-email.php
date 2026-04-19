<?php
/**
 * SEND MEETING EMAIL API
 * Purpose: Send meeting invitation to a specified email address
 * Method: POST
 * Parameters: email, meetingId, recipientEmail
 */

// Error handling FIRST - before any other code
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Enable output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        error_log('Fatal error in send-meeting-email.php: ' . $error['message']);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $error['message']]);
    }
});

try {
    require_once '../config.php';
    require_once '../mailer.php';
    
    // Parse request data
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);
    
    if (!$data || !isset($data['email'], $data['meetingId'], $data['recipientEmail'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Get meeting details from MongoDB
    $manager = getManager();
    $query = new MongoDB\Driver\Query(['meetingId' => $data['meetingId']]);
    $cursor = $manager->executeQuery('meetdesk.meetings', $query);
    $meeting = null;
    
    foreach ($cursor as $document) {
        $meeting = $document;
        break;
    }
    
    if (!$meeting) {
      ob_end_clean();
      http_response_code(404);
      echo json_encode(['success' => false, 'message' => 'Meeting not found']);
      exit;
    }
    
    // Verify organizer (user who created the meeting)
    if ($meeting->userEmail !== $data['email']) {
      ob_end_clean();
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'You don\'t have permission to share this meeting']);
      exit;
    }
    
    // Prepare email content
    $meetingDate = new DateTime($meeting->date);
    $formattedDate = $meetingDate->format('l, F j, Y');
    $organizer = isset($meeting->userName) ? $meeting->userName : 'Your meeting organizer';
    
    $emailSubject = "Meeting Invitation: " . $meeting->topic;
    
    // Create HTML email body
    $emailBody = "
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
            .code-box { background: white; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; margin: 10px 0; text-align: center; background: #f5f5f5; border: 2px solid #4285F4; }
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
                <p>Dear Friend,</p>
                <p><strong>" . htmlspecialchars($organizer) . "</strong> has invited you to join a meeting.</p>
                
                <div class='meeting-details'>
                    <h2 style='margin-top: 0; color: #4285F4;'>" . htmlspecialchars($meeting->topic) . "</h2>
                    <p><strong>Date:</strong> " . $formattedDate . "</p>
                    <p><strong>Time:</strong> " . $meeting->time . "</p>
                    <p><strong>Duration:</strong> " . $meeting->duration . " minutes</p>
                    <p><strong>Timezone:</strong> " . $meeting->timezone . "</p>
                    " . (isset($meeting->description) && !empty($meeting->description) ? "<p><strong>Description:</strong> " . htmlspecialchars($meeting->description) . "</p>" : "") . "
                </div>
                
                <div class='join-section'>
                    <h3>Join the Meeting</h3>
                    <p><strong>Meeting ID:</strong></p>
                    <div class='code-box'>" . $meeting->meetingId . "</div>
                    
                    <p><strong>Password:</strong></p>
                    <div class='code-box'>" . $meeting->password . "</div>
                </div>
                
                <p style='text-align: center;'>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/MD/join.html?id=" . $meeting->meetingId . "' class='button'>Join Meeting</a>
                </p>
                
                <p>Looking forward to meeting with you!</p>
                <p>Best regards,<br><strong>MeetDesk Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated email. Please do not reply to this address.</p>
                <p>&copy; 2026 MeetDesk. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using MailSender class
    $mailer = new MailSender();
    $result = $mailer->sendEmail($data['recipientEmail'], 'Attendee', $emailSubject, $emailBody);
    
    if ($result['success']) {
      ob_end_clean();
      http_response_code(200);
      echo json_encode([
        'success' => true,
        'message' => "Meeting invitation sent to " . $data['recipientEmail']
      ]);
    } else {
      ob_end_clean();
      http_response_code(500);
      echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Failed to send email'
      ]);
    }
    
} catch (Exception $e) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage()
  ]);
}
