<?php
// ===== EMAIL TEST SCRIPT =====
// Test if email sending works with your Gmail credentials

header('Content-Type: application/json');

require_once 'mailer.php';

$testEmail = $_GET['email'] ?? 'test@example.com';
$testName = $_GET['name'] ?? 'Test User';

error_log("========== EMAIL TEST START ==========");
error_log("Testing email to: $testEmail");
error_log("Testing name: $testName");

$result = sendRegistrationEmail($testEmail, $testName);

error_log("Result: " . json_encode($result));
error_log("========== EMAIL TEST END ==========");

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message'],
    'debug' => 'Check error_log for detailed SMTP responses'
]);
?>