<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_user_logged_in()) {
    redirect('/hotel_website/user/dashboard.php');
} else {
    redirect('/hotel_website/user/login.php');
}
exit;
