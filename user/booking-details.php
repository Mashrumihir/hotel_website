<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT b.*, r.room_no, r.price as room_price, rt.type_name, c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id JOIN `customers` c ON b.customer_id = c.customer_id WHERE b.booking_id = :bid AND b.customer_id = :uid LIMIT 1");
$stmt->execute(['bid' => $bookingId, 'uid' => $userId]);
$b = $stmt->fetch();

if (!$b) {
    set_toast('Booking record not found.', 'danger');
    header('Location: /hotel_website/user/my-bookings.php');
    exit;
}

$stmtPay = $pdo->prepare("SELECT * FROM `payments` WHERE `booking_id` = :bid ORDER BY `payment_id` DESC LIMIT 1");
$stmtPay->execute(['bid' => $bookingId]);
$pay = $stmtPay->fetch();

$nights = calculate_nights($b['check_in'], $b['check_out']);
$discount = ($nights >= 3) ? 1000 : 0;
$breakdown = calculate_booking_total($b['room_price'], $nights, $discount, 18);
?>

<style>
/* Luxury Hotel Invoice Styling */
.invoice-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 12px 40px rgba(11, 31, 58, 0.08);
    overflow: hidden;
    position: relative;
}

.invoice-header-strip {
    background: linear-gradient(135deg, #0B1F3A 0%, #172554 100%);
    border-bottom: 3px solid #C9A227;
}

.invoice-table thead th {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    border: 0 !important;
}

.invoice-table tbody td {
    padding: 14px 16px;
    border-color: #E2E8F0;
    vertical-align: middle;
}

.summary-card-box {
    background: linear-gradient(180deg, #F8FAFC 0%, #EFF6FF 100%);
    border: 1px solid #CBD5E1;
    border-radius: 14px;
}

/* Dark Theme Overrides for Invoice */
[data-theme="dark"] .invoice-card,
[data-bs-theme="dark"] .invoice-card,
body.dark-theme .invoice-card {
    background: #0F172A !important;
    border-color: #1E293B !important;
}

[data-theme="dark"] .summary-card-box,
[data-bs-theme="dark"] .summary-card-box,
body.dark-theme .summary-card-box {
    background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%) !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .summary-card-box .text-dark,
[data-theme="dark"] .summary-card-box .text-navy,
[data-bs-theme="dark"] .summary-card-box .text-dark,
[data-bs-theme="dark"] .summary-card-box .text-navy,
body.dark-theme .summary-card-box .text-dark,
body.dark-theme .summary-card-box .text-navy {
    color: #F8FAFC !important;
}

[data-theme="dark"] .summary-card-box .text-secondary,
[data-bs-theme="dark"] .summary-card-box .text-secondary,
body.dark-theme .summary-card-box .text-secondary {
    color: #CBD5E1 !important;
}

[data-theme="dark"] .summary-card-box .border-secondary,
[data-bs-theme="dark"] .summary-card-box .border-secondary,
body.dark-theme .summary-card-box .border-secondary {
    border-color: rgba(248, 250, 252, 0.2) !important;
}

[data-theme="dark"] .invoice-table tbody td,
[data-bs-theme="dark"] .invoice-table tbody td,
body.dark-theme .invoice-table tbody td {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    border-color: #1E293B !important;
}

[data-theme="dark"] .invoice-table tbody .text-navy,
[data-bs-theme="dark"] .invoice-table tbody .text-navy,
body.dark-theme .invoice-table tbody .text-navy {
    color: #F8FAFC !important;
}

/* Print CSS - Strictly 1 Page A4 Output with Full Color Preservation */
@page {
    size: A4 portrait;
    margin: 6mm 8mm;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 6mm 8mm;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    html, body, [data-theme="dark"], [data-bs-theme="dark"], body.dark-theme {
        background: #FFFFFF !important;
        color: #0F172A !important;
        font-size: 11px !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        overflow: visible !important;
    }

    .top-sticky-navbar,
    .hotel-sidebar,
    .sidebar-overlay,
    .no-print,
    header,
    footer,
    nav {
        display: none !important;
    }

    .main-content,
    .page-content,
    .container,
    .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .invoice-card,
    [data-theme="dark"] .invoice-card,
    body.dark-theme .invoice-card {
        background: #FFFFFF !important;
        color: #0F172A !important;
        box-shadow: none !important;
        border: 1.5px solid #0B1F3A !important;
        border-radius: 12px !important;
        margin: 0 !important;
        padding: 0 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* Top Navy Header Banner in Print */
    .invoice-header-strip,
    [data-theme="dark"] .invoice-header-strip,
    body.dark-theme .invoice-header-strip {
        background: linear-gradient(135deg, #0B1F3A 0%, #172554 100%) !important;
        color: #FFFFFF !important;
        border-bottom: 3px solid #C9A227 !important;
        padding: 1.2rem 1.5rem !important;
    }

    .invoice-header-strip h2 {
        font-size: 1.5rem !important;
        color: #FFFFFF !important;
    }

    .invoice-header-strip .text-gold {
        color: #F59E0B !important;
    }

    .invoice-header-strip .text-white-50 {
        color: rgba(255, 255, 255, 0.85) !important;
    }

    /* Badges & Customer Cards in Print */
    .bg-light {
        background-color: #F8FAFC !important;
        border-color: #E2E8F0 !important;
    }

    .badge.bg-success {
        background-color: #16A34A !important;
        color: #FFFFFF !important;
    }

    .badge.bg-primary {
        background-color: #2563EB !important;
        color: #FFFFFF !important;
    }

    .badge.bg-gold {
        background-color: #D97706 !important;
        color: #FFFFFF !important;
    }

    /* Typography in Print */
    .text-navy, .text-dark, h5, h6, strong {
        color: #0F172A !important;
    }

    .text-secondary, .text-muted {
        color: #475569 !important;
    }

    /* Itemized Table in Print */
    .invoice-table thead th,
    [data-theme="dark"] .invoice-table thead th,
    body.dark-theme .invoice-table thead th {
        background-color: #0F172A !important;
        color: #FFFFFF !important;
        padding: 8px 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
    }

    .invoice-table tbody td,
    [data-theme="dark"] .invoice-table tbody td,
    body.dark-theme .invoice-table tbody td {
        background-color: #FFFFFF !important;
        color: #0F172A !important;
        padding: 8px 12px !important;
        font-size: 11px !important;
        border-color: #E2E8F0 !important;
    }

    .invoice-table tbody .text-navy {
        color: #0F172A !important;
    }

    /* Summary Box in Print */
    .summary-card-box,
    [data-theme="dark"] .summary-card-box,
    body.dark-theme .summary-card-box {
        background: linear-gradient(180deg, #F8FAFC 0%, #EFF6FF 100%) !important;
        border: 1px solid #CBD5E1 !important;
        padding: 0.85rem 1.15rem !important;
    }

    .summary-card-box span {
        color: #334155 !important;
    }

    .summary-card-box .text-dark,
    .summary-card-box .text-navy {
        color: #0F172A !important;
        font-weight: 700 !important;
    }

    .summary-card-box .text-success {
        color: #16A34A !important;
        font-weight: 700 !important;
    }

    .summary-card-box .text-primary {
        color: #2563EB !important;
        font-weight: 800 !important;
    }

    /* Verified Digital Receipt Box in Print */
    .bg-light.rounded-3.border {
        background-color: #F8FAFC !important;
        border-color: #CBD5E1 !important;
    }

    .invoice-body-content {
        padding: 1rem 1.5rem !important;
    }

    .row, .mb-3, .mb-4, .mb-5, .mt-4, .mt-5, .pt-4, .pt-5 {
        margin-bottom: 0.5rem !important;
        margin-top: 0.35rem !important;
        padding-top: 0.35rem !important;
    }
}
</style>

<div class="container py-4">
    <!-- Action Bar -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
        <div>
            <h3 class="font-serif-title fw-bold text-navy mb-1">Official Hotel Tax Invoice</h3>
            <p class="small text-muted mb-0">Verified booking receipt &amp; tax breakdown for Reservation #HE-<?php echo $b['booking_id']; ?>.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="downloadInvoicePDF('printableUserInvoiceContent', 'Tax_Invoice')" class="btn btn-danger shadow-sm d-flex align-items-center gap-2 px-3.5 py-2 rounded-3 fw-bold">
                <i class="fa-solid fa-file-pdf fs-6"></i>
                <span>Download PDF</span>
            </button>
            <button onclick="window.print()" class="btn btn-luxury-primary shadow-sm d-flex align-items-center gap-2 px-3.5 py-2">
                <i class="fa-solid fa-print fs-6"></i>
                <span>Print Invoice</span>
            </button>
            <a href="/hotel_website/user/my-bookings.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> My Bookings
            </a>
        </div>
    </div>

    <!-- Official Invoice Card -->
    <div class="invoice-card bg-white print-area">
        <!-- Top Navy Brand Header Strip -->
        <div class="invoice-header-strip p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                <div>
                    <div class="d-flex align-items-center text-white mb-2">
                        <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease Logo" class="me-3" style="max-height: 52px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.5));" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <i class="fa-solid fa-crown text-gold fs-1 me-3" style="display:none;"></i>
                        <div>
                            <h2 class="font-serif-title fw-bold text-white mb-0" style="letter-spacing: 0.5px;">HOTEL EASE</h2>
                            <span class="text-gold small fw-bold uppercase tracking-widest" style="font-size: 10px;">Luxury Hospitality &amp; Suites</span>
                        </div>
                    </div>
                    <p class="small text-white-50 mb-1"><i class="fa-solid fa-location-dot me-1.5 text-gold"></i> Patel Colony, Road No :- 3 / Street No :- 5, Jamnagar</p>
                    <p class="small text-white-50 mb-0"><i class="fa-solid fa-phone me-1.5 text-gold"></i> +918160253134 &bull; GSTIN: <strong>24AAACH7777E1Z5</strong></p>
                </div>
                <div class="text-md-end bg-white bg-opacity-10 p-3 rounded-3 border border-white border-opacity-25">
                    <?php if ($pay && $pay['payment_status'] === 'Paid'): ?>
                        <span class="badge bg-success bg-gradient px-3 py-1.5 rounded-pill uppercase tracking-wider fw-bold mb-2 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> PAID IN FULL (<?php echo htmlspecialchars($pay['payment_mode']); ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark bg-gradient px-3 py-1.5 rounded-pill uppercase tracking-wider fw-bold mb-2 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-clock"></i> CASH AT CHECK-IN (PENDING)
                        </span>
                    <?php endif; ?>
                    <h5 class="fw-bold text-white mb-1 font-serif-title">Invoice #INV-2026-00<?php echo $b['booking_id']; ?></h5>
                    <p class="small text-white-50 mb-0">Issued Date: <strong class="text-white"><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong></p>
                </div>
            </div>
        </div>

        <div class="invoice-body-content p-4 p-md-5">
            <!-- Customer & Reservation Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="p-3.5 p-md-4 bg-light bg-gradient rounded-4 border h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 uppercase tracking-wider small fw-bold px-2.5 py-1">
                                    <i class="fa-solid fa-user me-1"></i> Billed Customer
                                </span>
                                <span class="badge bg-white text-secondary border small font-semibold">Registered Guest</span>
                            </div>
                            <h5 class="fw-bold text-navy mb-3 font-serif-title"><?php echo htmlspecialchars($b['customer_name']); ?></h5>
                        </div>
                        <div class="pt-3 border-top d-flex flex-column gap-2">
                            <div class="d-flex align-items-center justify-content-between small text-secondary">
                                <span><i class="fa-solid fa-envelope me-2 text-primary opacity-75"></i> Email Address:</span>
                                <strong class="text-navy"><?php echo htmlspecialchars($b['customer_email']); ?></strong>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-secondary">
                                <span><i class="fa-solid fa-mobile-screen me-2 text-primary opacity-75"></i> Phone Number:</span>
                                <strong class="text-navy"><?php echo htmlspecialchars($b['customer_mobile']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3.5 p-md-4 bg-light bg-gradient rounded-4 border h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 uppercase tracking-wider small fw-bold px-2.5 py-1">
                                    <i class="fa-solid fa-hotel me-1 text-warning"></i> Reservation Details
                                </span>
                                <span class="badge bg-white text-primary border border-primary border-opacity-25 small font-bold px-2.5 py-1"><i class="fa-solid fa-moon text-warning me-1"></i> <?php echo $nights; ?> <?php echo ($nights == 1) ? 'Night' : 'Nights'; ?> Stay</span>
                            </div>
                            <h5 class="fw-bold text-navy mb-3 font-serif-title">Room <?php echo htmlspecialchars($b['room_no']); ?> <small class="text-muted fs-6 font-sans">(<?php echo htmlspecialchars($b['type_name']); ?>)</small></h5>
                        </div>
                        <div class="pt-3 border-top d-flex flex-column gap-2">
                            <div class="d-flex align-items-center justify-content-between small text-secondary">
                                <span><i class="fa-solid fa-calendar-check me-2 text-success"></i> Check-in Date:</span>
                                <strong class="text-navy"><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-secondary">
                                <span><i class="fa-solid fa-calendar-xmark me-2 text-danger"></i> Check-out Date:</span>
                                <strong class="text-navy"><?php echo date('d M Y', strtotime($b['check_out'])); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itemized Table -->
            <div class="table-responsive rounded-3 border mb-4">
                <table class="table invoice-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th class="text-center">Nights</th>
                            <th class="text-end">Price / Night</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-bold text-navy">Room <?php echo htmlspecialchars($b['room_no']); ?> (<?php echo htmlspecialchars($b['type_name']); ?>) Accommodations</div>
                                <small class="text-muted">Luxury Guest Room Stay Package</small>
                            </td>
                            <td class="text-center fw-bold"><?php echo $nights; ?></td>
                            <td class="text-end fw-semibold"><?php echo format_price($b['room_price']); ?></td>
                            <td class="text-end fw-bold text-navy"><?php echo format_price($breakdown['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-bold text-navy">GST Tax (18%)</div>
                                <small class="text-muted">Government Goods and Services Tax</small>
                            </td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-end text-muted">18%</td>
                            <td class="text-end fw-bold text-navy"><?php echo format_price($breakdown['gst']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Grand Total Summary & Digital Signature Box -->
            <div class="row align-items-end g-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                            <i class="fa-solid fa-shield-halved fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-navy d-flex align-items-center gap-1.5" style="font-size: 13px;">
                                <i class="fa-solid fa-circle-check text-success"></i> Verified Digital Receipt
                            </h6>
                            <p class="small text-muted mb-0">
                                Transaction ID: <strong>#<?php echo htmlspecialchars($pay['transaction_id'] ?? ('PAY-00' . ($pay['payment_id'] ?? $b['booking_id']))); ?></strong>
                                <?php if (!empty($pay['payment_details'])): ?>
                                    &bull; <?php echo htmlspecialchars($pay['payment_details']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="summary-card-box p-4">
                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-bold text-dark"><?php echo format_price($breakdown['subtotal']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>GST Tax (18%):</span>
                            <span class="fw-bold text-dark"><?php echo format_price($breakdown['gst']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-success mb-3">
                            <span>Special Promo Discount:</span>
                            <span class="fw-bold">-<?php echo format_price($breakdown['discount']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top border-secondary border-opacity-25 pt-3">
                            <span class="fw-bold text-navy fs-5 font-serif-title">Grand Total:</span>
                            <span class="fs-3 fw-bold text-primary"><?php echo format_price($b['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Notes -->
            <div class="border-top mt-5 pt-4 text-center text-muted small">
                <p class="mb-1">Thank you for choosing <strong>Hotel Ease</strong>. We hope you enjoyed your stay!</p>
                <p class="mb-0">This is a computer-generated official tax invoice and requires no physical signature.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
