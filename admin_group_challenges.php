<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Load translations
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);

$errors = [];
$success = '';

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $challenge_id = sanitize($_POST['challenge_id'] ?? '');
    $action = sanitize($_POST['action'] ?? '');
    $admin_notes = sanitize($_POST['admin_notes'] ?? '');

    if (!empty($challenge_id) && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $pdo->prepare("UPDATE group_challenges SET status = ?, admin_notes = ? WHERE id = ?");
        if ($stmt->execute([$status, $admin_notes, $challenge_id])) {
            $success = "Challenge " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";

            // If approved, create the actual challenge and link it to the group
            if ($action === 'approve') {
                // Get challenge details
                $stmt = $pdo->prepare("SELECT * FROM group_challenges WHERE id = ?");
                $stmt->execute([$challenge_id]);
                $group_challenge = $stmt->fetch();

                if ($group_challenge) {
                    // Create the challenge
                    $stmt = $pdo->prepare("INSERT INTO challenges (name, description, daily_amount, max_participants, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
                    $stmt->execute([
                        $group_challenge['name'],
                        $group_challenge['description'],
                        $group_challenge['daily_amount'],
                        $group_challenge['max_participants'],
                        $group_challenge['start_date'],
                        $group_challenge['end_date'],
                        $_SESSION['user_id']
                    ]);

                    $new_challenge_id = $pdo->lastInsertId();

                    // Update group to link to the new challenge
                    $stmt = $pdo->prepare("UPDATE groups SET challenge_id = ? WHERE id = ?");
                    $stmt->execute([$new_challenge_id, $group_challenge['group_id']]);

                    // Update group challenge status
                    $stmt = $pdo->prepare("UPDATE group_challenges SET status = 'active' WHERE id = ?");
                    $stmt->execute([$challenge_id]);
                }
            }
        } else {
            $errors[] = 'Failed to update challenge status.';
        }
    }
}

// Fetch pending group challenges
$stmt = $pdo->prepare("
    SELECT gc.*, g.name as group_name, u.username as leader_name
    FROM group_challenges gc
    JOIN groups g ON gc.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gc.status = 'pending'
    ORDER BY gc.created_at DESC
");
$stmt->execute();
$pending_challenges = $stmt->fetchAll();

// Fetch approved/rejected challenges (last 50)
$stmt = $pdo->prepare("
    SELECT gc.*, g.name as group_name, u.username as leader_name
    FROM group_challenges gc
    JOIN groups g ON gc.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gc.status IN ('approved', 'rejected', 'active')
    ORDER BY gc.updated_at DESC
    LIMIT 50
");
$stmt->execute();
$processed_challenges = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Challenges - Admin - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5276;
            --secondary: #f39c12;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #27ae60;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .admin-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .admin-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .btn-approve {
            background: var(--success);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-approve:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: var(--accent);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .challenge-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }

        .challenge-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }

        .challenge-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .challenge-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .challenge-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1rem;
            color: var(--dark);
            margin-top: 5px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .status-approved {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .status-rejected {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .status-active {
            background: rgba(26, 82, 118, 0.1);
            color: #1a5276;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border-left: 4px solid var(--accent);
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border-left: 4px solid var(--success);
        }

        .modal-content {
            border: none;
            border-radius: 15px;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: var(--dark);
            transform: translateX(-5px);
        }

        .back-link i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1 class="admin-title">Group Challenges Management</h1>
            <p class="admin-subtitle">Review and approve custom challenges created by groups</p>
        </div>
    </div>

    <div class="container">
        <a href="admin.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Admin Panel
        </a>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Pending Challenges -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock me-2"></i> Pending Challenges (<?php echo count($pending_challenges); ?>)
            </div>
            <div class="card-body">
                <?php if (empty($pending_challenges)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>No pending challenges</h4>
                        <p class="text-muted">All group challenges have been reviewed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_challenges as $challenge): ?>
                        <div class="challenge-card">
                            <div class="challenge-header">
                                <h5 class="challenge-title"><?php echo htmlspecialchars($challenge['name']); ?></h5>
                                <span class="status-badge status-pending">Pending</span>
                            </div>

                            <div class="challenge-meta">
                                <strong>Group:</strong> <?php echo htmlspecialchars($challenge['group_name']); ?> |
                                <strong>Leader:</strong> <?php echo htmlspecialchars($challenge['leader_name']); ?> |
                                <strong>Created:</strong> <?php echo date('M j, Y', strtotime($challenge['created_at'])); ?>
                            </div>

                            <?php if (!empty($challenge['description'])): ?>
                                <p class="mb-3"><?php echo htmlspecialchars($challenge['description']); ?></p>
                            <?php endif; ?>

                            <div class="challenge-details">
                                <div class="detail-item">
                                    <span class="detail-label">Daily Amount</span>
                                    <span class="detail-value">TSh <?php echo number_format($challenge['daily_amount'], 0); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Max Participants</span>
                                    <span class="detail-value"><?php echo $challenge['max_participants']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Start Date</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($challenge['start_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">End Date</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($challenge['end_date'])); ?></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-approve" onclick="reviewChallenge(<?php echo $challenge['id']; ?>, 'approve')">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                                <button class="btn btn-reject" onclick="reviewChallenge(<?php echo $challenge['id']; ?>, 'reject')">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Processed Challenges -->
        <?php if (!empty($processed_challenges)): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i> Recent Challenges
            </div>
            <div class="card-body">
                <?php foreach ($processed_challenges as $challenge): ?>
                    <div class="challenge-card">
                        <div class="challenge-header">
                            <h5 class="challenge-title"><?php echo htmlspecialchars($challenge['name']); ?></h5>
                            <span class="status-badge status-<?php echo $challenge['status']; ?>">
                                <?php echo ucfirst($challenge['status']); ?>
                            </span>
                        </div>

                        <div class="challenge-meta">
                            <strong>Group:</strong> <?php echo htmlspecialchars($challenge['group_name']); ?> |
                            <strong>Leader:</strong> <?php echo htmlspecialchars($challenge['leader_name']); ?> |
                            <strong>Processed:</strong> <?php echo date('M j, Y', strtotime($challenge['updated_at'])); ?>
                        </div>

                        <?php if (!empty($challenge['admin_notes'])): ?>
                            <div class="alert alert-info mt-2 mb-0">
                                <strong>Admin Notes:</strong> <?php echo htmlspecialchars($challenge['admin_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="modal_challenge_id" name="challenge_id">
                        <input type="hidden" id="modal_action" name="action">

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add any notes for the group leader..."></textarea>
                        </div>

                        <div id="confirmation_text"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="modal_submit_btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function reviewChallenge(challengeId, action) {
            document.getElementById('modal_challenge_id').value = challengeId;
            document.getElementById('modal_action').value = action;

            const submitBtn = document.getElementById('modal_submit_btn');
            const confirmationText = document.getElementById('confirmation_text');

            if (action === 'approve') {
                submitBtn.className = 'btn btn-success';
                submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Approve Challenge';
                confirmationText.innerHTML = '<div class="alert alert-success"><i class="fas fa-info-circle me-2"></i>This will create the challenge and make it active for the group.</div>';
            } else {
                submitBtn.className = 'btn btn-danger';
                submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Reject Challenge';
                confirmationText.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>The group will be notified of the rejection.</div>';
            }

            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }
    </script>
</body>
</html>
