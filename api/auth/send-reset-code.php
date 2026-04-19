<?php
// ===== SEND PASSWORD RESET CODE =====
// RECEIVES: POST request with email
// RETURNS: JSON with success/error message

error_log('=== RESET CODE START ===');

require_once '../config.php';
require_once '../email-templates.php';

error_log('Config and mailer loaded');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Wrong method: ' . $_SERVER['REQUEST_METHOD']);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // ===== STEP 1: GET REQUEST DATA =====
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    error_log('Data received: ' . json_encode($data));
    
    if (!isset($data['email'])) {
        error_log('No email in request');
        jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
    }
    
    $email = strtolower(trim($data['email']));
    error_log('Email: ' . $email);
    
    // ===== STEP 2: FIND USER IN DATABASE =====
    $manager = getManager();
    $ns = getNamespace();
    
    $query = new MongoDB\Driver\Query(['email' => $email]);
    $cursor = $manager->executeQuery($ns, $query);
    $users = $cursor->toArray();
    $user = !empty($users) ? $users[0] : null;
    error_log('User found: ' . ($user ? 'yes' : 'no'));
    
    if (!$user) {
        // Security: Don't reveal if email exists or not
        error_log('User not found, returning generic message');
        jsonResponse(['success' => true, 'message' => 'If this email exists, you will receive a password reset code']);
    }
    
    error_log('Generating reset code');
    // ===== STEP 3: GENERATE RESET CODE =====
    // 6-digit random code
    $resetCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $resetCodeExpires = new MongoDB\BSON\UTCDateTime((time() + 1800) * 1000); // 30 minutes
    error_log('Reset code: ' . $resetCode);
    
    // ===== STEP 4: SAVE CODE TO DATABASE =====
    $updateData = [
        '$set' => [
            'reset_code' => $resetCode,
            'reset_code_expires' => $resetCodeExpires,
            'reset_code_attempts' => 0
        ]
    ];
    
    error_log('Update data: ' . json_encode($updateData, JSON_UNESCAPED_UNICODE));
    
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $user->_id],
        $updateData
    );
    $result = $manager->executeBulkWrite($ns, $bulk);
    error_log('DB update result: ' . $result->getModifiedCount() . ' records updated');
    
    // ===== STEP 5: SEND EMAIL WITH CODE =====
    $userName = $user->name ?? $user->email;
    error_log('Sending email to: ' . $userName);
    $emailResult = sendPasswordResetEmailEnhanced((string)$user->email, $userName, $resetCode);
    error_log('Email result: ' . json_encode($emailResult));
    
    if ($emailResult['success']) {
        error_log('Email sent successfully');
        jsonResponse([
            'success' => true,
            'message' => 'Password reset code sent to your email. Check your inbox.'
        ]);
    } else {
        // Code saved but email failed
        error_log('Email failed');
        jsonResponse([
            'success' => false,
            'message' => 'Error sending email: ' . $emailResult['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Reset code error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}