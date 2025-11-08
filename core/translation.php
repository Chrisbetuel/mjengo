<?php
/**
 * Translation System for Dynamic Content
 * Handles automatic translation of challenges and materials
 */

/**
 * Translate text using LibreTranslate (free online service)
 */
function translateText($text, $targetLang = 'sw', $sourceLang = 'en') {
    if (empty($text) || $targetLang === $sourceLang) {
        return $text;
    }

    // Check if we have a cached translation
    $cacheKey = md5($text . $sourceLang . $targetLang);
    $cached = getCachedTranslation($cacheKey);
    if ($cached) {
        return $cached;
    }

    // Use MyMemory Translation API (free alternative)
    $url = 'https://api.mymemory.translated.net/get';

    $params = [
        'q' => urlencode($text),
        'langpair' => $sourceLang . '|' . $targetLang
    ];

    $url .= '?' . http_build_query($params);

    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            'method' => 'GET'
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        // Fallback to original text if translation fails
        return $text;
    }

    $response = json_decode($result, true);
    if (isset($response['responseData']['translatedText'])) {
        $translatedText = $response['responseData']['translatedText'];
        // Clean up the text by replacing plus signs with spaces
        $translatedText = str_replace('+', ' ', $translatedText);
        // Clean up URL encoding artifacts
        $translatedText = urldecode($translatedText);
        // Remove any remaining encoding artifacts
        $translatedText = preg_replace('/%[0-9A-Fa-f]{2}/', '', $translatedText);
        // Cache the translation
        cacheTranslation($cacheKey, $translatedText);
        return $translatedText;
    }

    return $text;
}

/**
 * Get cached translation from database
 */
function getCachedTranslation($cacheKey) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT translated_text FROM translations WHERE cache_key = ?");
        $stmt->execute([$cacheKey]);
        $result = $stmt->fetch();
        return $result ? $result['translated_text'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Cache translation in database
 */
function cacheTranslation($cacheKey, $translatedText) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO translations (cache_key, translated_text, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)");
        $stmt->execute([$cacheKey, $translatedText]);
    } catch (PDOException $e) {
        // Silently fail if caching fails
    }
}

/**
 * Get translated challenge name
 */
function getChallengeName($challenge) {
    $lang = getCurrentLanguage();
    if ($lang === 'sw') {
        if (!empty($challenge['sw_name'])) {
            return $challenge['sw_name'];
        } else {
            // Auto-translate and cache
            $translated = translateText($challenge['name']);
            if ($translated !== $challenge['name']) {
                updateChallengeTranslation($challenge['id'], $translated, null);
            }
            return $translated;
        }
    }
    return $challenge['name'];
}

/**
 * Get translated challenge description
 */
function getChallengeDescription($challenge) {
    $lang = getCurrentLanguage();
    if ($lang === 'sw') {
        if (!empty($challenge['sw_description'])) {
            return $challenge['sw_description'];
        } else {
            // Auto-translate and cache
            $translated = translateText($challenge['description']);
            if ($translated !== $challenge['description']) {
                updateChallengeTranslation($challenge['id'], null, $translated);
            }
            return $translated;
        }
    }
    return $challenge['description'];
}

/**
 * Get translated material name
 */
function getMaterialName($material) {
    $lang = getCurrentLanguage();
    if ($lang === 'sw') {
        if (!empty($material['sw_name'])) {
            return $material['sw_name'];
        } else {
            // Auto-translate and cache
            $translated = translateText($material['name']);
            if ($translated !== $material['name']) {
                updateMaterialTranslation($material['id'], $translated, null);
            }
            return $translated;
        }
    }
    return $material['name'];
}

/**
 * Get translated material description
 */
function getMaterialDescription($material) {
    $lang = getCurrentLanguage();
    if ($lang === 'sw') {
        if (!empty($material['sw_description'])) {
            return $material['sw_description'];
        } else {
            // Auto-translate and cache
            $translated = translateText($material['description']);
            if ($translated !== $material['description']) {
                updateMaterialTranslation($material['id'], null, $translated);
            }
            return $translated;
        }
    }
    return $material['description'];
}

/**
 * Update challenge translation in database
 */
function updateChallengeTranslation($challengeId, $swName = null, $swDescription = null) {
    global $pdo;
    try {
        $updates = [];
        $params = [];

        if ($swName !== null) {
            $updates[] = "sw_name = ?";
            $params[] = $swName;
        }

        if ($swDescription !== null) {
            $updates[] = "sw_description = ?";
            $params[] = $swDescription;
        }

        if (!empty($updates)) {
            $params[] = $challengeId;
            $stmt = $pdo->prepare("UPDATE challenges SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Update material translation in database
 */
function updateMaterialTranslation($materialId, $swName = null, $swDescription = null) {
    global $pdo;
    try {
        $updates = [];
        $params = [];

        if ($swName !== null) {
            $updates[] = "sw_name = ?";
            $params[] = $swName;
        }

        if ($swDescription !== null) {
            $updates[] = "sw_description = ?";
            $params[] = $swDescription;
        }

        if (!empty($updates)) {
            $params[] = $materialId;
            $stmt = $pdo->prepare("UPDATE materials SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}
