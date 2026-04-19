<?php
/**
 * GET PROFILE ENDPOINT
 * Fetches latest user data from MongoDB
 * 
 * URL: GET http://localhost/MD/api/users/get-profile.php?email=john@example.com
 * 
 * RECEIVES from Frontend: email (query parameter)
 * 
 * RETURNS to Frontend:
 * {
 *   "_id": "507f1f77bcf86cd799439011",
 *   "name": "John Doe",
 *   "email": "john@example.com",
 *   "phone": "1234567890",
 *   "profileImage": "data:image/png;base64,...",
 *   "memberSince": "2024-12-01T10:30:00Z"
 * }
 */

require_once __DIR__ . '/../config.php';  // Get MongoDB connection helpers

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== STEP 2: RECEIVE EMAIL FROM FRONTEND =====
$email = strtolower(trim($_GET['email'] ?? ''));

if (!$email) {
    jsonResponse(['error' => 'Email is required'], 400);
}

try {
    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();  // MongoDB\Driver\Manager instance
    $ns = getNamespace();     // 'meetdesk.users'

    // ===== STEP 4: FIND USER BY EMAIL IN MONGODB =====
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();

    if (empty($users)) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    // ===== STEP 5: PREPARE USER DATA FROM DATABASE =====
    $userData = (array) $users[0];
    
    $memberSince = isset($userData['memberSince']) && $userData['memberSince'] instanceof MongoDB\BSON\UTCDateTime
        ? $userData['memberSince']->toDateTime()->format('c')
        : null;

    $response = [
        '_id' => (string) $userData['_id'],
        'name' => $userData['name'] ?? '',
        'email' => $userData['email'] ?? '',
        'phone' => $userData['phone'] ?? 'Not provided',
        'profileImage' => $userData['profileImage'] ?? null,
        'memberSince' => $memberSince
    ];

    // ===== STEP 6: SEND RESPONSE BACK TO FRONTEND =====
    jsonResponse($response);  // 200 = Success
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}