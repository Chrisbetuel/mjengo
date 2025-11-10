<?php
require_once 'config.php';

try {
    // Create lipa_kidogo table
    $sql = "
        CREATE TABLE IF NOT EXISTS lipa_kidogo (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            material_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            installment_amount DECIMAL(10,2) NOT NULL,
            num_installments INT NOT NULL,
            start_date DATE NOT NULL,
            user_type ENUM('businessman', 'employed') DEFAULT NULL,
            payment_duration ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (material_id) REFERENCES materials(id)
        )
    ";

    $pdo->exec($sql);
    echo "Lipa Kidogo table created successfully!\n";

    // Create lipa_kidogo_installments table if it doesn't exist
    $sql2 = "
        CREATE TABLE IF NOT EXISTS lipa_kidogo_installments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lipa_kidogo_id INT NOT NULL,
            user_id INT NOT NULL,
            material_id INT NOT NULL,
            installment_number INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            due_date DATE NOT NULL,
            status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lipa_kidogo_id) REFERENCES lipa_kidogo(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (material_id) REFERENCES materials(id)
        )
    ";

    $pdo->exec($sql2);
    echo "Lipa Kidogo installments table created successfully!\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
