<?php
require_once '../config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Check for redirect
            $redirect = $_GET['redirect'] ?? '';
            $challenge_id = $_GET['challenge_id'] ?? '';
            $material_id = $_GET['material_id'] ?? '';

            if ($redirect === 'join_challenge' && $challenge_id) {
                redirect("../join_challenge.php?challenge_id=$challenge_id");
            } elseif ($redirect === 'lipa_kidogo' && $material_id) {
                redirect("../lipa_kidogo.php?material_id=$material_id");
            } elseif ($redirect === 'direct_purchase' && $material_id) {
                redirect("../direct_purchase.php?material_id=$material_id");
            } else {
                redirect('../dashboard.php');
            }
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
        
        /* Login Container */
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease;
        }
        
        .login-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            background: white;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
        }
        
        .login-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
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
        
        .btn-login {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
            width: 100%;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.6);
        }
        
        .btn-login:active {
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
        .login-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .login-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .login-link:hover {
            color: var(--secondary);
            transform: translateX(5px);
        }
        
        .login-link i {
            margin-right: 8px;
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--primary);
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
            .login-container {
                padding: 0 20px;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .login-header {
                padding: 25px;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
        }
        
        /* Floating Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: float 4s ease-in-out infinite;
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

    <!-- Login Container -->
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1 class="login-title">Welcome Back</h1>
                    <p class="login-subtitle">Sign in to your <?php echo SITE_NAME; ?> account</p>
                </div>
                <div class="login-body">
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

                    <form method="POST">
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email or username" required>
                        </div>
                        
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>
                    </form>
                    
                    <div class="login-links">
                        <a href="register.php" class="login-link">
                            <i class="fas fa-user-plus"></i> Don't have an account? Register here
                        </a>
                       
                         <a href="reset_password.php" 
   style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #007bff; font-weight: bold; font-size: 14px; transition: 0.3s;"
   onmouseover="this.style.color='#0056b3';"
   onmouseout="this.style.color='#007bff';">
   <i class="fas fa-calm" style="font-size: 16px;"></i> Reset Password
</a>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
            
            // Add focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
            
            // Auto-focus email field
            document.getElementById('email').focus();
            
            // Check for redirect parameters and show message
            const urlParams = new URLSearchParams(window.location.search);
            const redirect = urlParams.get('redirect');
            const challengeId = urlParams.get('challenge_id');
            const materialId = urlParams.get('material_id');
            
            if (redirect && (challengeId || materialId)) {
                let message = 'Please login to continue';
                
                if (redirect === 'join_challenge') {
                    message = 'Please login to join this challenge';
                } else if (redirect === 'lipa_kidogo') {
                    message = 'Please login to purchase materials in installments';
                } else if (redirect === 'direct_purchase') {
                    message = 'Please login to purchase materials';
                }
                
                // You could display this message in a subtle way
                console.log(message); // For now, just log it
            }
        });
    </script>
</body>
</html>