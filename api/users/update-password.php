<?php
/**
 * UPDATE PASSWORD ENDPOINT
 * 
 * URL: POST http://localhost/MD/api/users/update-password.php
 * 
 * RECEIVES from Frontend:
 * {
 *   "userEmail": "user@example.com",
 *   "currentPassword": "oldPassword123",
 *   "newPassword": "newPassword123",
 *   "confirmPassword": "newPassword123"
 * }
 * 
 * RETURNS to Frontend:
 * {
 *   "success": true,
 *   "message": "Password updated successfully"
 * }
 */

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

try {
    // ===== STEP 1: CHECK REQUEST METHOD =====
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        jsonResponse(['error' => 'Method not allowed'], 405);
        exit;
    }

    // ===== STEP 2: PARSE REQUEST DATA =====
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $userEmail = strtolower(trim($input['userEmail'] ?? ''));
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';

    // ===== STEP 3: VALIDATE REQUIRED FIELDS =====
    if (empty($userEmail)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Email is required'], 400);
        exit;
    }

    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Invalid email format'], 400);
        exit;
    }

    if (empty($currentPassword)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Current password is required'], 400);
        exit;
    }

    if (empty($newPassword)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'New password is required'], 400);
        exit;
    }

    if (empty($confirmPassword)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Password confirmation is required'], 400);
        exit;
    }

    // ===== STEP 4: VALIDATE PASSWORD REQUIREMENTS =====
    if (strlen($newPassword) < 6) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'New password must be at least 6 characters long'], 400);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Passwords do not match'], 400);
        exit;
    }

    if ($currentPassword === $newPassword) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'New password must be different from current password'], 400);
        exit;
    }

    // ===== STEP 5: CONNECT TO DATABASE =====
    $manager = getManager();
    $namespace = 'meetdesk.users';

    // ===== STEP 6: FIND USER BY EMAIL =====
    $query = new MongoDB\Driver\Query(['email' => $userEmail]);
    $cursor = $manager->executeQuery($namespace, $query);
    $users = $cursor->toArray();

    if (empty($users)) {
        ob_end_clean();
        http_response_code(404);
        jsonResponse(['error' => 'User not found'], 404);
        exit;
    }

    $user = $users[0];

    // ===== STEP 7: VERIFY CURRENT PASSWORD =====
    if (!password_verify($currentPassword, $user->password)) {
        ob_end_clean();
        http_response_code(401);
        jsonResponse(['error' => 'Current password is incorrect'], 401);
        exit;
    }

    // ===== STEP 8: HASH NEW PASSWORD =====
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // ===== STEP 9: UPDATE PASSWORD IN DATABASE =====
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['_id' => $user->_id],
        [
            '$set' => [
                'password' => $hashedPassword,
                'passwordUpdatedAt' => new MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    $result = $manager->executeBulkWrite($namespace, $bulk);

    if ($result->getModifiedCount() === 0) {
        ob_end_clean();
        http_response_code(500);
        jsonResponse(['error' => 'Failed to update password'], 500);
        exit;
    }

    // ===== STEP 10: RETURN SUCCESS =====
    ob_end_clean();
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'message' => 'Password updated successfully'
    ], 200);

} catch (MongoDB\Exception\Exception $e) {
    ob_end_clean();
    error_log('MongoDB ERROR in update-password.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Database error'], 500);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    error_log('ERROR in update-password.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
    exit;
}
