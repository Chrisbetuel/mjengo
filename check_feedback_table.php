<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('DESCRIBE feedback');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Feedback table structure:\n";
    foreach($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
