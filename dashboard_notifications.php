<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Load translations
$translations = loadLanguage();

$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
        $result = markNotificationAsRead($_POST['notification_id'], $user_id);
        echo json_encode(['success' => $result]);
        exit;
    }

    if ($_POST['action'] === 'mark_all_read') {
        $result = markAllNotificationsAsRead($user_id);
        echo json_encode(['success' => $result]);
        exit;
    }

    if ($_POST['action'] === 'get_count') {
        $count = getUnreadNotificationCount($user_id);
        echo json_encode(['count' => $count]);
        exit;
    }
}

// Get notifications with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$all_notifications = getInAppNotifications($user_id, $per_page, false, $offset);
$total_notifications = count(getInAppNotifications($user_id, 1000, false)); // Get total for pagination
$total_pages = ceil($total_notifications / $per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('notifications'); ?> - <?php echo SITE_NAME; ?></title>
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
            --bg-color: #ffffff;
            --text-color: #2c3e50;
            --card-bg: #ffffff;
            --navbar-bg: rgba(44, 62, 80, 0.95);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .navbar {
            background: var(--navbar-bg) !important;
            backdrop-filter: blur(10px);
        }

        .notifications-container {
            padding: 100px 0 50px;
        }

        .notification-card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .notification-card.unread {
            border-left-color: var(--secondary);
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.05), rgba(255, 255, 255, 0.95));
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .notification-header {
            padding: 20px 25px 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .notification-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .notification-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .notification-body {
            padding: 15px 25px 20px;
        }

        .notification-message {
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-mark-read {
            background: var(--success);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .btn-action-link {
            background: var(--primary);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .pagination {
            justify-content: center;
            margin-top: 30px;
        }

        .page-link {
            color: var(--primary);
            border-color: var(--primary);
        }

        .page-link:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .notification-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background: #fee;
            color: #e74c3c;
        }

        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }

        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }

        .mark-all-read-btn {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .mark-all-read-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link" href="lipa_kidogo.php">Lipa Kidogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="direct_purchase.php">Direct Purchase</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard_notifications.php"><i class="fas fa-bell me-1"></i><?php echo __('notifications'); ?></a></li>
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

    <!-- Notifications Content -->
    <div class="notifications-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0">
                            <i class="fas fa-bell me-2"></i><?php echo __('notifications'); ?>
                        </h1>
                        <?php if (!empty($all_notifications)): ?>
                            <button id="markAllReadBtn" class="mark-all-read-btn">
                                <i class="fas fa-check-double me-2"></i><?php echo __('mark_all_read'); ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($all_notifications)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <h3><?php echo __('no_notifications'); ?></h3>
                            <p class="mb-4"><?php echo __('no_notifications_desc'); ?></p>
                            <a href="dashboard.php" class="btn btn-primary"><?php echo __('back_to_dashboard'); ?></a>
                        </div>
                    <?php else: ?>
                        <div id="notificationsList">
                            <?php foreach ($all_notifications as $notification): ?>
                                <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                                    <div class="notification-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="notification-title">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="badge bg-warning ms-2"><?php echo __('new'); ?></span>
                                                    <?php endif; ?>
                                                </h6>
                                                <div class="notification-meta">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                    <span class="notification-priority priority-<?php echo $notification['priority']; ?> ms-2">
                                                        <?php echo $notification['priority']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if (!$notification['is_read']): ?>
                                                <button class="btn-mark-read" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                    <i class="fas fa-check me-1"></i><?php echo __('mark_read'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="notification-body">
                                        <div class="notification-message">
                                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                        </div>
                                        <div class="notification-actions">
                                            <?php if ($notification['action_url'] && $notification['action_text']): ?>
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn-action-link">
                                                    <i class="fas fa-arrow-right"></i>
                                                    <?php echo htmlspecialchars($notification['action_text']); ?>
                                                </a>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i><?php echo ucfirst($notification['notification_type']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Notifications pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(notificationId) {
            fetch('dashboard_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`[data-id="${notificationId}"]`);
                    if (card) {
                        card.classList.remove('unread');
                        const markReadBtn = card.querySelector('.btn-mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                        const badge = card.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function markAllAsRead() {
            if (!confirm('<?php echo __('confirm_mark_all_read'); ?>')) {
                return;
            }

            fetch('dashboard_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-card.unread').forEach(card => {
                        card.classList.remove('unread');
                        const markReadBtn = card.querySelector('.btn-mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                        const badge = card.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                    });
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function updateNotificationCount() {
            fetch('dashboard_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_count'
            })
            .then(response => response.json())
            .then(data => {
                // Update notification badge in navbar if it exists
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const markAllBtn = document.getElementById('markAllReadBtn');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', markAllAsRead);
            }
        });
    </script>
</body>
</html>
