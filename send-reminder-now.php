<?php
/**
 * Manual Reminder Sender - Send reminders for upcoming meetings NOW
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/email-templates.php';

echo "<!DOCTYPE html><html><head><title>Send Reminders Now</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h2>📧 Send Meeting Reminders Now</h2>";

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $currentTime = $now->format('H:i');
    
    echo "<p class='info'>Current Time: <strong>{$today} {$currentTime}</strong></p>";
    
    // Look for meetings in the next 35 minutes that haven't been reminded
    $querySimple = new MongoDB\Driver\Query([
        'reminderSent' => false
    ]);
    
    $cursor = $manager->executeQuery($namespace, $querySimple);
    $allMeetings = $cursor->toArray();
    
    echo "<p class='info'>Total meetings without reminders: <strong>" . count($allMeetings) . "</strong></p>";
    
    // Filter for meetings in the next 35 minutes
    $meetingsToRemind = [];
    foreach ($allMeetings as $meeting) {
        try {
            $meetingDateTime = new DateTime($meeting->date . ' ' . $meeting->time);
            $timeDiff = $meetingDateTime->getTimestamp() - $now->getTimestamp();
            $minutesUntil = round($timeDiff / 60);
            
            echo "<p>Meeting: <strong>{$meeting->topic}</strong> at {$meeting->date} {$meeting->time} (in {$minutesUntil} minutes)</p>";
            
            // If meeting is between now and 35 minutes from now
            if ($timeDiff > 0 && $timeDiff <= 35 * 60) {
                $meetingsToRemind[] = $meeting;
                echo "<p class='success'>→ This meeting qualifies for reminder (within 35 min window)</p>";
            } else if ($timeDiff <= 0) {
                echo "<p class='error'>→ Meeting already started or passed</p>";
            } else {
                echo "<p>→ Meeting is more than 35 minutes away</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error parsing meeting time: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Meetings to Send Reminders For: " . count($meetingsToRemind) . "</h3>";
    
    if (empty($meetingsToRemind)) {
        echo "<p class='error'>No meetings found in the next 35 minutes that need reminders.</p>";
        echo "<p><strong>Possible reasons:</strong></p>";
        echo "<ul>";
        echo "<li>Meeting is more than 35 minutes away</li>";
        echo "<li>Meeting already passed</li>";
        echo "<li>Reminder already sent (reminderSent = true)</li>";
        echo "</ul>";
        exit;
    }
    
    // Send reminders
    $sentCount = 0;
    $failedCount = 0;
    
    foreach ($meetingsToRemind as $meeting) {
        echo "<hr>";
        echo "<h4>Processing: {$meeting->topic}</h4>";
        
        try {
            $meetingArray = (array)$meeting;
            
            // Send to organizer
            echo "<p>Sending to organizer: <strong>{$meeting->userEmail}</strong>... ";
            sendMeetingReminderEmail($meeting->userEmail, $meetingArray);
            echo "<span class='success'>✓ Sent</span></p>";
            
            // Send to attendees
            if (!empty($meeting->attendeeEmails) && is_array($meeting->attendeeEmails)) {
                echo "<p>Attendees: " . count($meeting->attendeeEmails) . "</p>";
                foreach ($meeting->attendeeEmails as $attendeeEmail) {
                    echo "<p>Sending to attendee: <strong>{$attendeeEmail}</strong>... ";
                    try {
                        sendMeetingReminderEmail($attendeeEmail, $meetingArray);
                        echo "<span class='success'>✓ Sent</span></p>";
                    } catch (Exception $e) {
                        echo "<span class='error'>✗ Failed: " . $e->getMessage() . "</span></p>";
                    }
                }
            } else {
                echo "<p>No attendees for this meeting</p>";
            }
            
            // Mark as sent
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(
                ['_id' => $meeting->_id],
                [
                    '$set' => [
                        'reminderSent' => true,
                        'reminderSentAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
                    ]
                ]
            );
            $manager->executeBulkWrite($namespace, $bulk);
            
            echo "<p class='success'>✓ Marked as sent in database</p>";
            $sentCount++;
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
            $failedCount++;
        }
    }
    
    echo "<hr>";
    echo "<h3 class='success'>Summary</h3>";
    echo "<p>Reminders sent successfully: <strong>{$sentCount}</strong></p>";
    echo "<p>Failed: <strong>{$failedCount}</strong></p>";
    echo "<p><strong>Check your email inbox (and spam folder)!</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
