<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$roomId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($roomId > 0) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->exec("DELETE FROM `payments` WHERE `booking_id` IN (SELECT `booking_id` FROM `bookings` WHERE `room_id` = $roomId)");
            $pdo->exec("DELETE FROM `bookings` WHERE `room_id` = $roomId");
            $stmt = $pdo->prepare("DELETE FROM `rooms` WHERE `room_id` = :id");
            $stmt->execute(['id' => $roomId]);
            set_toast('Room deleted from inventory.', 'info');
        } catch (Exception $e) {
            set_toast('Unable to delete room: ' . $e->getMessage(), 'error');
        }
    }
}
header('Location: /hotel_website/admin/rooms.php');
exit;
?>
