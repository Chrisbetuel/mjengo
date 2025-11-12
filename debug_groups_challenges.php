<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT g.*, u.username as leader_name, c.name as challenge_name FROM groups g JOIN users u ON g.leader_id = u.id LEFT JOIN challenges c ON g.challenge_id = c.id');
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Groups with challenges:\n";
foreach($groups as $group) {
    echo 'Group ID: ' . $group['id'] . ', Name: ' . $group['name'] . ', Leader: ' . $group['leader_name'] . ', Challenge: ' . ($group['challenge_name'] ?? 'None') . ', Status: ' . $group['status'] . "\n";
}
?>
