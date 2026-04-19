<?php
/**
 * GET SINGLE MEETING ENDPOINT
 * 
 * Purpose: Fetch basic details of a specific meeting by numeric meetingId
 * URL: GET http://localhost/MD/api/meetings/get-meeting.php?meetingId=123456789
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

$meetingId = isset($_GET['meetingId']) ? trim($_GET['meetingId']) : null;

if (!$meetingId) {
    http_response_code(400);
    jsonResponse(['error' => 'Meeting ID parameter is required'], 400);
    exit;
}

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    $query = new MongoDB\Driver\Query(['meetingId' => $meetingId]);
    $cursor = $manager->executeQuery($namespace, $query);
    $meeting = null;
    
    foreach ($cursor as $doc) {
        $meeting = $doc;
        break;
    }
    
    if (!$meeting) {
        http_response_code(404);
        jsonResponse(['error' => 'Meeting not found'], 404);
        exit;
    }
    
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'meeting' => [
            'meetingId' => $meeting->meetingId ?? '',
            'topic' => $meeting->topic,
            'description' => $meeting->description ?? '',
            'password' => $meeting->password ?? ''
        ]
    ], 200);
    
} catch (Exception $e) {
    error_log('Get single meeting error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to fetch meeting'], 500);
}
