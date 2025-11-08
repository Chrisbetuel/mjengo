<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = __('login_required');
    redirect('login.php');
}

// Get POST data
$payment_type = $_POST['payment_type'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';

// Validate required fields
if (!$payment_type || !$amount || !$payment_method) {
    $_SESSION['error'] = __('invalid_payment_data');
    redirect('dashboard.php');
}

try {
    $pdo->beginTransaction();

    switch ($payment_type) {
        case 'challenge':
            $challenge_id = $_POST['challenge_id'] ?? null;
            if (!$challenge_id) {
                throw new Exception(__('invalid_challenge_id'));
            }

            // Verify challenge exists and is active
            $stmt = $pdo->prepare("SELECT id, daily_amount FROM challenges WHERE id = ? AND status = 'active'");
            $stmt->execute([$challenge_id]);
            $challenge = $stmt->fetch();

            if (!$challenge) {
                throw new Exception(__('challenge_not_found'));
            }

            // Check if user is a participant
            $stmt = $pdo->prepare("SELECT id FROM participants WHERE challenge_id = ? AND user_id = ? AND status = 'active'");
            $stmt->execute([$challenge_id, $_SESSION['user_id']]);
            $participant = $stmt->fetch();

            if (!$participant) {
                throw new Exception(__('not_a_participant'));
            }

            // Record the payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, challenge_id, amount, payment_method, status, payment_date)
                VALUES (?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $challenge_id, $amount, $payment_method]);

            $payment_id = $pdo->lastInsertId();

            // Log the transaction
            $stmt = $pdo->prepare("
                INSERT INTO transaction_log (user_id, payment_id, action, details, created_at)
                VALUES (?, ?, 'payment', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $payment_id, "Challenge payment: TSh " . number_format($amount, 2)]);

            $_SESSION['success'] = __('payment_successful');
            $redirect_url = 'dashboard.php';
            break;

        case 'full_payment':
            $material_id = $_POST['material_id'] ?? null;
            if (!$material_id) {
                throw new Exception(__('invalid_material_id'));
            }

            // Verify material exists and is active
            $stmt = $pdo->prepare("SELECT id, name, price FROM materials WHERE id = ? AND status = 'active'");
            $stmt->execute([$material_id]);
            $material = $stmt->fetch();

            if (!$material) {
                throw new Exception(__('material_not_found'));
            }

            // Record the payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, material_id, amount, payment_method, status, payment_date)
                VALUES (?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $material_id, $amount, $payment_method]);

            $payment_id = $pdo->lastInsertId();

            // Log the transaction
            $stmt = $pdo->prepare("
                INSERT INTO transaction_log (user_id, payment_id, action, details, created_at)
                VALUES (?, ?, 'payment', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $payment_id, "Full payment for " . $material['name'] . ": TSh " . number_format($amount, 2)]);

            $_SESSION['success'] = __('payment_successful');
            $redirect_url = 'dashboard.php';
            break;

        case 'direct_purchase':
            $purchase_id = $_POST['purchase_id'] ?? null;
            if (!$purchase_id) {
                throw new Exception(__('invalid_purchase_id'));
            }

            // Verify purchase exists and belongs to user
            $stmt = $pdo->prepare("
                SELECT dp.id, dp.material_id, dp.total_amount, dp.quantity, m.name
                FROM direct_purchases dp
                JOIN materials m ON dp.material_id = m.id
                WHERE dp.id = ? AND dp.user_id = ? AND dp.status = 'pending'
            ");
            $stmt->execute([$purchase_id, $_SESSION['user_id']]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                throw new Exception(__('purchase_not_found'));
            }

            // Record the payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, material_id, purchase_id, amount, payment_method, status, payment_date)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $purchase['material_id'], $purchase_id, $amount, $payment_method]);

            $payment_id = $pdo->lastInsertId();

            // Update purchase status to paid
            $stmt = $pdo->prepare("UPDATE direct_purchases SET status = 'paid', paid_at = NOW() WHERE id = ?");
            $stmt->execute([$purchase_id]);

            // Log the transaction
            $stmt = $pdo->prepare("
                INSERT INTO transaction_log (user_id, payment_id, action, details, created_at)
                VALUES (?, ?, 'payment', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $payment_id, "Direct purchase payment for " . $purchase['quantity'] . "x " . $purchase['name'] . ": TSh " . number_format($amount, 2)]);

            $_SESSION['success'] = __('direct_purchase_payment_successful');
            $redirect_url = 'dashboard.php';
            break;

        case 'lipa_kidogo_installment':
            $installment_id = $_POST['installment_id'] ?? null;
            if (!$installment_id) {
                throw new Exception(__('invalid_installment_id'));
            }

            // Verify installment exists and belongs to user
            $stmt = $pdo->prepare("
                SELECT li.id, li.amount, li.material_id, m.name
                FROM lipa_kidogo_installments li
                JOIN materials m ON li.material_id = m.id
                WHERE li.id = ? AND li.user_id = ? AND li.status = 'pending'
            ");
            $stmt->execute([$installment_id, $_SESSION['user_id']]);
            $installment = $stmt->fetch();

            if (!$installment) {
                throw new Exception(__('installment_not_found'));
            }

            // Record the payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, material_id, installment_id, amount, payment_method, status, payment_date)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $installment['material_id'], $installment_id, $amount, $payment_method]);

            $payment_id = $pdo->lastInsertId();

            // Update installment status to paid
            $stmt = $pdo->prepare("UPDATE lipa_kidogo_installments SET status = 'paid', paid_at = NOW() WHERE id = ?");
            $stmt->execute([$installment_id]);

            // Log the transaction
            $stmt = $pdo->prepare("
                INSERT INTO transaction_log (user_id, payment_id, action, details, created_at)
                VALUES (?, ?, 'payment', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $payment_id, "Installment payment for " . $installment['name'] . ": TSh " . number_format($amount, 2)]);

            $_SESSION['success'] = __('installment_payment_successful');
            $redirect_url = 'dashboard.php';
            break;

        default:
            throw new Exception(__('invalid_payment_type'));
    }

    $pdo->commit();

    // Redirect to success page
    redirect("payment_success.php?type=$payment_type&amount=$amount");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Payment processing error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    redirect('dashboard.php');
}
?>
