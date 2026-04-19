<?php
/**
 * JOIN MEETING API
 * 
 * Allows users to join meetings using:
 * - Meeting ID + Password (like Zoom)
 * 
 * Features:
 * - Validate Meeting ID + Password
 * - Check if meeting is active
 * - Generate participant credentials
 * - Log attendance
 * - Return WebRTC config
 * - Distinguish host vs participant
 */

require_once '../config.php';

// ===== STEP 1: VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

// ===== STEP 2: PARSE REQUEST DATA =====
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    jsonResponse(['error' => 'Invalid request data'], 400);
    exit;
}

$meetingId = trim($data['meetingId'] ?? '');
$password = trim($data['password'] ?? '');
$participantName = htmlspecialchars($data['participantName'] ?? 'Guest');
$participantEmail = strtolower(trim($data['participantEmail'] ?? ''));

// Validate required fields
if (empty($meetingId) || empty($password)) {
    http_response_code(400);
    jsonResponse(['error' => 'Meeting ID and password are required'], 400);
    exit;
}

// ===== STEP 3: FIND MEETING BY ID & VALIDATE PASSWORD =====
try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    // Query to find meeting by numeric meetingId
    $query = new MongoDB\Driver\Query([
        'meetingId' => $meetingId,
        'status' => 'scheduled'
    ]);
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meeting = null;
    
    foreach ($cursor as $doc) {
        $meeting = $doc;
        break;
    }
    
    // ===== STEP 4: HANDLE NOT FOUND =====
    if (!$meeting) {
        http_response_code(404);
        jsonResponse(['error' => 'Meeting not found'], 404);
        exit;
    }
    
    // ===== STEP 5: VALIDATE PASSWORD =====
    $meetingArray = (array)$meeting;
    if ($meetingArray['password'] !== $password) {
        http_response_code(401);
        jsonResponse(['error' => 'Invalid password'], 401);
        exit;
    }
    
    // ===== STEP 6: DETERMINE PARTICIPANT ROLE =====
    $participantId = 'participant_' . uniqid() . '_' . time();
    
    $role = 'participant';
    if (!empty($participantEmail) && strtolower($participantEmail) === strtolower($meetingArray['userEmail'])) {
        $role = 'host';
    }

    // ===== STEP 7: CHECK IF MEETING IS ACTIVE =====
    $meetingDateTime = strtotime($meetingArray['date'] . ' ' . $meetingArray['time']);
    $endTime = $meetingDateTime + ($meetingArray['duration'] * 60);
    $now = time();
    
    if ($now < $meetingDateTime && $role !== 'host') {
        // Meeting hasn't started yet
        $minutesUntilStart = ceil(($meetingDateTime - $now) / 60);
        http_response_code(403);
        jsonResponse(['error' => "The meeting has not started yet. Please wait {$minutesUntilStart} minute(s)."], 403);
        exit;
    }
    
    if ($now > $endTime) {
        // Meeting has ended
        http_response_code(410);
        jsonResponse(['error' => 'Meeting has ended'], 410);
        exit;
    }
    
    // ===== STEP 9: LOG PARTICIPANT JOINING=====
    logParticipantJoin(
        (string)$meeting->_id,
        $participantId,
        $participantName,
        $participantEmail,
        $role
    );
    
    // ===== STEP 10: RETURN SUCCESS WITH MEETING DETAILS =====
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'status' => 'active',
        'message' => 'Welcome to meeting',
        'meeting' => [
            '_id' => (string)$meeting->_id,
            'meetingId' => $meetingArray['meetingId'],
            'topic' => $meetingArray['topic'],
            'description' => $meetingArray['description'] ?? '',
            'host' => $meetingArray['userName'],
            'hostEmail' => $meetingArray['userEmail'],
            'startTime' => $meetingArray['date'] . ' ' . $meetingArray['time'],
            'duration' => $meetingArray['duration'],
            'timezone' => $meetingArray['timezone'],
            'waitingRoomEnabled' => $meetingArray['enableWaitingRoom'] ?? false,
            'autoRecord' => $meetingArray['autoRecord'] ?? true
        ],
        'participant' => [
            'id' => $participantId,
            'name' => $participantName,
            'email' => $participantEmail,
            'role' => $role
        ],
        'webRTC' => [
            'signalingServer' => getenv('SIGNALING_SERVER') ?: 'wss://signal.meetdesk.com/socket.io',
            'iceServers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                ['urls' => 'stun:stun1.l.google.com:19302'],
                ['urls' => 'stun:stun2.l.google.com:19302'],
                ['urls' => 'stun:stun3.l.google.com:19302']
            ]
        ]
    ], 200);
    
} catch (Exception $e) {
    error_log('Join meeting error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to join meeting'], 500);
}

/**
 * Log participant joining the meeting
 */
function logParticipantJoin($meetingId, $participantId, $participantName, $participantEmail, $role) {
    try {
        $manager = getManager();
        $namespace = 'meetdesk.participants';
        
        $participant = [
            'meetingId' => new MongoDB\BSON\ObjectId($meetingId),
            'participantId' => $participantId,
            'participantName' => htmlspecialchars($participantName),
            'participantEmail' => strtolower($participantEmail),
            'role' => $role,
            'joinedAt' => new MongoDB\BSON\UTCDateTime(),
            'leftAt' => null,
            'durationSeconds' => 0
        ];
        
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($participant);
        $manager->executeBulkWrite($namespace, $bulk);
        
        error_log("Participant logged: $participantName ($participantId)");
        
    } catch (Exception $e) {
        error_log('Failed to log participant: ' . $e->getMessage());
        // Don't fail the join if logging fails
    }
}