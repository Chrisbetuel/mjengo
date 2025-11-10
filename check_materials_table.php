<?php
require_once 'core/db.php';

try {
    $stmt = $pdo->query('DESCRIBE materials');
    $columns = $stmt->fetchAll();

    echo "Materials table structure:\n";
    foreach($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }

    echo "\nSample materials:\n";
    $stmt = $pdo->query('SELECT id, name, description, price, status FROM materials LIMIT 5');
    $materials = $stmt->fetchAll();

    if (empty($materials)) {
        echo "No materials found in database.\n";
    } else {
        foreach($materials as $material) {
            echo "ID: {$material['id']}, Name: {$material['name']}, Price: {$material['price']}, Status: {$material['status']}\n";
        }
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
