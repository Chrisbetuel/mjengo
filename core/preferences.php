<?php
// User preferences and customization functions

/**
 * Get user preference value
 */
function getUserPreference($userId, $key, $default = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = ?");
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetch();

        return $result ? $result['preference_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error getting user preference: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set user preference value
 */
function setUserPreference($userId, $key, $value) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $key, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("Error setting user preference: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all user preferences
 */
function getAllUserPreferences($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $preferences;
    } catch (PDOException $e) {
        error_log("Error getting all user preferences: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dashboard widgets configuration
 */
function getDashboardWidgets($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT widget_name, widget_position, is_visible, widget_settings
            FROM dashboard_widgets
            WHERE user_id = ?
            ORDER BY widget_position ASC
        ");
        $stmt->execute([$userId]);
        $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $widgets;
    } catch (PDOException $e) {
        error_log("Error getting dashboard widgets: " . $e->getMessage());
        return [];
    }
}

/**
 * Update dashboard widget configuration
 */
function updateDashboardWidget($userId, $widgetName, $position, $isVisible, $settings = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO dashboard_widgets (user_id, widget_name, widget_position, is_visible, widget_settings)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                widget_position = VALUES(widget_position),
                is_visible = VALUES(is_visible),
                widget_settings = VALUES(widget_settings),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $widgetName, $position, $isVisible, $settings ? json_encode($settings) : null]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating dashboard widget: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notification preferences
 */
function getNotificationPreferences($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT notification_type, email_enabled, sms_enabled, in_app_enabled, frequency
            FROM notification_preferences
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $preferences;
    } catch (PDOException $e) {
        error_log("Error getting notification preferences: " . $e->getMessage());
        return [];
    }
}

/**
 * Update notification preference
 */
function updateNotificationPreference($userId, $type, $email, $sms, $inApp, $frequency) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, in_app_enabled, frequency)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email_enabled = VALUES(email_enabled),
                sms_enabled = VALUES(sms_enabled),
                in_app_enabled = VALUES(in_app_enabled),
                frequency = VALUES(frequency),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $type, $email, $sms, $inApp, $frequency]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating notification preference: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current theme for user
 */
function getCurrentTheme($userId) {
    return getUserPreference($userId, 'theme', 'light');
}

/**
 * Get current font size for user
 */
function getCurrentFontSize($userId) {
    return getUserPreference($userId, 'font_size', 'medium');
}

/**
 * Get dashboard layout preference
 */
function getDashboardLayout($userId) {
    return getUserPreference($userId, 'dashboard_layout', 'default');
}

/**
 * Initialize default preferences for new user
 */
function initializeUserPreferences($userId) {
    $defaults = [
        'theme' => 'light',
        'font_size' => 'medium',
        'dashboard_layout' => 'default',
        'language' => 'en'
    ];

    foreach ($defaults as $key => $value) {
        setUserPreference($userId, $key, $value);
    }

    // Initialize default widgets
    $defaultWidgets = [
        ['name' => 'welcome_card', 'position' => 1],
        ['name' => 'stats_overview', 'position' => 2],
        ['name' => 'active_challenges', 'position' => 3],
        ['name' => 'group_invitations', 'position' => 4],
        ['name' => 'user_groups', 'position' => 5],
        ['name' => 'lipa_kidogo_payments', 'position' => 6]
    ];

    foreach ($defaultWidgets as $widget) {
        updateDashboardWidget($userId, $widget['name'], $widget['position'], true);
    }

    // Initialize notification preferences
    $notificationTypes = ['payment_reminders', 'challenge_updates', 'group_invitations', 'payment_success'];
    foreach ($notificationTypes as $type) {
        updateNotificationPreference($userId, $type, true, false, true, $type === 'payment_reminders' ? 'daily' : 'immediate');
    }
}

/**
 * Apply theme CSS variables
 */
function applyThemeVariables($theme) {
    $themes = [
        'light' => [
            '--primary' => '#1a5276',
            '--secondary' => '#f39c12',
            '--accent' => '#e74c3c',
            '--light' => '#ecf0f1',
            '--dark' => '#2c3e50',
            '--success' => '#27ae60',
            '--bg-color' => '#ffffff',
            '--text-color' => '#2c3e50',
            '--card-bg' => '#ffffff',
            '--navbar-bg' => 'rgba(44, 62, 80, 0.95)'
        ],
        'dark' => [
            '--primary' => '#3498db',
            '--secondary' => '#f39c12',
            '--accent' => '#e74c3c',
            '--light' => '#34495e',
            '--dark' => '#ecf0f1',
            '--success' => '#27ae60',
            '--bg-color' => '#2c3e50',
            '--text-color' => '#ecf0f1',
            '--card-bg' => '#34495e',
            '--navbar-bg' => 'rgba(44, 62, 80, 0.98)'
        ]
    ];

    return $themes[$theme] ?? $themes['light'];
}
?>
