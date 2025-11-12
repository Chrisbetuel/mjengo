<?php
require_once 'config.php';

$pdo->exec("UPDATE group_members SET status = 'active' WHERE status IS NULL OR status = ''");
echo 'Updated empty statuses to active' . PHP_EOL;
?>
