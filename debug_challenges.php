<?php
require_once 'config.php';

echo "<h1>Debug Challenges</h1>";

// Check all challenges
$stmt = $pdo->query("SELECT * FROM challenges");
$challenges = $stmt->fetchAll();

echo "<h2>All Challenges:</h2>";
echo "<pre>" . print_r($challenges, true) . "</pre>";

// Check group with ID 1
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([1]);
$group = $stmt->fetch();

echo "<h2>Group 1:</h2>";
echo "<pre>" . print_r($group, true) . "</pre>";

// Check if challenge exists for group
if ($group && $group['challenge_id']) {
    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
    $stmt->execute([$group['challenge_id']]);
    $challenge = $stmt->fetch();

    echo "<h2>Challenge for Group 1:</h2>";
    echo "<pre>" . print_r($challenge, true) . "</pre>";
}
?>
