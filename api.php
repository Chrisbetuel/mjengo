<?php
// Mjengo Challenge API
// RESTful API endpoints for external integrations

require_once 'config.php';

// Set headers for API responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Remove query string and base path
$request = str_replace(BASE_URL . '/api.php', '', $request);
$request = parse_url($request, PHP_URL_PATH);
$request = trim($request, '/');

// Split the request into parts
$parts = explode('/', $request);
$endpoint = $parts[0] ?? '';
$id = $parts[1] ?? null;

// Handle case where endpoint is 'mjengo' (when accessed via full URL)
if ($endpoint === 'mjengo') {
    $endpoint = $parts[2] ?? '';
    $id = $parts[3] ?? null;
}

// Authentication middleware
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendResponse(401, ['error' => 'Authentication required']);
    }

    $token = $matches[1];

    // Verify token (implement your token verification logic here)
    // For now, we'll use a simple token check
    if ($token !== 'your-secret-api-token') {
        sendResponse(401, ['error' => 'Invalid token']);
    }
}

function requireAdminAuth() {
    if (!isLoggedIn() || !isAdmin()) {
        sendResponse(403, ['error' => 'Admin access required']);
    }
}

// Helper function to send JSON responses
function sendResponse($statusCode = 200, $data = [], $message = '') {
    http_response_code($statusCode);

    $response = ['status' => $statusCode];

    if (!empty($message)) {
        $response['message'] = $message;
    }

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit();
}

// Helper function to get JSON input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

// API Routes
try {
    switch ($endpoint) {
        case 'challenges':
            handleChallenges($method, $id);
            break;

        case 'materials':
            handleMaterials($method, $id);
            break;

        case 'users':
            handleUsers($method, $id);
            break;

        case 'payments':
            handlePayments($method, $id);
            break;

        case 'feedback':
            handleFeedback($method, $id);
            break;

        case 'stats':
            handleStats($method);
            break;

        case 'group-members':
            handleGroupMembers($method, $id);
            break;

        default:
            sendResponse(404, ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    sendResponse(500, ['error' => 'Internal server error']);
}

// Challenges endpoints
function handleChallenges($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific challenge
                $stmt = $pdo->prepare("
                    SELECT c.*, u.username as creator_name,
                           COUNT(p.id) as participant_count
                    FROM challenges c
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN participants p ON c.id = p.challenge_id
                    WHERE c.id = ? AND c.status = 'active'
                    GROUP BY c.id
                ");
                $stmt->execute([$id]);
                $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$challenge) {
                    sendResponse(404, ['error' => 'Challenge not found']);
                }

                sendResponse(200, $challenge);
            } else {
                // Get all active challenges
                $stmt = $pdo->query("
                    SELECT c.*, u.username as creator_name,
                           COUNT(p.id) as participant_count
                    FROM challenges c
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN participants p ON c.id = p.challenge_id
                    WHERE c.status = 'active'
                    GROUP BY c.id
                    ORDER BY c.created_at DESC
                ");
                $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $challenges);
            }
            break;

        case 'POST':
            requireAdminAuth();

            $data = getJsonInput();

            // Validate required fields
            $required = ['name', 'description', 'daily_amount', 'max_participants', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    sendResponse(400, ['error' => "Missing required field: $field"]);
                }
            }

            // Validate dates
            if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
                sendResponse(400, ['error' => 'End date must be after start date']);
            }

            $stmt = $pdo->prepare("
                INSERT INTO challenges (name, description, daily_amount, max_participants, start_date, end_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['description']),
                floatval($data['daily_amount']),
                intval($data['max_participants']),
                $data['start_date'],
                $data['end_date'],
                $_SESSION['user_id']
            ]);

            $challengeId = $pdo->lastInsertId();
            sendResponse(201, ['id' => $challengeId], 'Challenge created successfully');
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

// Materials endpoints
function handleMaterials($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific material
                $stmt = $pdo->prepare("
                    SELECT m.*, u.username as creator_name
                    FROM materials m
                    LEFT JOIN users u ON m.created_by = u.id
                    WHERE m.id = ? AND m.status = 'active'
                ");
                $stmt->execute([$id]);
                $material = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$material) {
                    sendResponse(404, ['error' => 'Material not found']);
                }

                sendResponse(200, $material);
            } else {
                // Get all active materials
                $stmt = $pdo->query("
                    SELECT m.*, u.username as creator_name
                    FROM materials m
                    LEFT JOIN users u ON m.created_by = u.id
                    WHERE m.status = 'active'
                    ORDER BY m.created_at DESC
                ");
                $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $materials);
            }
            break;

        case 'POST':
            requireAdminAuth();

            $data = getJsonInput();

            // Validate required fields
            $required = ['name', 'description', 'price'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    sendResponse(400, ['error' => "Missing required field: $field"]);
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO materials (name, description, price, created_by)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['description']),
                floatval($data['price']),
                $_SESSION['user_id']
            ]);

            $materialId = $pdo->lastInsertId();
            sendResponse(201, ['id' => $materialId], 'Material added successfully');
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

// Users endpoints
function handleUsers($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            requireAdminAuth();

            if ($id) {
                // Get specific user
                $stmt = $pdo->prepare("
                    SELECT id, username, email, phone_number, nida_id, role, created_at
                    FROM users WHERE id = ?
                ");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    sendResponse(404, ['error' => 'User not found']);
                }

                sendResponse(200, $user);
            } else {
                // Get all users
                $stmt = $pdo->query("
                    SELECT id, username, email, phone_number, nida_id, role, created_at
                    FROM users ORDER BY created_at DESC
                ");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $users);
            }
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

// Payments endpoints
function handlePayments($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            requireAuth();

            if ($id) {
                // Get specific payment
                $stmt = $pdo->prepare("
                    SELECT p.*, pt.user_id, pt.challenge_id, c.name as challenge_name
                    FROM payments p
                    JOIN participants pt ON p.participant_id = pt.id
                    JOIN challenges c ON pt.challenge_id = c.id
                    WHERE p.id = ? AND pt.user_id = ?
                ");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$payment) {
                    sendResponse(404, ['error' => 'Payment not found']);
                }

                sendResponse(200, $payment);
            } else {
                // Get user's payments
                $stmt = $pdo->prepare("
                    SELECT p.*, c.name as challenge_name, p.payment_date, p.amount, p.status
                    FROM payments p
                    JOIN participants pt ON p.participant_id = pt.id
                    JOIN challenges c ON pt.challenge_id = c.id
                    WHERE pt.user_id = ?
                    ORDER BY p.payment_date DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $payments);
            }
            break;

        case 'POST':
            requireAuth();

            $data = getJsonInput();

            // Validate required fields
            if (!isset($data['participant_id']) || !isset($data['amount'])) {
                sendResponse(400, ['error' => 'Missing required fields: participant_id, amount']);
            }

            // Verify the participant belongs to the user
            $stmt = $pdo->prepare("SELECT id FROM participants WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['participant_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                sendResponse(403, ['error' => 'Unauthorized access to participant']);
            }

            $stmt = $pdo->prepare("
                INSERT INTO payments (participant_id, amount, payment_date, status)
                VALUES (?, ?, CURDATE(), 'pending')
            ");

            $stmt->execute([
                intval($data['participant_id']),
                floatval($data['amount'])
            ]);

            $paymentId = $pdo->lastInsertId();
            sendResponse(201, ['id' => $paymentId], 'Payment recorded successfully');
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

// Feedback endpoints
function handleFeedback($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific feedback
                $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
                $stmt->execute([$id]);
                $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$feedback) {
                    sendResponse(404, ['error' => 'Feedback not found']);
                }

                sendResponse(200, $feedback);
            } else {
                // Get all feedback (admin only) or user's feedback
                if (isAdmin()) {
                    $stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC");
                } else {
                    requireAuth();
                    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $feedback);
            }
            break;

        case 'POST':
            $data = getJsonInput();

            // Validate required fields
            $required = ['name', 'message', 'rating'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    sendResponse(400, ['error' => "Missing required field: $field"]);
                }
            }

            // Validate rating
            $rating = intval($data['rating']);
            if ($rating < 1 || $rating > 5) {
                sendResponse(400, ['error' => 'Rating must be between 1 and 5']);
            }

            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

            $stmt = $pdo->prepare("
                INSERT INTO feedback (user_id, name, email, message, rating)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                sanitize($data['name']),
                sanitize($data['email'] ?? null),
                sanitize($data['message']),
                $rating
            ]);

            $feedbackId = $pdo->lastInsertId();
            sendResponse(201, ['id' => $feedbackId], 'Feedback submitted successfully');
            break;

        case 'PUT':
            requireAdminAuth();

            if (!$id) {
                sendResponse(400, ['error' => 'Feedback ID required']);
            }

            $data = getJsonInput();

            if (!isset($data['reply']) || empty($data['reply'])) {
                sendResponse(400, ['error' => 'Reply content required']);
            }

            $stmt = $pdo->prepare("UPDATE feedback SET reply = ? WHERE id = ?");
            $stmt->execute([sanitize($data['reply']), $id]);

            sendResponse(200, [], 'Reply added successfully');
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

// Statistics endpoints
function handleStats($method) {
    global $pdo;

    if ($method !== 'GET') {
        sendResponse(405, ['error' => 'Method not allowed']);
    }

    requireAdminAuth();

    $stats = [];

    // General statistics
    $stats['users'] = [
        'total' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
        'active_today' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM payments WHERE DATE(created_at) = CURDATE()")->fetchColumn()
    ];

    $stats['challenges'] = [
        'total' => $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM challenges WHERE status = 'active'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM challenges WHERE status = 'completed'")->fetchColumn()
    ];

    $stats['payments'] = [
        'total_amount' => $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?? 0,
        'today_amount' => $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn() ?? 0,
        'pending_count' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn()
    ];

    $stats['materials'] = [
        'total' => $pdo->query("SELECT COUNT(*) FROM materials WHERE status = 'active'")->fetchColumn(),
        'total_value' => $pdo->query("SELECT SUM(price) FROM materials WHERE status = 'active'")->fetchColumn() ?? 0
    ];

    sendResponse(200, $stats);
}

// Group Members endpoints
function handleGroupMembers($method, $id) {
    global $pdo;

    switch ($method) {
        case 'GET':
            requireAdminAuth();

            if ($id) {
                // Get specific member
                $stmt = $pdo->prepare("
                    SELECT gm.*, u.username, u.email, g.name as group_name
                    FROM group_members gm
                    JOIN users u ON gm.user_id = u.id
                    JOIN groups g ON gm.group_id = g.id
                    WHERE gm.id = ?
                ");
                $stmt->execute([$id]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$member) {
                    sendResponse(404, ['error' => 'Member not found']);
                }

                sendResponse(200, $member);
            } else {
                // Get members by group_id or all members
                $groupId = $_GET['group_id'] ?? null;

                if ($groupId) {
                    $stmt = $pdo->prepare("
                        SELECT gm.*, u.username, u.email, u.phone_number
                        FROM group_members gm
                        JOIN users u ON gm.user_id = u.id
                        WHERE gm.group_id = ?
                        ORDER BY gm.joined_at DESC
                    ");
                    $stmt->execute([$groupId]);
                } else {
                    $stmt = $pdo->query("
                        SELECT gm.*, u.username, u.email, u.phone_number, g.name as group_name
                        FROM group_members gm
                        JOIN users u ON gm.user_id = u.id
                        JOIN groups g ON gm.group_id = g.id
                        ORDER BY gm.joined_at DESC
                    ");
                }

                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(200, $members);
            }
            break;

        case 'PUT':
            requireAdminAuth();

            if (!$id) {
                sendResponse(400, ['error' => 'Member ID required']);
            }

            $data = getJsonInput();

            if (!isset($data['status']) || empty($data['status'])) {
                sendResponse(400, ['error' => 'Status is required']);
            }

            $validStatuses = ['active', 'inactive', 'pending', 'suspended'];
            if (!in_array($data['status'], $validStatuses)) {
                sendResponse(400, ['error' => 'Invalid status value']);
            }

            $stmt = $pdo->prepare("UPDATE group_members SET status = ? WHERE id = ?");
            $stmt->execute([sanitize($data['status']), $id]);

            if ($stmt->rowCount() > 0) {
                sendResponse(200, [], 'Member status updated successfully');
            } else {
                sendResponse(404, ['error' => 'Member not found']);
            }
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}
?>
