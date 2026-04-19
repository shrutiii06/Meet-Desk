<?php
/**
 * GET MEETINGS ENDPOINT
 * 
 * Purpose: Fetch scheduled meetings for a specific user with optional search/filter
 * URL: GET http://localhost/MD/api/meetings/get.php?email=user@example.com&search=&from_date=&to_date=&status=
 * 
 * Query Parameters:
 * - email (required): User email address
 * - search (optional): Search by topic or description (contains match)
 * - from_date (optional): Filter meetings from this date (YYYY-MM-DD)
 * - to_date (optional): Filter meetings until this date (YYYY-MM-DD)
 * - status (optional): Filter by status (scheduled, completed, cancelled) - default: scheduled
 * 
 * Examples:
 * GET /get.php?email=user@example.com
 * GET /get.php?email=user@example.com&search=team
 * GET /get.php?email=user@example.com&from_date=2026-02-20&to_date=2026-02-28
 * GET /get.php?email=user@example.com&search=sync&from_date=2026-02-01
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "meetings": [
 *     {
 *       "_id": "507f1f77bcf86cd799439011",
 *       "topic": "Team Standup",
 *       "description": "Weekly sync",
 *       "date": "2026-02-18",
 *       "time": "10:00",
 *       ...
 *     }
 *   ],
 *   "count": 2,
 *   "filters_applied": {
 *     "search": "team",
 *     "from_date": "2026-02-01",
 *     "to_date": "2026-02-28",
 *     "status": "scheduled"
 *   }
 * }
 */

require_once '../config.php';

// ===== STEP 1: VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

// ===== STEP 2: GET QUERY PARAMETERS =====
$userEmail = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : null;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : null;
$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : null;
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : null;
$status = isset($_GET['status']) ? trim($_GET['status']) : 'scheduled';

if (!$userEmail) {
    http_response_code(400);
    jsonResponse(['error' => 'Email parameter is required'], 400);
    exit;
}

// ===== STEP 3: BUILD FILTER QUERY =====
$filterQuery = [
    'userEmail' => $userEmail,
    'status' => $status
];

// Track which filters were applied
$appliedFilters = [
    'status' => $status
];

// Add search filter (topic or description contains)
if (!empty($searchTerm)) {
    $filterQuery['$or'] = [
        ['topic' => new MongoDB\BSON\Regex($searchTerm, 'i')],  // Case-insensitive search in topic
        ['description' => new MongoDB\BSON\Regex($searchTerm, 'i')]  // Case-insensitive search in description
    ];
    $appliedFilters['search'] = $searchTerm;
}

// Add date range filters
if (!empty($fromDate)) {
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        http_response_code(400);
        jsonResponse(['error' => 'Invalid from_date format. Use YYYY-MM-DD'], 400);
        exit;
    }
    
    if (!isset($filterQuery['date'])) {
        $filterQuery['date'] = [];
    }
    
    if (is_array($filterQuery['date'])) {
        $filterQuery['date']['$gte'] = $fromDate;
    } else {
        // Convert to array if it's a string
        $filterQuery['date'] = ['$gte' => $fromDate];
    }
    $appliedFilters['from_date'] = $fromDate;
}

if (!empty($toDate)) {
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        http_response_code(400);
        jsonResponse(['error' => 'Invalid to_date format. Use YYYY-MM-DD'], 400);
        exit;
    }
    
    if (!isset($filterQuery['date'])) {
        $filterQuery['date'] = [];
    }
    
    if (is_array($filterQuery['date'])) {
        $filterQuery['date']['$lte'] = $toDate;
    } else {
        // Convert to array if it's a string
        $filterQuery['date'] = ['$lte' => $toDate];
    }
    $appliedFilters['to_date'] = $toDate;
}

// ===== STEP 4: FETCH MEETINGS FROM MONGODB =====
try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    // Query with filters and sorting
    $query = new MongoDB\Driver\Query(
        $filterQuery,
        [
            'sort' => ['date' => 1, 'time' => 1]  // Sort by date and time ascending
        ]
    );
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = [];
    
    // Get current date and time for filtering expired meetings
    $today = date('Y-m-d');
    $now = new DateTime();
    
    foreach ($cursor as $doc) {
        // ===== FILTER OUT EXPIRED MEETINGS =====
        // Check if meeting is in the past
        if ($doc->date < $today) {
            // Meeting date is in the past - skip it
            continue;
        }
        
        // Check if meeting is today but time has passed
        if ($doc->date === $today) {
            $meetingTime = DateTime::createFromFormat('H:i', $doc->time);
            if ($meetingTime === false) {
                // Invalid time format - skip
                continue;
            }
            
            // Calculate meeting end time
            $meetingEndTime = clone $meetingTime;
            $meetingEndTime->add(new DateInterval('PT' . intval($doc->duration) . 'M'));
            
            // Skip if meeting has already ended
            if ($now > $meetingEndTime) {
                continue;
            }
        }
        
        // Meeting is still valid - include it
        $meetings[] = [
            '_id' => (string)$doc->_id,
            'meetingId' => $doc->meetingId ?? '',
            'topic' => $doc->topic,
            'description' => $doc->description ?? '',
            'date' => $doc->date,
            'time' => $doc->time,
            'duration' => $doc->duration,
            'timezone' => $doc->timezone,
            'repeat' => $doc->repeat ?? 'no',
            'enableWaitingRoom' => $doc->enableWaitingRoom ?? false,
            'autoRecord' => $doc->autoRecord ?? false,
            'isPublic' => $doc->isPublic ?? false,
            'userName' => $doc->userName,
            'password' => $doc->password ?? '',
            'attendeeEmails' => isset($doc->attendeeEmails) && is_array($doc->attendeeEmails) ? $doc->attendeeEmails : [],
            'participantCount' => isset($doc->attendeeEmails) ? count($doc->attendeeEmails) : 0
        ];
    }
    
    // ===== STEP 5: RETURN MEETINGS WITH FILTER INFO =====
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'meetings' => $meetings,
        'count' => count($meetings),
        'filters_applied' => (count($appliedFilters) > 1 || $status !== 'scheduled') ? $appliedFilters : null
    ], 200);
    
} catch (Exception $e) {
    error_log('Get meetings error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to fetch meetings: ' . $e->getMessage()], 500);
}