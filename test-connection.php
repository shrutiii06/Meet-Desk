<?php
/**
 * Test MongoDB Connection
 * Visit: http://localhost/MD/test-connection.php
 */

header('Content-Type: application/json');

try {
    echo "Testing MongoDB Connection...\n\n";
    
    // Check if MongoDB extension is loaded
    if (!extension_loaded('mongodb')) {
        throw new Exception('MongoDB extension is not loaded in PHP');
    }
    echo "✓ MongoDB extension loaded\n";
    
    // Try to connect to MongoDB
    $uri = 'mongodb://localhost:27017';
    $manager = new MongoDB\Driver\Manager($uri);
    echo "✓ Connected to MongoDB at: $uri\n";
    
    // Try to insert a test document
    $ns = 'meetdesk.test';
    $doc = [
        'test' => true,
        'timestamp' => new MongoDB\BSON\UTCDateTime(),
        'message' => 'Connection test successful'
    ];
    
    $bulk = new MongoDB\Driver\BulkWrite;
    $insertedId = $bulk->insert($doc);
    $result = $manager->executeBulkWrite($ns, $bulk);
    
    echo "✓ Inserted test document with ID: " . (string)$insertedId . "\n";
    echo "✓ Inserted count: " . $result->getInsertedCount() . "\n";
    
    // Query the document back
    $query = new MongoDB\Driver\Query(['test' => true]);
    $cursor = $manager->executeQuery($ns, $query);
    $docs = iterator_to_array($cursor);
    
    if (count($docs) > 0) {
        echo "✓ Retrieved test document\n";
        echo "✓ All systems working correctly!\n\n";
        
        echo json_encode([
            'status' => 'success',
            'message' => 'MongoDB connection working perfectly',
            'extensions' => [
                'mongodb' => extension_loaded('mongodb') ? 'enabled' : 'disabled'
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        throw new Exception('Could not retrieve test document');
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'extensions' => [
            'mongodb' => extension_loaded('mongodb') ? 'enabled' : 'disabled'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
