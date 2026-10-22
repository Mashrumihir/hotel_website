<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pdo = getDBConnection();

if ($bookingId > 0 && $pdo) {
    // Get booking details
    $stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `booking_id` = :bid LIMIT 1");
    $stmt->execute(['bid' => $bookingId]);
    $booking = $stmt->fetch();

    if ($booking) {
        // 1. Update booking status to Checked-Out
        $updB = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Checked-Out' WHERE `booking_id` = :bid");
        $updB->execute(['bid' => $bookingId]);

        // 2. Update room status to Available
        $updR = $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `room_id` = :rid");
        $updR->execute(['rid' => $booking['room_id']]);

        set_toast('Guest Checked-Out successfully! Room status updated to AVAILABLE 🟢', 'success');
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? '/hotel_website/admin/bookings.php';
header("Location: $redirect");
exit;
?>
