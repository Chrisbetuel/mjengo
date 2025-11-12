<?php
require_once 'config.php';

try {
    $pdo->exec('CREATE TABLE groups (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        leader_id INT NOT NULL,
        challenge_id INT,
        max_members INT DEFAULT 10,
        status ENUM("active", "inactive") DEFAULT "active",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (leader_id) REFERENCES users(id),
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
    )');

    echo 'Groups table created successfully.';
} catch (Exception $e) {
    echo 'Error creating groups table: ' . $e->getMessage();
}
?>
