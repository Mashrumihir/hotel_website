<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
set_toast('You have logged out of your account.', 'info');
header('Location: /hotel_website/index.php');
exit;
?>
