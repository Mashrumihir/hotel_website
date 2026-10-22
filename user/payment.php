<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT b.*, r.room_no, r.price as room_price, rt.type_name FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE b.booking_id = :bid AND b.customer_id = :uid LIMIT 1");
$stmt->execute(['bid' => $bookingId, 'uid' => $userId]);
$b = $stmt->fetch();

if (!$b) {
    set_toast('Invalid booking record for payment.', 'danger');
    header('Location: /hotel_website/user/my-bookings.php');
    exit;
}

$nights = calculate_nights($b['check_in'], $b['check_out']);
$discount = ($nights >= 3) ? 1000 : 0;
$breakdown = calculate_booking_total($b['room_price'], $nights, $discount, 18);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');
    $txnId = '';
    $details = '';
    $payStatus = 'Paid';

    if ($payment_mode === 'Card') {
        $cardName = sanitize($_POST['card_name'] ?? '');
        $cardNumber = sanitize($_POST['card_number'] ?? '');
        $cleanCard = str_replace(' ', '', $cardNumber);
        $last4 = strlen($cleanCard) >= 4 ? substr($cleanCard, -4) : '4242';
        $txnId = 'CARD-' . strtoupper(substr(md5(uniqid()), 0, 10));
        $details = 'Card ending in ' . $last4 . (!empty($cardName) ? ' (' . $cardName . ')' : '');
        $payStatus = 'Paid';
    } elseif ($payment_mode === 'UPI') {
        $upiId = sanitize($_POST['upi_id'] ?? '');
        $bankName = sanitize($_POST['netbank_select'] ?? '');
        $txnId = 'UPI-' . strtoupper(substr(md5(uniqid()), 0, 10));
        if (!empty($upiId)) {
            $details = 'UPI ID: ' . $upiId;
        } elseif (!empty($bankName)) {
            $details = 'Net Banking: ' . $bankName;
        } else {
            $details = 'UPI QR Instant Scan';
        }
        $payStatus = 'Paid';
    } else {
        $payment_mode = 'Cash';
        $txnId = 'CASH-' . strtoupper(substr(md5(uniqid()), 0, 10));
        $details = 'Payable in Cash/Card at Front Desk Check-In';
        $payStatus = 'Pending';
    }

    try {
        $pdo->beginTransaction();

        // Delete any existing pending payment records for this booking to avoid duplicates
        $stmtDel = $pdo->prepare("DELETE FROM `payments` WHERE `booking_id` = :bid AND `payment_status` = 'Pending'");
        $stmtDel->execute(['bid' => $bookingId]);

        // Insert payment record
        $stmtPay = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `amount`, `payment_mode`, `transaction_id`, `payment_details`, `payment_status`) VALUES (:bid, :amt, :mode, :txnid, :details, :pstat)");
        $stmtPay->execute([
            'bid' => $bookingId,
            'amt' => $b['total_amount'],
            'mode' => $payment_mode,
            'txnid' => $txnId,
            'details' => $details,
            'pstat' => $payStatus
        ]);

        // Update booking status to Confirmed
        $stmtUpd = $pdo->prepare("UPDATE `bookings` SET `booking_status` = 'Confirmed' WHERE `booking_id` = :bid");
        $stmtUpd->execute(['bid' => $bookingId]);

        // Add Notification
        $formattedAmtText = format_price_text($b['total_amount']);
        $notifMsg = ($payStatus === 'Paid')
            ? "Payment of " . $formattedAmtText . " via " . $payment_mode . " was successful! Reservation #HE-" . $bookingId . " is Confirmed."
            : "Reservation #HE-" . $bookingId . " is Confirmed! Payment of " . $formattedAmtText . " is scheduled for Front Desk Check-In.";

        $stmtNotif = $pdo->prepare("INSERT INTO `notifications` (`user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES ('Customer', :uid, :title, :msg, 0, NOW())");
        $stmtNotif->execute([
            'uid' => $userId,
            'title' => ($payStatus === 'Paid') ? 'Payment Successful' : 'Booking Confirmed (Pay at Desk)',
            'msg' => $notifMsg
        ]);

        $pdo->commit();

        set_toast($notifMsg, 'success');
        header('Location: /hotel_website/user/booking-details.php?id=' . $bookingId);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_toast('Payment processing failed: ' . $e->getMessage(), 'danger');
    }
}
?>

<style>
/* Exact Matching Payment Layout Styling */
.payment-main-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 12px 35px rgba(11, 31, 58, 0.06);
    padding: 2.25rem;
}

.payment-title-heading {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0b1f3a;
    letter-spacing: -0.2px;
}

/* Vertical Timeline Container */
.payment-timeline-container {
    position: relative;
    padding-left: 26px;
}

.payment-timeline-container::before {
    content: '';
    position: absolute;
    top: 26px;
    bottom: 26px;
    left: 8px;
    width: 2px;
    background-color: #cbd5e1;
    z-index: 1;
}

.payment-option-block {
    position: relative;
    margin-bottom: 1.1rem;
}

.payment-option-box {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.15rem 1.35rem;
    background: #ffffff;
    transition: all 0.22s ease-in-out;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 2;
}

.payment-option-box:hover {
    border-color: #94a3b8;
    background-color: #f8fafc;
}

.payment-option-block.selected .payment-option-box {
    border-color: #2563eb;
    background-color: #ffffff;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.08);
}

/* Bullet Node styling matching exact screenshot */
.timeline-radio-bullet {
    position: absolute;
    left: -26px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    transition: all 0.2s ease;
}

.payment-option-block.selected .timeline-radio-bullet {
    border-color: #2563eb;
    background: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18);
}

.timeline-radio-bullet::after {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #ffffff;
    display: block;
    opacity: 0;
    transform: scale(0);
    transition: all 0.2s ease;
}

.payment-option-block.selected .timeline-radio-bullet::after {
    opacity: 1;
    transform: scale(1);
}

.payment-option-title {
    font-weight: 600;
    font-size: 1.05rem;
    color: #1e293b;
}

/* Icon colors */
.icon-card { color: #2563eb; }
.icon-upi { color: #16a34a; }
.icon-cash { color: #eab308; }

/* Dynamic Subform Drawer */
.payment-subform-drawer {
    display: none;
    margin-top: 0.85rem;
    padding: 1.35rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    animation: drawerSlide 0.25s ease-out forwards;
}

@keyframes drawerSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.payment-option-block.selected .payment-subform-drawer {
    display: block;
}

/* Action Submit Button */
.btn-pay-confirm {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: none;
    border-radius: 14px;
    padding: 1.1rem 1.5rem;
    font-weight: 700;
    font-size: 1.1rem;
    width: 100%;
    transition: all 0.25s ease;
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
}

.btn-pay-confirm:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    color: #ffffff;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.42);
    transform: translateY(-2px);
}

/* UPI App Badge Buttons */
.upi-app-badge {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.5rem 0.85rem;
    background: #ffffff;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s ease;
}

.upi-app-badge:hover, .upi-app-badge.active {
    border-color: #16a34a;
    background: #f0fdf4;
    color: #15803d;
}

/* UPI / Net Banking Tab Switcher Styling per Screenshot */
#upiTab {
    background: #ffffff !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 4px !important;
}

#upiTab .nav-link {
    border-radius: 10px !important;
    padding: 9px 16px !important;
    font-weight: 700 !important;
    font-size: 0.92rem !important;
    color: #2563eb !important;
    background: transparent !important;
    border: none !important;
    transition: all 0.22s ease !important;
    cursor: pointer !important;
}

#upiTab .nav-link:hover {
    color: #1d4ed8 !important;
    background: #f8fafc !important;
}

#upiTab .nav-link.active {
    background: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3) !important;
}

#upiTab .nav-link.active i {
    color: #ffffff !important;
}

[data-theme="dark"] .payment-main-card,
[data-bs-theme="dark"] .payment-main-card,
body.dark-theme .payment-main-card {
    background: #0f172a !important;
    border-color: #1e293b !important;
}

[data-theme="dark"] .payment-title-heading,
[data-bs-theme="dark"] .payment-title-heading,
body.dark-theme .payment-title-heading {
    color: #f8fafc !important;
}

[data-theme="dark"] .payment-option-box,
[data-bs-theme="dark"] .payment-option-box,
body.dark-theme .payment-option-box {
    background: #1e293b !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .payment-option-title,
[data-bs-theme="dark"] .payment-option-title,
body.dark-theme .payment-option-title {
    color: #f8fafc !important;
}

[data-theme="dark"] .payment-subform-drawer,
[data-bs-theme="dark"] .payment-subform-drawer,
body.dark-theme .payment-subform-drawer {
    background: #1e293b !important;
    border-color: #334155 !important;
}

/* Payment Input Group Text & Form Control Styling */
.payment-input-group-text {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #64748b !important;
}

[data-theme="dark"] .payment-input-group-text,
[data-bs-theme="dark"] .payment-input-group-text,
body.dark-theme .payment-input-group-text {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}

[data-theme="dark"] .payment-subform-drawer .form-control,
[data-bs-theme="dark"] .payment-subform-drawer .form-control,
body.dark-theme .payment-subform-drawer .form-control {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

[data-theme="dark"] .payment-subform-drawer .form-control::placeholder,
[data-bs-theme="dark"] .payment-subform-drawer .form-control::placeholder,
body.dark-theme .payment-subform-drawer .form-control::placeholder {
    color: #64748b !important;
}

/* Animated Payment Progress Overlay Modal */
.payment-progress-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeInOverlay 0.3s ease forwards;
}

.payment-progress-card {
    background: #FFFFFF;
    border-radius: 24px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
    max-width: 440px;
    width: 90%;
    border: 1px solid #E2E8F0;
    animation: scaleUpCard 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

[data-theme="dark"] .payment-progress-card,
[data-bs-theme="dark"] .payment-progress-card,
body.dark-theme .payment-progress-card {
    background: #1E293B !important;
    border-color: #334155 !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7) !important;
}

[data-theme="dark"] .payment-progress-card h4,
[data-bs-theme="dark"] .payment-progress-card h4,
body.dark-theme .payment-progress-card h4 {
    color: #F8FAFC !important;
}

.progress-ring-loader {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    border: 5px solid #E2E8F0;
    border-top-color: #2563EB;
    border-right-color: #3B82F6;
    animation: spinRing 0.85s linear infinite;
}

.progress-ring-inner-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.success-checkmark-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 25px rgba(34, 197, 94, 0.4);
    animation: popCheckmark 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes spinRing {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes popCheckmark {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleUpCard {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<div class="container py-4">
    <div class="row g-4">
        <!-- User Sidebar -->
        <div class="col-lg-3">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <!-- Main Payment Section -->
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="font-serif-title fw-bold text-navy mb-1">Complete Payment &amp; Reservation</h3>
                    <p class="small text-muted mb-0">Booking Reference: <strong class="text-blue">#HE-<?php echo $b['booking_id']; ?></strong></p>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill small font-semibold">
                    <i class="fa-solid fa-lock me-1"></i> Secure 256-Bit SSL Payment
                </span>
            </div>

            <div class="row g-4">
                <!-- Payment Method Form (Layout matching screenshot) -->
                <div class="col-lg-7">
                    <div class="payment-main-card">
                        <h5 class="payment-title-heading mb-4">Select Payment Method</h5>

                        <form method="POST" id="paymentForm">
                            <!-- Timeline container with connected nodes -->
                            <div class="payment-timeline-container">
                                
                                <!-- LAYOUT 1: Credit / Debit Card -->
                                <div class="payment-option-block selected" id="blockCard">
                                    <div class="timeline-radio-bullet"></div>
                                    <div class="payment-option-box" onclick="selectPaymentMode('Card')">
                                        <div class="d-flex align-items-center gap-3">
                                            <input class="d-none" type="radio" name="payment_mode" id="modeCard" value="Card" checked>
                                            <i class="fa-solid fa-credit-card icon-card fs-5"></i>
                                            <span class="payment-option-title">Credit / Debit Card</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 opacity-75">
                                            <i class="fa-brands fa-cc-visa text-primary fs-5"></i>
                                            <i class="fa-brands fa-cc-mastercard text-danger fs-5"></i>
                                            <i class="fa-brands fa-cc-amex text-info fs-5"></i>
                                        </div>
                                    </div>

                                    <!-- Subform Drawer 1: Card Fields -->
                                    <div class="payment-subform-drawer">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label font-bold small text-muted mb-1">CARDHOLDER NAME</label>
                                                <input type="text" name="card_name" id="cardName" class="form-control rounded-3 font-semibold" placeholder="Name as printed on card" value="">
                                            </div>
                                             <div class="col-12">
                                                <label class="form-label font-bold small text-muted mb-1">CARD NUMBER</label>
                                                <div class="input-group">
                                                    <span class="input-group-text payment-input-group-text border-end-0 rounded-start-3">
                                                        <i class="fa-solid fa-credit-card"></i>
                                                    </span>
                                                    <input type="text" name="card_number" id="cardNumber" maxlength="19" class="form-control border-start-0 rounded-end-3 font-semibold" placeholder="4532 •••• •••• 8892" oninput="formatCardNumber(this)">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label font-bold small text-muted mb-1">EXPIRY DATE</label>
                                                <div class="input-group">
                                                    <span class="input-group-text payment-input-group-text border-end-0 rounded-start-3">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                    </span>
                                                    <input type="text" name="card_expiry" id="cardExpiry" maxlength="5" class="form-control border-start-0 rounded-end-3 font-semibold" placeholder="MM/YY" oninput="formatCardExpiry(this)">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label font-bold small text-muted mb-1">CVV / CVC</label>
                                                <div class="input-group">
                                                    <input type="password" name="card_cvv" id="cardCvv" maxlength="4" class="form-control border-end-0 rounded-start-3 font-semibold" placeholder="123">
                                                    <span class="input-group-text payment-input-group-text border-start-0 rounded-end-3" title="3-digit security code on back of card">
                                                        <i class="fa-solid fa-circle-info"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check small text-muted">
                                                    <input class="form-check-input" type="checkbox" id="saveCard" checked>
                                                    <label class="form-check-label" for="saveCard">Save this card securely for future fast payments</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- LAYOUT 2: UPI / Net Banking -->
                                <div class="payment-option-block" id="blockUPI">
                                    <div class="timeline-radio-bullet"></div>
                                    <div class="payment-option-box" onclick="selectPaymentMode('UPI')">
                                        <div class="d-flex align-items-center gap-3">
                                            <input class="d-none" type="radio" name="payment_mode" id="modeUPI" value="UPI">
                                            <i class="fa-solid fa-mobile-screen-button icon-upi fs-5"></i>
                                            <span class="payment-option-title">UPI / Net Banking</span>
                                        </div>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2.5 py-1">Instant</span>
                                    </div>

                                    <!-- Subform Drawer 2: UPI & Net Banking Fields -->
                                    <div class="payment-subform-drawer">
                                        <!-- Sub Nav Tabs for UPI vs Net Banking -->
                                        <ul class="nav nav-pills mb-3 nav-justified bg-white p-1 rounded-3 border small" id="upiTab" role="tablist">
                                            <li class="nav-item">
                                                <button class="nav-link active font-bold small" id="pills-upi-tab" onclick="switchUpiSubTab('upi', this)" type="button" role="tab">
                                                    <i class="fa-solid fa-mobile-screen-button me-1.5"></i> UPI
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button class="nav-link font-bold small" id="pills-netbank-tab" onclick="switchUpiSubTab('netbank', this)" type="button" role="tab">
                                                    <i class="fa-solid fa-building-columns me-1.5"></i> Net Banking
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="upiTabContent">
                                            <!-- UPI Tab -->
                                            <div class="tab-pane fade show active" id="pills-upi" role="tabpanel">
                                                <label class="form-label font-bold small text-muted mb-2">SELECT UPI APP OR VPA ID</label>
                                                <div class="d-flex flex-wrap gap-2 mb-3">
                                                    <button type="button" class="upi-app-badge" onclick="setUpiApp('gpay@okaxis', this)">
                                                        <i class="fa-brands fa-google text-primary me-1"></i> GPay
                                                    </button>
                                                    <button type="button" class="upi-app-badge" onclick="setUpiApp('phonepe@ybl', this)">
                                                        <i class="fa-solid fa-mobile text-purple me-1"></i> PhonePe
                                                    </button>
                                                    <button type="button" class="upi-app-badge" onclick="setUpiApp('paytm@paytm', this)">
                                                        <i class="fa-solid fa-wallet text-info me-1"></i> Paytm
                                                    </button>
                                                    <button type="button" class="upi-app-badge" onclick="setUpiApp('bhim@upi', this)">
                                                        <i class="fa-solid fa-indian-rupee-sign text-success me-1"></i> BHIM UPI
                                                    </button>
                                                </div>

                                                <div class="mb-1">
                                                    <label class="form-label font-bold small text-muted mb-1">ENTER UPI ID / VPA</label>
                                                    <div class="input-group">
                                                        <input type="text" name="upi_id" id="upiIdInput" class="form-control rounded-start-3 font-semibold" placeholder="username@upi or mobile@okhdfcbank" value="" oninput="resetUpiVerify()">
                                                        <button type="button" id="upiVerifyBtn" class="btn btn-outline-primary font-semibold px-3 rounded-end-3" onclick="verifyUpiId()">Verify</button>
                                                    </div>
                                                    <span id="upiVerifyMsg" class="small text-success fw-bold d-none mt-1"><i class="fa-solid fa-circle-check"></i> UPI ID Verified &amp; Ready</span>
                                                </div>
                                            </div>

                                            <!-- Net Banking Tab -->
                                            <div class="tab-pane fade" id="pills-netbank" role="tabpanel">
                                                <label class="form-label font-bold small text-muted mb-2">SELECT POPULAR BANK</label>
                                                <select name="netbank_select" id="netbankSelect" class="form-select rounded-3 font-semibold mb-3">
                                                    <option value="HDFC Bank" selected>HDFC Bank</option>
                                                    <option value="ICICI Bank">ICICI Bank</option>
                                                    <option value="State Bank of India">State Bank of India (SBI)</option>
                                                    <option value="Axis Bank">Axis Bank</option>
                                                    <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                                                    <option value="Punjab National Bank">Punjab National Bank</option>
                                                    <option value="Bank of Baroda">Bank of Baroda</option>
                                                </select>
                                                <div class="p-3 bg-white rounded-3 border text-center small text-muted">
                                                    You will be redirected to your bank's secure portal to authorize payment.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- LAYOUT 3: Cash at Front Desk Check-In -->
                                <div class="payment-option-block" id="blockCash">
                                    <div class="timeline-radio-bullet"></div>
                                    <div class="payment-option-box" onclick="selectPaymentMode('Cash')">
                                        <div class="d-flex align-items-center gap-3">
                                            <input class="d-none" type="radio" name="payment_mode" id="modeCash" value="Cash">
                                            <i class="fa-solid fa-money-bill-wave icon-cash fs-5"></i>
                                            <span class="payment-option-title">Cash at Front Desk Check-In</span>
                                        </div>
                                        <span class="badge bg-warning bg-opacity-10 text-dark rounded-pill small px-2.5 py-1 fw-bold">Pay Later</span>
                                    </div>

                                    <!-- Subform Drawer 3: Cash Payment Info -->
                                    <div class="payment-subform-drawer">
                                        <div class="p-3 bg-white rounded-3 border mb-2">
                                            <div class="d-flex align-items-center gap-2.5 mb-2 text-navy">
                                                <i class="fa-solid fa-hotel text-warning fs-5"></i>
                                                <strong class="font-bold">Pay Upon Arrival at Reception</strong>
                                            </div>
                                            <p class="small text-muted mb-2" style="line-height: 1.5;">
                                                You can pay the full stay amount of <strong><?php echo format_price($b['total_amount']); ?></strong> in Cash, UPI, or Card directly at the Hotel Front Desk when you check in.
                                            </p>
                                            <ul class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                                                <li>Your room reservation #HE-<?php echo $b['booking_id']; ?> will be instantly reserved.</li>
                                                <li>No advance online transaction charges required today.</li>
                                                <li>Please present valid government photo ID matching primary guest details at check-in.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /.payment-timeline-container -->

                            <div class="mt-4">
                                <button type="submit" id="btnSubmitPayment" class="btn-pay-confirm">
                                    Pay <?php echo format_price($b['total_amount']); ?> &amp; Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Summary Sidebar Box -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light border position-sticky" style="top: 85px;">
                        <h6 class="fw-bold text-navy uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-receipt text-primary me-2"></i> Order Summary
                        </h6>

                        <div class="p-3 bg-white rounded-3 border mb-3">
                            <div class="fw-bold text-navy mb-1">Room <?php echo htmlspecialchars($b['room_no']); ?> &bull; <?php echo htmlspecialchars($b['type_name']); ?></div>
                            <p class="small text-muted mb-1">
                                <i class="fa-solid fa-calendar-check text-primary me-1"></i> Check-in: <strong><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong>
                            </p>
                            <p class="small text-muted mb-1">
                                <i class="fa-solid fa-calendar-xmark text-danger me-1"></i> Check-out: <strong><?php echo date('d M Y', strtotime($b['check_out'])); ?></strong>
                            </p>
                            <p class="small text-muted mb-0">
                                <i class="fa-solid fa-moon text-warning me-1"></i> Stay Duration: <strong><?php echo $nights; ?> <?php echo ($nights == 1) ? 'Night' : 'Nights'; ?></strong>
                            </p>
                        </div>

                        <div class="small text-muted mb-2 d-flex justify-content-between">
                            <span>Room Charge (<?php echo $nights; ?> <?php echo ($nights == 1) ? 'night' : 'nights'; ?>):</span>
                            <strong class="text-dark"><?php echo format_price($breakdown['subtotal']); ?></strong>
                        </div>
                        <div class="small text-muted mb-2 d-flex justify-content-between">
                            <span>GST Tax (18%):</span>
                            <strong class="text-dark"><?php echo format_price($breakdown['gst']); ?></strong>
                        </div>
                        <div class="small text-success mb-3 d-flex justify-content-between">
                            <span>Promo Discount:</span>
                            <strong class="text-success">-<?php echo format_price($breakdown['discount']); ?></strong>
                        </div>

                        <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fs-6 fw-bold text-navy d-block">Total Amount Payable:</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Includes all applicable taxes</small>
                            </div>
                            <span class="fs-3 fw-bold text-blue font-serif-title"><?php echo format_price($b['total_amount']); ?></span>
                        </div>

                        <div class="mt-4 pt-3 border-top text-center text-muted small">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> SSL Encrypted &amp; Guaranteed Reservation
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Animated Payment Progress Overlay Modal -->
<div id="paymentProgressOverlay" class="payment-progress-overlay" style="display: none;">
    <div class="payment-progress-card text-center p-4 p-md-5">
        <!-- Step 1 & 2: Animated Ring Spinner -->
        <div id="paymentSpinnerBox" class="mb-4 position-relative d-inline-block">
            <div class="progress-ring-loader"></div>
            <div class="progress-ring-inner-icon">
                <i class="fa-solid fa-lock text-primary fs-3"></i>
            </div>
        </div>

        <!-- Step 3: Success Animated Checkmark -->
        <div id="paymentSuccessIconBox" class="mb-4" style="display: none;">
            <div class="success-checkmark-circle">
                <i class="fa-solid fa-check text-white fs-1"></i>
            </div>
        </div>

        <!-- Title & Subtext -->
        <h4 id="paymentProgressTitle" class="fw-bold text-navy mb-2">Processing Payment...</h4>
        <p id="paymentProgressSubtext" class="small text-muted mb-4">Please wait while we securely connect with your bank.</p>

        <!-- Animated Progress Bar -->
        <div class="progress rounded-pill mb-3" style="height: 10px; background-color: #E2E8F0;">
            <div id="paymentProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary rounded-pill" role="progressbar" style="width: 5%; transition: width 0.35s ease;"></div>
        </div>

        <!-- Security Badge -->
        <div class="small text-secondary d-flex align-items-center justify-content-center gap-1 font-semibold">
            <i class="fa-solid fa-shield-halved text-success"></i> 256-Bit SSL Encrypted &amp; Verified
        </div>
    </div>
</div>

<script>
const formattedTotalAmount = '<?php echo format_price($b['total_amount']); ?>';

function selectPaymentMode(mode) {
    const cardBlock = document.getElementById('blockCard');
    const upiBlock = document.getElementById('blockUPI');
    const cashBlock = document.getElementById('blockCash');

    const modeCard = document.getElementById('modeCard');
    const modeUPI = document.getElementById('modeUPI');
    const modeCash = document.getElementById('modeCash');

    const btn = document.getElementById('btnSubmitPayment');

    // Reset selected classes
    cardBlock.classList.remove('selected');
    upiBlock.classList.remove('selected');
    cashBlock.classList.remove('selected');

    if (mode === 'Card') {
        cardBlock.classList.add('selected');
        modeCard.checked = true;
        btn.innerHTML = 'Pay ' + formattedTotalAmount + ' &amp; Confirm Booking';
    } else if (mode === 'UPI') {
        upiBlock.classList.add('selected');
        modeUPI.checked = true;
        btn.innerHTML = 'Pay ' + formattedTotalAmount + ' &amp; Confirm Booking';
    } else if (mode === 'Cash') {
        cashBlock.classList.add('selected');
        modeCash.checked = true;
        btn.innerHTML = 'Confirm Booking (Pay Cash at Check-In)';
    }
}

function formatCardNumber(el) {
    let value = el.value.replace(/\D/g, '');
    let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
    el.value = formatted.substring(0, 19);
}

function formatCardExpiry(el) {
    let value = el.value.replace(/\D/g, '');
    if (value.length >= 2) {
        el.value = value.substring(0, 2) + '/' + value.substring(2, 4);
    } else {
        el.value = value;
    }
}

function setUpiApp(handle, btnEl) {
    document.querySelectorAll('.upi-app-badge').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    const input = document.getElementById('upiIdInput');
    if (input) {
        input.value = handle;
        verifyUpiId();
    }
}

function resetUpiVerify() {
    const msg = document.getElementById('upiVerifyMsg');
    const verifyBtn = document.getElementById('upiVerifyBtn');
    if (verifyBtn) {
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = 'Verify';
        verifyBtn.className = 'btn btn-outline-primary font-semibold px-3 rounded-end-3';
    }
    if (msg) {
        msg.className = 'small text-success fw-bold d-none mt-1';
        msg.innerHTML = '';
    }
}

function verifyUpiId() {
    const msg = document.getElementById('upiVerifyMsg');
    const input = document.getElementById('upiIdInput');
    const verifyBtn = document.getElementById('upiVerifyBtn');
    
    if (!input || input.value.trim() === '') {
        if (msg) {
            msg.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Please enter a valid UPI ID';
            msg.className = 'small text-danger fw-bold d-block mt-1';
        }
        if (verifyBtn) {
            verifyBtn.className = 'btn btn-outline-danger font-semibold px-3 rounded-end-3';
            verifyBtn.innerHTML = 'Verify';
            verifyBtn.disabled = false;
        }
        return;
    }

    if (verifyBtn) {
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying...';
        verifyBtn.className = 'btn btn-outline-primary font-semibold px-3 rounded-end-3';
    }

    setTimeout(() => {
        if (verifyBtn) {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Verified';
            verifyBtn.className = 'btn btn-success text-white font-semibold px-3 rounded-end-3';
        }
        if (msg) {
            msg.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> UPI ID Verified &amp; Ready';
            msg.className = 'small text-success fw-bold d-block mt-1';
        }
    }, 350);
}

function switchUpiSubTab(tabType, btnEl) {
    const upiTabBtn = document.getElementById('pills-upi-tab');
    const netbankTabBtn = document.getElementById('pills-netbank-tab');
    const upiPane = document.getElementById('pills-upi');
    const netbankPane = document.getElementById('pills-netbank');

    if (upiTabBtn) upiTabBtn.classList.remove('active');
    if (netbankTabBtn) netbankTabBtn.classList.remove('active');

    if (btnEl) btnEl.classList.add('active');

    if (tabType === 'upi') {
        if (upiPane) {
            upiPane.style.display = 'block';
            upiPane.classList.add('show', 'active');
        }
        if (netbankPane) {
            netbankPane.style.display = 'none';
            netbankPane.classList.remove('show', 'active');
        }
    } else if (tabType === 'netbank') {
        if (netbankPane) {
            netbankPane.style.display = 'block';
            netbankPane.classList.add('show', 'active');
        }
        if (upiPane) {
            upiPane.style.display = 'none';
            upiPane.classList.remove('show', 'active');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    selectPaymentMode('Card');

    // Attach Animated Progress Overlay to Payment Form Submission
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            if (this.dataset.submitting === 'true') return;
            e.preventDefault();
            const form = this;
            
            const overlay = document.getElementById('paymentProgressOverlay');
            const progressBar = document.getElementById('paymentProgressBar');
            const title = document.getElementById('paymentProgressTitle');
            const subtext = document.getElementById('paymentProgressSubtext');
            const spinnerBox = document.getElementById('paymentSpinnerBox');
            const successBox = document.getElementById('paymentSuccessIconBox');

            form.dataset.submitting = 'true';
            if (overlay) overlay.style.display = 'flex';
            if (progressBar) progressBar.style.width = '100%';
            if (title) title.textContent = 'Processing Payment...';
            if (subtext) subtext.textContent = 'Submitting your reservation securely...';
            if (spinnerBox) spinnerBox.style.display = 'block';
            if (successBox) successBox.style.display = 'none';

            // Submit immediately; the previous staged animation added 1.85 seconds
            // before the server could begin processing the reservation.
            form.submit();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
