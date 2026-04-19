<?php
/**
 * SEND MEETING REMINDERS
 * 
 * Runs every 30 minutes (or as a cron job)
 * Finds meetings starting in 30 minutes
 * Sends reminder emails to attendees
 * 
 * Usage:
 * curl https://meetdesk.com/api/meetings/send-reminders.php
 * 
 * Cron job:
 * */30 * * * * curl -s https://meetdesk.com/api/meetings/send-reminders.php
 */

require_once '../config.php';
require_once '../email-templates.php';

// ===== SECURITY: Only allow from localhost or valid source =====
$allowedIPs = ['127.0.0.1', 'localhost', '::1'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// For production, you might want to check a secret token instead
// if ($_GET['secret'] !== getenv('REMINDER_SECRET_TOKEN')) {
//     http_response_code(403);
//     jsonResponse(['error' => 'Unauthorized'], 403);
//     exit;
// }

// ===== FIND MEETINGS STARTING IN ~30 MINUTES =====
try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    // Calculate time window: now to 35 minutes from now
    // (to catch meetings within a 5-minute window)
    $now = time();
    $thirtyMinFromNow = $now + (30 * 60);
    $window = 5 * 60; // 5-minute window
    
    $startTime = new MongoDB\BSON\UTCDateTime($now * 1000);
    $endTime = new MongoDB\BSON\UTCDateTime(($thirtyMinFromNow + $window) * 1000);
    
    error_log("=== REMINDER JOB STARTED ===");
    error_log("Looking for meetings between " . date('Y-m-d H:i:s', $now) . " and " . date('Y-m-d H:i:s', ($thirtyMinFromNow + $window)));
    
    // Query to find meetings that:
    // 1. Are scheduled
    // 2. Have NOT sent reminder yet
    // 3. Meet in approximately 30 minutes
    $query = new MongoDB\Driver\Query([
        'status' => 'scheduled',
        'reminderSent' => false,
        'reminderScheduledFor' => [
            '$gte' => $startTime,
            '$lte' => $endTime
        ]
    ], ['limit' => 100]); // Max 100 per run
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = [];
    
    foreach ($cursor as $doc) {
        $meetings[] = $doc;
    }
    
    error_log("Found " . count($meetings) . " meetings to send reminders for");
    
    $remindersCount = 0;
    $failureCount = 0;
    $results = [];
    
    // ===== SEND REMINDER EMAIL FOR EACH MEETING =====
    foreach ($meetings as $meeting) {
        try {
            // Convert MongoDB document to array
            $meetingArray = (array)$meeting;
            
            // Only send reminder for private meetings with attendees
            if ($meetingArray['isPublic'] === false && !empty($meetingArray['attendeeEmails'])) {
                
                error_log("Sending reminder for meeting: " . $meetingArray['topic']);
                
                // Send email to each attendee
                foreach ($meetingArray['attendeeEmails'] as $attendeeEmail) {
                    try {
                        sendMeetingReminderEmail($attendeeEmail, $meetingArray);
                        $remindersCount++;
                        error_log("✓ Reminder sent to: $attendeeEmail");
                    } catch (Exception $e) {
                        error_log("✗ Failed to send reminder to $attendeeEmail: " . $e->getMessage());
                        $failureCount++;
                    }
                }
                
                // Update meeting to mark reminder as sent
                $update = new MongoDB\Driver\BulkWrite();
                $update->update(
                    ['_id' => $meeting->_id],
                    ['$set' => ['reminderSent' => true]]
                );
                $manager->executeBulkWrite($namespace, $update);
                
                error_log("Updated meeting record");
                
                $results[] = [
                    'meetingId' => $meetingArray['meetingId'],
                    'topic' => $meetingArray['topic'],
                    'status' => 'success',
                    'attendeeCount' => count($meetingArray['attendeeEmails'])
                ];
            } else {
                error_log("⊘ Skipped (public or no attendees): " . $meetingArray['topic']);
            }
            
        } catch (Exception $e) {
            error_log("ERROR processing meeting: " . $e->getMessage());
            $failureCount++;
        }
    }
    
    error_log("=== REMINDER JOB COMPLETED ===");
    error_log("Summary: $remindersCount emails sent, $failureCount failures");
    
    // ===== RETURN RESPONSE =====
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'message' => 'Reminder job completed',
        'remindersProcessed' => count($meetings),
        'emailsSent' => $remindersCount,
        'failures' => $failureCount,
        'results' => $results
    ], 200);
    
} catch (Exception $e) {
    error_log('Reminder job error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse([
        'success' => false,
        'error' => 'Reminder job failed',
        'message' => $e->getMessage()
    ], 500);
}