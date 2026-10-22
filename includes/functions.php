<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

// Calculate Dynamic Booking Breakdown
function calculate_booking_total($price_per_night, $nights, $discount = 0, $gst_percent = 18) {
    $subtotal = $price_per_night * $nights;
    $gst = ($subtotal * $gst_percent) / 100;
    $grand_total = max(0, ($subtotal + $gst) - $discount);
    return [
        'price_per_night' => $price_per_night,
        'nights' => $nights,
        'subtotal' => $subtotal,
        'gst' => $gst,
        'discount' => $discount,
        'grand_total' => $grand_total
    ];
}

// Check Room Availability Overlap Logic in MySQL
function check_room_available($room_id, $check_in, $check_out) {
    $pdo = getDBConnection();
    if (!$pdo) return false;

    $sql = "SELECT COUNT(*) FROM `bookings` 
            WHERE `room_id` = :room_id 
              AND `booking_status` IN ('Confirmed', 'Checked-In', 'Pending') 
              AND (`check_in` < :check_out AND `check_out` > :check_in)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'room_id' => $room_id,
        'check_in' => $check_in,
        'check_out' => $check_out
    ]);
    
    return ($stmt->fetchColumn() == 0);
}

// Get Status Badge HTML
function get_status_badge($status) {
    switch ($status) {
        case 'Available':
            return '<span class="badge bg-success font-semibold px-2.5 py-1 rounded-pill">🟢 Available</span>';
        case 'Occupied':
            return '<span class="badge bg-danger font-semibold px-2.5 py-1 rounded-pill">🔴 Occupied</span>';
        case 'Reserved':
            return '<span class="badge bg-warning text-dark font-semibold px-2.5 py-1 rounded-pill">🟡 Reserved</span>';
        case 'Maintenance':
            return '<span class="badge bg-secondary font-semibold px-2.5 py-1 rounded-pill">🟣 Maintenance</span>';
        case 'Confirmed':
            return '<span class="badge bg-primary font-semibold px-2.5 py-1 rounded-pill">Confirmed</span>';
        case 'Checked-In':
            return '<span class="badge bg-info text-dark font-semibold px-2.5 py-1 rounded-pill">Checked-In</span>';
        case 'Checked-Out':
            return '<span class="badge bg-secondary font-semibold px-2.5 py-1 rounded-pill">Checked-Out</span>';
        case 'Cancelled':
            return '<span class="badge bg-danger font-semibold px-2.5 py-1 rounded-pill">Cancelled</span>';
        case 'Pending':
            return '<span class="badge bg-warning text-dark font-semibold px-2.5 py-1 rounded-pill">Pending</span>';
        default:
            return '<span class="badge bg-dark font-semibold px-2.5 py-1 rounded-pill">' . htmlspecialchars($status) . '</span>';
    }
}

// Format Image URL helper to handle absolute URLs, leading slash paths, and relative paths
function format_img_url($url) {
    if (empty($url)) return '';
    $url = trim($url);
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0 || strpos($url, '//') === 0) {
        return $url;
    }
    if (strpos($url, '/hotel_website/') === 0) {
        return $url;
    }
    return '/hotel_website/' . ltrim($url, '/');
}
?>
