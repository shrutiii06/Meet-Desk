<?php
/**
 * LOGIN ENDPOINT
 * 
 * URL: POST http://localhost/MD/api/auth/login
 * 
 * RECEIVES from Frontend (login.html):
 * {
 *   "email": "john@example.com",
 *   "password": "password123"
 * }
 * 
 * RETURNS to Frontend:
 * {
 *   "_id": "507f1f77bcf86cd799439011",
 *   "name": "John Doe",
 *   "email": "john@example.com",
 *   "phone": "1234567890",
 *   "memberSince": "2024-12-01T10:30:00Z"
 * }
 */

require_once __DIR__ . '/../config.php';  // Get MongoDB connection helpers

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== STEP 2: RECEIVE CREDENTIALS FROM FRONTEND =====
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

try {
    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();  // MongoDB\Driver\Manager instance
    $ns = getNamespace();     // 'meetdesk.users'

    // ===== STEP 4: FIND USER BY EMAIL =====
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();

    if (empty($users)) {
        jsonResponse(['error' => 'No account found with this email'], 401);
    }

    // ===== STEP 5: GET USER DATA =====
    $user = $users[0];
    $userData = (array) $user;

    // ===== STEP 6: CHECK IF EMAIL IS VERIFIED =====
    // TEMPORARILY DISABLED FOR TESTING - Email system not yet configured
    // if (!isset($userData['emailVerified']) || $userData['emailVerified'] !== true) {
    //     jsonResponse([
    //         'error' => 'Email not verified',
    //         'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
    //         'emailVerified' => false,
    //         'email' => $email
    //     ], 403);
    // }

    // ===== STEP 7: CHECK IF ACCOUNT IS ACTIVE =====
    // TEMPORARILY DISABLED FOR TESTING - Allow pending users to login
    // if (isset($userData['status']) && $userData['status'] !== 'active') {
    //     jsonResponse(['error' => 'Your account is inactive. Please contact support.'], 401);
    // }

    // ===== STEP 8: VERIFY PASSWORD =====
    $storedPassword = $userData['password'] ?? '';
    if (!password_verify($password, $storedPassword)) {
        jsonResponse(['error' => 'Incorrect password'], 401);
    }

    // ===== STEP 9: PREPARE USER DATA FOR RESPONSE =====
    $memberSince = isset($userData['memberSince']) && $userData['memberSince'] instanceof MongoDB\BSON\UTCDateTime
        ? $userData['memberSince']->toDateTime()->format('c')
        : date('c');

    $response = [
        '_id' => (string) $userData['_id'],
        'name' => $userData['name'] ?? $email,
        'email' => $userData['email'],
        'phone' => $userData['phone'] ?? 'Not provided',
        'timezone' => $userData['timezone'] ?? 'UTC',
        'bio' => $userData['bio'] ?? '',
        'profileImage' => $userData['profileImage'] ?? null,
        'emailVerified' => $userData['emailVerified'] ?? false,
        'memberSince' => $memberSince
    ];

    // ===== STEP 10: SEND RESPONSE BACK TO FRONTEND =====
    jsonResponse($response);  // 200 = Success
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}