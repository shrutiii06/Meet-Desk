<?php
// Quick test to insert a test user into MongoDB
require_once 'api/config.php';

try {
    $manager = getManager();
    $namespace = 'meetdesk.users';
    
    $bulk = new MongoDB\Driver\BulkWrite();
    
    $bulk->insert([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'isActive' => true,
        'memberSince' => date('c'),
        'profileImage' => null
    ]);
    
    $result = $manager->executeBulkWrite($namespace, $bulk);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test user created',
        'email' => 'test@example.com',
        'password' => 'password123',
        'note' => 'Use these credentials to login in the app',
        'inserted_id' => (string)$result->getInsertedIds()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
