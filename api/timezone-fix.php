<?php
/**
 * Timezone Fix for MeetDesk
 * Add this to the top of config.php to fix server timezone
 */

// Set timezone to India Standard Time (UTC+5:30)
date_default_timezone_set('Asia/Kolkata');

// Verify timezone is set correctly
$currentTimezone = date_default_timezone_get();
$currentTime = date('Y-m-d H:i:s');

// Log for debugging
error_log("Timezone set to: $currentTimezone");
error_log("Current server time: $currentTime");
