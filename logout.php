<?php
require_once 'config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');
?>
