<?php
require_once 'config.php';

$pdo->exec("UPDATE group_members SET status = 'active'");
echo 'Set all statuses to active' . PHP_EOL;
?>
