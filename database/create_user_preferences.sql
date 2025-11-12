-- User preferences and customization tables

USE mjengo_challenge;

-- User preferences table for storing customization settings
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
);

-- Dashboard widgets table for storing widget configurations
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    widget_name VARCHAR(100) NOT NULL,
    widget_position INT DEFAULT 0,
    is_visible BOOLEAN DEFAULT TRUE,
    widget_settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_widget (user_id, widget_name)
);

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    frequency VARCHAR(20) DEFAULT 'daily', -- immediate, daily, weekly
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_notification (user_id, notification_type)
);

-- Insert default preferences for existing users
INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value)
SELECT id, 'theme', 'light' FROM users;

INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value)
SELECT id, 'dashboard_layout', 'default' FROM users;

INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value)
SELECT id, 'font_size', 'medium' FROM users;

-- Insert default widget configurations
INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'welcome_card', 1, TRUE FROM users u;

INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'stats_overview', 2, TRUE FROM users u;

INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'active_challenges', 3, TRUE FROM users u;

INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'group_invitations', 4, TRUE FROM users u;

INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'user_groups', 5, TRUE FROM users u;

INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible)
SELECT u.id, 'lipa_kidogo_payments', 6, TRUE FROM users u;

-- Insert default notification preferences
INSERT IGNORE INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, in_app_enabled, frequency)
SELECT u.id, 'payment_reminders', TRUE, FALSE, TRUE, 'daily' FROM users u;

INSERT IGNORE INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, in_app_enabled, frequency)
SELECT u.id, 'challenge_updates', TRUE, FALSE, TRUE, 'immediate' FROM users u;

INSERT IGNORE INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, in_app_enabled, frequency)
SELECT u.id, 'group_invitations', TRUE, FALSE, TRUE, 'immediate' FROM users u;

INSERT IGNORE INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, in_app_enabled, frequency)
SELECT u.id, 'payment_success', TRUE, FALSE, TRUE, 'immediate' FROM users u;
