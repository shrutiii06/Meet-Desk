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
// These allow the frontend (Vue.js) to make requests from browser
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');           // Allow any frontend
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== STEP 2: MONGODB CONNECTION =====
require_once __DIR__ . '/database.php';  // Get MONGODB_URI from database.php
$GLOBALS['mongoUri'] = getenv('MONGODB_URI') ?: MONGODB_URI;  // mongodb://localhost:27017
$GLOBALS['dbName'] = 'meetdesk';  // Database name

/**
 * getManager()
 * Creates MongoDB connection object
 * Used by every endpoint to connect to MongoDB
 */
function getManager() {
    return new MongoDB\Driver\Manager($GLOBALS['mongoUri']);
}

/**
 * getNamespace()
 * Returns "meetdesk.users" - the MongoDB collection
 * Where all user data is stored
 */
function getNamespace() {
    return $GLOBALS['dbName'] . '.users';
}

/**
 * jsonResponse($data, $code = 200)
 * Sends JSON response back to frontend
 * 
 * Example:
 *   jsonResponse(['error' => 'Email already exists'], 400);
 *   jsonResponse(['_id' => 'abc123', 'name' => 'John'], 201);
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}