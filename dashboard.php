<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Load translations
$translations = loadLanguage();

$user_id = $_SESSION['user_id'];

// Get unread notification count for navbar
$unread_notifications = getUnreadNotificationCount($user_id);

// Get recent notifications for display
$recent_notifications = getInAppNotifications($user_id, 5, false);

// Get user preferences for customization
$current_theme = getCurrentTheme($user_id);
$current_font_size = getCurrentFontSize($user_id);
$dashboard_widgets = getDashboardWidgets($user_id);

// Convert widgets to associative array for easier access
$widget_settings = [];
foreach ($dashboard_widgets as $widget) {
    $widget_settings[$widget['widget_name']] = $widget;
}

// Apply theme variables
$theme_vars = applyThemeVariables($current_theme);

// Define available dashboard widgets
$available_widgets = [
    'active_challenges' => [
        'title' => 'Your Active Challenges',
        'icon' => 'fas fa-tasks',
        'description' => 'View and manage your active challenges'
    ],
    'terminated_challenges' => [
        'title' => 'Terminated Challenges',
        'icon' => 'fas fa-ban',
        'description' => 'View challenges that were terminated'
    ],
    'group_invitations' => [
        'title' => 'Group Invitations',
        'icon' => 'fas fa-envelope',
        'description' => 'Manage your group invitations'
    ],
    'user_groups' => [
        'title' => 'Your Groups',
        'icon' => 'fas fa-users',
        'description' => 'View and manage your groups'
    ],
    'payment_plans' => [
        'title' => 'Your Payment Plans',
        'icon' => 'fas fa-credit-card',
        'description' => 'Track your installment payments'
    ]
];

// Get enabled widgets ordered by display_order
$enabled_widgets = array_filter($widget_settings, function($widget) {
    return $widget['is_visible'] == 1;
});

// Sort by display_order
usort($enabled_widgets, function($a, $b) {
    $a_order = $a['display_order'] ?? 0;
    $b_order = $b['display_order'] ?? 0;
    return $a_order <=> $b_order;
});

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user ? $user['username'] : 'User';
$email = $user ? $user['email'] : '';
$join_date = $user ? date('M d, Y', strtotime($user['created_at'])) : '';

// Fetch user's challenges (including terminated ones) with join attempt info
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.description, c.daily_amount, c.start_date, c.end_date, p.queue_position, p.joined_at, p.status as participant_status, p.join_attempt
    FROM challenges c
    JOIN participants p ON c.id = p.challenge_id
    WHERE p.user_id = ?
    ORDER BY p.joined_at DESC
");
$stmt->execute([$user_id]);
$user_challenges = $stmt->fetchAll();

// Fetch user's group invitations
$stmt = $pdo->prepare("
    SELECT gm.id, g.name as group_name, g.description, u.username as leader_name, gm.joined_at as invited_at
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gm.user_id = ? AND gm.status = 'invited'
    ORDER BY gm.joined_at DESC
");
$stmt->execute([$user_id]);
$group_invitations = $stmt->fetchAll();

// Fetch user's groups (where they are members)
$stmt = $pdo->prepare("
    SELECT g.id, g.name as group_name, g.description, g.status as group_status, u.username as leader_name, gm.joined_at, gm.status as member_status, g.leader_id
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gm.user_id = ? AND gm.status IN ('active', 'accepted')
    ORDER BY gm.joined_at DESC
");
$stmt->execute([$user_id]);
$user_groups = $stmt->fetchAll();

// Separate active and terminated challenges
$active_challenges = array_filter($user_challenges, function($c) { return $c['participant_status'] == 'active'; });
$terminated_challenges = array_filter($user_challenges, function($c) { return $c['participant_status'] == 'inactive'; });

// Fetch payment progress for each challenge
$challenge_progress = [];
foreach ($user_challenges as $challenge) {
    $challenge_id = $challenge['id'];

    // Get total participants
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM participants WHERE challenge_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id]);
    $total_participants = $stmt->fetch()['total'];

    // Get payments for today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as paid_today
        FROM payments p
        JOIN participants part ON p.participant_id = part.id
        WHERE part.challenge_id = ? AND p.payment_date = CURDATE() AND p.status = 'paid'
    ");
    $stmt->execute([$challenge_id]);
    $paid_today = $stmt->fetch()['paid_today'];

    // Calculate days remaining
    $today = new DateTime();
    $end_date = new DateTime($challenge['end_date']);
    $days_remaining = $today->diff($end_date)->days;

    $challenge_progress[$challenge_id] = [
        'total_participants' => $total_participants,
        'paid_today' => $paid_today,
        'payment_percentage' => $total_participants > 0 ? round(($paid_today / $total_participants) * 100, 1) : 0,
        'days_remaining' => $days_remaining
    ];
}

// Fetch Lipa Kidogo Kidogo payments
$stmt = $pdo->prepare("
    SELECT lkp.id, lkp.material_id, lkp.amount, lkp.payment_date, lkp.status, m.name as material_name, m.price as material_price
    FROM lipa_kidogo_payments lkp
    JOIN materials m ON lkp.material_id = m.id
    WHERE lkp.user_id = ?
    ORDER BY lkp.payment_date ASC
");
$stmt->execute([$user_id]);
$lipa_kidogo_payments = $stmt->fetchAll();

// Calculate payment statistics
$total_pending = 0;
$total_paid = 0;
$overdue_count = 0;
$today = date('Y-m-d');

foreach ($lipa_kidogo_payments as $payment) {
    if ($payment['status'] == 'paid') {
        $total_paid += $payment['amount'];
    } elseif ($payment['status'] == 'pending') {
        $total_pending += $payment['amount'];
        if ($payment['payment_date'] < $today) {
            $overdue_count++;
        }
    }
}

// Calculate total materials value
$total_material_value = 0;
$unique_materials = [];
foreach ($lipa_kidogo_payments as $payment) {
    if (!in_array($payment['material_id'], $unique_materials)) {
        $unique_materials[] = $payment['material_id'];
        $total_material_value += $payment['material_price'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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

        .notification-badge {
            background: var(--accent);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            text-align: center;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 100px 0 50px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stat-card.success {
            border-left-color: var(--success);
        }
        
        .stat-card.warning {
            border-left-color: var(--secondary);
        }
        
        .stat-card.danger {
            border-left-color: var(--accent);
        }
        
        .stat-card.info {
            border-left-color: var(--primary);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        /* Challenge Cards */
        .challenge-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            border: none;
        }
        
        .challenge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .challenge-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .challenge-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }
        
        .challenge-title {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        
        .challenge-body {
            padding: 25px;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .progress {
            height: 12px;
            border-radius: 10px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--success), #2ecc71);
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }
        
        /* Table Styling */
        .payment-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-action {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        /* Animations */
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
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 0.9rem;
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
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hard-hat me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="challenges.php">Challenges</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_group.php">Register Group</a></li>
                    <li class="nav-item"><a class="nav-link" href="#groups">My Groups</a></li>
                    <li class="nav-item"><a class="nav-link" href="lipa_kidogo.php">Lipa Kidogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="direct_purchase.php">Direct Purchase</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_notifications.php">
                            <i class="fas fa-bell me-1"></i><?php echo __('notifications'); ?>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="dashboard_settings.php"><i class="fas fa-cog me-1"></i>Settings</a></li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
                    <?php endif; ?>
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe me-1"></i> <?php echo AVAILABLE_LANGUAGES[getCurrentLanguage()]; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                            <?php foreach (AVAILABLE_LANGUAGES as $code => $name): ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="switch_language" value="1">
                                        <input type="hidden" name="language" value="<?php echo $code; ?>">
                                        <button type="submit" class="dropdown-item <?php echo $code === getCurrentLanguage() ? 'active' : ''; ?>">
                                            <?php echo $name; ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-card animate-on-scroll">
                <div class="welcome-content">
                    <h1 class="welcome-title"><?php echo __('welcome_back'); ?>, <?php echo htmlspecialchars($username); ?>! 👋</h1>
                    <p class="welcome-subtitle"><?php echo __('heres_your_overview'); ?></p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($email); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><i class="fas fa-calendar me-2"></i> <?php echo __('member_since'); ?> <?php echo $join_date; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><i class="fas fa-tasks me-2"></i> <?php echo count($user_challenges); ?> <?php echo __('active_challenges_count'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card success animate-on-scroll">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value text-success">TSh <?php echo number_format($total_paid, 2); ?></div>
                    <div class="stat-label"><?php echo __('total_paid'); ?></div>
                </div>

                <div class="stat-card warning animate-on-scroll">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value text-warning">TSh <?php echo number_format($total_pending, 2); ?></div>
                    <div class="stat-label"><?php echo __('pending_payments'); ?></div>
                </div>

                <div class="stat-card danger animate-on-scroll">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value text-danger"><?php echo $overdue_count; ?></div>
                    <div class="stat-label"><?php echo __('overdue_payments'); ?></div>
                </div>

                <div class="stat-card info animate-on-scroll">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-value text-primary">TSh <?php echo number_format($total_material_value, 2); ?></div>
                    <div class="stat-label"><?php echo __('materials_value'); ?></div>
                </div>
            </div>

            <!-- Active Challenges Section -->
            <?php if (isset($enabled_widgets['active_challenges'])): ?>
                <h2 class="mb-4 animate-on-scroll">Your Active Challenges</h2>

                <?php if (empty($active_challenges)): ?>
                    <div class="empty-state animate-on-scroll">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>No Active Challenges</h3>
                        <p class="mb-4">You haven't joined any challenges yet. Start your building journey today!</p>
                        <a href="challenges.php" class="btn btn-primary btn-lg">Browse Challenges</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($active_challenges as $challenge): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="challenge-card animate-on-scroll">
                                    <div class="challenge-header">
                                        <h5 class="challenge-title"><?php echo htmlspecialchars($challenge['name']); ?> (Join Challenge Times <?php echo $challenge['join_attempt']; ?>)</h5>
                                        <p class="mb-0 opacity-75">Queue Position: #<?php echo $challenge['queue_position']; ?></p>
                                    </div>
                                        <div class="challenge-body">
                                        <p class="card-text"><?php echo htmlspecialchars($challenge['description']); ?></p>

                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <p class="mb-2"><i class="fas fa-money-bill-wave me-2 text-success"></i><strong>Daily:</strong> TSh <?php echo number_format($challenge['daily_amount'], 2); ?></p>
                                                <p class="mb-2"><i class="fas fa-calendar-day me-2 text-primary"></i><strong>Started:</strong> <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></p>
                                            </div>
                                            <div class="col-sm-6">
                                                <p class="mb-2"><i class="fas fa-flag-checkered me-2 text-warning"></i><strong>Ends:</strong> <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></p>
                                                <p class="mb-2"><i class="fas fa-clock me-2 text-info"></i><strong>Days Left:</strong> <?php echo $challenge_progress[$challenge['id']]['days_remaining']; ?></p>
                                            </div>
                                        </div>

                                        <?php $progress = $challenge_progress[$challenge['id']]; ?>
                                        <div class="progress-container">
                                            <h6>Today's Payment Progress</h6>
                                            <div class="progress mb-2">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: <?php echo $progress['payment_percentage']; ?>%"
                                                     aria-valuenow="<?php echo $progress['payment_percentage']; ?>"
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted"><?php echo $progress['paid_today']; ?>/<?php echo $progress['total_participants']; ?> paid today</small>
                                                <small class="text-muted"><?php echo $progress['payment_percentage']; ?>%</small>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <a href="payment_gateway.php?type=challenge&challenge_id=<?php echo $challenge['id']; ?>&amount=<?php echo $challenge['daily_amount']; ?>" class="btn btn-success btn-action flex-fill">
                                                <i class="fas fa-credit-card me-2"></i> Pay Now
                                            </a>
                                            <button class="btn btn-primary btn-action flex-fill" onclick="viewDetails(<?php echo $challenge['id']; ?>)">
                                                <i class="fas fa-eye me-2"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Terminated Challenges Section -->
            <?php if (!empty($terminated_challenges)): ?>
                <h2 class="mb-4 animate-on-scroll text-danger">Terminated Challenges</h2>
                <div class="alert alert-danger animate-on-scroll">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> These challenges were terminated due to overdue payments (more than 3 days without payment).
                </div>
                <div class="row">
                    <?php foreach ($terminated_challenges as $challenge): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="challenge-card animate-on-scroll terminated-card">
                                <div class="challenge-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                                    <h5 class="challenge-title"><?php echo htmlspecialchars($challenge['name']); ?> (Join Challenge Times <?php echo $challenge['join_attempt']; ?>) <i class="fas fa-ban ms-2"></i></h5>
                                    <p class="mb-0 opacity-75">TERMINATED - Queue Position: #<?php echo $challenge['queue_position']; ?></p>
                                </div>
                                <div class="challenge-body">
                                    <p class="card-text"><?php echo htmlspecialchars($challenge['description']); ?></p>

                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Termination Reason:</strong> Overdue payments for more than 3 days.
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <p class="mb-2"><i class="fas fa-money-bill-wave me-2 text-success"></i><strong>Daily:</strong> TSh <?php echo number_format($challenge['daily_amount'], 2); ?></p>
                                            <p class="mb-2"><i class="fas fa-calendar-day me-2 text-primary"></i><strong>Started:</strong> <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p class="mb-2"><i class="fas fa-flag-checkered me-2 text-warning"></i><strong>Ended:</strong> <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></p>
                                            <p class="mb-2"><i class="fas fa-times-circle me-2 text-danger"></i><strong>Status:</strong> Terminated</p>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button class="btn btn-danger btn-action" disabled>
                                            <i class="fas fa-ban me-2"></i> Challenge Terminated
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Group Invitations Section -->
            <?php if (!empty($group_invitations)): ?>
                <h2 class="mb-4 animate-on-scroll">Group Invitations</h2>
                <div class="row">
                    <?php foreach ($group_invitations as $invitation): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="challenge-card animate-on-scroll">
                                <div class="challenge-header" style="background: linear-gradient(135deg, var(--secondary), #e67e22);">
                                    <h5 class="challenge-title"><?php echo htmlspecialchars($invitation['group_name']); ?> <i class="fas fa-envelope ms-2"></i></h5>
                                    <p class="mb-0 opacity-75">Invited by <?php echo htmlspecialchars($invitation['leader_name']); ?></p>
                                </div>
                                <div class="challenge-body">
                                    <p class="card-text"><?php echo htmlspecialchars($invitation['description']); ?></p>
                                    <p class="mb-3"><i class="fas fa-calendar-alt me-2 text-info"></i><strong>Invited:</strong> <?php echo date('M d, Y', strtotime($invitation['invited_at'])); ?></p>

                                    <div class="d-flex gap-2 mt-3">
                                        <a href="accept_invitation.php?id=<?php echo $invitation['id']; ?>" class="btn btn-success btn-action flex-fill">
                                            <i class="fas fa-check me-2"></i> View Invitation
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Your Groups Section -->
            <h2 id="groups" class="mb-4 animate-on-scroll">Your Groups</h2>

            <?php if (empty($user_groups)): ?>
                <div class="empty-state animate-on-scroll">
                    <div class="empty-state-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>No Groups Yet</h3>
                    <p class="mb-4">You haven't joined any groups yet. Create or join a group to start collaborating!</p>
                    <a href="register_group.php" class="btn btn-primary btn-lg">Create a Group</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($user_groups as $group): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="challenge-card animate-on-scroll">
                                <div class="challenge-header">
                                    <h5 class="challenge-title"><?php echo htmlspecialchars($group['group_name']); ?> <i class="fas fa-users ms-2"></i></h5>
                                    <p class="mb-0 opacity-75">Led by <?php echo htmlspecialchars($group['leader_name']); ?></p>
                                </div>
                                <div class="challenge-body">
                                    <p class="card-text"><?php echo htmlspecialchars($group['description']); ?></p>
                                    <p class="mb-2"><i class="fas fa-calendar-check me-2 text-success"></i><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($group['joined_at'])); ?></p>
                                    <p class="mb-3"><i class="fas fa-info-circle me-2 text-info"></i><strong>Status:</strong>
                                        <span class="badge bg-<?php echo $group['member_status'] == 'accepted' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($group['member_status']); ?>
                                        </span>
                                    </p>

                                    <div class="d-flex gap-2 mt-3">
                                        <?php if ($group['leader_id'] == $_SESSION['user_id']): ?>
                                            <a href="group_management.php?group_id=<?php echo $group['id']; ?>" class="btn btn-primary btn-action flex-fill">
                                                <i class="fas fa-cog me-2"></i> Manage Group
                                            </a>
                                            <a href="group_management.php?group_id=<?php echo $group['id']; ?>" class="btn btn-info btn-action flex-fill">
                                                <i class="fas fa-eye me-2"></i> View Details
                                            </a>
                                        <?php else: ?>
                                            <a href="group_management.php?group_id=<?php echo $group['id']; ?>" class="btn btn-info btn-action flex-fill">
                                                <i class="fas fa-eye me-2"></i> View Group Details
                                            </a>
                                            <div class="flex-fill"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Lipa Kidogo Kidogo Section -->
            <div class="mt-5">
                <h2 class="mb-4 animate-on-scroll">Your Payment Plans</h2>

                <?php if (!empty($lipa_kidogo_payments)): ?>
                    <div class="payment-table animate-on-scroll">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lipa_kidogo_payments as $payment): ?>
                                        <tr class="<?php echo ($payment['payment_date'] < $today && $payment['status'] == 'pending') ? 'table-warning' : ''; ?>">
                                            <td>
                                                <i class="fas fa-cube me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($payment['material_name']); ?>
                                            </td>
                                            <td class="fw-bold">TSh <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                                <?php if ($payment['payment_date'] < $today && $payment['status'] == 'pending'): ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> Overdue</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $payment['status'] == 'paid' ? 'success' :
                                                         ($payment['status'] == 'overdue' ? 'danger' : 'warning');
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $payment['status'] == 'paid' ? 'check' :
                                                             ($payment['status'] == 'overdue' ? 'exclamation-triangle' : 'clock');
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] == 'pending'): ?>
                                                    <a href="payment_gateway.php?type=lipa_kidogo_installment&installment_id=<?php echo $payment['id']; ?>&amount=<?php echo $payment['amount']; ?>" class="btn btn-success btn-sm btn-action">
                                                        <i class="fas fa-credit-card me-1"></i> Pay Now
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-check-circle text-success"></i> Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state animate-on-scroll">
                        <div class="empty-state-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3>No Payment Plans</h3>
                        <p class="mb-4">You haven't started any payment plans yet. Explore our flexible payment options!</p>
                        <a href="lipa_kidogo.php" class="btn btn-success me-2">Lipa Kidogo</a>
                        <a href="direct_purchase.php" class="btn btn-primary">Direct Purchase</a>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4 animate-on-scroll">
                    <a href="lipa_kidogo.php" class="btn btn-success btn-lg me-3">
                        <i class="fas fa-shopping-cart me-2"></i> Browse Materials
                    </a>
                    <a href="challenges.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-tasks me-2"></i> Browse Challenges
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Challenge Details -->
    <div class="modal fade" id="challengeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Challenge Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="challengeDetails">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('animated');
                        }, index * 200);
                    }
                });
            }, { threshold: 0.1 });
            
            animatedElements.forEach(element => {
                observer.observe(element);
            });
            
            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.background = 'rgba(44, 62, 80, 0.98)';
                    navbar.style.padding = '10px 0';
                } else {
                    navbar.style.background = 'rgba(44, 62, 80, 0.95)';
                    navbar.style.padding = '15px 0';
                }
            });
        });

        function viewDetails(challengeId) {
            // This would load challenge details via AJAX
            alert('Challenge details would be loaded here (implementation pending)');
        }

        function viewGroupDetails(groupId) {
            // This would load group details via AJAX
            alert('Group details would be loaded here (implementation pending)');
        }
    </script>
</body>
</html>