<?php
/**
 * Manual Reminder Test - Run via Browser
 * Open: http://localhost/MD/test-reminder-now.php
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/email-templates.php';
require_once __DIR__ . '/cron/CronLogger.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Send Reminders Now</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".log{background:white;padding:20px;border-radius:8px;margin:10px 0;border-left:4px solid #4285F4;}";
echo ".success{border-left-color:#22c55e;}.error{border-left-color:#ef4444;}</style></head><body>";
echo "<h1>🔔 Send Meeting Reminders</h1>";

$logger = new CronLogger('Manual Reminder Test');
$logger->info('Starting manual reminder test');

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    echo "<div class='log'>✓ Connected to MongoDB</div>";
    
    // Get current time
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $currentTime = $now->format('H:i');
    
    echo "<div class='log'>Current Time: {$today} {$currentTime}</div>";
    
    // Look for meetings in the next 35 minutes
    $reminderTime = new DateTime('+35 minutes');
    $reminderDate = $reminderTime->format('Y-m-d');
    $reminderTimeStr = $reminderTime->format('H:i');
    
    echo "<div class='log'>Looking for meetings between now and {$reminderTimeStr}</div>";
    
    // Query for meetings that need reminders
    $query = new MongoDB\Driver\Query([
        'reminderSent' => false,
        'date' => $today
    ]);
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = $cursor->toArray();
    
    echo "<div class='log'>Found " . count($meetings) . " meetings without reminders sent</div>";
    
    $sentCount = 0;
    foreach ($meetings as $meeting) {
        $meetingTime = $meeting->time;
        $meetingDateTime = new DateTime("{$today} {$meetingTime}");
        $timeDiff = ($meetingDateTime->getTimestamp() - $now->getTimestamp()) / 60; // minutes
        
        echo "<div class='log'>";
        echo "<strong>{$meeting->topic}</strong><br>";
        echo "Time: {$meetingTime} (in " . round($timeDiff) . " minutes)<br>";
        
        // Send reminder if meeting is 20-40 minutes away
        if ($timeDiff >= 20 && $timeDiff <= 40) {
            echo "✓ Sending reminder...<br>";
            
            // Get organizer email
            $userEmail = $meeting->userEmail ?? '';
            
            if ($userEmail) {
                $emailSent = sendMeetingReminderEmail(
                    $userEmail,
                    $meeting->userName ?? 'User',
                    $meeting->topic,
                    $meeting->date,
                    $meeting->time,
                    $meeting->meetingId
                );
                
                if ($emailSent) {
                    // Mark as sent
                    $bulk = new MongoDB\Driver\BulkWrite();
                    $bulk->update(
                        ['_id' => $meeting->_id],
                        ['$set' => ['reminderSent' => true]]
                    );
                    $manager->executeBulkWrite($namespace, $bulk);
                    
                    echo "<span style='color:#22c55e'>✓ Reminder sent to {$userEmail}</span><br>";
                    $sentCount++;
                } else {
                    echo "<span style='color:#ef4444'>✗ Failed to send email</span><br>";
                }
            } else {
                echo "<span style='color:#ef4444'>✗ No email address</span><br>";
            }
        } else {
            echo "⏭ Skipped (not in 25-35 minute window)<br>";
        }
        echo "</div>";
    }
    
    echo "<div class='log success'><strong>✓ Complete!</strong><br>Sent {$sentCount} reminder(s)</div>";
    
} catch (Exception $e) {
    echo "<div class='log error'><strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    $logger->error('Error: ' . $e->getMessage());
}

echo "<br><a href='dashboard.html' style='display:inline-block;padding:10px 20px;background:#4285F4;color:white;text-decoration:none;border-radius:6px;'>← Back to Dashboard</a>";
echo "</body></html>";
