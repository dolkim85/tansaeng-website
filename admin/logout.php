<?php
// Use Auth class for logout
require_once __DIR__ . '/../classes/Auth.php';
$auth = Auth::getInstance();
$auth->logout();

// Redirect to admin login
header('Location: /admin/login.php');
exit;
?>