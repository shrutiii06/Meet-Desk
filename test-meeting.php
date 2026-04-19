<?php
// Quick test to insert a meeting into MongoDB
require_once 'api/config.php';

try {
    $manager = getManager();
    $namespace = 'meetdesk.meetings';
    
    // Create a test meeting for today
    $today = date('Y-m-d');
    $bulk = new MongoDB\Driver\BulkWrite();
    
    $bulk->insert([
        'userEmail' => 'test@example.com',
        'userName' => 'Test User',
        'topic' => '1st meeting',
        'description' => 'Team standup meeting',
        'date' => $today,
        'time' => '08:00',
        'duration' => 60,
        'timezone' => 'IST',
        'repeat' => 'never',
        'enableWaitingRoom' => false,
        'autoRecord' => true,
        'addToCalendar' => true,
        'scheduledAt' => date('c'),
        'status' => 'scheduled'
    ]);
    
    $result = $manager->executeBulkWrite($namespace, $bulk);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test meeting created',
        'inserted_id' => $result->getInsertedIds(),
        'date' => $today
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
