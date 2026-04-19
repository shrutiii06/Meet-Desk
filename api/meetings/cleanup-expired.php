<?php
/**
 * DELETE EXPIRED MEETING
 * 
 * Deletes a specific meeting if its end time has passed
 * 
 * URL: POST http://localhost/MD/api/meetings/cleanup-expired.php
 * 
 * RECEIVES:
 * {
 *   "email": "john@example.com",
 *   "meetingId": "123456789"
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Meeting deleted successfully",
 *   "meetingTitle": "Team Standup",
 *   "wasExpired": true
 * }
 */

error_log('=== DELETE MEETING START ===');

require_once __DIR__ . '/../config.php';

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    // ===== STEP 2: GET REQUEST DATA =====
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    
    if (!isset($data['email']) || !isset($data['meetingId'])) {
        jsonResponse(['error' => 'Email and meetingId are required'], 400);
    }
    
    $email = strtolower(trim($data['email']));
    $meetingId = trim($data['meetingId']);
    
    error_log('Email: ' . $email . ', MeetingID: ' . $meetingId);
    
    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();
    $ns = getNamespace();
    
    // ===== STEP 4: FIND USER =====
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();
    
    if (empty($users)) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $user = $users[0];
    error_log('User found: ' . $email);
    
    // ===== STEP 5: FIND MEETING IN USER'S MEETINGS =====
    $meetings = $user->meetings ?? [];
    $meetingIndex = -1;
    $meetingData = null;
    $wasExpired = false;
    
    foreach ($meetings as $index => $meeting) {
        if (isset($meeting['id']) && $meeting['id'] === $meetingId) {
            $meetingIndex = $index;
            $meetingData = $meeting;
            
            // Check if meeting is expired
            if (isset($meeting['endTime']) && $meeting['endTime'] instanceof MongoDB\BSON\UTCDateTime) {
                $now = new MongoDB\BSON\UTCDateTime(time() * 1000);
                if ($meeting['endTime'] < $now) {
                    $wasExpired = true;
                    error_log('Meeting is expired: ' . ($meeting['title'] ?? 'Untitled'));
                }
            }
            break;
        }
    }
    
    if ($meetingIndex === -1) {
        jsonResponse(['error' => 'Meeting not found'], 404);
    }
    
    // ===== STEP 6: REMOVE MEETING FROM ARRAY =====
    array_splice($meetings, $meetingIndex, 1);
    
    // ===== STEP 7: UPDATE USER IN DATABASE =====
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $user->_id],
        ['$set' => ['meetings' => $meetings]]
    );
    $result = $manager->executeBulkWrite($ns, $bulk);
    
    error_log('Meeting deleted successfully');
    
    // ===== STEP 8: RETURN RESPONSE =====
    jsonResponse([
        'success' => true,
        'message' => 'Meeting deleted successfully',
        'meetingTitle' => $meetingData['title'] ?? 'Untitled',
        'wasExpired' => $wasExpired
    ]);
    
} catch (Exception $e) {
    error_log('Delete meeting error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    jsonResponse(['error' => $e->getMessage()], 500);
}