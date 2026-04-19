<?php
/**
 * CRON JOB: Send Meeting Reminders
 * 
 * Purpose: Send email reminders 30 minutes before meetings
 * Run: Every 30 minutes (configured in Windows Task Scheduler)
 * 
 * Windows Task Scheduler:
 * - Task Name: MeetDesk Send Reminders
 * - Repeat every: 30 minutes
 * 
 * This script:
 * 1. Finds all meetings happening in the next 35 minutes (gives 5 min buffer)
 * 2. That don't have reminders sent yet
 * 3. Sends email reminder to all attendees
 * 4. Marks reminder as sent in database
 * 5. Logs all activity to cron/logs/send_reminders_YYYY-MM-DD.log
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/email-templates.php';
require_once __DIR__ . '/CronLogger.php';

$logger = new CronLogger('Send Reminders');
$logger->info('Starting cron job: Send Meeting Reminders');

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    $logger->info('Connecting to MongoDB...');
    
    // Get current date and time
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $currentTime = $now->format('H:i');
    
    $logger->debug("Current Date: {$today}");
    $logger->debug("Current Time: {$currentTime}");
    
    // Calculate future time (30 minutes from now)
    $reminderTime = new DateTime('+30 minutes');
    $reminderDate = $reminderTime->format('Y-m-d');
    $reminderTimeStr = $reminderTime->format('H:i');
    
    $logger->debug("Looking for meetings between {$currentTime} and {$reminderTimeStr}");
    
    // Fetch all scheduled meetings not reminded
    $querySimple = new MongoDB\Driver\Query([
        'reminderSent' => false,
        'status' => 'scheduled'
    ]);
    
    $cursor = $manager->executeQuery($namespace, $querySimple);
    $allMeetings = $cursor->toArray();
    
    $logger->debug("Total scheduled meetings found: " . count($allMeetings));
    
    // Filter for meetings in the next 35 minutes
    $meetingsToRemind = [];
    foreach ($allMeetings as $meeting) {
        try {
            $meetingDateTime = new DateTime($meeting->date . ' ' . $meeting->time);
            $timeDiff = $meetingDateTime->getTimestamp() - $now->getTimestamp();
            
            // If meeting is between now and 31 minutes from now
            if ($timeDiff > 0 && $timeDiff <= 31 * 60) {
                $meetingsToRemind[] = $meeting;
            }
        } catch (Exception $e) {
            $logger->warning("Error parsing meeting time for " . $meeting->topic . ": " . $e->getMessage());
        }
    }
    
    $logger->info("Meetings found for reminders: " . count($meetingsToRemind));
    
    if (empty($meetingsToRemind)) {
        $logger->info("No reminders to send");
        $logger->logSummary(['reminders_sent' => 0, 'reminders_failed' => 0]);
        return;
    }
    
    // ===== SEND REMINDERS =====
    $sentCount = 0;
    $failedCount = 0;
    
    foreach ($meetingsToRemind as $meeting) {
        $logger->info("Processing meeting: " . $meeting->topic);
        
        try {
            // Convert MongoDB document to array for email template
            $meetingArray = (array)$meeting;
            
            // Send reminder to host
            $toEmail = $meeting->userEmail;
            
            $logger->debug("Sending reminder to host: {$toEmail}");
            
            sendMeetingReminderEmail($toEmail, $meetingArray);
            
            $logger->success("Reminder email sent to host");
            $emailSent = true;
            
            // Send reminders to attendees if they exist
            if (!empty($meeting->attendeeEmails) && is_array($meeting->attendeeEmails)) {
                foreach ($meeting->attendeeEmails as $attendeeEmail) {
                    try {
                        sendMeetingReminderEmail($attendeeEmail, $meetingArray);
                    } catch (Exception $e) {
                        $logger->warning("Failed to send reminder to attendee {$attendeeEmail}: " . $e->getMessage());
                    }
                }
                $logger->success("Reminder emails sent to " . count($meeting->attendeeEmails) . " attendee(s)");
            }
                
            // Mark reminder as sent in database
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
            
            $sentCount++;
            $logger->debug("Marked as sent in database");
        } catch (Exception $e) {
            $failedCount++;
            $logger->error("Error processing meeting: " . $e->getMessage());
        }
    }
    
    // Log summary
    $logger->logSummary([
        'reminders_sent' => $sentCount,
        'reminders_failed' => $failedCount,
        'total_meetings_processed' => count($meetingsToRemind)
    ]);
    
} catch (MongoDB\Exception\Exception $e) {
    $logger->error("MongoDB Error: " . $e->getMessage());
    $logger->logSummary(['status' => 'failed', 'error' => $e->getMessage()]);
    http_response_code(500);
    return;
} catch (Exception $e) {
    $logger->error("General Error: " . $e->getMessage());
    $logger->logSummary(['status' => 'failed', 'error' => $e->getMessage()]);
    http_response_code(500);
    return;
}

return;
?>
