<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_user();

$bookingId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1);

if ($isAjax) {
    header('Content-Type: application/json');
    if ($bookingId > 0 && $pdo) {
        $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Cancelled' WHERE `booking_id` = :bid AND `customer_id` = :uid");
        $stmt->execute(['bid' => $bookingId, 'uid' => $userId]);
        echo json_encode(['success' => true, 'message' => 'Reservation #HE-' . $bookingId . ' has been cancelled successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

if ($bookingId > 0 && $pdo) {
    $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Cancelled' WHERE `booking_id` = :bid AND `customer_id` = :uid");
    $stmt->execute(['bid' => $bookingId, 'uid' => $userId]);
    set_toast('Reservation #HE-' . $bookingId . ' has been cancelled.', 'info');
}

header('Location: /hotel_website/user/my-bookings.php');
exit;
