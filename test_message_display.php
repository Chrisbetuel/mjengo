<?php
require_once 'config.php';

// Simulate message display
$message = 'Test success message';
$messageType = 'success';

echo '<!DOCTYPE html><html><head><title>Test Message Display</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';

if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?> animate-on-scroll">
    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif;

echo '</body></html>';
?>
