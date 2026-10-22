<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$user = get_logged_user() ?? [
    'customer_id' => $_SESSION['user_id'] ?? 1,
    'name' => $_SESSION['user_name'] ?? 'Mihir Mashru',
    'email' => $_SESSION['user_email'] ?? 'mashrumihir15@gmail.com',
    'mobile' => '+91 9876543210',
    'address' => 'Rajkot, Gujarat, India',
    'id_proof' => 'Aadhar Card - 482910293841'
];
$userId = $user['customer_id'] ?? ($_SESSION['user_id'] ?? 1);
$pdo = getDBConnection();

// Dynamic Calculations & Database Queries
$totalBookings = 0;
$upcomingStays = 0;
$completedStays = 0;
$cancelledBookings = 0;
$savedRoomsCount = 0;
$recentBookings = [];
$activeStay = null;
$featuredRooms = [];
$notificationsList = [];
$unreadNotifCount = 0;
$wishlistRooms = [];

if ($pdo) {
    // 1. Total Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid");
    $stmt->execute(['uid' => $userId]);
    $totalBookings = $stmt->fetchColumn();

    // 2. Upcoming Stays
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `booking_status` IN ('Pending', 'Confirmed') AND `check_in` >= CURDATE()");
    $stmt->execute(['uid' => $userId]);
    $upcomingStays = $stmt->fetchColumn();

    // 3. Completed Stays
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `booking_status` = 'Checked-Out'");
    $stmt->execute(['uid' => $userId]);
    $completedStays = $stmt->fetchColumn();

    // 4. Cancelled Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = :uid AND `booking_status` = 'Cancelled'");
    $stmt->execute(['uid' => $userId]);
    $cancelledBookings = $stmt->fetchColumn();

    // 5. Saved Rooms / Wishlist Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `wishlist` WHERE `customer_id` = :uid");
    $stmt->execute(['uid' => $userId]);
    $savedRoomsCount = $stmt->fetchColumn();

    // 5. Recent Bookings List
    $stmt = $pdo->prepare("SELECT b.*, r.room_no, rt.type_name, p.payment_status, p.payment_mode FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id LEFT JOIN `payments` p ON b.booking_id = p.booking_id WHERE b.customer_id = :uid ORDER BY b.booking_id DESC LIMIT 5");
    $stmt->execute(['uid' => $userId]);
    $recentBookings = $stmt->fetchAll();

    // 6. Active / Next Upcoming Stay Card
    $stmt = $pdo->prepare("SELECT b.*, r.room_no, r.image, r.description, r.amenities, rt.type_name, p.payment_status FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id LEFT JOIN `payments` p ON b.booking_id = p.booking_id WHERE b.customer_id = :uid AND b.booking_status IN ('Confirmed', 'Checked-In', 'Pending') ORDER BY b.check_in ASC LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    $activeStay = $stmt->fetch();

    // 7. Recommended Rooms for You
    $stmt = $pdo->query("SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE r.status = 'Available' ORDER BY r.price DESC LIMIT 4");
    $featuredRooms = $stmt->fetchAll();

    // 8. Notifications
    $stmt = $pdo->prepare("SELECT * FROM `notifications` WHERE `user_id` = :uid AND `user_type` = 'Customer' ORDER BY `notification_id` DESC LIMIT 5");
    $stmt->execute(['uid' => $userId]);
    $notificationsList = $stmt->fetchAll();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = :uid AND `user_type` = 'Customer' AND `is_read` = 0");
    $stmtCount->execute(['uid' => $userId]);
    $unreadNotifCount = $stmtCount->fetchColumn();

    // 9. Wishlist Items
    $stmt = $pdo->prepare("SELECT w.wishlist_id, r.*, rt.type_name FROM `wishlist` w JOIN `rooms` r ON w.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE w.customer_id = :uid ORDER BY w.wishlist_id DESC LIMIT 4");
    $stmt->execute(['uid' => $userId]);
    $wishlistRooms = $stmt->fetchAll();

    $stmtWIds = $pdo->prepare("SELECT `room_id` FROM `wishlist` WHERE `customer_id` = :uid");
    $stmtWIds->execute(['uid' => $userId]);
    $userWishlistRoomIds = $stmtWIds->fetchAll(PDO::FETCH_COLUMN);
}
?>

<style>
    /* Exact Color System per Specification */
    :root {
        --primary-blue: #0B5ED7;
        --dark-blue: #123B6D;
        --light-blue: #EAF3FF;
        --bg-color: #F5F8FC;
        --text-color: #172033;
        --text-secondary: #64748B;
        --border-color: #E2E8F0;
        --color-success: #16A34A;
        --color-warning: #F59E0B;
        --color-danger: #DC3545;
    }

    body {
        background-color: var(--bg-color) !important;
        color: var(--text-color);
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
    }

    /* Top Sticky White Navbar (Height 64px - 72px) */
    .top-sticky-navbar {
        background: #FFFFFF;
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 4px 20px rgba(18, 59, 109, 0.04);
        position: sticky;
        top: 0;
        z-index: 1020;
        height: 68px;
        min-height: 68px;
        max-height: 68px;
        box-sizing: border-box;
    }

    /* Hero Banner */
    .saas-hero-banner {
        min-height: 260px;
        height: auto;
        border-radius: 18px;
        background: linear-gradient(90deg, rgba(7, 35, 70, 0.95) 0%, rgba(11, 94, 215, 0.65) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
        box-shadow: 0 16px 40px rgba(11, 94, 215, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Stat Cards */
    .stat-card-saas {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.4rem 1rem;
        box-shadow: 0 4px 16px rgba(18, 59, 109, 0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .stat-card-saas::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: transparent;
        transition: background 0.3s ease;
    }
    .stat-card-saas.card-blue::before { background: #2563EB; }
    .stat-card-saas.card-green::before { background: #16A34A; }
    .stat-card-saas.card-info::before { background: #0EA5E9; }
    .stat-card-saas.card-red::before { background: #EF4444; }

    .stat-card-saas:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 30px rgba(18, 59, 109, 0.1);
        border-color: #CBD5E1;
    }

    /* Card styling */
    .card-saas {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: 18px;
        box-shadow: 0 6px 24px rgba(18, 59, 109, 0.04);
    }

    .btn-primary-blue {
        background-color: var(--primary-blue) !important;
        color: #FFFFFF !important;
        border: 1px solid var(--primary-blue) !important;
        font-weight: 700;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    .btn-primary-blue:hover {
        background-color: #094CB2 !important;
        box-shadow: 0 8px 20px rgba(11, 94, 215, 0.3);
    }

    .btn-outline-white {
        background: transparent !important;
        color: #FFFFFF !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        font-weight: 700;
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    .btn-outline-white:hover {
        background: #FFFFFF !important;
        color: var(--dark-blue) !important;
    }

    /* User Nav Pill & Dropdown Menu Styling */
    .user-nav-pill {
        transition: all 0.2s ease !important;
    }
    .user-nav-pill:hover {
        background-color: #F8FAFC !important;
        border-color: #0B5ED7 !important;
        box-shadow: 0 4px 14px rgba(11, 94, 215, 0.12) !important;
    }
    .user-dropdown-menu .dropdown-item {
        color: #334155;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .user-dropdown-menu .dropdown-item:hover {
        background-color: #EFF6FF !important;
        color: #0B5ED7 !important;
    }
    .user-dropdown-menu .dropdown-item-logout:hover {
        background-color: #FEF2F2 !important;
        color: #DC3545 !important;
    }

    /* Upcoming Stays Pill & Grid Layout Styles */
    .upcoming-stays-pill-box {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: #EEF4FF;
        border: 1px solid #DBEAFE;
        padding: 0.6rem 1.25rem;
        border-radius: 50px;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06);
    }
    .upcoming-stays-pill-box .icon-circle-node {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #2563EB;
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
    }
    .upcoming-stays-pill-box .pill-title-text {
        color: #2563EB;
        font-weight: 700;
        font-size: 1.15rem;
        letter-spacing: -0.2px;
        line-height: 1;
    }

    .stay-details-grid-box {
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
    .stay-detail-label {
        color: #64748B;
    }
    .stay-detail-value {
        color: #0F172A;
    }
    .badge-status-confirmed,
    .badge-status-paid {
        background-color: #DCFCE7;
        color: #15803D;
        border: 1px solid #BBF7D0;
    }
    .btn-cancel-booking {
        color: #DC2626 !important;
    }

    /* Dark Theme Overrides for Upcoming Stays Section */
    [data-theme="dark"] .upcoming-stays-pill-box,
    [data-bs-theme="dark"] .upcoming-stays-pill-box,
    body.dark-theme .upcoming-stays-pill-box {
        background: #1E293B !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .upcoming-stays-pill-box .pill-title-text,
    [data-bs-theme="dark"] .upcoming-stays-pill-box .pill-title-text,
    body.dark-theme .upcoming-stays-pill-box .pill-title-text {
        color: #60A5FA !important;
    }

    [data-theme="dark"] #upcoming,
    [data-bs-theme="dark"] #upcoming,
    body.dark-theme #upcoming {
        background: #0F172A !important;
        border-color: #1E293B !important;
    }
    [data-theme="dark"] #upcoming h4,
    [data-bs-theme="dark"] #upcoming h4,
    body.dark-theme #upcoming h4 {
        color: #F8FAFC !important;
    }
    [data-theme="dark"] .stay-details-grid-box,
    [data-bs-theme="dark"] .stay-details-grid-box,
    body.dark-theme .stay-details-grid-box {
        background-color: #1E293B !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .stay-detail-label,
    [data-bs-theme="dark"] .stay-detail-label,
    body.dark-theme .stay-detail-label {
        color: #94A3B8 !important;
    }
    [data-theme="dark"] .stay-detail-value,
    [data-bs-theme="dark"] .stay-detail-value,
    body.dark-theme .stay-detail-value {
        color: #F8FAFC !important;
    }

    /* Table Theme Classes for Light & Dark Mode */
    .table-title-text {
        color: var(--dark-blue);
        font-weight: 700;
    }
    .table-subtext-muted {
        color: #64748B;
    }
    .badge-status-pending {
        background-color: #FEF3C7;
        color: #B45309;
        border: 1px solid #FDE68A;
    }

    [data-theme="dark"] .card-saas,
    [data-bs-theme="dark"] .card-saas,
    body.dark-theme .card-saas {
        background: #0F172A !important;
        border-color: #1E293B !important;
    }

    [data-theme="dark"] .card-saas h5,
    [data-bs-theme="dark"] .card-saas h5,
    body.dark-theme .card-saas h5 {
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .table-title-text,
    [data-bs-theme="dark"] .table-title-text,
    body.dark-theme .table-title-text {
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .table-subtext-muted,
    [data-bs-theme="dark"] .table-subtext-muted,
    body.dark-theme .table-subtext-muted {
        color: #94A3B8 !important;
    }

    [data-theme="dark"] table,
    [data-bs-theme="dark"] table,
    body.dark-theme table {
        color: #F1F5F9 !important;
    }

    [data-theme="dark"] table thead,
    [data-theme="dark"] table thead.table-light,
    [data-bs-theme="dark"] table thead,
    [data-bs-theme="dark"] table thead.table-light,
    body.dark-theme table thead,
    body.dark-theme table thead.table-light {
        background-color: #1E293B !important;
        border-color: #334155 !important;
    }

    [data-theme="dark"] table thead th,
    [data-bs-theme="dark"] table thead th,
    body.dark-theme table thead th {
        background-color: #1E293B !important;
        color: #60A5FA !important;
        border-color: #334155 !important;
    }

    [data-theme="dark"] table tbody tr,
    [data-bs-theme="dark"] table tbody tr,
    body.dark-theme table tbody tr {
        border-color: #1E293B !important;
        color: #F1F5F9 !important;
    }

    [data-theme="dark"] table td,
    [data-bs-theme="dark"] table td,
    body.dark-theme table td {
        color: #F1F5F9 !important;
        border-color: #1E293B !important;
    }

    [data-theme="dark"] .badge-status-pending,
    [data-bs-theme="dark"] .badge-status-pending,
    body.dark-theme .badge-status-pending {
        background-color: rgba(245, 158, 11, 0.2) !important;
        color: #FBBF24 !important;
        border-color: rgba(251, 191, 36, 0.3) !important;
    }

    [data-theme="dark"] .badge.bg-primary,
    [data-bs-theme="dark"] .badge.bg-primary,
    body.dark-theme .badge.bg-primary {
        background-color: #2563EB !important;
        color: #FFFFFF !important;
    }

    [data-theme="dark"] .badge.bg-danger,
    [data-bs-theme="dark"] .badge.bg-danger,
    body.dark-theme .badge.bg-danger {
        background-color: rgba(239, 68, 68, 0.2) !important;
        color: #F87171 !important;
        border: 1px solid rgba(248, 113, 113, 0.3) !important;
    }

    [data-theme="dark"] .badge.bg-warning,
    [data-bs-theme="dark"] .badge.bg-warning,
    body.dark-theme .badge.bg-warning {
        background-color: rgba(245, 158, 11, 0.2) !important;
        color: #FBBF24 !important;
        border: 1px solid rgba(251, 191, 36, 0.3) !important;
    }

    [data-theme="dark"] .btn-outline-primary,
    [data-bs-theme="dark"] .btn-outline-primary,
    body.dark-theme .btn-outline-primary {
        color: #60A5FA !important;
        border-color: #3B82F6 !important;
        background: transparent !important;
    }
    [data-theme="dark"] .btn-outline-primary:hover,
    [data-bs-theme="dark"] .btn-outline-primary:hover,
    body.dark-theme .btn-outline-primary:hover {
        background: #2563EB !important;
        color: #FFFFFF !important;
    }

    [data-theme="dark"] .btn-outline-danger,
    [data-bs-theme="dark"] .btn-outline-danger,
    body.dark-theme .btn-outline-danger {
        color: #F87171 !important;
        border-color: #EF4444 !important;
        background: transparent !important;
    }
    [data-theme="dark"] .btn-outline-danger:hover,
    [data-bs-theme="dark"] .btn-outline-danger:hover,
    body.dark-theme .btn-outline-danger:hover {
        background: #DC2626 !important;
        color: #FFFFFF !important;
    }

    [data-theme="dark"] .btn-light,
    [data-bs-theme="dark"] .btn-light,
    body.dark-theme .btn-light {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #94A3B8 !important;
    }

    /* Enforce Strict Single Horizontal Line & Fixed Width Alignment for Table Action Buttons */
    .table td .btn-action-view {
        min-width: 105px !important;
        width: auto !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-direction: row !important;
        line-height: 1.2 !important;
        height: 32px !important;
        min-height: 32px !important;
        max-height: 32px !important;
        padding: 0 0.65rem !important;
        font-size: 0.82rem !important;
        box-sizing: border-box !important;
    }
    .table td .btn-action-cancel {
        min-width: 96px !important;
        width: 96px !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-direction: row !important;
        line-height: 1.2 !important;
        height: 32px !important;
        min-height: 32px !important;
        max-height: 32px !important;
        padding: 0 0.5rem !important;
        font-size: 0.82rem !important;
        box-sizing: border-box !important;
    }
    .table td .btn i,
    .table th .btn i {
        display: inline-block !important;
        margin-right: 4px !important;
        line-height: 1 !important;
    }
    [data-theme="dark"] .badge-status-confirmed,
    [data-theme="dark"] .badge-status-paid,
    [data-bs-theme="dark"] .badge-status-confirmed,
    [data-bs-theme="dark"] .badge-status-paid,
    body.dark-theme .badge-status-confirmed,
    body.dark-theme .badge-status-paid {
        background-color: rgba(34, 197, 94, 0.2) !important;
        color: #4ADE80 !important;
        border-color: rgba(74, 222, 128, 0.3) !important;
    }
    [data-theme="dark"] .btn-cancel-booking,
    [data-bs-theme="dark"] .btn-cancel-booking,
    body.dark-theme .btn-cancel-booking {
        color: #F87171 !important;
    }

    /* Welcome Hero Card Light & Dark Theme */
    .user-welcome-hero-card {
        background: linear-gradient(135deg, #EAF6FF 0%, #FFFFFF 100%) !important;
        border-left: 4px solid #1769E0 !important;
    }
    .user-welcome-hero-card h2 {
        color: #172554 !important;
    }
    .user-welcome-hero-card p {
        color: #64748B !important;
    }
    [data-theme="dark"] .user-welcome-hero-card,
    [data-bs-theme="dark"] .user-welcome-hero-card,
    body.dark-theme .user-welcome-hero-card {
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%) !important;
        border: 1px solid #334155 !important;
        border-left: 4px solid #3B82F6 !important;
    }
    [data-theme="dark"] .user-welcome-hero-card h2,
    [data-bs-theme="dark"] .user-welcome-hero-card h2,
    body.dark-theme .user-welcome-hero-card h2 {
        color: #F8FAFC !important;
    }
    [data-theme="dark"] .user-welcome-hero-card p,
    [data-bs-theme="dark"] .user-welcome-hero-card p,
    body.dark-theme .user-welcome-hero-card p {
        color: #94A3B8 !important;
    }

    /* Stat Cards Light & Dark Theme */
    .stat-card-luxury {
        background: #FFFFFF !important;
        border: 1px solid #E2E8F0 !important;
    }
    .stat-card-luxury h3 {
        color: #172554 !important;
    }
    .stat-card-luxury span {
        color: #64748B !important;
    }
    [data-theme="dark"] .stat-card-luxury,
    [data-bs-theme="dark"] .stat-card-luxury,
    body.dark-theme .stat-card-luxury {
        background: #1E293B !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .stat-card-luxury h3,
    [data-bs-theme="dark"] .stat-card-luxury h3,
    body.dark-theme .stat-card-luxury h3 {
        color: #38BDF8 !important;
    }
    [data-theme="dark"] .stat-card-luxury span,
    [data-bs-theme="dark"] .stat-card-luxury span,
    body.dark-theme .stat-card-luxury span {
        color: #94A3B8 !important;
    }
</style>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER (Offset by fixed 260px sidebar) -->
<main class="main-content">

<?php 
$page_title = 'Dashboard';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-2">
    <div class="row g-4">
        <!-- DASHBOARD CONTAINER -->
        <div class="col-12">
            
            <!-- 7. WELCOME HERO SECTION (Compact Luxury Banner) -->
            <div class="card user-welcome-hero-card p-3 p-md-3.5 mb-4 border-0 shadow-sm rounded-4 d-flex flex-column justify-content-center">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <span class="badge bg-primary text-white uppercase tracking-wider px-2.5 py-1 rounded-pill font-bold mb-1.5 shadow-sm" style="font-size: 0.68rem; letter-spacing: 0.8px;">
                            GUEST PORTAL
                        </span>
                        <h2 class="font-serif-title fw-bold text-navy fs-3 mb-1">
                            Good Day, <?php echo htmlspecialchars($user['name']); ?> 👋
                        </h2>
                        <p class="small text-muted mb-0 font-medium">
                            Welcome back to Hotel Ease. Here's your luxury stay overview.
                        </p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="/hotel_website/user/rooms.php" class="btn btn-royal btn-sm px-3.5 py-2 shadow-sm d-flex align-items-center justify-content-center font-bold">
                            <i class="fa-solid fa-magnifying-glass me-1.5"></i> Explore Rooms
                        </a>
                        <a href="/hotel_website/user/my-bookings.php" class="btn btn-secondary-white btn-sm px-3.5 py-2 shadow-sm d-flex align-items-center justify-content-center font-bold">
                            <i class="fa-solid fa-calendar-check me-1.5"></i> View My Bookings
                        </a>
                    </div>
                </div>
            </div>

            <!-- 8. STATISTICS CARDS (Spec #20) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="/hotel_website/user/my-bookings.php" class="text-decoration-none">
                        <div class="stat-card-luxury card-blue text-center">
                            <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-2.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-hotel fs-5"></i>
                            </div>
                            <h3 id="stat-total-bookings" class="fw-bold text-navy mb-1 fs-2"><?php echo $totalBookings; ?></h3>
                            <span class="small text-muted font-semibold">Total Bookings</span>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="/hotel_website/user/dashboard.php#upcoming" class="text-decoration-none">
                        <div class="stat-card-luxury card-gold text-center">
                            <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-2.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-calendar-check fs-5"></i>
                            </div>
                            <h3 id="stat-upcoming-stays" class="fw-bold text-navy mb-1 fs-2"><?php echo $upcomingStays; ?></h3>
                            <span class="small text-muted font-semibold">Upcoming Stay</span>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="/hotel_website/user/my-bookings.php" class="text-decoration-none">
                        <div class="stat-card-luxury card-deep text-center">
                            <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-2.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-ban fs-5"></i>
                            </div>
                            <h3 id="stat-cancelled-bookings" class="fw-bold text-navy mb-1 fs-2"><?php echo $cancelledBookings; ?></h3>
                            <span class="small text-muted font-semibold">Cancelled Bookings</span>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="/hotel_website/user/wishlist.php" class="text-decoration-none">
                        <div class="stat-card-luxury card-sky text-center">
                            <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-2.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-heart fs-5"></i>
                            </div>
                            <h3 id="stat-saved-rooms" class="fw-bold text-navy mb-1 fs-2"><?php echo $savedRoomsCount; ?></h3>
                            <span class="small text-muted font-semibold">Favorites</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- UPCOMING STAYS SECTION (Spec #21: Royal Blue Vertical Accent) -->
            <div id="upcoming" class="card card-saas p-4 mb-4" style="border-left: 4px solid #1769E0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="upcoming-stays-pill-box">
                        <div class="icon-circle-node">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <span class="pill-title-text">Upcoming Stays</span>
                    </div>
                    <?php if ($activeStay): ?>
                        <span class="badge badge-status-confirmed px-3 py-2 rounded-pill font-semibold small">
                            <i class="fa-solid fa-circle-check me-1.5"></i><?php echo htmlspecialchars($activeStay['booking_status']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($activeStay): 
                    $checkIn = new DateTime($activeStay['check_in']);
                    $checkOut = new DateTime($activeStay['check_out']);
                    $nights = $checkIn->diff($checkOut)->days;
                    if ($nights <= 0) $nights = 1;
                    $payStatus = !empty($activeStay['payment_status']) ? $activeStay['payment_status'] : 'Paid';
                ?>
                    <hr class="my-3 opacity-25">

                    <div class="row align-items-center g-4">
                        <!-- Left Column: Image with Overlay Badge -->
                        <div class="col-lg-4 col-md-5">
                            <div class="position-relative rounded-4 overflow-hidden shadow-sm" style="height: 180px;">
                                <img src="<?php echo htmlspecialchars($activeStay['image']); ?>" class="w-100 h-100 object-fit-cover" alt="Room Image">
                                <span class="position-absolute bottom-0 start-0 m-2.5 bg-dark bg-opacity-75 text-white px-2.5 py-1 rounded-2 small font-semibold">
                                    Room <?php echo htmlspecialchars($activeStay['room_no']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Right Column: Info & Grid -->
                        <div class="col-lg-8 col-md-7">
                            <span class="text-primary font-semibold small d-block mb-1">
                                <?php echo htmlspecialchars($activeStay['type_name']); ?>
                            </span>
                            <h4 class="font-serif-title fw-bold text-dark mb-1">Luxury Grand Hotel</h4>
                            <div class="small text-secondary mb-3">
                                <i class="fa-solid fa-location-dot text-primary me-1"></i> Rajkot, Gujarat, India
                            </div>

                            <!-- Details Grid Box -->
                            <div class="stay-details-grid-box mb-3">
                                <div class="row g-2 align-items-center text-start">
                                    <div class="col-6 col-sm-3 mb-2 mb-sm-0">
                                        <span class="d-block small stay-detail-label font-medium mb-1">
                                            <i class="fa-solid fa-calendar-check text-primary me-1"></i> Check-in
                                        </span>
                                        <strong class="stay-detail-value font-bold small d-block">
                                            <?php echo date('d M Y', strtotime($activeStay['check_in'])); ?>
                                        </strong>
                                    </div>
                                    <div class="col-6 col-sm-3 mb-2 mb-sm-0">
                                        <span class="d-block small stay-detail-label font-medium mb-1">
                                            <i class="fa-solid fa-calendar-days text-primary me-1"></i> Check-out
                                        </span>
                                        <strong class="stay-detail-value font-bold small d-block">
                                            <?php echo date('d M Y', strtotime($activeStay['check_out'])); ?>
                                        </strong>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <span class="d-block small stay-detail-label font-medium mb-1">
                                            <i class="fa-solid fa-ticket text-primary me-1"></i> Reservation ID
                                        </span>
                                        <strong class="text-primary font-bold small d-block">
                                            #HTL<?php echo $activeStay['booking_id']; ?>
                                        </strong>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <span class="d-block small stay-detail-label font-medium mb-1">
                                            <i class="fa-solid fa-wallet text-primary me-1"></i> Pay status
                                        </span>
                                        <span class="badge badge-status-paid font-semibold px-2 py-1 rounded-1 small">
                                            <?php echo htmlspecialchars($payStatus); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-primary-blue rounded-3 px-4 py-2 font-semibold btn-sm shadow-sm" onclick="viewBookingDetails(<?php echo $activeStay['booking_id']; ?>)">
                                    <i class="fa-solid fa-list-check me-1.5"></i> View Details
                                </button>
                                <?php if ($activeStay['booking_status'] !== 'Cancelled' && $activeStay['booking_status'] !== 'Checked-Out'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-cancel-booking border-0 font-semibold px-3 py-2 btn-sm" onclick="confirmCancelBooking(<?php echo $activeStay['booking_id']; ?>)">
                                        <i class="fa-solid fa-xmark me-1"></i> Cancel Booking
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-calendar-minus fs-1 mb-2 text-secondary opacity-50"></i>
                        <p class="mb-2 font-semibold">No active or upcoming stays at the moment.</p>
                        <a href="/hotel_website/user/rooms.php" class="btn btn-sm btn-primary rounded-pill px-4">Browse Rooms & Book Now</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 9. SEARCH ROOMS SECTION -->
            <div class="card card-saas p-4 mb-4">
                <h5 class="font-serif-title fw-bold mb-3" style="color: var(--dark-blue);">
                    <i class="fa-solid fa-magnifying-glass text-primary me-2"></i> Where do you want to stay?
                </h5>
                <form action="/hotel_website/user/rooms.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label font-bold text-secondary small"><i class="fa-solid fa-location-dot text-primary me-1"></i> Destination / City</label>
                        <select name="location" class="form-select rounded-3 font-semibold">
                            <option value="">Select Destination / City</option>
                            <option value="Rajkot">Rajkot, Gujarat</option>
                            <option value="Ahmedabad">Ahmedabad, Gujarat</option>
                            <option value="Mumbai">Mumbai, Maharashtra</option>
                            <option value="Goa">Goa, India</option>
                            <option value="Delhi">Delhi, NCR</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-bold text-secondary small"><i class="fa-solid fa-calendar-day text-primary me-1"></i> Check-in</label>
                        <input type="date" name="check_in" value="<?php echo date('Y-m-d'); ?>" class="form-control rounded-3 font-semibold">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-bold text-secondary small"><i class="fa-solid fa-calendar-check text-primary me-1"></i> Check-out</label>
                        <input type="date" name="check_out" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" class="form-control rounded-3 font-semibold">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-bold text-secondary small"><i class="fa-solid fa-users text-primary me-1"></i> Guests</label>
                        <select name="guests" class="form-select rounded-3 font-semibold">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4+ Guests</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-12">
                        <button type="submit" class="btn btn-primary-blue w-100 uppercase tracking-wider py-2">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search Rooms
                        </button>
                    </div>
                </form>
            </div>



            <!-- 11. RECENT BOOKINGS TABLE -->
            <div class="card card-saas p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="font-serif-title fw-bold mb-0" style="color: var(--dark-blue);">
                        <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Bookings
                    </h5>
                    <a href="/hotel_website/user/my-bookings.php" class="small fw-bold text-primary text-decoration-none">View All →</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle small mb-0">
                        <thead class="table-light text-nowrap">
                            <tr class="text-nowrap">
                                <th class="text-nowrap">Booking ID</th>
                                <th class="text-nowrap">Hotel</th>
                                <th class="text-nowrap">Room</th>
                                <th class="text-nowrap">Check-in</th>
                                <th class="text-nowrap">Check-out</th>
                                <th class="text-nowrap">Amount</th>
                                <th class="text-nowrap">Payment</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentBookings)): ?>
                                <?php foreach ($recentBookings as $b): 
                                    $pStatus = $b['payment_status'] ?? 'Paid';
                                    $pBadgeClass = ($pStatus === 'Paid') ? 'badge-status-paid' : 'badge-status-pending';
                                ?>
                                    <tr>
                                        <td class="fw-bold text-primary">#HTL<?php echo $b['booking_id']; ?></td>
                                        <td class="fw-bold table-title-text">Luxury Grand Hotel</td>
                                        <td class="fw-semibold">Room <?php echo htmlspecialchars($b['room_no']); ?> <small class="table-subtext-muted">(<?php echo htmlspecialchars($b['type_name']); ?>)</small></td>
                                        <td><?php echo date('d M Y', strtotime($b['check_in'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($b['check_out'])); ?></td>
                                        <td class="fw-bold table-title-text"><?php echo format_price($b['total_amount']); ?></td>
                                        <td><span class="badge <?php echo $pBadgeClass; ?> font-semibold px-2.5 py-1 rounded-pill"><?php echo htmlspecialchars($pStatus); ?></span></td>
                                        <td><?php echo get_status_badge($b['booking_status']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-end gap-1.5 flex-nowrap text-nowrap">
                                                 <a href="/hotel_website/user/booking-details.php?id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-outline-primary btn-action-view rounded-3 font-semibold text-nowrap" title="View Details">
                                                    <i class="fa-solid fa-eye"></i> View Details
                                                </a>

                                                <?php if ($b['booking_status'] !== 'Cancelled' && $b['booking_status'] !== 'Checked-Out'): ?>
                                                    <a href="/hotel_website/user/cancel-booking.php?id=<?php echo $b['booking_id']; ?>" onclick="return confirm('Are you sure you want to cancel reservation #HE-<?php echo $b['booking_id']; ?>?')" class="btn btn-sm btn-outline-danger btn-action-cancel rounded-3 font-semibold text-nowrap" title="Cancel Reservation">
                                                        <i class="fa-solid fa-xmark"></i> Cancel
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-light border btn-action-cancel rounded-3 font-semibold text-nowrap" disabled>
                                                        <i class="fa-solid fa-ban"></i> Cancelled
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No recent bookings found. <a href="/hotel_website/user/rooms.php" class="text-primary fw-bold">Explore rooms to book!</a></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 12. RECOMMENDED HOTELS -->
            <?php if (!empty($featuredRooms)): ?>
                <div class="card card-saas p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="font-serif-title fw-bold mb-0" style="color: var(--dark-blue);">
                            <i class="fa-solid fa-thumbs-up text-primary me-2"></i> Recommended Hotels
                        </h5>
                        <a href="/hotel_website/user/rooms.php" class="small fw-bold text-primary text-decoration-none">View All →</a>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($featuredRooms as $fr): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="card room-card-modern border-0 h-100">
                                    <div class="room-card-img-wrap" style="height: 150px; cursor: pointer;" onclick="openRoomGalleryModal('<?php echo htmlspecialchars($fr['room_no']); ?>', '<?php echo htmlspecialchars($fr['type_name']); ?>', '<?php echo htmlspecialchars($fr['image']); ?>')">
                                        <img src="<?php echo htmlspecialchars($fr['image']); ?>" alt="Room Image">
                                        <div class="room-card-img-overlay"></div>
                                        <span class="room-type-pill" style="font-size: 0.65rem; padding: 4px 10px;">
                                            <?php echo htmlspecialchars($fr['type_name']); ?>
                                        </span>
                                        <?php $isFav = in_array($fr['room_id'], $userWishlistRoomIds ?? []); ?>
                                        <button type="button" 
                                                onclick="toggleWishlistRoom(event, <?php echo $fr['room_id']; ?>, this)" 
                                                class="fav-btn-glass <?php echo $isFav ? 'text-danger' : ''; ?>" style="width: 32px; height: 32px; font-size: 0.9rem;"
                                                title="<?php echo $isFav ? 'Remove from favorites' : 'Save to favorites'; ?>"
                                                data-room-id="<?php echo $fr['room_id']; ?>"
                                                data-saved="<?php echo $isFav ? '1' : '0'; ?>">
                                            <i class="<?php echo $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart'; ?>"></i>
                                        </button>
                                        <div class="room-rating-pill" style="font-size: 0.7rem; padding: 2px 8px;">
                                            <i class="fa-solid fa-star text-warning"></i> 5.0
                                        </div>
                                    </div>
                                    <div class="p-3 d-flex flex-column justify-content-between flex-grow-1">
                                        <div>
                                            <h6 class="fw-bold font-serif-title mb-1 text-truncate text-navy">Room <?php echo htmlspecialchars($fr['room_no']); ?></h6>
                                            <p class="small text-muted mb-2 text-truncate font-medium"><i class="fa-solid fa-location-dot text-primary me-1"></i>Rajkot, Gujarat</p>
                                            <span class="fw-bold text-primary font-serif-title fs-5"><?php echo format_price($fr['price']); ?> <small class="text-muted fw-normal fs-6">/ night</small></span>
                                        </div>
                                        <div class="pt-3 mt-auto border-top">
                                            <a href="/hotel_website/user/book-room.php?id=<?php echo $fr['room_id']; ?>" class="btn btn-primary-blue btn-sm w-100 rounded-pill font-semibold">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>



            <!-- 13. SAVED FAVORITE ROOMS -->
            <div class="card card-saas p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="font-serif-title fw-bold mb-0" style="color: var(--dark-blue);">
                        <i class="fa-solid fa-heart text-danger me-2"></i> My Favorite Rooms
                    </h5>
                    <a href="/hotel_website/user/wishlist.php" class="small fw-bold text-danger text-decoration-none">View All →</a>
                </div>

                <?php if (!empty($wishlistRooms)): ?>
                    <div class="row g-3" id="favoriteRoomsContainer">
                        <?php foreach ($wishlistRooms as $wr): ?>
                            <div class="col-md-6 col-lg-3" id="fav-card-<?php echo $wr['wishlist_id']; ?>">
                                <div class="card room-card-modern border-0 h-100">
                                    <div class="room-card-img-wrap" style="height: 150px; cursor: pointer;" onclick="openRoomGalleryModal('<?php echo htmlspecialchars($wr['room_no']); ?>', '<?php echo htmlspecialchars($wr['type_name']); ?>', '<?php echo htmlspecialchars($wr['image']); ?>')">
                                        <img src="<?php echo htmlspecialchars($wr['image']); ?>" alt="Room Image">
                                        <div class="room-card-img-overlay"></div>
                                        <span class="room-type-pill" style="font-size: 0.65rem; padding: 4px 10px;">
                                            <?php echo htmlspecialchars($wr['type_name']); ?>
                                        </span>
                                        <button type="button" 
                                                onclick="removeDashboardFavorite(event, <?php echo $wr['room_id']; ?>, 'fav-card-<?php echo $wr['wishlist_id']; ?>')" 
                                                class="fav-btn-glass text-danger" style="width: 32px; height: 32px; font-size: 0.9rem;" 
                                                title="Remove from favorites">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                        <div class="room-rating-pill" style="font-size: 0.7rem; padding: 2px 8px;">
                                            <i class="fa-solid fa-star text-warning"></i> 5.0
                                        </div>
                                    </div>
                                    <div class="p-3 d-flex flex-column justify-content-between flex-grow-1">
                                        <div>
                                            <h6 class="fw-bold font-serif-title mb-1 text-truncate text-navy">Room <?php echo htmlspecialchars($wr['room_no']); ?></h6>
                                            <p class="small text-muted mb-2 text-truncate font-medium"><i class="fa-solid fa-location-dot text-primary me-1"></i>Rajkot, Gujarat</p>
                                            <span class="fw-bold text-primary font-serif-title fs-5"><?php echo format_price($wr['price']); ?> <small class="text-muted fw-normal fs-6">/ night</small></span>
                                        </div>
                                        <div class="pt-3 mt-auto border-top d-flex gap-2">
                                            <a href="/hotel_website/user/book-room.php?id=<?php echo $wr['room_id']; ?>" class="btn btn-primary-blue btn-sm flex-fill rounded-pill font-semibold">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-heart-crack fs-1 mb-2 text-danger opacity-50"></i>
                        <h6 class="fw-bold mb-1" style="color: var(--dark-blue);">No favorite rooms saved yet.</h6>
                        <p class="small text-secondary mb-3">Explore our luxury rooms and save your favorite stays!</p>
                        <a href="/hotel_website/user/rooms.php" class="btn btn-primary-blue btn-sm px-4">Explore Rooms</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
function removeDashboardFavorite(e, roomId, cardId) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const cardEl = document.getElementById(cardId);
    if (cardEl) {
        cardEl.style.transition = 'all 0.3s ease';
        cardEl.style.opacity = '0';
        cardEl.style.transform = 'scale(0.85)';
        setTimeout(() => {
            cardEl.remove();
            const container = document.getElementById('favoriteRoomsContainer');
            if (container && container.children.length === 0) {
                location.reload();
            }
        }, 300);
    }
    fetch('/hotel_website/api/realtime.php?action=toggle_wishlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'room_id=' + encodeURIComponent(roomId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const statSaved = document.getElementById('stat-saved-rooms');
            if (statSaved && data.total_saved !== undefined) {
                statSaved.textContent = data.total_saved;
            }
            if (typeof showDynamicToast === 'function') {
                showDynamicToast('Room removed from your saved list.', 'success');
            }
        }
    });
}



function refreshUserRealtimeStats() {
    fetch('/hotel_website/api/realtime.php?action=get_user_stats')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                const d = data.data;
                const elTotal = document.getElementById('stat-total-bookings');
                const elUpcoming = document.getElementById('stat-upcoming-stays');
                const elCancelled = document.getElementById('stat-cancelled-bookings');
                const elSaved = document.getElementById('stat-saved-rooms');
                
                if (elTotal) elTotal.textContent = d.total_bookings;
                if (elUpcoming) elUpcoming.textContent = d.upcoming_stays;
                if (elCancelled) elCancelled.textContent = d.cancelled_bookings !== undefined ? d.cancelled_bookings : 0;
                if (elSaved) elSaved.textContent = d.saved_rooms;
            }
        })
        .catch(err => console.error('Realtime sync error:', err));
}
setInterval(refreshUserRealtimeStats, 6000);

function viewBookingDetails(bookingId) {
    if (!bookingId) return;
    window.location.href = '/hotel_website/user/booking-details.php?id=' + encodeURIComponent(bookingId);
}

function confirmCancelBooking(bookingId) {
    if (!bookingId) return;
    if (confirm('Are you sure you want to cancel reservation #HTL' + bookingId + '?')) {
        window.location.href = '/hotel_website/user/cancel-booking.php?id=' + encodeURIComponent(bookingId);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
