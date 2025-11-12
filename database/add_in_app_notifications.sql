-- Add in-app notifications table

USE mjengo_challenge;

-- In-app notifications table
CREATE TABLE IF NOT EXISTS in_app_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    action_text VARCHAR(100),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_type_created (notification_type, created_at)
);

-- Insert sample notifications for testing
INSERT INTO in_app_notifications (user_id, title, message, notification_type, action_url, action_text, priority) VALUES
(1, 'Welcome to Mjengo Challenge!', 'Your account has been successfully created. Start by joining a challenge or browsing materials.', 'system', 'challenges.php', 'Browse Challenges', 'high'),
(1, 'Payment Reminder', 'Your next payment for Challenge #1 is due in 2 days. Don\'t forget to make your payment on time.', 'payment_reminders', 'payment_gateway.php?type=challenge', 'Make Payment', 'high'),
(1, 'Challenge Update', 'New participant joined your challenge! You now have 15 active members.', 'challenge_updates', 'challenge_details.php?id=1', 'View Details', 'medium'),
(1, 'Group Invitation', 'You have been invited to join "Building Group Alpha". Click to accept or decline.', 'group_invitations', 'dashboard.php', 'View Invitation', 'medium'),
(1, 'Payment Successful', 'Your payment of TZS 5,000 has been processed successfully. Thank you!', 'payment_success', NULL, NULL, 'low');
