-- Add user_type and payment_duration columns to lipa_kidogo table
-- First, create the lipa_kidogo table if it doesn't exist

CREATE TABLE IF NOT EXISTS lipa_kidogo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    installment_amount DECIMAL(10,2) NOT NULL,
    num_installments INT NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- Add the new columns
ALTER TABLE lipa_kidogo ADD COLUMN user_type ENUM('businessman', 'employed') DEFAULT NULL;
ALTER TABLE lipa_kidogo ADD COLUMN payment_duration ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily';
