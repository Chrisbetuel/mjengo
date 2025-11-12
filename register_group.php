<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=register_group');
}

// Load translations
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = sanitize($_POST['group_name'] ?? '');
    $group_description = sanitize($_POST['group_description'] ?? '');
    $group_type = sanitize($_POST['group_type'] ?? 'existing');
    $challenge_id = sanitize($_POST['challenge_id'] ?? '');
    $max_members = sanitize($_POST['max_members'] ?? 10);

    // Custom challenge fields
    $custom_challenge_name = sanitize($_POST['custom_challenge_name'] ?? '');
    $custom_challenge_description = sanitize($_POST['custom_challenge_description'] ?? '');
    $custom_daily_amount = sanitize($_POST['custom_daily_amount'] ?? '');
    $custom_start_date = sanitize($_POST['custom_start_date'] ?? '');
    $custom_end_date = sanitize($_POST['custom_end_date'] ?? '');
    $custom_max_participants = sanitize($_POST['custom_max_participants'] ?? 90);

    if (empty($group_name)) {
        $errors[] = 'Group name is required.';
    } elseif (strlen($group_name) < 3) {
        $errors[] = 'Group name must be at least 3 characters long.';
    } elseif (!is_numeric($max_members) || $max_members < 2 || $max_members > 50) {
        $errors[] = 'Maximum members must be between 2 and 50.';
    } else {
        if ($group_type === 'existing') {
            // Existing challenge validation
            if (empty($challenge_id)) {
                $errors[] = 'Please select a challenge.';
            } else {
                // Check if challenge exists and is active
                $stmt = $pdo->prepare("SELECT id, name FROM challenges WHERE id = ? AND status = 'active'");
                $stmt->execute([$challenge_id]);
                $challenge = $stmt->fetch();

                if (!$challenge) {
                    $errors[] = 'Selected challenge is not available.';
                } else {
                    // Check if user already has a group for this challenge
                    $stmt = $pdo->prepare("SELECT id FROM groups WHERE leader_id = ? AND challenge_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $challenge_id]);
                    if ($stmt->fetch()) {
                        $errors[] = 'You already have a group for this challenge.';
                    }
                }
            }
        } elseif ($group_type === 'custom') {
            // Custom challenge validation
            if (empty($custom_challenge_name)) {
                $errors[] = 'Custom challenge name is required.';
            } elseif (empty($custom_daily_amount) || !is_numeric($custom_daily_amount) || $custom_daily_amount < 100) {
                $errors[] = 'Daily amount must be at least TSh 100.';
            } elseif (empty($custom_start_date) || empty($custom_end_date)) {
                $errors[] = 'Start and end dates are required.';
            } elseif (strtotime($custom_start_date) >= strtotime($custom_end_date)) {
                $errors[] = 'End date must be after start date.';
            } elseif (strtotime($custom_start_date) < strtotime('today')) {
                $errors[] = 'Start date cannot be in the past.';
            } elseif (!is_numeric($custom_max_participants) || $custom_max_participants < 2 || $custom_max_participants > 200) {
                $errors[] = 'Max participants must be between 2 and 200.';
            }
        }

        if (empty($errors)) {
            // Begin transaction
            $pdo->beginTransaction();

            try {
                if ($group_type === 'existing') {
                    // Create group for existing challenge
                    $stmt = $pdo->prepare("INSERT INTO groups (name, description, leader_id, challenge_id, max_members) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$group_name, $group_description, $_SESSION['user_id'], $challenge_id, $max_members]);
                    $group_id = $pdo->lastInsertId();

                    $success = 'Group created successfully! You can now invite members to join your group.';
                } elseif ($group_type === 'custom') {
                    // Create group first
                    $stmt = $pdo->prepare("INSERT INTO groups (name, description, leader_id, max_members) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$group_name, $group_description, $_SESSION['user_id'], $max_members]);
                    $group_id = $pdo->lastInsertId();

                    // Create custom challenge
                    $stmt = $pdo->prepare("INSERT INTO group_challenges (group_id, name, description, daily_amount, max_participants, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$group_id, $custom_challenge_name, $custom_challenge_description, $custom_daily_amount, $custom_max_participants, $custom_start_date, $custom_end_date]);

                    $success = 'Group and custom challenge created successfully! Your challenge is pending admin approval. You will be notified once it\'s approved.';
                }

                // Add the leader as a member
                $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'active')");
                $stmt->execute([$group_id, $_SESSION['user_id']]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to create group. Please try again.';
            }
        }
    }
}

// Fetch active challenges
$stmt = $pdo->prepare("SELECT id, name, description, daily_amount FROM challenges WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$challenges = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Group - <?php echo SITE_NAME; ?></title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
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

        /* Registration Container */
        .register-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease;
        }

        .register-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            background: white;
            backdrop-filter: blur(10px);
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }

        .register-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .register-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .register-body {
            padding: 40px;
        }

        /* Form Styling */
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px 15px 50px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
            background: white;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
            z-index: 5;
        }

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
            background: white;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 82, 118, 0.4);
            width: 100%;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.6);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* Alert Styling */
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

        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        /* Links */
        .register-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .register-link:hover {
            color: var(--dark);
            transform: translateX(5px);
        }

        .register-link i {
            margin-right: 8px;
        }

        /* Animation */
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
        @media (max-width: 576px) {
            .register-container {
                padding: 0 20px;
            }

            .register-body {
                padding: 30px 25px;
            }

            .register-header {
                padding: 25px;
            }

            .register-title {
                font-size: 1.8rem;
            }
        }

        /* Challenge Cards */
        .challenge-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .challenge-option:hover {
            border-color: var(--primary);
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .challenge-option.selected {
            border-color: var(--primary);
            background: rgba(26, 82, 118, 0.05);
        }

        .challenge-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .challenge-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .challenge-amount {
            color: var(--primary);
            font-weight: 600;
        }

        /* Radio buttons */
        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            font-weight: 500;
            color: var(--dark);
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

    <!-- Registration Container -->
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h1 class="register-title">Register Group</h1>
                    <p class="register-subtitle">Create a group for building challenges</p>
                </div>
                <div class="register-body">
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

                    <form method="POST" id="groupRegistrationForm" novalidate>
                        <div class="input-group">
                            <i class="fas fa-users input-icon"></i>
                            <input type="text" class="form-control" id="group_name" name="group_name" placeholder="Enter group name" required>
                            <div class="invalid-feedback">Please enter a group name.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-align-left input-icon"></i>
                            <textarea class="form-control" id="group_description" name="group_description" rows="3" placeholder="Describe your group (optional)"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Choose Option</label>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="group_type" id="existing_challenge" value="existing" checked>
                                <label class="form-check-label" for="existing_challenge">
                                    Join an existing challenge as a group
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="group_type" id="custom_challenge" value="custom">
                                <label class="form-check-label" for="custom_challenge">
                                    Create a custom challenge for my group (requires admin approval)
                                </label>
                            </div>
                        </div>

                        <div id="existing_challenge_section">
                            <label class="form-label">Select Challenge</label>
                            <?php if (empty($challenges)): ?>
                                <div class="alert alert-info">No active challenges available at the moment.</div>
                            <?php else: ?>
                                <?php foreach ($challenges as $challenge): ?>
                                    <div class="challenge-option" onclick="selectChallenge(<?php echo $challenge['id']; ?>)">
                                        <div class="challenge-name"><?php echo htmlspecialchars($challenge['name']); ?></div>
                                        <div class="challenge-description"><?php echo htmlspecialchars(substr($challenge['description'], 0, 100)); ?>...</div>
                                        <div class="challenge-amount">Daily Amount: TSh <?php echo number_format($challenge['daily_amount'], 0); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <input type="hidden" id="challenge_id" name="challenge_id" required>
                            <div class="invalid-feedback">Please select a challenge.</div>
                        </div>

                        <div id="custom_challenge_section" style="display: none;">
                            <div class="input-group mb-3">
                                <i class="fas fa-trophy input-icon"></i>
                                <input type="text" class="form-control" id="custom_challenge_name" name="custom_challenge_name" placeholder="Custom challenge name">
                            </div>

                            <div class="input-group mb-3">
                                <i class="fas fa-align-left input-icon"></i>
                                <textarea class="form-control" id="custom_challenge_description" name="custom_challenge_description" rows="3" placeholder="Describe your custom challenge"></textarea>
                            </div>

                            <div class="input-group mb-3">
                                <i class="fas fa-money-bill-wave input-icon"></i>
                                <input type="number" class="form-control" id="custom_daily_amount" name="custom_daily_amount" placeholder="Daily amount (TSh)" min="100" step="50">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <i class="fas fa-calendar-plus input-icon"></i>
                                        <input type="date" class="form-control" id="custom_start_date" name="custom_start_date" placeholder="Start date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <i class="fas fa-calendar-check input-icon"></i>
                                        <input type="date" class="form-control" id="custom_end_date" name="custom_end_date" placeholder="End date">
                                    </div>
                                </div>
                            </div>

                            <div class="input-group mb-3">
                                <i class="fas fa-users-cog input-icon"></i>
                                <input type="number" class="form-control" id="custom_max_participants" name="custom_max_participants" placeholder="Max participants" min="2" max="200" value="90">
                            </div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-user-friends input-icon"></i>
                            <input type="number" class="form-control" id="max_members" name="max_members" placeholder="Maximum members (2-50)" min="2" max="50" value="10" required>
                            <div class="invalid-feedback">Please enter a valid number of members (2-50).</div>
                        </div>

                        <button type="submit" class="btn-register" <?php echo empty($challenges) ? 'disabled' : ''; ?>>
                            <i class="fas fa-plus-circle me-2"></i> Create Group
                        </button>
                    </form>

                    <div class="register-links">
                        <a href="challenges.php" class="register-link">
                            <i class="fas fa-arrow-left"></i> Back to Challenges
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedChallengeId = null;

        function selectChallenge(challengeId) {
            // Remove selected class from all options
            document.querySelectorAll('.challenge-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');

            // Set the hidden input value
            document.getElementById('challenge_id').value = challengeId;
            selectedChallengeId = challengeId;

            // Remove invalid feedback if present
            document.getElementById('challenge_id').classList.remove('is-invalid');
        }

        // Toggle sections based on group type
        document.addEventListener('DOMContentLoaded', function() {
            const existingRadio = document.getElementById('existing_challenge');
            const customRadio = document.getElementById('custom_challenge');
            const existingSection = document.getElementById('existing_challenge_section');
            const customSection = document.getElementById('custom_challenge_section');
            const form = document.getElementById('groupRegistrationForm');

            function toggleSections() {
                if (existingRadio.checked) {
                    existingSection.style.display = 'block';
                    customSection.style.display = 'none';
                    selectedChallengeId = null;
                    document.getElementById('challenge_id').value = '';
                    document.querySelectorAll('.challenge-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                } else {
                    existingSection.style.display = 'none';
                    customSection.style.display = 'block';
                    selectedChallengeId = null;
                    document.getElementById('challenge_id').value = '';
                }
            }

            existingRadio.addEventListener('change', toggleSections);
            customRadio.addEventListener('change', toggleSections);

            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                if (existingRadio.checked && !selectedChallengeId) {
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementById('challenge_id').classList.add('is-invalid');
                }

                form.classList.add('was-validated');
            });

            // Auto-focus first field
            document.getElementById('group_name').focus();
        });
    </script>
</body>
</html>
