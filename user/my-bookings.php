<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch logged-in user profile details
$userStmt = $pdo->prepare("SELECT * FROM `customers` WHERE `customer_id` = :uid");
$userStmt->execute(['uid' => $userId]);
$userProfile = $userStmt->fetch(PDO::FETCH_ASSOC);

// Fetch bookings with room, room_types, customer, and payment information
$stmt = $pdo->prepare("
    SELECT b.*, r.room_no, r.image, r.price as room_price, r.amenities,
           rt.type_name,
           c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile,
           p.payment_status, p.payment_mode, p.amount as paid_amount, p.payment_date
    FROM `bookings` b 
    JOIN `rooms` r ON b.room_id = r.room_id 
    JOIN `room_types` rt ON r.room_type_id = rt.room_type_id 
    JOIN `customers` c ON b.customer_id = c.customer_id
    LEFT JOIN `payments` p ON b.booking_id = p.booking_id 
    WHERE b.customer_id = :uid 
    ORDER BY b.booking_id DESC
");
$stmt->execute(['uid' => $userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalBookingsCount = count($bookings);
?>

<style>
/* =========================================================
   HOTELEASE LUXURY MY BOOKINGS STYLING & ACTIONS
   ========================================================= */

.my-bookings-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Page Header & Badge */
.page-title-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-weight: 700;
    color: #0F172A;
    font-size: 1.6rem;
}

.total-bookings-pill {
    background-color: #E0F2FE !important;
    color: #2563EB !important;
    border: 1px solid #BAE6FD !important;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    padding: 5px 12px !important;
    border-radius: 50px !important;
    white-space: nowrap !important;
}

/* Card Container */
.bookings-card-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto !important;
    width: 100%;
    -webkit-overflow-scrolling: touch;
}

/* Table Styling */
.bookings-table {
    margin-bottom: 0;
    width: 100%;
    min-width: 860px;
    table-layout: auto;
}

.bookings-table thead th {
    background-color: #F8FAFC !important;
    color: #64748B !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding: 10px 12px !important;
    border-bottom: 1px solid #E2E8F0 !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}

.bookings-table tbody td {
    padding: 10px 12px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #F1F5F9 !important;
    color: #0F172A;
    white-space: nowrap !important;
}

.booking-row-item {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.bookings-table tbody tr:last-child td {
    border-bottom: none !important;
}

.bookings-table tbody tr:hover td {
    background-color: #F8FAFC !important;
}

/* Ref Code Styling */
.ref-code-text {
    color: #3B82F6 !important;
    font-family: 'Courier New', Courier, monospace;
    font-weight: 700;
    white-space: nowrap !important;
    font-size: 0.84rem;
    letter-spacing: 0.2px;
}

/* Room Thumbnail Box */
.room-thumb-box {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
    border: 1px solid #E2E8F0;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
}

.room-thumb-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.25s ease;
}

.room-thumb-box:hover img {
    transform: scale(1.1);
}

/* Status Badges */
.badge-status-confirmed {
    background-color: #ECFDF5 !important;
    color: #059669 !important;
    border: 1px solid #6EE7B7 !important;
    border-radius: 50px !important;
    padding: 3px 9px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
}

.badge-status-completed {
    background-color: #F3E8FF !important;
    color: #7E22CE !important;
    border: 1px solid #D8B4FE !important;
    border-radius: 50px !important;
    padding: 3px 9px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
}

.badge-status-pending {
    background-color: #FFFBEB !important;
    color: #D97706 !important;
    border: 1px solid #FCD34D !important;
    border-radius: 50px !important;
    padding: 3px 9px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
}

.badge-status-cancelled {
    background-color: #FEF2F2 !important;
    color: #DC2626 !important;
    border: 1px solid #FCA5A5 !important;
    border-radius: 50px !important;
    padding: 3px 9px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
}

.badge-pay-paid {
    background-color: #F0FDF4 !important;
    color: #166534 !important;
    border: 1px solid #BBF7D0 !important;
    border-radius: 50px !important;
    padding: 2px 7px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
}

.badge-pay-pending {
    background-color: #FFF7ED !important;
    color: #C2410C !important;
    border: 1px solid #FFEDD5 !important;
    border-radius: 50px !important;
    padding: 2px 7px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
}

.badge-pay-partial {
    background-color: #FEFCE8 !important;
    color: #A16207 !important;
    border: 1px solid #FEF08A !important;
    border-radius: 50px !important;
    padding: 2px 7px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
}

/* =========================================================
   ACTIONS COLUMN & BUTTON STYLING (MATCHING SCREENSHOT)
   ========================================================= */
.booking-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: nowrap;
}

.btn-invoice,
.btn-cancel,
.btn-cancelled,
.btn-invoice-compact,
.btn-cancel-compact,
.btn-cancelled-compact {
    min-height: 32px;
    height: 32px;
    padding: 5px 14px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    text-decoration: none !important;
}

/* 🔵 INVOICE BUTTON (Solid Luxury Purple/Blue Pill) */
.btn-invoice,
.btn-invoice-compact {
    background-color: #5B50E5 !important;
    color: #FFFFFF !important;
    border: 1px solid #5B50E5 !important;
    box-shadow: 0 2px 6px rgba(91, 80, 229, 0.25);
}
.btn-invoice:hover,
.btn-invoice-compact:hover {
    background-color: #4338CA !important;
    border-color: #4338CA !important;
    color: #FFFFFF !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 56, 202, 0.35);
}
.btn-invoice:active,
.btn-invoice-compact:active {
    transform: scale(0.97);
}

/* 🔴 CANCEL BUTTON (Red Outline Pill) */
.btn-cancel,
.btn-cancel-compact {
    background-color: #FFFFFF !important;
    color: #E11D48 !important;
    border: 1px solid #FECDD3 !important;
}
.btn-cancel:hover,
.btn-cancel-compact:hover {
    background-color: #FFE4E6 !important;
    border-color: #E11D48 !important;
    color: #BE123C !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(225, 29, 72, 0.15);
}
.btn-cancel:active,
.btn-cancel-compact:active {
    transform: scale(0.97);
}

/* ⚪ CANCELLED BUTTON (Disabled Subtle Gray Pill) */
.btn-cancelled,
.btn-cancelled-compact {
    background-color: #F8FAFC !important;
    color: #94A3B8 !important;
    border: 1px solid #E2E8F0 !important;
    cursor: not-allowed !important;
    opacity: 0.75;
}

/* Mobile Stacked Card View */
.mobile-booking-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 14px;
    cursor: pointer;
    box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
}

/* Dark Theme Compatibility */
[data-theme="dark"] .bookings-card-wrapper,
[data-bs-theme="dark"] .bookings-card-wrapper,
body.dark-theme .bookings-card-wrapper {
    background-color: #0F172A !important;
    border-color: #1E293B !important;
}

[data-theme="dark"] .bookings-table thead th,
[data-bs-theme="dark"] .bookings-table thead th,
body.dark-theme .bookings-table thead th {
    background-color: #1E293B !important;
    color: #94A3B8 !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .bookings-table tbody td,
[data-bs-theme="dark"] .bookings-table tbody td,
body.dark-theme .bookings-table tbody td {
    border-color: #1E293B !important;
    color: #F1F5F9 !important;
}

[data-theme="dark"] .bookings-table tbody tr:hover td,
[data-bs-theme="dark"] .bookings-table tbody tr:hover td,
body.dark-theme .bookings-table tbody tr:hover td {
    background-color: #1E293B !important;
}

/* Printable Modal CSS */
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableUserInvoiceContent, #printableUserInvoiceContent * {
        visibility: visible !important;
    }
    #printableUserInvoiceContent {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        background: #FFFFFF !important;
        color: #0F172A !important;
    }
}
</style>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
<?php
$page_title = 'My Bookings';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-2 px-md-3 py-3 my-bookings-container">

    <!-- PAGE TITLE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h2 class="page-title-heading mb-1">My Bookings</h2>
            <p class="small text-muted mb-0">View and manage your luxury room reservations and stay history.</p>
        </div>
        <div>
            <span class="badge total-bookings-pill" id="totalBookingsBadge">
                <i class="fa-solid fa-bookmark me-1.5"></i> <span id="totalCountNumber"><?php echo $totalBookingsCount; ?></span> total bookings
            </span>
        </div>
    </div>

    <!-- SEARCH & FILTER CONTROLS BAR -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white" style="border: 1px solid #E2E8F0 !important;">
        <div class="row g-2 align-items-center">
            <!-- Search Input -->
            <div class="col-md-7 col-lg-8 col-12">
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" 
                           id="bookingSearchInput" 
                           class="form-control rounded-3 ps-5 py-2 border-secondary-subtle small" 
                           placeholder="Search bookings by ID, room name, room number, status..." 
                           onkeyup="filterBookingsList()" 
                           oninput="filterBookingsList()">
                </div>
            </div>
            <!-- Status Filter -->
            <div class="col-md-5 col-lg-4 col-12">
                <div class="d-flex align-items-center justify-content-md-end gap-2">
                    <label for="bookingStatusFilter" class="small font-bold text-muted text-nowrap d-none d-sm-inline">Status:</label>
                    <select id="bookingStatusFilter" class="form-select rounded-3 py-2 border-secondary-subtle small font-semibold" style="max-width: 220px;" onchange="filterBookingsList()">
                        <option value="all">All Statuses</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed / Checked-Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- DESKTOP TABLE VIEW (>= 768px) -->
    <div class="card bookings-card-wrapper overflow-hidden mb-4 d-none d-md-block" id="bookingsTableCard">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table bookings-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 13%; min-width: 110px;">REFERENCE ID</th>
                        <th style="width: 13%; min-width: 115px;">STATUS</th>
                        <th style="width: 24%; min-width: 180px;">ROOM &amp; TYPE</th>
                        <th style="width: 20%; min-width: 165px;">DATES &amp; GUESTS</th>
                        <th style="width: 10%; min-width: 90px;">AMOUNT</th>
                        <th class="text-end" style="width: 20%; min-width: 185px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="bookingsTbody">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $b): 
                            $st = $b['booking_status'];
                            
                            // Determine Payment Status logic
                            $rawPayStatus = $b['payment_status'] ?? '';
                            if (!empty($rawPayStatus) && strtolower($rawPayStatus) === 'paid') {
                                $payStatus = 'Paid';
                            } elseif (strtolower($rawPayStatus) === 'partial') {
                                $payStatus = 'Partial';
                            } else {
                                if ($st === 'Confirmed') {
                                    $payStatus = 'Paid';
                                } elseif ($st === 'Pending') {
                                    $payStatus = 'Payment Pending';
                                } else {
                                    $payStatus = !empty($rawPayStatus) ? ucfirst($rawPayStatus) : 'Payment Pending';
                                }
                            }

                            // Calculate nights
                            $dIn = new DateTime($b['check_in']);
                            $dOut = new DateTime($b['check_out']);
                            $nights = max(1, $dIn->diff($dOut)->days);
                            
                            $refCodeFormatted = 'HE-2026-' . str_pad($b['booking_id'], 4, '0', STR_PAD_LEFT);
                            $refCodePlain = 'HE-2026-00' . $b['booking_id'];

                            // Booking payloads
                            $bookingPayload = [
                                'ref' => $refCodeFormatted,
                                'booking_id' => $b['booking_id'],
                                'guest_name' => $b['customer_name'] ?? ($_SESSION['user_name'] ?? 'Valued Guest'),
                                'guest_email' => $b['customer_email'] ?? ($_SESSION['user_email'] ?? ''),
                                'guest_mobile' => $b['customer_mobile'] ?? '',
                                'room_no' => $b['room_no'],
                                'type_name' => $b['type_name'],
                                'image' => $b['image'],
                                'check_in' => date('d M Y', strtotime($b['check_in'])),
                                'check_out' => date('d M Y', strtotime($b['check_out'])),
                                'nights' => $nights,
                                'adults' => $b['adults'],
                                'children' => $b['children'] ?? 0,
                                'special_request' => $b['special_request'] ?? '',
                                'rate' => number_format($b['total_amount'] / $nights, 2),
                                'subtotal' => number_format($b['total_amount'], 2),
                                'tax' => number_format($b['total_amount'] * 0.10, 2),
                                'total' => number_format($b['total_amount'] * 1.10, 2),
                                'status' => $st,
                                'payment_status' => $payStatus,
                                'payment_mode' => $b['payment_mode'] ?? 'Card / Online',
                                'created_at' => date('M d, Y', strtotime($b['created_at']))
                            ];
                            $jsonBooking = htmlspecialchars(json_encode($bookingPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $searchStr = htmlspecialchars(strtolower($refCodePlain . ' ' . $refCodeFormatted . ' ' . $b['type_name'] . ' room ' . $b['room_no'] . ' ' . $b['booking_id'] . ' ' . $st . ' ' . $payStatus), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="booking-row-item" 
                                onclick="handleBookingRowClick(event, this)"
                                data-booking="<?php echo $jsonBooking; ?>"
                                data-booking-id="<?php echo $b['booking_id']; ?>"
                                data-search="<?php echo $searchStr; ?>"
                                data-status="<?php echo strtolower($st); ?>"
                                title="Click to view details for <?php echo $refCodeFormatted; ?>">
                                
                                <!-- REFERENCE ID -->
                                <td class="text-nowrap">
                                    <span class="ref-code-text"><?php echo $refCodeFormatted; ?></span>
                                </td>

                                <!-- STATUS & PAYMENT BADGES -->
                                <td class="text-nowrap" id="status-cell-<?php echo $b['booking_id']; ?>">
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <!-- Booking Status -->
                                        <?php if ($st === 'Confirmed'): ?>
                                            <span class="badge badge-status-confirmed"><i class="fa-solid fa-circle-check"></i>CONFIRMED</span>
                                        <?php elseif ($st === 'Pending'): ?>
                                            <span class="badge badge-status-pending"><i class="fa-solid fa-clock"></i>PENDING</span>
                                        <?php elseif ($st === 'Checked-Out' || $st === 'Completed'): ?>
                                            <span class="badge badge-status-completed"><i class="fa-solid fa-flag-checkered"></i>COMPLETED</span>
                                        <?php elseif ($st === 'Cancelled'): ?>
                                            <span class="badge badge-status-cancelled"><i class="fa-solid fa-circle-xmark"></i>CANCELLED</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-2 py-0.5 font-bold extra-small"><?php echo strtoupper($st); ?></span>
                                        <?php endif; ?>

                                        <!-- Payment Status -->
                                        <?php if (strtolower($payStatus) === 'paid'): ?>
                                            <span class="badge badge-pay-paid">PAID</span>
                                        <?php elseif (strtolower($payStatus) === 'partial'): ?>
                                            <span class="badge badge-pay-partial">PARTIAL</span>
                                        <?php else: ?>
                                            <span class="badge badge-pay-pending">PAYMENT PENDING</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- ROOM & TYPE -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="room-thumb-box" onclick="event.stopPropagation(); openRoomPhotoModal('<?php echo htmlspecialchars($b['image']); ?>', 'Room <?php echo htmlspecialchars($b['room_no']); ?> — <?php echo htmlspecialchars($b['type_name']); ?>')" title="Click to view room photo">
                                            <img src="<?php echo htmlspecialchars($b['image']); ?>" alt="Room">
                                        </div>
                                        <div class="overflow-hidden">
                                            <strong class="text-navy font-bold extra-small d-block leading-snug text-truncate"><?php echo htmlspecialchars($b['type_name']); ?> &ndash; Room <?php echo htmlspecialchars($b['room_no']); ?></strong>
                                            <span class="text-muted extra-small font-medium d-block">Room #<?php echo htmlspecialchars($b['room_no']); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- DATES & GUESTS -->
                                <td class="text-nowrap">
                                    <div class="fw-bold text-navy extra-small mb-0.5">
                                        <?php echo date('d M Y', strtotime($b['check_in'])); ?> &rarr; <?php echo date('d M Y', strtotime($b['check_out'])); ?>
                                    </div>
                                    <div class="extra-small text-muted font-medium">
                                        <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?> &middot; <?php echo $b['adults']; ?> adult<?php echo $b['adults'] > 1 ? 's' : ''; ?><?php echo (!empty($b['children']) && $b['children'] > 0) ? ', ' . $b['children'] . ' child' : ''; ?>
                                    </div>
                                </td>

                                <!-- AMOUNT -->
                                <td class="text-nowrap">
                                    <div class="fw-bold text-navy small" style="letter-spacing: -0.3px;"><?php echo format_price($b['total_amount']); ?></div>
                                </td>

                                 <!-- ACTIONS -->
                                <td class="text-end text-nowrap" onclick="event.stopPropagation();">
                                    <div class="booking-actions" onclick="event.stopPropagation();">
                                        <!-- Invoice -->
                                        <button type="button" 
                                                class="btn btn-invoice" 
                                                data-booking="<?php echo $jsonBooking; ?>"
                                                onclick="handleInvoiceBtnClick(event, this)"
                                                title="View &amp; Print Invoice">
                                            <i class="fa-solid fa-file-invoice"></i> Invoice
                                        </button>

                                        <!-- Cancel -->
                                        <div class="cancel-action-wrap" id="cancel-action-wrap-<?php echo $b['booking_id']; ?>">
                                            <?php if ($st !== 'Cancelled' && $st !== 'Checked-Out'): ?>
                                                <button type="button" 
                                                        class="btn btn-cancel" 
                                                        data-id="<?php echo $b['booking_id']; ?>"
                                                        data-ref="<?php echo $refCodeFormatted; ?>"
                                                        data-booking="<?php echo $jsonBooking; ?>"
                                                        onclick="handleCancelBtnClick(event, this)"
                                                        title="Cancel Reservation">
                                                    <i class="fa-solid fa-xmark"></i> Cancel
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-cancelled" disabled title="Reservation Cancelled">
                                                    <i class="fa-solid fa-ban"></i> Cancelled
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MOBILE STACKED CARDS VIEW (< 768px) -->
    <div id="bookingsMobileCards" class="d-md-none space-y-3 mb-4">
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $b): 
                $st = $b['booking_status'];
                $rawPayStatus = $b['payment_status'] ?? '';
                if (!empty($rawPayStatus) && strtolower($rawPayStatus) === 'paid') {
                    $payStatus = 'Paid';
                } elseif (strtolower($rawPayStatus) === 'partial') {
                    $payStatus = 'Partial';
                } else {
                    $payStatus = ($st === 'Confirmed') ? 'Paid' : 'Payment Pending';
                }

                $dIn = new DateTime($b['check_in']);
                $dOut = new DateTime($b['check_out']);
                $nights = max(1, $dIn->diff($dOut)->days);
                $refCodeFormatted = 'HE-2026-' . str_pad($b['booking_id'], 4, '0', STR_PAD_LEFT);

                $bookingPayload = [
                    'ref' => $refCodeFormatted,
                    'booking_id' => $b['booking_id'],
                    'guest_name' => $b['customer_name'] ?? ($_SESSION['user_name'] ?? 'Valued Guest'),
                    'guest_email' => $b['customer_email'] ?? ($_SESSION['user_email'] ?? ''),
                    'guest_mobile' => $b['customer_mobile'] ?? '',
                    'room_no' => $b['room_no'],
                    'type_name' => $b['type_name'],
                    'image' => $b['image'],
                    'check_in' => date('d M Y', strtotime($b['check_in'])),
                    'check_out' => date('d M Y', strtotime($b['check_out'])),
                    'nights' => $nights,
                    'adults' => $b['adults'],
                    'children' => $b['children'] ?? 0,
                    'special_request' => $b['special_request'] ?? '',
                    'rate' => number_format($b['total_amount'] / $nights, 2),
                    'subtotal' => number_format($b['total_amount'], 2),
                    'tax' => number_format($b['total_amount'] * 0.10, 2),
                    'total' => number_format($b['total_amount'] * 1.10, 2),
                    'status' => $st,
                    'payment_status' => $payStatus,
                    'payment_mode' => $b['payment_mode'] ?? 'Card / Online',
                    'created_at' => date('M d, Y', strtotime($b['created_at']))
                ];
                $jsonBooking = htmlspecialchars(json_encode($bookingPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                $searchStr = htmlspecialchars(strtolower($refCodeFormatted . ' ' . $b['type_name'] . ' room ' . $b['room_no'] . ' ' . $b['booking_id'] . ' ' . $st . ' ' . $payStatus), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="mobile-booking-card booking-card-item-mobile" 
                     onclick="handleBookingRowClick(event, this)"
                     data-booking="<?php echo $jsonBooking; ?>"
                     data-booking-id="<?php echo $b['booking_id']; ?>"
                     data-search="<?php echo $searchStr; ?>"
                     data-status="<?php echo strtolower($st); ?>">
                    
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                        <span class="ref-code-text"><?php echo $refCodeFormatted; ?></span>
                        <div class="d-flex align-items-center gap-1" id="status-cell-mob-<?php echo $b['booking_id']; ?>">
                            <?php if ($st === 'Confirmed'): ?>
                                <span class="badge badge-status-confirmed">CONFIRMED</span>
                            <?php elseif ($st === 'Pending'): ?>
                                <span class="badge badge-status-pending">PENDING</span>
                            <?php elseif ($st === 'Checked-Out' || $st === 'Completed'): ?>
                                <span class="badge badge-status-completed">COMPLETED</span>
                            <?php elseif ($st === 'Cancelled'): ?>
                                <span class="badge badge-status-cancelled">CANCELLED</span>
                            <?php endif; ?>

                            <?php if (strtolower($payStatus) === 'paid'): ?>
                                <span class="badge badge-pay-paid">PAID</span>
                            <?php else: ?>
                                <span class="badge badge-pay-pending">PAYMENT PENDING</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2.5 mb-3">
                        <img src="<?php echo htmlspecialchars($b['image']); ?>" class="rounded-3 object-fit-cover shadow-2xs" style="width: 40px; height: 40px;" alt="Room">
                        <div class="overflow-hidden">
                            <h6 class="fw-bold text-navy mb-0.5 text-truncate extra-small"><?php echo htmlspecialchars($b['type_name']); ?> &ndash; Room <?php echo htmlspecialchars($b['room_no']); ?></h6>
                            <small class="text-muted d-block extra-small"><?php echo date('d M Y', strtotime($b['check_in'])); ?> &rarr; <?php echo date('d M Y', strtotime($b['check_out'])); ?></small>
                            <small class="text-muted d-block extra-small"><?php echo $nights; ?> nights &middot; <?php echo $b['adults']; ?> adults</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" onclick="event.stopPropagation();">
                        <div class="fw-bold text-navy small"><?php echo format_price($b['total_amount']); ?></div>
                        
                        <div class="booking-actions" onclick="event.stopPropagation();">
                            <button type="button" 
                                    class="btn btn-invoice" 
                                    data-booking="<?php echo $jsonBooking; ?>"
                                    onclick="handleInvoiceBtnClick(event, this)">
                                <i class="fa-solid fa-file-invoice"></i> Invoice
                            </button>

                            <div class="cancel-action-wrap" id="cancel-action-wrap-mob-<?php echo $b['booking_id']; ?>">
                                <?php if ($st !== 'Cancelled' && $st !== 'Checked-Out'): ?>
                                    <button type="button" 
                                            class="btn btn-cancel" 
                                            data-id="<?php echo $b['booking_id']; ?>"
                                            data-ref="<?php echo $refCodeFormatted; ?>"
                                            data-booking="<?php echo $jsonBooking; ?>"
                                            onclick="handleCancelBtnClick(event, this)">
                                        <i class="fa-solid fa-xmark"></i> Cancel
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-cancelled" disabled>
                                        <i class="fa-solid fa-ban"></i> Cancelled
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- EMPTY STATE -->
    <div id="bookingsEmptyState" class="text-center py-5 card border-0 shadow-sm rounded-4 bg-white" style="display: <?php echo empty($bookings) ? 'block' : 'none'; ?>;">
        <div class="py-4">
            <i class="fa-solid fa-calendar-xmark fs-1 text-secondary opacity-50 mb-3 d-block"></i>
            <h4 class="fw-bold text-navy mb-1">No Reservations Found</h4>
            <p class="text-muted small mb-4">You currently have no luxury room bookings matching your search or applied filter criteria.</p>
            <a href="/hotel_website/user/rooms.php" class="btn btn-primary rounded-pill px-4 font-bold shadow-sm">
                <i class="fa-solid fa-bed me-1.5"></i> Search &amp; Book Rooms
            </a>
        </div>
    </div>

</div>
</main>

<!-- =========================================================
     MODAL 1: VIEW BOOKING DETAILS MODAL
   ========================================================= -->
<div class="modal fade" id="viewBookingDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 p-4 bg-navy text-white" style="background: #0B1F3A !important;">
                <h5 class="modal-title font-serif-title fw-bold text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-bookmark text-primary"></i> Reservation Details
                    <span id="vdRefNo" class="ref-code-text" style="color: #60A5FA !important;">#HE-000</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Room Photo -->
                    <div class="col-md-5 col-12">
                        <div class="rounded-4 overflow-hidden border shadow-sm mb-3">
                            <img id="vdRoomImg" src="" class="img-fluid w-100 object-fit-cover" style="height: 220px;" alt="Room Image">
                        </div>
                        <h5 id="vdRoomTitle" class="fw-bold text-navy mb-2 font-serif-title">Room Specification</h5>
                        <div id="vdStatusBadge" class="mb-2"></div>
                    </div>
                    <!-- Stay Details -->
                    <div class="col-md-7 col-12">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <h6 class="fw-bold text-navy extra-small text-uppercase tracking-wider mb-2"><i class="fa-solid fa-calendar-days text-primary me-1.5"></i> Stay Dates &amp; Duration</h6>
                            <div class="row g-2 text-dark small">
                                <div class="col-6">Check-In: <strong id="vdCheckIn" class="d-block text-navy fw-bold">N/A</strong></div>
                                <div class="col-6">Check-Out: <strong id="vdCheckOut" class="d-block text-navy fw-bold">N/A</strong></div>
                                <div class="col-6 mt-2">Duration: <strong id="vdNights" class="d-block text-navy fw-bold">1 Night</strong></div>
                                <div class="col-6 mt-2">Guests: <strong id="vdGuests" class="d-block text-navy fw-bold">2 Adults</strong></div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-navy extra-small text-uppercase tracking-wider mb-2"><i class="fa-solid fa-credit-card text-success me-1.5"></i> Financial Breakdown</h6>
                            <div class="row g-2 text-dark small">
                                <div class="col-6">Total Amount: <strong id="vdAmount" class="d-block text-primary fw-bold fs-5 font-serif-title">₹0</strong></div>
                                <div class="col-6">Payment Status: <strong id="vdPayStatus" class="d-block text-success fw-bold">Paid</strong></div>
                                <div class="col-12 mt-2">Payment Method: <span id="vdPayMode" class="text-muted fw-semibold">Card / Online Payment</span></div>
                                <div class="col-12 mt-2">Special Requests: <span id="vdSpecialRequest" class="text-muted italic">None</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="vdInvoiceBtn" class="btn btn-invoice font-semibold" onclick="triggerInvoiceFromDetails()">
                        <i class="fa-solid fa-file-invoice me-1.5"></i> Invoice
                    </button>
                    <div id="vdCancelWrap"></div>
                </div>
                <button type="button" class="btn btn-secondary rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL 2: CONFIRM CANCEL MODAL
   ========================================================= -->
<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-4 border-0 shadow-lg" style="border-top: 5px solid #DC2626 !important;">
            <div class="modal-body text-center pt-2 pb-2">
                <div class="mb-3 text-danger" style="font-size: 3rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h4 class="font-serif-title fw-bold text-navy mb-2">Cancel Booking?</h4>
                <p class="small text-muted mb-3">Are you sure you want to cancel this reservation?</p>
                
                <div class="p-3 bg-light rounded-3 border text-start mb-3">
                    <div class="d-flex justify-content-between mb-1.5 extra-small">
                        <span class="text-muted">Booking Reference:</span>
                        <strong id="cancelRefDisplay" class="text-navy font-monospace">#HE-2026-0000</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1.5 extra-small">
                        <span class="text-muted">Room Specification:</span>
                        <strong id="cancelRoomDisplay" class="text-navy">Room 101</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1.5 extra-small">
                        <span class="text-muted">Stay Dates:</span>
                        <strong id="cancelDatesDisplay" class="text-navy">Dates</strong>
                    </div>
                    <div class="d-flex justify-content-between extra-small">
                        <span class="text-muted">Total Amount:</span>
                        <strong id="cancelAmountDisplay" class="text-primary font-bold">₹0.00</strong>
                    </div>
                </div>

                <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning-emphasis extra-small rounded-3 text-start mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> Refund handling will process as per HotelEase cancellation terms and conditions.
                </div>

                <div class="d-flex gap-2 justify-content-center mt-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="button" id="confirmCancelSubmitBtn" class="btn btn-danger rounded-pill px-4 font-bold shadow-sm" onclick="submitCancelReservation()">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL 3: INVOICE PREVIEW MODAL
   ========================================================= -->
<div class="modal fade" id="bookingInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background: #FFFFFF; color: #0F172A;">
            <div class="modal-header border-0 p-4 bg-navy text-white" style="background: #0B1F3A !important;">
                <h5 class="modal-title font-serif-title fw-bold text-white mb-0">
                    <i class="fa-solid fa-file-invoice text-primary me-2"></i>Official Reservation Invoice
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                
                <div id="printableUserInvoiceContent" class="invoice-container p-3 p-md-4 bg-white text-navy rounded-4">
                    <!-- 1. Header Banner -->
                    <div class="invoice-header-banner p-4 rounded-4 text-white position-relative overflow-hidden mb-4" style="background: linear-gradient(135deg, #071A2F 0%, #0B1F3A 100%); border-bottom: 4px solid #D4AF5A;">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 position-relative z-2">
                            <div>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease" style="max-height: 48px; object-fit: contain;" onerror="this.style.display='none';">
                                    <div>
                                        <h2 class="font-serif-title fw-bold text-white mb-0" style="letter-spacing: 1px; font-size: 24px;">HOTEL EASE</h2>
                                        <div class="text-gold extra-small fw-bold uppercase tracking-widest">Luxury Hospitality &amp; Suites</div>
                                    </div>
                                </div>
                                <div class="extra-small text-white-50 leading-relaxed">
                                    <div><i class="fa-solid fa-location-dot text-gold me-1.5"></i> Patel Colony, Road No :- 3 / Street No :- 5, Jamnagar</div>
                                    <div><i class="fa-solid fa-phone text-gold me-1.5"></i> +918160253134 &bull; GSTIN: <strong>24AAACH7777E1Z5</strong></div>
                                </div>
                            </div>

                            <div class="p-3 rounded-3 text-md-end" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); min-width: 240px;">
                                <div id="invStatusStamp" class="mb-2">
                                    <span class="badge bg-success bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-circle-check me-1"></i> PAID IN FULL (Card)</span>
                                </div>
                                <h5 class="fw-bold text-white mb-1 font-serif-title">Invoice <span id="invRefNo">#INV-2026-00101</span></h5>
                                <div class="extra-small text-white-50">Issued Date: <strong id="invDate" class="text-white"><?php echo date('d M Y'); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Customer & Reservation Grid -->
                    <div class="row g-3 mb-4">
                        <!-- Billed Customer Card -->
                        <div class="col-md-6">
                            <div class="p-3.5 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 uppercase tracking-wider extra-small font-bold px-3 py-1.5 rounded-pill">
                                            <i class="fa-solid fa-user me-1.5"></i> Billed Customer
                                        </span>
                                        <span class="badge bg-white text-secondary border extra-small font-semibold px-2.5 py-1 rounded-pill">Registered Guest</span>
                                    </div>
                                    <h5 id="invGuestName" class="fw-bold text-navy mb-3 font-serif-title" style="font-size: 18px;">Guest Name</h5>
                                </div>
                                <div class="pt-3 border-top space-y-2 extra-small">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-envelope text-primary me-2 opacity-75"></i> Email Address:</span>
                                        <strong id="invGuestEmail" class="text-navy">email@example.com</strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-mobile-screen text-primary me-2 opacity-75"></i> Phone Number:</span>
                                        <strong id="invGuestMobile" class="text-navy">+91 9876543210</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reservation Details Card -->
                        <div class="col-md-6">
                            <div class="p-3.5 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="badge uppercase tracking-wider extra-small font-bold px-3 py-1.5 rounded-pill" style="background-color: #FEF08A; color: #854D0E; border: 1px solid #FDE047;">
                                            <i class="fa-solid fa-hotel me-1.5 text-warning"></i> Reservation Details
                                        </span>
                                        <span id="invNightsBadge" class="badge bg-white text-primary border border-primary border-opacity-25 extra-small font-bold px-3 py-1.5 rounded-pill">
                                            <i class="fa-solid fa-moon text-warning me-1"></i> 1 Night Stay
                                        </span>
                                    </div>
                                    <h5 id="invRoomNo" class="fw-bold text-navy mb-3 font-serif-title" style="font-size: 17px;">Room 102 (Deluxe Room)</h5>
                                </div>
                                <div class="pt-3 border-top space-y-2 extra-small">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-calendar-check text-success me-2"></i> Check-In Date:</span>
                                        <strong id="invCheckIn" class="text-navy">07 Oct 2026</strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-calendar-xmark text-danger me-2"></i> Check-Out Date:</span>
                                        <strong id="invCheckOut" class="text-navy">08 Oct 2026</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Line Items Table -->
                    <div class="table-responsive rounded-4 border overflow-hidden mb-4">
                        <table class="table align-middle mb-0">
                            <thead style="background-color: #0B1F3A; color: #FFFFFF;">
                                <tr>
                                    <th class="py-3 px-4 text-uppercase extra-small font-bold tracking-wider" style="color: #FFFFFF !important; background-color: #0B1F3A !important;">ITEM DESCRIPTION</th>
                                    <th class="py-3 px-3 text-center text-uppercase extra-small font-bold tracking-wider" style="color: #FFFFFF !important; background-color: #0B1F3A !important;">NIGHTS</th>
                                    <th class="py-3 px-3 text-end text-uppercase extra-small font-bold tracking-wider" style="color: #FFFFFF !important; background-color: #0B1F3A !important;">PRICE / NIGHT</th>
                                    <th class="py-3 px-4 text-end text-uppercase extra-small font-bold tracking-wider" style="color: #FFFFFF !important; background-color: #0B1F3A !important;">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="py-3 px-4">
                                        <div id="invItemDesc" class="fw-bold text-navy">Room 102 (Deluxe Room) Accommodations</div>
                                        <div class="extra-small text-muted">Luxury Guest Room Stay Package</div>
                                    </td>
                                    <td id="invItemNights" class="text-center fw-bold py-3 px-3">1</td>
                                    <td id="invItemRate" class="text-end fw-semibold py-3 px-3">₹4,500.00</td>
                                    <td id="invItemTotal" class="text-end fw-bold text-navy py-3 px-4">₹4,500.00</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="fw-bold text-navy">GST Tax (18%)</div>
                                        <div class="extra-small text-muted">Government Goods and Services Tax</div>
                                    </td>
                                    <td class="text-center text-muted py-3 px-3">-</td>
                                    <td class="text-end text-muted py-3 px-3">18%</td>
                                    <td id="invTaxRow" class="text-end fw-bold text-navy py-3 px-4">₹810.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. Bottom Summary Grid -->
                    <div class="row g-3 align-items-end mb-4">
                        <!-- Left: Verified Digital Receipt Box -->
                        <div class="col-md-6">
                            <div class="p-3.5 bg-light rounded-4 border d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="fa-solid fa-shield-halved fs-3 text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-navy extra-small d-flex align-items-center gap-1.5 mb-1">
                                        <i class="fa-solid fa-circle-check text-success"></i> Verified Digital Receipt
                                    </div>
                                    <div id="invTxnDetails" class="extra-small text-muted">
                                        Transaction ID: <strong id="invTxnId">#CARD-370AE3050F</strong> &bull; Card payment
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Financial Breakdown Card -->
                        <div class="col-md-6">
                            <div class="p-3.5 rounded-4 border" style="background-color: #F8FAFC;">
                                <div class="d-flex justify-content-between extra-small text-secondary mb-2">
                                    <span>Subtotal:</span>
                                    <strong id="invSubtotal" class="text-navy">₹4,500.00</strong>
                                </div>
                                <div class="d-flex justify-content-between extra-small text-secondary mb-2">
                                    <span>GST Tax (18%):</span>
                                    <strong id="invTax" class="text-navy">₹810.00</strong>
                                </div>
                                <div class="d-flex justify-content-between extra-small text-success mb-3">
                                    <span>Special Promo Discount:</span>
                                    <strong id="invDiscount">-₹0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <span class="fw-bold text-navy font-serif-title fs-5">Grand Total:</span>
                                    <span id="invGrandTotal" class="fw-bold text-primary" style="font-size: 24px; color: #2563EB !important;">₹5,310.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Footer -->
                    <div class="border-top pt-3 text-center text-muted extra-small">
                        <p class="mb-1">Thank you for choosing <strong>Hotel Ease</strong>. We hope you enjoyed your stay!</p>
                        <p class="mb-0">This is a computer-generated official tax invoice and requires no physical signature.</p>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4 gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 font-bold shadow-sm d-inline-flex align-items-center gap-1.5" onclick="downloadInvoicePDF('printableUserInvoiceContent', 'Invoice')">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 font-bold shadow-sm d-inline-flex align-items-center gap-1.5" onclick="triggerInvoicePrint()">
                    <i class="fa-solid fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL 4: PHOTO LIGHTBOX PREVIEW MODAL
   ========================================================= -->
<div class="modal fade" id="roomPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden" style="background: #0B1F3A;">
            <div class="modal-header border-0 pb-0 text-white p-3 p-md-4">
                <h5 class="modal-title font-serif-title fw-bold text-white d-flex align-items-center gap-2" id="roomPhotoModalLabel">
                    <i class="fa-solid fa-image text-warning"></i> Room Photo Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4 text-center">
                <div class="rounded-3 overflow-hidden border border-white border-opacity-10 bg-black bg-opacity-25 shadow">
                    <img id="modalRoomPhoto" src="" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;" alt="Room Photo High-Res">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4 justify-content-between">
                <span id="modalRoomTitle" class="text-gold small font-semibold"></span>
                <button type="button" class="btn btn-outline-light rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentActivePayload = null;
let pendingCancelBookingId = null;

function showBookingModal(modalElement) {
    if (!modalElement) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
        return;
    }

    modalElement.classList.add('show');
    modalElement.style.display = 'block';
    modalElement.removeAttribute('aria-hidden');
    modalElement.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');

    if (!document.querySelector('.booking-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show booking-modal-backdrop';
        document.body.appendChild(backdrop);
    }
}

function hideBookingModal(modalElement) {
    if (!modalElement) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();
        return;
    }

    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    const backdrop = document.querySelector('.booking-modal-backdrop');
    if (backdrop) backdrop.remove();
}

document.addEventListener('click', function (event) {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) return;

    const dismissButton = event.target.closest('[data-bs-dismiss="modal"]');
    if (dismissButton) {
        const modalElement = dismissButton.closest('.modal');
        hideBookingModal(modalElement);
    }
});

// Safe Click Action Handlers
function handleBookingRowClick(e, element) {
    if (e && e.target && (e.target.closest('button') || e.target.closest('.room-thumb-box') || e.target.closest('a') || e.target.closest('.booking-actions') || e.target.closest('.cancel-action-wrap'))) {
        return;
    }
    const dataStr = element ? element.getAttribute('data-booking') : null;
    if (dataStr) {
        try {
            const payload = JSON.parse(dataStr);
            openViewDetailsModal(payload);
        } catch(err) {
            console.error('Error parsing booking data:', err);
        }
    }
}

function handleInvoiceBtnClick(e, btn) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const targetBtn = btn || (e ? e.currentTarget : null) || (e && e.target ? e.target.closest('button') : null);
    let dataStr = targetBtn ? targetBtn.getAttribute('data-booking') : null;
    if (!dataStr && targetBtn) {
        const parent = targetBtn.closest('[data-booking]');
        if (parent) dataStr = parent.getAttribute('data-booking');
    }
    if (dataStr) {
        try {
            const payload = JSON.parse(dataStr);
            openInvoiceModal(payload);
        } catch(err) {
            console.error('Error parsing invoice payload:', err);
        }
    }
}

function handleCancelBtnClick(e, btn) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const targetBtn = btn || (e ? e.currentTarget : null) || (e && e.target ? e.target.closest('button') : null);
    if (!targetBtn) return;
    const bookingId = targetBtn.getAttribute('data-id');
    const refCode = targetBtn.getAttribute('data-ref');
    
    let payload = null;
    let dataStr = targetBtn.getAttribute('data-booking');
    if (!dataStr) {
        const parent = targetBtn.closest('[data-booking]');
        if (parent) dataStr = parent.getAttribute('data-booking');
    }
    if (dataStr) {
        try { payload = JSON.parse(dataStr); } catch(err) {}
    }

    if (bookingId && refCode) {
        openCancelModal(bookingId, refCode, payload);
    }
}

// Filter desktop table rows and mobile cards dynamically
function filterBookingsList() {
    const query = (document.getElementById('bookingSearchInput')?.value || '').toLowerCase().trim();
    const status = (document.getElementById('bookingStatusFilter')?.value || 'all').toLowerCase();
    
    // Filter Desktop Table Rows
    const rows = document.querySelectorAll('.booking-row-item');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        
        const matchesQuery = !query || searchText.includes(query);
        let matchesStatus = true;
        
        if (status !== 'all') {
            if (status === 'completed') {
                matchesStatus = (rowStatus === 'completed' || rowStatus === 'checked-out');
            } else {
                matchesStatus = (rowStatus === status);
            }
        }
        
        if (matchesQuery && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Filter Mobile Cards
    const cards = document.querySelectorAll('.booking-card-item-mobile');
    cards.forEach(card => {
        const searchText = card.getAttribute('data-search') || '';
        const cardStatus = card.getAttribute('data-status') || '';
        
        const matchesQuery = !query || searchText.includes(query);
        let matchesStatus = true;
        
        if (status !== 'all') {
            if (status === 'completed') {
                matchesStatus = (cardStatus === 'completed' || cardStatus === 'checked-out');
            } else {
                matchesStatus = (cardStatus === status);
            }
        }
        
        if (matchesQuery && matchesStatus) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    // Toggle Empty State
    const emptyState = document.getElementById('bookingsEmptyState');
    const tableCard = document.getElementById('bookingsTableCard');
    const mobileContainer = document.getElementById('bookingsMobileCards');

    if (visibleCount === 0) {
        if (tableCard) tableCard.style.display = 'none';
        if (mobileContainer) mobileContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
    } else {
        if (tableCard) tableCard.style.display = '';
        if (mobileContainer) mobileContainer.style.display = '';
        if (emptyState) emptyState.style.display = 'none';
    }

    // Update Counter
    const countNum = document.getElementById('totalCountNumber');
    if (countNum) countNum.textContent = visibleCount;
}

// Open View Details Modal
function openViewDetailsModal(payload) {
    if (!payload) return;
    currentActivePayload = payload;
    
    document.getElementById('vdRefNo').textContent = payload.ref || 'HE-2026-0000';
    document.getElementById('vdRoomTitle').textContent = payload.type_name + ' – Room ' + payload.room_no;
    document.getElementById('vdCheckIn').textContent = payload.check_in;
    document.getElementById('vdCheckOut').textContent = payload.check_out;
    document.getElementById('vdNights').textContent = payload.nights + ' Night' + (payload.nights > 1 ? 's' : '');
    document.getElementById('vdGuests').textContent = payload.adults + ' Adult' + (payload.adults > 1 ? 's' : '') + (payload.children > 0 ? ', ' + payload.children + ' Child' : '');
    document.getElementById('vdAmount').textContent = '₹' + payload.subtotal;
    document.getElementById('vdPayMode').textContent = payload.payment_mode || 'Card / Online';
    document.getElementById('vdPayStatus').textContent = payload.payment_status || 'Paid';
    document.getElementById('vdSpecialRequest').textContent = payload.special_request || 'None';
    
    // Status Badge HTML
    const stBadge = document.getElementById('vdStatusBadge');
    if (stBadge) {
        const st = (payload.status || '').toLowerCase();
        if (st === 'confirmed') {
            stBadge.innerHTML = '<span class="badge badge-status-confirmed"><i class="fa-solid fa-circle-check me-1"></i>CONFIRMED</span>';
        } else if (st === 'pending') {
            stBadge.innerHTML = '<span class="badge badge-status-pending"><i class="fa-solid fa-clock me-1"></i>PENDING</span>';
        } else if (st === 'completed' || st === 'checked-out') {
            stBadge.innerHTML = '<span class="badge badge-status-completed"><i class="fa-solid fa-flag-checkered me-1"></i>COMPLETED</span>';
        } else if (st === 'cancelled') {
            stBadge.innerHTML = '<span class="badge badge-status-cancelled"><i class="fa-solid fa-circle-xmark me-1"></i>CANCELLED</span>';
        } else {
            stBadge.innerHTML = '<span class="badge bg-secondary rounded-pill px-3 py-1 font-bold extra-small">' + (payload.status || '').toUpperCase() + '</span>';
        }
    }

    if (payload.image) {
        document.getElementById('vdRoomImg').src = payload.image;
    }

    // Modal footer cancel button
    const vdCancelWrap = document.getElementById('vdCancelWrap');
    if (vdCancelWrap) {
        const st = (payload.status || '').toLowerCase();
        if (st !== 'cancelled' && st !== 'checked-out') {
            vdCancelWrap.innerHTML = `<button type="button" class="btn btn-cancel font-semibold" onclick="triggerCancelFromDetails()"><i class="fa-solid fa-xmark me-1.5"></i> Cancel Booking</button>`;
        } else {
            vdCancelWrap.innerHTML = `<button class="btn btn-cancelled" disabled><i class="fa-solid fa-ban me-1.5"></i> Cancelled</button>`;
        }
    }

    showBookingModal(document.getElementById('viewBookingDetailsModal'));
}

function triggerInvoiceFromDetails() {
    if (currentActivePayload) {
        const viewEl = document.getElementById('viewBookingDetailsModal');
        hideBookingModal(viewEl);
        setTimeout(() => {
            openInvoiceModal(currentActivePayload);
        }, 200);
    }
}

function triggerCancelFromDetails() {
    if (currentActivePayload) {
        const viewEl = document.getElementById('viewBookingDetailsModal');
        hideBookingModal(viewEl);
        setTimeout(() => {
            openCancelModal(currentActivePayload.booking_id, currentActivePayload.ref, currentActivePayload);
        }, 200);
    }
}

// Open Cancel Confirmation Modal
function openCancelModal(bookingId, refCode, payload = null) {
    pendingCancelBookingId = bookingId;
    
    document.getElementById('cancelRefDisplay').textContent = refCode || '#HE-2026-0000';
    
    if (payload) {
        document.getElementById('cancelRoomDisplay').textContent = 'Room ' + payload.room_no + ' (' + payload.type_name + ')';
        document.getElementById('cancelDatesDisplay').textContent = payload.check_in + ' → ' + payload.check_out;
        document.getElementById('cancelAmountDisplay').textContent = '₹' + payload.subtotal;
    } else {
        document.getElementById('cancelRoomDisplay').textContent = 'Luxury Room';
        document.getElementById('cancelDatesDisplay').textContent = 'Stay Schedule';
        document.getElementById('cancelAmountDisplay').textContent = 'Standard Amount';
    }

    showBookingModal(document.getElementById('confirmCancelModal'));
}

// Submit Cancellation Action
function submitCancelReservation() {
    if (!pendingCancelBookingId) return;
    
    const submitBtn = document.getElementById('confirmCancelSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1.5"></i> Cancelling...';
    }
    
    fetch('/hotel_website/user/cancel-booking.php?ajax=1&id=' + encodeURIComponent(pendingCancelBookingId), {
        method: 'GET'
    })
    .then(res => res.json())
    .then(data => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Yes, Cancel Booking';
        }
        
        const modalEl = document.getElementById('confirmCancelModal');
        hideBookingModal(modalEl);
        
        if (data.success) {
            // Update in-place DOM for matching booking ID elements
            const elements = document.querySelectorAll('[data-booking]');
            elements.forEach(el => {
                const dataStr = el.getAttribute('data-booking');
                if (dataStr) {
                    try {
                        const p = JSON.parse(dataStr);
                        if (p.booking_id == pendingCancelBookingId) {
                            p.status = 'Cancelled';
                            el.setAttribute('data-booking', JSON.stringify(p));
                            el.setAttribute('data-status', 'cancelled');

                            // Desktop status badge update
                            const statusCell = document.getElementById('status-cell-' + pendingCancelBookingId);
                            if (statusCell) {
                                const stBadge = statusCell.querySelector('.badge-status-confirmed, .badge-status-pending, .badge-status-completed');
                                if (stBadge) {
                                    stBadge.className = 'badge badge-status-cancelled';
                                    stBadge.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i>CANCELLED';
                                }
                            }

                            // Mobile status badge update
                            const statusCellMob = document.getElementById('status-cell-mob-' + pendingCancelBookingId);
                            if (statusCellMob) {
                                const stBadge = statusCellMob.querySelector('.badge-status-confirmed, .badge-status-pending, .badge-status-completed');
                                if (stBadge) {
                                    stBadge.className = 'badge badge-status-cancelled';
                                    stBadge.innerHTML = 'CANCELLED';
                                }
                            }

                            // Desktop cancel action wrap update
                            const cancelWrap = document.getElementById('cancel-action-wrap-' + pendingCancelBookingId);
                            if (cancelWrap) {
                                cancelWrap.innerHTML = '<button class="btn btn-cancelled" disabled title="Reservation Cancelled"><i class="fa-solid fa-ban me-1"></i>Cancelled</button>';
                            }

                            // Mobile cancel action wrap update
                            const cancelWrapMob = document.getElementById('cancel-action-wrap-mob-' + pendingCancelBookingId);
                            if (cancelWrapMob) {
                                cancelWrapMob.innerHTML = '<button class="btn btn-cancelled" disabled><i class="fa-solid fa-ban me-1"></i>Cancelled</button>';
                            }
                        }
                    } catch(e) {}
                }
            });

            if (typeof showDynamicToast === 'function') {
                showDynamicToast('Booking cancelled successfully.', 'success');
            }
            
            // Re-apply filter to update counters
            filterBookingsList();
        } else {
            if (typeof showDynamicToast === 'function') {
                showDynamicToast(data.message || 'Failed to cancel reservation.', 'danger');
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Yes, Cancel Booking';
        }
        window.location.href = '/hotel_website/user/cancel-booking.php?id=' + encodeURIComponent(pendingCancelBookingId);
    });
}

// Open Invoice Preview Modal
function openInvoiceModal(payload) {
    if (!payload) return;

    const setTxt = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.textContent = txt || '';
    };

    setTxt('invRefNo', '#' + (payload.ref || 'INV-2026-00101'));
    setTxt('invGuestName', payload.guest_name || 'Guest');
    setTxt('invGuestEmail', payload.guest_email || 'N/A');
    setTxt('invGuestMobile', payload.guest_mobile || 'N/A');
    setTxt('invRoomNo', 'Room ' + (payload.room_no || '') + ' (' + (payload.type_name || '') + ')');
    setTxt('invCheckIn', payload.check_in || '');
    setTxt('invCheckOut', payload.check_out || '');
    
    const n = payload.nights || '1';
    const nightsBadge = document.getElementById('invNightsBadge');
    if (nightsBadge) {
        nightsBadge.innerHTML = '<i class="fa-solid fa-moon text-warning me-1"></i> ' + n + ' ' + (n == 1 ? 'Night' : 'Nights') + ' Stay';
    }

    setTxt('invItemDesc', 'Room ' + (payload.room_no || '') + ' (' + (payload.type_name || '') + ') Accommodations');
    setTxt('invItemNights', n);
    setTxt('invItemRate', '₹' + (payload.rate || '0.00'));
    setTxt('invItemTotal', '₹' + (payload.subtotal || '0.00'));
    setTxt('invTaxRow', '₹' + (payload.tax || '0.00'));

    setTxt('invSubtotal', '₹' + (payload.subtotal || '0.00'));
    setTxt('invTax', '₹' + (payload.tax || '0.00'));
    setTxt('invDiscount', '-₹' + (payload.discount || '0.00'));
    setTxt('invGrandTotal', '₹' + (payload.total || '0.00'));

    setTxt('invTxnId', '#' + (payload.txn_id || 'CARD-370AE3050F'));

    const stampElem = document.getElementById('invStatusStamp');
    if (stampElem) {
        const pStatus = (payload.payment_status || 'Paid').toLowerCase();
        const pMode = payload.payment_mode || 'Card';
        if (pStatus === 'paid') {
            stampElem.innerHTML = '<span class="badge bg-success bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-circle-check me-1"></i> PAID IN FULL (' + pMode + ')</span>';
        } else {
            stampElem.innerHTML = '<span class="badge bg-warning text-dark bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-clock me-1"></i> PAYMENT PENDING</span>';
        }
    }

    const modalElem = document.getElementById('bookingInvoiceModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

// Print Invoice Handler
function triggerInvoicePrint() {
    window.print();
}

// Room Photo Modal Handler
function openRoomPhotoModal(imageSrc, titleText) {
    const photoImg = document.getElementById('modalRoomPhoto');
    const titleSpan = document.getElementById('modalRoomTitle');
    const modalTitleHeader = document.getElementById('roomPhotoModalLabel');
    
    if (photoImg) photoImg.src = imageSrc;
    if (titleSpan) titleSpan.textContent = titleText;
    if (modalTitleHeader) modalTitleHeader.innerHTML = '<i class="fa-solid fa-camera text-warning me-2"></i> ' + titleText;
    
    const photoModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('roomPhotoModal'));
    photoModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
