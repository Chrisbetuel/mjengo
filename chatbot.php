<?php
require_once 'config.php';
require_once 'core/language.php';
header('Content-Type: application/json');

$lang = getCurrentLanguage();
$userMessage = trim($_POST['message'] ?? '');
$sessionId = trim($_POST['session_id'] ?? '');
$action = $_POST['action'] ?? '';

if ($action === 'feedback') {
    echo json_encode(['status' => 'success']);
    exit;
}

if ($userMessage === '') {
    echo json_encode(['reply' => 'Please type a message!', 'suggestions' => []]);
    exit;
}

$userLower = strtolower($userMessage);
$reply = '';
$suggestions = [];

// Price inquiry
if (preg_match('/\b(price|bei|cost|gharama)\b/i', $userLower)) {
    $stmt = $pdo->query("SELECT name, price, unit FROM materials LIMIT 5");
    $materials = $stmt->fetchAll();
    $reply = $lang === 'sw' ? "Bei za vifaa:\n\n" : "Material prices:\n\n";
    foreach ($materials as $m) {
        $reply .= " {$m['name']}: TSh " . number_format($m['price']) . "/{$m['unit']}\n";
    }
    $suggestions = [
        ['text' => ' Buy Now', 'icon' => 'fa-shopping-cart'],
        ['text' => ' Lipa Kidogo', 'icon' => 'fa-credit-card']
    ];
}
// Challenge inquiry
elseif (preg_match('/\b(challenge|changamoto)\b/i', $userLower)) {
    $stmt = $pdo->query("SELECT name, daily_amount FROM challenges WHERE status = 'active' LIMIT 3");
    $challenges = $stmt->fetchAll();
    $reply = $lang === 'sw' ? "Changamoto:\n\n" : "Challenges:\n\n";
    foreach ($challenges as $c) {
        $reply .= " {$c['name']} - TSh " . number_format($c['daily_amount']) . "/day\n";
    }
    $suggestions = [
        ['text' => ' Join Challenge', 'icon' => 'fa-bullseye']
    ];
}
// Greeting
elseif (preg_match('/^(hi|hello|habari)\b/i', $userLower)) {
    $reply = $lang === 'sw' 
        ? "Habari!  Karibu Mjengo Challenge. Naweza kukusaidia vipi?"
        : "Hello!  Welcome to Mjengo Challenge. How can I help?";
    $suggestions = [
        ['text' => ' Prices', 'icon' => 'fa-tag'],
        ['text' => ' Challenges', 'icon' => 'fa-bullseye'],
        ['text' => ' Contact', 'icon' => 'fa-phone']
    ];
}
// Default
else {
    $reply = $lang === 'sw'
        ? "Naweza kukusaidia na:\n Bei za vifaa\n Changamoto\n Lipa Kidogo\n\nNiambie unavyohitaji!"
        : "I can help with:\n Material prices\n Challenges\n Installments\n\nTell me what you need!";
    $suggestions = [
        ['text' => ' Prices', 'icon' => 'fa-tag'],
        ['text' => ' Challenges', 'icon' => 'fa-bullseye']
    ];
}

echo json_encode([
    'reply' => $reply,
    'suggestions' => $suggestions,
    'intent' => 'general',
    'sentiment' => 'neutral'
]);
