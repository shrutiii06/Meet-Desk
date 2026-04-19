<?php
/**
 * UPDATE USER PROFILE ENDPOINT
 * 
 * URL: POST http://localhost/MD/api/users/update-profile.php
 * 
 * RECEIVES from Frontend:
 * {
 *   "userEmail": "john@example.com",
 *   "name": "John Doe",
 *   "phone": "1234567890",
 *   "timezone": "IST",
 *   "bio": "Software developer",
 *   "profileImage": "data:image/png;base64,iVBORw0KGgo..."
 * }
 * 
 * RETURNS to Frontend:
 * {
 *   "success": true,
 *   "message": "Profile updated successfully",
 *   "user": { ... }
 * }
 */

// Set error handling FIRST
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';  // Get MongoDB connection helpers

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== STEP 2: RECEIVE DATA FROM FRONTEND =====
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userEmail = strtolower(trim($input['userEmail'] ?? $input['email'] ?? ''));

if (!$userEmail) {
    ob_end_clean();
    jsonResponse(['error' => 'Email is required'], 400);
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    jsonResponse(['error' => 'Invalid email format'], 400);
}

try {
    // ===== STEP 3: CONNECT TO MONGODB =====
    $manager = getManager();
    $ns = getNamespace();

    // ===== STEP 4: PREPARE UPDATE DATA =====
    $updateData = [];

    // Name (optional)
    if (isset($input['name'])) {
        $name = htmlspecialchars(trim($input['name']));
        if (!empty($name) && strlen($name) >= 2) {
            $updateData['name'] = $name;
        }
    }

    // Phone (optional)
    if (isset($input['phone'])) {
        $phone = htmlspecialchars(trim($input['phone']));
        if (!empty($phone) && preg_match('/^[+\d\s\-()]+$/', $phone)) {
            $updateData['phone'] = $phone;
        }
    }

    // Timezone (optional)
    if (isset($input['timezone'])) {
        $timezone = htmlspecialchars(trim($input['timezone']));
        $allowedTimezones = ['IST', 'UTC', 'EST', 'PST', 'GMT', 'CET', 'JST', 'AST'];
        if (!empty($timezone) && in_array($timezone, $allowedTimezones)) {
            $updateData['timezone'] = $timezone;
        }
    }

    // Bio (optional)
    if (isset($input['bio'])) {
        $bio = htmlspecialchars(trim($input['bio']));
        if (strlen($bio) <= 500) {
            $updateData['bio'] = $bio;
        }
    }

    // Profile Image (optional)
    if (isset($input['profileImage']) && !empty($input['profileImage'])) {
        $profileImage = $input['profileImage'];
        // Validate it's base64 or URL
        if (strpos($profileImage, 'data:image') === 0 || strpos($profileImage, 'http') === 0 || $profileImage === '') {
            $updateData['profileImage'] = $profileImage;
        }
    }

    // Always add updatedAt timestamp
    $updateData['updatedAt'] = new MongoDB\BSON\UTCDateTime();

    if (empty($updateData)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'No valid fields to update'], 400);
        exit;
    }

    // ===== STEP 5: UPDATE PROFILE IN MONGODB =====
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['email' => $userEmail],
        ['$set' => $updateData]
    );
    $result = $manager->executeBulkWrite($ns, $bulk);

    // ===== STEP 6: CHECK IF UPDATE WAS SUCCESSFUL =====
    if ($result->getModifiedCount() === 0 && $result->getUpsertedCount() === 0) {
        ob_end_clean();
        http_response_code(500);
        jsonResponse(['error' => 'Failed to update profile'], 500);
        exit;
    }

    // ===== STEP 7: FETCH UPDATED USER DATA =====
    $query = new MongoDB\Driver\Query(['email' => $userEmail]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();

    if (empty($users)) {
        ob_end_clean();
        http_response_code(404);
        jsonResponse(['error' => 'User not found'], 404);
        exit;
    }

    $user = $users[0];

    // ===== STEP 8: BUILD AND RETURN RESPONSE =====
    ob_end_clean();
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            '_id' => (string)$user->_id,
            'name' => $user->name ?? '',
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'timezone' => $user->timezone ?? 'UTC',
            'bio' => $user->bio ?? '',
            'profileImage' => $user->profileImage ?? null,
            'memberSince' => isset($user->memberSince) ? $user->memberSince->__toString() : null,
            'updatedAt' => isset($user->updatedAt) ? $user->updatedAt->__toString() : null
        ]
    ], 200);

} catch (MongoDB\Exception\Exception $e) {
    ob_end_clean();
    error_log('MongoDB ERROR in update-profile.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    ob_end_clean();
    error_log('ERROR in update-profile.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to update profile: ' . $e->getMessage()], 500);
}