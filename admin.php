<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'core/translation.php';

autoLoginAdmin();

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_challenge'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $daily_amount = floatval($_POST['daily_amount']);
        $max_participants = intval($_POST['max_participants']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Auto-translate to Swahili
        require_once 'core/translation.php';
        $sw_name = translateText($name, 'sw', 'en');
        $sw_description = translateText($description, 'sw', 'en');

        $stmt = $pdo->prepare("INSERT INTO challenges (name, description, sw_name, sw_description, daily_amount, max_participants, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $sw_name, $sw_description, $daily_amount, $max_participants, $start_date, $end_date, $_SESSION['user_id']]);

        $success = "Challenge created successfully!";
    }

    if (isset($_POST['add_material'])) {
        $name = sanitize($_POST['material_name']);
        $description = sanitize($_POST['material_description']);
        $price = floatval($_POST['material_price']);
        $image_path = null;

        // Handle image upload
        if (isset($_FILES['material_image']) && $_FILES['material_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            $file_extension = strtolower(pathinfo($_FILES['material_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid('material_', true) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['material_image']['tmp_name'], $upload_path)) {
                    $image_path = $upload_path;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        if (!isset($error)) {
            $stmt = $pdo->prepare("INSERT INTO materials (name, description, image, price, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $image_path, $price, $_SESSION['user_id']]);

            $success = "Building material added successfully!";
        }
    }

    // Edit Challenge
    if (isset($_POST['edit_challenge'])) {
        $id = intval($_POST['challenge_id']);
        $name = sanitize($_POST['edit_name']);
        $description = sanitize($_POST['edit_description']);
        $daily_amount = floatval($_POST['edit_daily_amount']);
        $max_participants = intval($_POST['edit_max_participants']);
        $start_date = $_POST['edit_start_date'];
        $end_date = $_POST['edit_end_date'];

        $stmt = $pdo->prepare("UPDATE challenges SET name = ?, description = ?, daily_amount = ?, max_participants = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->execute([$name, $description, $daily_amount, $max_participants, $start_date, $end_date, $id]);

        $success = "Challenge updated successfully!";
    }

    // Delete Challenge
    if (isset($_POST['delete_challenge'])) {
        $id = intval($_POST['challenge_id']);

        // Check if challenge has active participants
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE challenge_id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $active_participant_count = $stmt->fetch()['count'];

        if ($active_participant_count > 0) {
            $error = "Cannot delete challenge: Challenge has {$active_participant_count} active participant(s). Please terminate all participants first.";
        } else {
            // Start transaction for safe deletion
            $pdo->beginTransaction();
            try {
                // Delete related payments first
                $stmt = $pdo->prepare("DELETE FROM payments WHERE participant_id IN (SELECT id FROM participants WHERE challenge_id = ?)");
                $stmt->execute([$id]);

                // Delete daily winners
                $stmt = $pdo->prepare("DELETE FROM daily_winners WHERE challenge_id = ?");
                $stmt->execute([$id]);

                // Delete participants
                $stmt = $pdo->prepare("DELETE FROM participants WHERE challenge_id = ?");
                $stmt->execute([$id]);

                // Delete the challenge
                $stmt = $pdo->prepare("DELETE FROM challenges WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                $success = "Challenge deleted successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to delete challenge. Please try again.";
            }
        }
    }

    // Edit Material
    if (isset($_POST['edit_material'])) {
        $id = intval($_POST['material_id']);
        $name = sanitize($_POST['edit_material_name']);
        $description = sanitize($_POST['edit_material_description']);
        $price = floatval($_POST['edit_material_price']);
        $image_path = null;

        // Handle image upload
        if (isset($_FILES['edit_material_image']) && $_FILES['edit_material_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            $file_extension = strtolower(pathinfo($_FILES['edit_material_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid('material_', true) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['edit_material_image']['tmp_name'], $upload_path)) {
                    $image_path = $upload_path;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        if (!isset($error)) {
            if ($image_path) {
                $stmt = $pdo->prepare("UPDATE materials SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $image_path, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE materials SET name = ?, description = ?, price = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $id]);
            }

            $success = "Material updated successfully!";
        }
    }

    // Delete Material
    if (isset($_POST['delete_material'])) {
        $id = intval($_POST['material_id']);
        
        // Check if material has related records
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM direct_purchases WHERE material_id = ?");
        $stmt->execute([$id]);
        $purchase_count = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lipa_kidogo_payments WHERE material_id = ?");
        $stmt->execute([$id]);
        $installment_count = $stmt->fetch()['count'];

        if ($purchase_count > 0 || $installment_count > 0) {
            $error = "Cannot delete material: Material has related purchase records. Please delete these records first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Material deleted successfully!";
        }
    }

    // Change User Role
    if (isset($_POST['change_role'])) {
        $id = intval($_POST['user_id']);
        $new_role = $_POST['new_role'];

        if ($new_role === 'admin' || $new_role === 'user') {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $id]);
            $success = "User role updated successfully!";
        }
    }

    // Edit User
    if (isset($_POST['edit_user'])) {
        $id = intval($_POST['user_id']);
        $username = sanitize($_POST['edit_username']);
        $email = sanitize($_POST['edit_email']);
        $phone_number = sanitize($_POST['edit_phone_number']);
        $nida_id = sanitize($_POST['edit_nida_id']);

        // Check if username or email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ? OR nida_id = ?) AND id != ?");
        $stmt->execute([$username, $email, $nida_id, $id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $error = "Username, email, or NIDA ID already exists for another user.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, nida_id = ? WHERE id = ?");
            $stmt->execute([$username, $email, $phone_number, $nida_id, $id]);
            $success = "User information updated successfully!";
        }
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        $id = intval($_POST['user_id']);
        // Prevent deleting the current admin user
        if ($id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            // Check if user has related records
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE user_id = ?");
            $stmt->execute([$id]);
            $participant_count = $stmt->fetch()['count'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM direct_purchases WHERE user_id = ?");
            $stmt->execute([$id]);
            $purchase_count = $stmt->fetch()['count'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lipa_kidogo_payments WHERE user_id = ?");
            $stmt->execute([$id]);
            $installment_count = $stmt->fetch()['count'];

            if ($participant_count > 0 || $purchase_count > 0 || $installment_count > 0) {
                $error = "Cannot delete user: User has active participations or purchase records. Please remove these records first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "User deleted successfully!";
            }
        }
    }

    // Update Order Status
    if (isset($_POST['update_order_status'])) {
        $id = intval($_POST['order_id']);
        $status = $_POST['status'];

        $valid_statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE direct_purchases SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Order status updated successfully!";
        }
    }
    
    // Update Material Status
    if (isset($_POST['update_material_status'])) {
        $id = intval($_POST['material_id']);
        $status = $_POST['material_status'];

        $valid_statuses = ['active', 'inactive'];
        if (in_array($status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE materials SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Material status updated successfully!";
        }
    }

    // Approve Group Registration
    if (isset($_POST['approve_group_registration'])) {
        $id = intval($_POST['group_registration_id']);
        $daily_amount = floatval($_POST['daily_amount']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Get group registration details
        $stmt = $pdo->prepare("SELECT * FROM group_registrations WHERE id = ?");
        $stmt->execute([$id]);
        $group_reg = $stmt->fetch();

        if ($group_reg) {
            // Update group registration with approval details
            $stmt = $pdo->prepare("UPDATE group_registrations SET status = 'approved', daily_amount = ?, start_date = ?, end_date = ?, admin_notes = ? WHERE id = ?");
            $stmt->execute([$daily_amount, $start_date, $end_date, sanitize($_POST['admin_notes'] ?? ''), $id]);

            // Create the actual challenge
            $stmt = $pdo->prepare("INSERT INTO challenges (name, description, daily_amount, max_participants, start_date, end_date, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$group_reg['group_name'], $group_reg['description'], $daily_amount, $group_reg['required_people'], $start_date, $end_date, $_SESSION['user_id']]);

            $success = "Group registration approved and challenge created successfully!";
        }
    }

    // Reject Group Registration
    if (isset($_POST['reject_group_registration'])) {
        $id = intval($_POST['group_registration_id']);
        $admin_notes = sanitize($_POST['admin_notes'] ?? '');

        $stmt = $pdo->prepare("UPDATE group_registrations SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->execute([$admin_notes, $id]);

        $success = "Group registration rejected.";
    }

    // Set Group Price
    if (isset($_POST['set_group_price'])) {
        $id = intval($_POST['group_registration_id']);
        $daily_amount = floatval($_POST['daily_amount']);

        $stmt = $pdo->prepare("UPDATE group_registrations SET daily_amount = ? WHERE id = ?");
        $stmt->execute([$daily_amount, $id]);

        $success = "Group registration price updated successfully!";
    }

    // Update Penalty
    if (isset($_POST['update_penalty'])) {
        $id = intval($_POST['penalty_id']);
        $penalty_amount = floatval($_POST['penalty_amount']);
        $status = $_POST['penalty_status'];

        $valid_statuses = ['active', 'paid', 'waived'];
        if (in_array($status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE penalties SET penalty_amount = ?, status = ? WHERE id = ?");
            $stmt->execute([$penalty_amount, $status, $id]);
            $success = "Penalty updated successfully!";
        }
    }

    // Pay Penalty
    if (isset($_POST['pay_penalty'])) {
        $id = intval($_POST['penalty_id']);

        $stmt = $pdo->prepare("UPDATE penalties SET status = 'paid' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Penalty marked as paid!";
    }

    // Waive Penalty
    if (isset($_POST['waive_penalty'])) {
        $id = intval($_POST['penalty_id']);

        $stmt = $pdo->prepare("UPDATE penalties SET status = 'waived' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Penalty waived successfully!";
    }

    // Delete Penalty
    if (isset($_POST['delete_penalty'])) {
        $id = intval($_POST['penalty_id']);

        $stmt = $pdo->prepare("DELETE FROM penalties WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Penalty deleted successfully!";
    }
}

// Fetch all challenges
$stmt = $pdo->prepare("SELECT * FROM challenges ORDER BY created_at DESC");
$stmt->execute();
$challenges = $stmt->fetchAll();

// Fetch all users
$stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, phone_number, nida_id, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch statistics - UPDATED TO MATCH YOUR DATABASE
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM challenges WHERE status = 'active'");
$stmt->execute();
$active_challenges = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM materials WHERE status = 'active'");
$stmt->execute();
$total_materials = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM direct_purchases WHERE status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM group_registrations WHERE status = 'pending'");
$stmt->execute();
$pending_group_registrations = $stmt->fetch()['total'];

// Fetch total payments
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
$stmt->execute();
$total_payments = $stmt->fetch()['total'] ?? 0;

// Fetch penalties
$stmt = $pdo->prepare("
    SELECT pen.*, u.username, c.name as challenge_name, p.status as participant_status
    FROM penalties pen
    JOIN participants p ON pen.participant_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN challenges c ON p.challenge_id = c.id
    WHERE pen.status = 'active'
    ORDER BY pen.applied_date DESC
");
$stmt->execute();
$penalties = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
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
        
        /* Admin Content */
        .admin-content {
            padding: 100px 0 50px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }
        
        .admin-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .admin-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stats-grid.sideways {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding-bottom: 10px;
        }

        .stats-grid.sideways .stat-card {
            flex: 0 0 250px;
            min-width: 250px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
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
        
        .stat-card.info {
            border-left-color: var(--info);
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
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
        }
        
        /* List Groups */
        .admin-list-group .list-group-item {
            border: none;
            border-radius: 10px !important;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .admin-list-group .list-group-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
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
            .admin-title {
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
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt me-2"></i><?php echo SITE_NAME; ?> Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><?php echo __('home'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="challenges.php"><?php echo __('challenges'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><?php echo __('dashboard'); ?></a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin.php"><?php echo __('admin'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><?php echo __('logout'); ?></a></li>
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

    <!-- Admin Content -->
    <div class="admin-content">
        <div class="container">
            <!-- Header -->
            <div class="admin-header animate-on-scroll">
                <h1 class="admin-title">Admin Control Panel</h1>
                <p class="admin-subtitle">Manage challenges, materials, users, and orders</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card info animate-on-scroll">
                    <div class="stat-icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card success animate-on-scroll">
                    <div class="stat-icon text-success">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo $active_challenges; ?></div>
                    <div class="stat-label">Active Challenges</div>
                </div>
                
                <div class="stat-card warning animate-on-scroll">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $total_materials; ?></div>
                    <div class="stat-label">Materials</div>
                </div>
                
                <div class="stat-card danger animate-on-scroll">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value text-danger"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success animate-on-scroll"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger animate-on-scroll"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Forms Section -->
            <div class="row">
                <!-- Create Challenge -->
                <div class="col-lg-6 mb-4">
                    <div class="form-card animate-on-scroll">
                        <div class="form-header">
                            <h5 class="form-title"><i class="fas fa-plus-circle me-2"></i> Create New Challenge</h5>
                        </div>
                        <div class="form-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Challenge Name</label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter challenge name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the challenge" required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="daily_amount" class="form-label">Daily Amount (TSh)</label>
                                        <input type="number" class="form-control" id="daily_amount" name="daily_amount" step="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="max_participants" class="form-label">Max Participants</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" value="90" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                                <button type="submit" name="create_challenge" class="btn btn-admin w-100">
                                    <i class="fas fa-rocket me-2"></i> Create Challenge
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Material -->
                <div class="col-lg-6 mb-4">
                    <div class="form-card animate-on-scroll">
                        <div class="form-header">
                            <h5 class="form-title"><i class="fas fa-cubes me-2"></i> Add Building Material</h5>
                        </div>
                        <div class="form-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="material_name" class="form-label">Material Name</label>
                                    <input type="text" class="form-control" id="material_name" name="material_name" placeholder="Enter material name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="material_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="material_description" name="material_description" rows="2" placeholder="Describe the material" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="material_price" class="form-label">Price (TSh)</label>
                                    <input type="number" class="form-control" id="material_price" name="material_price" step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="mb-3">
                                    <label for="material_image" class="form-label">Material Image</label>
                                    <input type="file" class="form-control" id="material_image" name="material_image" accept="image/*">
                                    <div class="form-text">Upload an image of the building material (JPG, JPEG, PNG, GIF - Max 5MB)</div>
                                </div>
                                <button type="submit" name="add_material" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-2"></i> Add Material
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Challenge Modal -->
            <div class="modal fade" id="editChallengeModal" tabindex="-1" aria-labelledby="editChallengeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editChallengeModalLabel"><i class="fas fa-edit me-2"></i>Edit Challenge</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="editChallengeForm">
                                <input type="hidden" id="edit_challenge_id" name="challenge_id">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Challenge Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="edit_name" placeholder="Enter challenge name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="edit_description" rows="3" placeholder="Describe the challenge" required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_daily_amount" class="form-label">Daily Amount (TSh)</label>
                                        <input type="number" class="form-control" id="edit_daily_amount" name="edit_daily_amount" step="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_max_participants" class="form-label">Max Participants</label>
                                        <input type="number" class="form-control" id="edit_max_participants" name="edit_max_participants" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="edit_start_date" name="edit_start_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="edit_end_date" name="edit_end_date" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="edit_challenge" class="btn btn-admin flex-fill">
                                        <i class="fas fa-save me-2"></i> Update Challenge
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Material Modal -->
            <div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editMaterialModalLabel"><i class="fas fa-edit me-2"></i>Edit Material</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="editMaterialForm">
                                <input type="hidden" id="edit_material_id" name="material_id">
                                <div class="mb-3">
                                    <label for="edit_material_name" class="form-label">Material Name</label>
                                    <input type="text" class="form-control" id="edit_material_name" name="edit_material_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_material_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_material_description" name="edit_material_description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_material_price" class="form-label">Price (TSh)</label>
                                    <input type="number" class="form-control" id="edit_material_price" name="edit_material_price" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_material_status" class="form-label">Status</label>
                                    <select class="form-control" id="edit_material_status" name="material_status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="edit_material" class="btn btn-admin flex-fill">
                                        <i class="fas fa-save me-2"></i> Update Material
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Sections -->
            <div class="row">
                <!-- Challenges List -->
                <div class="col-lg-6 mb-4">
                    <div class="form-card animate-on-scroll">
                        <div class="form-header">
                            <h5 class="form-title"><i class="fas fa-list me-2"></i> Manage Challenges</h5>
                        </div>
                        <div class="form-body">
                            <?php if (empty($challenges)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No challenges created yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="admin-list-group list-group">
                                    <?php foreach ($challenges as $challenge): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($challenge['name']); ?></h6>
                                                <span class="badge bg-<?php echo $challenge['status'] === 'active' ? 'success' : ($challenge['status'] === 'completed' ? 'primary' : 'secondary'); ?>">
                                                    <?php echo ucfirst($challenge['status']); ?>
                                                </span>
                                            </div>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars(substr($challenge['description'], 0, 50)) . '...'; ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-money-bill-wave me-1"></i>TSh <?php echo number_format($challenge['daily_amount'], 2); ?> |
                                                <i class="fas fa-users me-1"></i><?php echo $challenge['max_participants']; ?> participants |
                                                <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($challenge['start_date'])); ?> - <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?>
                                            </small>
                                            <div class="mt-2 d-flex gap-2">
                                                <button class="btn btn-outline-primary btn-action flex-fill" onclick="editChallenge(<?php echo $challenge['id']; ?>, '<?php echo addslashes($challenge['name']); ?>', '<?php echo addslashes($challenge['description']); ?>', <?php echo $challenge['daily_amount']; ?>, <?php echo $challenge['max_participants']; ?>, '<?php echo $challenge['start_date']; ?>', '<?php echo $challenge['end_date']; ?>')">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                                <a href="challenge_members.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-outline-info btn-action flex-fill">
                                                    <i class="fas fa-users me-1"></i> Members
                                                </a>
                                                <button class="btn btn-outline-danger btn-action flex-fill" onclick="deleteChallenge(<?php echo $challenge['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="col-lg-6 mb-4">
                    <div class="form-card animate-on-scroll">
                        <div class="form-header">
                            <h5 class="form-title"><i class="fas fa-cubes me-2"></i> Manage Materials</h5>
                        </div>
                        <div class="form-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM materials ORDER BY created_at DESC");
                            $stmt->execute();
                            $materials = $stmt->fetchAll();
                            ?>

                            <?php if (empty($materials)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No materials added yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="admin-list-group list-group">
                                    <?php foreach ($materials as $material): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($material['name']); ?></h6>
                                                <span class="badge bg-success">TSh <?php echo number_format($material['price'], 2); ?></span>
                                            </div>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars(substr($material['description'], 0, 50)) . '...'; ?></p>
                                            <small class="text-muted">
                                                Status: <span class="badge bg-<?php echo $material['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($material['status']); ?>
                                                </span>
                                            </small>
                                            <div class="mt-2 d-flex gap-2">
                                                <button class="btn btn-outline-primary btn-action flex-fill" onclick="editMaterial(<?php echo $material['id']; ?>, '<?php echo addslashes($material['name']); ?>', '<?php echo addslashes($material['description']); ?>', <?php echo $material['price']; ?>, '<?php echo $material['status']; ?>')">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                                <button class="btn btn-outline-danger btn-action flex-fill" onclick="deleteMaterial(<?php echo $material['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Direct Purchases Management -->
            <div class="form-card animate-on-scroll mb-4">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-shopping-cart me-2"></i> Direct Purchase Orders</h5>
                </div>
                <div class="form-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT dp.*, u.username, m.name as material_name
                        FROM direct_purchases dp
                        JOIN users u ON dp.user_id = u.id
                        JOIN materials m ON dp.material_id = m.id
                        ORDER BY dp.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $orders = $stmt->fetchAll();
                    ?>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No direct purchase orders yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td><?php echo htmlspecialchars($order['material_name']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td class="fw-bold text-success">TSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $order['status'] === 'paid' ? 'success' :
                                                         ($order['status'] === 'pending' ? 'warning' :
                                                          ($order['status'] === 'shipped' ? 'info' :
                                                           ($order['status'] === 'delivered' ? 'primary' : 'secondary')));
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $order['status'] === 'paid' ? 'check' :
                                                             ($order['status'] === 'pending' ? 'clock' :
                                                              ($order['status'] === 'shipped' ? 'shipping-fast' :
                                                               ($order['status'] === 'delivered' ? 'box' : 'times')));
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-outline-primary btn-action" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success btn-action" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Registrations Management -->
            <div class="form-card animate-on-scroll mb-4">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-users-cog me-2"></i> Group Registrations Management</h5>
                </div>
                <div class="form-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT gr.*, u.username as leader_name
                        FROM group_registrations gr
                        JOIN users u ON gr.leader_id = u.id
                        WHERE gr.status = 'pending'
                        ORDER BY gr.created_at DESC
                    ");
                    $stmt->execute();
                    $group_registrations = $stmt->fetchAll();
                    ?>

                    <?php if (empty($group_registrations)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No pending group registrations.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Group Name</th>
                                        <th>Leader</th>
                                        <th>Description</th>
                                        <th>Required People</th>
                                        <th>Materials Needed</th>
                                        <th>Daily Amount</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group_registrations as $group): ?>
                                    <tr>
                                        <td><strong>#<?php echo $group['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($group['leader_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($group['description'], 0, 50)) . (strlen($group['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo $group['required_people']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($group['materials_needed'], 0, 50)) . (strlen($group['materials_needed']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo $group['daily_amount'] ? 'TSh ' . number_format($group['daily_amount'], 2) : 'Not Set'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($group['created_at'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-outline-warning btn-action" onclick="setGroupPrice(<?php echo $group['id']; ?>, <?php echo $group['daily_amount'] ?? 0; ?>)">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </button>
                                                <button class="btn btn-outline-success btn-action" onclick="approveGroupRegistration(<?php echo $group['id']; ?>, <?php echo $group['daily_amount'] ?? 0; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action" onclick="rejectGroupRegistration(<?php echo $group['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approve Group Registration Modal -->
            <div class="modal fade" id="approveGroupModal" tabindex="-1" aria-labelledby="approveGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approveGroupModalLabel"><i class="fas fa-check me-2"></i>Approve Group Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="approveGroupForm">
                                <input type="hidden" id="approve_group_registration_id" name="group_registration_id">
                                <div class="mb-3">
                                    <label for="approve_daily_amount" class="form-label">Daily Amount (TSh)</label>
                                    <input type="number" class="form-control" id="approve_daily_amount" name="daily_amount" step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="approve_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="approve_start_date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="approve_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="approve_end_date" name="end_date" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="approve_admin_notes" class="form-label">Admin Notes (Optional)</label>
                                    <textarea class="form-control" id="approve_admin_notes" name="admin_notes" rows="3" placeholder="Additional notes for approval"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="approve_group_registration" class="btn btn-success flex-fill">
                                        <i class="fas fa-check me-2"></i> Approve & Create Challenge
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reject Group Registration Modal -->
            <div class="modal fade" id="rejectGroupModal" tabindex="-1" aria-labelledby="rejectGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejectGroupModalLabel"><i class="fas fa-times me-2"></i>Reject Group Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="rejectGroupForm">
                                <input type="hidden" id="reject_group_registration_id" name="group_registration_id">
                                <div class="mb-3">
                                    <label for="reject_admin_notes" class="form-label">Rejection Reason</label>
                                    <textarea class="form-control" id="reject_admin_notes" name="admin_notes" rows="3" placeholder="Reason for rejection" required></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="reject_group_registration" class="btn btn-danger flex-fill">
                                        <i class="fas fa-times me-2"></i> Reject Registration
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Overview Section -->
            <div class="form-card animate-on-scroll mb-4">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-credit-card me-2"></i> Payments Overview</h5>
                </div>
                <div class="form-body">
                    <?php
                    // Fetch recent payments
                    $stmt = $pdo->prepare("
                        SELECT p.id, p.amount, p.payment_date, p.status, 
                               u.username, c.name as challenge_name, 'challenge' as payment_type
                        FROM payments p
                        JOIN participants part ON p.participant_id = part.id
                        JOIN users u ON part.user_id = u.id
                        JOIN challenges c ON part.challenge_id = c.id
                        ORDER BY p.payment_date DESC
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $challenge_payments = $stmt->fetchAll();

                    // Fetch recent direct purchases
                    $stmt = $pdo->prepare("
                        SELECT dp.id, dp.total_amount as amount, dp.created_at as payment_date, dp.status,
                               u.username, m.name as material_name, 'direct' as payment_type
                        FROM direct_purchases dp
                        JOIN users u ON dp.user_id = u.id
                        JOIN materials m ON dp.material_id = m.id
                        WHERE dp.status = 'paid'
                        ORDER BY dp.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $direct_payments = $stmt->fetchAll();

                    // Fetch recent lipa kidogo payments
                    $stmt = $pdo->prepare("
                        SELECT lkp.id, lkp.amount, lkp.payment_date, lkp.status,
                               u.username, m.name as material_name, 'installment' as payment_type
                        FROM lipa_kidogo_payments lkp
                        JOIN users u ON lkp.user_id = u.id
                        JOIN materials m ON lkp.material_id = m.id
                        WHERE lkp.status = 'paid'
                        ORDER BY lkp.payment_date DESC
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $installment_payments = $stmt->fetchAll();

                    // Combine all payments
                    $all_payments = array_merge($challenge_payments, $direct_payments, $installment_payments);

                    // Sort by date descending
                    usort($all_payments, function($a, $b) {
                        return strtotime($b['payment_date']) - strtotime($a['payment_date']);
                    });

                    $recent_payments = array_slice($all_payments, 0, 10);

                    // Calculate payment statistics
                    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
                    $stmt->execute();
                    $total_challenge_payments = $stmt->fetch()['total'] ?? 0;

                    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM direct_purchases WHERE status = 'paid'");
                    $stmt->execute();
                    $total_direct_payments = $stmt->fetch()['total'] ?? 0;

                    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM lipa_kidogo_payments WHERE status = 'paid'");
                    $stmt->execute();
                    $total_installment_payments = $stmt->fetch()['total'] ?? 0;

                    $total_all_payments = $total_challenge_payments + $total_direct_payments + $total_installment_payments;

                    // Today's payments
                    $today = date('Y-m-d');
                    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE DATE(payment_date) = ? AND status = 'paid'");
                    $stmt->execute([$today]);
                    $today_challenge = $stmt->fetch()['total'] ?? 0;

                    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM direct_purchases WHERE DATE(created_at) = ? AND status = 'paid'");
                    $stmt->execute([$today]);
                    $today_direct = $stmt->fetch()['total'] ?? 0;

                    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM lipa_kidogo_payments WHERE DATE(payment_date) = ? AND status = 'paid'");
                    $stmt->execute([$today]);
                    $today_installment = $stmt->fetch()['total'] ?? 0;

                    $today_total = $today_challenge + $today_direct + $today_installment;
                    ?>

                    <!-- Payment Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div class="stat-icon text-success">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-value text-success">TSh <?php echo number_format($total_all_payments, 2); ?></div>
                                <div class="stat-label">Total Payments</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <div class="stat-icon text-info">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="stat-value text-info">TSh <?php echo number_format($today_total, 2); ?></div>
                                <div class="stat-label">Today's Payments</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-value text-warning">TSh <?php echo number_format($total_challenge_payments, 2); ?></div>
                                <div class="stat-label">Challenge Payments</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-value text-primary">TSh <?php echo number_format($total_direct_payments + $total_installment_payments, 2); ?></div>
                                <div class="stat-label">Purchase Payments</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments Table -->
                    <h6 class="mb-3">Recent Payments</h6>
                    <?php if (empty($recent_payments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No payments recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $payment['payment_type'] === 'challenge' ? 'warning' :
                                                         ($payment['payment_type'] === 'direct' ? 'primary' : 'info');
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $payment['payment_type'] === 'challenge' ? 'tasks' :
                                                             ($payment['payment_type'] === 'direct' ? 'shopping-cart' : 'credit-card');
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['payment_type'] === 'challenge'): ?>
                                                    <?php echo htmlspecialchars($payment['challenge_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($payment['material_name']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold text-success">TSh <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $payment['status'] === 'paid' ? 'success' : 'warning';
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $payment['status'] === 'paid' ? 'check' : 'clock';
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reports Section -->
            <div class="form-card animate-on-scroll mb-4">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-chart-bar me-2"></i> Reports & Analytics</h5>
                </div>
                <div class="form-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-credit-card fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Payments Report</h5>
                                    <p class="card-text text-muted">Generate detailed report of all payment transactions including customer details and order status.</p>
                                    <a href="generate_payments_report.php" class="btn btn-success w-100">
                                        <i class="fas fa-chart-line me-2"></i>Generate Payments Report
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Users Report</h5>
                                    <p class="card-text text-muted">Generate comprehensive report of all registered users with names and phone numbers.</p>
                                    <a href="generate_users_report.php" class="btn btn-primary w-100">
                                        <i class="fas fa-user-friends me-2"></i>Generate Users Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penalty Management -->
            <div class="form-card animate-on-scroll mb-4">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-exclamation-triangle me-2"></i> Penalty Management</h5>
                </div>
                <div class="form-body">
                    <?php if (empty($penalties)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No active penalties found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Challenge</th>
                                        <th>Overdue Days</th>
                                        <th>Penalty Amount</th>
                                        <th>Status</th>
                                        <th>Applied Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penalties as $penalty): ?>
                                        <tr>
                                            <td><strong>#<?php echo $penalty['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($penalty['username']); ?></td>
                                            <td><?php echo htmlspecialchars($penalty['challenge_name']); ?></td>
                                            <td><?php echo $penalty['consecutive_days']; ?> days</td>
                                            <td class="fw-bold text-danger">TSh <?php echo number_format($penalty['penalty_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $penalty['status'] === 'active' ? 'danger' :
                                                         ($penalty['status'] === 'paid' ? 'success' : 'warning');
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $penalty['status'] === 'active' ? 'exclamation-triangle' :
                                                             ($penalty['status'] === 'paid' ? 'check' : 'clock');
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($penalty['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($penalty['applied_date'])); ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-outline-primary btn-action" onclick="updatePenalty(<?php echo $penalty['id']; ?>, <?php echo $penalty['penalty_amount']; ?>, '<?php echo $penalty['status']; ?>')">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </button>
                                                    <button class="btn btn-outline-success btn-action" onclick="payPenalty(<?php echo $penalty['id']; ?>)">
                                                        <i class="fas fa-money-bill-wave me-1"></i> Pay
                                                    </button>
                                                    <button class="btn btn-outline-warning btn-action" onclick="waivePenalty(<?php echo $penalty['id']; ?>)">
                                                        <i class="fas fa-ban me-1"></i> Waive
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-action" onclick="deletePenalty(<?php echo $penalty['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Update Penalty Modal -->
            <div class="modal fade" id="updatePenaltyModal" tabindex="-1" aria-labelledby="updatePenaltyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updatePenaltyModalLabel"><i class="fas fa-edit me-2"></i>Update Penalty</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="updatePenaltyForm">
                                <input type="hidden" id="update_penalty_id" name="penalty_id">
                                <div class="mb-3">
                                    <label for="update_penalty_amount" class="form-label">Penalty Amount (TSh)</label>
                                    <input type="number" class="form-control" id="update_penalty_amount" name="penalty_amount" step="0.01" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_penalty_status" class="form-label">Status</label>
                                    <select class="form-control" id="update_penalty_status" name="penalty_status" required>
                                        <option value="active">Active</option>
                                        <option value="paid">Paid</option>
                                        <option value="waived">Waived</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_penalty" class="btn btn-admin flex-fill">
                                        <i class="fas fa-save me-2"></i> Update Penalty
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-edit me-2"></i>Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="editUserForm">
                                <input type="hidden" id="edit_user_id" name="user_id">
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="edit_username" name="edit_username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="edit_email">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_phone_number" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="edit_phone_number" name="edit_phone_number">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_nida_id" class="form-label">NIDA ID</label>
                                    <input type="text" class="form-control" id="edit_nida_id" name="edit_nida_id">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="edit_password" name="edit_password" placeholder="Enter new password (optional)">
                                    <div class="form-text">Minimum 6 characters. Leave blank to keep current password.</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="edit_user" class="btn btn-admin flex-fill">
                                        <i class="fas fa-save me-2"></i> Update User
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Management -->
            <div class="form-card animate-on-scroll">
                <div class="form-header">
                    <h5 class="form-title"><i class="fas fa-users me-2"></i> User Management</h5>
                </div>
                <div class="form-body">
                    <div class="table-responsive">
                        <table class="table table-hover admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                    <th>NIDA ID</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nida_id']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                                <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'shield-alt' : 'user'; ?> me-1"></i>
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-outline-primary btn-action" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo addslashes($user['phone_number']); ?>', '<?php echo addslashes($user['nida_id']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-warning btn-action" onclick="changeRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            const oneMonthLater = new Date();
            oneMonthLater.setMonth(oneMonthLater.getMonth() + 1);
            const oneMonthLaterStr = oneMonthLater.toISOString().split('T')[0];
            
            document.getElementById('start_date').value = today;
            document.getElementById('start_date').min = today;
            document.getElementById('end_date').value = oneMonthLaterStr;
            document.getElementById('end_date').min = today;
            
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

        function editChallenge(id, name, description, dailyAmount, maxParticipants, startDate, endDate) {
            document.getElementById('edit_challenge_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_daily_amount').value = dailyAmount;
            document.getElementById('edit_max_participants').value = maxParticipants;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            const modal = new bootstrap.Modal(document.getElementById('editChallengeModal'));
            modal.show();
        }

        function deleteChallenge(id) {
            if (confirm('Are you sure you want to delete this challenge? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'challenge_id';
                input.value = id;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_challenge';
                deleteInput.value = '1';

                form.appendChild(input);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editMaterial(id, name, description, price, status) {
            document.getElementById('edit_material_id').value = id;
            document.getElementById('edit_material_name').value = name;
            document.getElementById('edit_material_description').value = description;
            document.getElementById('edit_material_price').value = price;
            document.getElementById('edit_material_status').value = status;
            const modal = new bootstrap.Modal(document.getElementById('editMaterialModal'));
            modal.show();
        }

        function deleteMaterial(id) {
            if (confirm('Are you sure you want to delete this material? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'material_id';
                input.value = id;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_material';
                deleteInput.value = '1';

                form.appendChild(input);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function changeRole(id, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            if (confirm(`Change user role to ${newRole}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = id;

                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'new_role';
                roleInput.value = newRole;

                const changeInput = document.createElement('input');
                changeInput.type = 'hidden';
                changeInput.name = 'change_role';
                changeInput.value = '1';

                form.appendChild(idInput);
                form.appendChild(roleInput);
                form.appendChild(changeInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_id';
                input.value = id;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_user';
                deleteInput.value = '1';

                form.appendChild(input);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewOrder(id) {
            // For now, show basic order info. In full implementation, this would open a modal or redirect to order details
            alert('View order details - Order ID: ' + id + '. In full implementation, this would show detailed order information.');
        }

        function updateStatus(id, currentStatus) {
            const statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
            const statusOptions = statuses.map(status => status.charAt(0).toUpperCase() + status.slice(1)).join('\n');

            let newStatus = prompt(`Update order status to one of:\n${statusOptions}\n\nCurrent status: ${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}`, currentStatus);

            if (newStatus) {
                newStatus = newStatus.toLowerCase();
                if (statuses.includes(newStatus) && newStatus !== currentStatus) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'order_id';
                    idInput.value = id;

                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = newStatus;

                    const updateInput = document.createElement('input');
                    updateInput.type = 'hidden';
                    updateInput.name = 'update_order_status';
                    updateInput.value = '1';

                    form.appendChild(idInput);
                    form.appendChild(statusInput);
                    form.appendChild(updateInput);
                    document.body.appendChild(form);
                    form.submit();
                } else if (newStatus === currentStatus) {
                    alert('Status unchanged.');
                } else {
                    alert('Invalid status selected.');
                }
            }
        }

        function setGroupPrice(id, currentAmount) {
            let newAmount = prompt('Set new daily amount (TSh):', currentAmount || '0.00');

            if (newAmount !== null) {
                newAmount = parseFloat(newAmount);
                if (!isNaN(newAmount) && newAmount >= 0) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'group_registration_id';
                    idInput.value = id;

                    const amountInput = document.createElement('input');
                    amountInput.type = 'hidden';
                    amountInput.name = 'daily_amount';
                    amountInput.value = newAmount;

                    const setInput = document.createElement('input');
                    setInput.type = 'hidden';
                    setInput.name = 'set_group_price';
                    setInput.value = '1';

                    form.appendChild(idInput);
                    form.appendChild(amountInput);
                    form.appendChild(setInput);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Please enter a valid amount (0 or greater).');
                }
            }
        }

        function approveGroupRegistration(id, currentAmount) {
            document.getElementById('approve_group_registration_id').value = id;
            document.getElementById('approve_daily_amount').value = currentAmount || '';
            const modal = new bootstrap.Modal(document.getElementById('approveGroupModal'));
            modal.show();
        }

        function rejectGroupRegistration(id) {
            document.getElementById('reject_group_registration_id').value = id;
            const modal = new bootstrap.Modal(document.getElementById('rejectGroupModal'));
            modal.show();
        }

        function updatePenalty(id, amount, status) {
            document.getElementById('update_penalty_id').value = id;
            document.getElementById('update_penalty_amount').value = amount;
            document.getElementById('update_penalty_status').value = status;
            const modal = new bootstrap.Modal(document.getElementById('updatePenaltyModal'));
            modal.show();
        }

        function payPenalty(id) {
            if (confirm('Mark this penalty as paid?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'penalty_id';
                input.value = id;

                const payInput = document.createElement('input');
                payInput.type = 'hidden';
                payInput.name = 'pay_penalty';
                payInput.value = '1';

                form.appendChild(input);
                form.appendChild(payInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function waivePenalty(id) {
            if (confirm('Are you sure you want to waive this penalty? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'penalty_id';
                input.value = id;

                const waiveInput = document.createElement('input');
                waiveInput.type = 'hidden';
                waiveInput.name = 'waive_penalty';
                waiveInput.value = '1';

                form.appendChild(input);
                form.appendChild(waiveInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deletePenalty(id) {
            if (confirm('Are you sure you want to delete this penalty? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'penalty_id';
                input.value = id;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_penalty';
                deleteInput.value = '1';

                form.appendChild(input);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>