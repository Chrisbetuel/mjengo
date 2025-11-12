<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    // Check for new tables
    $newTables = ['groups', 'group_members'];
    echo "\nChecking for new tables:\n";
    foreach ($newTables as $table) {
        if (in_array($table, $tables)) {
            echo "- $table: EXISTS\n";
        } else {
            echo "- $table: DOES NOT EXIST\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
