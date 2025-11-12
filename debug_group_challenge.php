<?php
require_once 'config.php';

$group_id = sanitize($_GET['group_id'] ?? '1'); // Default to 1 for testing

echo "<h1>Debug Group Challenge</h1>";
echo "<p>Group ID: $group_id</p>";

// Check group and challenge
$stmt = $pdo->prepare("SELECT g.*, c.name as challenge_name, c.status as challenge_status, c.daily_amount, c.start_date, c.end_date FROM groups g LEFT JOIN challenges c ON g.id = c.group_id WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

echo "<h2>Group Data:</h2>";
echo "<pre>" . print_r($group, true) . "</pre>";

if (!empty($group['challenge_name'])) {
    echo "<h2>Challenge Details:</h2>";
    echo "Name: " . $group['challenge_name'] . "<br>";
    echo "Status: " . $group['challenge_status'] . "<br>";
    echo "Daily Amount: " . $group['daily_amount'] . "<br>";

    // Check participants
    echo "<h2>Participants:</h2>";
    $stmt = $pdo->prepare("
        SELECT p.*, u.username
        FROM participants p
        JOIN challenges c ON p.challenge_id = c.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE c.name = ? AND c.group_id = ?
    ");
    $stmt->execute([$group['challenge_name'], $group_id]);
    $participants = $stmt->fetchAll();
    echo "<pre>" . print_r($participants, true) . "</pre>";

    // Check active participants
    echo "<h2>Active Participants:</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_participants
        FROM participants p
        JOIN challenges c ON p.challenge_id = c.id
        WHERE c.name = ? AND c.group_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$group['challenge_name'], $group_id]);
    $total_participants = $stmt->fetch()['total_participants'];
    echo "Total Active Participants: $total_participants<br>";

    // Check today's payments
    echo "<h2>Today's Payments:</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as paid_today
        FROM payments pay
        JOIN participants p ON pay.participant_id = p.id
        JOIN challenges c ON p.challenge_id = c.id
        WHERE c.name = ? AND c.group_id = ? AND pay.payment_date = CURDATE() AND pay.status = 'paid'
    ");
    $stmt->execute([$group['challenge_name'], $group_id]);
    $paid_today = $stmt->fetch()['paid_today'];
    echo "Paid Today: $paid_today<br>";

    // Check challenge ID
    echo "<h2>Challenge ID:</h2>";
    $stmt = $pdo->prepare("SELECT id FROM challenges WHERE name = ? AND group_id = ?");
    $stmt->execute([$group['challenge_name'], $group_id]);
    $challenge_result = $stmt->fetch();
    echo "Challenge ID: " . ($challenge_result ? $challenge_result['id'] : 'Not found') . "<br>";
}
?>
