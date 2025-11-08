<?php
require_once 'config.php';
require_once 'core/translation.php';

// Fetch active materials
$stmt = $pdo->prepare("SELECT id, name, description, image, price, sw_name, sw_description FROM materials WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Your Dreams With Mjengo Challenge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a5276;
            --secondary: #f39c12;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: var(--light);
            color: var(--dark);
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
            background: rgba(26, 82, 118, 0.7);
        }
        
        /* Navigation */
        .navbar {
            background: rgba(44, 62, 80, 0.9) !important;
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
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            color: white;
            padding: 120px 0 80px;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            animation: fadeInDown 1s ease;
        }
        
        .hero h1 span {
            color: var(--secondary);
        }
        
        .hero p {
            font-size: 1.4rem;
            margin-bottom: 30px;
            max-width: 600px;
            animation: fadeInUp 1s ease 0.3s both;
        }
        
        .hero-btns {
            animation: fadeInUp 1s ease 0.6s both;
        }
        
        .btn-primary {
            background: var(--secondary);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
        }
        
        .btn-primary:hover {
            background: #e67e22;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.6);
        }
        
        .btn-outline-light {
            border: 2px solid white;
            color: white;
            padding: 10px 28px;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        /* Floating Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: float 4s ease-in-out infinite;
        }
        
        /* Features Section */
        .features {
            padding: 100px 0;
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
        
        .feature-box {
            text-align: center;
            padding: 40px 30px;
            border-radius: 15px;
            transition: all 0.4s ease;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 100%;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .feature-box::before {
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
        
        .feature-box:hover::before {
            transform: scaleX(1);
        }
        
        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 25px;
            color: var(--primary);
            display: inline-block;
            transition: all 0.4s ease;
        }
        
        .feature-box:hover .feature-icon {
            color: var(--secondary);
            transform: scale(1.1);
        }
        
        .feature-box h4 {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .feature-box p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Materials Section */
        .materials {
            padding: 100px 0;
            background: rgba(26, 82, 118, 0.8);
            color: white;
            position: relative;
        }

        .materials-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .materials-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .materials-title p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .material-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
        }

        .material-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .material-card .card-img-top {
            transition: transform 0.4s ease;
        }

        .material-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .material-card .card-body {
            padding: 20px;
        }

        .material-card .card-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .material-card .card-text {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .price-badge {
            background: var(--secondary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .material-item {
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .material-item:hover {
            transform: translateY(-10px);
        }

        .material-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: var(--secondary);
            transition: all 0.3s ease;
        }

        .material-item:hover .material-icon {
            background: var(--secondary);
            color: white;
            transform: rotateY(180deg);
        }

        .material-item h4 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Materials Carousel */
        .materials-carousel-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }

        .materials-carousel {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .material-slide {
            min-width: 100%;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .material-slide.active {
            display: flex;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .carousel-arrow:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-prev {
            left: 20px;
        }

        .carousel-next {
            right: 20px;
        }

        .carousel-indicators {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: var(--secondary);
            transform: scale(1.2);
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: rgba(255, 255, 255, 0.9);
            position: relative;
        }
        
        .steps {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .step-number {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-right: 30px;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .step-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            flex-grow: 1;
        }
        
        .step-content h4 {
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: rgba(26, 82, 118, 0.9);
            color: white;
            text-align: center;
            position: relative;
        }
        
        .cta h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 70px 0 20px;
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
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
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
            <a class="navbar-brand" href="#">
                <i class="fas fa-hard-hat me-2"></i>MJENGO<span>CHALLENGE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="#"><?php echo __('home'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="challenges.php"><?php echo __('challenges'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="lipa_kidogo.php"><?php echo __('lipa_kidogo'); ?></a></li>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1><?php echo __('build_your_dreams'); ?> <span><?php echo __('mjengo_challenge'); ?></span></h1>
                        <p><?php echo __('hero_description'); ?></p>
                        <div class="hero-btns">
                            <a href="challenges.php" class="btn btn-primary btn-lg me-3 floating"><?php echo __('view_challenges'); ?></a>
                            <a href="direct_purchase.php" class="btn btn-outline-light btn-lg"><?php echo __('direct_purchase_btn'); ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <div class="floating">
                            <i class="fas fa-tools" style="font-size: 15rem; color: rgba(255,255,255,0.2);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2><?php echo __('why_choose_mjengo'); ?></h2>
                <p class="lead"><?php echo __('we_revolutionize'); ?></p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-box animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <h4><?php echo __('daily_deliveries'); ?></h4>
                        <p><?php echo __('daily_deliveries_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h4><?php echo __('flexible_payments'); ?></h4>
                        <p><?php echo __('flexible_payments_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4><?php echo __('quality_guaranteed'); ?></h4>
                        <p><?php echo __('quality_guaranteed_desc'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Materials Section -->
    <section class="materials">
        <div class="container">
            <div class="materials-title">
                <h2><?php echo __('building_materials_offer'); ?></h2>
                <p><?php echo __('materials_description'); ?></p>
            </div>

            <?php if (empty($materials)): ?>
                <div class="text-center">
                    <div class="material-item animate-on-scroll">
                        <div class="material-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h4><?php echo __('no_materials_available'); ?></h4>
                        <p><?php echo __('materials_available_soon'); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="materials-carousel-container">
                    <div class="materials-carousel">
                        <?php foreach ($materials as $index => $material): ?>
                            <div class="material-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                                <div class="material-card animate-on-scroll">
                                    <?php if (!empty($material['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($material['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($material['name']); ?>" style="height: 300px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo htmlspecialchars(getMaterialName($material)); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars(substr(getMaterialDescription($material), 0, 150)); ?><?php echo strlen(getMaterialDescription($material)) > 150 ? '...' : ''; ?></p>
                                        <div class="price-badge mb-3">
                                            TSh <?php echo number_format($material['price'], 2); ?>
                                        </div>
                                        <a href="direct_purchase.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-shopping-cart me-2"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Navigation Arrows -->
                    <button class="carousel-arrow carousel-prev" id="prevMaterial">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-arrow carousel-next" id="nextMaterial">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <!-- Indicators -->
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($materials); $i++): ?>
                            <span class="indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2><?php echo __('how_mjengo_works'); ?></h2>
                <p class="lead"><?php echo __('simple_steps'); ?></p>
            </div>
            <div class="steps">
                <div class="step animate-on-scroll">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4><?php echo __('choose_your_challenge'); ?></h4>
                        <p><?php echo __('choose_challenge_desc'); ?></p>
                    </div>
                </div>
                <div class="step animate-on-scroll">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4><?php echo __('make_regular_payments'); ?></h4>
                        <p><?php echo __('regular_payments_desc'); ?></p>
                    </div>
                </div>
                <div class="step animate-on-scroll">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4><?php echo __('receive_daily_deliveries'); ?></h4>
                        <p><?php echo __('daily_deliveries_desc'); ?></p>
                    </div>
                </div>
                <div class="step animate-on-scroll">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4><?php echo __('complete_your_project'); ?></h4>
                        <p><?php echo __('complete_project_desc'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Start Your Construction Project?</h2>
            <p>Join thousands of satisfied customers who have transformed their building experience with Mjengo Challenge</p>
            <a href="register.php" class="btn btn-primary btn-lg">Get Started Today</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">MJENGO<span>CHALLENGE</span></div>
                    <p>Your trusted partner for building material challenges and daily deliveries. Building dreams, one challenge at a time.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a href="#">Home</a></li>
                            <li><a href="challenges.php">Challenges</a></li>
                            <li><a href="lipa_kidogo.php">Lipa Kidogo</a></li>
                            <li><a href="direct_purchase.php">Direct Purchase</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5>Account</h5>
                        <ul>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                            <li><a href="#">My Dashboard</a></li>
                            <li><a href="#">Payment History</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-links">
                        <h5>Contact Us</h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> Dar es salaam, Tanzania</li>
                            <li><i class="fas fa-phone me-2"></i> +255 714 859 934</li>
                            <li><i class="fas fa-envelope me-2"></i> chrisbetuelmlay@oweru.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Mjengo Challenge. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                    }
                });
            }, { threshold: 0.1 });

            animatedElements.forEach(element => {
                observer.observe(element);
            });

            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.background = 'rgba(44, 62, 80, 0.95)';
                    navbar.style.padding = '10px 0';
                } else {
                    navbar.style.background = 'rgba(44, 62, 80, 0.9)';
                    navbar.style.padding = '15px 0';
                }
            });

            // Add active class to nav links based on scroll position
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');

            window.addEventListener('scroll', function() {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (scrollY >= (sectionTop - 100)) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href').substring(1) === current) {
                        link.classList.add('active');
                    }
                });
            });

            // Materials Carousel Functionality
            let currentSlide = 0;
            const slides = document.querySelectorAll('.material-slide');
            const indicators = document.querySelectorAll('.indicator');
            const totalSlides = slides.length;

            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                indicators.forEach(indicator => indicator.classList.remove('active'));

                slides[index].classList.add('active');
                indicators[index].classList.add('active');
                currentSlide = index;
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            }

            function prevSlide() {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
            }

            // Event listeners for carousel controls
            document.getElementById('nextMaterial').addEventListener('click', nextSlide);
            document.getElementById('prevMaterial').addEventListener('click', prevSlide);

            // Event listeners for indicators
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => showSlide(index));
            });

            // Auto-play carousel (optional)
            setInterval(nextSlide, 3000); // Change slide every 3 seconds
        });
    </script>
</body>
</html>