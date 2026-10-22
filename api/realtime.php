<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? 'get_live_stats');

switch ($action) {

    // 1. Live User Dashboard Realtime API
    case 'get_user_stats':
        $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : intval($_GET['user_id'] ?? 0);
        
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
            exit;
        }

        // Total Bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid");
        $stmt->execute(['uid' => $userId]);
        $totalBookings = intval($stmt->fetchColumn());

        // Upcoming Stays
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `check_in` > CURDATE() AND `booking_status` != 'Cancelled'");
        $stmt->execute(['uid' => $userId]);
        $upcomingStays = intval($stmt->fetchColumn());

        // Completed Stays
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `booking_status` = 'Checked-Out'");
        $stmt->execute(['uid' => $userId]);
        $completedStays = intval($stmt->fetchColumn());

        // Cancelled Bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `booking_status` = 'Cancelled'");
        $stmt->execute(['uid' => $userId]);
        $cancelledBookings = intval($stmt->fetchColumn());

        // Saved Rooms (Wishlist)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `wishlist` WHERE `customer_id` = :uid");
        $stmt->execute(['uid' => $userId]);
        $savedRooms = intval($stmt->fetchColumn());

        // Recent Bookings
        $stmt = $pdo->prepare("
            SELECT b.`booking_id`, b.`check_in`, b.`check_out`, b.`total_amount`, b.`booking_status`,
                   r.`room_no`, rt.`type_name`
            FROM `bookings` b
            JOIN `rooms` r ON b.`room_id` = r.`room_id`
            JOIN `room_types` rt ON r.`room_type_id` = rt.`room_type_id`
            WHERE b.`customer_id` = :uid
            ORDER BY b.`booking_id` DESC
            LIMIT 5
        ");
        $stmt->execute(['uid' => $userId]);
        $recentBookings = $stmt->fetchAll();

        // Format recent bookings for UI
        $formattedBookings = array_map(function($b) {
            return [
                'booking_id' => '#HE-' . $b['booking_id'],
                'raw_id' => $b['booking_id'],
                'room' => 'Room ' . $b['room_no'] . ' (' . $b['type_name'] . ')',
                'check_in' => $b['check_in'],
                'check_out' => $b['check_out'],
                'total' => '₹' . number_format($b['total_amount'], 2),
                'status' => $b['booking_status']
            ];
        }, $recentBookings);

        echo json_encode([
            'status' => 'success',
            'timestamp' => time(),
            'data' => [
                'total_bookings' => $totalBookings,
                'upcoming_stays' => $upcomingStays,
                'completed_stays' => $completedStays,
                'cancelled_bookings' => $cancelledBookings,
                'saved_rooms' => $savedRooms,
                'recent_activity' => $formattedBookings
            ]
        ]);
        break;

    // 2. Live Admin & Website Realtime API
    case 'get_live_stats':
    default:
        // Total Rooms & Status Counts
        $totalRooms = intval($pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn());
        $availableRooms = intval($pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `status` = 'Available'")->fetchColumn());
        $occupiedRooms = intval($pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `status` = 'Occupied'")->fetchColumn());
        $reservedRooms = intval($pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `status` = 'Reserved'")->fetchColumn());

        // Total Bookings & Revenue
        $totalBookings = intval($pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn());
        $activeBookings = intval($pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `booking_status` IN ('Confirmed', 'Checked-In')")->fetchColumn());
        $totalRevenue = floatval($pdo->query("SELECT COALESCE(SUM(`amount`), 0) FROM `payments` WHERE `payment_status` = 'Paid'")->fetchColumn());

        // Total Customers & Employees
        $totalCustomers = intval($pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn());
        $totalEmployees = intval($pdo->query("SELECT COUNT(*) FROM `employees` WHERE `status` = 'Active'")->fetchColumn());

        echo json_encode([
            'status' => 'success',
            'timestamp' => time(),
            'data' => [
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
                'occupied_rooms' => $occupiedRooms,
                'reserved_rooms' => $reservedRooms,
                'total_bookings' => $totalBookings,
                'active_bookings' => $activeBookings,
                'total_revenue' => '₹' . number_format($totalRevenue, 2),
                'raw_revenue' => $totalRevenue,
                'total_customers' => $totalCustomers,
                'total_employees' => $totalEmployees
            ]
        ]);
        break;

    // 3. Realtime Wishlist Toggle
    case 'toggle_wishlist':
        $userId = intval($_SESSION['user_id'] ?? 0);
        $roomId = intval($_POST['room_id'] ?? $_GET['room_id'] ?? 0);

        if ($userId <= 0 || $roomId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters or unauthenticated']);
            exit;
        }

        $check = $pdo->prepare("SELECT `wishlist_id` FROM `wishlist` WHERE `customer_id` = :uid AND `room_id` = :rid");
        $check->execute(['uid' => $userId, 'rid' => $roomId]);
        $existing = $check->fetch();

        if ($existing) {
            $del = $pdo->prepare("DELETE FROM `wishlist` WHERE `wishlist_id` = :wid");
            $del->execute(['wid' => $existing['wishlist_id']]);
            $isSaved = false;
            $msg = 'Room removed from your saved list.';
        } else {
            $ins = $pdo->prepare("INSERT INTO `wishlist` (`customer_id`, `room_id`) VALUES (:uid, :rid)");
            $ins->execute(['uid' => $userId, 'rid' => $roomId]);
            $isSaved = true;
            $msg = 'Room added to your saved list!';
        }

        // Return updated count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `wishlist` WHERE `customer_id` = :uid");
        $countStmt->execute(['uid' => $userId]);
        $newCount = intval($countStmt->fetchColumn());

        echo json_encode([
            'status' => 'success',
            'saved' => $isSaved,
            'message' => $msg,
            'total_saved' => $newCount
        ]);
        break;

    // 4. Set Display Currency API
    case 'set_currency':
        $curr = strtoupper(sanitize($_POST['currency'] ?? $_GET['currency'] ?? 'INR'));
        $validCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED'];
        if (!in_array($curr, $validCurrencies)) {
            $curr = 'INR';
        }
        $_SESSION['pref_currency'] = $curr;
        setcookie('hotel_ease_currency', $curr, time() + (86400 * 30), '/');
        echo json_encode([
            'status' => 'success',
            'currency' => $curr,
            'message' => 'Display currency updated to ' . $curr
        ]);
        break;
}
