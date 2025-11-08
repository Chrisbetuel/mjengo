<?php
require_once 'config.php';
require_once 'core/translation.php';

// Get challenge ID
$challenge_id = $_GET['challenge_id'] ?? null;
if (!$challenge_id) {
    $_SESSION['error'] = __('invalid_challenge_id');
    redirect('challenges.php');
}

try {
    // Get challenge details
    $stmt = $pdo->prepare("SELECT id, name, description, daily_amount, max_participants, start_date, end_date, created_by, status FROM challenges WHERE id = ?");
    $stmt->execute([$challenge_id]);
    $challenge = $stmt->fetch();

    if (!$challenge) {
        $_SESSION['error'] = __('challenge_not_found');
        redirect('challenges.php');
    }

    // Get participant count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id]);
    $participant_count = $stmt->fetch()['count'];

    // Get participants list (if user is logged in and is admin or participant)
    $participants = [];
    $is_participant = false;
    $user_join_count = 0;

    if (isLoggedIn()) {
        // Check if user is a participant
        $stmt = $pdo->prepare("SELECT status FROM participants WHERE challenge_id = ? AND user_id = ?");
        $stmt->execute([$challenge_id, $_SESSION['user_id']]);
        $participant_status = $stmt->fetch();

        $is_participant = $participant_status !== false;
        $user_status = $participant_status ? $participant_status['status'] : null;

        // Count user's joins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND user_id = ?");
        $stmt->execute([$challenge_id, $_SESSION['user_id']]);
        $user_join_count = $stmt->fetch()['count'];

        // Get participants list if user is admin or participant
        if (isAdmin() || $is_participant) {
            $stmt = $pdo->prepare("
                SELECT p.id, p.user_id, p.status, p.joined_at, u.username, u.email
                FROM participants p
                JOIN users u ON p.user_id = u.id
                WHERE p.challenge_id = ?
                ORDER BY p.joined_at ASC
            ");
            $stmt->execute([$challenge_id]);
            $participants = $stmt->fetchAll();
        }
    }

    // Check if challenge is created by admin (assuming admin user_id = 1)
    $is_admin_created = ($challenge['created_by'] == 1);

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
    <title><?php echo htmlspecialchars(getChallengeName($challenge)); ?> - <?php echo SITE_NAME; ?></title>
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

        /* Animated Background */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .background-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: backgroundAnimation 24s infinite;
        }

        .slide-1 {
            background-image: url('https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            animation-delay: 0s;
        }

        .slide-2 {
            background-image: url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            animation-delay: 6s;
        }

        .slide-3 {
            background-image: url('https://images.unsplash.com/photo-1541140532154-b024d705b90a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            animation-delay: 12s;
        }

        .slide-4 {
            background-image: url('https://images.unsplash.com/photo-1582139329536-e7284fece509?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            animation-delay: 18s;
        }

        @keyframes backgroundAnimation {
            0% { opacity: 0; }
            10% { opacity: 1; }
            25% { opacity: 1; }
            35% { opacity: 0; }
            100% { opacity: 0; }
        }

        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 82, 118, 0.85);
        }

        /* Navigation */
        .navbar {
            background: rgba(44, 62, 80, 0.95) !important;
            backdrop-filter: blur(10px);
            padding: 15px 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: white !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand span {
            color: var(--secondary);
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--secondary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link.active {
            color: var(--secondary) !important;
        }

        .nav-link.active::after {
            width: 100%;
        }

        /* Page Header */
        .page-header {
            background: rgba(26, 82, 118, 0.9);
            color: white;
            padding: 100px 0 60px;
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,192C1248,192,1344,128,1392,96L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center bottom;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            animation: fadeInDown 1s ease;
        }

        .page-subtitle {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.3s both;
        }

        /* Challenge Details */
        .challenge-details-container {
            padding: 0 0 80px;
        }

        .challenge-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .challenge-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
        }

        .challenge-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .challenge-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            background: var(--secondary);
        }

        .challenge-body {
            padding: 30px;
        }

        .challenge-description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #555;
        }

        .challenge-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(26, 82, 118, 0.05);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .challenge-dates {
            background: rgba(243, 156, 18, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--secondary);
        }

        .dates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .date-item {
            text-align: center;
        }

        .date-value {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.1rem;
            display: block;
            margin-bottom: 5px;
        }

        .date-label {
            color: #666;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 82, 118, 0.4);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.6);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }

        .btn-secondary-custom {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: var(--primary);
            color: white;
        }

        /* Participants Section */
        .participants-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .participants-header {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            color: white;
            padding: 20px 30px;
            font-weight: 600;
        }

        .participants-body {
            padding: 30px;
        }

        .participant-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .participant-item:last-child {
            border-bottom: none;
        }

        .participant-info {
            display: flex;
            align-items: center;
        }

        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }

        .participant-details h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark);
        }

        .participant-details small {
            color: #666;
        }

        .participant-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent);
        }

        .status-pending {
            background: rgba(243, 156, 18, 0.1);
            color: var(--secondary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #777;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
        }

        .footer-logo span {
            color: var(--secondary);
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }

            .challenge-stats {
                grid-template-columns: 1fr;
            }

            .dates-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .participant-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="background-container">
        <div class="background-slide slide-1"></div>
        <div class="background-slide slide-2"></div>
        <div class="background-slide slide-3"></div>
        <div class="background-slide slide-4"></div>
        <div class="background-overlay"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hard-hat me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><?php echo __('home'); ?></a></li>
                    <li class="nav-item"><a class="nav-link active" href="challenges.php"><?php echo __('challenges'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="lipa_kidogo.php"><?php echo __('lipa_kidogo'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="direct_purchase.php"><?php echo __('direct_purchase'); ?></a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><?php echo __('dashboard'); ?></a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php"><?php echo __('admin'); ?></a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><?php echo __('logout'); ?></a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><?php echo __('login'); ?></a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="register.php"><?php echo __('register'); ?></a></li>
                    <?php endif; ?>
                </ul>
                <!-- Language Selector -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe me-1"></i><?php echo __('language'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                            <li>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="switch_language" value="1">
                                    <input type="hidden" name="language" value="en">
                                    <button type="submit" class="dropdown-item <?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>">
                                        <i class="fas fa-check me-2 <?php echo getCurrentLanguage() === 'en' ? '' : 'invisible'; ?>"></i>
                                        <?php echo __('english'); ?>
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="switch_language" value="1">
                                    <input type="hidden" name="language" value="sw">
                                    <button type="submit" class="dropdown-item <?php echo getCurrentLanguage() === 'sw' ? 'active' : ''; ?>">
                                        <i class="fas fa-check me-2 <?php echo getCurrentLanguage() === 'sw' ? '' : 'invisible'; ?>"></i>
                                        <?php echo __('swahili'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title"><?php echo __('challenge_details'); ?></h1>
                <p class="page-subtitle"><?php echo __('detailed_information_about_challenge'); ?></p>
            </div>
        </div>
    </section>

    <!-- Challenge Details -->
    <section class="challenge-details-container">
        <div class="container">
            <div class="challenge-card">
                <div class="challenge-header">
                    <h2 class="challenge-title"><?php echo htmlspecialchars(getChallengeName($challenge)); ?></h2>
                    <span class="challenge-status"><?php echo ucfirst($challenge['status']); ?></span>
                </div>

                <div class="challenge-body">
                    <p class="challenge-description"><?php echo htmlspecialchars(getChallengeDescription($challenge)); ?></p>

                    <div class="challenge-stats">
                        <div class="stat-card">
                            <span class="stat-value">TSh <?php echo number_format($challenge['daily_amount'], 0); ?></span>
                            <span class="stat-label"><?php echo __('daily_amount'); ?></span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value"><?php echo $participant_count; ?>/<?php echo $challenge['max_participants']; ?></span>
                            <span class="stat-label"><?php echo __('participants'); ?></span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value"><?php echo $is_admin_created ? __('public') : __('private'); ?></span>
                            <span class="stat-label"><?php echo __('challenge_type'); ?></span>
                        </div>
                    </div>

                    <div class="challenge-dates">
                        <div class="dates-grid">
                            <div class="date-item">
                                <span class="date-value"><?php echo date('F d, Y', strtotime($challenge['start_date'])); ?></span>
                                <span class="date-label"><?php echo __('start_date'); ?></span>
                            </div>
                            <div class="date-item">
                                <span class="date-value"><?php echo date('F d, Y', strtotime($challenge['end_date'])); ?></span>
                                <span class="date-label"><?php echo __('end_date'); ?></span>
                            </div>
                            <div class="date-item">
                                <span class="date-value"><?php echo date('F d, Y', strtotime($challenge['created_at'] ?? 'now')); ?></span>
                                <span class="date-label"><?php echo __('created_date'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="challenges.php" class="btn btn-secondary-custom">
                            <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_challenges'); ?>
                        </a>

                        <?php if (isLoggedIn()): ?>
                            <?php if ($is_participant): ?>
                                <a href="dashboard.php" class="btn btn-primary-custom">
                                    <i class="fas fa-tachometer-alt me-2"></i><?php echo __('view_my_progress'); ?>
                                </a>
                            <?php elseif ($user_join_count >= 3): ?>
                                <button class="btn btn-secondary-custom" disabled>
                                    <i class="fas fa-ban me-2"></i><?php echo __('max_joins_reached'); ?>
                                </button>
                            <?php elseif ($participant_count >= $challenge['max_participants']): ?>
                                <button class="btn btn-secondary-custom" disabled>
                                    <i class="fas fa-users me-2"></i><?php echo __('challenge_full'); ?>
                                </button>
                            <?php elseif (!$is_admin_created): ?>
                                <button class="btn btn-secondary-custom" disabled>
                                    <i class="fas fa-lock me-2"></i><?php echo __('invite_only'); ?>
                                </button>
                            <?php else: ?>
                                <a href="join_challenge.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-success-custom">
                                    <i class="fas fa-plus-circle me-2"></i><?php echo __('join_challenge'); ?> (<?php echo __('attempt'); ?> <?php echo $user_join_count + 1; ?>)
                                </a>
                                <a href="payment_gateway.php?type=challenge&challenge_id=<?php echo $challenge['id']; ?>&amount=<?php echo $challenge['daily_amount']; ?>" class="btn btn-primary-custom">
                                    <i class="fas fa-credit-card me-2"></i><?php echo __('pay_now'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (!$is_admin_created): ?>
                                <button class="btn btn-secondary-custom" disabled>
                                    <i class="fas fa-lock me-2"></i><?php echo __('invite_only'); ?>
                                </button>
                            <?php else: ?>
                                <a href="login.php?redirect=join_challenge&challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-success-custom">
                                    <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login_to_join'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Participants Section -->
            <?php if (!empty($participants)): ?>
                <div class="participants-section">
                    <div class="participants-header">
                        <i class="fas fa-users me-2"></i><?php echo __('participants'); ?> (<?php echo count($participants); ?>)
                    </div>
                    <div class="participants-body">
                        <?php foreach ($participants as $participant): ?>
                            <div class="participant-item">
                                <div class="participant-info">
                                    <div class="participant-avatar">
                                        <?php echo strtoupper(substr($participant['username'], 0, 1)); ?>
                                    </div>
                                    <div class="participant-details">
                                        <h6><?php echo htmlspecialchars($participant['username']); ?></h6>
                                        <small><?php echo htmlspecialchars($participant['email']); ?> • <?php echo __('joined'); ?> <?php echo date('M d, Y', strtotime($participant['joined_at'])); ?></small>
                                    </div>
                                </div>
                                <span class="participant-status status-<?php echo $participant['status']; ?>">
                                    <?php echo ucfirst($participant['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (isLoggedIn() && ($is_participant || isAdmin())): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4><?php echo __('no_participants_yet'); ?></h4>
                    <p><?php echo __('participants_will_appear_here'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo"><?php echo SITE_NAME; ?></div>
                    <p><?php echo __('trusted_partner'); ?></p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('quick_links'); ?></h5>
                        <ul>
                            <li><a href="index.php"><?php echo __('home'); ?></a></li>
                            <li><a href="challenges.php"><?php echo __('challenges'); ?></a></li>
                            <li><a href="lipa_kidogo.php"><?php echo __('lipa_kidogo'); ?></a></li>
                            <li><a href="direct_purchase.php"><?php echo __('direct_purchase'); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('account'); ?></h5>
                        <ul>
                            <li><a href="login.php"><?php echo __('login'); ?></a></li>
                            <li><a href="register.php"><?php echo __('register'); ?></a></li>
                            <li><a href="dashboard.php"><?php echo __('my_dashboard'); ?></a></li>
                            <li><a href="#"><?php echo __('payment_history'); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('contact_us'); ?></h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> <?php echo __('dar_es_salaam'); ?></li>
                            <li><i class="fas fa-phone me-2"></i> <?php echo __('phone'); ?></li>
                            <li><i class="fas fa-envelope me-2"></i> <?php echo __('email'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 <?php echo SITE_NAME; ?>. <?php echo __('all_rights_reserved'); ?></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
