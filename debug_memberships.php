<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT gm.id, g.name, gm.status FROM group_members gm JOIN groups g ON gm.group_id = g.id WHERE gm.user_id = 1');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Group memberships for user 1:' . PHP_EOL;
foreach($results as $row) {
    echo 'ID: ' . $row['id'] . ', Group: ' . $row['name'] . ', Status: ' . $row['status'] . PHP_EOL;
}
?>
