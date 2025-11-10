<?php
require_once 'core/db.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM materials');
    $count = $stmt->fetchColumn();
    echo 'Materials count: ' . $count . PHP_EOL;

    if ($count > 0) {
        $stmt = $pdo->query('SELECT * FROM materials ORDER BY created_at DESC');
        $materials = $stmt->fetchAll();
        echo 'Materials:' . PHP_EOL;
        foreach ($materials as $material) {
            echo '- ' . $material['name'] . ' (' . $material['price'] . ' TSh)' . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
