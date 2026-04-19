<?php
/**
 * Send ALL Pending Reminders - Ignore Time Window
 * Open: http://localhost/MD/send-all-reminders.php
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/email-templates.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Send All Reminders</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".log{background:white;padding:20px;border-radius:8px;margin:10px 0;border-left:4px solid #4285F4;}";
echo ".success{border-left-color:#22c55e;}.error{border-left-color:#ef4444;}</style></head><body>";
echo "<h1>🔔 Send All Pending Reminders</h1>";

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    echo "<div class='log'>✓ Connected to MongoDB</div>";
    
    // Get today's date
    $today = date('Y-m-d');
    
    echo "<div class='log'>Date: {$today}</div>";
    
    // Query for ALL meetings today that haven't had reminders sent
    $query = new MongoDB\Driver\Query([
        'reminderSent' => false,
        'date' => $today
    ]);
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = $cursor->toArray();
    
    echo "<div class='log'>Found " . count($meetings) . " meetings without reminders</div>";
    
    if (count($meetings) == 0) {
        echo "<div class='log'>No meetings found that need reminders.</div>";
    }
    
    $sentCount = 0;
    foreach ($meetings as $meeting) {
        echo "<div class='log'>";
        echo "<strong>{$meeting->topic}</strong><br>";
        echo "Time: {$meeting->time}<br>";
        echo "Meeting ID: {$meeting->meetingId}<br>";
        echo "Type: " . ($meeting->isPublic ? 'Public' : 'Private') . "<br>";
        
        // Convert meeting object to array for email function
        $meetingArray = [
            'topic' => $meeting->topic,
            'date' => $meeting->date,
            'time' => $meeting->time,
            'meetingId' => $meeting->meetingId,
            'password' => $meeting->password ?? '',
            'timezone' => $meeting->timezone ?? 'IST',
            'userName' => $meeting->userName ?? 'User'
        ];
        
        // Collect all recipients
        $recipients = [];
        
        // Add organizer
        if (!empty($meeting->userEmail)) {
            $recipients[] = $meeting->userEmail;
        }
        
        // Add attendees for private meetings
        if (!$meeting->isPublic && !empty($meeting->attendeeEmails)) {
            $attendeeEmails = is_array($meeting->attendeeEmails) 
                ? $meeting->attendeeEmails 
                : (array)$meeting->attendeeEmails;
            
            foreach ($attendeeEmails as $email) {
                if (!empty($email) && !in_array($email, $recipients)) {
                    $recipients[] = $email;
                }
            }
        }
        
        echo "Recipients: " . count($recipients) . " (" . implode(', ', $recipients) . ")<br>";
        
        // Send to all recipients
        $successCount = 0;
        foreach ($recipients as $recipientEmail) {
            echo "→ Sending to: {$recipientEmail}... ";
            
            $emailSent = sendMeetingReminderEmail($recipientEmail, $meetingArray);
            
            if ($emailSent) {
                echo "<span style='color:#22c55e'>✓</span><br>";
                $successCount++;
            } else {
                echo "<span style='color:#ef4444'>✗</span><br>";
            }
        }
        
        // Mark as sent if at least one email was successful
        if ($successCount > 0) {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(
                ['_id' => $meeting->_id],
                ['$set' => ['reminderSent' => true]]
            );
            $manager->executeBulkWrite($namespace, $bulk);
            
            echo "<span style='color:#22c55e;font-weight:bold'>✓ Sent {$successCount}/{" . count($recipients) . "} reminders</span><br>";
            $sentCount += $successCount;
        } else {
            echo "<span style='color:#ef4444;font-weight:bold'>✗ All emails failed</span><br>";
        }
        
        echo "</div>";
    }
    
    echo "<div class='log success'><strong>✓ Complete!</strong><br>Sent {$sentCount} reminder(s)</div>";
    
} catch (Exception $e) {
    echo "<div class='log error'><strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<br><a href='dashboard.html' style='display:inline-block;padding:10px 20px;background:#4285F4;color:white;text-decoration:none;border-radius:6px;'>← Back to Dashboard</a>";
echo "</body></html>";
