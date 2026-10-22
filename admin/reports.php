<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();
$range = isset($_GET['range']) ? sanitize($_GET['range']) : 'all';

// Handle POST actions for Edit and Delete Report Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'edit_report_entry') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $booking_status = sanitize($_POST['booking_status'] ?? 'Checked-Out');
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $payment_mode = sanitize($_POST['payment_mode'] ?? 'Card');

        if ($booking_id > 0 && $total_amount >= 0) {
            $updB = $pdo->prepare("UPDATE `bookings` SET `booking_status` = :st, `total_amount` = :amt WHERE `booking_id` = :bid");
            $updB->execute(['st' => $booking_status, 'amt' => $total_amount, 'bid' => $booking_id]);

            // Update payment record if exists
            $checkP = $pdo->prepare("SELECT COUNT(*) FROM `payments` WHERE `booking_id` = :bid");
            $checkP->execute(['bid' => $booking_id]);
            if ($checkP->fetchColumn() > 0) {
                $updP = $pdo->prepare("UPDATE `payments` SET `payment_mode` = :mode, `amount` = :amt WHERE `booking_id` = :bid");
                $updP->execute(['mode' => $payment_mode, 'amt' => $total_amount, 'bid' => $booking_id]);
            } else {
                $insP = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `amount`, `payment_mode`, `payment_status`) VALUES (:bid, :amt, :mode, 'Paid')");
                $insP->execute(['bid' => $booking_id, 'amt' => $total_amount, 'mode' => $payment_mode]);
            }

            set_toast('Report entry #' . $booking_id . ' updated successfully!', 'success');
        } else {
            set_toast('Please fill in required fields.', 'danger');
        }
        redirect('/hotel_website/admin/reports.php?range=' . urlencode($range));
    }

    elseif ($action === 'delete_report_entry') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            try {
                $pdo->exec("DELETE FROM `payments` WHERE `booking_id` = $booking_id");
                $del = $pdo->prepare("DELETE FROM `bookings` WHERE `booking_id` = :bid");
                $del->execute(['bid' => $booking_id]);
                set_toast('Report entry removed from system logs.', 'info');
            } catch (Exception $e) {
                set_toast('Unable to delete report entry: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/hotel_website/admin/reports.php?range=' . urlencode($range));
    }
}

// Fetch Reports
$sql = "SELECT b.*, c.name as customer_name, c.mobile as customer_mobile, r.room_no, rt.type_name, p.payment_mode FROM `bookings` b JOIN `customers` c ON b.customer_id = c.customer_id JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id LEFT JOIN `payments` p ON b.booking_id = p.booking_id";

if ($range === 'today') {
    $sql .= " WHERE DATE(b.created_at) = CURDATE()";
} elseif ($range === 'week') {
    $sql .= " WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($range === 'month') {
    $sql .= " WHERE MONTH(b.created_at) = MONTH(NOW()) AND YEAR(b.created_at) = YEAR(NOW())";
}

$sql .= " ORDER BY b.booking_id DESC";
$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll();

$totalSum = 0;
foreach ($reports as $rep) {
    $totalSum += $rep['total_amount'];
}
?>

<style>
.table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}
.table-responsive::-webkit-scrollbar {
    height: 6px;
}
.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.reports-table th, .reports-table td {
    vertical-align: middle !important;
    padding: 0.65rem 0.5rem !important;
}
.reports-table thead th {
    background-color: #f8f9fa !important;
    color: #475569 !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    font-size: 0.75rem !important;
    letter-spacing: 0.5px !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.table-responsive {
    overflow-x: auto !important;
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
</style>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

<?php 
$page_title = 'Financial & Occupancy Reports';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">
    <!-- Streamlined Control Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="small font-bold text-uppercase text-muted me-1"><i class="fa-solid fa-filter me-1 text-primary"></i> Filter:</span>
                <a href="reports.php?range=all" class="btn btn-sm <?php echo ($range==='all')?'btn-primary':'btn-outline-secondary'; ?> rounded-pill font-semibold">All Time</a>
                <a href="reports.php?range=today" class="btn btn-sm <?php echo ($range==='today')?'btn-primary':'btn-outline-secondary'; ?> rounded-pill font-semibold">Today</a>
                <a href="reports.php?range=week" class="btn btn-sm <?php echo ($range==='week')?'btn-primary':'btn-outline-secondary'; ?> rounded-pill font-semibold">This Week</a>
                <a href="reports.php?range=month" class="btn btn-sm <?php echo ($range==='month')?'btn-primary':'btn-outline-secondary'; ?> rounded-pill font-semibold">This Month</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill shadow-sm font-semibold">
                    <i class="fa-solid fa-print me-1"></i> Print Report
                </button>
                <button onclick="alert('Exporting CSV File...')" class="btn btn-success btn-sm rounded-pill font-semibold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Report Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3.5 p-md-4 bg-white print-area mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
            <h5 class="fw-bold text-navy mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i>Business Report Summary (<?php echo strtoupper($range); ?>)</h5>
            <span class="fs-5 fw-bold text-success">Total Revenue: <?php echo format_price($totalSum); ?></span>
        </div>

        <div class="table-responsive">
            <table class="table reports-table align-middle small mb-0 w-100">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width: 100px;">Booking ID</th>
                        <th class="text-nowrap">Customer</th>
                        <th class="text-nowrap" style="width: 130px;">Room</th>
                        <th class="text-nowrap" style="width: 100px;">Check-In</th>
                        <th class="text-nowrap" style="width: 100px;">Check-Out</th>
                        <th class="text-nowrap" style="width: 120px;">Payment Mode</th>
                        <th class="text-nowrap" style="width: 90px;">Amount</th>
                        <th class="text-nowrap" style="width: 100px;">Status</th>
                        <th class="text-end text-nowrap" style="width: 210px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $r): ?>
                            <?php $r_json = htmlspecialchars(json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>
                            <tr data-report="<?php echo $r_json; ?>">
                                <td class="fw-bold text-primary text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        #HE-<?php echo $r['booking_id']; ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-navy text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo htmlspecialchars($r['customer_name']); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        Room <?php echo htmlspecialchars($r['room_no']); ?> <small class="text-muted">(<?php echo htmlspecialchars($r['type_name']); ?>)</small>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo date('Y-m-d', strtotime($r['check_in'])); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo date('Y-m-d', strtotime($r['check_out'])); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo htmlspecialchars($r['payment_mode'] ?? 'Card'); ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-navy text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo format_price($r['total_amount']); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="clickable-report-title" onclick="openViewReportModalFromData(this)">
                                        <?php echo get_status_badge($r['booking_status']); ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap" style="width: 210px;">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5 flex-nowrap text-nowrap">
                                        <!-- View Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info btn-action-view rounded-3 font-semibold text-nowrap" 
                                                onclick="openViewReportModalFromData(this)" title="View Specification">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </button>

                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action-edit rounded-3 font-semibold text-nowrap" 
                                                onclick="openEditReportModalFromData(this)" title="Edit Report Entry">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-action-delete"
                                                data-id="<?php echo $r['booking_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($r['customer_name']); ?>"
                                                onclick="event.stopPropagation(); openDeleteReportModal(this)" title="Delete Report Entry">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No report records found for selected range.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>

<!-- 1. EDIT REPORT ENTRY MODAL -->
<div class="modal fade" id="editReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Report Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_report_entry">
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
                            <label class="form-label font-bold small text-muted">PAYMENT MODE *</label>
                            <select name="payment_mode" id="edit_payment_mode" class="form-select rounded-3 font-semibold">
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Cash">Cash</option>
                                <option value="Online Payment">Online Payment</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">BILLED AMOUNT (₹) *</label>
                            <input type="number" step="0.01" name="total_amount" id="edit_total_amount" required class="form-control rounded-3 font-semibold">
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

<!-- 2. VIEW REPORT SPECIFICATION MODAL -->
<div class="modal fade" id="viewReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-chart-pie text-info me-2"></i>Report Entry Specification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body space-y-4 pt-3">
                
                <!-- Header Card -->
                <div class="p-3 bg-light rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h4 id="view_rep_booking_ref" class="fw-bold text-primary mb-1"></h4>
                        <span id="view_rep_status_badge"></span>
                    </div>
                    <div class="text-sm-end">
                        <div class="text-muted extra-small font-bold text-uppercase">Billed Revenue</div>
                        <div id="view_rep_amount" class="fs-4 fw-bold text-navy"></div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-user me-1"></i>Guest Name</div>
                            <div id="view_rep_customer_name" class="fw-bold text-navy"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-phone me-1"></i>Mobile Number</div>
                            <div id="view_rep_customer_mobile" class="fw-semibold text-navy"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-hotel me-1"></i>Room Specification</div>
                            <div id="view_rep_room_no" class="fw-semibold text-navy"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-credit-card me-1"></i>Payment Mode</div>
                            <div id="view_rep_payment_mode" class="fw-semibold text-navy"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-calendar-check me-1"></i>Check-In Date</div>
                            <div id="view_rep_check_in" class="fw-semibold text-navy"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="text-muted extra-small font-bold text-uppercase mb-1"><i class="fa-solid fa-calendar-xmark me-1"></i>Check-Out Date</div>
                            <div id="view_rep_check_out" class="fw-semibold text-navy"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
<!-- ANIMATED DELETE REPORT ENTRY MODAL -->
<div class="modal fade" id="deleteReportModal" tabindex="-1" aria-labelledby="deleteReportModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deleteReportModalTitle">Delete Report Entry?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently delete this report record?</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-file-invoice-dollar me-2 text-danger"></i>
                    <span id="delete_report_ref">#HE-0</span> &bull; <span id="delete_report_cust">Guest</span>
                </div>

                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger extra-small rounded-3 text-start mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Warning: Deleting this report entry will remove associated revenue logs and stay references.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_report_entry">
                    <input type="hidden" name="booking_id" id="delete_report_booking_id">

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

<script>
function openDeleteReportModal(elem) {
    if (!elem) return;
    let id = elem.getAttribute('data-id') || elem.closest('tr')?.getAttribute('data-id');
    let name = elem.getAttribute('data-name') || elem.closest('tr')?.getAttribute('data-name');
    document.getElementById('delete_report_booking_id').value = id;
    document.getElementById('delete_report_ref').textContent = '#HE-' + id;
    document.getElementById('delete_report_cust').textContent = name || 'Guest';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteReportModal')).show();
}
function openViewReportModalFromData(elem) {
    const raw = elem.getAttribute('data-report') || elem.closest('tr')?.getAttribute('data-report');
    if (!raw) return;
    try {
        const r = JSON.parse(raw);
        openViewReportModal(r);
    } catch(e) {
        console.error("Invalid report JSON data", e);
    }
}

function openEditReportModalFromData(elem) {
    const raw = elem.getAttribute('data-report') || elem.closest('tr')?.getAttribute('data-report');
    if (!raw) return;
    try {
        const r = JSON.parse(raw);
        openEditReportModal(r);
    } catch(e) {
        console.error("Invalid report JSON data", e);
    }
}

// Open Edit Report Modal
function openEditReportModal(r) {
    document.getElementById('edit_booking_id').value = r.booking_id;
    document.getElementById('edit_booking_status').value = r.booking_status || 'Checked-Out';
    document.getElementById('edit_payment_mode').value = r.payment_mode || 'Card';
    document.getElementById('edit_total_amount').value = r.total_amount || 0;

    const editModal = new bootstrap.Modal(document.getElementById('editReportModal'));
    editModal.show();
}

// Open View Report Modal
function openViewReportModal(r) {
    document.getElementById('view_rep_booking_ref').textContent = '#HE-' + r.booking_id;
    document.getElementById('view_rep_customer_name').textContent = r.customer_name || 'Guest';
    document.getElementById('view_rep_customer_mobile').textContent = r.customer_mobile || 'N/A';
    document.getElementById('view_rep_room_no').textContent = 'Room ' + r.room_no + ' (' + r.type_name + ')';
    document.getElementById('view_rep_check_in').textContent = r.check_in || '';
    document.getElementById('view_rep_check_out').textContent = r.check_out || '';
    document.getElementById('view_rep_amount').textContent = '₹' + parseFloat(r.total_amount).toLocaleString('en-IN');
    document.getElementById('view_rep_payment_mode').textContent = r.payment_mode || 'Card';

    // Status Badge
    let stBadge = '<span class="badge bg-success px-3 py-1.5 rounded-pill font-semibold">' + r.booking_status + '</span>';
    if (r.booking_status === 'Cancelled') stBadge = '<span class="badge bg-danger px-3 py-1.5 rounded-pill font-semibold">Cancelled</span>';
    else if (r.booking_status === 'Pending') stBadge = '<span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill font-semibold">Pending</span>';
    document.getElementById('view_rep_status_badge').innerHTML = stBadge;

    const viewModal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    viewModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
