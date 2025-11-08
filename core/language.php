<?php
/**
 * Language/Translation System
 * Handles loading and displaying text in different languages
 */

// Default language
define('DEFAULT_LANGUAGE', 'en');

// Available languages
define('AVAILABLE_LANGUAGES', ['en' => 'English', 'sw' => 'Swahili']);

/**
 * Get current language from session or default
 */
function getCurrentLanguage() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

/**
 * Set current language in session
 */
function setCurrentLanguage($lang) {
    if (array_key_exists($lang, AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}

/**
 * Load language file
 */
function loadLanguage($lang = null) {
    if (!$lang) {
        $lang = getCurrentLanguage();
    }

    $langFile = __DIR__ . '/../languages/' . $lang . '.php';

    if (file_exists($langFile)) {
        return include $langFile;
    }

    // Fallback to default language
    $defaultLangFile = __DIR__ . '/../languages/' . DEFAULT_LANGUAGE . '.php';
    if (file_exists($defaultLangFile)) {
        return include $defaultLangFile;
    }

    return [];
}

/**
 * Get translated text
 */
function __($key, $lang = null) {
    static $translations = null;

    if ($translations === null) {
        $translations = loadLanguage($lang);
    }

    return $translations[$key] ?? $key;
}

/**
 * Get translated text with sprintf formatting
 */
function __f($key, ...$args) {
    $text = __($key);
    return sprintf($text, ...$args);
}

/**
 * Handle language switching
 */
function handleLanguageSwitch() {
    if (isset($_POST['switch_language']) && isset($_POST['language'])) {
        $newLang = $_POST['language'];
        if (setCurrentLanguage($newLang)) {
            // Redirect to refresh the page with new language
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
