-- Database schema for Mjengo Challenge

CREATE DATABASE IF NOT EXISTS mjengo_challenge;
USE mjengo_challenge;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    phone_number VARCHAR(20) NOT NULL,
    nida_id VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Challenges table
CREATE TABLE challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_amount DECIMAL(10,2) NOT NULL,
    max_participants INT DEFAULT 90,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Participants table
CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    queue_position INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (challenge_id) REFERENCES challenges(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_participant (challenge_id, user_id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    status ENUM('paid', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id)
);

-- Daily winners table
CREATE TABLE daily_winners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    participant_id INT NOT NULL,
    win_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id),
    FOREIGN KEY (participant_id) REFERENCES participants(id),
    UNIQUE KEY unique_win (challenge_id, win_date)
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, phone_number, nida_id, password, role) VALUES ('admin', 'admin@mjengo.com', '+255123456789', '1234567890123456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Lipa Kidogo Kidogo tables
CREATE TABLE materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE lipa_kidogo_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- Direct purchases table
CREATE TABLE direct_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- Insert sample materials
INSERT INTO materials (name, description, price, created_by) VALUES
('Cement - 50kg Bag', 'High quality cement for construction', 1200.00, 1),
('Steel Reinforcement Bars - 12mm', '12mm diameter steel bars, 6m length', 850.00, 1),
('Sand - 1 Cubic Meter', 'Clean building sand for concrete mixing', 2500.00, 1),
('Gravel - 1 Cubic Meter', 'Construction gravel for concrete', 1800.00, 1),
('Bricks - 1000 pieces', 'Standard red clay bricks', 15000.00, 1);
