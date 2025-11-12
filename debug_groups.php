<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT g.*, u.username as leader_name FROM groups g JOIN users u ON g.leader_id = u.id');
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Groups in database:\n";
foreach($groups as $group) {
    echo 'Group ID: ' . $group['id'] . ', Name: ' . $group['name'] . ', Leader: ' . $group['leader_name'] . ', Status: ' . $group['status'] . "\n";
}
?>
