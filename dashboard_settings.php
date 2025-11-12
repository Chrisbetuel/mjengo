<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Load translations
$translations = loadLanguage();

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['theme'])) {
        $theme = sanitize($_POST['theme']);
        if (in_array($theme, AVAILABLE_THEMES)) {
            setUserPreference($user_id, 'theme', $theme);
            $_SESSION['success_message'] = __('settings_updated');
        }
    }

    if (isset($_POST['font_size'])) {
        $font_size = sanitize($_POST['font_size']);
        if (in_array($font_size, AVAILABLE_FONT_SIZES)) {
            setUserPreference($user_id, 'font_size', $font_size);
            $_SESSION['success_message'] = __('settings_updated');
        }
    }

    if (isset($_POST['update_theme'])) {
        $theme = sanitize($_POST['theme']);
        if (in_array($theme, AVAILABLE_THEMES)) {
            setUserPreference($user_id, 'theme', $theme);
            $_SESSION['success_message'] = __('settings_updated');
        }
    }

    if (isset($_POST['update_font_size'])) {
        $font_size = sanitize($_POST['font_size']);
        if (in_array($font_size, AVAILABLE_FONT_SIZES)) {
            setUserPreference($user_id, 'font_size', $font_size);
            $_SESSION['success_message'] = __('settings_updated');
        }
    }

    if (isset($_POST['update_notifications'])) {
        $notification_types = ['payment_reminders', 'challenge_updates', 'group_invitations', 'payment_success'];

        foreach ($notification_types as $type) {
            $email = isset($_POST[$type . '_email']) ? 1 : 0;
            $sms = isset($_POST[$type . '_sms']) ? 1 : 0;
            $in_app = isset($_POST[$type . '_in_app']) ? 1 : 0;
            $frequency = sanitize($_POST[$type . '_frequency'] ?? 'daily');

            updateNotificationPreference($user_id, $type, $email, $sms, $in_app, $frequency);
        }

        $_SESSION['success_message'] = __('settings_updated');
    }

    if (isset($_POST['update_widgets'])) {
        $widgets = [
            'welcome_card',
            'stats_overview',
            'active_challenges',
            'terminated_challenges',
            'group_invitations',
            'user_groups',
            'lipa_kidogo_payments'
        ];

        foreach ($widgets as $index => $widget) {
            $position = intval($_POST[$widget . '_position'] ?? ($index + 1));
            $visible = isset($_POST[$widget . '_visible']) ? 1 : 0;

            updateDashboardWidget($user_id, $widget, $position, $visible);
        }

        $_SESSION['success_message'] = __('settings_updated');
    }

    // Redirect to refresh the page
    redirect('dashboard_settings.php');
}

// Get current preferences
$current_theme = getCurrentTheme($user_id);
$current_font_size = getCurrentFontSize($user_id);
$dashboard_widgets = getDashboardWidgets($user_id);
$notification_preferences = getNotificationPreferences($user_id);

// Convert notification preferences to associative array for easier access
$notification_prefs = [];
foreach ($notification_preferences as $pref) {
    $notification_prefs[$pref['notification_type']] = $pref;
}

// Convert widgets to associative array
$widget_settings = [];
foreach ($dashboard_widgets as $widget) {
    $widget_settings[$widget['widget_name']] = $widget;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard_settings'); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            <?php
            $theme_vars = applyThemeVariables($current_theme);
            foreach ($theme_vars as $var => $value) {
                echo "$var: $value;\n            ";
            }
            ?>
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: <?php echo $current_font_size === 'small' ? '14px' : ($current_font_size === 'large' ? '18px' : '16px'); ?>;
        }

        .navbar {
            background: var(--navbar-bg) !important;
            backdrop-filter: blur(10px);
        }

        .settings-container {
            padding: 100px 0 50px;
        }

        .settings-card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 20px;
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .settings-body {
            padding: 30px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .widget-item {
            background: var(--light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .widget-drag-handle {
            cursor: move;
            color: var(--primary);
            margin-right: 15px;
        }

        .notification-type {
            background: var(--light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .notification-type h6 {
            color: var(--primary);
            margin-bottom: 15px;
            font-weight: 600;
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard_settings.php"><i class="fas fa-cog me-1"></i>Settings</a></li>
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

    <!-- Settings Content -->
    <div class="settings-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1 class="mb-4 text-center">
                        <i class="fas fa-cog me-2"></i><?php echo __('dashboard_settings'); ?>
                    </h1>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <!-- Appearance Settings -->
                    <div class="settings-card">
                        <h5 class="settings-header">
                            <i class="fas fa-palette me-2"></i><?php echo __('appearance_settings'); ?>
                        </h5>
                        <div class="settings-body">
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="theme" class="form-label"><?php echo __('theme'); ?></label>
                                        <select class="form-select" id="theme" name="theme">
                                            <?php foreach (AVAILABLE_THEMES as $theme): ?>
                                                <option value="<?php echo $theme; ?>" <?php echo $current_theme === $theme ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($theme); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="font_size" class="form-label"><?php echo __('font_size'); ?></label>
                                        <select class="form-select" id="font_size" name="font_size">
                                            <?php foreach (AVAILABLE_FONT_SIZES as $size): ?>
                                                <option value="<?php echo $size; ?>" <?php echo $current_font_size === $size ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($size); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" name="update_theme" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo __('save_changes'); ?>
                                    </button>
                                    <button type="submit" name="update_font_size" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo __('save_changes'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Dashboard Widgets -->
                    <div class="settings-card">
                        <h5 class="settings-header">
                            <i class="fas fa-th-large me-2"></i><?php echo __('dashboard_widgets'); ?>
                        </h5>
                        <div class="settings-body">
                            <p class="text-muted mb-3"><?php echo __('customize_dashboard_widgets'); ?></p>
                            <form method="POST" id="widgetsForm">
                                <div id="widgetsList">
                                    <?php
                                    $widget_labels = [
                                        'welcome_card' => __('welcome'),
                                        'stats_overview' => __('statistics'),
                                        'active_challenges' => __('active_challenges'),
                                        'terminated_challenges' => __('terminated_challenges'),
                                        'group_invitations' => __('group_invitations'),
                                        'user_groups' => __('your_groups'),
                                        'lipa_kidogo_payments' => __('payment_plans')
                                    ];

                                    foreach ($widget_settings as $widget_name => $widget):
                                        $label = $widget_labels[$widget_name] ?? ucfirst(str_replace('_', ' ', $widget_name));
                                    ?>
                                        <div class="widget-item" data-widget="<?php echo $widget_name; ?>">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-grip-vertical widget-drag-handle"></i>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="visible_<?php echo $widget_name; ?>"
                                                           name="<?php echo $widget_name; ?>_visible"
                                                           <?php echo $widget['is_visible'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label fw-bold" for="visible_<?php echo $widget_name; ?>">
                                                        <?php echo $label; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="<?php echo $widget_name; ?>_position"
                                                   value="<?php echo $widget['widget_position']; ?>" class="position-input">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" name="update_widgets" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo __('save_changes'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="settings-card">
                        <h5 class="settings-header">
                            <i class="fas fa-bell me-2"></i><?php echo __('notification_preferences'); ?>
                        </h5>
                        <div class="settings-body">
                            <form method="POST">
                                <?php
                                $notification_labels = [
                                    'payment_reminders' => __('payment_reminders'),
                                    'challenge_updates' => __('challenge_updates'),
                                    'group_invitations' => __('group_invitations'),
                                    'payment_success' => __('payment_success')
                                ];

                                foreach ($notification_labels as $type => $label):
                                    $pref = $notification_prefs[$type] ?? ['email_enabled' => 1, 'sms_enabled' => 0, 'in_app_enabled' => 1, 'frequency' => 'daily'];
                                ?>
                                    <div class="notification-type">
                                        <h6><?php echo $label; ?></h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="email_<?php echo $type; ?>"
                                                           name="<?php echo $type; ?>_email"
                                                           <?php echo $pref['email_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="email_<?php echo $type; ?>">
                                                        <i class="fas fa-envelope me-1"></i>Email
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="sms_<?php echo $type; ?>"
                                                           name="<?php echo $type; ?>_sms"
                                                           <?php echo $pref['sms_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="sms_<?php echo $type; ?>">
                                                        <i class="fas fa-sms me-1"></i>SMS
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="in_app_<?php echo $type; ?>"
                                                           name="<?php echo $type; ?>_in_app"
                                                           <?php echo $pref['in_app_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="in_app_<?php echo $type; ?>">
                                                        <i class="fas fa-bell me-1"></i><?php echo __('in_app'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="frequency_<?php echo $type; ?>" class="form-label small"><?php echo __('frequency'); ?></label>
                                                <select class="form-select form-select-sm" id="frequency_<?php echo $type; ?>" name="<?php echo $type; ?>_frequency">
                                                    <option value="immediate" <?php echo $pref['frequency'] === 'immediate' ? 'selected' : ''; ?>><?php echo __('immediate'); ?></option>
                                                    <option value="daily" <?php echo $pref['frequency'] === 'daily' ? 'selected' : ''; ?>><?php echo __('daily'); ?></option>
                                                    <option value="weekly" <?php echo $pref['frequency'] === 'weekly' ? 'selected' : ''; ?>><?php echo __('weekly'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mt-3">
                                    <button type="submit" name="update_notifications" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo __('save_changes'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="settings-card">
                        <h5 class="settings-header">
                            <i class="fas fa-bolt me-2"></i><?php echo __('quick_actions'); ?>
                        </h5>
                        <div class="settings-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="payment_gateway.php?type=challenge" class="btn btn-success btn-lg w-100 mb-3">
                                        <i class="fas fa-credit-card me-2"></i><?php echo __('make_payment'); ?>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="challenges.php" class="btn btn-primary btn-lg w-100 mb-3">
                                        <i class="fas fa-plus me-2"></i><?php echo __('join_challenge'); ?>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="lipa_kidogo.php" class="btn btn-info btn-lg w-100 mb-3">
                                        <i class="fas fa-shopping-cart me-2"></i><?php echo __('browse_materials'); ?>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="register_group.php" class="btn btn-warning btn-lg w-100 mb-3">
                                        <i class="fas fa-users me-2"></i><?php echo __('create_group'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make widgets sortable
            const widgetsList = document.getElementById('widgetsList');
            if (widgetsList) {
                new Sortable(widgetsList, {
                    handle: '.widget-drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        // Update position inputs
                        const items = widgetsList.querySelectorAll('.widget-item');
                        items.forEach((item, index) => {
                            const positionInput = item.querySelector('.position-input');
                            if (positionInput) {
                                positionInput.value = index + 1;
                            }
                        });
                    }
                });
            }

            // Auto-save theme changes
            document.getElementById('theme').addEventListener('change', function() {
                this.form.submit();
            });

            // Auto-save font size changes
            document.getElementById('font_size').addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
