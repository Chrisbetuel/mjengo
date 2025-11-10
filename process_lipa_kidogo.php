<?php
require_once 'config.php';
require_once 'core/translation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = __('login_required');
    redirect('login.php');
}

// Get POST data
$material_id = $_POST['material_id'] ?? null;
$installment_amount = floatval($_POST['installment_amount'] ?? 0);
$start_date = $_POST['start_date'] ?? '';
$user_type = $_POST['user_type'] ?? null;
$payment_duration = $_POST['payment_duration'] ?? 'daily';

// Validate required fields
if (!$material_id || !$installment_amount || !$start_date) {
    $_SESSION['error'] = __('all_fields_required');
    redirect('lipa_kidogo.php');
}

if ($installment_amount <= 0) {
    $_SESSION['error'] = __('invalid_installment_amount');
    redirect('lipa_kidogo.php');
}

// Validate start date
$start_timestamp = strtotime($start_date);
$today = strtotime('today');

if ($start_timestamp < $today) {
    $_SESSION['error'] = __('start_date_cannot_be_past');
    redirect('lipa_kidogo.php');
}

try {
    $pdo->beginTransaction();

    // Get material details
    $stmt = $pdo->prepare("SELECT id, name, price FROM materials WHERE id = ? AND status = 'active'");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();

    if (!$material) {
        throw new Exception(__('material_not_found'));
    }

    $total_price = $material['price'];

    // Calculate number of installments needed
    $num_installments = ceil($total_price / $installment_amount);

    // Create lipa kidogo record
    $stmt = $pdo->prepare("
        INSERT INTO lipa_kidogo (user_id, material_id, total_amount, installment_amount, num_installments, start_date, user_type, payment_duration, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $material_id,
        $total_price,
        $installment_amount,
        $num_installments,
        $start_date,
        $user_type,
        $payment_duration
    ]);

    $lipa_kidogo_id = $pdo->lastInsertId();

    // Generate installment schedule based on payment duration
    $current_date = $start_timestamp;
    $interval = '+1 day';
    if ($payment_duration === 'weekly') {
        $interval = '+1 week';
    } elseif ($payment_duration === 'monthly') {
        $interval = '+1 month';
    }

    for ($i = 1; $i <= $num_installments; $i++) {
        $installment_date = date('Y-m-d', $current_date);
        $amount = ($i == $num_installments) ? ($total_price - ($i-1) * $installment_amount) : $installment_amount;

        $stmt = $pdo->prepare("
            INSERT INTO lipa_kidogo_installments (lipa_kidogo_id, user_id, material_id, installment_number, amount, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $lipa_kidogo_id,
            $_SESSION['user_id'],
            $material_id,
            $i,
            $amount,
            $installment_date,
            'pending'
        ]);

        // Move to next interval
        $current_date = strtotime($interval, $current_date);
    }

    // Log the transaction
    $stmt = $pdo->prepare("
        INSERT INTO transaction_log (user_id, lipa_kidogo_id, action, details, created_at)
        VALUES (?, ?, 'lipa_kidogo_setup', ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $lipa_kidogo_id,
        "Lipa Kidogo setup: {$material['name']} - {$num_installments} installments of TSh " . number_format($installment_amount, 2)
    ]);

    $pdo->commit();

    $_SESSION['success'] = __('lipa_kidogo_setup_successful');
    redirect('dashboard.php');

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Lipa Kidogo processing error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    redirect('lipa_kidogo.php');
}
?>
