<?php
/**
 * REGISTRATION ENDPOINT
 * 
 * URL: POST http://localhost/MD/api/auth/register
 * 
 * RECEIVES from Frontend (register.html):
 * {
 *   "name": "John Doe",
 *   "email": "john@example.com",
 *   "phone": "1234567890",
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

// Start output buffering to prevent any premature output
ob_start();

require_once __DIR__ . '/../config.php';  // Get MongoDB connection helpers
require_once __DIR__ . '/../mailer.php';      // Get email functions
require_once __DIR__ . '/../email-templates.php';  // Get email template functions

// Clean any output that may have occurred
ob_end_clean();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== STEP 1: CHECK REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== STEP 2: RECEIVE DATA FROM FRONTEND =====
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$phone = trim($input['phone'] ?? '') ?: 'Not provided';
$password = $input['password'] ?? '';

// ===== STEP 3: VALIDATE DATA =====
if (!$name || !$email || !$password) {
    jsonResponse(['error' => 'Name, email and password are required'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
}

try {
    // ===== STEP 4: CONNECT TO MONGODB =====
    $manager = getManager();  // MongoDB\Driver\Manager instance
    $ns = getNamespace();     // 'meetdesk.users'

    // ===== STEP 5: CHECK IF EMAIL ALREADY EXISTS =====
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $existing = $cursor->toArray();

    if (!empty($existing)) {
        jsonResponse(['error' => 'An account with this email already exists'], 400);
    }

    // ===== STEP 6: HASH PASSWORD =====
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // ===== STEP 7: GENERATE VERIFICATION TOKEN =====
    $verificationToken = bin2hex(random_bytes(32));  // Generate 64-char random token
    $tokenExpiry = new MongoDB\BSON\UTCDateTime((time() + 24 * 60 * 60) * 1000);  // 24 hours

    // ===== STEP 8: CREATE USER DOCUMENT =====
    $doc = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $hashedPassword,
        'profileImage' => null,
        'timezone' => 'UTC',
        'bio' => '',
        'emailVerified' => false,  // Email not verified yet
        'verificationToken' => $verificationToken,
        'verificationTokenExpiry' => $tokenExpiry,
        'status' => 'pending',  // Pending until email verification
        'memberSince' => new MongoDB\BSON\UTCDateTime(),
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ];

    // ===== STEP 9: INSERT INTO MONGODB =====
    $bulk = new MongoDB\Driver\BulkWrite;
    $id = $bulk->insert($doc);
    $manager->executeBulkWrite($ns, $bulk);

    // ===== STEP 10: SEND VERIFICATION EMAIL =====
    $verificationLink = "http://localhost/MD/verify-email.html?token={$verificationToken}&email=" . urlencode($email);
    
    // Send verification email (don't wait for result)
    try {
        sendEmailVerificationEmail($email, $name, $verificationLink);
    } catch (Exception $e) {
        error_log('Email verification failed for ' . $email . ': ' . $e->getMessage());
    }

    // ===== STEP 11: SEND WELCOME EMAIL =====
    try {
        $emailResult = sendRegistrationEmail($email, $name);
        if (!$emailResult['success']) {
            error_log('Registration email failed for ' . $email . ': ' . $emailResult['message']);
        }
    } catch (Exception $e) {
        error_log('Welcome email error: ' . $e->getMessage());
    }

    // ===== STEP 12: SEND RESPONSE BACK TO FRONTEND =====
    $user = [
        '_id' => (string) $id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'emailVerified' => false,
        'status' => 'pending',
        'memberSince' => date('c')
    ];

    jsonResponse([
        'success' => true,
        'message' => 'Registration successful! Please verify your email to activate your account.',
        'user' => $user
    ], 201);  // 201 = Created successfully
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}