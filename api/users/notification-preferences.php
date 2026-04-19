<?php
/**
 * NOTIFICATION PREFERENCES ENDPOINT
 * 
 * URL: GET http://localhost/MD/api/users/notification-preferences.php?email=user@example.com
 *      POST http://localhost/MD/api/users/notification-preferences.php
 * 
 * GET RECEIVES:
 * - email: user email
 * 
 * GET RETURNS:
 * {
 *   "success": true,
 *   "preferences": {
 *     "emailOnMeetingScheduled": true,
 *     "emailOnMeetingReminder": true,
 *     "emailOnMeetingChanged": true,
 *     "emailOnMeetingCancelled": true,
 *     "emailOnAttendeeResponse": false,
 *     "dailyDigest": false
 *   }
 * }
 * 
 * POST RECEIVES:
 * {
 *   "email": "user@example.com",
 *   "emailOnMeetingScheduled": true,
 *   "emailOnMeetingReminder": true,
 *   "emailOnMeetingChanged": true,
 *   "emailOnMeetingCancelled": true,
 *   "emailOnAttendeeResponse": false,
 *   "dailyDigest": false
 * }
 * 
 * POST RETURNS:
 * {
 *   "success": true,
 *   "message": "Notification preferences updated successfully"
 * }
 */

error_log('=== NOTIFICATION PREFERENCES START ===');

require_once __DIR__ . '/../config.php';

// ===== STEP 1: HANDLE GET REQUEST =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['email'])) {
            jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
        }

        $email = strtolower(trim($_GET['email']));
        error_log('Getting preferences for: ' . $email);

        $manager = getManager();
        $ns = getNamespace();

        // Find user
        $query = new MongoDB\Driver\Query(['email' => $email]);
        $cursor = $manager->executeQuery($ns, $query);
        $users = $cursor->toArray();

        if (empty($users)) {
            error_log('User not found');
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $user = $users[0];

        // Get notification preferences (if not set, return defaults)
        $prefs = isset($user->notificationPreferences) ? (array)$user->notificationPreferences : [];

        // Set default values for missing preferences
        $defaults = [
            'emailOnMeetingScheduled' => true,
            'emailOnMeetingReminder' => true,
            'emailOnMeetingChanged' => true,
            'emailOnMeetingCancelled' => true,
            'emailOnAttendeeResponse' => false,
            'dailyDigest' => false
        ];

        $preferences = array_merge($defaults, $prefs);

        error_log('Preferences fetched: ' . json_encode($preferences));

        jsonResponse([
            'success' => true,
            'preferences' => $preferences
        ]);

    } catch (Exception $e) {
        error_log('GET Preferences error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ===== STEP 2: HANDLE POST REQUEST =====
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        if (!isset($data['email'])) {
            jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
        }

        $email = strtolower(trim($data['email']));
        error_log('Updating preferences for: ' . $email);

        $manager = getManager();
        $ns = getNamespace();

        // Build preferences object from request data
        $preferences = [
            'emailOnMeetingScheduled' => (bool)($data['emailOnMeetingScheduled'] ?? true),
            'emailOnMeetingReminder' => (bool)($data['emailOnMeetingReminder'] ?? true),
            'emailOnMeetingChanged' => (bool)($data['emailOnMeetingChanged'] ?? true),
            'emailOnMeetingCancelled' => (bool)($data['emailOnMeetingCancelled'] ?? true),
            'emailOnAttendeeResponse' => (bool)($data['emailOnAttendeeResponse'] ?? false),
            'dailyDigest' => (bool)($data['dailyDigest'] ?? false),
            'updatedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        error_log('New preferences: ' . json_encode($preferences));

        // Update user document
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['email' => $email],
            ['$set' => ['notificationPreferences' => $preferences]]
        );
        $result = $manager->executeBulkWrite($ns, $bulk);

        if ($result->getModifiedCount() > 0) {
            error_log('Preferences updated successfully');
            jsonResponse([
                'success' => true,
                'message' => 'Notification preferences updated successfully'
            ]);
        } else {
            error_log('No document was updated');
            jsonResponse([
                'success' => false,
                'message' => 'Failed to update preferences'
            ], 400);
        }

    } catch (Exception $e) {
        error_log('POST Preferences error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ===== STEP 3: METHOD NOT ALLOWED =====
else {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
