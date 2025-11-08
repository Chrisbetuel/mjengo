<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=join_challenge&challenge_id=' . ($_GET['challenge_id'] ?? ''));
}

// Get challenge ID
$challenge_id = $_GET['challenge_id'] ?? null;
if (!$challenge_id) {
    $_SESSION['error'] = __('invalid_challenge_id');
    redirect('challenges.php');
}

try {
    // Get challenge details
    $stmt = $pdo->prepare("SELECT id, name, description, daily_amount, max_participants, start_date, end_date, created_by, status FROM challenges WHERE id = ? AND status = 'active'");
    $stmt->execute([$challenge_id]);
    $challenge = $stmt->fetch();

    if (!$challenge) {
        $_SESSION['error'] = __('challenge_not_found');
        redirect('challenges.php');
    }

    // Check if challenge is created by admin (assuming admin user_id = 1)
    $is_admin_created = ($challenge['created_by'] == 1);
    if (!$is_admin_created) {
        $_SESSION['error'] = __('invite_only_challenge');
        redirect('challenges.php');
    }

    // Check how many times user has joined this challenge
    $stmt = $pdo->prepare("SELECT COUNT(*) as join_count FROM participants WHERE challenge_id = ? AND user_id = ?");
    $stmt->execute([$challenge_id, $_SESSION['user_id']]);
    $join_count = $stmt->fetch()['join_count'];

    if ($join_count >= 3) {
        $_SESSION['error'] = __('max_joins_reached');
        redirect('challenges.php');
    }

    // Check current participant count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id]);
    $current_participants = $stmt->fetch()['count'];

    if ($current_participants >= $challenge['max_participants']) {
        $_SESSION['error'] = __('challenge_full');
        redirect('challenges.php');
    }

    // Check if user is already active in this challenge
    $stmt = $pdo->prepare("SELECT id FROM participants WHERE challenge_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id, $_SESSION['user_id']]);
    $existing_participant = $stmt->fetch();

    if ($existing_participant) {
        $_SESSION['error'] = __('already_joined_challenge');
        redirect('challenges.php');
    }

    // Process join request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Insert new participant
        $stmt = $pdo->prepare("INSERT INTO participants (challenge_id, user_id, status, joined_at) VALUES (?, ?, 'active', NOW())");
        $stmt->execute([$challenge_id, $_SESSION['user_id']]);

        $_SESSION['success'] = __('successfully_joined_challenge');
        redirect('dashboard.php');
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = __('database_error');
    redirect('challenges.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('join_challenge'); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            overflow-x: hidden;
        }

        .join-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .join-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .join-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .join-header h2 {
            margin-bottom: 10px;
            font-weight: 700;
        }

        .join-body {
            padding: 30px;
        }

        .challenge-summary {
            background: rgba(26, 82, 118, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }

        .challenge-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .challenge-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            text-align: center;
        }

        .detail-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
        }

        .join-benefits {
            background: rgba(39, 174, 96, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--success);
        }

        .benefits-title {
            font-weight: 600;
            color: var(--success);
            margin-bottom: 15px;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
        }

        .benefits-list li {
            padding: 5px 0;
            color: #555;
        }

        .benefits-list li i {
            color: var(--success);
            margin-right: 10px;
        }

        .join-terms {
            background: rgba(243, 156, 18, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--secondary);
        }

        .terms-title {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 15px;
        }

        .terms-list {
            list-style: none;
            padding: 0;
        }

        .terms-list li {
            padding: 5px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .terms-list li i {
            color: var(--secondary);
            margin-right: 10px;
        }

        .btn-join-confirm {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-join-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }

        .btn-cancel {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: var(--primary);
            color: white;
        }

        .attempt-notice {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .attempt-notice i {
            color: var(--accent);
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-card">
            <div class="join-header">
                <i class="fas fa-plus-circle fa-3x mb-3"></i>
                <h2><?php echo __('join_challenge'); ?></h2>
                <p><?php echo __('join_challenge_description'); ?></p>
            </div>

            <div class="join-body">
                <?php if ($join_count > 0): ?>
                    <div class="attempt-notice">
                        <i class="fas fa-info-circle"></i>
                        <strong><?php echo __('attempt'); ?> <?php echo $join_count + 1; ?> <?php echo __('of'); ?> 3</strong>
                        <br>
                        <small><?php echo __('you_have_joined_before'); ?></small>
                    </div>
                <?php endif; ?>

                <div class="challenge-summary">
                    <h5 class="challenge-title"><?php echo htmlspecialchars(getChallengeName($challenge)); ?></h5>
                    <p><?php echo htmlspecialchars(getChallengeDescription($challenge)); ?></p>

                    <div class="challenge-details">
                        <div class="detail-item">
                            <span class="detail-value">TSh <?php echo number_format($challenge['daily_amount'], 0); ?></span>
                            <span class="detail-label"><?php echo __('daily_amount'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-value"><?php echo $current_participants; ?>/<?php echo $challenge['max_participants']; ?></span>
                            <span class="detail-label"><?php echo __('participants'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></span>
                            <span class="detail-label"><?php echo __('starts'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></span>
                            <span class="detail-label"><?php echo __('ends'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="join-benefits">
                    <h6 class="benefits-title">
                        <i class="fas fa-check-circle me-2"></i><?php echo __('what_you_get'); ?>
                    </h6>
                    <ul class="benefits-list">
                        <li><i class="fas fa-check"></i><?php echo __('daily_savings_structure'); ?></li>
                        <li><i class="fas fa-check"></i><?php echo __('community_support'); ?></li>
                        <li><i class="fas fa-check"></i><?php echo __('progress_tracking'); ?></li>
                        <li><i class="fas fa-check"></i><?php echo __('achievement_badges'); ?></li>
                        <li><i class="fas fa-check"></i><?php echo __('flexible_payment_options'); ?></li>
                    </ul>
                </div>

                <div class="join-terms">
                    <h6 class="terms-title">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('important_terms'); ?>
                    </h6>
                    <ul class="terms-list">
                        <li><i class="fas fa-clock"></i><?php echo __('commitment_required'); ?></li>
                        <li><i class="fas fa-money-bill-wave"></i><?php echo __('daily_payments_mandatory'); ?></li>
                        <li><i class="fas fa-calendar-alt"></i><?php echo __('challenge_duration'); ?></li>
                        <li><i class="fas fa-handshake"></i><?php echo __('terms_agreement'); ?></li>
                    </ul>
                </div>

                <form method="POST">
                    <button type="submit" class="btn-join-confirm">
                        <i class="fas fa-plus-circle me-2"></i><?php echo __('confirm_join_challenge'); ?>
                    </button>
                </form>

                <a href="challenges.php" class="btn-cancel">
                    <i class="fas fa-arrow-left me-2"></i><?php echo __('cancel_go_back'); ?>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
