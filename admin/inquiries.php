<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();

// Ensure DB columns exist
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `contact_messages` ADD COLUMN `booking_id` INT(11) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `contact_messages` ADD COLUMN `category` VARCHAR(100) DEFAULT 'General Inquiry'");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `contact_messages` ADD COLUMN `admin_reply` TEXT DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `contact_messages` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    } catch (Exception $e) {}
}

// Handle Status Update & Admin Reply Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $msg_id = intval($_POST['message_id'] ?? 0);

    if ($action === 'update_status' && $msg_id > 0) {
        $status = sanitize($_POST['status'] ?? 'Unread');
        $reply  = sanitize($_POST['admin_reply'] ?? '');

        // Fetch ticket details for notification
        $stmtCheck = $pdo->prepare("SELECT customer_id, subject FROM `contact_messages` WHERE `message_id` = :id");
        $stmtCheck->execute(['id' => $msg_id]);
        $tktData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // If admin typed a reply and status was Unread, set status to Replied
        if (!empty($reply) && $status === 'Unread') {
            $status = 'Replied';
        }

        $stmtUpd = $pdo->prepare("UPDATE `contact_messages` SET `status` = :st, `admin_reply` = :rep WHERE `message_id` = :id");
        $stmtUpd->execute([
            'st' => $status,
            'rep' => $reply,
            'id' => $msg_id
        ]);

        // Send notification to customer
        if (!empty($tktData['customer_id'])) {
            $stmtNotif = $pdo->prepare("INSERT INTO `notifications` (`user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES ('Customer', :uid, 'Support Ticket Response', :msg, 0, NOW())");
            $stmtNotif->execute([
                'uid' => $tktData['customer_id'],
                'msg' => "Official response posted for support ticket #" . (1000 + $msg_id) . ": " . ($tktData['subject'] ?? 'General Inquiry')
            ]);
        }

        set_toast('✓ Ticket response and status updated successfully!', 'success');
        redirect('/hotel_website/admin/inquiries.php');
    }

    if ($action === 'delete_inquiry' && $msg_id > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM `contact_messages` WHERE `message_id` = :id");
        $stmtDel->execute(['id' => $msg_id]);
        set_toast('Support ticket deleted successfully.', 'success');
        redirect('/hotel_website/admin/inquiries.php');
    }
}

// Fetch all support requests & inquiries
$stmt = $pdo->query("SELECT cm.*, c.name as cust_name_db, c.email as cust_email_db, c.mobile as cust_mobile_db FROM `contact_messages` cm LEFT JOIN `customers` c ON cm.customer_id = c.customer_id ORDER BY cm.message_id DESC");
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content">
<?php
$page_title = 'Customer Support Inquiries';
include __DIR__ . '/includes/navbar.php';
?>

<style>
    .inquiry-toolbar {
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    }

    .inquiry-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #inquiriesTable {
        min-width: 980px;
    }

    #inquiriesTable .inquiry-row {
        transition: background-color 0.18s ease, box-shadow 0.18s ease;
    }

    #inquiriesTable .inquiry-row:hover {
        background-color: #f7fbff;
        box-shadow: inset 4px 0 0 #2563eb;
    }

    .inquiry-action-cell {
        min-width: 220px;
        white-space: nowrap;
    }

    .inquiry-actions {
        flex-wrap: wrap;
    }

    @media (max-width: 767.98px) {
        .inquiry-page-title {
            font-size: 1.65rem;
        }

        .inquiry-action-cell {
            white-space: normal;
        }

        .inquiry-actions .btn {
            flex: 1 1 100%;
        }
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- Header Title Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-1 extra-small text-muted font-semibold">
                    <li class="breadcrumb-item"><a href="/hotel_website/admin/dashboard.php" class="text-decoration-none text-secondary">Admin</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Support Tickets</li>
                </ol>
            </nav>
            <h3 class="font-serif-title fw-bold text-navy mb-1 inquiry-page-title">
                <i class="fa-solid fa-headset text-primary me-2"></i> Support Tickets &amp; Concierge Desk
            </h3>
            <p class="small text-muted mb-0">View, manage, and post official responses to customer support requests and guest inquiries.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3.5 py-2 rounded-pill font-semibold">
                Total Tickets: <?php echo count($inquiries); ?>
            </span>
        </div>
    </div>

    <!-- Main Inquiries Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="p-3.5 border-bottom inquiry-toolbar">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="searchInquiryInput" class="form-control border-start-0 ps-0 font-medium" placeholder="Search ticket ID, customer name, email, category, or message...">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 ms-auto">
                    <select id="filterStatusSelect" class="form-select form-select-sm rounded-3 font-semibold">
                        <option value="all">All Statuses</option>
                        <option value="Unread">Unread / Open</option>
                        <option value="Replied">Replied / Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive inquiry-table-wrap">
            <table class="table align-middle small mb-0" id="inquiriesTable">
                <thead class="table-light text-uppercase tracking-wider extra-small font-bold">
                    <tr>
                        <th class="ps-4" style="min-width: 220px;">Customer Guest</th>
                        <th style="min-width: 240px;">Subject</th>
                        <th style="width: 180px;">Date Submitted</th>
                        <th class="text-end pe-4" style="width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inquiries)): ?>
                        <?php foreach ($inquiries as $msg): ?>
                            <?php
                            $msg_id = $msg['message_id'];
                            $ticket_no = '#TKT-' . (1000 + $msg_id);
                            $sender_name = !empty($msg['name']) ? $msg['name'] : ($msg['cust_name_db'] ?? 'Guest');
                            $sender_email = !empty($msg['email']) ? $msg['email'] : ($msg['cust_email_db'] ?? 'N/A');
                            $sender_phone = !empty($msg['phone']) ? $msg['phone'] : ($msg['cust_mobile_db'] ?? 'N/A');
                            $category = !empty($msg['category']) ? $msg['category'] : 'General Inquiry';
                            $subject = !empty($msg['subject']) ? $msg['subject'] : 'General Inquiry';
                            $body = $msg['message'] ?? '';
                            $admin_reply = $msg['admin_reply'] ?? '';
                            $status = $msg['status'] ?? 'Unread';
                            $booking_id = !empty($msg['booking_id']) ? $msg['booking_id'] : '';
                            $date = date('d M Y, h:i A', strtotime($msg['created_at']));

                            $statusBadge = 'bg-warning text-dark border-warning';
                            $statusLabel = 'Unread';
                            if ($status === 'Replied' || $status === 'Resolved') {
                                $statusBadge = 'bg-success bg-opacity-10 text-success border-success border-opacity-25';
                                $statusLabel = 'Replied';
                            } elseif ($status === 'Closed') {
                                $statusBadge = 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25';
                                $statusLabel = 'Closed';
                            }

                            $jsonMsg = htmlspecialchars(json_encode([
                                'id' => $msg_id,
                                'ticket_no' => $ticket_no,
                                'name' => $sender_name,
                                'email' => $sender_email,
                                'phone' => $sender_phone,
                                'category' => $category,
                                'subject' => $subject,
                                'body' => $body,
                                'admin_reply' => $admin_reply,
                                'booking_id' => $booking_id,
                                'status' => $status,
                                'date' => $date
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="inquiry-row" style="cursor: pointer;" data-inquiry='<?php echo $jsonMsg; ?>' data-status="<?php echo htmlspecialchars($status); ?>" data-search="<?php echo htmlspecialchars(strtolower($ticket_no . ' ' . $sender_name . ' ' . $sender_email . ' ' . $category . ' ' . $subject . ' ' . $body)); ?>" onclick="openAdminInquiryModal(this)">
                                <td class="ps-4">
                                    <div class="fw-bold text-navy mb-0.5"><?php echo htmlspecialchars($sender_name); ?></div>
                                    <div class="extra-small text-muted"><i class="fa-solid fa-envelope me-1 text-primary opacity-75"></i><?php echo htmlspecialchars($sender_email); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-navy text-truncate max-w-xs mb-0.5" title="<?php echo htmlspecialchars($subject); ?>">
                                        <?php echo htmlspecialchars($subject); ?>
                                    </div>
                                    <?php if (!empty($booking_id)): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 extra-small">Booking #<?php echo $booking_id; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap text-muted extra-small">
                                    <i class="fa-solid fa-clock me-1 text-secondary"></i><?php echo $date; ?>
                                </td>
                                <td class="text-end pe-4 inquiry-action-cell" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5 inquiry-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-2.5 py-1" onclick="event.stopPropagation(); openAdminInquiryModal(this)" title="View &amp; Reply">
                                            <i class="fa-solid fa-eye me-1"></i> View / Reply
                                        </button>
                                        <button type="button" class="btn btn-action-delete" 
                                                data-id="<?php echo $msg_id; ?>"
                                                data-subject="<?php echo htmlspecialchars($subject); ?>"
                                                onclick="event.stopPropagation(); openDeleteInquiryModal(this);" title="Delete Support Ticket">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                No customer support tickets or messages saved in the database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<!-- View & Reply Support Ticket Modal -->
<div class="modal fade" id="viewInquiryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-white">
            <div class="modal-header border-bottom p-4">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill extra-small font-bold mb-1" id="modalTicketNo">#TKT-1001</span>
                    <h5 class="modal-title font-serif-title fw-bold text-navy mb-0" id="modalSubject">Support Ticket Inquiry</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Guest Contact Details Box -->
                <div class="p-3 bg-light rounded-4 border mb-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <span class="extra-small text-muted d-block font-semibold">Customer Guest</span>
                            <strong id="modalSenderName" class="text-navy fs-6">Guest Name</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="extra-small text-muted d-block font-semibold">Email Contact</span>
                            <strong id="modalSenderEmail" class="text-navy small font-bold">user@example.com</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="extra-small text-muted d-block font-semibold">Phone / Category</span>
                            <strong id="modalSenderCategory" class="text-navy small font-bold">General Inquiry</strong>
                        </div>
                    </div>
                </div>

                <!-- Related Booking Ref -->
                <div id="modalBookingBox" class="mb-3 d-none">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill font-bold small">
                        <i class="fa-solid fa-calendar-check me-1"></i> Related Reservation: Booking #<span id="modalBookingId"></span>
                    </span>
                </div>

                <!-- Customer Message -->
                <div class="mb-4">
                    <label class="form-label extra-small text-muted font-bold text-uppercase tracking-wider mb-1">Customer Message Content</label>
                    <div id="modalBody" class="p-3.5 bg-light rounded-4 border text-dark font-medium small" style="line-height: 1.6; white-space: pre-line; min-height: 90px;"></div>
                </div>

                <!-- Form to Post Admin Reply & Update Status -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="message_id" id="modalMsgIdInput" value="">
                    
                    <div class="mb-3">
                        <label class="form-label small font-bold text-navy mb-1">
                            <i class="fa-solid fa-reply me-1 text-primary"></i> Concierge Response / Official Admin Reply *
                        </label>
                        <textarea name="admin_reply" id="modalAdminReplyInput" class="form-control rounded-3 font-semibold" rows="4" placeholder="Type official response or resolution notes to send to the guest..."></textarea>
                        <span class="extra-small text-muted d-block mt-1">This response will be visible on the customer's support ticket dashboard and logged into account notifications.</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <label class="small font-bold text-navy mb-0">Update Status:</label>
                            <select name="status" id="modalStatusSelect" class="form-select form-select-sm rounded-3 font-semibold" style="width: 150px;">
                                <option value="Unread">Open / Unread</option>
                                <option value="Replied">Replied / Resolved</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-luxury-primary font-bold px-4 py-2 shadow-sm rounded-pill">
                            <i class="fa-solid fa-paper-plane me-1.5"></i> Save &amp; Post Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ANIMATED DELETE INQUIRY MODAL -->
<div class="modal fade" id="deleteInquiryModal" tabindex="-1" aria-labelledby="deleteInquiryModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deleteInquiryModalTitle">Delete Support Ticket?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently delete this customer inquiry?</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-headset me-2 text-danger"></i>
                    <span id="delete_inquiry_subject">Support Ticket</span>
                </div>

                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger extra-small rounded-3 text-start mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Warning: This action will purge the inquiry ticket and correspondence history.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_inquiry">
                    <input type="hidden" name="message_id" id="delete_inquiry_id">

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
function showInquiryModal(modalElem) {
    if (!modalElem) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElem).show();
        return;
    }

    modalElem.classList.add('show');
    modalElem.style.display = 'block';
    modalElem.removeAttribute('aria-hidden');
    modalElem.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');

    if (!document.querySelector('.inquiry-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show inquiry-modal-backdrop';
        backdrop.addEventListener('click', function () {
            hideInquiryModal(modalElem);
        });
        document.body.appendChild(backdrop);
    }
}

function hideInquiryModal(modalElem) {
    if (!modalElem) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(modalElem);
        if (instance) {
            instance.hide();
            return;
        }
    }

    modalElem.classList.remove('show');
    modalElem.style.display = 'none';
    modalElem.setAttribute('aria-hidden', 'true');
    modalElem.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.inquiry-modal-backdrop').forEach(function (backdrop) {
        backdrop.remove();
    });
}

function openDeleteInquiryModal(elem) {
    if (!elem) return;
    let id = elem.getAttribute('data-id') || elem.closest('[data-id]')?.getAttribute('data-id');
    let subject = elem.getAttribute('data-subject') || elem.closest('[data-subject]')?.getAttribute('data-subject');
    const idInput = document.getElementById('delete_inquiry_id');
    const subjectOutput = document.getElementById('delete_inquiry_subject');
    const modalElem = document.getElementById('deleteInquiryModal');

    if (!idInput || !subjectOutput || !modalElem || !id) return;
    idInput.value = id;
    subjectOutput.textContent = subject || ('Support Ticket #' + id);
    showInquiryModal(modalElem);
}

function openAdminInquiryModal(elem) {
    if (!elem) return;
    const raw = elem.getAttribute ? (elem.getAttribute('data-inquiry') || elem.closest('[data-inquiry]')?.getAttribute('data-inquiry')) : null;
    if (!raw) return;
    try {
        const data = JSON.parse(raw);
        document.getElementById('modalMsgIdInput').value = data.id;
        document.getElementById('modalTicketNo').textContent = data.ticket_no;
        document.getElementById('modalSenderName').textContent = data.name;
        document.getElementById('modalSenderEmail').textContent = data.email + (data.phone && data.phone !== 'N/A' ? ' | ' + data.phone : '');
        document.getElementById('modalSenderCategory').textContent = data.category;
        document.getElementById('modalSubject').textContent = data.subject;
        document.getElementById('modalBody').textContent = data.body || 'No message content provided.';
        document.getElementById('modalAdminReplyInput').value = data.admin_reply || '';
        document.getElementById('modalStatusSelect').value = data.status || 'Unread';

        const bookingBox = document.getElementById('modalBookingBox');
        if (data.booking_id && data.booking_id > 0) {
            document.getElementById('modalBookingId').textContent = data.booking_id;
            bookingBox.classList.remove('d-none');
        } else {
            bookingBox.classList.add('d-none');
        }

        showInquiryModal(document.getElementById('viewInquiryModal'));
    } catch (e) {
        console.error('Failed to parse inquiry data:', e);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInquiryInput');
    const statusSelect = document.getElementById('filterStatusSelect');
    const rows = document.querySelectorAll('.inquiry-row');

    function filterInquiries() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const st = statusSelect?.value || 'all';

        rows.forEach(r => {
            const searchData = r.getAttribute('data-search') || '';
            const rowStatus = r.getAttribute('data-status') || '';

            const matchQ = !q || searchData.includes(q);
            const matchSt = st === 'all' || rowStatus === st;

            if (matchQ && matchSt) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterInquiries);
    if (statusSelect) statusSelect.addEventListener('change', filterInquiries);

    document.querySelectorAll('#viewInquiryModal [data-bs-dismiss="modal"], #deleteInquiryModal [data-bs-dismiss="modal"]').forEach(function (closeButton) {
        closeButton.addEventListener('click', function () {
            hideInquiryModal(closeButton.closest('.modal'));
        });
    });

    document.querySelectorAll('#viewInquiryModal, #deleteInquiryModal').forEach(function (modalElem) {
        modalElem.addEventListener('click', function (event) {
            if (event.target === modalElem) {
                hideInquiryModal(modalElem);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
