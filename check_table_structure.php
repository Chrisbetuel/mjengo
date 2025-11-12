<?php
require_once 'config.php';

try {
    echo "Checking group_members table structure:\n";
    $stmt = $pdo->query('DESCRIBE group_members');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Key'] ? $col['Key'] : ' ') . "\n";
    }

    echo "\nChecking groups table structure:\n";
    $stmt = $pdo->query('DESCRIBE groups');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Key'] ? $col['Key'] : ' ') . "\n";
    }

    echo "\nChecking group_registrations table structure:\n";
    $stmt = $pdo->query('DESCRIBE group_registrations');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Key'] ? $col['Key'] : ' ') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
