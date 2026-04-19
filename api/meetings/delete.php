<?php
/**
 * DELETE MEETING ENDPOINT
 * 
 * Purpose: Delete a scheduled meeting
 * URL: DELETE http://localhost/MD/api/meetings/delete.php
 * 
 * BODY:
 * {
 *   "meetingId": "507f1f77bcf86cd799439011",
 *   "userEmail": "user@example.com"
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Meeting deleted successfully"
 * }
 */

require_once '../config.php';
require_once '../email-templates.php';

// ===== STEP 1: VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

// ===== STEP 2: PARSE REQUEST DATA =====
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['meetingId']) || empty($data['userEmail'])) {
    http_response_code(400);
    jsonResponse(['error' => 'meetingId and userEmail are required'], 400);
    exit;
}

$meetingId = $data['meetingId'];
$userEmail = strtolower(trim($data['userEmail']));

// ===== STEP 3: FETCH MEETING DETAILS BEFORE DELETION =====
try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    // First, fetch the meeting to get attendee emails
    $query = new MongoDB\Driver\Query([
        '_id' => new MongoDB\BSON\ObjectId($meetingId),
        'userEmail' => $userEmail
    ]);
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = $cursor->toArray();
    
    if (empty($meetings)) {
        http_response_code(404);
        jsonResponse(['error' => 'Meeting not found or unauthorized'], 404);
        exit;
    }
    
    $meeting = $meetings[0];
    
    // ===== STEP 4: DELETE MEETING FROM MONGODB =====
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete([
        '_id' => new MongoDB\BSON\ObjectId($meetingId),
        'userEmail' => $userEmail
    ]);
    
    $result = $manager->executeBulkWrite($namespace, $bulk);
    
    if ($result->getDeletedCount() == 0) {
        http_response_code(404);
        jsonResponse(['error' => 'Meeting not found or unauthorized'], 404);
        exit;
    }
    
    // ===== STEP 5: SEND CANCELLATION EMAILS =====
    $meetingData = [
        'topic' => $meeting->topic ?? 'Meeting',
        'description' => $meeting->description ?? '',
        'date' => $meeting->date ?? '',
        'time' => $meeting->time ?? '',
        'duration' => $meeting->duration ?? 60,
        'meetingId' => $meeting->meetingId ?? '',
        'password' => $meeting->password ?? '',
        'userName' => $meeting->userName ?? 'Organizer',
        'userEmail' => $userEmail
    ];
    
    $changeDetails = [
        [
            'label' => 'Cancellation Notice',
            'value' => 'This meeting has been cancelled by the organizer.'
        ]
    ];
    
    // Send to organizer
    sendMeetingChangedEmail($userEmail, $meetingData, 'cancelled', $changeDetails);
    
    // Send to attendees (if private meeting)
    if (isset($meeting->attendeeEmails) && !empty($meeting->attendeeEmails)) {
        $attendeeEmails = is_array($meeting->attendeeEmails) 
            ? $meeting->attendeeEmails 
            : (array)$meeting->attendeeEmails;
        
        foreach ($attendeeEmails as $attendeeEmail) {
            if (!empty($attendeeEmail)) {
                sendMeetingChangedEmail($attendeeEmail, $meetingData, 'cancelled', $changeDetails);
            }
        }
    }
    
    // ===== STEP 6: RETURN SUCCESS =====
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'message' => 'Meeting deleted successfully'
    ], 200);
    
} catch (Exception $e) {
    error_log('Delete meeting error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to delete meeting: ' . $e->getMessage()], 500);
}