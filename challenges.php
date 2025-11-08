<?php
require_once 'config.php';
require_once 'core/translation.php';



// Fetch active challenges from database
try {
    $stmt = $pdo->prepare("SELECT id, name, description, daily_amount, max_participants, start_date, end_date, created_by, status FROM challenges WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    $challenges = $stmt->fetchAll();

    // Get participant counts for each challenge
    $participant_counts = [];
    foreach ($challenges as $challenge) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND status = 'active'");
        $stmt->execute([$challenge['id']]);
        $participant_counts[$challenge['id']] = $stmt->fetch()['count'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $challenges = [];
    $participant_counts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenges - <?php echo SITE_NAME; ?></title>
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
            margin: 0 auto 30px;
            animation: fadeInUp 1s ease 0.3s both;
        }

        .page-actions {
            animation: fadeInUp 1s ease 0.5s both;
        }

        .btn-header {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-header:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        /* Challenge Cards */
        .challenges-container {
            padding: 0 0 80px;
        }
        
        .challenge-card {
            transition: all 0.4s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
            background: white;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
        }
        
        .challenge-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .challenge-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .challenge-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 20px;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .challenge-card .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }
        
        .challenge-card .card-title {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }
        
        .challenge-card .card-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
        }
        
        .challenge-card .card-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .challenge-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
            padding: 0 10px;
        }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #777;
            display: block;
        }
        
        .progress-container {
            margin-bottom: 20px;
        }
        
        .progress {
            height: 10px;
            border-radius: 10px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--success), #2ecc71);
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #777;
            margin-top: 5px;
        }
        
        .challenge-dates {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .date-item {
            text-align: center;
            flex: 1;
        }
        
        .date-label {
            display: block;
            font-size: 0.8rem;
            color: #777;
            margin-bottom: 5px;
        }
        
        .date-value {
            display: block;
            font-weight: 600;
            color: var(--dark);
        }
        
        .btn-join {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
            width: 100%;
        }
        
        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.6);
        }
        
        .btn-disabled {
            background: #95a5a6;
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
        }
        
        .btn-login {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            background: var(--primary);
            color: white;
        }
        
        .challenge-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--secondary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        /* Role Selection Cards */
        .role-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .role-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }
        
        .role-card.border-primary {
            border-color: var(--primary) !important;
            background-color: rgba(26, 82, 118, 0.05);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 50px 0;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #777;
            max-width: 500px;
            margin: 0 auto 30px;
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
        
        .footer-links h5 {
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        .footer-links h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--secondary);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #bbb;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--secondary);
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            margin-top: 20px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bbb;
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
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .page-subtitle {
                font-size: 1.1rem;
            }
            
            .challenge-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .challenge-dates {
                flex-direction: column;
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
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="register.php">Register</a></li>
                        <li class="nav-item"><a class="nav-link" href="register_group.php">Register Group</a></li>
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
                <h1 class="page-title"><?php echo __('building_challenges'); ?></h1>
                <p class="page-subtitle"><?php echo __('challenges_subtitle'); ?></p>
                <div class="page-actions">
                    <a href="register_group.php" class="btn-header me-3">
                        <i class="fas fa-users me-2"></i><?php echo __('register_group'); ?>
                    </a>
                    <a href="register.php" class="btn-header">
                        <i class="fas fa-user-plus me-2"></i><?php echo __('register'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Challenges Section -->
    <section class="challenges-container">
        <div class="container">
            <?php if (empty($challenges)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3><?php echo __('no_active_challenges'); ?></h3>
                    <p><?php echo __('no_active_challenges_desc'); ?></p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <?php if (isLoggedIn() && isAdmin()): ?>
                            <a href="#create-challenge" class="btn btn-success btn-lg">
                                <i class="fas fa-plus-circle me-2"></i><?php echo __('create_challenge'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="register_group.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-users me-2"></i><?php echo __('register_group'); ?>
                        </a>
                        <a href="index.php" class="btn btn-secondary btn-lg"><?php echo __('back_to_home'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($challenges as $challenge): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card challenge-card h-100">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo htmlspecialchars(getChallengeName($challenge)); ?></h5>
                                    <div class="challenge-badge"><?php echo __('active'); ?></div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text"><?php echo htmlspecialchars(getChallengeDescription($challenge)); ?></p>

                                    <div class="challenge-stats">
                                        <div class="stat-item">
                                            <span class="stat-value">TSh <?php echo number_format($challenge['daily_amount'], 0); ?></span>
                                            <span class="stat-label"><?php echo __('daily_amount'); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-value"><?php echo $participant_counts[$challenge['id']] ?? 0; ?>/<?php echo $challenge['max_participants']; ?></span>
                                            <span class="stat-label"><?php echo __('participants'); ?></span>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress-container">
                                        <?php
                                        $current_participants = $participant_counts[$challenge['id']] ?? 0;
                                        $percentage = $challenge['max_participants'] > 0 ? ($current_participants / $challenge['max_participants']) * 100 : 0;
                                        ?>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: <?php echo $percentage; ?>%"
                                                 aria-valuenow="<?php echo $percentage; ?>"
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="progress-text">
                                            <span><?php echo __('progress'); ?></span>
                                            <span><?php echo round($percentage, 1); ?>%</span>
                                        </div>
                                    </div>

                                    <div class="challenge-dates">
                                        <div class="date-item">
                                            <span class="date-label"><?php echo __('starts'); ?></span>
                                            <span class="date-value"><?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></span>
                                        </div>
                                        <div class="date-item">
                                            <span class="date-label"><?php echo __('ends'); ?></span>
                                            <span class="date-value"><?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></span>
                                        </div>
                                    </div>

                                    <?php if (isLoggedIn()): ?>
                                        <?php
                                        // Check how many times user has joined this challenge (active or inactive)
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as join_count FROM participants WHERE challenge_id = ? AND user_id = ?");
                                        $stmt->execute([$challenge['id'], $_SESSION['user_id']]);
                                        $join_count = $stmt->fetch()['join_count'];

                                        // Check if challenge is created by admin (assuming admin user_id = 1)
                                        $is_admin_created = ($challenge['created_by'] == 1);
                                        ?>

                                        <?php if ($join_count >= 3): ?>
                                            <div class="d-grid gap-2">
                                                <button class="btn-disabled" disabled>
                                                    <i class="fas fa-ban me-2"></i> Max Joins Reached
                                                </button>
                                                <a href="challenge_details.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-info">
                                                    <i class="fas fa-eye me-2"></i> Details
                                                </a>
                                            </div>
                                        <?php elseif ($current_participants >= $challenge['max_participants']): ?>
                                            <div class="d-grid gap-2">
                                                <button class="btn-disabled" disabled>
                                                    <i class="fas fa-users me-2"></i> Challenge Full
                                                </button>
                                                <a href="challenge_details.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-info">
                                                    <i class="fas fa-eye me-2"></i> Details
                                                </a>
                                            </div>
                                        <?php elseif (!$is_admin_created): ?>
                                            <!-- User-created challenges: no join option, only view details -->
                                            <div class="d-grid gap-2">
                                                <button class="btn-disabled" disabled>
                                                    <i class="fas fa-lock me-2"></i> Invite Only
                                                </button>
                                                <a href="challenge_details.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-info">
                                                    <i class="fas fa-eye me-2"></i> Details
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-grid gap-2">
                                                <a href="join_challenge.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-join">
                                                    <i class="fas fa-plus-circle me-2"></i> Join Challenge (Attempt <?php echo $join_count + 1; ?>)
                                                </a>
                                                <a href="payment_gateway.php?type=challenge&challenge_id=<?php echo $challenge['id']; ?>&amount=<?php echo $challenge['daily_amount']; ?>" class="btn btn-danger">
                                                    <i class="fas fa-credit-card me-2"></i> Pay Now
                                                </a>
                                                <a href="challenge_details.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-info">
                                                    <i class="fas fa-eye me-2"></i> Details
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php
                                        // Check if challenge is created by admin (assuming admin user_id = 1)
                                        $is_admin_created = ($challenge['created_by'] == 1);
                                        ?>
                                        <?php if (!$is_admin_created): ?>
                                            <button class="btn-disabled" disabled>
                                                <i class="fas fa-lock me-2"></i> Invite Only
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php?redirect=join_challenge&challenge_id=<?php echo $challenge['id']; ?>" class="btn-login">
                                                <i class="fas fa-sign-in-alt me-2"></i> Login to Join
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    <script>
        // Role selection functionality
        function selectRole(role) {
            // Remove selected class from all role cards
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });

            // Add selected class to clicked card
            const selectedCard = document.querySelector(`#${role}`).closest('.role-card');
            selectedCard.classList.add('border-primary', 'bg-light');

            // Check the radio button
            document.getElementById(role).checked = true;

            // Enable continue button
            document.getElementById('continueBtn').disabled = false;
        }

        // Animation on scroll for challenge cards
        document.addEventListener('DOMContentLoaded', function() {
            const challengeCards = document.querySelectorAll('.challenge-card');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        // Stagger the animation for each card
                        setTimeout(() => {
                            entry.target.classList.add('animated');
                        }, index * 200);
                    }
                });
            }, { threshold: 0.1 });

            challengeCards.forEach(card => {
                observer.observe(card);
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
    </script>
</body>
</html>