<?php
require_once 'config.php';

echo 'Testing API routing with mjengo prefix...' . PHP_EOL;
$_SERVER['REQUEST_URI'] = '/mjengo/api.php/group-members';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Simulate the API routing logic
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

echo 'Method: ' . $method . PHP_EOL;
echo 'Request: ' . $request . PHP_EOL;

// Remove query string and base path
$request = str_replace(BASE_URL . '/api.php', '', $request);
echo 'After BASE_URL replacement: ' . $request . PHP_EOL;
$request = parse_url($request, PHP_URL_PATH);
echo 'After parse_url: ' . $request . PHP_EOL;
$request = trim($request, '/');
echo 'After trim: ' . $request . PHP_EOL;

// Split the request into parts
$parts = explode('/', $request);
echo 'Parts: ';
print_r($parts);
$endpoint = $parts[0] ?? '';
$id = $parts[1] ?? null;
echo 'Endpoint: ' . $endpoint . PHP_EOL;
echo 'ID: ' . $id . PHP_EOL;

// Handle case where endpoint is 'mjengo' (when accessed via full URL)
if ($endpoint === 'mjengo') {
    $endpoint = $parts[1] ?? '';
    $id = $parts[2] ?? null;
    echo 'After mjengo handling - Endpoint: ' . $endpoint . PHP_EOL;
    echo 'After mjengo handling - ID: ' . $id . PHP_EOL;
}

echo 'Final endpoint: ' . $endpoint . PHP_EOL;
?>
