<?php
// ===== RESET PASSWORD =====
// RECEIVES: POST request with email, code, and new password
// RETURNS: JSON with success/error

error_log('=== RESET PASSWORD START ===');

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // ===== STEP 1: GET REQUEST DATA =====
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    
    if (!isset($data['email']) || !isset($data['code']) || !isset($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'Email, code, and password are required'], 400);
    }
    
    $email = strtolower(trim($data['email']));
    $code = trim($data['code']);
    $newPassword = $data['password'];
    
    error_log('Email: ' . $email . ', Code: ' . $code . ', Password length: ' . strlen($newPassword));
    
    // ===== STEP 2: VALIDATE PASSWORD =====
    if (strlen($newPassword) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
    }
    
    // ===== STEP 3: FIND USER =====
    $manager = getManager();
    $ns = getNamespace();
    
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();
    $user = !empty($users) ? $users[0] : null;
    
    error_log('User found: ' . ($user ? 'yes' : 'no'));
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 401);
    }
    
    // ===== STEP 4: VERIFY CODE =====
    error_log('DB code: [' . ($user->reset_code ?? 'null') . ']');
    error_log('Input code: [' . $code . ']');
    error_log('Code match: ' . ((isset($user->reset_code) && $user->reset_code === $code) ? 'yes' : 'no'));
    
    if (!isset($user->reset_code)) {
        error_log('No reset code found in database');
        jsonResponse(['success' => false, 'message' => 'No password reset request found. Please request a new code.'], 401);
    }
    
    if ($user->reset_code !== $code) {
        error_log('Code mismatch');
        jsonResponse(['success' => false, 'message' => 'Invalid reset code. The code you entered is incorrect.'], 401);
    }
    
    // Check if code expired
    $now = new MongoDB\BSON\UTCDateTime(time() * 1000);
    error_log('Code expires at: ' . $user->reset_code_expires . ', Now: ' . $now);
    
    if ($user->reset_code_expires < $now) {
        error_log('Code expired');
        jsonResponse(['success' => false, 'message' => 'Reset code has expired'], 401);
    }
    
    error_log('Code verified successfully');
    
    // ===== STEP 5: HASH NEW PASSWORD =====
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // ===== STEP 6: UPDATE PASSWORD IN DATABASE =====
    $updateData = [
        '$set' => [
            'password' => $hashedPassword,
            'updated_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ],
        '$unset' => [
            'reset_code' => "",
            'reset_code_expires' => "",
            'reset_code_attempts' => ""
        ]
    ];
    
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $user->_id],
        $updateData
    );
    $result = $manager->executeBulkWrite($ns, $bulk);
    
    error_log('DB update result: ' . $result->getModifiedCount() . ' records updated');
    
    if ($result->getModifiedCount() > 0) {
        error_log('Password reset successful');
        jsonResponse([
            'success' => true,
            'message' => 'Password reset successfully. You can now log in with your new password.'
        ]);
    } else {
        error_log('Password reset failed - no records updated');
        jsonResponse([
            'success' => false,
            'message' => 'Failed to update password'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}