<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
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

// Check if user is the group leader or a member
$stmt = $pdo->prepare("SELECT g.*, c.name as challenge_name, c.status as challenge_status, c.daily_amount, c.start_date, c.end_date FROM groups g LEFT JOIN challenges c ON g.challenge_id = c.id WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    redirect('dashboard.php');
}

// Check if user is leader or member
$is_leader = ($group['leader_id'] == $_SESSION['user_id']);
$stmt = $pdo->prepare("SELECT status FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $_SESSION['user_id']]);
$member_status = $stmt->fetch();

if (!$is_leader && !$member_status) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

// Member invitation is now handled via WhatsApp sharing (no POST handler needed)

// Handle group update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    $group_name = sanitize($_POST['group_name'] ?? '');
    $group_description = sanitize($_POST['group_description'] ?? '');

    if (empty($group_name)) {
        $errors[] = 'Group name is required.';
    } elseif (strlen($group_name) < 3) {
        $errors[] = 'Group name must be at least 3 characters long.';
    } else {
        $stmt = $pdo->prepare("UPDATE groups SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$group_name, $group_description, $group_id])) {
            $success = 'Group updated successfully!';
            // Refresh group data
            $group['name'] = $group_name;
            $group['description'] = $group_description;
            // Redirect to refresh page and show updated data
            header("Location: group_management.php?group_id=$group_id");
            exit();
        } else {
            $errors[] = 'Failed to update group.';
        }
    }
}

// Handle member status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_member_status'])) {
    $member_id = intval($_POST['member_id']);
    $new_status = sanitize($_POST['member_status']);

    if ($is_leader) {
        $valid_statuses = ['invited', 'accepted', 'active'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE group_members SET status = ? WHERE id = ? AND group_id = ?");
            if ($stmt->execute([$new_status, $member_id, $group_id])) {
                $success = 'Member status updated successfully!';
                header("Location: group_management.php?group_id=$group_id");
                exit();
            } else {
                $errors[] = 'Failed to update member status.';
            }
        } else {
            $errors[] = 'Invalid status.';
        }
    } else {
        $errors[] = 'Only group leaders can change member status.';
    }
}

// Handle member removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $member_id = intval($_POST['member_id']);

    if ($is_leader) {
        // Prevent leader from removing themselves
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE id = ? AND group_id = ?");
        $stmt->execute([$member_id, $group_id]);
        $member_to_remove = $stmt->fetch();

        if ($member_to_remove && $member_to_remove['user_id'] == $_SESSION['user_id']) {
            $errors[] = 'You cannot remove yourself from the group. Transfer leadership first.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE id = ? AND group_id = ?");
            if ($stmt->execute([$member_id, $group_id])) {
                $success = 'Member removed successfully!';
                header("Location: group_management.php?group_id=$group_id");
                exit();
            } else {
                $errors[] = 'Failed to remove member.';
            }
        }
    } else {
        $errors[] = 'Only group leaders can remove members.';
    }
}

// Handle member actions (accept, reject, leave)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_member'])) {
        $member_id = intval($_POST['member_id']);
        if ($is_leader) {
            $stmt = $pdo->prepare("UPDATE group_members SET status = 'accepted' WHERE id = ? AND group_id = ? AND status = 'invited'");
            if ($stmt->execute([$member_id, $group_id])) {
                $success = 'Member accepted successfully!';
                header("Location: group_management.php?group_id=$group_id");
                exit();
            } else {
                $errors[] = 'Failed to accept member.';
            }
        } else {
            $errors[] = 'Only group leaders can accept members.';
        }
    }

    if (isset($_POST['reject_member'])) {
        $member_id = intval($_POST['member_id']);
        if ($is_leader) {
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE id = ? AND group_id = ? AND status = 'invited'");
            if ($stmt->execute([$member_id, $group_id])) {
                $success = 'Member invitation rejected successfully!';
            } else {
                $errors[] = 'Failed to reject member.';
            }
        } else {
            $errors[] = 'Only group leaders can reject members.';
        }
    }

    if (isset($_POST['leave_group'])) {
        $member_id = intval($_POST['member_id']);
        // Check if the current user is trying to leave
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE id = ? AND group_id = ?");
        $stmt->execute([$member_id, $group_id]);
        $member_to_leave = $stmt->fetch();

        if ($member_to_leave && $member_to_leave['user_id'] == $_SESSION['user_id']) {
            // User is leaving the group
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE id = ? AND group_id = ?");
            if ($stmt->execute([$member_id, $group_id])) {
                $success = 'You have left the group successfully!';
                // Redirect to dashboard after leaving
                redirect('dashboard.php');
            } else {
                $errors[] = 'Failed to leave group.';
            }
        } else {
            $errors[] = 'You can only leave groups you are a member of.';
        }
    }
}

// Fetch group members
$stmt = $pdo->prepare("
    SELECT gm.*, u.username, u.email
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.status, gm.joined_at DESC
");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll();

// Available users fetch removed - invitation now via WhatsApp sharing

// Fetch group challenge details and payment progress if challenge exists
$challenge_details = null;
$payment_progress = null;
$user_queue_position = null;

if (!empty($group['challenge_name'])) {
    $challenge_details = [
        'name' => $group['challenge_name'],
        'status' => $group['challenge_status'],
        'daily_amount' => $group['daily_amount'],
        'start_date' => $group['start_date'],
        'end_date' => $group['end_date']
    ];

    // Get user's queue position in the challenge
    $stmt = $pdo->prepare("
        SELECT queue_position
        FROM participants
        WHERE challenge_id = (SELECT id FROM challenges WHERE name = ? AND group_id = ?)
        AND user_id = ?
    ");
    $stmt->execute([$group['challenge_name'], $group_id, $_SESSION['user_id']]);
    $queue_result = $stmt->fetch();
    $user_queue_position = $queue_result ? $queue_result['queue_position'] : null;

    // Calculate payment progress for the group challenge
    if ($group['challenge_status'] == 'active') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_participants
            FROM participants p
            JOIN challenges c ON p.challenge_id = c.id
            WHERE c.name = ? AND c.group_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$group['challenge_name'], $group_id]);
        $total_participants = $stmt->fetch()['total_participants'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as paid_today
            FROM payments pay
            JOIN participants p ON pay.participant_id = p.id
            JOIN challenges c ON p.challenge_id = c.id
            WHERE c.name = ? AND c.group_id = ? AND pay.payment_date = CURDATE() AND pay.status = 'paid'
        ");
        $stmt->execute([$group['challenge_name'], $group_id]);
        $paid_today = $stmt->fetch()['paid_today'];

        $today = new DateTime();
        $end_date = new DateTime($group['end_date']);
        $days_remaining = $today->diff($end_date)->days;

        $payment_progress = [
            'total_participants' => $total_participants,
            'paid_today' => $paid_today,
            'payment_percentage' => $total_participants > 0 ? round(($paid_today / $total_participants) * 100, 1) : 0,
            'days_remaining' => $days_remaining
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Group - <?php echo SITE_NAME; ?></title>
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

        .header-card {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 82, 118, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .status-invited { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .status-accepted { background: rgba(26, 82, 118, 0.1); color: #1a5276; }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
        }

        .alert-danger { background: rgba(231, 76, 60, 0.1); color: #c0392b; border-left: 4px solid var(--accent); }
        .alert-success { background: rgba(39, 174, 96, 0.1); color: #27ae60; border-left: 4px solid var(--success); }

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

        .member-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s;
        }

        .member-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>

        <!-- Header -->
        <div class="header-card">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="mb-2"><i class="fas fa-users me-3"></i><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($group['description']); ?></p>
                    <?php if (!empty($group['challenge_name'])): ?>
                        <p class="mt-2 mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Custom Challenge: <?php echo htmlspecialchars($group['challenge_name']); ?>
                            <span class="badge bg-<?php echo $group['challenge_status'] == 'approved' ? 'success' : ($group['challenge_status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($group['challenge_status'] ?? 'none'); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="mt-3">
                        <span class="badge bg-primary fs-6"><?php echo count($members); ?> Members</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Alerts -->
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

                <!-- Group Members -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i> Group Members (<?php echo count($members); ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No members yet</h5>
                                <p class="text-muted">Start by inviting members to your group.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <div class="member-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($member['username']); ?>
                                                <?php if ($member['user_id'] == $_SESSION['user_id']): ?>
                                                    <small class="text-primary">(You - Leader)</small>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="status-badge status-<?php echo $member['status']; ?>">
                                                <?php echo ucfirst($member['status']); ?>
                                            </span>
                                            <div class="mt-2">
                                                <?php if ($is_leader && $member['user_id'] != $_SESSION['user_id']): ?>
                                                    <!-- Leader actions for other members -->
                                                    <?php if ($member['status'] == 'invited'): ?>
                                                        <form method="POST" style="display: inline-block;" class="me-1">
                                                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                            <button type="submit" name="accept_member" class="btn btn-success btn-sm" onclick="return confirm('Accept <?php echo htmlspecialchars($member['username']); ?> into the group?')">Accept</button>
                                                        </form>
                                                        <form method="POST" style="display: inline-block;" class="me-1">
                                                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                            <button type="submit" name="reject_member" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject <?php echo htmlspecialchars($member['username']); ?> invitation?')">Reject</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline-block;">
                                                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                            <input type="hidden" name="change_member_status" value="1">
                                                            <select name="member_status" class="form-select form-select-sm d-inline-block w-auto me-2" onchange="this.form.submit()">
                                                                <option value="invited" <?php echo $member['status'] == 'invited' ? 'selected' : ''; ?>>Invited</option>
                                                                <option value="accepted" <?php echo $member['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                                <option value="active" <?php echo $member['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                            </select>
                                                            <button type="submit" name="remove_member" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($member['username']); ?> from the group?')">Remove</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php elseif (!$is_leader && $member['user_id'] == $_SESSION['user_id']): ?>
                                                    <!-- Leave group option for non-leader members -->
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                        <button type="submit" name="leave_group" class="btn btn-outline-warning btn-sm" onclick="return confirm('Are you sure you want to leave this group?')">Leave Group</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($member['joined_at'] ?? $member['invited_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Group Challenge Section -->
                <?php if ($challenge_details): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy me-2"></i> Group Challenge: <?php echo htmlspecialchars($challenge_details['name']); ?>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2"><i class="fas fa-calendar-day me-2 text-primary"></i><strong>Started:</strong> <?php echo date('M d, Y', strtotime($challenge_details['start_date'])); ?></p>
                                    <p class="mb-2"><i class="fas fa-flag-checkered me-2 text-warning"></i><strong>Ends:</strong> <?php echo date('M d, Y', strtotime($challenge_details['end_date'])); ?></p>
                                    <p class="mb-2"><i class="fas fa-money-bill-wave me-2 text-success"></i><strong>Daily Amount:</strong> TSh <?php echo number_format($challenge_details['daily_amount'], 2); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><i class="fas fa-list-ol me-2 text-info"></i><strong>Your Queue Position:</strong> #<?php echo $user_queue_position ?? 'N/A'; ?></p>
                                    <p class="mb-2"><i class="fas fa-info-circle me-2 text-secondary"></i><strong>Status:</strong>
                                        <span class="badge bg-<?php echo $challenge_details['status'] == 'approved' ? 'success' : ($challenge_details['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($challenge_details['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <?php if ($payment_progress && $challenge_details['status'] == 'active'): ?>
                                <div class="progress-container mb-3">
                                    <h6>Today's Payment Progress</h6>
                                    <div class="progress mb-2">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: <?php echo $payment_progress['payment_percentage']; ?>%"
                                             aria-valuenow="<?php echo $payment_progress['payment_percentage']; ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted"><?php echo $payment_progress['paid_today']; ?>/<?php echo $payment_progress['total_participants']; ?> paid today</small>
                                        <small class="text-muted"><?php echo $payment_progress['payment_percentage']; ?>%</small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo $payment_progress['days_remaining']; ?> days remaining</small>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="payment_gateway.php?type=challenge&challenge_id=<?php echo $group['challenge_id']; ?>&amount=<?php echo $challenge_details['daily_amount']; ?>" class="btn btn-success btn-action flex-fill">
                                        <i class="fas fa-credit-card me-2"></i> Pay Now
                                    </a>
                                    <button class="btn btn-primary btn-action flex-fill" onclick="viewChallengeDetails()">
                                        <i class="fas fa-eye me-2"></i> View Details
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Share Group Link -->
                <?php if ($is_leader): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-share-alt me-2"></i> Share Group Invitation
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <p class="text-muted mb-4">Share this group invitation link via WhatsApp to invite new members</p>

                                <?php
                                $group_link = BASE_URL . "/join_group.php?group_id=" . $group_id;
                                $share_message = "Join my group '" . htmlspecialchars($group['name']) . "' on " . SITE_NAME . "! Click here: " . $group_link;
                                $whatsapp_url = "https://wa.me/?text=" . urlencode($share_message);
                                ?>

                                <div class="mb-3">
                                    <input type="text" class="form-control" id="groupLink" value="<?php echo $group_link; ?>" readonly>
                                </div>

                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-success">
                                        <i class="fab fa-whatsapp me-2"></i> Share via WhatsApp
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="copyToClipboard()">
                                        <i class="fas fa-copy me-2"></i> Copy Link
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Members will be able to join directly through this link after logging in to their account.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Edit Group -->
                <?php if ($is_leader): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-edit me-2"></i> Edit Group Details
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Group Name</label>
                                    <input type="text" class="form-control" name="group_name" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="group_description" rows="3"><?php echo htmlspecialchars($group['description']); ?></textarea>
                                </div>
                                <button type="submit" name="update_group" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update Group
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Group Stats -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i> Group Statistics
                    </div>
                    <div class="card-body">
                        <?php
                        $active_count = 0;
                        $invited_count = 0;
                        $accepted_count = 0;

                        foreach ($members as $member) {
                            switch ($member['status']) {
                                case 'active': $active_count++; break;
                                case 'invited': $invited_count++; break;
                                case 'accepted': $accepted_count++; break;
                            }
                        }
                        ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 text-success"><?php echo $active_count; ?></div>
                                <small class="text-muted">Active</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-warning"><?php echo $invited_count; ?></div>
                                <small class="text-muted">Invited</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-primary"><?php echo $accepted_count; ?></div>
                                <small class="text-muted">Accepted</small>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <div class="h5"><?php echo $group['max_members']; ?></div>
                            <small class="text-muted">Max Members</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewChallengeDetails() {
            window.location.href = 'challenge_details.php?challenge_id=<?php echo $group_id; ?>';
        }

        function copyToClipboard() {
            const linkInput = document.getElementById('groupLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                document.execCommand('copy');
                // Show success feedback
                const originalText = linkInput.value;
                linkInput.value = 'Link copied!';
                setTimeout(() => {
                    linkInput.value = originalText;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy link. Please copy manually.');
            }
        }
    </script>
</body>
</html>
