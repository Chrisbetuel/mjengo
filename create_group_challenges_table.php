<?php
require_once 'config.php';

try {
    $pdo->exec('CREATE TABLE group_challenges (
        id INT PRIMARY KEY AUTO_INCREMENT,
        group_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        daily_amount DECIMAL(10,2) NOT NULL,
        max_participants INT DEFAULT 90,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM("pending", "approved", "active", "inactive", "completed", "rejected") DEFAULT "pending",
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES groups(id)
    )');

    echo 'Group challenges table created successfully.';
} catch (Exception $e) {
    echo 'Error creating group challenges table: ' . $e->getMessage();
}
?>
