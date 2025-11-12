<?php
require_once 'config.php';

try {
    $pdo->exec('ALTER TABLE challenges ADD COLUMN group_id INT NULL, ADD CONSTRAINT fk_challenges_group_id FOREIGN KEY (group_id) REFERENCES groups(id)');
    echo 'Column group_id added to challenges table successfully.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
