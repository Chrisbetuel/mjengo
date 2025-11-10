<?php
require_once 'config.php';

// Auto-login admin for development
autoLoginAdmin();

// Check if admin
if (!isAdmin()) {
    redirect('index.php');
}

// Simulate form submission
$message = '';
$messageType = '';

if (isset($_POST['test_success'])) {
    $message = 'Test success message';
    $messageType = 'success';
} elseif (isset($_POST['test_error'])) {
    $message = 'Test error message';
    $messageType = 'danger';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Admin Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Test Admin Message Display</h1>

        <!-- Message Display -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> animate-on-scroll">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="mb-3">
            <button type="submit" name="test_success" class="btn btn-success me-2">Test Success Message</button>
            <button type="submit" name="test_error" class="btn btn-danger">Test Error Message</button>
        </form>

        <a href="admin.php" class="btn btn-primary">Back to Admin Panel</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            animatedElements.forEach(element => {
                element.classList.add('animated');
            });
        });
    </script>
</body>
</html>
