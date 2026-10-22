<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
set_toast('Admin session logged out successfully.', 'info');
header('Location: /hotel_website/index.php');
exit;
?>
