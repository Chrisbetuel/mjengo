<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'core/translation.php';

autoLoginAdmin();

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filters
$query_parts = [];
$params = [];

if ($payment_type === 'challenge' || $payment_type === 'all') {
    $challenge_query = "
        SELECT
            p.id,
            p.amount,
            p.payment_date,
            p.status,
            u.username,
            u.phone_number,
            u.nida_id,
            c.name as challenge_name,
            'challenge' as payment_type,
            p.created_at
        FROM payments p
        JOIN participants part ON p.participant_id = part.id
        JOIN users u ON part.user_id = u.id
        JOIN challenges c ON part.challenge_id = c.id
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
    ";
    $query_parts[] = $challenge_query;
    $params[] = $start_date;
    $params[] = $end_date;
}

if ($payment_type === 'direct' || $payment_type === 'all') {
    $direct_query = "
        SELECT
            dp.id,
            dp.total_amount as amount,
            DATE(dp.created_at) as payment_date,
            dp.status,
            u.username,
            u.phone_number,
            u.nida_id,
            m.name as challenge_name,
            'direct' as payment_type,
            dp.created_at
        FROM direct_purchases dp
        JOIN users u ON dp.user_id = u.id
        JOIN materials m ON dp.material_id = m.id
        WHERE DATE(dp.created_at) BETWEEN ? AND ?
    ";
    $query_parts[] = $direct_query;
    $params[] = $start_date;
    $params[] = $end_date;
}

if ($payment_type === 'installment' || $payment_type === 'all') {
    $installment_query = "
        SELECT
            lkp.id,
            lkp.amount,
            lkp.payment_date,
            lkp.status,
            u.username,
            u.phone_number,
            u.nida_id,
            m.name as challenge_name,
            'installment' as payment_type,
            lkp.created_at
        FROM lipa_kidogo_payments lkp
        JOIN users u ON lkp.user_id = u.id
        JOIN materials m ON lkp.material_id = m.id
        WHERE DATE(lkp.payment_date) BETWEEN ? AND ?
    ";
    $query_parts[] = $installment_query;
    $params[] = $start_date;
    $params[] = $end_date;
}

$query = implode(' UNION ALL ', $query_parts) . ' ORDER BY payment_date DESC, created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Apply status filter if specified
if ($status !== 'all') {
    $payments = array_filter($payments, function($payment) use ($status) {
        return $payment['status'] === $status;
    });
}

// Check if Excel download is requested
if (isset($_GET['download']) && $_GET['download'] === 'excel') {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Mjengo Challenge Admin')
        ->setLastModifiedBy('Mjengo Challenge System')
        ->setTitle('Payments Report')
        ->setSubject('Payments Report')
        ->setDescription('Generated payments report from Mjengo Challenge system');

    // Set headers
    $headers = ['ID', 'Date', 'User', 'Phone', 'NIDA ID', 'Type', 'Description', 'Amount', 'Status'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
        $sheet->getStyle($col . '1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $col++;
    }

    // Add data
    $row = 2;
    $total_amount = 0;
    foreach ($payments as $payment) {
        $sheet->setCellValue('A' . $row, $payment['id']);
        $sheet->setCellValue('B' . $row, date('M d, Y', strtotime($payment['payment_date'])));
        $sheet->setCellValue('C' . $row, htmlspecialchars($payment['username']));
        $sheet->setCellValue('D' . $row, htmlspecialchars($payment['phone_number']));
        $sheet->setCellValue('E' . $row, htmlspecialchars($payment['nida_id']));
        $sheet->setCellValue('F' . $row, ucfirst($payment['payment_type']));
        $sheet->setCellValue('G' . $row, htmlspecialchars($payment['challenge_name']));
        $sheet->setCellValue('H' . $row, number_format($payment['amount'], 2));
        $sheet->setCellValue('I' . $row, ucfirst($payment['status']));

        // Style the row
        $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Color code status
        $status_color = '';
        switch ($payment['status']) {
            case 'paid':
                $status_color = 'FFD4EDDA'; // Light green
                break;
            case 'pending':
                $status_color = 'FFFFF3CD'; // Light yellow
                break;
            case 'overdue':
                $status_color = 'FFF8D7DA'; // Light red
                break;
        }
        if ($status_color) {
            $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($status_color);
        }

        $total_amount += $payment['amount'];
        $row++;
    }

    // Add total row
    $sheet->setCellValue('G' . $row, 'TOTAL');
    $sheet->setCellValue('H' . $row, number_format($total_amount, 2));
    $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);
    $sheet->getStyle('G' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
    $sheet->getStyle('G' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Auto-size columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="payments_report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Calculate summary statistics
$total_payments = count($payments);
$total_amount = array_sum(array_column($payments, 'amount'));
$paid_payments = count(array_filter($payments, function($p) { return $p['status'] === 'paid'; }));
$pending_payments = count(array_filter($payments, function($p) { return $p['status'] === 'pending'; }));
$overdue_payments = count(array_filter($payments, function($p) { return $p['status'] === 'overdue'; }));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Report - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .report-header {
            background: linear-gradient(135deg, #1a5276, #2c3e50);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #1a5276;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .btn-admin {
            background: linear-gradient(135deg, #1a5276, #2c3e50);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-admin:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2"><i class="fas fa-chart-line me-2"></i>Payments Report</h1>
                    <p class="mb-0">Detailed report of all payment transactions</p>
                </div>
                <div>
                    <a href="admin.php" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left me-1"></i>Back to Admin
                    </a>
                    <a href="?<?php echo http_build_query($_GET); ?>&download=excel" class="btn-admin">
                        <i class="fas fa-download me-1"></i>Download Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-form">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <label for="payment_type" class="form-label">Payment Type</label>
                    <select class="form-control" id="payment_type" name="payment_type">
                        <option value="all" <?php echo $payment_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="challenge" <?php echo $payment_type === 'challenge' ? 'selected' : ''; ?>>Challenge</option>
                        <option value="direct" <?php echo $payment_type === 'direct' ? 'selected' : ''; ?>>Direct Purchase</option>
                        <option value="installment" <?php echo $payment_type === 'installment' ? 'selected' : ''; ?>>Installment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-admin w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1"><?php echo $total_payments; ?></h3>
                            <p class="text-muted mb-0">Total Payments</p>
                        </div>
                        <i class="fas fa-receipt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1">TSh <?php echo number_format($total_amount, 2); ?></h3>
                            <p class="text-muted mb-0">Total Amount</p>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1"><?php echo $paid_payments; ?></h3>
                            <p class="text-muted mb-0">Paid</p>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1"><?php echo $pending_payments + $overdue_payments; ?></h3>
                            <p class="text-muted mb-0">Pending/Overdue</p>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No payments found for the selected criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong>#<?php echo $payment['id']; ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                <td><?php echo htmlspecialchars($payment['phone_number']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $payment['payment_type'] === 'challenge' ? 'warning' :
                                             ($payment['payment_type'] === 'direct' ? 'primary' : 'info');
                                    ?>">
                                        <i class="fas fa-<?php
                                            echo $payment['payment_type'] === 'challenge' ? 'tasks' :
                                                 ($payment['payment_type'] === 'direct' ? 'shopping-cart' : 'credit-card');
                                        ?> me-1"></i>
                                        <?php echo ucfirst($payment['payment_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['challenge_name']); ?></td>
                                <td class="fw-bold text-success">TSh <?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $payment['status'] === 'paid' ? 'success' :
                                             ($payment['status'] === 'pending' ? 'warning' : 'danger');
                                    ?>">
                                        <i class="fas fa-<?php
                                            echo $payment['status'] === 'paid' ? 'check' :
                                                 ($payment['status'] === 'pending' ? 'clock' : 'exclamation-triangle');
                                        ?> me-1"></i>
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
