<?php
/**
 * Web-based Cron Trigger for Meeting Reminders
 * This runs through Apache where MongoDB extension works
 */

// Prevent direct browser access (optional security)
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Allow from localhost only (Task Scheduler runs locally)
if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied']));
}

// Set headers
header('Content-Type: application/json');

// Include the actual cron job
ob_start();
require_once __DIR__ . '/../cron/send-reminders-cron.php';
$output = ob_get_clean();

// Return success response
echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'Reminder cron job executed successfully'
]);
