<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'core/translation.php';

// Test feedback management functionality
echo "<h1>Feedback Management Validation Test</h1>";

// Test 1: Check if feedback table exists and has required columns
echo "<h2>Test 1: Database Structure Validation</h2>";
try {
    $stmt = $pdo->query("DESCRIBE feedback");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $required_columns = ['id', 'user_id', 'subject', 'message', 'reply', 'created_at'];
    $existing_columns = array_column($columns, 'Field');

    echo "<p>Feedback table columns: " . implode(', ', $existing_columns) . "</p>";

    $missing_columns = array_diff($required_columns, $existing_columns);
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✓ All required columns exist</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missing_columns) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking feedback table: " . $e->getMessage() . "</p>";
}

// Test 2: Check feedback data
echo "<h2>Test 2: Feedback Data Validation</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM feedback");
    $stmt->execute();
    $total_feedback = $stmt->fetch()['total'];

    echo "<p>Total feedback entries: $total_feedback</p>";

    if ($total_feedback > 0) {
        $stmt = $pdo->prepare("
            SELECT f.*, u.username
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $feedbacks = $stmt->fetchAll();

        echo "<h3>Recent Feedback Entries:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Subject</th><th>Message</th><th>Reply</th><th>Created</th></tr>";

        foreach ($feedbacks as $feedback) {
            $reply_status = $feedback['reply'] ? 'Replied' : 'Pending';
            $reply_color = $feedback['reply'] ? 'green' : 'orange';

            echo "<tr>";
            echo "<td>{$feedback['id']}</td>";
            echo "<td>" . htmlspecialchars($feedback['username'] ?? 'Anonymous') . "</td>";
            echo "<td>" . htmlspecialchars(substr($feedback['subject'], 0, 30)) . "</td>";
            echo "<td>" . htmlspecialchars(substr($feedback['message'], 0, 50)) . "...</td>";
            echo "<td style='color: $reply_color;'>$reply_status</td>";
            echo "<td>" . date('M d, Y H:i', strtotime($feedback['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error fetching feedback data: " . $e->getMessage() . "</p>";
}

// Test 3: Test feedback deletion functionality
echo "<h2>Test 3: Feedback Deletion Test</h2>";
echo "<p><strong>Note:</strong> This test will create a temporary feedback entry and then delete it to validate the delete functionality.</p>";

try {
    // Create a test feedback entry
    $stmt = $pdo->prepare("INSERT INTO feedback (user_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([1, 'Test Feedback for Validation', 'This is a test feedback entry created for validation purposes.', ]);

    $test_feedback_id = $pdo->lastInsertId();
    echo "<p style='color: blue;'>✓ Created test feedback entry with ID: $test_feedback_id</p>";

    // Verify it exists
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
    $stmt->execute([$test_feedback_id]);
    $test_feedback = $stmt->fetch();

    if ($test_feedback) {
        echo "<p style='color: green;'>✓ Test feedback entry exists</p>";

        // Test deletion
        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->execute([$test_feedback_id]);

        // Verify deletion
        $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
        $stmt->execute([$test_feedback_id]);
        $deleted_feedback = $stmt->fetch();

        if (!$deleted_feedback) {
            echo "<p style='color: green;'>✓ Test feedback entry successfully deleted</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to delete test feedback entry</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Test feedback entry was not created properly</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing feedback deletion: " . $e->getMessage() . "</p>";
}

// Test 4: Test feedback reply functionality
echo "<h2>Test 4: Feedback Reply Test</h2>";
echo "<p><strong>Note:</strong> This test will create a temporary feedback entry, add a reply, and then delete it.</p>";

try {
    // Create another test feedback entry
    $stmt = $pdo->prepare("INSERT INTO feedback (user_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([1, 'Test Feedback for Reply Validation', 'This is a test feedback entry for reply validation.']);

    $test_feedback_id = $pdo->lastInsertId();
    echo "<p style='color: blue;'>✓ Created test feedback entry with ID: $test_feedback_id</p>";

    // Add a reply
    $test_reply = "This is a test reply to validate the reply functionality.";
    $stmt = $pdo->prepare("UPDATE feedback SET reply = ? WHERE id = ?");
    $stmt->execute([$test_reply, $test_feedback_id]);

    // Verify reply was added
    $stmt = $pdo->prepare("SELECT reply FROM feedback WHERE id = ?");
    $stmt->execute([$test_feedback_id]);
    $replied_feedback = $stmt->fetch();

    if ($replied_feedback && $replied_feedback['reply'] === $test_reply) {
        echo "<p style='color: green;'>✓ Reply successfully added to feedback</p>";
        echo "<p>Reply content: " . htmlspecialchars($replied_feedback['reply']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to add reply to feedback</p>";
    }

    // Clean up - delete the test entry
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([$test_feedback_id]);
    echo "<p style='color: blue;'>✓ Cleaned up test feedback entry</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing feedback reply: " . $e->getMessage() . "</p>";
}

// Test 5: Validate admin.php feedback management functions
echo "<h2>Test 5: Admin Panel Feedback Functions Validation</h2>";
echo "<p>Checking if required JavaScript functions are properly implemented in admin.php...</p>";

// Read admin.php to check for function implementations
$admin_content = file_get_contents('admin.php');

$required_functions = [
    'viewFeedback',
    'replyFeedback',
    'deleteFeedback'
];

$function_status = [];
foreach ($required_functions as $function) {
    if (strpos($admin_content, "function $function(") !== false) {
        $function_status[$function] = true;
    } else {
        $function_status[$function] = false;
    }
}

echo "<ul>";
foreach ($function_status as $function => $exists) {
    $status = $exists ? "<span style='color: green;'>✓ Exists</span>" : "<span style='color: red;'>✗ Missing</span>";
    echo "<li>$function: $status</li>";
}
echo "</ul>";

if (array_sum($function_status) === count($required_functions)) {
    echo "<p style='color: green;'>✓ All required JavaScript functions are implemented</p>";
} else {
    echo "<p style='color: red;'>✗ Some JavaScript functions are missing</p>";
}

// Test 6: Check modal implementations
echo "<h2>Test 6: Modal Implementation Check</h2>";
$required_modals = [
    'viewFeedbackModal',
    'replyFeedbackModal'
];

$modal_status = [];
foreach ($required_modals as $modal) {
    if (strpos($admin_content, "id=\"$modal\"") !== false) {
        $modal_status[$modal] = true;
    } else {
        $modal_status[$modal] = false;
    }
}

echo "<ul>";
foreach ($modal_status as $modal => $exists) {
    $status = $exists ? "<span style='color: green;'>✓ Exists</span>" : "<span style='color: red;'>✗ Missing</span>";
    echo "<li>$modal: $status</li>";
}
echo "</ul>";

if (array_sum($modal_status) === count($required_modals)) {
    echo "<p style='color: green;'>✓ All required modals are implemented</p>";
} else {
    echo "<p style='color: red;'>✗ Some modals are missing</p>";
}

echo "<hr>";
echo "<h2>Validation Summary</h2>";
echo "<p>Feedback management functionality has been validated. Check the results above for any issues.</p>";
echo "<p><a href='admin.php'>← Back to Admin Panel</a></p>";
?>
