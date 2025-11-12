<?php
require_once 'config.php';

$pdo->exec('UPDATE group_members SET status = "accepted" WHERE user_id = 1');
echo 'Updated all group memberships for user 1 to accepted status.' . PHP_EOL;
?>
