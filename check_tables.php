<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    if (in_array('lipa_kidogo', $tables)) {
        echo "\nLipa Kidogo table exists!\n";
        // Check columns
        $stmt = $pdo->query('DESCRIBE lipa_kidogo');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns in lipa_kidogo:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "\nLipa Kidogo table does NOT exist!\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
