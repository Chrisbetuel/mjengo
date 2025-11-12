<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT gm.status, COUNT(*) as count FROM group_members gm GROUP BY gm.status');
$stmt->execute();
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Status counts:\n";
foreach($statuses as $status) {
    echo "Status: '{$status['status']}' - Count: {$status['count']}\n";
}
?>
