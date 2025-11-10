<?php
require_once '../config.php';

// Load translations
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);

if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Check for redirect parameter
$redirect = $_GET['redirect'] ?? '';
if ($redirect === 'group_registration') {
    // User came from group registration, show message
    $group_redirect_message = true;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone_number = sanitize($_POST['phone_number'] ?? '');
    $nida_id = sanitize($_POST['nida_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $accept_terms = isset($_POST['accept_terms']) ? true : false;

    if (empty($first_name) || empty($last_name) || empty($username) || empty($phone_number) || empty($nida_id) || empty($password) || empty($confirm_password)) {
        $errors[] = 'Please fill in all required fields.';
    } elseif (!$accept_terms) {
        $errors[] = $translations['terms_required'];
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } else {
        // Check if username, phone_number, or nida_id already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR phone_number = ? OR nida_id = ?");
        $stmt->execute([$username, $phone_number, $nida_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username, phone number, or NIDA ID already exists.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, phone_number, nida_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$first_name, $last_name, $username, $email, $phone_number, $nida_id, $hashed_password])) {
                if ($redirect === 'group_registration') {
                    redirect('register_group.php');
                } else {
                    $success = 'Registration successful! You can now <a href="login.php" class="alert-link">login</a>.';
                }
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5276;
            --secondary: #f39c12;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #3027aeff;
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
            max-width: 500px;
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
            background: linear-gradient(135deg, var(--success), #2ecc71);
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
            border-color: var(--success);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--success);
            font-size: 1.2rem;
            z-index: 5;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
            width: 100%;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        /* Password Strength Indicator */
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 8px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            background: #e74c3c;
            width: 33%;
        }
        
        .strength-medium {
            background: #f39c12;
            width: 66%;
        }
        
        .strength-strong {
            background: #27ae60;
            width: 100%;
        }
        
        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            text-align: right;
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
            color: var(--success);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .register-link:hover {
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .register-link i {
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
            color: var(--success);
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
        
        /* Form Validation */
        .is-invalid {
            border-color: #e74c3c !important;
        }
        
        .is-valid {
            border-color: #27ae60 !important;
        }
        
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #e74c3c;
        }
        
        .was-validated .form-control:invalid ~ .invalid-feedback,
        .was-validated .form-control:invalid ~ .invalid-tooltip,
        .form-control.is-invalid ~ .invalid-feedback,
        .form-control.is-invalid ~ .invalid-tooltip {
            display: block;
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
                    <h1 class="register-title">Join Us Today</h1>
                    <p class="register-subtitle">Create your <?php echo SITE_NAME; ?> account</p>
                </div>
                <div class="register-body">
                    <?php if (isset($group_redirect_message)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Group Registration:</strong> To register a group, you need to create an account first. After registration, you'll be redirected to complete your group registration.
                        </div>
                    <?php endif; ?>

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

                    <form method="POST" id="registrationForm" novalidate>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter your first name" required>
                            <div class="invalid-feedback">Please enter your first name.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter your last name" required>
                            <div class="invalid-feedback">Please enter your last name.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                            <div class="invalid-feedback">Please choose a username.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address (optional)">
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="Enter your phone number" required>
                            <div class="invalid-feedback">Please provide a valid phone number.</div>
                        </div>

                        <div class="input-group">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" class="form-control" id="nida_id" name="nida_id" placeholder="Enter your NIDA ID" required>
                            <div class="invalid-feedback">Please provide your NIDA ID.</div>
                        </div>
                        
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required minlength="6">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            <div class="password-strength">
                                <div class="strength-bar" id="passwordStrength"></div>
                            </div>
                            <div class="strength-text" id="passwordText">Password strength</div>
                        </div>
                        
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>

                        <!-- Terms and Conditions Checkbox -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" required>
                            <label class="form-check-label" for="accept_terms">
                                <?php echo $translations['accept_terms']; ?> <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none"><?php echo $translations['read_terms']; ?></a>
                            </label>
                            <div class="invalid-feedback"><?php echo $translations['terms_required']; ?></div>
                        </div>

                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </button>
                    </form>
                    
                    <div class="register-links">
                        <a href="login.php" class="register-link">
                            <i class="fas fa-sign-in-alt"></i> Already have an account? Login here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel"><?php echo $translations['terms_and_conditions']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($lang === 'sw'): ?>
                        <!-- Swahili Version -->
                        <div class="terms-section">
                            <h3>1. Utangulizi</h3>
                            <p>Karibu kwenye <?php echo SITE_NAME; ?>. Sheria na masharti haya yanafafanua sheria za matumizi ya tovuti yetu na huduma zetu. Kwa kutumia tovuti hii, unakubali kufuata sheria na masharti hizi.</p>
                        </div>

                        <div class="terms-section">
                            <h3>2. Matumizi ya Huduma</h3>
                            <p>Huduma zetu zinakusudiwa kusaidia watumiaji kupata vifaa vya ujenzi kupitia michuano ya malipo ya kila siku na mipango mingine ya malipo. Unakubali kutumia huduma hizi kwa madhumuni halali tu.</p>
                        </div>

                        <div class="terms-section">
                            <h3>3. Usajili na Akaunti</h3>
                            <p>Ili kutumia huduma zetu kamili, lazima ujisajili akaunti. Taarifa zote unazotoa lazima ziwe sahihi na za sasa. Wewe ndiye mwajibikaji wa kulinda usiri wa akaunti yako.</p>
                        </div>

                        <div class="terms-section">
                            <h3>4. Malipo na Malipo</h3>
                            <p>Malipo yanafanywa kupitia njia salama za malipo. Unakubali kulipa ada zote zinazohusiana na huduma unazochagua. Malipo ni ya mwisho na hayatolewi.</p>
                        </div>

                        <div class="terms-section">
                            <h3>5. Utoaji wa Vifaa</h3>
                            <p>Vifaa vya ujenzi hutolewa kulingana na mpango wa malipo uliochagua. Tunajitahidi kutoa vifaa kwa wakati, lakini hatuhakikishi utoaji wa wakati maalum.</p>
                        </div>

                        <div class="terms-section">
                            <h3>6. Kufuta na Kurejesha</h3>
                            <p>Unaweza kufuta akaunti yako wakati wowote. Baada ya kufuta, taarifa zako zote zitafutwa. Hakuna kurejesha kwa malipo yaliyofanywa.</p>
                        </div>

                        <div class="terms-section">
                            <h3>7. Ubora wa Vifaa</h3>
                            <p>Tunatoa vifaa vya ubora wa juu kutoka kwa wasambazaji wa kuaminika. Hata hivyo, hatuhakikishi ubora wa vifaa baada ya utoaji.</p>
                        </div>

                        <div class="terms-section">
                            <h3>8. Ulinzi wa Taarifa</h3>
                            <p>Tunazingatia sana ulinzi wa taarifa zako binafsi. Taarifa zako hutumiwa tu kwa madhumuni ya kutoa huduma zetu na hazitashirikiwa na wahusika wengine bila idhini yako.</p>
                        </div>

                        <div class="terms-section">
                            <h3>9. Marekebisho ya Sheria na Masharti</h3>
                            <p>Tuna haki ya kurekebisha sheria na masharti haya wakati wowote. Marekebisho yatatangazwa kwenye tovuti yetu.</p>
                        </div>

                        <div class="terms-section">
                            <h3>10. Mawasiliano</h3>
                            <p>Kwa maswali yoyote kuhusu sheria na masharti hizi, tafadhali wasiliana nasi kupitia anwani ya barua pepe au nambari ya simu iliyotolewa kwenye tovuti yetu.</p>
                        </div>
                    <?php else: ?>
                        <!-- English Version -->
                        <div class="terms-section">
                            <h3>1. Introduction</h3>
                            <p>Welcome to <?php echo SITE_NAME; ?>. These terms and conditions outline the rules for the use of our website and services. By using this website, you agree to comply with these terms and conditions.</p>
                        </div>

                        <div class="terms-section">
                            <h3>2. Use of Services</h3>
                            <p>Our services are intended to help users acquire building materials through daily payment challenges and other payment plans. You agree to use these services for lawful purposes only.</p>
                        </div>

                        <div class="terms-section">
                            <h3>3. Registration and Account</h3>
                            <p>To use our full services, you must register an account. All information you provide must be accurate and current. You are responsible for maintaining the confidentiality of your account.</p>
                        </div>

                        <div class="terms-section">
                            <h3>4. Payments and Billing</h3>
                            <p>Payments are made through secure payment methods. You agree to pay all fees associated with the services you choose. Payments are final and non-refundable.</p>
                        </div>

                        <div class="terms-section">
                            <h3>5. Material Delivery</h3>
                            <p>Building materials are delivered according to the payment plan you choose. We strive to deliver materials on time, but we do not guarantee delivery at specific times.</p>
                        </div>

                        <div class="terms-section">
                            <h3>6. Cancellation and Refunds</h3>
                            <p>You can delete your account at any time. After deletion, all your information will be removed. There are no refunds for payments made.</p>
                        </div>

                        <div class="terms-section">
                            <h3>7. Material Quality</h3>
                            <p>We provide high-quality materials from trusted suppliers. However, we do not guarantee the quality of materials after delivery.</p>
                        </div>

                        <div class="terms-section">
                            <h3>8. Privacy Protection</h3>
                            <p>We take the protection of your personal information very seriously. Your information is used only to provide our services and will not be shared with third parties without your consent.</p>
                        </div>

                        <div class="terms-section">
                            <h3>9. Amendments to Terms and Conditions</h3>
                            <p>We reserve the right to amend these terms and conditions at any time. Amendments will be announced on our website.</p>
                        </div>

                        <div class="terms-section">
                            <h3>10. Contact</h3>
                            <p>For any questions regarding these terms and conditions, please contact us via the email address or phone number provided on our website.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Agree</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
            
            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('passwordStrength');
                const strengthText = document.getElementById('passwordText');
                
                // Reset
                strengthBar.className = 'strength-bar';
                strengthBar.style.width = '0';
                
                if (password.length === 0) {
                    strengthText.textContent = 'Password strength';
                    return;
                }
                
                // Calculate strength
                let strength = 0;
                
                // Length check
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;
                
                // Character variety checks
                if (/[a-z]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
                
                // Update strength indicator
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = '#e74c3c';
                } else if (strength <= 4) {
                    strengthBar.classList.add('strength-medium');
                    strengthText.textContent = 'Medium password';
                    strengthText.style.color = '#f39c12';
                } else {
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = '#27ae60';
                }
            });
            
            // Form validation
            const form = document.getElementById('registrationForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Check if passwords match
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementById('confirm_password').classList.add('is-invalid');
                } else {
                    document.getElementById('confirm_password').classList.remove('is-invalid');
                }
                
                form.classList.add('was-validated');
            });
            
            // Real-time password match validation
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Auto-focus first name field
            document.getElementById('first_name').focus();
        });
    </script>
</body>
</html>