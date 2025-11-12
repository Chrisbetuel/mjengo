<?php
// In-app notifications functions

/**
 * Get in-app notifications for user
 */
function getInAppNotifications($userId, $limit = 20, $unreadOnly = false) {
    global $pdo;

    try {
        $whereClause = "user_id = ? AND (expires_at IS NULL OR expires_at > NOW())";
        if ($unreadOnly) {
            $whereClause .= " AND is_read = FALSE";
        }

        $stmt = $pdo->prepare("
            SELECT id, title, message, notification_type, is_read, action_url, action_text, priority, created_at
            FROM in_app_notifications
            WHERE $whereClause
            ORDER BY priority DESC, created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $notifications;
    } catch (PDOException $e) {
        error_log("Error getting in-app notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM in_app_notifications
            WHERE user_id = ? AND is_read = FALSE AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return $result ? $result['count'] : 0;
    } catch (PDOException $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE in_app_notifications
            SET is_read = TRUE
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user
 */
function markAllNotificationsAsRead($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE in_app_notifications
            SET is_read = TRUE
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Create in-app notification
 */
function createInAppNotification($userId, $title, $message, $type, $actionUrl = null, $actionText = null, $priority = 'medium', $expiresAt = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO in_app_notifications (user_id, title, message, notification_type, action_url, action_text, priority, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl, $actionText, $priority, $expiresAt]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating in-app notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete expired notifications
 */
function cleanupExpiredNotifications() {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            DELETE FROM in_app_notifications
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Error cleaning up expired notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification based on user preferences
 */
function sendNotification($userId, $title, $message, $type, $actionUrl = null, $actionText = null, $priority = 'medium') {
    global $pdo;

    try {
        // Get user notification preferences
        $stmt = $pdo->prepare("
            SELECT email_enabled, sms_enabled, in_app_enabled
            FROM notification_preferences
            WHERE user_id = ? AND notification_type = ?
        ");
        $stmt->execute([$userId, $type]);
        $prefs = $stmt->fetch();

        if (!$prefs) {
            // Use defaults if no preferences set
            $prefs = ['email_enabled' => 1, 'sms_enabled' => 0, 'in_app_enabled' => 1];
        }

        $success = false;

        // Send in-app notification
        if ($prefs['in_app_enabled']) {
            createInAppNotification($userId, $title, $message, $type, $actionUrl, $actionText, $priority);
            $success = true;
        }

        // Send email notification (if enabled)
        if ($prefs['email_enabled']) {
            // Get user email
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && $user['email']) {
                sendEmailNotification($user['email'], $title, $message, $actionUrl, $actionText);
                $success = true;
            }
        }

        // SMS notifications can be added here in the future
        // if ($prefs['sms_enabled']) {
        //     sendSMSNotification($user['phone_number'], $message);
        // }

        return $success;
    } catch (PDOException $e) {
        error_log("Error sending notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification
 */
function sendEmailNotification($email, $title, $message, $actionUrl = null, $actionText = null) {
    require_once 'core/email.php';

    $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #1a5276;'>$title</h2>
            <p>$message</p>
    ";

    if ($actionUrl && $actionText) {
        $emailBody .= "
            <div style='margin: 20px 0;'>
                <a href='$actionUrl' style='background-color: #1a5276; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>$actionText</a>
            </div>
        ";
    }

    $emailBody .= "
            <p style='color: #666; font-size: 12px;'>This is an automated notification from Mjengo Challenge.</p>
        </div>
    ";

    return sendEmail($email, $title, $emailBody);
}
