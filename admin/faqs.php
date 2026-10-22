<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();

// 1. AUTO-MIGRATE FAQS TABLE
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `faqs` (
            `faq_id` INT AUTO_INCREMENT PRIMARY KEY,
            `question` VARCHAR(255) NOT NULL,
            `answer` TEXT NOT NULL,
            `category` VARCHAR(100) DEFAULT 'General',
            `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
            `sort_order` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed initial default FAQs if empty
        $checkCount = $pdo->query("SELECT COUNT(*) FROM `faqs`")->fetchColumn();
        if ($checkCount == 0) {
            $initialFaqs = [
                [
                    'q' => 'How can I cancel my hotel booking?',
                    'a' => 'You can cancel your hotel reservation anytime by navigating to My Bookings on your dashboard. Locate the specific reservation and click the Cancel Booking button. Cancellations made 24 hours prior to check-in qualify for a 100% full refund.',
                    'cat' => 'Cancellation',
                    'order' => 1
                ],
                [
                    'q' => 'How can I modify my reservation?',
                    'a' => 'To modify check-in dates, guest counts, or room type preferences, submit a support request referencing your Booking ID or contact our 24/7 concierge front desk team directly.',
                    'cat' => 'Reservation',
                    'order' => 2
                ],
                [
                    'q' => 'Where can I see my booking details?',
                    'a' => 'All active, completed, and upcoming hotel bookings are listed under the My Bookings dashboard page. Click View Details on any booking card to inspect payment receipts and check-in timelines.',
                    'cat' => 'Reservation',
                    'order' => 3
                ],
                [
                    'q' => 'How do I request a refund?',
                    'a' => 'Refunds for eligible cancelled stays are automatically initiated immediately upon cancellation. Funds reflect in your account within 3 to 5 business days.',
                    'cat' => 'Billing',
                    'order' => 4
                ],
                [
                    'q' => 'How can I download my invoice?',
                    'a' => 'Digital tax invoices are available for instant download once your payment is confirmed. Go to My Bookings, open your reservation summary, and click Download Invoice (PDF).',
                    'cat' => 'Billing',
                    'order' => 5
                ],
                [
                    'q' => 'What should I do if I forgot my password?',
                    'a' => 'If you are logged in, update your security credentials under Settings -> Privacy & Security. If locked out, click "Forgot Password?" on the guest sign-in screen.',
                    'cat' => 'Account',
                    'order' => 6
                ],
                [
                    'q' => 'How can I contact the hotel?',
                    'a' => 'You can reach our 24/7 concierge front desk by calling our hotline or emailing concierge@hotelease.com. For instant message tracking, submit an online ticket.',
                    'cat' => 'General',
                    'order' => 7
                ],
                [
                    'q' => 'What should I do if I have a problem during my stay?',
                    'a' => 'For immediate room service, housekeeping, or technical assistance during your stay, dial 0 from your in-room telephone or send a priority ticket via the mobile concierge portal.',
                    'cat' => 'Support',
                    'order' => 8
                ]
            ];

            $stmtSeed = $pdo->prepare("INSERT INTO `faqs` (`question`, `answer`, `category`, `status`, `sort_order`) VALUES (:q, :a, :cat, 'Active', :ord)");
            foreach ($initialFaqs as $f) {
                $stmtSeed->execute(['q' => $f['q'], 'a' => $f['a'], 'cat' => $f['cat'], 'ord' => $f['order']]);
            }
        }
    } catch (Exception $e) {
        // Table creation or seed fallback
    }
}

// 2. HANDLE POST ACTIONS (ADD, EDIT, DELETE, QUICK STATUS TOGGLE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $faq_id = intval($_POST['faq_id'] ?? 0);

    // ADD FAQ
    if ($action === 'add_faq') {
        $question = sanitize($_POST['question'] ?? '');
        $answer = sanitize($_POST['answer'] ?? '');
        $category = sanitize($_POST['category'] ?? 'General');
        $status = sanitize($_POST['status'] ?? 'Active');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if (!empty($question) && !empty($answer)) {
            $stmtAdd = $pdo->prepare("INSERT INTO `faqs` (`question`, `answer`, `category`, `status`, `sort_order`) VALUES (:q, :a, :cat, :st, :ord)");
            $stmtAdd->execute([
                'q' => $question,
                'a' => $answer,
                'cat' => $category,
                'st' => $status,
                'ord' => $sort_order
            ]);
            set_toast('✓ FAQ question added successfully!', 'success');
        } else {
            set_toast('Question and Answer fields are required.', 'danger');
        }
        redirect('/hotel_website/admin/faqs.php');
    }

    // EDIT FAQ
    elseif ($action === 'edit_faq' && $faq_id > 0) {
        $question = sanitize($_POST['question'] ?? '');
        $answer = sanitize($_POST['answer'] ?? '');
        $category = sanitize($_POST['category'] ?? 'General');
        $status = sanitize($_POST['status'] ?? 'Active');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if (!empty($question) && !empty($answer)) {
            $stmtEdit = $pdo->prepare("UPDATE `faqs` SET `question` = :q, `answer` = :a, `category` = :cat, `status` = :st, `sort_order` = :ord WHERE `faq_id` = :id");
            $stmtEdit->execute([
                'q' => $question,
                'a' => $answer,
                'cat' => $category,
                'st' => $status,
                'ord' => $sort_order,
                'id' => $faq_id
            ]);
            set_toast('✓ FAQ updated successfully!', 'success');
        } else {
            set_toast('Question and Answer fields cannot be empty.', 'danger');
        }
        redirect('/hotel_website/admin/faqs.php');
    }

    // DELETE FAQ
    elseif ($action === 'delete_faq' && $faq_id > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM `faqs` WHERE `faq_id` = :id");
        $stmtDel->execute(['id' => $faq_id]);
        set_toast('✓ FAQ question deleted from database!', 'warning');
        redirect('/hotel_website/admin/faqs.php');
    }

    // QUICK STATUS UPDATE
    elseif ($action === 'quick_status' && $faq_id > 0) {
        $status = sanitize($_POST['status'] ?? 'Active');
        $stmtStatus = $pdo->prepare("UPDATE `faqs` SET `status` = :st WHERE `faq_id` = :id");
        $stmtStatus->execute(['st' => $status, 'id' => $faq_id]);
        set_toast('✓ FAQ status updated to ' . strtoupper($status), 'info');
        redirect('/hotel_website/admin/faqs.php');
    }
}

// FETCH ALL FAQS FROM DATABASE
$faqs = [];
$totalFaqs = 0;
$activeFaqs = 0;
$inactiveFaqs = 0;
$categories = [];

if ($pdo) {
    $stmtAll = $pdo->query("SELECT * FROM `faqs` ORDER BY `sort_order` ASC, `faq_id` ASC");
    $faqs = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    $totalFaqs = count($faqs);
    foreach ($faqs as $f) {
        if ($f['status'] === 'Active') $activeFaqs++;
        else $inactiveFaqs++;

        if (!empty($f['category']) && !in_array($f['category'], $categories)) {
            $categories[] = $f['category'];
        }
    }
}

include __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">

<?php 
$page_title = 'Frequently Asked Questions';
include __DIR__ . '/includes/navbar.php'; 
?>

<link rel="stylesheet" href="../assets/css/admin-faqs.css?v=20261009">

<style>
.modal { z-index: 1085 !important; }
.modal-backdrop { z-index: 1080 !important; }
.faq-question-text { font-weight: 700; color: #0F172A; }
.faq-answer-preview { color: #64748B; font-size: 0.85rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.kpi-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1.25rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
.badge-cat { background: #EFF6FF; color: #2563EB; font-weight: 600; border: 1px solid #BFDBFE; padding: 0.35rem 0.65rem; border-radius: 8px; }
</style>

<div class="container-fluid px-4 py-4 faq-page">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 faq-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 extra-small text-muted font-bold text-uppercase mb-1">
                <a href="/hotel_website/admin/dashboard.php" class="text-muted text-decoration-none">Dashboard</a> / 
                <a href="/hotel_website/admin/faqs.php" class="text-primary text-decoration-none">FAQs</a>
            </div>
            <h3 class="font-serif-title fw-bold text-navy mb-0">
                <i class="fa-solid fa-circle-question text-primary me-2"></i>Frequently Asked Questions
            </h3>
            <p class="small text-muted mb-0">Manage website guest FAQs, create new support questions, and toggle visibility in real-time.</p>
        </div>

        <button type="button" class="btn btn-primary rounded-3 font-semibold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFaqModal" onclick="openAddFaqModal()">
            <i class="fa-solid fa-plus me-1.5"></i> Add New FAQ
        </button>
    </div>

    <!-- KPI STATS CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card faq-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="extra-small text-muted font-bold text-uppercase">Total FAQs</span>
                    <div class="badge bg-primary-subtle text-primary rounded-circle p-2"><i class="fa-solid fa-list-check"></i></div>
                </div>
                <h2 class="fw-bold text-navy mb-0"><?php echo $totalFaqs; ?></h2>
                <div class="extra-small text-muted mt-1">Database questions</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card faq-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="extra-small text-muted font-bold text-uppercase">Active FAQs</span>
                    <div class="badge bg-success-subtle text-success rounded-circle p-2"><i class="fa-solid fa-eye"></i></div>
                </div>
                <h2 class="fw-bold text-success mb-0"><?php echo $activeFaqs; ?></h2>
                <div class="extra-small text-muted mt-1">Visible to website guests</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card faq-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="extra-small text-muted font-bold text-uppercase">Inactive FAQs</span>
                    <div class="badge bg-secondary-subtle text-secondary rounded-circle p-2"><i class="fa-solid fa-eye-slash"></i></div>
                </div>
                <h2 class="fw-bold text-secondary mb-0"><?php echo $inactiveFaqs; ?></h2>
                <div class="extra-small text-muted mt-1">Hidden from public</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card faq-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="extra-small text-muted font-bold text-uppercase">Categories</span>
                    <div class="badge bg-info-subtle text-info rounded-circle p-2"><i class="fa-solid fa-folder-tree"></i></div>
                </div>
                <h2 class="fw-bold text-navy mb-0"><?php echo count($categories); ?></h2>
                <div class="extra-small text-muted mt-1">FAQ Topic Categories</div>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH CONTROLS -->
    <div class="card border rounded-4 mb-4 shadow-sm bg-white faq-filter-card">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="faqSearchInput" class="form-control border-start-0" placeholder="Search FAQ questions or answers...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="faqCategoryFilter" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-end ms-auto">
                <button type="button" class="btn btn-outline-secondary rounded-3 font-semibold px-3" onclick="resetFaqFilters()" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- 1. ACCORDION CARDS VIEW (PRIMARY ADMIN LAYOUT VIEW) -->
    <div id="faqAccordionView" class="faq-accordion-container mb-4">
        <?php if (!empty($faqs)): ?>
            <div class="accordion custom-faq-accordion" id="adminFaqAccordion">
                <?php foreach ($faqs as $index => $faq): 
                    $faq_json = htmlspecialchars(json_encode($faq, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                    $accId = "adminFaqCollapse_" . $faq['faq_id'];
                    $headingId = "adminFaqHeading_" . $faq['faq_id'];
                ?>
                    <div class="accordion-item faq-accordion-card mb-3 border rounded-4 shadow-sm bg-white overflow-hidden faq-item-row" data-category="<?php echo htmlspecialchars($faq['category']); ?>">
                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                            <button class="accordion-button collapsed px-4 py-3.5 bg-white text-navy font-semibold fs-6 border-0 shadow-none d-flex align-items-center justify-content-between" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#<?php echo $accId; ?>" 
                                    aria-expanded="false" 
                                    aria-controls="<?php echo $accId; ?>">
                                <span class="d-flex align-items-center flex-wrap gap-2.5 me-auto">
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 extra-small font-bold">#FAQ-<?php echo $faq['faq_id']; ?></span>
                                    <span class="badge bg-light text-navy border rounded-pill px-2.5 py-1 extra-small font-semibold"><?php echo htmlspecialchars($faq['category']); ?></span>
                                    <span class="fw-bold text-navy"><?php echo htmlspecialchars($faq['question']); ?></span>
                                </span>
                            </button>
                        </h2>
                        <div id="<?php echo $accId; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#adminFaqAccordion">
                            <div class="accordion-body px-4 py-3 bg-light bg-opacity-50 border-top">
                                <div class="text-secondary font-medium mb-3 fs-6" style="line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2 border-top border-secondary border-opacity-10">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="extra-small text-muted font-bold text-uppercase me-1">STATUS:</span>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="quick_status">
                                            <input type="hidden" name="faq_id" value="<?php echo $faq['faq_id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="form-select form-select-sm font-semibold rounded-3 faq-status-select <?php echo $faq['status'] === 'Active' ? 'bg-success-subtle text-success border-success' : 'bg-secondary-subtle text-secondary border-secondary'; ?>">
                                                <option value="Active" <?php echo $faq['status'] === 'Active' ? 'selected' : ''; ?>>🟢 Active</option>
                                                <option value="Inactive" <?php echo $faq['status'] === 'Inactive' ? 'selected' : ''; ?>>🔴 Inactive</option>
                                            </select>
                                        </form>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-3 font-semibold px-3 py-1.5 shadow-2xs" onclick="event.stopPropagation(); openViewFaqModal(<?php echo $faq_json; ?>)">
                                            <i class="fa-solid fa-eye me-1.5"></i> View
                                        </button>
                                        <!-- On Click Edit -->
                                        <button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold px-3.5 py-1.5 shadow-2xs" onclick="openEditFaqModal(<?php echo $faq_json; ?>)">
                                            <i class="fa-solid fa-pen-to-square me-1.5"></i> Edit FAQ
                                        </button>
                                        <!-- On Click Delete -->
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 font-semibold px-3 py-1.5 shadow-2xs" data-id="<?php echo $faq['faq_id']; ?>" data-question="<?php echo htmlspecialchars($faq['question']); ?>" onclick="openDeleteFaqModal(this)">
                                            <i class="fa-solid fa-trash-can me-1.5"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card border rounded-4 p-5 text-center text-muted bg-white shadow-sm">
                <i class="fa-solid fa-circle-question fs-1 mb-3 text-secondary"></i>
                <h5 class="fw-bold text-navy">No FAQs Found</h5>
                <p class="small text-muted mb-3">There are no FAQ items registered in the database.</p>
                <div>
                    <button type="button" class="btn btn-primary rounded-3 px-4 font-semibold" onclick="openAddFaqModal()">
                        <i class="fa-solid fa-plus me-1.5"></i> Add First FAQ
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

</main>

<!-- 1. ADD NEW FAQ MODAL -->
<div class="modal fade" id="addFaqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-circle-plus text-primary me-2"></i>Add New FAQ Question
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_faq">
                <div class="modal-body space-y-3 pt-3">
                    <div>
                        <label class="form-label">QUESTION *</label>
                        <input type="text" name="question" required class="form-control" placeholder="e.g. How can I request an early check-in?">
                    </div>
                    <div>
                        <label class="form-label">ANSWER *</label>
                        <textarea name="answer" rows="4" required class="form-control" placeholder="Provide a detailed, helpful answer for website guests..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">CATEGORY</label>
                            <input type="text" name="category" class="form-control" value="General" placeholder="e.g. Reservation, Billing, Support">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">STATUS</label>
                            <select name="status" class="form-select font-semibold">
                                <option value="Active" selected>🟢 Active (Visible)</option>
                                <option value="Inactive">🔴 Inactive (Hidden)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SORT ORDER</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3">
                    <button type="button" class="btn btn-light rounded-3 font-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4"><i class="fa-solid fa-floppy-disk me-1.5"></i> Save FAQ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. EDIT FAQ MODAL -->
<div class="modal fade faq-edit-modal" id="editFaqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit FAQ Question
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_faq">
                <input type="hidden" name="faq_id" id="edit_faq_id">
                <div class="modal-body faq-edit-modal-body">
                    <div>
                        <label class="form-label faq-modal-label">QUESTION *</label>
                        <input type="text" name="question" id="edit_question" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label faq-modal-label">ANSWER *</label>
                        <textarea name="answer" id="edit_answer" rows="5" required class="form-control"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label faq-modal-label">CATEGORY</label>
                            <input type="text" name="category" id="edit_category" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label faq-modal-label">STATUS</label>
                            <select name="status" id="edit_status" class="form-select font-semibold">
                                <option value="Active">🟢 Active (Visible)</option>
                                <option value="Inactive">🔴 Inactive (Hidden)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label faq-modal-label">SORT ORDER</label>
                            <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer faq-edit-modal-footer">
                    <button type="button" class="btn btn-light rounded-3 font-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4"><i class="fa-solid fa-floppy-disk me-1.5"></i> Update FAQ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. VIEW FAQ DETAILS MODAL -->
<div class="modal fade" id="viewFaqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-circle-question text-info me-2"></i>FAQ Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body space-y-3 pt-3">
                <div>
                    <span class="badge badge-cat mb-2" id="view_category">General</span>
                    <h5 class="fw-bold text-navy" id="view_question">Question</h5>
                </div>
                <hr>
                <div>
                    <label class="form-label text-muted">ANSWER:</label>
                    <p class="text-secondary leading-relaxed small" id="view_answer" style="white-space: pre-line;"></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- 4. ANIMATED DELETE FAQ MODAL -->
<div class="modal fade" id="deleteFaqModal" tabindex="-1" aria-labelledby="deleteFaqModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deleteFaqModalTitle">Delete FAQ Question?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently delete this FAQ entry?</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-circle-question me-2 text-danger"></i>
                    <span id="delete_faq_question">FAQ Question</span>
                </div>

                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger extra-small rounded-3 text-start mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Warning: This action will remove the question from the database and public support center.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_faq">
                    <input type="hidden" name="faq_id" id="delete_faq_id">

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
function toggleAdminFaq(button) {
    const targetId = button.getAttribute('data-bs-target');
    const target = targetId ? document.querySelector(targetId) : null;
    if (!target) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
        const bsCollapse = bootstrap.Collapse.getInstance(target) || new bootstrap.Collapse(target, { toggle: false });
        bsCollapse.toggle();
    } else {
        const shouldOpen = !target.classList.contains('show');
        document.querySelectorAll('#adminFaqAccordion .accordion-collapse').forEach(collapse => {
            collapse.classList.remove('show');
            collapse.style.display = '';
        });
        document.querySelectorAll('#adminFaqAccordion .accordion-button').forEach(itemButton => {
            itemButton.classList.add('collapsed');
            itemButton.setAttribute('aria-expanded', 'false');
        });

        if (shouldOpen) {
            target.classList.add('show');
            button.classList.remove('collapsed');
            button.setAttribute('aria-expanded', 'true');
        }
    }
}

function showFaqModal(modalElem) {
    if (!modalElem) return;
    document.querySelectorAll('.modal.faq-modal-open').forEach(openModal => {
        if (openModal !== modalElem) closeFaqModal(openModal);
    });

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElem).show();
        return;
    }

    modalElem.classList.add('show', 'faq-modal-open');
    modalElem.style.display = 'block';
    modalElem.removeAttribute('aria-hidden');
    modalElem.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');

    if (!document.querySelector('.faq-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show faq-modal-backdrop';
        backdrop.addEventListener('click', () => closeFaqModal(modalElem));
        document.body.appendChild(backdrop);
    }
}

function closeFaqModal(modalElem) {
    if (!modalElem) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(modalElem);
        if (instance) instance.hide();
    }
    modalElem.classList.remove('show', 'faq-modal-open');
    modalElem.style.display = 'none';
    modalElem.setAttribute('aria-hidden', 'true');
    modalElem.removeAttribute('aria-modal');
    document.querySelectorAll('.faq-modal-backdrop').forEach(backdrop => backdrop.remove());
    if (!document.querySelector('.modal.show, .faq-modal-open')) {
        document.body.classList.remove('modal-open');
    }
}

function openViewFaqModal(faq) {
    document.getElementById('view_question').textContent = faq.question;
    document.getElementById('view_answer').textContent = faq.answer;
    document.getElementById('view_category').textContent = faq.category || 'General';
    showFaqModal(document.getElementById('viewFaqModal'));
}

function openEditFaqModal(faq) {
    document.getElementById('edit_faq_id').value = faq.faq_id;
    document.getElementById('edit_question').value = faq.question;
    document.getElementById('edit_answer').value = faq.answer;
    document.getElementById('edit_category').value = faq.category || 'General';
    document.getElementById('edit_status').value = faq.status || 'Active';
    document.getElementById('edit_sort_order').value = faq.sort_order || 0;
    showFaqModal(document.getElementById('editFaqModal'));
}

function openDeleteFaqModal(id, questionText) {
    if (typeof id === 'object' && id !== null) {
        const elem = id;
        id = elem.getAttribute('data-id') || elem.closest('[data-id]')?.getAttribute('data-id');
        questionText = elem.getAttribute('data-question') || elem.closest('[data-question]')?.getAttribute('data-question');
    }
    document.getElementById('delete_faq_id').value = id;
    document.getElementById('delete_faq_question').textContent = questionText || 'FAQ Question';
    showFaqModal(document.getElementById('deleteFaqModal'));
}

function openAddFaqModal() {
    showFaqModal(document.getElementById('addFaqModal'));
}

function switchFaqView(viewMode) {
    const accView = document.getElementById('faqAccordionView');
    const tblView = document.getElementById('faqTableViewContainer');
    const btnAcc = document.getElementById('btnViewAccordion');
    const btnTbl = document.getElementById('btnViewTable');

    if (viewMode === 'table') {
        if (accView) accView.style.display = 'none';
        if (tblView) tblView.style.display = 'block';
        if (btnAcc) { btnAcc.classList.remove('btn-primary', 'active'); btnAcc.classList.add('btn-outline-secondary'); }
        if (btnTbl) { btnTbl.classList.remove('btn-outline-secondary'); btnTbl.classList.add('btn-primary', 'active'); }
    } else {
        if (accView) accView.style.display = 'block';
        if (tblView) tblView.style.display = 'none';
        if (btnAcc) { btnAcc.classList.remove('btn-outline-secondary'); btnAcc.classList.add('btn-primary', 'active'); }
        if (btnTbl) { btnTbl.classList.remove('btn-primary', 'active'); btnTbl.classList.add('btn-outline-secondary'); }
    }
}

// Client-side Live Search & Category Filtering
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('faqSearchInput');
    const categoryFilter = document.getElementById('faqCategoryFilter');

    document.querySelectorAll('#adminFaqAccordion .accordion-header').forEach(header => {
        header.addEventListener('click', function (event) {
            if (event.target.closest('a, button:not(.accordion-button), select, input, textarea, form')) return;
            const button = this.querySelector('.accordion-button');
            if (button) {
                event.preventDefault();
                toggleAdminFaq(button);
            }
        });
    });

    function filterTable() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const cat = categoryFilter?.value || '';

        const allItems = document.querySelectorAll('.faq-row, .faq-item-row');
        allItems.forEach(row => {
            const text = row.textContent.toLowerCase();
            const rowCat = row.getAttribute('data-category') || '';

            const matchQuery = query === '' || text.includes(query);
            const matchCat = cat === '' || rowCat === cat;

            if (matchQuery && matchCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (categoryFilter) categoryFilter.addEventListener('change', filterTable);
});

document.addEventListener('click', function (event) {
    const button = event.target.closest('button, a');
    if (!button) return;
    const text = (button.textContent || '').trim().toLowerCase();
    if (!button.hasAttribute('data-bs-dismiss') &&
        !button.classList.contains('btn-close') &&
        text !== 'cancel' && text !== 'close') {
        return;
    }
    const modal = button.closest('.modal');
    if (modal) closeFaqModal(modal);
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    const modal = document.querySelector('.modal.faq-modal-open, .modal.show');
    if (modal) closeFaqModal(modal);
});

function resetFaqFilters() {
    const searchInput = document.getElementById('faqSearchInput');
    const categoryFilter = document.getElementById('faqCategoryFilter');
    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = '';
    const allItems = document.querySelectorAll('.faq-row, .faq-item-row');
    allItems.forEach(row => row.style.display = '');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
