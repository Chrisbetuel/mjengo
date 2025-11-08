<?php
require_once '../core/db.php';

try {
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    if (in_array('payments', $tables)) {
        echo "\nPayments table exists!\n";
    } else {
        echo "\nPayments table does NOT exist!\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
