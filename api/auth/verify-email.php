<?php
/**
 * VERIFY EMAIL ENDPOINT
 * 
 * URL: GET/POST http://localhost/MD/api/auth/verify-email.php
 * 
 * Receives:
 * - GET: ?token=verification_token&email=user@example.com
 * - POST: { "token": "token", "email": "user@example.com" }
 * 
 * Returns:
 * {
 *   "success": true,
 *   "message": "Email verified successfully"
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
    // ===== STEP 1: GET EMAIL & TOKEN =====
    $email = '';
    $token = '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $email = strtolower(trim($_GET['email'] ?? ''));
        $token = trim($_GET['token'] ?? '');
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = strtolower(trim($input['email'] ?? ''));
        $token = trim($input['token'] ?? '');
    } else {
        ob_end_clean();
        http_response_code(405);
        jsonResponse(['error' => 'Method not allowed'], 405);
        exit;
    }

    // ===== STEP 2: VALIDATE INPUTS =====
    if (empty($email) || empty($token)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Email and token are required'], 400);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_end_clean();
        http_response_code(400);
        jsonResponse(['error' => 'Invalid email format'], 400);
        exit;
    }

    // ===== STEP 3: CONNECT TO DATABASE =====
    $manager = getManager();
    $namespace = 'meetdesk.users';

    // ===== STEP 4: FIND USER BY EMAIL =====
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($namespace, $query);
    $users = $cursor->toArray();

    if (empty($users)) {
        ob_end_clean();
        http_response_code(404);
        jsonResponse(['error' => 'User not found'], 404);
        exit;
    }

    $user = $users[0];

    // ===== STEP 5: CHECK IF ALREADY VERIFIED =====
    if (isset($user->emailVerified) && $user->emailVerified === true) {
        ob_end_clean();
        http_response_code(200);
        jsonResponse([
            'success' => true,
            'message' => 'Email already verified'
        ], 200);
        exit;
    }

    // ===== STEP 6: VALIDATE VERIFICATION TOKEN =====
    if (!isset($user->verificationToken) || $user->verificationToken !== $token) {
        ob_end_clean();
        http_response_code(401);
        jsonResponse(['error' => 'Invalid or expired verification token'], 401);
        exit;
    }

    // ===== STEP 7: CHECK TOKEN EXPIRATION (24 hours) =====
    if (isset($user->verificationTokenExpiry)) {
        $expiryTime = $user->verificationTokenExpiry->toDateTime()->getTimestamp();
        $currentTime = time();

        if ($currentTime > $expiryTime) {
            ob_end_clean();
            http_response_code(401);
            jsonResponse(['error' => 'Verification token has expired. Please register again or request a new token.'], 401);
            exit;
        }
    }

    // ===== STEP 8: MARK EMAIL AS VERIFIED =====
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['_id' => $user->_id],
        [
            '$set' => [
                'emailVerified' => true,
                'emailVerifiedAt' => new MongoDB\BSON\UTCDateTime(),
                'status' => 'active'  // Automatically activate on verification
            ],
            '$unset' => [
                'verificationToken' => 1,
                'verificationTokenExpiry' => 1
            ]
        ]
    );

    $result = $manager->executeBulkWrite($namespace, $bulk);

    if ($result->getModifiedCount() === 0) {
        ob_end_clean();
        http_response_code(500);
        jsonResponse(['error' => 'Failed to verify email'], 500);
        exit;
    }

    // ===== STEP 9: RETURN SUCCESS =====
    ob_end_clean();
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'message' => 'Email verified successfully! Your account is now active.'
    ], 200);

} catch (MongoDB\Exception\Exception $e) {
    ob_end_clean();
    error_log('MongoDB ERROR in verify-email.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Database error'], 500);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    error_log('ERROR in verify-email.php: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to verify email: ' . $e->getMessage()], 500);
    exit;
}
