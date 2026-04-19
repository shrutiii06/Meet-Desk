<?php
/**
 * CRON JOB: Cleanup Expired Meetings
 * 
 * Purpose: Mark past meetings as completed and archive them
 * Run: Every 1 hour (configured in Windows Task Scheduler)
 * 
 * Windows Task Scheduler:
 * - Task Name: MeetDesk Cleanup Expired Meetings
 * - Repeat every: 1 hour
 * 
 * This script:
 * 1. Finds all meetings that have already ended (current time > end time)
 * 2. Moves them to "completed" status
 * 3. Logs completion for records
 * 4. Logs all activity to cron/logs/cleanup_expired_YYYY-MM-DD.log
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/CronLogger.php';

$logger = new CronLogger('Cleanup Expired Meetings');
$logger->info('Starting cron job: Cleanup Expired Meetings');

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
    
    $logger->info('Searching for expired meetings...');
    
    // Find meetings that have ended TODAY (date = today AND time < currentTime)
    $query1 = new MongoDB\Driver\Query([
        'date' => $today,
        'time' => ['$lt' => $currentTime],
        'status' => ['$ne' => 'completed']
    ]);
    
    $cursor1 = $manager->executeQuery($namespace, $query1);
    $todaysMeetings = $cursor1->toArray();
    $logger->debug("Meetings ended today: " . count($todaysMeetings));
    
    // Find meetings on PAST DATES
    $query2 = new MongoDB\Driver\Query([
        'date' => ['$lt' => $today],
        'status' => ['$ne' => 'completed']
    ]);
    
    $cursor2 = $manager->executeQuery($namespace, $query2);
    $pastMeetings = $cursor2->toArray();
    $logger->debug("Meetings from past dates: " . count($pastMeetings));
    
    $allExpiredMeetings = array_merge($todaysMeetings, $pastMeetings);
    
    $logger->info("Expired meetings found: " . count($allExpiredMeetings));
    
    if (empty($allExpiredMeetings)) {
        $logger->info('No expired meetings to cleanup');
        $logger->logSummary(['meetings_updated' => 0, 'errors' => 0]);
        exit(0);
    }
    
    $logger->info('Updating expired meetings to completed status...');
    $updatedCount = 0;
    $errorCount = 0;
    
    foreach ($allExpiredMeetings as $meeting) {
        $logger->info("Processing meeting: " . $meeting->topic);
        $logger->debug("Meeting ID: " . $meeting->meetingId . ", Date: " . $meeting->date . " at " . $meeting->time);
        
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(
                ['_id' => $meeting->_id],
                [
                    '$set' => [
                        'status' => 'completed',
                        'completedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                        'updatedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
                    ]
                ]
            );
            
            $result = $manager->executeBulkWrite($namespace, $bulk);
            
            if ($result->getModifiedCount() > 0) {
                $updatedCount++;
                $logger->success("Status updated to completed");
            } else {
                $logger->warning("No changes made to meeting");
            }
        } catch (Exception $e) {
            $errorCount++;
            $logger->error("Error updating meeting: " . $e->getMessage());
        }
    }
    
    $logger->logSummary([
        'meetings_updated' => $updatedCount,
        'errors' => $errorCount,
        'total_processed' => count($allExpiredMeetings)
    ]);
    
} catch (MongoDB\Exception\Exception $e) {
    $logger->error("MongoDB Error: " . $e->getMessage());
    $logger->logSummary(['status' => 'failed', 'error' => $e->getMessage()]);
    exit(1);
} catch (Exception $e) {
    $logger->error("General Error: " . $e->getMessage());
    $logger->logSummary(['status' => 'failed', 'error' => $e->getMessage()]);
    exit(1);
}

exit(0);
?>
