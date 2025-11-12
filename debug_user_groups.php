<?php
require_once 'config.php';

// Simulate logged in user (admin with ID 1)
$_SESSION['user_id'] = 1;

// Fetch user's groups (where they are members)
$stmt = $pdo->prepare("
    SELECT g.id, g.name as group_name, g.description, g.status as group_status, u.username as leader_name, gm.joined_at, gm.status as member_status, g.leader_id
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gm.user_id = ? AND gm.status IN ('active')
    ORDER BY gm.joined_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "User groups for user ID " . $_SESSION['user_id'] . ":\n";
foreach($user_groups as $group) {
    echo "Group ID: {$group['id']}, Name: {$group['group_name']}, Leader: {$group['leader_name']}, Member Status: {$group['member_status']}, Leader ID: {$group['leader_id']}, User ID: {$_SESSION['user_id']}\n";
}
?>
