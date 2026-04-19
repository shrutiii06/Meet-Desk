<?php
/**
 * UPDATE MEETING ENDPOINT
 * 
 * URL: POST http://localhost/MD/api/meetings/update.php
 * 
 * RECEIVES from Frontend:
 * {
 *   "email": "john@example.com",
 *   "originalDate": "2024-12-25",
 *   "originalTime": "10:00",
 *   "topic": "Team Meeting",
 *   "description": "Weekly sync",
 *   "date": "2024-12-25",
 *   "time": "10:30",
 *   "duration": "60"
 * }
 * 
 * RETURNS to Frontend:
 * {
 *   "success": true,
 *   "message": "Meeting updated successfully"
 * }
 */

// Error handling FIRST - before any other code
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Enable output buffering
ob_start();

// Ensure we always output JSON
header('Content-Type: application/json; charset=utf-8');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        error_log('Fatal error in update.php: ' . $error['message']);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $error['message']]);
    }
});

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../email-templates.php';
    
    // ===== STEP 1: CHECK REQUEST METHOD =====
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // ===== STEP 2: GET REQUEST DATA =====
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true) ?: [];
    
    // Accept both 'email' and 'userEmail' for compatibility
    $email = $data['userEmail'] ?? $data['email'] ?? null;
    
    if (!$email) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    $email = strtolower(trim($email));
    $originalDate = $data['originalDate'] ?? '';
    $originalTime = $data['originalTime'] ?? '';
    $topic = trim($data['topic'] ?? '');
    $description = trim($data['description'] ?? '');
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';
    $duration = $data['duration'] ?? '60';

    if (!$topic) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Topic is required']);
        exit;
    }

    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();
    $meetingsNs = 'meetdesk.meetings';

    // ===== STEP 4: FIND MEETING IN MEETINGS COLLECTION =====
    $query = new MongoDB\Driver\Query([
        'userEmail' => $email,
        'date' => $originalDate,
        'time' => $originalTime
    ]);
    
    $cursor = $manager->executeQuery($meetingsNs, $query);
    $meetings = $cursor->toArray();

    if (empty($meetings)) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Meeting not found']);
        exit;
    }

    $meeting = $meetings[0];

    // ===== STEP 5: UPDATE MEETING IN DATABASE =====
    $updateData = [
        '$set' => [
            'topic' => $topic,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'duration' => (int)$duration,
            'updatedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]
    ];

    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $meeting->_id],
        $updateData
    );
    $result = $manager->executeBulkWrite($meetingsNs, $bulk);

    if ($result->getModifiedCount() > 0) {
        // ===== STEP 6: SEND EMAIL NOTIFICATIONS =====
        $changeDetails = [];
        
        // Check what changed
        if ($meeting->date !== $date || $meeting->time !== $time) {
            $oldDateTime = date('F j, Y \a\t g:i A', strtotime($meeting->date . ' ' . $meeting->time));
            $newDateTime = date('F j, Y \a\t g:i A', strtotime($date . ' ' . $time));
            $changeDetails[] = [
                'label' => 'Date/Time Changed',
                'value' => "From: $oldDateTime<br>To: $newDateTime"
            ];
        }
        
        if ($meeting->topic !== $topic) {
            $changeDetails[] = [
                'label' => 'Topic Changed',
                'value' => "From: {$meeting->topic}<br>To: $topic"
            ];
        }
        
        if ($meeting->duration !== (int)$duration) {
            $changeDetails[] = [
                'label' => 'Duration Changed',
                'value' => "From: {$meeting->duration} min<br>To: $duration min"
            ];
        }
        
        // Prepare meeting data for email
        $meetingData = [
            'topic' => $topic,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
            'meetingId' => $meeting->meetingId ?? '',
            'password' => $meeting->password ?? '',
            'userName' => $meeting->userName ?? 'Organizer',
            'userEmail' => $email
        ];
        
        // Determine change type
        $changeType = 'modified';
        if (!empty($changeDetails)) {
            $oldTime = strtotime($meeting->date . ' ' . $meeting->time);
            $newTime = strtotime($date . ' ' . $time);
            if ($newTime > $oldTime) {
                $changeType = 'postponed';
            } elseif ($newTime < $oldTime) {
                $changeType = 'preponed';
            }
        }
        
        // Send to organizer
        sendMeetingChangedEmail($email, $meetingData, $changeType, $changeDetails);
        
        // Send to attendees (if private meeting)
        if (isset($meeting->attendeeEmails) && !empty($meeting->attendeeEmails)) {
            $attendeeEmails = is_array($meeting->attendeeEmails) 
                ? $meeting->attendeeEmails 
                : (array)$meeting->attendeeEmails;
            
            foreach ($attendeeEmails as $attendeeEmail) {
                if (!empty($attendeeEmail)) {
                    sendMeetingChangedEmail($attendeeEmail, $meetingData, $changeType, $changeDetails);
                }
            }
        }
        
        ob_end_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Meeting updated successfully'
        ]);
    } else {
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update meeting'
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