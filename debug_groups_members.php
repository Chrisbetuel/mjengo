<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT g.id, g.name, g.leader_id, gm.user_id, gm.status FROM groups g LEFT JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id IS NOT NULL ORDER BY g.id');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Groups and members:' . PHP_EOL;
foreach($results as $row) {
    echo 'Group ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Leader: ' . $row['leader_id'] . ', Member: ' . $row['user_id'] . ', Status: ' . $row['status'] . PHP_EOL;
}
?>
