<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=payment_gateway');
}

// Get payment parameters
$type = $_GET['type'] ?? '';
$challenge_id = $_GET['challenge_id'] ?? null;
$material_id = $_GET['material_id'] ?? null;
$installment_id = $_GET['installment_id'] ?? null;
$amount = $_GET['amount'] ?? 0;

// Validate parameters
if (!$type || !$amount) {
    $_SESSION['error'] = __('invalid_payment_request');
    redirect('dashboard.php');
}

$amount = floatval($amount);

// Get payment details based on type
$payment_details = [];
$redirect_url = 'dashboard.php';

try {
    switch ($type) {
        case 'challenge':
            if (!$challenge_id) {
                throw new Exception(__('invalid_challenge_id'));
            }

            $stmt = $pdo->prepare("SELECT id, name, daily_amount FROM challenges WHERE id = ? AND status = 'active'");
            $stmt->execute([$challenge_id]);
            $challenge = $stmt->fetch();

            if (!$challenge) {
                throw new Exception(__('challenge_not_found'));
            }

            $payment_details = [
                'type' => 'challenge',
                'challenge_id' => $challenge_id,
                'challenge_name' => getChallengeName($challenge),
                'amount' => $amount,
                'description' => __('daily_challenge_payment') . ' - ' . getChallengeName($challenge)
            ];
            $redirect_url = "challenges.php";
            break;

        case 'full_payment':
            if (!$material_id) {
                throw new Exception(__('invalid_material_id'));
            }

            $stmt = $pdo->prepare("SELECT id, name, price FROM materials WHERE id = ? AND status = 'active'");
            $stmt->execute([$material_id]);
            $material = $stmt->fetch();

            if (!$material) {
                throw new Exception(__('material_not_found'));
            }

            $payment_details = [
                'type' => 'full_payment',
                'material_id' => $material_id,
                'material_name' => getMaterialName($material),
                'amount' => $amount,
                'description' => __('full_payment_for') . ' ' . getMaterialName($material)
            ];
            $redirect_url = "direct_purchase.php";
            break;

        case 'direct_purchase':
            $purchase_id = $_GET['purchase_id'] ?? null;
            if (!$purchase_id) {
                throw new Exception(__('invalid_purchase_id'));
            }

            $stmt = $pdo->prepare("
                SELECT dp.id, dp.total_amount, m.name, m.sw_name
                FROM direct_purchases dp
                JOIN materials m ON dp.material_id = m.id
                WHERE dp.id = ? AND dp.user_id = ? AND dp.status = 'pending'
            ");
            $stmt->execute([$purchase_id, $_SESSION['user_id']]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                throw new Exception(__('purchase_not_found'));
            }

            $payment_details = [
                'type' => 'direct_purchase',
                'purchase_id' => $purchase_id,
                'material_name' => getMaterialName($purchase),
                'amount' => $amount,
                'description' => __('direct_purchase_for') . ' ' . getMaterialName($purchase)
            ];
            $redirect_url = "direct_purchase.php";
            break;

        case 'lipa_kidogo_installment':
            if (!$installment_id) {
                throw new Exception(__('invalid_installment_id'));
            }

            $stmt = $pdo->prepare("
                SELECT li.id, li.amount, m.name, m.sw_name
                FROM lipa_kidogo_installments li
                JOIN materials m ON li.material_id = m.id
                WHERE li.id = ? AND li.user_id = ? AND li.status = 'pending'
            ");
            $stmt->execute([$installment_id, $_SESSION['user_id']]);
            $installment = $stmt->fetch();

            if (!$installment) {
                throw new Exception(__('installment_not_found'));
            }

            $payment_details = [
                'type' => 'lipa_kidogo_installment',
                'installment_id' => $installment_id,
                'material_name' => getMaterialName($installment),
                'amount' => $amount,
                'description' => __('installment_payment_for') . ' ' . getMaterialName($installment)
            ];
            $redirect_url = "dashboard.php";
            break;

        default:
            throw new Exception(__('invalid_payment_type'));
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect('dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('payment_gateway'); ?> - <?php echo SITE_NAME; ?></title>
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

        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .payment-header h2 {
            margin-bottom: 10px;
            font-weight: 700;
        }

        .payment-body {
            padding: 30px;
        }

        .payment-details {
            background: rgba(26, 82, 118, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }

        .amount-display {
            text-align: center;
            margin-bottom: 30px;
        }

        .amount-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }

        .amount-label {
            color: #666;
            font-size: 0.9rem;
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary);
            background: rgba(26, 82, 118, 0.05);
        }

        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(26, 82, 118, 0.1);
        }

        .payment-method-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            color: var(--primary);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.6);
        }

        .btn-back {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary);
            color: white;
        }

        .security-notice {
            background: rgba(243, 156, 18, 0.1);
            border: 1px solid rgba(243, 156, 18, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }

        .security-notice i {
            color: var(--secondary);
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-credit-card fa-3x mb-3"></i>
                <h2><?php echo __('secure_payment'); ?></h2>
                <p><?php echo __('complete_your_payment_securely'); ?></p>
            </div>

            <div class="payment-body">
                <div class="payment-details">
                    <h5><?php echo __('payment_details'); ?></h5>
                    <p><strong><?php echo __('description'); ?>:</strong> <?php echo htmlspecialchars($payment_details['description']); ?></p>
                    <p><strong><?php echo __('type'); ?>:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment_details['type'])); ?></p>
                </div>

                <div class="amount-display">
                    <span class="amount-value">TSh <?php echo number_format($payment_details['amount'], 2); ?></span>
                    <span class="amount-label"><?php echo __('total_amount'); ?></span>
                </div>

                <form id="paymentForm" method="POST" action="process_payment.php">
                    <input type="hidden" name="payment_type" value="<?php echo $type; ?>">
                    <input type="hidden" name="amount" value="<?php echo $payment_details['amount']; ?>">

                    <?php if (isset($payment_details['challenge_id'])): ?>
                        <input type="hidden" name="challenge_id" value="<?php echo $payment_details['challenge_id']; ?>">
                    <?php endif; ?>

                    <?php if (isset($payment_details['material_id'])): ?>
                        <input type="hidden" name="material_id" value="<?php echo $payment_details['material_id']; ?>">
                    <?php endif; ?>

                    <?php if (isset($payment_details['installment_id'])): ?>
                        <input type="hidden" name="installment_id" value="<?php echo $payment_details['installment_id']; ?>">
                    <?php endif; ?>

                    <?php if (isset($payment_details['purchase_id'])): ?>
                        <input type="hidden" name="purchase_id" value="<?php echo $payment_details['purchase_id']; ?>">
                    <?php endif; ?>

                    <div class="payment-methods">
                        <div class="payment-method selected" onclick="selectPaymentMethod('mpesa')">
                            <i class="fas fa-mobile-alt payment-method-icon"></i>
                            <div>
                                <strong>M-Pesa</strong>
                                <br>
                                <small><?php echo __('pay_with_mpesa'); ?></small>
                            </div>
                            <input type="radio" name="payment_method" value="mpesa" checked style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('card')">
                            <i class="fas fa-credit-card payment-method-icon"></i>
                            <div>
                                <strong><?php echo __('credit_debit_card'); ?></strong>
                                <br>
                                <small><?php echo __('visa_mastercard'); ?></small>
                            </div>
                            <input type="radio" name="payment_method" value="card" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('bank')">
                            <i class="fas fa-university payment-method-icon"></i>
                            <div>
                                <strong><?php echo __('bank_transfer'); ?></strong>
                                <br>
                                <small><?php echo __('direct_bank_transfer'); ?></small>
                            </div>
                            <input type="radio" name="payment_method" value="bank" style="display: none;">
                        </div>
                    </div>

                    <button type="submit" class="btn-pay">
                        <i class="fas fa-lock me-2"></i><?php echo __('pay_now_securely'); ?>
                    </button>
                </form>

                <a href="<?php echo $redirect_url; ?>" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i><?php echo __('back'); ?>
                </a>

                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    <strong><?php echo __('secure_payment'); ?>:</strong> <?php echo __('your_payment_is_protected'); ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');

            // Check the radio button
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
        }

        // Auto-submit form for demo purposes (remove in production)
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('.btn-pay');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo __('processing'); ?>...';
            submitBtn.disabled = true;

            // Simulate payment processing
            setTimeout(() => {
                // Redirect to success page (you would normally handle this server-side)
                window.location.href = 'payment_success.php?type=<?php echo $type; ?>&amount=<?php echo $payment_details['amount']; ?>';
            }, 2000);
        });
    </script>
</body>
</html>
