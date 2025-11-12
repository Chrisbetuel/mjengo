<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM group_members WHERE user_id = 1');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Number of group memberships for user 1: ' . $result['count'] . PHP_EOL;
?>
