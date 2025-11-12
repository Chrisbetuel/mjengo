<?php
// Configuration file
define('BASE_URL', 'http://localhost/mjengo');
define('SITE_NAME', 'Mjengo Challenge');

// Theme constants
define('AVAILABLE_THEMES', ['light', 'dark']);
define('AVAILABLE_FONT_SIZES', ['small', 'medium', 'large']);
define('DEFAULT_THEME', 'light');
define('DEFAULT_FONT_SIZE', 'medium');

// Session configuration
session_start();

// Include database connection
require_once 'core/db.php';

// Include preferences system
require_once 'core/preferences.php';

// Include language system
require_once 'core/language.php';

// Include notifications system
require_once 'core/notifications.php';

// Handle language switching
handleLanguageSwitch();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function autoLoginAdmin() {
    global $pdo;
    if (!isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
        }
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
