<?php
require_once 'config.php';

echo 'BASE_URL: ' . BASE_URL . PHP_EOL;
$request = '/mjengo/api.php/group-members';
echo 'Request: ' . $request . PHP_EOL;
$request_clean = str_replace(BASE_URL . '/api.php', '', $request);
echo 'After BASE_URL replacement: ' . $request_clean . PHP_EOL;
$request_clean = parse_url($request_clean, PHP_URL_PATH);
echo 'After parse_url: ' . $request_clean . PHP_EOL;
$request_clean = trim($request_clean, '/');
echo 'After trim: ' . $request_clean . PHP_EOL;
$parts = explode('/', $request_clean);
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
?>
