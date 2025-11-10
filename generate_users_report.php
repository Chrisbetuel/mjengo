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
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query
$query = "
    SELECT
        u.id,
        u.username,
        u.email,
        u.phone_number,
        u.nida_id,
        u.role,
        u.created_at,
        COUNT(DISTINCT p.id) as total_participations,
        COUNT(DISTINCT dp.id) as total_direct_purchases,
        COUNT(DISTINCT lkp.id) as total_installments,
        COALESCE(SUM(p.amount), 0) + COALESCE(SUM(dp.total_amount), 0) + COALESCE(SUM(lkp.amount), 0) as total_spent
    FROM users u
    LEFT JOIN participants part ON u.id = part.user_id
    LEFT JOIN payments p ON part.id = p.participant_id AND p.status = 'paid'
    LEFT JOIN direct_purchases dp ON u.id = dp.user_id AND dp.status = 'paid'
    LEFT JOIN lipa_kidogo_payments lkp ON u.id = lkp.user_id AND lkp.status = 'paid'
    WHERE 1=1
";

$params = [];

if ($role !== 'all') {
    $query .= " AND u.role = ?";
    $params[] = $role;
}

if ($start_date) {
    $query .= " AND DATE(u.created_at) >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND DATE(u.created_at) <= ?";
    $params[] = $end_date;
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Check if Excel download is requested
if (isset($_GET['download']) && $_GET['download'] === 'excel') {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Mjengo Challenge Admin')
        ->setLastModifiedBy('Mjengo Challenge System')
        ->setTitle('Users Report')
        ->setSubject('Users Report')
        ->setDescription('Generated users report from Mjengo Challenge system');

    // Set headers
    $headers = ['ID', 'Username', 'Email', 'Phone Number', 'NIDA ID', 'Role', 'Joined Date', 'Participations', 'Direct Purchases', 'Installments', 'Total Spent'];
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
    $total_users = 0;
    $total_spent = 0;
    foreach ($users as $user) {
        $sheet->setCellValue('A' . $row, $user['id']);
        $sheet->setCellValue('B' . $row, htmlspecialchars($user['username']));
        $sheet->setCellValue('C' . $row, htmlspecialchars($user['email'] ?? ''));
        $sheet->setCellValue('D' . $row, htmlspecialchars($user['phone_number']));
        $sheet->setCellValue('E' . $row, htmlspecialchars($user['nida_id']));
        $sheet->setCellValue('F' . $row, ucfirst($user['role']));
        $sheet->setCellValue('G' . $row, date('M d, Y', strtotime($user['created_at'])));
        $sheet->setCellValue('H' . $row, $user['total_participations']);
        $sheet->setCellValue('I' . $row, $user['total_direct_purchases']);
        $sheet->setCellValue('J' . $row, $user['total_installments']);
        $sheet->setCellValue('K' . $row, number_format($user['total_spent'], 2));

        // Style the row
        $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Color code role
        $role_color = '';
        switch ($user['role']) {
            case 'admin':
                $role_color = 'FFFFD700'; // Gold
                break;
            case 'user':
                $role_color = 'FFE6E6FA'; // Light blue
                break;
        }
        if ($role_color) {
            $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($role_color);
        }

        $total_users++;
        $total_spent += $user['total_spent'];
        $row++;
    }

    // Add summary row
    $sheet->setCellValue('A' . $row, 'TOTAL');
    $sheet->setCellValue('B' . $row, $total_users . ' users');
    $sheet->setCellValue('K' . $row, number_format($total_spent, 2));
    $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
    $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Auto-size columns
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="users_report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Calculate summary statistics
$total_users_count = count($users);
$admin_count = count(array_filter($users, function($u) { return $u['role'] === 'admin'; }));
$user_count = count(array_filter($users, function($u) { return $u['role'] === 'user'; }));
$total_participations = array_sum(array_column($users, 'total_participations'));
$total_spent = array_sum(array_column($users, 'total_spent'));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Report - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2 mb-2"><i class="fas fa-users me-2"></i>Users Report</h1>
                    <p class="mb-0">Comprehensive report of all registered users</p>
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
                    <label for="role" class="form-label">User Role</label>
                    <select class="form-control" id="role" name="role">
                        <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Joined From</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Joined To</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
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
                            <h3 class="h4 mb-1"><?php echo $total_users_count; ?></h3>
                            <p class="text-muted mb-0">Total Users</p>
                        </div>
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1"><?php echo $admin_count; ?></h3>
                            <p class="text-muted mb-0">Admins</p>
                        </div>
                        <i class="fas fa-shield-alt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1"><?php echo $total_participations; ?></h3>
                            <p class="text-muted mb-0">Total Participations</p>
                        </div>
                        <i class="fas fa-tasks fa-2x text-info"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h4 mb-1">TSh <?php echo number_format($total_spent, 2); ?></h3>
                            <p class="text-muted mb-0">Total Spent</p>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>NIDA ID</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Participations</th>
                        <th>Purchases</th>
                        <th>Installments</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No users found for the selected criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['nida_id']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'warning' : 'secondary'; ?>">
                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'shield-alt' : 'user'; ?> me-1"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['total_participations']; ?></td>
                                <td><?php echo $user['total_direct_purchases']; ?></td>
                                <td><?php echo $user['total_installments']; ?></td>
                                <td class="fw-bold text-success">TSh <?php echo number_format($user['total_spent'], 2); ?></td>
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
