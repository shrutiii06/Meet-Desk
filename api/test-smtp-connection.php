<?php
// Test basic SMTP connection to Gmail

echo "Testing Gmail SMTP Connection...\n\n";

$host = 'smtp.gmail.com';
$port = 587;

echo "Attempting to connect to $host:$port...\n";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$connection = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

if (!$connection) {
    echo "❌ Connection Failed: $errstr ($errno)\n";
    exit;
}

echo "✅ Connected! Waiting for server response...\n";
$response = fgets($connection, 512);
echo "Server says: $response\n\n";

echo "Sending EHLO command...\n";
fputs($connection, "EHLO localhost\r\n");
$response = fgets($connection, 512);
echo "Response: $response";

while (substr($response, 3, 1) != ' ') {
    $response = fgets($connection, 512);
    echo "Response: $response";
}

echo "\n✅ SMTP connection test passed!\n";
echo "❌ But authentication is still failing - this might be an app password issue\n";

fputs($connection, "QUIT\r\n");
fclose($connection);
?>