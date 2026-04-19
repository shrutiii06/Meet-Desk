<?php
/**
 * API CONFIG - Central configuration for all backend endpoints
 * 
 * This file handles:
 * 1. CORS headers (allows frontend to call backend)
 * 2. MongoDB connection setup
 * 3. Helper functions for responses
 */

// ===== TIMEZONE FIX =====
date_default_timezone_set('Asia/Kolkata');

// ===== STEP 1: CORS HEADERS =====
header('Content-Type: application/json');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== STEP 2: MONGODB CONNECTION =====
require_once __DIR__ . '/database.php';
$GLOBALS['mongoUri'] = getenv('MONGODB_URI') ?: MONGODB_URI;
$GLOBALS['dbName'] = 'meetdesk';

function getManager() {
    return new MongoDB\Driver\Manager($GLOBALS['mongoUri']);
}

function getNamespace() {
    return $GLOBALS['dbName'] . '.users';
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
