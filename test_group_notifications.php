<?php
require_once 'config.php';
require_once 'core/notifications.php';

// Test script for group invitation notifications
echo "<h1>Testing Group Invitation Notifications</h1>";

// Get test users
$stmt = $pdo->prepare("SELECT id, username FROM users LIMIT 3");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) < 2) {
    echo "<p style='color: red;'>Need at least 2 users for testing. Please create more users first.</p>";
    exit;
}

echo "<h2>Test Users:</h2>";
foreach ($users as $user) {
    echo "- {$user['username']} (ID: {$user['id']})<br>";
}

$leader = $users[0];
$member1 = $users[1];
$member2 = isset($users[2]) ? $users[2] : $users[1];

echo "<h2>Testing Notification Functions</h2>";

// Test 1: Send group invitation notification
echo "<h3>Test 1: Group Invitation Notification</h3>";
$group_name = "Test Group " . date('Y-m-d H:i:s');
$notification_title = "Group Invitation";
$notification_message = "You have been invited to join the group '{$group_name}'.";
$action_url = "accept_invitation.php?id=123"; // Mock ID
$action_text = "View Invitation";

$result = sendNotification($member1['id'], $notification_title, $notification_message, 'group_invitations', $action_url, $action_text);
echo "Sending invitation to {$member1['username']}: " . ($result ? "<span style='color: green;'>SUCCESS</span>" : "<span style='color: red;'>FAILED</span>") . "<br>";

// Test 2: Send acceptance notification
echo "<h3>Test 2: Invitation Acceptance Notification</h3>";
$notification_title = "Group Invitation Accepted";
$notification_message = "{$member1['username']} has accepted your invitation to join the group '{$group_name}'.";
$action_url = "group_management.php?group_id=123"; // Mock ID
$action_text = "View Group";

$result = sendNotification($leader['id'], $notification_title, $notification_message, 'group_invitations', $action_url, $action_text);
echo "Sending acceptance notification to {$leader['username']}: " . ($result ? "<span style='color: green;'>SUCCESS</span>" : "<span style='color: red;'>FAILED</span>") . "<br>";

// Test 3: Send decline notification
echo "<h3>Test 3: Invitation Decline Notification</h3>";
$notification_title = "Group Invitation Declined";
$notification_message = "{$member2['username']} has declined your invitation to join the group '{$group_name}'.";
$action_url = "group_management.php?group_id=123"; // Mock ID
$action_text = "View Group";

$result = sendNotification($leader['id'], $notification_title, $notification_message, 'group_invitations', $action_url, $action_text);
echo "Sending decline notification to {$leader['username']}: " . ($result ? "<span style='color: green;'>SUCCESS</span>" : "<span style='color: red;'>FAILED</span>") . "<br>";

// Check notification counts
echo "<h2>Notification Counts:</h2>";
foreach ($users as $user) {
    $count = getUnreadNotificationCount($user['id']);
    echo "{$user['username']}: {$count} unread notifications<br>";
}

// Show recent notifications for each user
echo "<h2>Recent Notifications:</h2>";
foreach ($users as $user) {
    echo "<h3>{$user['username']}'s notifications:</h3>";
    $notifications = getInAppNotifications($user['id'], 5, false);
    if (empty($notifications)) {
        echo "<p>No notifications found.</p>";
    } else {
        foreach ($notifications as $notification) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>{$notification['title']}</strong><br>";
            echo "{$notification['message']}<br>";
            echo "<small>Type: {$notification['notification_type']} | Created: {$notification['created_at']}</small>";
            echo "</div>";
        }
    }
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='dashboard_notifications.php'>View notifications in dashboard</a></p>";
?>
