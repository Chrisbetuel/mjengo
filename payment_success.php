<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get success parameters
$type = $_GET['type'] ?? '';
$amount = $_GET['amount'] ?? 0;

// Validate parameters
if (!$type || !$amount) {
    $_SESSION['error'] = __('invalid_payment_data');
    redirect('dashboard.php');
}

$amount = floatval($amount);

// Set success message based on type
switch ($type) {
    case 'challenge':
        $title = __('challenge_payment_successful');
        $message = __('challenge_payment_completed');
        $icon = 'fas fa-trophy';
        $redirect_url = 'dashboard.php';
        $redirect_text = __('view_my_progress');
        break;

    case 'full_payment':
        $title = __('material_purchase_successful');
        $message = __('material_purchase_completed');
        $icon = 'fas fa-box-open';
        $redirect_url = 'dashboard.php';
        $redirect_text = __('view_my_purchases');
        break;

    case 'lipa_kidogo_installment':
        $title = __('installment_payment_successful');
        $message = __('installment_payment_completed');
        $icon = 'fas fa-credit-card';
        $redirect_url = 'dashboard.php';
        $redirect_text = __('view_installment_progress');
        break;

    default:
        $title = __('payment_successful');
        $message = __('payment_completed_successfully');
        $icon = 'fas fa-check-circle';
        $redirect_url = 'dashboard.php';
        $redirect_text = __('continue');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('payment_successful'); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .success-header {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 40px 30px;
            position: relative;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 2s infinite;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            animation: bounceIn 1s ease;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .success-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .success-body {
            padding: 40px 30px;
        }

        .payment-details {
            background: rgba(39, 174, 96, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--success);
        }

        .amount-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
            display: block;
            margin-bottom: 10px;
        }

        .amount-label {
            color: #666;
            font-size: 0.9rem;
        }

        .success-message {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #555;
        }

        .next-steps {
            background: rgba(26, 82, 118, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }

        .next-steps h6 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .next-steps ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .next-steps li {
            padding: 5px 0;
            color: #555;
        }

        .next-steps li i {
            color: var(--primary);
            margin-right: 10px;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
            width: 100%;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }

        .btn-secondary-custom {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }

        .btn-secondary-custom:hover {
            background: var(--primary);
            color: white;
        }

        .share-section {
            border-top: 1px solid #eee;
            padding-top: 30px;
            margin-top: 30px;
        }

        .share-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .share-facebook {
            background: #1877f2;
        }

        .share-twitter {
            background: #1da1f2;
        }

        .share-whatsapp {
            background: #25d366;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Confetti Animation */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f39c12;
            animation: confetti-fall 3s linear infinite;
        }

        .confetti:nth-child(odd) {
            background: #e74c3c;
            animation-delay: 1s;
        }

        .confetti:nth-child(3n) {
            background: #27ae60;
            animation-delay: 2s;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-header">
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <i class="<?php echo $icon; ?> success-icon"></i>
                <h2 class="success-title"><?php echo $title; ?></h2>
                <p class="success-subtitle"><?php echo __('congratulations'); ?>!</p>
            </div>

            <div class="success-body">
                <div class="payment-details">
                    <span class="amount-display">TSh <?php echo number_format($amount, 2); ?></span>
                    <span class="amount-label"><?php echo __('amount_paid'); ?></span>
                </div>

                <p class="success-message"><?php echo $message; ?></p>

                <div class="next-steps">
                    <h6><?php echo __('what_happens_next'); ?>?</h6>
                    <ul>
                        <li><i class="fas fa-check"></i><?php echo __('payment_confirmed'); ?></li>
                        <li><i class="fas fa-envelope"></i><?php echo __('receipt_sent'); ?></li>
                        <li><i class="fas fa-truck"></i><?php echo __('order_processing'); ?></li>
                        <li><i class="fas fa-star"></i><?php echo __('track_progress_dashboard'); ?></li>
                    </ul>
                </div>

                <a href="<?php echo $redirect_url; ?>" class="btn btn-success-custom">
                    <i class="fas fa-arrow-right me-2"></i><?php echo $redirect_text; ?>
                </a>

                <a href="dashboard.php" class="btn btn-secondary-custom">
                    <i class="fas fa-home me-2"></i><?php echo __('back_to_dashboard'); ?>
                </a>

                <div class="share-section">
                    <h6 class="share-title"><?php echo __('share_your_achievement'); ?></h6>
                    <div class="share-buttons">
                        <a href="#" class="share-btn share-facebook" onclick="shareOnFacebook()">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="share-btn share-twitter" onclick="shareOnTwitter()">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="share-btn share-whatsapp" onclick="shareOnWhatsApp()">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create confetti effect
        function createConfetti() {
            const confettiContainer = document.querySelector('.success-header');
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confettiContainer.appendChild(confetti);

                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }
        }

        // Trigger confetti on page load
        window.addEventListener('load', createConfetti);

        // Social sharing functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent("<?php echo __('just_completed_payment_mjengo'); ?>");
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
        }

        function shareOnTwitter() {
            const text = encodeURIComponent("<?php echo __('just_completed_payment_mjengo'); ?> #MjengoChallenge");
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }

        function shareOnWhatsApp() {
            const text = encodeURIComponent("<?php echo __('just_completed_payment_mjengo'); ?> " + window.location.href);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        // Auto redirect after 10 seconds
        setTimeout(() => {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 10000);
    </script>
</body>
</html>
