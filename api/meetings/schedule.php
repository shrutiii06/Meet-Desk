<?php
/**
 * SCHEDULE MEETING API - UPDATED
 * 
 * Features:
 * - Generate numeric Meeting ID (numbers only)
 * - Generate numeric Password (6 digits)
 * - Support public/private meetings
 * - Send email notifications (if private)
 * - Handle recurring meetings
 * - Track email sending
 */

// Set error handling FIRST - before any other code
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors, only log them
ini_set('log_errors', 1);

// Enable output buffering to prevent accidental output
ob_start();

// Ensure we always output JSON
header('Content-Type: application/json; charset=utf-8');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        error_log('Fatal error in schedule.php: ' . $error['message']);
        http_response_code(500);
        echo json_encode(['error' => 'Fatal error: ' . $error['message']]);
    }
});

try {
    require_once '../config.php';
    
    // Load email templates if available
    $emailTemplatesExist = file_exists(__DIR__ . '/../email-templates.php');
    if ($emailTemplatesExist) {
        require_once __DIR__ . '/../email-templates.php';
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(['error' => 'Failed to load configuration: ' . $e->getMessage()]));
}

// ===== STEP 1: VALIDATE REQUEST METHOD =====
try {
    error_log('DEBUG: Schedule API called via ' . $_SERVER['REQUEST_METHOD']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        jsonResponse(['error' => 'Method not allowed'], 405);
        exit;
    }

    // ===== STEP 2: PARSE REQUEST DATA =====
    $inputData = file_get_contents('php://input');
    error_log('DEBUG: Received input: ' . substr($inputData, 0, 100));
    
    $data = json_decode($inputData, true);

    if (!$data) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Invalid JSON data'], 400);
        exit;
    }
    
    error_log('DEBUG: Parsed data: ' . json_encode($data));

    // ===== STEP 3: VALIDATE REQUIRED FIELDS =====
    $required = ['userEmail', 'userName', 'topic', 'date', 'time', 'duration'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            ob_end_clean();
            http_response_code(400);
            jsonResponse(['error' => "Missing required field: $field"], 400);
            exit;
        }
    }
    
    error_log('DEBUG: All required fields present');

    // ===== STEP 4: VALIDATE ATTENDEES IF PRIVATE =====
    $isPublic = boolval($data['isPublic'] ?? false);
    $attendeeEmails = [];

    if (!$isPublic) {
        if (empty($data['attendeeEmails'])) {
            ob_end_clean();
            http_response_code(400);
            jsonResponse(['error' => 'Private meetings require attendee emails'], 400);
            exit;
        }
        
        // Parse and validate attendee emails
        if (is_array($data['attendeeEmails'])) {
            $attendeeEmails = $data['attendeeEmails'];
        } else {
            $attendeeEmails = explode(',', $data['attendeeEmails']);
        }
        
        // Clean emails
        $attendeeEmails = array_map('trim', $attendeeEmails);
        $attendeeEmails = array_filter($attendeeEmails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });
        
        if (empty($attendeeEmails)) {
            ob_end_clean();
            http_response_code(400);
            jsonResponse(['error' => 'No valid attendee emails provided'], 400);
            exit;
        }
    }
    
    error_log('DEBUG: Attendee validation complete - ' . count($attendeeEmails) . ' attendees');

    // ===== STEP 5: GENERATE MEETING ID & PASSWORD (NUMBERS ONLY) =====
    $meetingId = generateMeetingId();           // Format: 1677123456789 (timestamp + random)
    $password = generateMeetingPassword();      // Format: 123456 (6 digits)
    $joinLink = 'meetdesk.com/join/' . $meetingId;
    
    error_log('DEBUG: Generated meetingId:' . $meetingId . ', password:' . $password);

    // ===== STEP 6: CREATE MEETING DOCUMENT =====
    $manager = getManager();
    error_log('DEBUG: getManager() called successfully');
    
    $namespace = 'meetdesk.meetings';
    
    // Parse date and time for calculations
    $dateTimeString = $data['date'] . ' ' . $data['time'];
    $timestamp = strtotime($dateTimeString);
    
    if ($timestamp === false) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Invalid date/time format'], 400);
        exit;
    }
    
    error_log('DEBUG: DateTime parsed successfully');
    
    $scheduledDateTime = new MongoDB\BSON\UTCDateTime($timestamp * 1000);
    $reminderDateTime = new MongoDB\BSON\UTCDateTime(($timestamp - 30 * 60) * 1000);
    
    error_log('DEBUG: UTCDateTime objects created');
    
    $meeting = [
        // Original fields
        'userEmail' => strtolower($data['userEmail']),
        'userName' => htmlspecialchars($data['userName']),
        'topic' => htmlspecialchars($data['topic']),
        'description' => htmlspecialchars($data['description'] ?? ''),
        'date' => $data['date'],
        'time' => $data['time'],
        'duration' => intval($data['duration']),
        'timezone' => htmlspecialchars($data['timezone'] ?? 'UTC'),
        'repeat' => htmlspecialchars($data['repeat'] ?? 'never'),
        'enableWaitingRoom' => boolval($data['enableWaitingRoom'] ?? false),
        'autoRecord' => boolval($data['autoRecord'] ?? true),
        'addToCalendar' => boolval($data['addToCalendar'] ?? true),
        
        // NEW FIELDS - Meeting Credentials
        'meetingId' => $meetingId,
        'password' => $password,
        'joinLink' => $joinLink,
        
        // NEW FIELDS - Public vs Private
        'isPublic' => $isPublic,
        'attendeeEmails' => $attendeeEmails,
        
        // NEW FIELDS - Email Tracking
        'attendeesSent' => false,
        'reminderSent' => false,
        'editNotificationSent' => false,
        
        // NEW FIELDS - Recurring
        'recurringParentId' => null,
        'recurringOccurrence' => 1,
        
        // NEW FIELDS - Timestamps
        'scheduledDateTime' => $scheduledDateTime,
        'reminderScheduledFor' => $reminderDateTime,
        
        // Status
        'status' => 'scheduled',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'scheduledAt' => new MongoDB\BSON\UTCDateTime(strtotime($data['scheduledAt'] ?? 'now') * 1000)
    ];
    
    error_log('DEBUG: Meeting array created');
    
    // ===== STEP 7: INSERT INTO DATABASE =====
    $bulk = new MongoDB\Driver\BulkWrite();
    error_log('DEBUG: BulkWrite object created');
    
    $insertMongoId = $bulk->insert($meeting);
    error_log('DEBUG: Meeting inserted into BulkWrite');
    
    $result = $manager->executeBulkWrite($namespace, $bulk);
    error_log('DEBUG: executeBulkWrite completed - inserted=' . $result->getInsertedCount());
    
    $meetingMongoId = (string)$insertMongoId;
    error_log('DEBUG: Meeting saved with ID: ' . $meetingMongoId);
    
    // ===== STEP 8: SEND SCHEDULED EMAIL (if private) =====
    error_log('DEBUG: Starting email send - isPublic=' . ($isPublic ? 'true' : 'false'));
    
    if (!$isPublic && !empty($attendeeEmails) && function_exists('sendMeetingScheduledEmail')) {
        error_log('DEBUG: Sending ' . count($attendeeEmails) . ' emails');
        
        $emailResults = [];
        foreach ($attendeeEmails as $attendeeEmail) {
            try {
                error_log('DEBUG: Sending email to ' . $attendeeEmail);
                sendMeetingScheduledEmail($attendeeEmail, '', $meeting);
                $emailResults[$attendeeEmail] = true;
            } catch (Exception $e) {
                error_log("Failed to send email to {$attendeeEmail}: " . $e->getMessage());
                $emailResults[$attendeeEmail] = false;
            }
        }
        
        // Update meeting to mark emails as sent
        $update = new MongoDB\Driver\BulkWrite();
        $update->update(
            ['_id' => new MongoDB\BSON\ObjectId($meetingMongoId)],
            ['$set' => ['attendeesSent' => true]]
        );
        $manager->executeBulkWrite($namespace, $update);
        error_log('DEBUG: Emails sent flag updated');
    }
    
    // ===== STEP 9: HANDLE RECURRING MEETINGS =====
    $repeatType = $data['repeat'] ?? 'never';
    error_log('DEBUG: Checking recurring - repeat=' . $repeatType);
    
    if ($repeatType !== 'never' && function_exists('createRecurringMeetings')) {
        error_log('DEBUG: Creating recurring meetings for: ' . $repeatType);
        createRecurringMeetings($meeting, $repeatType, $meetingMongoId);
    }
    
    // ===== STEP 10: RETURN SUCCESS =====
    http_response_code(201);
    ob_end_clean();
    jsonResponse([
        'success' => true,
        'message' => 'Meeting scheduled successfully',
        'meetingId' => $meetingMongoId,
        'meeting' => [
            'topic' => $data['topic'],
            'date' => $data['date'],
            'time' => $data['time'],
            'timezone' => $data['timezone'] ?? 'UTC',
            'meetingId' => $meetingId,
            'password' => $password,
            'joinLink' => $joinLink,
            'isPublic' => $isPublic,
            'attendeesNotified' => count($attendeeEmails)
        ]
    ], 201);

} catch (MongoDB\Exception\Exception $e) {
    ob_end_clean();
    error_log('MONGODB ERROR in schedule.php: ' . $e->getMessage());
    error_log('MongoDB Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    error_log('GENERAL ERROR in schedule.php: ' . $e->getMessage());
    error_log('File: ' . $e->getFile());
    error_log('Line: ' . $e->getLine());
    error_log('Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to schedule meeting: ' . $e->getMessage()], 500);
    exit;
}

/**
 * Generate numeric Meeting ID (10 digits only)
 * Format: 10-digit random number
 * Example: 1234567890
 */
function generateMeetingId() {
    // Generate 10-digit numeric ID
    return str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
}

/**
 * Generate numeric Password (6-digit number)
 * Example: 123456
 */
function generateMeetingPassword() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Create recurring meeting instances
 */
function createRecurringMeetings($baseMeeting, $repeatType, $parentId) {
    try {
        $manager = getManager();
        $namespace = 'meetdesk.meetings';
        
        $date = new DateTime($baseMeeting['date'] . ' ' . $baseMeeting['time']);
        $occurrences = [];
        
        // Generate 12 occurrences
        for ($i = 1; $i <= 12; $i++) {
            switch ($repeatType) {
                case 'daily':
                    $date->modify('+1 day');
                    break;
                case 'weekly':
                    $date->modify('+1 week');
                    break;
                case 'monthly':
                    $date->modify('+1 month');
                    break;
            }
            
            $recurringMeeting = $baseMeeting;
            $recurringMeeting['date'] = $date->format('Y-m-d');
            $recurringMeeting['recurringParentId'] = new MongoDB\BSON\ObjectId($parentId);
            $recurringMeeting['recurringOccurrence'] = $i + 1;
            $recurringMeeting['createdAt'] = new MongoDB\BSON\UTCDateTime();
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->insert($recurringMeeting);
            $manager->executeBulkWrite($namespace, $bulk);
        }
        
        error_log("Created 12 recurring meetings for pattern: $repeatType");
    } catch (Exception $e) {
        error_log("Error creating recurring meetings: " . $e->getMessage());
    }
}