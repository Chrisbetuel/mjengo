<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM feedback');
    $count = $stmt->fetchColumn();
    echo "Total feedback entries: $count\n";

    if ($count > 0) {
        $stmt = $pdo->query('SELECT id, subject, name, email, message, rating, reply, created_at FROM feedback ORDER BY created_at DESC LIMIT 5');
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nRecent feedback:\n";
        foreach ($feedbacks as $feedback) {
            $reply_status = $feedback['reply'] ? 'Replied' : 'Pending';
            echo "- ID: {$feedback['id']}, Subject: {$feedback['subject']}, Name: {$feedback['name']}, Reply: $reply_status\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
