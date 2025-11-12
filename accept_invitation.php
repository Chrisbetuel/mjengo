<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Load translations
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);

// Get invitation ID from URL
$invitation_id = sanitize($_GET['id'] ?? '');

if (empty($invitation_id)) {
    redirect('dashboard.php');
}

// Fetch invitation details
$stmt = $pdo->prepare("
    SELECT gm.*, g.name as group_name, g.description, u.username as leader_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as current_members
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    JOIN users u ON g.leader_id = u.id
    WHERE gm.id = ? AND gm.user_id = ? AND gm.status = 'invited'
");
$stmt->execute([$invitation_id, $_SESSION['user_id']]);
$invitation = $stmt->fetch();

if (!$invitation) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

// Handle invitation response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'accept') {
        // Check if group is not full
        $stmt = $pdo->prepare("SELECT max_members FROM groups WHERE id = ?");
        $stmt->execute([$invitation['group_id']]);
        $group_info = $stmt->fetch();
        $max_members = $group_info ? $group_info['max_members'] : 0;

        if ($invitation['current_members'] >= $max_members) {
            $errors[] = 'This group is already full.';
        } else {
            // Update member status to accepted
            $stmt = $pdo->prepare("UPDATE group_members SET status = 'accepted', joined_at = NOW() WHERE id = ?");
            if ($stmt->execute([$invitation_id])) {
                $success = 'You have successfully joined the group!';
                // Refresh invitation data
                $invitation['status'] = 'accepted';

                // Send notification to group leader
                $notification_title = "Group Invitation Accepted";
                $notification_message = "{$_SESSION['username']} has accepted your invitation to join the group '{$invitation['group_name']}'.";
                $action_url = "group_management.php?group_id={$invitation['group_id']}";
                $action_text = "View Group";

                sendNotification($invitation['leader_id'], $notification_title, $notification_message, 'group_invitations', $action_url, $action_text);
            } else {
                $errors[] = 'Failed to join the group. Please try again.';
            }
        }
    } elseif ($action === 'decline') {
        // Get group leader info before deleting the invitation
        $leader_id = $invitation['leader_id'];
        $group_name = $invitation['group_name'];

        // Remove the invitation
        $stmt = $pdo->prepare("DELETE FROM group_members WHERE id = ?");
        if ($stmt->execute([$invitation_id])) {
            $success = 'Invitation declined.';

            // Send notification to group leader
            $notification_title = "Group Invitation Declined";
            $notification_message = "{$_SESSION['username']} has declined your invitation to join the group '{$group_name}'.";
            $action_url = "group_management.php?group_id={$invitation['group_id']}";
            $action_text = "View Group";

            sendNotification($leader_id, $notification_title, $notification_message, 'group_invitations', $action_url, $action_text);

            // Redirect back to dashboard
            redirect('dashboard.php');
        } else {
            $errors[] = 'Failed to decline invitation. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Invitation - <?php echo SITE_NAME; ?></title>
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

        .invitation-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .invitation-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            background: white;
        }

        .invitation-header {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .invitation-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }

        .invitation-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .invitation-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .invitation-body {
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

        .btn-accept {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
            width: 100%;
            margin-bottom: 15px;
        }

        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }

        .btn-decline {
            background: linear-gradient(135deg, var(--accent), #c0392b);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
            width: 100%;
        }

        .btn-decline:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.6);
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

        .invitation-icon {
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="invitation-container">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>

            <div class="invitation-card">
                <div class="invitation-header">
                    <div class="invitation-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <h1 class="invitation-title">Group Invitation</h1>
                    <p class="invitation-subtitle">You've been invited to join a group</p>
                </div>

                <div class="invitation-body">
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

                    <?php if ($invitation['status'] !== 'accepted'): ?>
                        <!-- Group Details -->
                        <div class="group-card">
                            <h3 class="group-name"><?php echo htmlspecialchars($invitation['group_name']); ?></h3>
                            <div class="group-leader">
                                <i class="fas fa-user-tie me-2"></i>Led by <?php echo htmlspecialchars($invitation['leader_name']); ?>
                            </div>
                            <?php if (!empty($invitation['description'])): ?>
                                <div class="group-description">
                                    <?php echo htmlspecialchars($invitation['description']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="group-stats">
                                <div class="member-count">
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo $invitation['current_members']; ?> Members
                                </div>
                                <div class="text-muted">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Invited <?php echo date('M d, Y', strtotime($invitation['joined_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <form method="POST">
                            <button type="submit" name="action" value="accept" class="btn-accept">
                                <i class="fas fa-check-circle me-2"></i> Accept Invitation
                            </button>
                            <button type="submit" name="action" value="decline" class="btn-decline">
                                <i class="fas fa-times-circle me-2"></i> Decline Invitation
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Success Message -->
                        <div class="text-center py-5">
                            <div style="font-size: 4rem; color: var(--success); margin-bottom: 20px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="text-success">Welcome to the Group!</h3>
                            <p class="text-muted">You have successfully joined "<?php echo htmlspecialchars($invitation['group_name']); ?>"</p>
                            <a href="dashboard.php" class="btn btn-primary mt-3">
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
