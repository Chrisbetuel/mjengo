<?php
require_once 'config.php';

// Auto-login admin for development
autoLoginAdmin();

// Check if admin
if (!isAdmin()) {
    redirect('index.php');
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_material']) || isset($_POST['edit_material'])) {
        // Handle material addition or editing
        $name = sanitize($_POST['material_name']);
        $description = sanitize($_POST['material_description']);
        $price = floatval($_POST['material_price']);
        $editId = isset($_POST['edit_material_id']) ? intval($_POST['edit_material_id']) : null;

        // Validate inputs
        if (empty($name) || empty($description) || $price <= 0) {
            $message = 'Please fill in all required fields with valid data.';
            $messageType = 'danger';
        } else {
            $imagePath = null;

            // Handle file upload
            if (isset($_FILES['material_image']) && $_FILES['material_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/materials/';
                $fileName = uniqid() . '_' . basename($_FILES['material_image']['name']);
                $uploadFile = $uploadDir . $fileName;

                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (in_array($_FILES['material_image']['type'], $allowedTypes)) {
                    if (move_uploaded_file($_FILES['material_image']['tmp_name'], $uploadFile)) {
                        $imagePath = $uploadFile;
                    } else {
                        $message = 'Failed to upload image.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Invalid image file type. Only JPG, PNG, and GIF are allowed.';
                    $messageType = 'danger';
                }
            }

            if (empty($message)) {
                try {
                    if ($editId) {
                        // Update existing material
                        if ($imagePath) {
                            $stmt = $pdo->prepare("UPDATE materials SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
                            $stmt->execute([$name, $description, $price, $imagePath, $editId]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE materials SET name = ?, description = ?, price = ? WHERE id = ?");
                            $stmt->execute([$name, $description, $price, $editId]);
                        }
                        $message = 'Material updated successfully!';
                    } else {
                        // Insert new material
                        if ($imagePath) {
                            $stmt = $pdo->prepare("INSERT INTO materials (name, description, price, image, created_by) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$name, $description, $price, $imagePath, $_SESSION['user_id']]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO materials (name, description, price, created_by) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$name, $description, $price, $_SESSION['user_id']]);
                        }
                        $message = 'Material added successfully!';
                    }
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error ' . ($editId ? 'updating' : 'adding') . ' material: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    } elseif (isset($_POST['delete_material'])) {
        // Handle material deletion
        $materialId = intval($_POST['material_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$materialId]);
            $message = 'Material deleted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting material: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif (isset($_POST['create_challenge'])) {
        // Handle challenge creation
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $dailyAmount = floatval($_POST['daily_amount']);
        $maxParticipants = intval($_POST['max_participants']);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];

        if (empty($name) || empty($description) || $dailyAmount <= 0 || empty($startDate) || empty($endDate)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'danger';
        } elseif (strtotime($startDate) >= strtotime($endDate)) {
            $message = 'End date must be after start date.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO challenges (name, description, daily_amount, max_participants, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $dailyAmount, $maxParticipants, $startDate, $endDate, $_SESSION['user_id']]);

                $message = 'Challenge created successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error creating challenge: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Get materials for display
$materials = [];
try {
    $stmt = $pdo->query("SELECT * FROM materials ORDER BY created_at DESC");
    $materials = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently for now
}

// Get challenges for display
$challenges = [];
try {
    $stmt = $pdo->query("SELECT * FROM challenges ORDER BY created_at DESC LIMIT 5");
    $challenges = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently for now
}

// Get statistics
$stats = [
    'users' => 0,
    'challenges' => 0,
    'materials' => 0,
    'orders' => 0
];

try {
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $stats['challenges'] = $pdo->query("SELECT COUNT(*) FROM challenges WHERE status = 'active'")->fetchColumn();
    $stats['materials'] = $pdo->query("SELECT COUNT(*) FROM materials WHERE status = 'active'")->fetchColumn();
    $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM direct_purchases WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Building Challenges</title>
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
        
        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, var(--dark), var(--primary));
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-brand span {
            color: var(--secondary);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--secondary);
        }
        
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: var(--secondary);
        }
        
        .sidebar-menu i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 10px;
        }
        
        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 15px 0;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark);
            cursor: pointer;
            display: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Admin Content */
        .admin-content {
            padding: 0;
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
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: visible;
            }
            
            .sidebar .menu-text {
                display: none;
            }
            
            .sidebar-header .sidebar-brand span {
                display: none;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 280px;
            }
            
            .sidebar:hover .menu-text {
                display: inline;
            }
            
            .sidebar:hover .sidebar-brand span {
                display: inline;
            }
            
            .sidebar:hover .sidebar-menu i {
                margin-right: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: 280px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
            
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
        
        /* Statistics Button */
        .stats-toggle {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats-section {
            display: none;
        }
        
        .stats-section.active {
            display: block;
            animation: fadeInUp 0.5s ease;
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

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-shield-alt me-2"></i>Building<span>Admin</span>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li><a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a></li>
                
                <li><a href="#">
                    <i class="fas fa-tasks"></i>
                    <span class="menu-text">Challenges</span>
                </a></li>
                
                <li><a href="#">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Analytics</span>
                </a></li>
                
                <li><a href="#">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Settings</span>
                </a></li>
            </ul>
            
            <div class="sidebar-divider"></div>
            
            <ul>
                <li><a href="#challenges-section">
                    <i class="fas fa-plus-circle"></i>
                    <span class="menu-text">Create Challenge</span>
                </a></li>
                
                <li><a href="#materials-section">
                    <i class="fas fa-cubes"></i>
                    <span class="menu-text">Add Material</span>
                </a></li>
                
                <li><a href="#challenges-management">
                    <i class="fas fa-list"></i>
                    <span class="menu-text">Manage Challenges</span>
                </a></li>
                
                <li><a href="#materials-management">
                    <i class="fas fa-boxes"></i>
                    <span class="menu-text">Manage Materials</span>
                </a></li>
                
                <li><a href="#orders-management">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="menu-text">Orders</span>
                </a></li>
                
                <li><a href="#group-management">
                    <i class="fas fa-users-cog"></i>
                    <span class="menu-text">Group Registrations</span>
                </a></li>
                
                <li><a href="#payments-section">
                    <i class="fas fa-credit-card"></i>
                    <span class="menu-text">Payments</span>
                </a></li>
                
                <li><a href="#reports-section">
                    <i class="fas fa-chart-bar"></i>
                    <span class="menu-text">Reports</span>
                </a></li>
                
                <li><a href="#penalties-section">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="menu-text">Penalties</span>
                </a></li>
                
                <li><a href="#feedback-section">
                    <i class="fas fa-comments"></i>
                    <span class="menu-text">Feedback</span>
                </a></li>
                
                <li><a href="#users-management">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">User Management</span>
                </a></li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="dropdown">
                <a class="btn btn-outline-light btn-sm dropdown-toggle w-100" href="#" role="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-globe me-1"></i>Language
                </a>
                <ul class="dropdown-menu w-100" aria-labelledby="languageDropdown">
                    <li>
                        <form method="POST" class="d-inline w-100">
                            <input type="hidden" name="switch_language" value="1">
                            <input type="hidden" name="language" value="en">
                            <button type="submit" class="dropdown-item active">
                                <i class="fas fa-check me-2"></i>
                                English
                            </button>
                        </form>
                    </li>
                    <li>
                        <form method="POST" class="d-inline w-100">
                            <input type="hidden" name="switch_language" value="1">
                            <input type="hidden" name="language" value="sw">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-check me-2 invisible"></i>
                                Swahili
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            
            <div class="mt-3">
                <a href="#" class="btn btn-danger btn-sm w-100">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <button class="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="user-info">
                <div class="me-3">
                    <strong>Welcome, Admin</strong>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=1a5276&color=fff" alt="Admin">
                        <span class="ms-2">Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
            <div class="container-fluid">
                <!-- Header -->
                <div class="admin-header animate-on-scroll">
                    <h1 class="admin-title">Admin Control Panel</h1>
                    <p class="admin-subtitle">Manage challenges, materials, users, and orders</p>
                </div>

                <!-- Message Display -->
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> animate-on-scroll">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Statistics Toggle Button -->
                <div class="stats-toggle animate-on-scroll">
                    <button class="btn btn-admin" id="toggleStatsBtn">
                        <i class="fas fa-chart-bar me-2"></i> Show Statistics
                    </button>
                </div>

                <!-- Statistics Section (Hidden by Default) -->
                <div class="stats-section" id="statsSection">
                    <div class="stats-grid">
                        <div class="stat-card info animate-on-scroll">
                            <div class="stat-icon text-info">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value text-info">2</div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        
                        <div class="stat-card success animate-on-scroll">
                            <div class="stat-icon text-success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-value text-success">5</div>
                            <div class="stat-label">Active Challenges</div>
                        </div>
                        
                        <div class="stat-card warning animate-on-scroll">
                            <div class="stat-icon text-warning">
                                <i class="fas fa-cubes"></i>
                            </div>
                            <div class="stat-value text-warning">0</div>
                            <div class="stat-label">Materials</div>
                        </div>
                        
                        <div class="stat-card danger animate-on-scroll">
                            <div class="stat-icon text-danger">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value text-danger">0</div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                    </div>
                </div>

                <!-- Forms Section -->
                <div class="row" id="challenges-section">
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

                    <!-- Add/Edit Material -->
                    <div class="col-lg-6 mb-4" id="materials-section">
                        <div class="form-card animate-on-scroll">
                            <div class="form-header">
                                <h5 class="form-title"><i class="fas fa-cubes me-2"></i> <span id="form-title">Add Building Material</span></h5>
                            </div>
                            <div class="form-body">
                                <form method="POST" enctype="multipart/form-data" id="material-form">
                                    <input type="hidden" name="edit_material_id" id="edit_material_id" value="">
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
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="add_material" class="btn btn-success flex-fill" id="submit-btn">
                                            <i class="fas fa-plus me-2"></i> Add Material
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="cancel-edit" style="display: none;" onclick="cancelEdit()">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Management Sections -->
                <div class="row">
                    <!-- Challenges List -->
                    <div class="col-lg-6 mb-4" id="challenges-management">
                        <div class="form-card animate-on-scroll">
                            <div class="form-header">
                                <h5 class="form-title"><i class="fas fa-list me-2"></i> Manage Challenges</h5>
                            </div>
                            <div class="form-body">
                                <div class="admin-list-group list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">30-Day Savings Challenge</h6>
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                        <p class="mb-1 text-muted">Save money daily for 30 days to build your savings...</p>
                                        <small class="text-muted">
                                            <i class="fas fa-money-bill-wave me-1"></i>TSh 5,000.00 |
                                            <i class="fas fa-users me-1"></i>90 participants |
                                            <i class="fas fa-calendar me-1"></i>Jun 01, 2023 - Jun 30, 2023
                                        </small>
                                        <div class="mt-2 d-flex gap-2">
                                            <button class="btn btn-outline-primary btn-action flex-fill">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <a href="#" class="btn btn-outline-info btn-action flex-fill">
                                                <i class="fas fa-users me-1"></i> Members
                                            </a>
                                            <button class="btn btn-outline-danger btn-action flex-fill">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Home Construction Challenge</h6>
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                        <p class="mb-1 text-muted">Group challenge to save for home construction materials...</p>
                                        <small class="text-muted">
                                            <i class="fas fa-money-bill-wave me-1"></i>TSh 10,000.00 |
                                            <i class="fas fa-users me-1"></i>45 participants |
                                            <i class="fas fa-calendar me-1"></i>May 15, 2023 - Aug 15, 2023
                                        </small>
                                        <div class="mt-2 d-flex gap-2">
                                            <button class="btn btn-outline-primary btn-action flex-fill">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <a href="#" class="btn btn-outline-info btn-action flex-fill">
                                                <i class="fas fa-users me-1"></i> Members
                                            </a>
                                            <button class="btn btn-outline-danger btn-action flex-fill">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Materials List -->
                    <div class="col-lg-6 mb-4" id="materials-management">
                        <div class="form-card animate-on-scroll">
                            <div class="form-header">
                                <h5 class="form-title"><i class="fas fa-cubes me-2"></i> Manage Materials</h5>
                            </div>
                            <div class="form-body">
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
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($material['description']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-money-bill-wave me-1"></i>TSh <?php echo number_format($material['price'], 2); ?> |
                                            <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($material['created_at'])); ?>
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
                <div class="form-card animate-on-scroll mb-4" id="orders-management">
                    <div class="form-header">
                        <h5 class="form-title"><i class="fas fa-shopping-cart me-2"></i> Direct Purchase Orders</h5>
                    </div>
                    <div class="form-body">
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No direct purchase orders yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Group Registrations Management -->
                <div class="form-card animate-on-scroll mb-4" id="group-management">
                    <div class="form-header">
                        <h5 class="form-title"><i class="fas fa-users-cog me-2"></i> Group Registrations Management</h5>
                    </div>
                    <div class="form-body">
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No pending group registrations.</p>
                        </div>
                    </div>
                </div>

                <!-- Payments Overview Section -->
                <div class="form-card animate-on-scroll mb-4" id="payments-section">
                    <div class="form-header">
                        <h5 class="form-title"><i class="fas fa-credit-card me-2"></i> Payments Overview</h5>
                    </div>
                    <div class="form-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card success">
                                    <div class="stat-icon text-success">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="stat-value text-success">TSh 0.00</div>
                                    <div class="stat-label">Total Payments</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card info">
                                    <div class="stat-icon text-info">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="stat-value text-info">TSh 0.00</div>
                                    <div class="stat-label">Today's Payments</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card warning">
                                    <div class="stat-icon text-warning">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div class="stat-value text-warning">TSh 0.00</div>
                                    <div class="stat-label">Challenge Payments</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card primary">
                                    <div class="stat-icon text-primary">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="stat-value text-primary">TSh 0.00</div>
                                    <div class="stat-label">Purchase Payments</div>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-3">Recent Payments</h6>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No payments recorded yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Reports Section -->
                <div class="form-card animate-on-scroll mb-4" id="reports-section">
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
                                        <a href="#" class="btn btn-success w-100">
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
                                        <a href="#" class="btn btn-primary w-100">
                                            <i class="fas fa-user-friends me-2"></i>Generate Users Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penalty Management -->
                <div class="form-card animate-on-scroll mb-4" id="penalties-section">
                    <div class="form-header">
                        <h5 class="form-title"><i class="fas fa-exclamation-triangle me-2"></i> Penalty Management</h5>
                    </div>
                    <div class="form-body">
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No active penalties found.</p>
                        </div>
                    </div>
                </div>

                <!-- Feedback Management -->
                <div class="form-card animate-on-scroll mb-4" id="feedback-section">
                    <div class="form-header">
                        <h5 class="form-title"><i class="fas fa-comments me-2"></i> Feedback Management</h5>
                    </div>
                    <div class="form-body">
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No feedback received yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Users Management -->
                <div class="form-card animate-on-scroll" id="users-management">
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
                                    <tr>
                                        <td><strong>#1</strong></td>
                                        <td>john_doe</td>
                                        <td>john@example.com</td>
                                        <td>+255 123 456 789</td>
                                        <td>123456789012345678</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-user me-1"></i>
                                                User
                                            </span>
                                        </td>
                                        <td>Jun 01, 2023</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-outline-primary btn-action">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-warning btn-action">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>#2</strong></td>
                                        <td>jane_smith</td>
                                        <td>jane@example.com</td>
                                        <td>+255 987 654 321</td>
                                        <td>987654321098765432</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-user me-1"></i>
                                                User
                                            </span>
                                        </td>
                                        <td>Jun 05, 2023</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-outline-primary btn-action">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-warning btn-action">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSidebar = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const toggleStatsBtn = document.getElementById('toggleStatsBtn');
            const statsSection = document.getElementById('statsSection');
            
            // Toggle sidebar on mobile
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Toggle statistics section
            if (toggleStatsBtn && statsSection) {
                toggleStatsBtn.addEventListener('click', function() {
                    statsSection.classList.toggle('active');
                    if (statsSection.classList.contains('active')) {
                        toggleStatsBtn.innerHTML = '<i class="fas fa-chart-bar me-2"></i> Hide Statistics';
                    } else {
                        toggleStatsBtn.innerHTML = '<i class="fas fa-chart-bar me-2"></i> Show Statistics';
                    }
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(event.target) && 
                    !event.target.closest('.toggle-sidebar') &&
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Animation on scroll
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
            
            // Smooth scrolling for sidebar links
            document.querySelectorAll('.sidebar-menu a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                        
                        // Close sidebar on mobile after clicking
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('active');
                        }
                    }
                });
            });
        });

        // Sample functions for demonstration
        function editChallenge(id, name, description, dailyAmount, maxParticipants, startDate, endDate) {
            alert('Edit challenge: ' + name);
        }

        function deleteChallenge(id) {
            if (confirm('Are you sure you want to delete this challenge? This action cannot be undone.')) {
                alert('Challenge deleted');
            }
        }

        function editMaterial(id, name, description, price, status) {
            // Populate the form with material data
            document.getElementById('edit_material_id').value = id;
            document.getElementById('material_name').value = name;
            document.getElementById('material_description').value = description;
            document.getElementById('material_price').value = price;

            // Update form title and button
            document.getElementById('form-title').textContent = 'Edit Building Material';
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i> Update Material';
            document.getElementById('submit-btn').name = 'edit_material';

            // Show cancel button
            document.getElementById('cancel-edit').style.display = 'block';

            // Scroll to form
            document.getElementById('materials-section').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEdit() {
            // Reset the form
            document.getElementById('material-form').reset();
            document.getElementById('edit_material_id').value = '';

            // Reset form title and button
            document.getElementById('form-title').textContent = 'Add Building Material';
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-plus me-2"></i> Add Material';
            document.getElementById('submit-btn').name = 'add_material';

            // Hide cancel button
            document.getElementById('cancel-edit').style.display = 'none';
        }

        function deleteMaterial(id) {
            if (confirm('Are you sure you want to delete this material? This action cannot be undone.')) {
                // Create a form to submit delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'material_id';
                inputId.value = id;

                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_material';
                inputDelete.value = '1';

                form.appendChild(inputId);
                form.appendChild(inputDelete);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>