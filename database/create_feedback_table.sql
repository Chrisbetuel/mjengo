USE mjengo_challenge;

CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    subject VARCHAR(255),
    name VARCHAR(100),
    email VARCHAR(100),
    message TEXT NOT NULL,
    rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
