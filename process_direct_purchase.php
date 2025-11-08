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
$quantity = intval($_POST['quantity'] ?? 1);
$delivery_address = trim($_POST['delivery_address'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');

// Validate required fields
if (!$material_id || !$delivery_address || !$phone_number) {
    $_SESSION['error'] = __('all_fields_required');
    redirect('direct_purchase.php');
}

if ($quantity < 1) {
    $_SESSION['error'] = __('invalid_quantity');
    redirect('direct_purchase.php');
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

    // Calculate total amount
    $unit_price = $material['price'];
    $total_amount = $unit_price * $quantity;

    // Create purchase record
    $stmt = $pdo->prepare("
        INSERT INTO direct_purchases (user_id, material_id, quantity, unit_price, total_amount, delivery_address, phone_number, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $material_id,
        $quantity,
        $unit_price,
        $total_amount,
        $delivery_address,
        $phone_number
    ]);

    $purchase_id = $pdo->lastInsertId();

    // Log the transaction
    $stmt = $pdo->prepare("
        INSERT INTO transaction_log (user_id, purchase_id, action, details, created_at)
        VALUES (?, ?, 'direct_purchase', ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $purchase_id,
        "Direct purchase: {$quantity}x {$material['name']} - TSh " . number_format($total_amount, 2)
    ]);

    $pdo->commit();

    // Redirect to payment gateway with purchase details
    $redirect_url = "payment_gateway.php?type=direct_purchase&purchase_id=$purchase_id&amount=$total_amount";
    redirect($redirect_url);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Direct purchase processing error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    redirect('direct_purchase.php');
}
?>
