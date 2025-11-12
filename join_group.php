<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Store the current URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect('login.php');
}

// Load translations
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);

// Get group ID from URL
$group_id = sanitize($_GET['group_id'] ?? '');

if (empty($group_id)) {
    redirect('dashboard.php');
}

// Fetch group details
$stmt = $pdo->prepare("SELECT g.*, u.username as leader_name FROM groups g JOIN users u ON g.leader_id = u.id WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

// Check if user is already a member of this group
$stmt = $pdo->prepare("SELECT id, status FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $_SESSION['user_id']]);
$existing_membership = $stmt->fetch();

if ($existing_membership) {
    if ($existing_membership['status'] == 'active') {
        $errors[] = 'You are already a member of this group.';
    } elseif ($existing_membership['status'] == 'invited') {
        // Update status to active if previously invited
        $stmt = $pdo->prepare("UPDATE group_members SET status = 'active', joined_at = NOW() WHERE id = ?");
        if ($stmt->execute([$existing_membership['id']])) {
            $success = 'Welcome! You have successfully joined the group.';

            // Send notification to group leader
            $notification_title = "New Member Joined Group";
            $notification_message = "{$_SESSION['username']} has joined your group '{$group['name']}'.";
            $action_url = "group_management.php?group_id={$group_id}";
            $action_text = "View Group";

            sendNotification($group['leader_id'], $notification_title, $notification_message, 'group_joins', $action_url, $action_text);
        } else {
            $errors[] = 'Failed to join the group. Please try again.';
        }
    } else {
        $errors[] = 'Your membership status needs to be resolved by the group leader.';
    }
} else {
    // Check current member count
    $stmt = $pdo->prepare("SELECT COUNT(*) as current_members FROM group_members WHERE group_id = ? AND status = 'active'");
    $stmt->execute([$group_id]);
    $current_members = $stmt->fetch()['current_members'];

    if ($current_members >= $group['max_members']) {
        $errors[] = 'This group is already full.';
    } else {
        // Add user to group
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, status, joined_at) VALUES (?, ?, 'active', NOW())");
        if ($stmt->execute([$group_id, $_SESSION['user_id']])) {
            $success = 'Welcome! You have successfully joined the group.';

            // Send notification to group leader
            $notification_title = "New Member Joined Group";
            $notification_message = "{$_SESSION['username']} has joined your group '{$group['name']}'.";
            $action_url = "group_management.php?group_id={$group_id}";
            $action_text = "View Group";

            sendNotification($group['leader_id'], $notification_title, $notification_message, 'group_joins', $action_url, $action_text);
        } else {
            $errors[] = 'Failed to join the group. Please try again.';
        }
    }
}

// If successful, redirect to group management
if (!empty($success)) {
    $_SESSION['success_message'] = $success;
    redirect("group_management.php?group_id={$group_id}");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Group - <?php echo SITE_NAME; ?></title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .join-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .join-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            background: white;
        }

        .join-header {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .join-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }

        .join-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .join-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .join-body {
            padding: 40px;
        }

        .group-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .group-card:hover {
            border-color: var(--primary);
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .group-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .group-leader {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .group-description {
            color: #666;
            margin-bottom: 20px;
        }

        .group-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-count {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
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

        .join-icon {
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="join-container">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>

            <div class="join-card">
                <div class="join-header">
                    <div class="join-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h1 class="join-title">Join Group</h1>
                    <p class="join-subtitle">You've been invited to join a group</p>
                </div>

                <div class="join-body">
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

                    <!-- Group Details -->
                    <div class="group-card">
                        <h3 class="group-name"><?php echo htmlspecialchars($group['name']); ?></h3>
                        <div class="group-leader">
                            <i class="fas fa-user-tie me-2"></i>Led by <?php echo htmlspecialchars($group['leader_name']); ?>
                        </div>
                        <?php if (!empty($group['description'])): ?>
                            <div class="group-description">
                                <?php echo htmlspecialchars($group['description']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="group-stats">
                            <div class="member-count">
                                <i class="fas fa-users me-2"></i>
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as member_count FROM group_members WHERE group_id = ? AND status = 'active'");
                                $stmt->execute([$group_id]);
                                $member_count = $stmt->fetch()['member_count'];
                                echo $member_count;
                                ?> Members
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-user-plus me-2"></i>
                                Max: <?php echo $group['max_members']; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($errors) && empty($success)): ?>
                        <!-- Join Button -->
                        <div class="text-center">
                            <p class="text-muted mb-4">Click the button below to join this group</p>
                            <form method="POST">
                                <button type="submit" name="join_group" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus me-2"></i> Join Group
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Action Buttons -->
                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i> Go to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
