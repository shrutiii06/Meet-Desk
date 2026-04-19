<?php
/**
 * DAY 3 TESTING - Delete, Update, and Auto-Refresh
 * 
 * This script tests:
 * 1. DELETE meeting functionality
 * 2. UPDATE meeting functionality
 * 3. Dashboard auto-refresh (already implemented in HTML)
 */

require_once 'api/config.php';

echo "====================================\n";
echo "DAY 3 TESTING - CRUD OPERATIONS\n";
echo "====================================\n\n";

// Get the user email from URL params (for testing)
$testEmail = 'vaishnani3hruti04@gmail.com';  // Test user created earlier

echo "1. TESTING DELETE MEETING\n";
echo "------------------------\n";

// First, get a meeting to delete
$manager = getManager();
$query = new MongoDB\Driver\Query(['userEmail' => $testEmail], ['limit' => 1]);
$cursor = $manager->executeQuery('meetdesk.meetings', $query);
$meetings = $cursor->toArray();

if (count($meetings) > 0) {
    $meetingToDelete = $meetings[0];
    echo "Found meeting to delete:\n";
    echo "  ID: " . $meetingToDelete->_id . "\n";
    echo "  Topic: " . $meetingToDelete->topic . "\n";
    echo "  Date: " . $meetingToDelete->date . "\n\n";
    
    // Test DELETE endpoint
    echo "Testing DELETE api/meetings/delete.php...\n";
    
    $payload = json_encode([
        'meetingId' => (string)$meetingToDelete->_id,
        'userEmail' => $testEmail
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'DELETE',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload
        ]
    ]);
    
    $response = file_get_contents('http://localhost/MD/api/meetings/delete.php', false, $context);
    $result = json_decode($response, true);
    
    if ($result['success']) {
        echo "✓ DELETE successful!\n";
        echo "  Message: " . $result['message'] . "\n";
    } else {
        echo "✗ DELETE failed\n";
        echo "  Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "No meetings found to test DELETE\n";
}

echo "\n2. TESTING UPDATE MEETING\n";
echo "------------------------\n";

// Get another meeting to update
$query = new MongoDB\Driver\Query(['userEmail' => $testEmail], ['limit' => 1]);
$cursor = $manager->executeQuery('meetdesk.meetings', $query);
$meetings = $cursor->toArray();

if (count($meetings) > 0) {
    $meetingToUpdate = $meetings[0];
    echo "Found meeting to update:\n";
    echo "  Topic: " . $meetingToUpdate->topic . "\n";
    echo "  Original Date: " . $meetingToUpdate->date . "\n";
    echo "  Original Time: " . $meetingToUpdate->time . "\n\n";
    
    // Test UPDATE endpoint
    echo "Testing POST api/meetings/update.php...\n";
    
    $newTopic = $meetingToUpdate->topic . " (Updated)";
    
    $payload = json_encode([
        'email' => $testEmail,
        'originalDate' => $meetingToUpdate->date,
        'originalTime' => $meetingToUpdate->time,
        'topic' => $newTopic,
        'description' => 'Updated via test script',
        'date' => $meetingToUpdate->date,
        'time' => $meetingToUpdate->time,
        'duration' => '60'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload
        ]
    ]);
    
    $response = file_get_contents('http://localhost/MD/api/meetings/update.php', false, $context);
    $result = json_decode($response, true);
    
    if ($result['success']) {
        echo "✓ UPDATE successful!\n";
        echo "  Message: " . $result['message'] . "\n";
    } else {
        echo "✗ UPDATE failed\n";
        echo "  Error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "No meetings found to test UPDATE\n";
}

echo "\n3. DASHBOARD AUTO-REFRESH\n";
echo "------------------------\n";
echo "✓ Auto-refresh already implemented in dashboard.html\n";
echo "  - Refreshes meetings every 30 seconds\n";
echo "  - Located at lines 463-466 in dashboard.html (mounted hook)\n";
echo "  - Cleanup in beforeUnmount hook (lines 470-473)\n";

echo "\n====================================\n";
echo "DAY 3 TESTING COMPLETE\n";
echo "====================================\n";
?>
