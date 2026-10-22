<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();
$admin = get_logged_admin();

// Dynamic Calculations from MySQL Database
$totalRooms = 0;
$availableRooms = 0;
$occupiedRooms = 0;
$totalCustomers = 0;
$todayCheckIns = 0;
$todayCheckOuts = 0;
$totalBookings = 0;
$totalRevenue = 0.00;
$recentReservations = [];
$floorPlanRooms = [];

if ($pdo) {
    // 1. Total Rooms
    $totalRooms = $pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();

    // 2. Available Rooms
    $availableRooms = $pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `status` = 'Available'")->fetchColumn();

    // 3. Occupied Rooms
    $occupiedRooms = $pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `status` = 'Occupied'")->fetchColumn();

    // 4. Total Customers
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();

    // 5. Today's Check-ins
    $todayCheckIns = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `check_in` = CURDATE()")->fetchColumn();

    // 6. Today's Check-outs
    $todayCheckOuts = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `check_out` = CURDATE()")->fetchColumn();

    // 7. Total Bookings
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();

    // 8. Total Revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `payments` WHERE `payment_status` = 'Paid'")->fetchColumn();

    // Recent Bookings List
    $stmt = $pdo->query("SELECT b.*, c.name as customer_name, r.room_no, rt.type_name FROM `bookings` b JOIN `customers` c ON b.customer_id = c.customer_id JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id ORDER BY b.booking_id DESC LIMIT 5");
    $recentReservations = $stmt->fetchAll();

    $floorStmt = $pdo->query("SELECT room_no, status, floor FROM `rooms` ORDER BY floor ASC, room_no ASC");
    $floorPlanRooms = $floorStmt->fetchAll();
}
?>

<style>
.table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 8px;
    padding-bottom: 2px;
}
.table-responsive::-webkit-scrollbar {
    height: 6px;
}
.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
/* Enforce Strict Single Horizontal Line & Fixed Width Alignment for Table Action Buttons */
.table td .btn-action-checkin,
.table td .btn-action-checkout {
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-direction: row !important;
    line-height: 1.2 !important;
    height: 32px !important;
    min-height: 32px !important;
    max-height: 32px !important;
    padding: 0 0.75rem !important;
    font-size: 0.82rem !important;
    box-sizing: border-box !important;
}
.table td .btn i {
    display: inline-block !important;
    margin-right: 5px !important;
    line-height: 1 !important;
}
.dashboard-floor-plan {
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    background: #FFFFFF;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    padding: 1.25rem;
}
.floor-plan-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.floor-plan-heading h5 {
    margin: 0;
    color: #0F172A;
    font-weight: 700;
}
.floor-plan-heading p {
    margin: 0.25rem 0 0;
    color: #64748B;
    font-size: 0.8rem;
}
.floor-plan-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.floor-plan-actions a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.75rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
}
.floor-plan-actions .primary { color: #FFFFFF; background: #2563EB; }
.floor-plan-actions .secondary { color: #1D4ED8; background: #EFF6FF; border: 1px solid #BFDBFE; }
.floor-plan-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    color: #64748B;
    font-size: 0.75rem;
}
.floor-plan-legend span { display: inline-flex; align-items: center; gap: 0.3rem; }
.floor-plan-legend i { font-size: 0.7rem; }
.floor-plan-legend .available { color: #16A34A; }
.floor-plan-legend .occupied { color: #B91C1C; }
.floor-plan-legend .reserved { color: #B45309; }
.floor-plan-legend .cleaning { color: #2563EB; }
.floor-plan-legend .maintenance { color: #64748B; }
.floor-plan-row { margin-top: 1rem; }
.floor-plan-row h6 {
    margin: 0 0 0.55rem;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
}
.floor-plan-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}
.floor-room-tile {
    display: flex;
    width: 60px;
    height: 58px;
    align-items: center;
    justify-content: center;
    border: 2px solid;
    border-radius: 9px;
    color: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.floor-room-tile:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(15, 23, 42, 0.12); }
.floor-room-tile.available { color: #15803D; background: #DCFCE7; border-color: #86EFAC; }
.floor-room-tile.occupied { color: #B91C1C; background: #FEE2E2; border-color: #FCA5A5; }
.floor-room-tile.reserved { color: #B45309; background: #FEF3C7; border-color: #FBBF24; }
.floor-room-tile.cleaning { color: #1D4ED8; background: #DBEAFE; border-color: #93C5FD; }
.floor-room-tile.maintenance { color: #64748B; background: #F1F5F9; border-color: #CBD5E1; }
@media (max-width: 700px) {
    .floor-plan-heading { display: block; }
    .floor-plan-legend { margin-top: 0.75rem; }
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER (Offset by fixed 260px sidebar - Exact User Layout Match) -->
<main class="main-content">

<?php 
$page_title = 'Admin Dashboard';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-2">
    <!-- Header Banner (Compact Size) -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-3.5 mb-4 bg-navy text-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="background: linear-gradient(135deg, #071A2F 0%, #0B2744 100%);">
        <div>
            <span class="badge bg-primary text-white uppercase tracking-wider px-2.5 py-1 rounded-pill font-semibold mb-1.5" style="border-left: 3px solid #D4AF5A; font-size: 0.68rem; letter-spacing: 0.8px;">OPERATIONS CONTROL</span>
            <h3 class="font-serif-title fw-bold text-white fs-3 mb-1">Hotel Ease Administration 👋</h3>
            <p class="text-white-50 small mb-0">Manage your hotel operations from one place.</p>
        </div>
    </div>

    <!-- 8 KPI Statistic Cards (Specs #26) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-sky">
                <span class="small text-muted font-semibold uppercase">Total Rooms</span>
                <h3 class="fw-bold text-navy mb-0 mt-1"><?php echo $totalRooms; ?></h3>
                <span class="small text-muted">Active Inventory</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-blue">
                <span class="small text-muted font-semibold uppercase">Available Rooms</span>
                <h3 class="fw-bold text-success mb-0 mt-1"><?php echo $availableRooms; ?></h3>
                <span class="small text-success font-semibold">Ready to Book</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-deep">
                <span class="small text-muted font-semibold uppercase">Occupied Rooms</span>
                <h3 class="fw-bold text-danger mb-0 mt-1"><?php echo $occupiedRooms; ?></h3>
                <span class="small text-danger font-semibold">Guests In-House</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-blue">
                <span class="small text-muted font-semibold uppercase">Total Users</span>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?php echo $totalCustomers; ?></h3>
                <span class="small text-muted">Registered Guests</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-blue">
                <span class="small text-muted font-semibold uppercase">Today's Check-ins</span>
                <h3 class="fw-bold text-warning mb-0 mt-1"><?php echo $todayCheckIns; ?></h3>
                <span class="small text-muted">Expected Arrivals</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-deep">
                <span class="small text-muted font-semibold uppercase">Today's Check-outs</span>
                <h3 class="fw-bold text-secondary mb-0 mt-1"><?php echo $todayCheckOuts; ?></h3>
                <span class="small text-muted">Departures Today</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-deep">
                <span class="small text-muted font-semibold uppercase">Total Bookings</span>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?php echo $totalBookings; ?></h3>
                <span class="small text-muted">Lifetime Stays</span>
            </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="stat-card-luxury card-gold">
                <span class="small text-muted font-semibold uppercase">Total Revenue</span>
                <h3 class="fw-bold text-gold mb-0 mt-1"><?php echo format_price($totalRevenue); ?></h3>
                <span class="small text-muted font-semibold">Settled Earnings</span>
            </div>
        </div>
    </div>

    <!-- Visual Floor Plan -->
    <section class="dashboard-floor-plan mb-4" aria-labelledby="floorPlanTitle">
        <div class="floor-plan-heading">
            <div>
                <h5 id="floorPlanTitle" class="font-serif-title">Visual Floor Plan</h5>
                <p>Monitor room availability and housekeeping status by floor.</p>
            </div>
            <div>
                <div class="floor-plan-actions justify-content-end mb-2">
                    <a class="primary" href="/hotel_website/admin/rooms.php?open=add"><i class="fa-solid fa-plus"></i>Add Room</a>
                    <a class="secondary" href="/hotel_website/admin/rooms.php"><i class="fa-solid fa-sliders"></i>Manage Rooms</a>
                </div>
                <div class="floor-plan-legend" aria-label="Room status legend">
                    <span class="available"><i class="fa-solid fa-square"></i>Available</span>
                    <span class="occupied"><i class="fa-solid fa-square"></i>Occupied</span>
                    <span class="reserved"><i class="fa-solid fa-square"></i>Reserved</span>
                    <span class="cleaning"><i class="fa-solid fa-square"></i>Cleaning</span>
                    <span class="maintenance"><i class="fa-solid fa-square"></i>Maint.</span>
                </div>
            </div>
        </div>
        <?php if (!empty($floorPlanRooms)): ?>
            <?php
            $roomsByFloor = [];
            foreach ($floorPlanRooms as $floorRoom) {
                $floorName = $floorRoom['floor'] ?: 'Floor 1';
                $roomsByFloor[$floorName][] = $floorRoom;
            }
            ?>
            <?php foreach ($roomsByFloor as $floorName => $floorRooms): ?>
                <div class="floor-plan-row">
                    <h6><?php echo htmlspecialchars($floorName); ?></h6>
                    <div class="floor-plan-grid">
                        <?php foreach ($floorRooms as $floorRoom):
                            $roomStatus = strtolower($floorRoom['status'] ?? 'available');
                            $roomStatusClass = in_array($roomStatus, ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'], true) ? $roomStatus : 'maintenance';
                        ?>
                            <a class="floor-room-tile <?php echo $roomStatusClass; ?>" href="/hotel_website/admin/rooms.php" title="Room <?php echo htmlspecialchars($floorRoom['room_no']); ?> - <?php echo htmlspecialchars($floorRoom['status']); ?>">
                                <?php echo htmlspecialchars($floorRoom['room_no']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted small mb-0">No rooms have been added to the inventory yet.</p>
        <?php endif; ?>
    </section>

    <!-- Recent Guest Bookings Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="font-serif-title fw-bold text-navy mb-0">Recent Guest Reservations</h5>
            <a href="/hotel_website/admin/bookings.php" class="small fw-bold text-primary text-decoration-none">View All Bookings →</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer Name</th>
                        <th>Room</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end text-nowrap" style="width: 240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentReservations)): ?>
                        <?php foreach ($recentReservations as $res): ?>
                            <tr>
                                <td class="fw-bold text-primary">#HE-<?php echo $res['booking_id']; ?></td>
                                <td class="fw-semibold text-navy"><?php echo htmlspecialchars($res['customer_name']); ?></td>
                                <td>Room <?php echo htmlspecialchars($res['room_no']); ?> <small class="text-muted">(<?php echo htmlspecialchars($res['type_name']); ?>)</small></td>
                                <td><?php echo date('Y-m-d', strtotime($res['check_in'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($res['check_out'])); ?></td>
                                <td class="fw-bold text-navy"><?php echo format_price($res['total_amount']); ?></td>
                                <td><?php echo get_status_badge($res['booking_status']); ?></td>
                                <td class="text-end text-nowrap" style="width: 240px;">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5 flex-nowrap text-nowrap">
                                        <a href="/hotel_website/admin/bookings.php" class="btn btn-sm btn-outline-info rounded-3 font-semibold text-nowrap" title="View Reservation Details">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </a>
                                        <?php if ($res['booking_status'] === 'Pending' || $res['booking_status'] === 'Confirmed'): ?>
                                            <a href="/hotel_website/admin/check-in.php?id=<?php echo $res['booking_id']; ?>" class="btn btn-sm btn-outline-success btn-action-checkin rounded-3 font-semibold text-nowrap" title="Check-In Guest">
                                                <i class="fa-solid fa-right-to-bracket"></i> Check-In
                                            </a>
                                        <?php elseif ($res['booking_status'] === 'Checked-In'): ?>
                                            <a href="/hotel_website/admin/check-out.php?id=<?php echo $res['booking_id']; ?>" class="btn btn-sm btn-outline-danger btn-action-checkout rounded-3 font-semibold text-nowrap" title="Check-Out Guest">
                                                <i class="fa-solid fa-right-from-bracket"></i> Check-Out
                                            </a>
                                        <?php elseif ($res['booking_status'] === 'Checked-Out'): ?>
                                            <button class="btn btn-sm btn-light border rounded-3 font-semibold text-nowrap" disabled>
                                                <i class="fa-solid fa-circle-check text-success"></i> Checked-Out
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light border rounded-3 font-semibold text-nowrap" disabled>
                                                <i class="fa-solid fa-ban text-muted"></i> Cancelled
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No recent reservations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
