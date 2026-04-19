<?php
/**
 * AGORA TOKEN GENERATOR
 * 
 * Generates a temporary access token for the Agora Video SDK.
 * Required when the Agora project is configured in "Secure Mode" (App ID + App Certificate).
 * 
 * Provide your App ID and App Certificate below to secure your productions endpoints!
 */

require_once '../config.php';
require_once '../lib/AgoraDynamicKey/RtcTokenBuilder2.php';

// ===== ADD YOUR AGORA CREDENTIALS HERE =====
$appId = "e4913bff00bd4c8aa0d39945b3770209";
$appCertificate = "f7ece2fb0efb46e29ce3a189bedff1b3";
// ===========================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

$channelName = isset($_REQUEST['roomId']) ? trim($_REQUEST['roomId']) : '';
$uidStr = isset($_REQUEST['uid']) ? trim($_REQUEST['uid']) : '';

if (empty($channelName) || empty($uidStr)) {
    http_response_code(400);
    jsonResponse(['error' => 'roomId and uid are required'], 400);
    exit;
}

if ($appId === "YOUR_AGORA_APP_ID_HERE" || empty($appId) || empty($appCertificate)) {
    http_response_code(500);
    jsonResponse(['error' => 'Server is misconfigured. Please set Agora App ID and App Certificate in api/meetings/generate-token.php'], 500);
    exit;
}

try {
    $uid = (int)$uidStr;
    $role = RtcTokenBuilder2::ROLE_PUBLISHER;
    // Token valid for 24 hours
    $expireTimeInSeconds = 86400;
    
    // Generate the Token
    $token = RtcTokenBuilder2::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $expireTimeInSeconds, $expireTimeInSeconds);
    
    http_response_code(200);
    jsonResponse([
        'success' => true,
        'appId' => $appId,
        'token' => $token,
        'uid' => $uid,
        'roomId' => $channelName
    ], 200);
    
} catch (Exception $e) {
    error_log('Token generation error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to generate token'], 500);
}
