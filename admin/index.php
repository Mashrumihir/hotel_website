<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect('/hotel_website/admin/dashboard.php');
} else {
    redirect('/hotel_website/admin/login.php');
}
exit;
