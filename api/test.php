<?php
// ===== TEST FILE TO CHECK IF API IS ACCESSIBLE =====

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Test 1: Check if MongoDB connection works
require_once 'config.php';

try {
    $manager = getManager();
    jsonResponse(['status' => 'success', 'message' => 'API is working! MongoDB connection OK']);
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(['status' => 'error', 'message' => 'MongoDB connection failed: ' . $e->getMessage()], 500);
}
?>