<?php
require_once 'config.php';
require_once 'core/language.php';
require_once 'core/translation.php';

// Fetch active materials
$stmt = $pdo->prepare("SELECT id, name, sw_name, description, sw_description, image, price FROM materials WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lipa Kidogo Kidogo - <?php echo SITE_NAME; ?></title>
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
        
        /* Page Header */
        .page-header {
            background: rgba(26, 82, 118, 0.9);
            color: white;
            padding: 100px 0 60px;
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,192C1248,192,1344,128,1392,96L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center bottom;
        }
        
        .page-header-content {
            position: relative;
            z-index: 1;
        }
        
        .page-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            animation: fadeInDown 1s ease;
        }
        
        .page-subtitle {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.3s both;
        }
        
        /* Material Cards */
        .materials-container {
            padding: 0 0 80px;
        }
        
        .material-card {
            transition: all 0.4s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
            background: white;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
        }
        
        .material-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .material-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .material-card .card-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
        }
        
        .material-card .card-title {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .material-card .card-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .price-badge {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.2rem;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-buy {
            background: linear-gradient(135deg, var(--secondary), #e67e22);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
            width: 100%;
        }
        
        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.6);
        }
        
        .btn-login {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            background: var(--primary);
            color: white;
        }
        
        .material-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--secondary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        /* How It Works Section */
        .how-it-works {
            padding: 80px 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--secondary);
        }
        
        .step-card {
            text-align: center;
            padding: 40px 25px;
            border-radius: 15px;
            transition: all 0.4s ease;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--secondary);
            transform: scaleX(0);
            transition: transform 0.4s ease;
            z-index: -1;
        }
        
        .step-card:hover::before {
            transform: scaleX(1);
        }
        
        .step-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .step-icon {
            font-size: 3.5rem;
            margin-bottom: 25px;
            color: var(--primary);
            display: inline-block;
            transition: all 0.4s ease;
        }
        
        .step-card:hover .step-icon {
            color: var(--secondary);
            transform: scale(1.1);
        }
        
        .step-card h5 {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .step-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 50px 0;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #777;
            max-width: 500px;
            margin: 0 auto 30px;
        }
        
        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 1.4rem;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
        }
        
        .footer-logo span {
            color: var(--secondary);
        }
        
        .footer-links h5 {
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        .footer-links h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--secondary);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #bbb;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--secondary);
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            margin-top: 20px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bbb;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
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
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .page-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
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
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hard-hat me-2"></i>MJENGO<span>CHALLENGE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><?php echo __('home'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="challenges.php"><?php echo __('challenges'); ?></a></li>
                    <li class="nav-item"><a class="nav-link active" href="lipa_kidogo.php"><?php echo __('lipa_kidogo'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="direct_purchase.php"><?php echo __('direct_purchase'); ?></a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><?php echo __('dashboard'); ?></a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php"><?php echo __('admin'); ?></a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><?php echo __('logout'); ?></a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><?php echo __('login'); ?></a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="register.php"><?php echo __('register'); ?></a></li>
                    <?php endif; ?>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title"><?php echo __('lipa_kidogo_title'); ?></h1>
                <p class="page-subtitle"><?php echo __('lipa_kidogo_subtitle'); ?></p>
            </div>
        </div>
    </section>

    <!-- Materials Section -->
    <section class="materials-container">
        <div class="container">
            <?php if (empty($materials)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3><?php echo __('no_materials_available'); ?></h3>
                    <p><?php echo __('materials_available_soon'); ?></p>
                    <a href="index.php" class="btn btn-primary btn-lg"><?php echo __('back_to_home'); ?></a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($materials as $material): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card material-card h-100">
                                <?php if (!empty($material['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($material['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($material['name']); ?>" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="material-badge">Installments</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars(getMaterialName($material)); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(getMaterialDescription($material)); ?></p>

                                    <div class="mt-auto text-center">
                                        <div class="price-badge mb-3">
                                            TSh <?php echo number_format($material['price'], 2); ?>
                                        </div>

                                        <?php if (isLoggedIn()): ?>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-secondary" onclick="selectMaterial(<?php echo $material['id']; ?>, '<?php echo addslashes($material['name']); ?>', <?php echo $material['price']; ?>)">
                                                    <i class="fas fa-shopping-cart me-2"></i> Buy in Installments
                                                </button>
                                                <a href="payment_gateway.php?type=full_payment&material_id=<?php echo $material['id']; ?>&amount=<?php echo $material['price']; ?>" class="btn btn-danger">
                                                    <i class="fas fa-credit-card me-2"></i> Pay Now
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <a href="login.php?redirect=lipa_kidogo&material_id=<?php echo $material['id']; ?>" class="btn-login">
                                                <i class="fas fa-sign-in-alt me-2"></i> Login to Buy
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2><?php echo __('how_lipa_kidogo_works'); ?></h2>
                <p class="lead"><?php echo __('simple_steps_flexible_payments'); ?></p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="step-card animate-on-scroll">
                        <div class="step-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h5><?php echo __('choose_material'); ?></h5>
                        <p><?php echo __('choose_material_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-card animate-on-scroll">
                        <div class="step-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h5><?php echo __('pay_in_installments'); ?></h5>
                        <p><?php echo __('pay_in_installments_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-card animate-on-scroll">
                        <div class="step-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h5><?php echo __('get_your_materials'); ?></h5>
                        <p><?php echo __('get_your_materials_desc'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Material Selection Modal -->
    <div class="modal fade" id="materialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buy in Installments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="materialDetails"></div>
                    <form id="installmentForm" method="POST" action="process_lipa_kidogo.php">
                        <input type="hidden" id="selectedMaterialId" name="material_id">
                        <div class="mb-3">
                            <label for="installmentAmount" class="form-label">Daily Installment Amount (TSh)</label>
                            <input type="number" class="form-control" id="installmentAmount" name="installment_amount" step="0.01" min="1" required>
                            <div class="form-text">Enter the amount you can pay daily</div>
                        </div>
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-credit-card me-2"></i> Pay Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo"><?php echo SITE_NAME; ?></div>
                    <p><?php echo __('trusted_partner'); ?></p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('quick_links'); ?></h5>
                        <ul>
                            <li><a href="index.php"><?php echo __('home'); ?></a></li>
                            <li><a href="challenges.php"><?php echo __('challenges'); ?></a></li>
                            <li><a href="lipa_kidogo.php"><?php echo __('lipa_kidogo'); ?></a></li>
                            <li><a href="direct_purchase.php"><?php echo __('direct_purchase'); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('account'); ?></h5>
                        <ul>
                            <li><a href="login.php"><?php echo __('login'); ?></a></li>
                            <li><a href="register.php"><?php echo __('register'); ?></a></li>
                            <li><a href="dashboard.php"><?php echo __('my_dashboard'); ?></a></li>
                            <li><a href="#"><?php echo __('payment_history'); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5><?php echo __('contact_us'); ?></h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> <?php echo __('dar_es_salaam'); ?></li>
                            <li><i class="fas fa-phone me-2"></i> <?php echo __('phone'); ?></li>
                            <li><i class="fas fa-envelope me-2"></i> <?php echo __('email'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 <?php echo SITE_NAME; ?>. <?php echo __('all_rights_reserved'); ?></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation on scroll for material cards and step cards
        document.addEventListener('DOMContentLoaded', function() {
            const materialCards = document.querySelectorAll('.material-card');
            const stepCards = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        // Stagger the animation for each card
                        setTimeout(() => {
                            entry.target.classList.add('animated');
                        }, index * 200);
                    }
                });
            }, { threshold: 0.1 });
            
            materialCards.forEach(card => {
                observer.observe(card);
            });
            
            stepCards.forEach(card => {
                observer.observe(card);
            });
            
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

        function selectMaterial(id, name, price) {
            document.getElementById('selectedMaterialId').value = id;
            document.getElementById('materialDetails').innerHTML = `
                <div class="alert alert-info border-0 rounded-3 mb-4" style="background: rgba(26, 82, 118, 0.1); border-left: 4px solid var(--primary) !important;">
                    <h6 class="fw-bold">${name}</h6>
                    <p class="mb-1">Total Price: <strong>TSh ${price.toLocaleString()}</strong></p>
                    <p class="mb-0">You can pay this amount in daily installments of your choice.</p>
                </div>
            `;

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            document.getElementById('startDate').min = today;

            // Set suggested installment amount (5% of total price)
            const suggestedAmount = Math.max(100, Math.round(price * 0.05));
            document.getElementById('installmentAmount').value = suggestedAmount;
            document.getElementById('installmentAmount').min = Math.max(1, Math.round(price * 0.01));
            document.getElementById('installmentAmount').max = price;

            new bootstrap.Modal(document.getElementById('materialModal')).show();
        }
    </script>
</body>
</html>