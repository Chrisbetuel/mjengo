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
echo 'Endpoint: ' . $endpoint . PHP_EOL;
?>
