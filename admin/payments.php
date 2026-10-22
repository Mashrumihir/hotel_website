<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();

// Handle Form Submissions (Add, Edit, Mark Paid, Refund, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // 1. RECORD NEW PAYMENT
    if ($action === 'add_payment') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        $payment_details = sanitize($_POST['payment_details'] ?? '');
        $payment_status = sanitize($_POST['payment_status'] ?? 'Paid');

        if ($booking_id > 0 && $amount > 0) {
            $tx = !empty($transaction_id) ? $transaction_id : ('TXN-' . time());
            $stmt = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `amount`, `payment_mode`, `transaction_id`, `payment_details`, `payment_status`) VALUES (:bid, :amt, :mode, :txid, :det, :st)");
            $stmt->execute([
                'bid' => $booking_id,
                'amt' => $amount,
                'mode' => $payment_mode,
                'txid' => $tx,
                'det' => $payment_details,
                'st' => $payment_status
            ]);

            // Sync booking status to Confirmed if currently Pending and payment is Paid
            if ($payment_status === 'Paid') {
                $stmtB = $pdo->prepare("SELECT `booking_status` FROM `bookings` WHERE `booking_id` = :bid");
                $stmtB->execute(['bid' => $booking_id]);
                $bRow = $stmtB->fetch();
                if ($bRow && $bRow['booking_status'] === 'Pending') {
                    $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Confirmed' WHERE `booking_id` = :bid")->execute(['bid' => $booking_id]);
                }
            }

            set_toast('Payment transaction #' . htmlspecialchars($tx) . ' recorded successfully!', 'success');
        } else {
            set_toast('Please select a valid booking and enter an amount.', 'danger');
        }
        redirect('/hotel_website/admin/payments.php');
    }

    // 2. EDIT PAYMENT
    elseif ($action === 'edit_payment') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        $payment_details = sanitize($_POST['payment_details'] ?? '');
        $payment_status = sanitize($_POST['payment_status'] ?? 'Paid');

        if ($payment_id > 0 && $booking_id > 0 && $amount > 0) {
            $stmt = $pdo->prepare("UPDATE `payments` SET `booking_id` = :bid, `amount` = :amt, `payment_mode` = :mode, `transaction_id` = :txid, `payment_details` = :det, `payment_status` = :st WHERE `payment_id` = :pid");
            $stmt->execute([
                'bid' => $booking_id,
                'amt' => $amount,
                'mode' => $payment_mode,
                'txid' => $transaction_id,
                'det' => $payment_details,
                'st' => $payment_status,
                'pid' => $payment_id
            ]);
            set_toast('Payment audit record updated successfully!', 'success');
        } else {
            set_toast('Please fill in required fields.', 'danger');
        }
        redirect('/hotel_website/admin/payments.php');
    }

    // 3. MARK AS PAID
    elseif ($action === 'mark_paid') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        if ($payment_id > 0) {
            $stmt = $pdo->prepare("UPDATE `payments` SET `payment_status` = 'Paid' WHERE `payment_id` = :pid");
            $stmt->execute(['pid' => $payment_id]);

            // Sync linked booking
            $stmtP = $pdo->prepare("SELECT `booking_id` FROM `payments` WHERE `payment_id` = :pid");
            $stmtP->execute(['pid' => $payment_id]);
            $pRow = $stmtP->fetch();
            if ($pRow) {
                $bid = $pRow['booking_id'];
                $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Confirmed' WHERE `booking_id` = :bid AND `booking_status` = 'Pending'")->execute(['bid' => $bid]);
            }

            set_toast('Payment status updated to PAID! 🟢', 'success');
        }
        redirect('/hotel_website/admin/payments.php');
    }

    // 4. PROCESS REFUND
    elseif ($action === 'refund_payment') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $refund_amount = floatval($_POST['refund_amount'] ?? 0);
        $refund_reason = sanitize($_POST['refund_reason'] ?? 'Admin refund request');

        if ($payment_id > 0 && $refund_amount > 0) {
            $note = " [REFUND PROCESSED: ₹" . number_format($refund_amount, 2) . " on " . date('Y-m-d H:i') . " | Reason: " . $refund_reason . "]";
            $stmt = $pdo->prepare("UPDATE `payments` SET `payment_status` = 'Refunded', `payment_details` = CONCAT(COALESCE(`payment_details`, ''), :note) WHERE `payment_id` = :pid");
            $stmt->execute(['note' => $note, 'pid' => $payment_id]);

            set_toast('Refund of ₹' . number_format($refund_amount, 2) . ' processed successfully!', 'warning');
        }
        redirect('/hotel_website/admin/payments.php');
    }

    // 5. DELETE PAYMENT
    elseif ($action === 'delete_payment') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        if ($payment_id > 0) {
            $del = $pdo->prepare("DELETE FROM `payments` WHERE `payment_id` = :pid");
            $del->execute(['pid' => $payment_id]);
            set_toast('Payment audit record deleted.', 'info');
        }
        redirect('/hotel_website/admin/payments.php');
    }
}

// Fetch Payments and Bookings List from Database
$payments = [];
$bookingsList = [];

if ($pdo) {
    // Payments list with joins on bookings, customers, rooms, room_types
    $stmt = $pdo->query("
        SELECT p.*, 
               b.booking_id, b.total_amount as booking_total, b.check_in, b.check_out, b.adults, b.children, b.booking_status,
               c.customer_id, c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile, 
               c.id_proof as customer_id_proof, c.address as customer_address, c.created_at as customer_created_at, c.profile_pic,
               r.room_id, r.room_no, r.price as room_price, rt.type_name 
        FROM `payments` p 
        JOIN `bookings` b ON p.booking_id = b.booking_id 
        JOIN `customers` c ON b.customer_id = c.customer_id 
        JOIN `rooms` r ON b.room_id = r.room_id 
        JOIN `room_types` rt ON r.room_type_id = rt.room_type_id 
        ORDER BY p.payment_id DESC
    ");
    $payments = $stmt->fetchAll();

    // Bookings list for Record Payment modal selector
    $stmtB = $pdo->query("
        SELECT b.booking_id, b.total_amount, b.check_in, b.check_out, b.booking_status,
               c.customer_id, c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile,
               r.room_no, rt.type_name,
               COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount ELSE 0 END), 0) as total_paid
        FROM `bookings` b 
        JOIN `customers` c ON b.customer_id = c.customer_id 
        JOIN `rooms` r ON b.room_id = r.room_id 
        JOIN `room_types` rt ON r.room_type_id = rt.room_type_id 
        LEFT JOIN `payments` p ON b.booking_id = p.booking_id
        GROUP BY b.booking_id
        ORDER BY b.booking_id DESC
    ");
    $bookingsList = $stmtB->fetchAll();
}

// Real-time KPI Calculations
$total_revenue = 0;
$total_payments_count = count($payments);
$paid_count = 0;
$paid_amount = 0;
$pending_count = 0;
$pending_amount = 0;
$refunded_count = 0;
$refunded_amount = 0;
$failed_count = 0;
$failed_amount = 0;

// Revenue Periods
$today_revenue = 0;
$week_revenue = 0;
$month_revenue = 0;
$year_revenue = 0;

// Payment Method Distribution
$mode_distribution = [
    'Card' => 0,
    'Cash' => 0,
    'UPI' => 0,
    'Online Payment' => 0
];

$today_date = date('Y-m-d');
$current_year = date('Y');
$current_month = date('m');

foreach ($payments as $p) {
    $amt = floatval($p['amount']);
    $st = $p['payment_status'] ?? 'Paid';
    $mode = $p['payment_mode'] ?? 'Card';
    $pdate = date('Y-m-d', strtotime($p['payment_date']));
    $pyear = date('Y', strtotime($p['payment_date']));
    $pmonth = date('m', strtotime($p['payment_date']));

    if ($st === 'Paid') {
        $paid_count++;
        $paid_amount += $amt;
        $total_revenue += $amt;

        // Period revenue calculations
        if ($pdate === $today_date) $today_revenue += $amt;
        if (date('W', strtotime($p['payment_date'])) === date('W') && $pyear === $current_year) $week_revenue += $amt;
        if ($pmonth === $current_month && $pyear === $current_year) $month_revenue += $amt;
        if ($pyear === $current_year) $year_revenue += $amt;

        // Method distribution
        if (isset($mode_distribution[$mode])) {
            $mode_distribution[$mode] += $amt;
        } else {
            $mode_distribution[$mode] = $amt;
        }
    } elseif ($st === 'Pending') {
        $pending_count++;
        $pending_amount += $amt;
    } elseif ($st === 'Refunded') {
        $refunded_count++;
        $refunded_amount += $amt;
    } elseif ($st === 'Failed') {
        $failed_count++;
        $failed_amount += $amt;
    }
}
?>

<!-- Include Admin Payments Stylesheet -->
<link rel="stylesheet" href="../assets/css/admin-payments.css">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

<?php 
$page_title = 'Payment Management';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">
    
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 extra-small text-muted font-bold text-uppercase mb-0.5">
                    <span>Dashboard</span> / <span class="text-primary">Payments</span>
                </div>
                <h3 class="font-serif-title fw-bold text-navy mb-0">Payment Management</h3>
                <p class="small text-muted mb-0">Manage hotel payments, transactions, invoices, refunds and payment status.</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary rounded-3 font-semibold px-3" onclick="exportPaymentsCSV()">
                <i class="fa-solid fa-file-csv me-1.5"></i> Export CSV
            </button>
        </div>
    </div>



    <!-- Filters & Toolbar Section -->
    <div class="toolbar-card mb-4">
        <div class="row g-2 align-items-center">
            
            <!-- Search Box -->
            <div class="col-12 col-md-4 col-xl-3">
                <div class="search-box-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchPaymentInput" class="form-control" placeholder="Search Txn ID, Guest, Email, Phone, Room...">
                    <button type="button" id="btnSearchClear" class="btn-search-clear" title="Clear Search">&times;</button>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="filterStatusSelect" class="form-select filter-select">
                    <option value="all">All Statuses</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending">Pending</option>
                    <option value="Refunded">Refunded</option>
                    <option value="Failed">Failed</option>
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="filterMethodSelect" class="form-select filter-select">
                    <option value="all">All Methods</option>
                    <option value="Card">Card</option>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Online Payment">Online Payment</option>
                </select>
            </div>

            <!-- Date Filter Select -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="filterDateSelect" class="form-select filter-select">
                    <option value="all">All Dates</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <!-- Sort By -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="sortPaymentSelect" class="form-select filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="amount_high">Amount: High to Low</option>
                    <option value="amount_low">Amount: Low to High</option>
                </select>
            </div>


        </div>

        <!-- Custom Date Range Box (Hidden by default) -->
        <div id="customDateRangeBox" class="row g-2 align-items-center mt-2 pt-2 border-top" style="display: none;">
            <div class="col-6 col-md-3">
                <label class="form-label extra-small font-bold text-muted mb-0.5">FROM DATE</label>
                <input type="date" id="filterFromDate" class="form-control filter-select">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label extra-small font-bold text-muted mb-0.5">TO DATE</label>
                <input type="date" id="filterToDate" class="form-control filter-select">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label extra-small font-bold text-muted mb-0.5">MIN AMOUNT (₹)</label>
                <input type="number" id="filterMinAmount" class="form-control filter-select" placeholder="0">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label extra-small font-bold text-muted mb-0.5">MAX AMOUNT (₹)</label>
                <input type="number" id="filterMaxAmount" class="form-control filter-select" placeholder="50000">
            </div>
        </div>

        <!-- Filter Action Bar Footer -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top extra-small text-muted">
            <div id="paymentsCounterText" class="font-semibold">Showing 1–<?php echo count($payments); ?> of <?php echo count($payments); ?> payments</div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none p-0 extra-small" onclick="resetPaymentFilters()">
                    <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE VIEW CONTAINER -->
    <div id="paymentsTableViewContainer" class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table payments-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th style="width: 140px;">Room &amp; Type</th>
                        <th style="width: 150px;">Date &amp; Time</th>
                        <th style="width: 110px;">Amount</th>
                        <th style="width: 130px;">Method</th>
                        <th style="width: 110px;">Status</th>
                        <th class="text-center" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <?php 
                            $p_json = htmlspecialchars(json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $txnId = $p['transaction_id'] ?? ('PAY-00' . $p['payment_id']);
                            ?>
                            <tr class="payment-row-item" data-payment="<?php echo $p_json; ?>" onclick="openPaymentDrawer(this)">
                                
                                <!-- Customer -->
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-3 text-nowrap">
                                        <?php if (!empty($p['profile_pic'])): ?>
                                            <img src="/hotel_website/<?php echo htmlspecialchars($p['profile_pic']); ?>" alt="Guest" class="customer-avatar-thumb flex-shrink-0">
                                        <?php else: ?>
                                            <div class="customer-avatar-thumb flex-shrink-0">
                                                <?php echo strtoupper(substr($p['customer_name'] ?? 'G', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="fw-bold text-navy text-nowrap mb-0" onclick="event.stopPropagation(); openViewCustomerModal(<?php echo $p_json; ?>)">
                                            <?php echo htmlspecialchars($p['customer_name']); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Room -->
                                <td class="text-nowrap">
                                    <div class="fw-bold text-navy mb-0">Room <?php echo htmlspecialchars($p['room_no']); ?></div>
                                    <div class="extra-small text-muted"><?php echo htmlspecialchars($p['type_name']); ?></div>
                                </td>

                                <!-- Date & Time -->
                                <td class="text-nowrap">
                                    <div class="fw-semibold text-navy extra-small mb-0.5">
                                        <?php echo date('M d, Y', strtotime($p['payment_date'])); ?>
                                    </div>
                                    <div class="extra-small text-muted">
                                        <i class="fa-solid fa-clock me-1"></i><?php echo date('h:i A', strtotime($p['payment_date'])); ?>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="fw-bold text-navy text-nowrap">
                                    ₹<?php echo number_format($p['amount'], 2); ?>
                                </td>

                                <!-- Method -->
                                <td class="text-nowrap">
                                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill font-semibold">
                                        <i class="fa-solid fa-credit-card text-primary me-1"></i><?php echo htmlspecialchars($p['payment_mode']); ?>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="text-nowrap">
                                    <?php 
                                    $st = $p['payment_status'] ?? 'Paid';
                                    if ($st === 'Paid') echo '<span class="badge-payment-status badge-status-paid"><i class="fa-solid fa-circle-check"></i> Paid</span>';
                                    elseif ($st === 'Pending') echo '<span class="badge-payment-status badge-status-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>';
                                    elseif ($st === 'Refunded') echo '<span class="badge-payment-status badge-status-refunded"><i class="fa-solid fa-rotate-left"></i> Refunded</span>';
                                    elseif ($st === 'Failed') echo '<span class="badge-payment-status badge-status-failed"><i class="fa-solid fa-xmark-circle"></i> Failed</span>';
                                    ?>
                                </td>

                                 <!-- Actions Column -->
                                <td class="text-center text-nowrap" style="width: 90px;" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDeletePaymentModal(this.closest('tr'))" title="Delete Payment Record">
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
    <div id="paymentsEmptyState" class="text-center py-5 bg-white rounded-4 border p-4" style="display: none;">
        <div class="mb-3 text-muted" style="font-size: 3rem;">
            <i class="fa-solid fa-credit-card-slash"></i>
        </div>
        <h5 class="fw-bold text-navy mb-1">No Payments Found</h5>
        <p class="small text-muted mb-3">There are no payment records matching your current search or filter criteria.</p>
        <div class="d-flex align-items-center justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="resetPaymentFilters()">
                Clear Filters
            </button>
        </div>
    </div>

</div>

</main>

<!-- ================================================== -->
<!-- RIGHT-SIDE DRAWER (PAYMENT DETAILS PANEL) -->
<!-- ================================================== -->
<div id="paymentDrawerBackdrop" class="payment-drawer-backdrop" onclick="closePaymentDrawer()"></div>
<div id="paymentDrawer" class="payment-drawer">
    
    <!-- Drawer Header -->
    <div class="drawer-header">
        <div>
            <div id="drawerTxnId" class="drawer-title">#TXN-0000</div>
            <div id="drawerPaymentDate" class="extra-small text-white-50">Logged: N/A</div>
        </div>
        <button type="button" class="btn-drawer-close" onclick="closePaymentDrawer()">&times;</button>
    </div>

    <!-- Drawer Body -->
    <div class="drawer-body">
        
        <!-- Section 1: Customer Info -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <span><i class="fa-solid fa-user text-primary me-1.5"></i> Customer Information</span>
                <button type="button" class="btn btn-link btn-sm text-primary p-0 extra-small font-bold" onclick="openViewCustomerModalFromDrawer()">View Profile &rarr;</button>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <img id="drawerCustomerAvatar" src="" alt="Guest" class="customer-avatar-thumb" style="width: 50px; height: 50px; display: none;">
                <div id="drawerCustomerInitial" class="customer-avatar-thumb" style="width: 50px; height: 50px; font-size: 1.2rem;">G</div>
                
                <div>
                    <h6 id="drawerCustomerName" class="fw-bold text-navy mb-1">Guest Name</h6>
                    <div id="drawerCustomerEmail" class="extra-small text-muted"><i class="fa-solid fa-envelope me-1"></i>email@example.com</div>
                    <div id="drawerCustomerMobile" class="extra-small text-muted"><i class="fa-solid fa-phone me-1"></i>+91 9876543210</div>
                </div>
            </div>
            <div class="p-2 bg-white rounded-3 border extra-small text-muted">
                <i class="fa-solid fa-id-card me-1 text-primary"></i> Govt ID Proof: <span id="drawerCustomerIDProof" class="fw-bold text-navy">Verified</span>
            </div>
        </div>

        <!-- Section 2: Booking Details -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <span><i class="fa-solid fa-bookmark text-primary me-1.5"></i> Booking Information</span>
                <button type="button" class="btn btn-link btn-sm text-primary p-0 extra-small font-bold" onclick="openViewBookingModalFromDrawer()">View Booking &rarr;</button>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <div class="p-2 bg-white rounded-3 border">
                        <div class="extra-small text-muted font-bold text-uppercase">Booking Ref</div>
                        <div id="drawerBookingRef" class="fw-bold text-primary extra-small">#HE-100</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-white rounded-3 border">
                        <div class="extra-small text-muted font-bold text-uppercase">Assigned Room</div>
                        <div id="drawerRoomNo" class="fw-bold text-navy extra-small">Room 101</div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center extra-small text-muted">
                <span>Room Type: <strong id="drawerRoomType" class="text-navy">Deluxe Suite</strong></span>
                <span>Stay: <strong id="drawerCheckIn" class="text-navy">Oct 12</strong> &rarr; <strong id="drawerCheckOut" class="text-navy">Oct 15</strong></span>
            </div>
        </div>

        <!-- Section 3: Financial Summary -->
        <div class="drawer-section">
            <div class="drawer-section-title d-flex align-items-center justify-content-between mb-3">
                <span><i class="fa-solid fa-receipt text-primary me-1.5"></i> Payment Breakdown</span>
                <div id="drawerStatusBadge"></div>
            </div>
            
            <div class="summary-card-box p-3.5 rounded-4 shadow-sm mb-3" style="background-color: #071C32 !important; border: 1px solid rgba(255, 255, 255, 0.15);">
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
                    <span class="fw-bold text-white fs-6 font-serif-title">Total Booking Amount:</span>
                    <span id="drawerTotalAmount" class="fs-4 fw-bold text-primary" style="color: #38BDF8 !important;">₹4,130.00</span>
                </div>
            </div>

            <div class="calc-row">
                <span>Amount Paid</span>
                <strong id="drawerPaidAmount" class="text-success fs-6">₹0.00</strong>
            </div>
            <div class="calc-row">
                <span>Remaining Balance</span>
                <strong id="drawerRemainingAmount" class="text-danger">₹0.00</strong>
            </div>
            <div class="calc-row">
                <span>Payment Mode / Method</span>
                <strong id="drawerPaymentMode" class="text-navy">Card</strong>
            </div>

            <div class="mt-2.5 p-2.5 bg-white rounded-3 border extra-small">
                <div class="text-muted font-bold text-uppercase mb-1"><i class="fa-solid fa-note-sticky me-1 text-primary"></i> Notes / Reference Details</div>
                <div id="drawerPaymentNotes" class="text-navy italic">No additional notes.</div>
            </div>
        </div>

    </div>

    <!-- Drawer Footer Actions -->
    <div class="drawer-footer">
        <div id="drawerActionButtons" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 1: RECORD NEW PAYMENT MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-plus-circle text-primary me-2"></i>Record Guest Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="this.querySelector('button[type=submit]').classList.add('disabled'); this.querySelector('button[type=submit]').innerHTML='<i class=\"fa-solid fa-spinner fa-spin me-1.5\"></i> Processing...';">
                <input type="hidden" name="action" value="add_payment">

                <div class="modal-body space-y-3 pt-3">
                    
                    <!-- Booking Selection -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label font-bold small text-muted">SELECT RESERVATION *</label>
                        <select name="booking_id" id="add_booking_id" required class="form-select rounded-3 font-semibold">
                            <option value="">-- Choose Guest Booking --</option>
                            <?php foreach ($bookingsList as $b): ?>
                                <?php 
                                $rem = max(0, floatval($b['total_amount']) - floatval($b['total_paid'])); 
                                ?>
                                <option value="<?php echo $b['booking_id']; ?>" 
                                        data-total="<?php echo $b['total_amount']; ?>" 
                                        data-paid="<?php echo $b['total_paid']; ?>"
                                        data-name="<?php echo htmlspecialchars($b['customer_name']); ?>"
                                        data-room="<?php echo htmlspecialchars($b['room_no']); ?>">
                                    #HE-<?php echo $b['booking_id']; ?> - <?php echo htmlspecialchars($b['customer_name']); ?> (Room <?php echo htmlspecialchars($b['room_no']); ?>) [Total: ₹<?php echo number_format($b['total_amount'], 0); ?> | Paid: ₹<?php echo number_format($b['total_paid'], 0); ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Calculator Summary Box -->
                    <div class="calc-summary-card mb-3">
                        <div class="extra-small font-bold text-uppercase text-primary mb-2"><i class="fa-solid fa-calculator me-1"></i> Balance Breakdown</div>
                        <div class="calc-row">
                            <span>Total Booking Amount</span>
                            <strong id="add_calc_total">₹0.00</strong>
                        </div>
                        <div class="calc-row">
                            <span>Previously Paid Amount</span>
                            <strong id="add_calc_paid" class="text-success">₹0.00</strong>
                        </div>
                        <div class="calc-row total-row">
                            <span>Outstanding Remaining Balance</span>
                            <strong id="add_calc_remaining" class="text-danger">₹0.00</strong>
                        </div>
                    </div>

                    <!-- Payment Inputs -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT AMOUNT (₹) *</label>
                            <input type="number" step="0.01" name="amount" id="add_amount" required placeholder="0.00" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT METHOD *</label>
                            <select name="payment_mode" class="form-select rounded-3 font-semibold">
                                <option value="Card" selected>Card / Credit Card</option>
                                <option value="Cash">Cash at Desk</option>
                                <option value="UPI">UPI / GPay / PhonePe</option>
                                <option value="Online Payment">Online Payment Gateway</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT STATUS *</label>
                            <select name="payment_status" class="form-select rounded-3 font-semibold">
                                <option value="Paid" selected>Paid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">TRANSACTION REFERENCE / ID</label>
                            <input type="text" name="transaction_id" placeholder="e.g. CARD-991203 / UPI-109283" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">PAYMENT DETAILS / NOTES</label>
                            <textarea name="payment_details" rows="2" class="form-control rounded-3 font-semibold" placeholder="Transaction notes, receipt reference, front desk remarks..."></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Payment Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 2: EDIT PAYMENT MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Payment Audit Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_payment">
                <input type="hidden" name="payment_id" id="edit_payment_id">

                <div class="modal-body space-y-3 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">BOOKING REF *</label>
                            <select name="booking_id" id="edit_booking_id" class="form-select rounded-3 font-semibold" required>
                                <?php foreach ($bookingsList as $b): ?>
                                    <option value="<?php echo $b['booking_id']; ?>">#HE-<?php echo $b['booking_id']; ?> - <?php echo htmlspecialchars($b['customer_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT AMOUNT (₹) *</label>
                            <input type="number" step="0.01" name="amount" id="edit_amount" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT METHOD *</label>
                            <select name="payment_mode" id="edit_payment_mode" class="form-select rounded-3 font-semibold">
                                <option value="Card">Card</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Online Payment">Online Payment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PAYMENT STATUS *</label>
                            <select name="payment_status" id="edit_payment_status" class="form-select rounded-3 font-semibold">
                                <option value="Paid">Paid</option>
                                <option value="Pending">Pending</option>
                                <option value="Refunded">Refunded</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">TRANSACTION REFERENCE / ID</label>
                            <input type="text" name="transaction_id" id="edit_transaction_id" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">PAYMENT DETAILS / NOTES</label>
                            <textarea name="payment_details" id="edit_payment_details" rows="2" class="form-control rounded-3 font-semibold"></textarea>
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
<!-- MODAL 3: MARK AS PAID CONFIRMATION -->
<!-- ================================================== -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-check-circle text-success me-2"></i>Confirm Mark as Paid
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="payment_id" id="mark_payment_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Are you sure you want to mark transaction <strong id="mark_txn_id" class="text-primary">#PAY-0</strong> for <strong id="mark_customer_name">Guest</strong> as <strong>PAID IN FULL</strong>?</p>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 border border-success border-opacity-25 mb-2 extra-small">
                        <i class="fa-solid fa-wallet me-1"></i> Billed Amount: <strong id="mark_amount">₹0.00</strong><br>
                        Payment status will update to <strong>PAID 🟢</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 font-semibold px-4">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 4: REFUND MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-rotate-left text-warning me-2"></i>Process Payment Refund
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="refund_payment">
                <input type="hidden" name="payment_id" id="refund_payment_id">

                <div class="modal-body space-y-3 pt-3">
                    <p class="mb-2">Process refund for transaction <strong id="refund_txn_id" class="text-primary">#PAY-0</strong> (<span id="refund_customer_name">Guest</span>)?</p>
                    
                    <div class="p-2.5 bg-light rounded-3 border extra-small mb-2 d-flex justify-content-between align-items-center">
                        <span>Original Paid Amount:</span>
                        <strong id="refund_amount_display" class="fs-6 text-navy">₹0.00</strong>
                    </div>

                    <div>
                        <label class="form-label font-bold small text-muted">REFUND AMOUNT (₹) *</label>
                        <input type="number" step="0.01" name="refund_amount" id="refund_amount_input" required class="form-control rounded-3 font-semibold">
                    </div>

                    <div>
                        <label class="form-label font-bold small text-muted">REFUND REASON / NOTES *</label>
                        <textarea name="refund_reason" rows="2" required class="form-control rounded-3 font-semibold" placeholder="e.g. Guest cancellation / early departure / room change adjustment"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-3 font-semibold px-4">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 5: ANIMATED DELETE PAYMENT MODAL -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deletePaymentModalTitle">Delete Payment Audit Record?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently delete this payment transaction?</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-credit-card me-2 text-danger"></i>
                    <span id="delete_txn_id">#PAY-0</span> &bull; <span id="delete_customer_name">Guest</span>
                </div>

                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger extra-small rounded-3 text-start mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Warning: Deleting this payment audit log will remove transaction settlement history.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="payment_id" id="delete_payment_id">

                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-light rounded-3 px-4 font-semibold" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-action-delete rounded-3 px-4 font-bold shadow-sm">
                            <i class="fa-solid fa-trash-can me-1.5"></i> Confirm Delete
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 6: VIEW BOOKING SPECIFICATION MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="viewBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 p-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-bookmark text-primary me-2"></i>Booking Specification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body space-y-3 pt-3">
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <h4 id="vb_ref" class="fw-bold text-primary mb-0">#HE-0</h4>
                        <div id="vb_status" class="extra-small font-bold text-uppercase text-muted">Confirmed</div>
                    </div>
                    <div class="text-end">
                        <div class="extra-small text-muted font-bold text-uppercase">Total Charge</div>
                        <div id="vb_amount" class="fs-4 fw-bold text-navy">₹0.00</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <div class="extra-small text-muted font-bold text-uppercase mb-1">Guest</div>
                            <div id="vb_customer" class="fw-bold text-navy">Guest Name</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <div class="extra-small text-muted font-bold text-uppercase mb-1">Assigned Room</div>
                            <div id="vb_room" class="fw-bold text-navy">Room 101</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <div class="extra-small text-muted font-bold text-uppercase mb-1">Check-In Date</div>
                            <div id="vb_checkin" class="fw-bold text-navy">Oct 12, 2026</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <div class="extra-small text-muted font-bold text-uppercase mb-1">Check-Out Date</div>
                            <div id="vb_checkout" class="fw-bold text-navy">Oct 15, 2026</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 7: VIEW CUSTOMER PROFILE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-user text-primary me-2"></i>Customer Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body space-y-3 pt-3">
                <div class="p-3 bg-light rounded-3 border text-center">
                    <h5 id="vc_name" class="fw-bold text-navy mb-1">Guest Name</h5>
                    <div id="vc_email" class="small text-muted mb-0.5">guest@example.com</div>
                    <div id="vc_mobile" class="small text-muted">+91 9876543210</div>
                </div>
                <div class="space-y-2 extra-small">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-0.5">Govt ID Proof</div>
                        <div id="vc_idproof" class="fw-bold text-navy">Verified</div>
                    </div>
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-0.5">Registered Address</div>
                        <div id="vc_address" class="text-navy">Address text</div>
                    </div>
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-0.5">Member Since</div>
                        <div id="vc_reg_date" class="text-navy">2026-09-01</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 8: PRINTABLE PAYMENT RECEIPT -->
<!-- ================================================== -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 p-0 border-0 shadow">
            <div class="modal-header border-0 p-3 bg-navy text-white rounded-top-4">
                <h5 class="modal-title font-serif-title fw-bold text-white mb-0">
                    <i class="fa-solid fa-receipt text-primary me-2"></i>Official Payment Receipt Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div id="printableReceiptContent" class="receipt-container">
                    
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start receipt-header">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="bg-primary text-white font-serif-title fw-bold rounded-3 px-2.5 py-1 fs-4">HE</div>
                                <h3 class="font-serif-title fw-bold text-navy mb-0">HotelEase</h3>
                            </div>
                            <p class="extra-small text-muted mb-0">HotelEase Luxury Resorts &amp; Hospitality<br>123 Grand Horizon Boulevard, Suite 500<br>Contact: +91 9876543210 | support@hotelease.com</p>
                        </div>
                        <div class="text-end">
                            <h4 class="receipt-title mb-1">RECEIPT</h4>
                            <div class="extra-small text-muted">Receipt Ref: <strong id="rcptTxnId" class="text-primary">#TXN-001</strong></div>
                            <div class="extra-small text-muted">Booking Ref: <strong id="rcptRefNo">#HE-100</strong></div>
                            <div class="extra-small text-muted">Issued Date: <strong id="rcptDate">Sep 26, 2026</strong></div>
                            <div id="rcptStatusStamp" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Customer & Booking Info -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="extra-small font-bold text-uppercase text-muted mb-1"><i class="fa-solid fa-user me-1"></i> Guest Details</div>
                                <h6 id="rcptGuestName" class="fw-bold text-navy mb-1">Guest Name</h6>
                                <div id="rcptGuestEmail" class="extra-small text-muted">email@example.com</div>
                                <div id="rcptGuestMobile" class="extra-small text-muted">+91 9876543210</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="extra-small font-bold text-uppercase text-muted mb-1"><i class="fa-solid fa-hotel me-1"></i> Stay Specification</div>
                                <div id="rcptRoomNo" class="fw-bold text-navy mb-1">Room 101 (Deluxe Suite)</div>
                                <div class="extra-small text-muted">Check-In: <strong id="rcptCheckIn" class="text-navy">Oct 12, 2026</strong></div>
                                <div class="extra-small text-muted">Check-Out: <strong id="rcptCheckOut" class="text-navy">Oct 15, 2026</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <table class="table align-middle mb-4">
                        <thead>
                            <tr class="table-light">
                                <th>Transaction Description</th>
                                <th class="text-center">Payment Method</th>
                                <th class="text-end">Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="rcptItemDesc" class="fw-semibold text-navy">Room Accommodation Payment</td>
                                <td id="rcptItemMode" class="text-center fw-bold text-primary">Card</td>
                                <td id="rcptItemAmount" class="text-end fw-bold text-navy fs-6">₹0.00</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Summary Box -->
                    <div class="row justify-content-end mb-4">
                        <div class="col-6">
                            <div class="receipt-summary-box">
                                <div class="d-flex justify-content-between fs-5 fw-bold">
                                    <span class="text-navy">Total Received:</span>
                                    <span id="rcptPaidTotal" class="text-success">₹0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-top pt-3 text-muted extra-small d-flex justify-content-between align-items-end">
                        <div>
                            <strong>Receipt Terms &amp; Policy:</strong><br>
                            1. Thank you for choosing HotelEase.<br>
                            2. Computer generated payment receipt.<br>
                            3. For queries, contact accounting@hotelease.com.
                        </div>
                        <div class="text-end">
                            <div class="border-bottom border-dark pb-1 mb-1 font-serif-title text-navy fw-bold" style="width: 140px; display: inline-block;">HotelEase Desk</div>
                            <div>Authorized Signatory</div>
                        </div>
                    </div>

                </div>

            </div>
            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-3 font-semibold px-4" onclick="triggerReceiptPrint()">
                    <i class="fa-solid fa-print me-1.5"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Admin Payments JavaScript -->
<script src="../assets/js/admin-payments.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
