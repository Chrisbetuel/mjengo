<?php
require_once 'config.php';

echo "Testing API endpoint routing...\n";

$request = '/api.php/group-members';
$parts = explode('/', trim($request, '/'));
echo "Request: $request\n";
echo "Parts: ";
print_r($parts);

echo "\nTesting endpoint extraction...\n";
$endpoint = $parts[0] ?? '';
$id = $parts[1] ?? null;
echo "Endpoint: $endpoint\n";
echo "ID: $id\n";

echo "\nTesting switch case...\n";
switch ($endpoint) {
    case 'group-members':
        echo "Matched group-members endpoint\n";
        break;
    default:
        echo "No match found\n";
}
?>
