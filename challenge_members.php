<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$challenge_id = intval($_GET['challenge_id'] ?? 0);

if (!$challenge_id) {
    redirect('admin.php');
}

// Fetch challenge details
$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->execute([$challenge_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    redirect('admin.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $user_id = intval($_POST['user_id']);

        // Check if user exists and is not already a participant
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "User not found.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM participants WHERE challenge_id = ? AND user_id = ?");
            $stmt->execute([$challenge_id, $user_id]);
            $existing_participant = $stmt->fetch();

            if ($existing_participant) {
                $error = "User is already a participant in this challenge.";
            } else {
                // Check current participant count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND status = 'active'");
                $stmt->execute([$challenge_id]);
                $current_count = $stmt->fetch()['count'];

                if ($current_count >= $challenge['max_participants']) {
                    $error = "Challenge is full. Cannot add more participants.";
                } else {
                    // Add member
                    $queue_position = $current_count + 1;
                    $stmt = $pdo->prepare("INSERT INTO participants (challenge_id, user_id, queue_position) VALUES (?, ?, ?)");
                    if ($stmt->execute([$challenge_id, $user_id, $queue_position])) {
                        $success = "Member added successfully!";
                    } else {
                        $error = "Failed to add member. Please try again.";
                    }
                }
            }
        }
    }

    if (isset($_POST['terminate_member'])) {
        $participant_id = intval($_POST['participant_id']);

        $stmt = $pdo->prepare("UPDATE participants SET status = 'inactive' WHERE id = ? AND challenge_id = ?");
        if ($stmt->execute([$participant_id, $challenge_id])) {
            $success = "Member terminated successfully!";
        } else {
            $error = "Failed to terminate member. Please try again.";
        }
    }
}

// Fetch current participants with payment data
$stmt = $pdo->prepare("
    SELECT p.id, p.user_id, p.queue_position, p.joined_at, p.status, p.join_attempt,
           u.username, u.email, u.phone_number
    FROM participants p
    JOIN users u ON p.user_id = u.id
    WHERE p.challenge_id = ?
    ORDER BY p.queue_position ASC
");
$stmt->execute([$challenge_id]);
$participants = $stmt->fetchAll();

// Calculate real payment data for each participant
foreach ($participants as &$participant) {
    // Calculate total expected payments (days from start to today)
    $start_date = new DateTime($challenge['start_date']);
    $today = new DateTime();
    $participant['total_payments'] = max(0, $today->diff($start_date)->days + 1); // +1 to include start date

    // Count paid payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as paid FROM payments WHERE participant_id = ? AND status = 'paid'");
    $stmt->execute([$participant['id']]);
    $participant['paid_payments'] = $stmt->fetch()['paid'];

    // Calculate overdue payments
    $participant['overdue_payments'] = max(0, $participant['total_payments'] - $participant['paid_payments']);

    // Check today's payment status
    $today_str = $today->format('Y-m-d');
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE participant_id = ? AND payment_date = ? AND status = 'paid'");
    $stmt->execute([$participant['id'], $today_str]);
    $participant['paid_today'] = $stmt->fetch() ? true : false;

    // Calculate consecutive overdue days
    $stmt = $pdo->prepare("SELECT MAX(payment_date) as last_paid FROM payments WHERE participant_id = ? AND status = 'paid'");
    $stmt->execute([$participant['id']]);
    $last_paid = $stmt->fetch()['last_paid'];

    if ($last_paid) {
        $last_paid_date = new DateTime($last_paid);
        $consecutive_overdue = 0;
        $current = clone $last_paid_date;
        $current->modify('+1 day');
        while ($current <= $today) {
            $consecutive_overdue++;
            $current->modify('+1 day');
        }
    } else {
        $consecutive_overdue = $participant['total_payments'];
    }
    $participant['consecutive_overdue'] = $consecutive_overdue;

    // Calculate penalty from database
    $stmt = $pdo->prepare("SELECT SUM(penalty_amount) as total_penalty FROM penalties WHERE participant_id = ? AND status = 'active'");
    $stmt->execute([$participant['id']]);
    $penalty_result = $stmt->fetch();
    $participant['penalty'] = $penalty_result['total_penalty'] ?? 0;

    // If no penalty in database and consecutive overdue >= 3, calculate and insert new penalty
    if ($participant['penalty'] == 0 && $consecutive_overdue >= 3) {
        $new_penalty = $challenge['daily_amount'] * $consecutive_overdue;
        $stmt = $pdo->prepare("INSERT INTO penalties (participant_id, penalty_amount, consecutive_days, applied_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$participant['id'], $new_penalty, $consecutive_overdue, date('Y-m-d')]);
        $participant['penalty'] = $new_penalty;
    }

    // Get list of overdue dates for modal
    if ($participant['overdue_payments'] > 0) {
        $overdue_dates = [];
        $current_date = clone $start_date;
        $paid_dates = [];

        // Get all paid dates for this participant
        $stmt = $pdo->prepare("SELECT payment_date FROM payments WHERE participant_id = ? AND status = 'paid'");
        $stmt->execute([$participant['id']]);
        $paid_payments = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Find overdue dates
        for ($i = 0; $i < $participant['total_payments']; $i++) {
            $date_str = $current_date->format('Y-m-d');
            if (!in_array($date_str, $paid_payments)) {
                $overdue_dates[] = $date_str;
            }
            $current_date->modify('+1 day');
        }
        $participant['overdue_dates'] = $overdue_dates;
    } else {
        $participant['overdue_dates'] = [];
    }
}

// Fetch available users (not already participants)
$stmt = $pdo->prepare("
    SELECT id, username, email, phone_number
    FROM users
    WHERE id NOT IN (SELECT user_id FROM participants WHERE challenge_id = ?)
    ORDER BY username ASC
");
$stmt->execute([$challenge_id]);
$available_users = $stmt->fetchAll();

// Calculate challenge statistics
$total_participants = count(array_filter($participants, function($p) { return $p['status'] == 'active'; }));
$terminated_participants = count(array_filter($participants, function($p) { return $p['status'] == 'inactive'; }));

// Calculate days since challenge start
$start_date = new DateTime($challenge['start_date']);
$today = new DateTime();
$days_elapsed = max(0, $today->diff($start_date)->days);

// Calculate end date and days remaining
$end_date = new DateTime($challenge['end_date']);
$days_remaining = max(0, $today->diff($end_date)->days);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - <?php echo htmlspecialchars($challenge['name']); ?> - <?php echo SITE_NAME; ?></title>
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
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
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
            opacity: 0;
            animation: backgroundAnimation 24s infinite;
        }

        .slide-1 {
            background: linear-gradient(135deg, #1a5276, #2c3e50);
            animation-delay: 0s;
        }

        .slide-2 {
            background: linear-gradient(135deg, #3498db, #2980b9);
            animation-delay: 6s;
        }

        .slide-3 {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            animation-delay: 12s;
        }

        .slide-4 {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            animation-delay: 18s;
        }

        @keyframes backgroundAnimation {
            0% { opacity: 0; }
            10% { opacity: 1; }
            25% { opacity: 1; }
            35% { opacity: 0; }
            100% { opacity: 0; }
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

        /* Content */
        .admin-content {
            padding: 100px 0 50px;
        }

        .challenge-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
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
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .challenge-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary);
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-weight: 600;
        }

        /* Form Cards */
        .form-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            border: none;
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }

        .form-title {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .form-body {
            padding: 25px;
        }

        /* Form Styling */
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
            background: white;
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 82, 118, 0.4);
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.6);
        }

        /* Tables */
        .admin-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .admin-table .table {
            margin-bottom: 0;
        }

        .admin-table th {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .admin-table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Buttons */
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        /* Alert */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-left: 4px solid var(--danger);
        }

        /* Progress Bars */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--success), #2ecc71);
            border-radius: 4px;
            transition: width 1s ease-in-out;
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
            .challenge-title {
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
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt me-2"></i><?php echo SITE_NAME; ?> Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="challenges.php">Challenges</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin.php">Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="admin-content">
        <div class="container">
            <!-- Challenge Header -->
            <div class="challenge-header animate-on-scroll">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="challenge-title"><?php echo htmlspecialchars($challenge['name']); ?></h1>
                        <p class="challenge-subtitle"><?php echo htmlspecialchars($challenge['description']); ?></p>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <p class="mb-1"><i class="fas fa-money-bill-wave me-2"></i><strong>Daily:</strong> TSh <?php echo number_format($challenge['daily_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><i class="fas fa-users me-2"></i><strong>Max Participants:</strong> <?php echo $challenge['max_participants']; ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><i class="fas fa-calendar-day me-2"></i><strong>Started:</strong> <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><i class="fas fa-flag-checkered me-2"></i><strong>Ends:</strong> <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="admin.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Admin
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card success animate-on-scroll">
                    <div class="stat-icon text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo $total_participants; ?></div>
                    <div class="stat-label">Active Members</div>
                </div>

                <div class="stat-card danger animate-on-scroll">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-value text-danger"><?php echo $terminated_participants; ?></div>
                    <div class="stat-label">Terminated</div>
                </div>

                <div class="stat-card info animate-on-scroll">
                    <div class="stat-icon text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $days_elapsed; ?></div>
                    <div class="stat-label">Days Elapsed</div>
                </div>

                <div class="stat-card warning animate-on-scroll">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $days_remaining; ?></div>
                    <div class="stat-label">Days Remaining</div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success animate-on-scroll"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger animate-on-scroll"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Member Form -->
            <div class="form-card animate-on-scroll">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-user-plus me-2"></i> Add New Member</h5>
                </div>
                <div class="form-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-8">
                                <label for="user_id" class="form-label">Select User</label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="">Choose a user...</option>
                                    <?php foreach ($available_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['username']); ?> - <?php echo htmlspecialchars($user['email']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="add_member" class="btn btn-admin w-100">
                                    <i class="fas fa-plus me-2"></i> Add Member
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Members List -->
            <div class="form-card animate-on-scroll">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-users me-2"></i> Challenge Members</h5>
                </div>
                <div class="form-body">
                    <?php if (empty($participants)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No members have joined this challenge yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>Queue #</th>
                                        <th>Member</th>
                                        <th>Contact</th>
                                        <th>Today's Payment</th>
                                        <th>Payment Progress</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $participant): ?>
                                        <tr class="<?php echo $participant['status'] == 'inactive' ? 'table-secondary' : ''; ?>">
                                            <td>
                                                <strong>#<?php echo $participant['queue_position']; ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($participant['username']); ?></strong>
                                                    <?php if ($participant['join_attempt'] > 1): ?>
                                                        <br><small class="text-info"><i class="fas fa-redo me-1"></i>Join Challenge Times <?php echo $participant['join_attempt']; ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($participant['status'] == 'inactive'): ?>
                                                        <br><small class="text-muted">Terminated</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($participant['email']); ?></small><br>
                                                <small><?php echo htmlspecialchars($participant['phone_number']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($participant['status'] == 'active'): ?>
                                                    <span class="badge bg-<?php echo $participant['paid_today'] ? 'success' : 'danger'; ?>">
                                                        <i class="fas fa-<?php echo $participant['paid_today'] ? 'check' : 'times'; ?> me-1"></i>
                                                        <?php echo $participant['paid_today'] ? 'Paid' : 'Not Paid'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($participant['status'] == 'active'): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <?php echo $participant['paid_payments']; ?>/<?php echo $participant['total_payments']; ?> payments
                                                        </small>
                                                    </div>
                                                    <?php
                                                    $payment_percentage = $participant['total_payments'] > 0 ?
                                                        round(($participant['paid_payments'] / $participant['total_payments']) * 100, 1) : 0;
                                                    ?>
                                                    <div class="progress mb-1">
                                                        <div class="progress-bar" role="progressbar"
                                                             style="width: <?php echo $payment_percentage; ?>%"
                                                             aria-valuenow="<?php echo $payment_percentage; ?>"
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?php echo $payment_percentage; ?>% complete</small>
                                                    <?php if ($participant['overdue_payments'] > 0): ?>
                                                        <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $participant['overdue_payments']; ?> overdue</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $participant['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <i class="fas fa-<?php echo $participant['status'] == 'active' ? 'check' : 'times'; ?> me-1"></i>
                                                    <?php echo ucfirst($participant['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($participant['joined_at'])); ?></td>
                                            <td>
                                                <?php if ($participant['status'] == 'active'): ?>
                                                    <button class="btn btn-outline-danger btn-action" onclick="terminateMember(<?php echo $participant['id']; ?>, '<?php echo htmlspecialchars($participant['username']); ?>')">
                                                        <i class="fas fa-user-times"></i> Terminate
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Terminated</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Termination Modal -->
    <div class="modal fade" id="terminateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-times me-2"></i>Terminate Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to terminate <strong id="terminateUsername"></strong> from this challenge?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone. The member will lose access to the challenge and their payment progress will be frozen.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="terminateForm" style="display: inline;">
                        <input type="hidden" id="terminateParticipantId" name="participant_id">
                        <button type="submit" name="terminate_member" class="btn btn-danger">
                            <i class="fas fa-user-times me-2"></i>Terminate Member
                        </button>
                    </form>
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

        function terminateMember(participantId, username) {
            document.getElementById('terminateParticipantId').value = participantId;
            document.getElementById('terminateUsername').textContent = username;
            const modal = new bootstrap.Modal(document.getElementById('terminateModal'));
            modal.show();
        }
    </script>
</body>
</html>
