<?php
require_once 'config.php';

try {
    // Drop the incorrect foreign key
    $pdo->exec('ALTER TABLE group_members DROP FOREIGN KEY group_members_ibfk_1');

    // Add the correct foreign key
    $pdo->exec('ALTER TABLE group_members ADD CONSTRAINT group_members_ibfk_1 FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE');

    echo 'Foreign key updated successfully.';
} catch (Exception $e) {
    echo 'Error updating foreign key: ' . $e->getMessage();
}
?>
