<?php
require_once 'config.php';

$stmt = $pdo->prepare('SELECT gm.*, g.name as group_name, u.username as member_name FROM group_members gm JOIN groups g ON gm.group_id = g.id JOIN users u ON gm.user_id = u.id ORDER BY gm.group_id, gm.user_id');
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Group members:\n";
foreach($members as $member) {
    echo "Group: {$member['group_name']} (ID: {$member['group_id']}), Member: {$member['member_name']} (ID: {$member['user_id']}), Status: {$member['status']}\n";
}
?>
