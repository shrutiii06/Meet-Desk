<?php
/**
 * UPDATE USER PROFILE ENDPOINT
 * Updates name and phone number
 * 
 * URL: POST http://localhost/MD/api/users/update-user.php
 * 
 * RECEIVES from Frontend:
 * {
 *   "email": "john@example.com",
 *   "name": "John Doe",
 *   "phone": "1234567890"
 * }
 * 
 * RETURNS to Frontend:
 * {
 *   "message": "Profile updated successfully",
 *   "name": "John Doe",
 *   "phone": "1234567890"
 * }
 */

require_once __DIR__ . '/../config.php';  // Get MongoDB connection helpers

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== STEP 2: RECEIVE DATA FROM FRONTEND =====
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = strtolower(trim($input['email'] ?? ''));
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '') ?: 'Not provided';

if (!$email) {
    jsonResponse(['error' => 'Email is required'], 400);
}

if (!$name) {
    jsonResponse(['error' => 'Name is required'], 400);
}

try {
    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();  // MongoDB\Driver\Manager instance
    $ns = getNamespace();     // 'meetdesk.users'

    // ===== STEP 4: UPDATE USER IN MONGODB =====
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['email' => $email],
        ['$set' => [
            'name' => $name,
            'phone' => $phone
        ]]
    );
    $result = $manager->executeBulkWrite($ns, $bulk);

    // ===== STEP 5: CHECK IF UPDATE WAS SUCCESSFUL =====
    if ($result->getModifiedCount() === 0 && $result->getUpsertedCount() === 0) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    // ===== STEP 6: SEND RESPONSE BACK TO FRONTEND =====
    jsonResponse([
        'message' => 'Profile updated successfully',
        'name' => $name,
        'phone' => $phone
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}