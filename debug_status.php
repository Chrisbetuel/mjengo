<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT status FROM group_members WHERE user_id = 1');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Statuses for user 1:' . PHP_EOL;
foreach($results as $row) {
    echo 'Status: ' . $row['status'] . PHP_EOL;
}
?>
