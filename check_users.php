<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users');
$stmt->execute();
$result = $stmt->fetch();
echo 'Total users: ' . $result['count'] . PHP_EOL;

$stmt = $pdo->prepare('SELECT id, username FROM users LIMIT 5');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'First 5 users:' . PHP_EOL;
foreach ($users as $user) {
    echo "- ID: {$user['id']}, Username: {$user['username']}" . PHP_EOL;
}
?>
