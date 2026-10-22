<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // 1. ADD NEW BOOKING
    if ($action === 'add_booking') {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        // Handle Quick New Customer Creation if selected
        if ($customer_id === 0 && !empty($_POST['new_customer_name'])) {
            $c_name = sanitize($_POST['new_customer_name']);
            $c_email = sanitize($_POST['new_customer_email'] ?? '');
            $c_mobile = sanitize($_POST['new_customer_mobile'] ?? '');
            $c_idproof = sanitize($_POST['new_customer_id_proof'] ?? '');

            // Fallback email if empty
            if (empty($c_email)) {
                $c_email = 'guest_' . time() . '@hotelease.com';
            }

            $stmtC = $pdo->prepare("INSERT INTO `customers` (`name`, `email`, `mobile`, `id_proof`) VALUES (:name, :email, :mobile, :idproof)");
            $stmtC->execute([
                'name' => $c_name,
                'email' => $c_email,
                'mobile' => $c_mobile,
                'idproof' => $c_idproof
            ]);
            $customer_id = $pdo->lastInsertId();
        }

        $room_id = intval($_POST['room_id'] ?? 0);
        $check_in = sanitize($_POST['check_in'] ?? '');
        $check_out = sanitize($_POST['check_out'] ?? '');
        $adults = intval($_POST['adults'] ?? 1);
        $children = intval($_POST['children'] ?? 0);
        $booking_status = sanitize($_POST['booking_status'] ?? 'Confirmed');
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $special_request = sanitize($_POST['special_request'] ?? '');

        // Payment info
        $payment_status = sanitize($_POST['payment_status'] ?? 'Paid');
        $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');
        $transaction_id = sanitize($_POST['transaction_id'] ?? ('TXN' . time()));

        if ($customer_id > 0 && $room_id > 0 && !empty($check_in) && !empty($check_out)) {
            // Insert into bookings table
            $stmtB = $pdo->prepare("INSERT INTO `bookings` (`customer_id`, `room_id`, `check_in`, `check_out`, `adults`, `children`, `special_request`, `booking_status`, `total_amount`) VALUES (:cid, :rid, :cin, :cout, :ad, :ch, :sr, :st, :amt)");
            $stmtB->execute([
                'cid' => $customer_id,
                'rid' => $room_id,
                'cin' => $check_in,
                'cout' => $check_out,
                'ad' => $adults,
                'ch' => $children,
                'sr' => $special_request,
                'st' => $booking_status,
                'amt' => $total_amount
            ]);
            $new_booking_id = $pdo->lastInsertId();

            // Insert into payments table
            $stmtP = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `amount`, `payment_mode`, `transaction_id`, `payment_status`) VALUES (:bid, :amt, :mode, :tx, :pst)");
            $stmtP->execute([
                'bid' => $new_booking_id,
                'amt' => $total_amount,
                'mode' => $payment_mode,
                'tx' => $transaction_id,
                'pst' => $payment_status
            ]);

            // Sync Room Status
            if ($booking_status === 'Checked-In') {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `room_id` = :rid")->execute(['rid' => $room_id]);
            } elseif ($booking_status === 'Confirmed') {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Reserved' WHERE `room_id` = :rid")->execute(['rid' => $room_id]);
            }

            set_toast('New reservation #HE-' . $new_booking_id . ' created successfully!', 'success');
        } else {
            set_toast('Failed to create booking. Please select valid guest, room, and stay dates.', 'danger');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 2. EDIT BOOKING
    elseif ($action === 'edit_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $room_id = intval($_POST['room_id'] ?? 0);
        $check_in = sanitize($_POST['check_in'] ?? '');
        $check_out = sanitize($_POST['check_out'] ?? '');
        $adults = intval($_POST['adults'] ?? 1);
        $children = intval($_POST['children'] ?? 0);
        $booking_status = sanitize($_POST['booking_status'] ?? 'Pending');
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $special_request = sanitize($_POST['special_request'] ?? '');

        if ($booking_id > 0 && $room_id > 0 && !empty($check_in) && !empty($check_out)) {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `room_id` = :rid, `check_in` = :cin, `check_out` = :cout, `adults` = :ad, `children` = :ch, `booking_status` = :st, `total_amount` = :amt, `special_request` = :sr WHERE `booking_id` = :bid");
            $stmt->execute([
                'rid' => $room_id,
                'cin' => $check_in,
                'cout' => $check_out,
                'ad' => $adults,
                'ch' => $children,
                'st' => $booking_status,
                'amt' => $total_amount,
                'sr' => $special_request,
                'bid' => $booking_id
            ]);

            // Sync Room Status
            if ($booking_status === 'Checked-In') {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `room_id` = :rid")->execute(['rid' => $room_id]);
            } elseif ($booking_status === 'Confirmed') {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Reserved' WHERE `room_id` = :rid")->execute(['rid' => $room_id]);
            } elseif ($booking_status === 'Checked-Out' || $booking_status === 'Cancelled') {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `room_id` = :rid")->execute(['rid' => $room_id]);
            }

            set_toast('Reservation #HE-' . $booking_id . ' updated successfully!', 'success');
        } else {
            set_toast('Please fill in required fields.', 'danger');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 3. CONFIRM BOOKING
    elseif ($action === 'confirm_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Confirmed' WHERE `booking_id` = :bid");
            $stmt->execute(['bid' => $booking_id]);

            // Sync Room Status
            $stmtB = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `booking_id` = :bid");
            $stmtB->execute(['bid' => $booking_id]);
            $bRow = $stmtB->fetch();
            if ($bRow) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Reserved' WHERE `room_id` = :rid")->execute(['rid' => $bRow['room_id']]);
            }
            set_toast('Reservation #HE-' . $booking_id . ' confirmed!', 'success');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 4. CHECK-IN GUEST
    elseif ($action === 'checkin_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Checked-In' WHERE `booking_id` = :bid");
            $stmt->execute(['bid' => $booking_id]);

            // Sync Room Status
            $stmtB = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `booking_id` = :bid");
            $stmtB->execute(['bid' => $booking_id]);
            $bRow = $stmtB->fetch();
            if ($bRow) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `room_id` = :rid")->execute(['rid' => $bRow['room_id']]);
            }
            set_toast('Guest checked-in! Room status updated to OCCUPIED 🔴', 'success');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 5. CHECK-OUT GUEST
    elseif ($action === 'checkout_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $payment_status = sanitize($_POST['payment_status'] ?? 'Paid');

        if ($booking_id > 0) {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Checked-Out' WHERE `booking_id` = :bid");
            $stmt->execute(['bid' => $booking_id]);

            // Sync Room Status
            $stmtB = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `booking_id` = :bid");
            $stmtB->execute(['bid' => $booking_id]);
            $bRow = $stmtB->fetch();
            if ($bRow) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `room_id` = :rid")->execute(['rid' => $bRow['room_id']]);
            }

            // Update Payment Status if set
            $checkP = $pdo->prepare("SELECT `payment_id` FROM `payments` WHERE `booking_id` = :bid");
            $checkP->execute(['bid' => $booking_id]);
            if ($checkP->fetch()) {
                $pdo->prepare("UPDATE `payments` SET `payment_status` = :pst WHERE `booking_id` = :bid")->execute(['pst' => $payment_status, 'bid' => $booking_id]);
            }

            set_toast('Guest checked-out! Room status updated to AVAILABLE 🟢', 'success');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 6. CANCEL BOOKING
    elseif ($action === 'cancel_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Cancelled' WHERE `booking_id` = :bid");
            $stmt->execute(['bid' => $booking_id]);

            // Sync Room Status
            $stmtB = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `booking_id` = :bid");
            $stmtB->execute(['bid' => $booking_id]);
            $bRow = $stmtB->fetch();
            if ($bRow) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `room_id` = :rid")->execute(['rid' => $bRow['room_id']]);
            }
            set_toast('Reservation #HE-' . $booking_id . ' cancelled.', 'warning');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 7. UPDATE PAYMENT STATUS
    elseif ($action === 'update_payment') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_status = sanitize($_POST['payment_status'] ?? 'Paid');
        $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');

        if ($booking_id > 0) {
            $stmtCheck = $pdo->prepare("SELECT `payment_id` FROM `payments` WHERE `booking_id` = :bid");
            $stmtCheck->execute(['bid' => $booking_id]);
            $pRow = $stmtCheck->fetch();

            if ($pRow) {
                $stmtUpd = $pdo->prepare("UPDATE `payments` SET `amount` = :amt, `payment_status` = :pst, `payment_mode` = :pmode, `transaction_id` = :tx WHERE `booking_id` = :bid");
                $stmtUpd->execute([
                    'amt' => $amount,
                    'pst' => $payment_status,
                    'pmode' => $payment_mode,
                    'tx' => $transaction_id,
                    'bid' => $booking_id
                ]);
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `amount`, `payment_mode`, `transaction_id`, `payment_status`) VALUES (:bid, :amt, :pmode, :tx, :pst)");
                $stmtIns->execute([
                    'bid' => $booking_id,
                    'amt' => $amount,
                    'pmode' => $payment_mode,
                    'tx' => $transaction_id,
                    'pst' => $payment_status
                ]);
            }
            set_toast('Payment status updated for #HE-' . $booking_id, 'success');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 8. DELETE BOOKING
    elseif ($action === 'delete_booking') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            // Delete from payments first
            $pdo->prepare("DELETE FROM `payments` WHERE `booking_id` = :bid")->execute(['bid' => $booking_id]);
            // Delete booking
            $del = $pdo->prepare("DELETE FROM `bookings` WHERE `booking_id` = :bid");
            $del->execute(['bid' => $booking_id]);
            set_toast('Reservation #HE-' . $booking_id . ' deleted successfully.', 'info');
        }
        redirect('/hotel_website/admin/bookings.php');
    }

    // 9. BULK ACTIONS
    elseif ($action === 'bulk_action') {
        $bulk_type = sanitize($_POST['bulk_type'] ?? '');
        $ids = $_POST['booking_ids'] ?? [];
        if (is_array($ids) && !empty($ids)) {
            $sanitized_ids = array_map('intval', array_filter($ids, 'is_numeric'));
            if (!empty($sanitized_ids)) {
                $inQuery = implode(',', array_fill(0, count($sanitized_ids), '?'));
                if ($bulk_type === 'delete') {
                    $pdo->prepare("DELETE FROM `payments` WHERE `booking_id` IN ($inQuery)")->execute($sanitized_ids);
                    $stmt = $pdo->prepare("DELETE FROM `bookings` WHERE `booking_id` IN ($inQuery)");
                    $stmt->execute($sanitized_ids);
                    set_toast(count($sanitized_ids) . ' booking(s) deleted successfully!', 'success');
                } elseif ($bulk_type === 'confirm') {
                    $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Confirmed' WHERE `booking_id` IN ($inQuery)");
                    $stmt->execute($sanitized_ids);
                    set_toast(count($sanitized_ids) . ' booking(s) confirmed!', 'success');
                } elseif ($bulk_type === 'cancel') {
                    $stmt = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Cancelled' WHERE `booking_id` IN ($inQuery)");
                    $stmt->execute($sanitized_ids);
                    set_toast(count($sanitized_ids) . ' booking(s) cancelled!', 'warning');
                }
            }
        }
        redirect('/hotel_website/admin/bookings.php');
    }
}

// Fetch All Required Data
$bookings = [];
$allCustomers = [];
$allRooms = [];

if ($pdo) {
    // 1. Bookings list with customer & room details
    $stmt = $pdo->query("
        SELECT b.*, 
               c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile, 
               c.id_proof as customer_id_proof, c.profile_pic, 
               r.room_no, r.price as room_price, r.image as room_image, 
               rt.type_name, 
               p.payment_status, p.payment_mode, p.transaction_id
        FROM `bookings` b 
        JOIN `customers` c ON b.customer_id = c.customer_id 
        JOIN `rooms` r ON b.room_id = r.room_id 
        JOIN `room_types` rt ON r.room_type_id = rt.room_type_id 
        LEFT JOIN `payments` p ON b.booking_id = p.booking_id 
        ORDER BY b.booking_id DESC
    ");
    $bookings = $stmt->fetchAll();

    // 2. Customers list
    $stmtC = $pdo->query("SELECT * FROM `customers` ORDER BY `name` ASC");
    $allCustomers = $stmtC->fetchAll();

    // 3. Rooms list
    $stmtR = $pdo->query("SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id ORDER BY r.room_no ASC");
    $allRooms = $stmtR->fetchAll();
}

// Calculate 6 Real-time KPI Stats
$total_count = count($bookings);
$confirmed_count = 0;
$checkedin_count = 0;
$checkedout_count = 0;
$pending_count = 0;
$cancelled_count = 0;
$total_revenue = 0;

foreach ($bookings as $b) {
    $st = $b['booking_status'] ?? 'Pending';
    if ($st === 'Confirmed') $confirmed_count++;
    elseif ($st === 'Checked-In') $checkedin_count++;
    elseif ($st === 'Checked-Out') $checkedout_count++;
    elseif ($st === 'Pending') $pending_count++;
    elseif ($st === 'Cancelled') $cancelled_count++;

    if ($st !== 'Cancelled') {
        $total_revenue += floatval($b['total_amount'] ?? 0);
    }
}
?>

<!-- Include Admin Bookings Stylesheet -->
<link rel="stylesheet" href="../assets/css/admin-bookings.css?v=20261006-2">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

<?php 
$page_title = 'Booking Management';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">
    
    <!-- Top Bar / Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <h3 class="font-serif-title fw-bold text-navy mb-0">Booking Management</h3>
                <p class="small text-muted mb-0">Manage guest reservations, view stay details, process check-ins/check-outs, and issue invoices.</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary rounded-3 font-semibold px-3 text-nowrap" onclick="exportBookingsCSV()">
                <i class="fa-solid fa-file-csv me-1.5"></i> Export CSV
            </button>
        </div>
    </div>



    <!-- Filters & Toolbar Card -->
    <div class="toolbar-card mb-4 p-3 bg-white rounded-4 border shadow-sm">
        <div class="row g-2 align-items-center mb-3">
            <!-- Live Search Box -->
            <div class="col-12 col-md-4">
                <div class="search-box-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchBookingInput" class="form-control" placeholder="Search Guest, Email, Phone, Ref, Room...">
                </div>
            </div>

            <!-- Payment Status Filter -->
            <div class="col-6 col-md-3 col-xl-3">
                <select id="filterPaymentSelect" class="form-select filter-select">
                    <option value="all">All Payments</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending">Payment Pending</option>
                    <option value="Failed">Failed</option>
                    <option value="Refunded">Refunded</option>
                </select>
            </div>

            <!-- Date Filter -->
            <div class="col-6 col-md-2 col-xl-2">
                <input type="date" id="filterDateInput" class="form-control filter-select" title="Filter by Check-in / Check-out Date">
            </div>

            <!-- Sort By Select -->
            <div class="col-6 col-md-3 col-xl-3">
                <select id="sortBookingSelect" class="form-select filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="checkin_asc">Check-in Date &uarr;</option>
                    <option value="checkin_desc">Check-in Date &darr;</option>
                    <option value="amount_low">Price Low to High</option>
                    <option value="amount_high">Price High to Low</option>
                    <option value="name_asc">Guest Name A-Z</option>
                    <option value="name_desc">Guest Name Z-A</option>
                </select>
            </div>
        </div>

        <!-- Status Filter Pills Row -->
        <div class="d-flex align-items-center gap-2 flex-wrap border-top pt-3">
            <button type="button" class="btn booking-status-pill active" data-status="all" onclick="filterByStatusPill('all', this)" style="background-color: #2563EB; color: #ffffff; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                All (<?php echo $total_count; ?>)
            </button>
            <button type="button" class="btn booking-status-pill" data-status="Confirmed" onclick="filterByStatusPill('Confirmed', this)" style="background-color: #F1F5F9; color: #334155; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                Confirmed (<?php echo $confirmed_count; ?>)
            </button>
            <button type="button" class="btn booking-status-pill" data-status="Pending" onclick="filterByStatusPill('Pending', this)" style="background-color: #F1F5F9; color: #334155; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                Pending (<?php echo $pending_count; ?>)
            </button>
            <button type="button" class="btn booking-status-pill" data-status="Checked-In" onclick="filterByStatusPill('Checked-In', this)" style="background-color: #F1F5F9; color: #334155; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                Checked-In (<?php echo $checkedin_count; ?>)
            </button>
            <button type="button" class="btn booking-status-pill" data-status="Checked-Out" onclick="filterByStatusPill('Checked-Out', this)" style="background-color: #F1F5F9; color: #334155; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                Checked-Out (<?php echo $checkedout_count; ?>)
            </button>
            <button type="button" class="btn booking-status-pill" data-status="Cancelled" onclick="filterByStatusPill('Cancelled', this)" style="background-color: #F1F5F9; color: #334155; border-radius: 50rem; padding: 6px 16px; font-weight: 600; font-size: 0.85rem;">
                Cancelled (<?php echo $cancelled_count; ?>)
            </button>
        </div>
    </div>

    <!-- MAIN TABLE VIEW CONTAINER -->
    <div id="bookingsTableViewContainer" class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table bookings-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Guest Details</th>
                        <th style="width: 160px;">Room &amp; Type</th>
                        <th style="width: 160px;">Stay Dates</th>
                        <th style="width: 110px;">Total Price</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 130px;">Payment</th>
                        <th class="text-center actions-column" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $b): ?>
                            <?php 
                            $b_json = htmlspecialchars(json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            
                            // Calculate nights
                            $cin = new DateTime($b['check_in']);
                            $cout = new DateTime($b['check_out']);
                            $nights = $cin->diff($cout)->days;
                            if ($nights <= 0) $nights = 1;
                            ?>
                            <tr class="booking-row-item" data-booking="<?php echo $b_json; ?>" onclick="openBookingDrawer(this)">
                                
                                <!-- Guest Details -->
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-3 text-nowrap">
                                        <?php 
                                        $pic_url = !empty($b['profile_pic']) ? format_img_url($b['profile_pic']) : ''; 
                                        ?>
                                        <?php if (!empty($pic_url)): ?>
                                            <img src="<?php echo htmlspecialchars($pic_url); ?>" 
                                                 alt="Guest Avatar" class="customer-avatar-thumb flex-shrink-0"
                                                 onerror="this.style.display='none'; var f=this.nextElementSibling; if(f) f.style.display='flex';"
                                                 onclick="event.stopPropagation(); openPhotoPreview('<?php echo htmlspecialchars($pic_url); ?>', '<?php echo htmlspecialchars(addslashes($b['customer_name'])); ?> Profile Photo');">
                                            <div class="customer-avatar-thumb flex-shrink-0" style="display:none;">
                                                <?php echo strtoupper(substr($b['customer_name'] ?? 'G', 0, 1)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="customer-avatar-thumb flex-shrink-0">
                                                <?php echo strtoupper(substr($b['customer_name'] ?? 'G', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>

                                        <span class="fw-bold text-navy clickable-row-item text-nowrap"><?php echo htmlspecialchars($b['customer_name']); ?></span>
                                    </div>
                                </td>

                                <!-- Room Info -->
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-3 text-nowrap">
                                        <?php 
                                        $rm_url = !empty($b['room_image']) ? format_img_url($b['room_image']) : ''; 
                                        ?>
                                        <?php if (!empty($rm_url)): ?>
                                            <img src="<?php echo htmlspecialchars($rm_url); ?>" 
                                                 alt="Room" class="room-thumb flex-shrink-0"
                                                 onerror="this.style.display='none';"
                                                 onclick="event.stopPropagation(); openPhotoPreview('<?php echo htmlspecialchars($rm_url); ?>', 'Room <?php echo htmlspecialchars($b['room_no']); ?> Photo');">
                                        <?php endif; ?>
                                        <div class="text-nowrap">
                                            <div class="fw-bold text-navy mb-0 text-nowrap">Room <?php echo htmlspecialchars($b['room_no']); ?></div>
                                            <div class="extra-small text-muted text-nowrap"><?php echo htmlspecialchars($b['type_name']); ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Stay Dates -->
                                <td class="text-nowrap">
                                    <div class="fw-semibold text-navy extra-small mb-0.5">
                                        <?php echo date('M d, Y', strtotime($b['check_in'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['check_out'])); ?>
                                    </div>
                                    <div class="badge bg-light text-muted border px-2 py-0.5 extra-small rounded-pill">
                                        <i class="fa-solid fa-moon me-1"></i><?php echo $nights; ?> <?php echo $nights === 1 ? 'Night' : 'Nights'; ?>
                                    </div>
                                </td>

                                <!-- Total Price -->
                                <td class="fw-bold text-navy text-nowrap">
                                    ₹<?php echo number_format($b['total_amount'], 2); ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="text-nowrap">
                                    <?php 
                                    $st = $b['booking_status'] ?? 'Pending';
                                    if ($st === 'Pending') echo '<span class="badge-status badge-status-pending"><i class="fa-solid fa-clock"></i> Pending</span>';
                                    elseif ($st === 'Confirmed') echo '<span class="badge-status badge-status-confirmed"><i class="fa-solid fa-circle-check"></i> Confirmed</span>';
                                    elseif ($st === 'Checked-In') echo '<span class="badge-status badge-status-checked-in"><i class="fa-solid fa-bed"></i> Checked-In</span>';
                                    elseif ($st === 'Checked-Out') echo '<span class="badge-status badge-status-checked-out"><i class="fa-solid fa-right-from-bracket"></i> Checked-Out</span>';
                                    elseif ($st === 'Cancelled') echo '<span class="badge-status badge-status-cancelled"><i class="fa-solid fa-ban"></i> Cancelled</span>';
                                    ?>
                                </td>

                                <!-- Payment Badge -->
                                <td class="text-nowrap">
                                    <?php 
                                    $pst = $b['payment_status'] ?? 'Paid';
                                    if ($pst === 'Paid') echo '<span class="badge-payment badge-payment-paid"><i class="fa-solid fa-check-circle"></i> Paid</span>';
                                    elseif ($pst === 'Pending') echo '<span class="badge-payment badge-payment-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>';
                                    elseif ($pst === 'Failed') echo '<span class="badge-payment badge-payment-failed"><i class="fa-solid fa-xmark-circle"></i> Failed</span>';
                                    elseif ($pst === 'Refunded') echo '<span class="badge-payment badge-payment-refunded"><i class="fa-solid fa-rotate-left"></i> Refunded</span>';
                                    ?>
                                </td>

                                <!-- Actions Column -->
                                <td class="text-center text-nowrap actions-cell" style="width: 110px;" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                                        <!-- Print Invoice Button -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="event.stopPropagation(); openAdminInvoiceModal(event, <?php echo $b['booking_id']; ?>)" title="Print Tax Invoice" aria-label="Print Tax Invoice">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="event.stopPropagation(); openDeleteBookingModal(<?php echo $b['booking_id']; ?>, '#HE-<?php echo $b['booking_id']; ?>', '<?php echo htmlspecialchars(addslashes($b['customer_name'])); ?>')" title="Delete Reservation" aria-label="Delete Reservation">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>



    <!-- Empty State Container -->
    <div id="bookingsEmptyState" class="text-center py-5 bg-white rounded-4 border p-4 mb-4" style="display: none;">
        <div class="mb-3 text-muted" style="font-size: 3rem;">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <h5 class="fw-bold text-navy mb-1">No Reservations Found</h5>
        <p class="small text-muted mb-3">No bookings match your selected filter criteria or search query.</p>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="document.getElementById('searchBookingInput').value=''; document.getElementById('filterStatusSelect').value='all'; document.getElementById('filterPaymentSelect').value='all'; filterAndRenderBookings();">
            Reset Filters
        </button>
    </div>

    <!-- PAGINATION BAR -->
    <div id="bookingsPaginationContainer" class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mt-4 mb-5 bg-white p-3 rounded-4 border shadow-sm">
        <div class="small text-muted font-semibold">
            Showing <span id="paginationStart" class="fw-bold text-navy">0</span> to <span id="paginationEnd" class="fw-bold text-navy">0</span> of <span id="paginationTotal" class="fw-bold text-navy">0</span> bookings
        </div>
        <nav aria-label="Bookings pagination">
            <ul id="paginationPages" class="pagination pagination-sm mb-0 gap-1">
                <!-- Dynamically rendered page items -->
            </ul>
        </nav>
    </div>

</div>

</main>

<!-- ================================================== -->
<!-- RIGHT-SIDE DRAWER (BOOKING DETAILS PANEL) -->
<!-- ================================================== -->
<div id="bookingDrawerBackdrop" class="booking-drawer-backdrop" onclick="closeBookingDrawer()"></div>
<div id="bookingDrawer" class="booking-drawer">
    
    <!-- Drawer Header -->
    <div class="drawer-header">
        <div>
            <div id="drawerBookingRef" class="drawer-title">#HE-100</div>
            <div id="drawerBookingDate" class="extra-small text-white-50">Created: N/A</div>
        </div>
        <button type="button" class="btn-drawer-close" onclick="closeBookingDrawer()">&times;</button>
    </div>

    <!-- Drawer Body -->
    <div class="drawer-body">
        
        <!-- Section 1: Guest Information -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-user text-primary"></i> Guest Information
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <img id="drawerGuestAvatar" src="" alt="Guest" class="customer-avatar-thumb" style="width: 54px; height: 54px; display: none;" onclick="openPhotoPreview(this.src, 'Guest Profile Photo')">
                <div id="drawerGuestInitial" class="customer-avatar-thumb" style="width: 54px; height: 54px; font-size: 1.3rem;">G</div>
                
                <div>
                    <h6 id="drawerGuestName" class="fw-bold text-navy mb-1">Guest Name</h6>
                    <div id="drawerGuestEmail" class="small text-muted"><i class="fa-solid fa-envelope me-1"></i>email@example.com</div>
                    <div id="drawerGuestMobile" class="small text-muted"><i class="fa-solid fa-phone me-1"></i>+91 9876543210</div>
                </div>
            </div>
            <div class="p-2 bg-white rounded-3 border extra-small text-muted">
                <i class="fa-solid fa-id-card me-1 text-primary"></i> ID Proof: <span id="drawerGuestID" class="fw-bold text-navy">Govt ID Verified</span>
            </div>
        </div>

        <!-- Section 2: Room Information -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-bed text-primary"></i> Room Details
            </div>
            <div class="d-flex align-items-center gap-3">
                <img id="drawerRoomPhoto" src="" alt="Room" class="rounded-3 border" style="width: 80px; height: 60px; object-fit: cover;" onclick="openPhotoPreview(this.src, 'Room Photo')">
                <div>
                    <h6 id="drawerRoomNo" class="fw-bold text-navy mb-0.5">Room 101</h6>
                    <div id="drawerRoomType" class="small text-muted mb-1">Deluxe Suite</div>
                    <div id="drawerRoomPrice" class="extra-small fw-bold text-primary">₹2,500/night</div>
                </div>
            </div>
        </div>

        <!-- Section 3: Stay Details & Guests -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-calendar-days text-primary"></i> Stay Specification
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="extra-small text-muted font-bold text-uppercase">Check-In</div>
                        <div id="drawerCheckIn" class="fw-bold text-navy small">Oct 12, 2026</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="extra-small text-muted font-bold text-uppercase">Check-Out</div>
                        <div id="drawerCheckOut" class="fw-bold text-navy small">Oct 15, 2026</div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center extra-small text-muted border-top pt-2">
                <span><i class="fa-solid fa-moon me-1 text-primary"></i> Duration: <strong id="drawerNightsCount" class="text-navy">3 Nights</strong></span>
                <span><i class="fa-solid fa-users me-1 text-primary"></i> Occupancy: <strong id="drawerGuestsCount" class="text-navy">2 Adults</strong></span>
            </div>
            <div class="mt-2.5 p-2.5 bg-white rounded-3 border extra-small">
                <div class="text-muted font-bold text-uppercase mb-1"><i class="fa-solid fa-comment-dots me-1 text-primary"></i> Special Requests</div>
                <div id="drawerSpecialRequests" class="text-navy italic">None recorded.</div>
            </div>
        </div>

        <!-- Section 4: Timeline Tracker -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-timeline text-primary"></i> Reservation Progress
            </div>
            <div class="booking-timeline">
                <div id="tlStepCreated" class="timeline-step completed">
                    <div class="timeline-icon"><i class="fa-solid fa-plus"></i></div>
                    <div class="timeline-label">Booked</div>
                </div>
                <div id="tlStepConfirmed" class="timeline-step completed">
                    <div class="timeline-icon"><i class="fa-solid fa-check"></i></div>
                    <div class="timeline-label">Confirmed</div>
                </div>
                <div id="tlStepCheckIn" class="timeline-step active">
                    <div class="timeline-icon"><i class="fa-solid fa-bed"></i></div>
                    <div class="timeline-label">Checked-In</div>
                </div>
                <div id="tlStepCheckOut" class="timeline-step">
                    <div class="timeline-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                    <div class="timeline-label">Checked-Out</div>
                </div>
            </div>
        </div>

        <!-- Section 5: Financial Breakdown -->
        <div class="drawer-section">
            <div class="drawer-section-title d-flex align-items-center justify-content-between mb-3">
                <span><i class="fa-solid fa-receipt text-primary me-1.5"></i> Financial Summary</span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill font-semibold px-3 py-1" onclick="openEditBookingModalFromData(activeBookingData)" title="Edit Billing Breakdown">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Pricing
                </button>
            </div>
            <div class="summary-card-box p-3.5 rounded-4 shadow-sm" style="background-color: #071C32 !important; border: 1px solid rgba(255, 255, 255, 0.15);">
                <div class="d-flex justify-content-between small mb-2" style="color: rgba(255,255,255,0.85);">
                    <span>Subtotal:</span>
                    <strong id="drawerSubtotal" class="text-white">₹3,500.00</strong>
                </div>
                <div class="d-flex justify-content-between small mb-2" style="color: rgba(255,255,255,0.85);">
                    <span>GST Tax (18%):</span>
                    <strong id="drawerTax" class="text-white">₹630.00</strong>
                </div>
                <div class="d-flex justify-content-between small text-success mb-3">
                    <span>Special Promo Discount:</span>
                    <strong id="drawerDiscount" class="text-success">-₹0.00</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top border-white border-opacity-20 pt-2.5">
                    <span class="fw-bold text-white fs-6 font-serif-title">Grand Total:</span>
                    <span id="drawerTotalAmount" class="fs-4 fw-bold text-primary" style="color: #38BDF8 !important;">₹4,130.00</span>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
                <div id="drawerBookingStatusBadge"></div>
                <div id="drawerPaymentStatusBadge"></div>
            </div>
        </div>

    </div>

    <!-- Drawer Footer Quick Action Toolbar -->
    <div class="drawer-footer">
        <div id="drawerActionButtons" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 1: NEW BOOKING MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="newBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-calendar-plus text-primary me-2"></i>Create New Reservation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_booking">

                <div class="modal-body space-y-3 pt-3">
                    
                    <!-- Section 1: Guest Selection -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <h6 class="fw-bold text-navy small text-uppercase mb-2"><i class="fa-solid fa-user me-1 text-primary"></i> Guest Information</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label font-bold small text-muted">SELECT EXISTING GUEST</label>
                                <select name="customer_id" id="new_customer_id" class="form-select rounded-3 font-semibold" onchange="document.getElementById('newGuestFields').style.display = this.value == '0' ? 'flex' : 'none';">
                                    <option value="0">+ Add New Guest On-The-Fly</option>
                                    <?php foreach ($allCustomers as $c): ?>
                                        <option value="<?php echo $c['customer_id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile']); ?> - <?php echo htmlspecialchars($c['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Inline New Guest Fields -->
                        <div id="newGuestFields" class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">FULL NAME *</label>
                                <input type="text" name="new_customer_name" class="form-control rounded-3 font-semibold" placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">MOBILE NUMBER *</label>
                                <input type="text" name="new_customer_mobile" class="form-control rounded-3 font-semibold" placeholder="+91 9876543210">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">EMAIL ADDRESS</label>
                                <input type="email" name="new_customer_email" class="form-control rounded-3 font-semibold" placeholder="john@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">GOVT ID PROOF</label>
                                <input type="text" name="new_customer_id_proof" class="form-control rounded-3 font-semibold" placeholder="Aadhaar / Passport / DL">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Room & Stay Dates -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <h6 class="fw-bold text-navy small text-uppercase mb-2"><i class="fa-solid fa-bed me-1 text-primary"></i> Room &amp; Stay Parameters</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">ASSIGN ROOM *</label>
                                <select name="room_id" id="new_room_id" required class="form-select rounded-3 font-semibold">
                                    <option value="">-- Select Room --</option>
                                    <?php foreach ($allRooms as $rm): ?>
                                        <option value="<?php echo $rm['room_id']; ?>" data-price="<?php echo $rm['price']; ?>">
                                            Room <?php echo htmlspecialchars($rm['room_no']); ?> - <?php echo htmlspecialchars($rm['type_name']); ?> (₹<?php echo number_format($rm['price'], 0); ?>/night) [<?php echo $rm['status']; ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-bold small text-muted">CHECK-IN DATE *</label>
                                <input type="date" name="check_in" id="new_check_in" required value="<?php echo date('Y-m-d'); ?>" class="form-control rounded-3 font-semibold">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-bold small text-muted">CHECK-OUT DATE *</label>
                                <input type="date" name="check_out" id="new_check_out" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="form-control rounded-3 font-semibold">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label font-bold small text-muted">ADULTS</label>
                                <input type="number" name="adults" min="1" value="1" class="form-control rounded-3 font-semibold">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-bold small text-muted">CHILDREN</label>
                                <input type="number" name="children" min="0" value="0" class="form-control rounded-3 font-semibold">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">RESERVATION STATUS</label>
                                <select name="booking_status" class="form-select rounded-3 font-semibold">
                                    <option value="Confirmed" selected>Confirmed</option>
                                    <option value="Checked-In">Checked-In</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Live Pricing Breakdown Summary -->
                    <div class="calc-summary-card mb-3">
                        <div class="extra-small font-bold text-uppercase text-primary mb-2"><i class="fa-solid fa-calculator me-1"></i> Real-time Price Estimation</div>
                        <div class="calc-row">
                            <span>Stay Duration</span>
                            <strong id="new_calc_nights">1 Night</strong>
                        </div>
                        <div class="calc-row">
                            <span>Room Rate / Night</span>
                            <strong id="new_calc_rate">₹0.00</strong>
                        </div>
                        <div class="calc-row">
                            <span>Subtotal</span>
                            <strong id="new_calc_subtotal">₹0.00</strong>
                        </div>
                        <div class="calc-row">
                            <span>Tax (10% Service Tax)</span>
                            <strong id="new_calc_tax">₹0.00</strong>
                        </div>
                        <div class="calc-row total-row">
                            <span>Estimated Total Charge</span>
                            <strong id="new_calc_total" class="text-primary">₹0.00</strong>
                        </div>
                        <input type="hidden" name="total_amount" id="new_total_amount" value="0">
                    </div>

                    <!-- Section 4: Payment Details -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT MODE</label>
                            <select name="payment_mode" class="form-select rounded-3 font-semibold">
                                <option value="Card">Card / Credit Card</option>
                                <option value="Cash">Cash at Desk</option>
                                <option value="UPI">UPI / GPay / PhonePe</option>
                                <option value="Online Payment">Online Payment Gateway</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT STATUS</label>
                            <select name="payment_status" class="form-select rounded-3 font-semibold">
                                <option value="Paid">Paid</option>
                                <option value="Pending">Payment Pending</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">SPECIAL REQUESTS / NOTES</label>
                            <textarea name="special_request" rows="2" class="form-control rounded-3 font-semibold" placeholder="Guest preferences, early check-in request, extra bed, etc."></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-calendar-check me-1.5"></i> Confirm &amp; Create Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 2: EDIT RESERVATION MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Reservation Specification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_booking">
                <input type="hidden" name="booking_id" id="edit_booking_id">

                <div class="modal-body space-y-3 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">BOOKING STATUS *</label>
                            <select name="booking_status" id="edit_booking_status" class="form-select rounded-3 font-semibold">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Checked-In">Checked-In</option>
                                <option value="Checked-Out">Checked-Out</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ASSIGNED ROOM *</label>
                            <select name="room_id" id="edit_room_id" class="form-select rounded-3 font-semibold">
                                <?php foreach ($allRooms as $rm): ?>
                                    <option value="<?php echo $rm['room_id']; ?>" data-price="<?php echo $rm['price']; ?>">Room <?php echo htmlspecialchars($rm['room_no']); ?> (<?php echo htmlspecialchars($rm['type_name']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">CHECK-IN DATE *</label>
                            <input type="date" name="check_in" id="edit_check_in" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">CHECK-OUT DATE *</label>
                            <input type="date" name="check_out" id="edit_check_out" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ADULTS</label>
                            <input type="number" name="adults" id="edit_adults" min="1" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">CHILDREN</label>
                            <input type="number" name="children" id="edit_children" min="0" class="form-control rounded-3 font-semibold">
                        </div>

                        <!-- ENHANCED FINANCIAL BREAKDOWN & EDIT PRICING SECTION -->
                        <div class="col-12">
                            <div class="p-3.5 rounded-4 shadow-sm border border-secondary border-opacity-25 mt-1" style="background-color: #071C32 !important; color: #ffffff;">
                                <h6 class="fw-bold small text-uppercase mb-3 d-flex align-items-center justify-content-between" style="color: #38BDF8;">
                                    <span><i class="fa-solid fa-calculator me-1.5"></i> Financial Summary &amp; Billing Edit</span>
                                    <span class="extra-small text-white-50 font-normal">Real-time Calculation</span>
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label font-bold extra-small text-white-50">SUBTOTAL (₹)</label>
                                        <input type="number" step="0.01" name="subtotal" id="edit_subtotal" class="form-control rounded-3 font-semibold bg-white text-navy" placeholder="3500.00" oninput="calculateEditTotals()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-bold extra-small text-white-50">GST TAX (18%) (₹)</label>
                                        <input type="number" step="0.01" name="gst_tax" id="edit_gst_tax" class="form-control rounded-3 font-semibold bg-white text-navy" placeholder="630.00" oninput="calculateEditTotals()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-bold extra-small text-success">SPECIAL PROMO DISCOUNT (₹)</label>
                                        <input type="number" step="0.01" name="discount" id="edit_discount" class="form-control rounded-3 font-semibold bg-white text-navy" placeholder="0.00" oninput="calculateEditTotals()">
                                    </div>
                                    <div class="col-12 border-top border-white border-opacity-15 pt-3 d-flex align-items-center justify-content-between">
                                        <span class="fw-bold text-white fs-6 font-serif-title">Grand Total Amount:</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-white font-bold fs-5">₹</span>
                                            <input type="number" step="0.01" name="total_amount" id="edit_total_amount" required class="form-control rounded-3 font-bold text-dark border-0 fs-5 px-3 py-1" style="width: 190px; background-color: #38BDF8 !important; color: #071C32 !important;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">SPECIAL REQUESTS / NOTES</label>
                            <textarea name="special_request" id="edit_special_request" rows="2" class="form-control rounded-3 font-semibold"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- STATUS TRANSITION MODALS -->
<!-- ================================================== -->

<!-- CONFIRM BOOKING MODAL -->
<div class="modal fade" id="confirmBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-circle-check text-primary me-2"></i>Confirm Reservation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="confirm_booking">
                <input type="hidden" name="booking_id" id="confirm_booking_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Are you sure you want to confirm reservation <strong id="confirm_booking_ref" class="text-primary">#HE-0</strong> for <strong id="confirm_guest_name">Guest</strong>?</p>
                    <div class="alert alert-info border-0 rounded-3 extra-small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i> The assigned room status will automatically update to <strong>RESERVED</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">Confirm Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CHECK-IN MODAL -->
<div class="modal fade" id="checkInBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-right-to-bracket text-success me-2"></i>Guest Check-In
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="checkin_booking">
                <input type="hidden" name="booking_id" id="checkin_booking_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Process Check-In for <strong id="checkin_guest_name">Guest</strong> (<strong id="checkin_booking_ref" class="text-primary">#HE-0</strong>)?</p>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 border border-success border-opacity-25 mb-2 extra-small">
                        <i class="fa-solid fa-bed me-1"></i> Assigned Room: <strong id="checkin_room_no">Room 101</strong><br>
                        Room status will update to <strong>OCCUPIED 🔴</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 font-semibold px-4">Complete Check-In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CHECK-OUT MODAL -->
<div class="modal fade" id="checkOutBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-right-from-bracket text-warning me-2"></i>Guest Check-Out
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="checkout_booking">
                <input type="hidden" name="booking_id" id="checkout_booking_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Process Check-Out for <strong id="checkout_guest_name">Guest</strong> (<strong id="checkout_booking_ref" class="text-primary">#HE-0</strong>)?</p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted font-bold text-uppercase">Total Bill Charge</span>
                            <strong id="checkout_total_amount" class="fs-5 text-navy">₹0.00</strong>
                        </div>
                        <div>
                            <label class="form-label font-bold extra-small text-muted mb-1">SET FINAL PAYMENT STATUS</label>
                            <select name="payment_status" id="checkout_payment_status" class="form-select rounded-3 font-semibold">
                                <option value="Paid">Paid in Full</option>
                                <option value="Pending">Payment Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="extra-small text-muted">
                        <i class="fa-solid fa-circle-info me-1 text-primary"></i> Room status will automatically update to <strong>AVAILABLE 🟢</strong> for new bookings.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-3 font-semibold px-4">Complete Check-Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CANCEL BOOKING MODAL -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-ban text-danger me-2"></i>Cancel Reservation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="booking_id" id="cancel_booking_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Are you sure you want to cancel reservation <strong id="cancel_booking_ref" class="text-primary">#HE-0</strong> for <strong id="cancel_guest_name">Guest</strong>?</p>
                    <div class="alert alert-warning border-0 rounded-3 extra-small mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Room availability will be restored.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-3 font-semibold px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- UPDATE PAYMENT MODAL -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-credit-card text-info me-2"></i>Update Payment Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="booking_id" id="pay_booking_id">

                <div class="modal-body space-y-3 pt-3">
                    <div class="p-2.5 bg-light rounded-3 border extra-small mb-3">
                        Updating payment status for <strong id="pay_booking_ref" class="text-primary">#HE-0</strong> (<span id="pay_guest_name">Guest</span>)
                    </div>
                    <div>
                        <label class="form-label font-bold small text-muted">TOTAL AMOUNT (₹)</label>
                        <input type="number" step="0.01" name="amount" id="pay_amount" required class="form-control rounded-3 font-semibold">
                    </div>
                    <div>
                        <label class="form-label font-bold small text-muted">PAYMENT STATUS</label>
                        <select name="payment_status" id="pay_status" class="form-select rounded-3 font-semibold">
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label font-bold small text-muted">PAYMENT METHOD</label>
                        <select name="payment_mode" id="pay_mode" class="form-select rounded-3 font-semibold">
                            <option value="Card">Card</option>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Online Payment">Online Payment</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label font-bold small text-muted">TRANSACTION / REF NO</label>
                        <input type="text" name="transaction_id" id="pay_transaction_id" class="form-control rounded-3 font-semibold" placeholder="TXN987654321">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white rounded-3 font-semibold px-4">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE BOOKING MODAL -->
<div class="modal fade" id="deleteBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-trash-can text-danger me-2"></i>Delete Reservation Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_booking">
                <input type="hidden" name="booking_id" id="delete_booking_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Are you sure you want to permanently delete reservation <strong id="delete_booking_ref" class="text-primary">#HE-0</strong> for <strong id="delete_guest_name">Guest</strong>?</p>
                    <div class="alert alert-danger border-0 rounded-3 extra-small mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> This action cannot be undone. Associated payment logs will also be deleted.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 font-semibold px-4">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- ================================================== -->
<!-- PRINTABLE LUXURY INVOICE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="adminInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 p-3 text-white rounded-top-4" style="background-color: #0B1F3A !important;">
                <h5 class="modal-title font-serif-title fw-bold text-white mb-0">
                    <i class="fa-solid fa-file-invoice text-primary me-2"></i>Reservation Tax Invoice Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                
                <div id="printableAdminInvoiceContent" class="invoice-container p-3 p-md-4 bg-white text-navy rounded-4">
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
                                <div id="adminInvStatusStamp" class="mb-2">
                                    <span class="badge bg-success bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-circle-check me-1"></i> PAID IN FULL</span>
                                </div>
                                <h5 class="fw-bold text-white mb-1 font-serif-title">Invoice <span id="adminInvRefNo">#INV-2026-00101</span></h5>
                                <div class="extra-small text-white-50">Issued Date: <strong id="adminInvDate" class="text-white"><?php echo date('d M Y'); ?></strong></div>
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
                                    <h5 id="adminInvGuestName" class="fw-bold text-navy mb-3 font-serif-title" style="font-size: 18px;">Guest Name</h5>
                                </div>
                                <div class="pt-3 border-top space-y-2 extra-small">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-envelope text-primary me-2 opacity-75"></i> Email Address:</span>
                                        <strong id="adminInvGuestEmail" class="text-navy">email@example.com</strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-mobile-screen text-primary me-2 opacity-75"></i> Phone Number:</span>
                                        <strong id="adminInvGuestMobile" class="text-navy">+91 9876543210</strong>
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
                                        <span id="adminInvNightsBadge" class="badge bg-white text-primary border border-primary border-opacity-25 extra-small font-bold px-3 py-1.5 rounded-pill">
                                            <i class="fa-solid fa-moon text-warning me-1"></i> 1 Night Stay
                                        </span>
                                    </div>
                                    <h5 id="adminInvRoomNo" class="fw-bold text-navy mb-3 font-serif-title" style="font-size: 17px;">Room 102 (Deluxe Room)</h5>
                                </div>
                                <div class="pt-3 border-top space-y-2 extra-small">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-calendar-check text-success me-2"></i> Check-In Date:</span>
                                        <strong id="adminInvCheckIn" class="text-navy">07 Oct 2026</strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"><i class="fa-solid fa-calendar-xmark text-danger me-2"></i> Check-Out Date:</span>
                                        <strong id="adminInvCheckOut" class="text-navy">08 Oct 2026</strong>
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
                                        <div id="adminInvItemDesc" class="fw-bold text-navy">Room 102 (Deluxe Room) Accommodations</div>
                                        <div class="extra-small text-muted">Luxury Guest Room Stay Package</div>
                                    </td>
                                    <td id="adminInvItemNights" class="text-center fw-bold py-3 px-3">1</td>
                                    <td id="adminInvItemRate" class="text-end fw-semibold py-3 px-3">₹4,500.00</td>
                                    <td id="adminInvItemTotal" class="text-end fw-bold text-navy py-3 px-4">₹4,500.00</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="fw-bold text-navy">GST Tax (18%)</div>
                                        <div class="extra-small text-muted">Government Goods and Services Tax</div>
                                    </td>
                                    <td class="text-center text-muted py-3 px-3">-</td>
                                    <td class="text-end text-muted py-3 px-3">18%</td>
                                    <td id="adminInvTaxRow" class="text-end fw-bold text-navy py-3 px-4">₹810.00</td>
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
                                    <div id="adminInvTxnDetails" class="extra-small text-muted">
                                        Transaction ID: <strong id="adminInvTxnId">#CARD-370AE3050F</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Financial Breakdown Card -->
                        <div class="col-md-6">
                            <div class="p-3.5 rounded-4 border" style="background-color: #F8FAFC;">
                                <div class="d-flex justify-content-between extra-small text-secondary mb-2">
                                    <span>Subtotal:</span>
                                    <strong id="adminInvSubtotal" class="text-navy">₹4,500.00</strong>
                                </div>
                                <div class="d-flex justify-content-between extra-small text-secondary mb-2">
                                    <span>GST Tax (18%):</span>
                                    <strong id="adminInvTax" class="text-navy">₹810.00</strong>
                                </div>
                                <div class="d-flex justify-content-between extra-small text-success mb-3">
                                    <span>Special Promo Discount:</span>
                                    <strong id="adminInvDiscount">-₹0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <span class="fw-bold text-navy font-serif-title fs-5">Grand Total:</span>
                                    <span id="adminInvGrandTotal" class="fw-bold text-primary" style="font-size: 24px; color: #2563EB !important;">₹5,310.00</span>
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
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger rounded-3 font-semibold px-4" onclick="downloadInvoicePDF('printableAdminInvoiceContent', 'Invoice')">
                    <i class="fa-solid fa-file-pdf me-1.5"></i> Download PDF
                </button>
                <button type="button" class="btn btn-primary rounded-3 font-semibold px-4" onclick="window.print()">
                    <i class="fa-solid fa-print me-1.5"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- PHOTO PREVIEW LIGHTBOX MODAL WITH ZOOM CONTROLS -->
<!-- ================================================== -->
<div class="modal fade" id="bookingPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-navy border-0 text-white rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 id="lightboxZoomCaption" class="modal-title font-serif-title fw-bold text-white">Photo Preview</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div id="lightboxWrapper" class="lightbox-img-wrapper">
                    <img id="lightboxZoomImg" src="" alt="Zoom Photo">
                </div>
                
                <!-- Zoom Controls Toolbar -->
                <div class="lightbox-toolbar">
                    <button type="button" id="btnZoomIn" class="btn-lightbox" title="Zoom In (+)">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>
                    <button type="button" id="btnZoomOut" class="btn-lightbox" title="Zoom Out (-)">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </button>
                    <button type="button" id="btnResetZoom" class="btn-lightbox" title="Reset Scale (100%)">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include HTML2PDF and Admin Bookings JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="../assets/js/hotel-ease.js"></script>
<script src="../assets/js/admin-bookings.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
