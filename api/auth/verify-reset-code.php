<?php
// ===== VERIFY PASSWORD RESET CODE =====
// RECEIVES: POST request with email and code
// RETURNS: JSON with success/error

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // ===== STEP 1: GET REQUEST DATA =====
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    
    if (!isset($data['email']) || !isset($data['code'])) {
        jsonResponse(['success' => false, 'message' => 'Email and code are required'], 400);
    }
    
    $email = strtolower(trim($data['email']));
    $code = trim($data['code']);
    
    // ===== STEP 2: FIND USER =====
    $manager = getManager();
    $ns = getNamespace();
    
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();
    $user = !empty($users) ? $users[0] : null;
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 401);
    }
    
    // ===== STEP 3: VERIFY CODE =====
    // Check if code exists
    if (!isset($user->reset_code) || $user->reset_code !== $code) {
        jsonResponse(['success' => false, 'message' => 'Invalid reset code'], 401);
    }
    
    // Check if code expired
    $now = new MongoDB\BSON\UTCDateTime(time() * 1000);
    if ($user->reset_code_expires < $now) {
        jsonResponse(['success' => false, 'message' => 'Reset code has expired'], 401);
    }
    
    // Check attempts (prevent brute force)
    $attempts = $user->reset_code_attempts ?? 0;
    if ($attempts > 5) {
        jsonResponse(['success' => false, 'message' => 'Too many attempts. Try again later.'], 429);
    }
    
    // ===== STEP 4: CODE IS VALID =====
    jsonResponse(['success' => true, 'message' => 'Code verified. You can now reset your password.']);
    
} catch (Exception $e) {
    error_log('Verify code error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}