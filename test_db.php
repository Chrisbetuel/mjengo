<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();
    echo 'Database connection successful. Users count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM challenges WHERE status = "active"');
    $result = $stmt->fetch();
    echo 'Active challenges count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM groups');
    $result = $stmt->fetch();
    echo 'Groups count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM group_members');
    $result = $stmt->fetch();
    echo 'Group members count: ' . $result['count'] . PHP_EOL;
} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage() . PHP_EOL;
}
?>
