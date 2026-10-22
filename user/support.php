<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();

// Ensure DB columns exist on contact_messages table
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

$user = get_logged_user();
$userId = $_SESSION['user_id'] ?? ($user['customer_id'] ?? 0);

// Fetch user's bookings for the form dropdown
$userBookings = [];
if ($pdo && $userId) {
    $stmtB = $pdo->prepare("SELECT b.booking_id, b.room_id, r.room_no, rt.type_name, b.check_in, b.check_out FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE b.customer_id = :uid ORDER BY b.booking_id DESC");
    $stmtB->execute(['uid' => $userId]);
    $userBookings = $stmtB->fetchAll();
}

// Fetch active FAQs from database
$dbFaqs = [];
if ($pdo) {
    try {
        $stmtF = $pdo->query("SELECT * FROM `faqs` WHERE `status` = 'Active' ORDER BY `sort_order` ASC, `faq_id` ASC");
        $dbFaqs = $stmtF->fetchAll();
    } catch (Exception $e) {}
}

// Handle Support Request Submission
$requestSubmitted = false;
$submissionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_support_request') {
    $category = sanitize($_POST['category'] ?? 'General Inquiry');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;

    $uName = $user['name'] ?? ($_SESSION['user_name'] ?? 'Registered Guest');
    $uEmail = $user['email'] ?? ($_SESSION['user_email'] ?? '');
    $uPhone = $user['mobile'] ?? '';

    if (!empty($subject) && !empty($message)) {
        try {
            $stmtIns = $pdo->prepare("INSERT INTO `contact_messages` (`customer_id`, `booking_id`, `category`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES (:cid, :bid, :cat, :name, :email, :phone, :subject, :msg, 'Unread', NOW())");
            $stmtIns->execute([
                'cid' => $userId,
                'bid' => $bookingId,
                'cat' => $category,
                'name' => $uName,
                'email' => $uEmail,
                'phone' => $uPhone,
                'subject' => $subject,
                'msg' => $message
            ]);

            // Notify Admin
            $stmtNotif = $pdo->prepare("INSERT INTO `notifications` (`user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES ('Admin', 0, 'New Support Ticket', :notifMsg, 0, NOW())");
            $stmtNotif->execute([
                'notifMsg' => "Support ticket from " . $uName . " (" . $category . ": " . $subject . ")"
            ]);

            $requestSubmitted = true;
            set_toast('✓ Support request submitted successfully.', 'success');
        } catch (Exception $e) {
            $submissionError = 'Failed to submit support request: ' . $e->getMessage();
            set_toast($submissionError, 'danger');
        }
    } else {
        $submissionError = 'Please fill out all required fields.';
        set_toast($submissionError, 'danger');
    }
}

// Fetch Logged-in User's Previous Support Requests
$userTickets = [];
if ($pdo && $userId) {
    $stmtT = $pdo->prepare("SELECT * FROM `contact_messages` WHERE `customer_id` = :uid ORDER BY `message_id` DESC");
    $stmtT->execute(['uid' => $userId]);
    $userTickets = $stmtT->fetchAll();
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content">
<?php
$page_title = 'Help & Support';
include __DIR__ . '/includes/navbar.php';
?>

<style>
/* =========================================================
   HOTEL EASE - LUXURY CUSTOMER SUPPORT DASHBOARD CSS
   ========================================================= */
.support-hero-banner {
    background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 50%, #1D4ED8 100%);
    border-radius: 20px;
    box-shadow: 0 12px 32px rgba(37, 99, 235, 0.2);
    color: #FFFFFF;
    position: relative;
    overflow: hidden;
}
.support-hero-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 380px;
    height: 380px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
    pointer-events: none;
}

.search-input-box {
    background: #FFFFFF;
    border: 2px solid transparent;
    border-radius: 50px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    transition: all 0.25s ease;
}
.search-input-box:focus-within {
    border-color: #60A5FA;
    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.25);
}

.quick-help-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    text-decoration: none;
    color: inherit;
    display: block;
}
.quick-help-card:hover {
    transform: translateY(-5px);
    border-color: #2563EB;
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.12);
}
.quick-help-card .icon-box {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    transition: all 0.3s ease;
}
.quick-help-card:hover .icon-box {
    background: #2563EB;
    color: #FFFFFF;
    transform: scale(1.05);
}
.quick-help-card .arrow-icon {
    color: #94A3B8;
    transition: all 0.25s ease;
}
.quick-help-card:hover .arrow-icon {
    color: #2563EB;
    transform: translateX(4px);
}

/* FAQ Accordion Styling */
.faq-accordion .accordion-item {
    border: 1px solid #E2E8F0;
    border-radius: 14px !important;
    margin-bottom: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
    transition: all 0.25s ease;
}
.faq-accordion .accordion-item:hover {
    border-color: #CBD5E1;
}
.faq-accordion .accordion-button {
    font-weight: 700;
    color: #0F172A;
    background: #FFFFFF;
    padding: 1.1rem 1.4rem;
    box-shadow: none !important;
    font-size: 0.98rem;
    cursor: pointer;
    text-align: left;
}
.faq-accordion .accordion-button:not(.collapsed) {
    background: #EFF6FF;
    color: #1D4ED8;
    border-bottom: 1px solid #DBEAFE;
}
.faq-accordion .accordion-button::after {
    display: none;
}
.faq-icon-toggle {
    font-size: 14px;
    transition: transform 0.25s ease, color 0.25s ease;
}
.faq-accordion .accordion-button:not(.collapsed) .faq-icon-toggle {
    transform: rotate(45deg);
    color: #1D4ED8 !important;
}

.contact-method-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 1.4rem;
    transition: all 0.25s ease;
    text-decoration: none;
    color: inherit;
}
.contact-method-card:hover {
    border-color: #2563EB;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.1);
}

/* Ticket Timeline Indicator */
.ticket-timeline {
    position: relative;
    padding-left: 2rem;
}
.ticket-timeline::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 10px;
    bottom: 10px;
    width: 2px;
    background: #E2E8F0;
}
.timeline-step {
    position: relative;
    margin-bottom: 1.5rem;
}
.timeline-step:last-child {
    margin-bottom: 0;
}
.timeline-icon-dot {
    position: absolute;
    left: -2rem;
    top: 2px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #E2E8F0;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    border: 2px solid #FFFFFF;
    z-index: 2;
}
.timeline-step.completed .timeline-icon-dot {
    background: #16A34A;
    color: #FFFFFF;
}
.timeline-step.active .timeline-icon-dot {
    background: #2563EB;
    color: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
}

/* Dark Theme Overrides */
[data-theme="dark"] .quick-help-card,
[data-bs-theme="dark"] .quick-help-card,
body.dark-theme .quick-help-card,
[data-theme="dark"] .contact-method-card,
[data-bs-theme="dark"] .contact-method-card,
body.dark-theme .contact-method-card {
    background: #1E293B !important;
    border-color: #334155 !important;
    color: #F8FAFC !important;
}
[data-theme="dark"] .quick-help-card .icon-box,
[data-bs-theme="dark"] .quick-help-card .icon-box,
body.dark-theme .quick-help-card .icon-box {
    background: #0F172A !important;
    color: #38BDF8 !important;
}
[data-theme="dark"] .quick-help-card:hover,
[data-bs-theme="dark"] .quick-help-card:hover,
body.dark-theme .quick-help-card:hover {
    border-color: #3B82F6 !important;
}

[data-theme="dark"] .faq-accordion .accordion-item,
[data-bs-theme="dark"] .faq-accordion .accordion-item,
body.dark-theme .faq-accordion .accordion-item {
    background: #1E293B !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .faq-accordion .accordion-button,
[data-bs-theme="dark"] .faq-accordion .accordion-button,
body.dark-theme .faq-accordion .accordion-button {
    background: #1E293B !important;
    color: #F8FAFC !important;
}
[data-theme="dark"] .faq-accordion .accordion-button:not(.collapsed),
[data-bs-theme="dark"] .faq-accordion .accordion-button:not(.collapsed),
body.dark-theme .faq-accordion .accordion-button:not(.collapsed) {
    background: #0F172A !important;
    color: #60A5FA !important;
    border-bottom-color: #334155 !important;
}
[data-theme="dark"] .faq-accordion .accordion-body,
[data-bs-theme="dark"] .faq-accordion .accordion-body,
body.dark-theme .faq-accordion .accordion-body {
    background: #1E293B !important;
    color: #CBD5E1 !important;
}
[data-theme="dark"] .ticket-timeline::before,
[data-bs-theme="dark"] .ticket-timeline::before,
body.dark-theme .ticket-timeline::before {
    background: #334155 !important;
}
[data-theme="dark"] .timeline-icon-dot,
[data-bs-theme="dark"] .timeline-icon-dot,
body.dark-theme .timeline-icon-dot {
    border-color: #1E293B !important;
}
</style>

<div class="container-fluid px-4 py-3">

    <!-- =========================================================
         1. PAGE HEADER & BREADCRUMB (Item #3)
       ========================================================= -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-1 extra-small text-muted font-semibold">
                    <li class="breadcrumb-item"><a href="/hotel_website/user/dashboard.php" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Support</li>
                </ol>
            </nav>
            <h3 class="font-serif-title fw-bold text-navy mb-0">Support Center</h3>
            <p class="small text-muted mb-0">We're here to help with your HotelEase experience 24 hours a day, 7 days a week.</p>
        </div>
        <div>
            <a href="#support-form-section" class="btn btn-primary rounded-pill px-4 py-2 font-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-headset"></i>
                <span>Contact Support</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($requestSubmitted): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 p-3.5 shadow-sm border border-success border-opacity-25" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="fa-solid fa-circle-check fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0.5 text-success">Support Request Submitted Successfully!</h6>
                    <span class="small text-muted">Ticket reference saved to your account. Our concierge team is reviewing your request.</span>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- =========================================================
         2. HERO SUPPORT CARD (Item #4)
       ========================================================= -->
    <div class="support-hero-banner p-4 p-md-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary uppercase tracking-wider px-3 py-1.5 rounded-pill font-bold mb-2.5 extra-small shadow-sm">
                    <i class="fa-solid fa-shield-heart me-1"></i> HELP &amp; SUPPORT
                </span>
                <h2 class="font-serif-title fw-bold fs-1 text-white mb-2">Need Help?</h2>
                <p class="text-white-50 font-medium mb-4 fs-6">Find instant answers, manage your reservations, or get in touch with our dedicated support team.</p>

                <!-- Search Input Field -->
                <div class="search-input-box p-1.5 d-flex align-items-center gap-2 max-w-lg">
                    <i class="fa-solid fa-magnifying-glass text-primary ms-3 fs-5"></i>
                    <input type="text" id="helpSearchInput" class="form-control border-0 shadow-none bg-transparent text-dark font-medium py-2" placeholder="Search bookings, payments, cancellations, rooms..." style="font-size: 0.95rem;">
                    <button type="button" id="helpSearchBtn" class="btn btn-primary rounded-pill px-4 py-2 font-bold shadow-xs d-flex align-items-center gap-1.5 flex-shrink-0">
                        <span>Search</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-end">
                <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-20 text-center max-w-xs shadow-lg">
                    <div class="bg-white text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-md" style="width: 72px; height: 72px;">
                        <i class="fa-solid fa-headset fs-2 text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-white font-serif-title mb-1">24/7 Concierge Care</h5>
                    <p class="extra-small text-white-50 mb-0">Our support specialists are standing by to ensure your stay is seamless.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         3. QUICK HELP CARDS (Item #5)
       ========================================================= -->
    <div class="mb-5">
        <h5 class="font-serif-title fw-bold text-navy mb-3">How can we help?</h5>
        <div class="row g-3">
            
            <div class="col-sm-6 col-xl-3">
                <a href="#faq-section" class="quick-help-card" onclick="filterFaqByCategory('booking')">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <i class="fa-solid fa-arrow-right arrow-icon fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Booking Help</h6>
                    <p class="extra-small text-muted mb-0">Manage reservations, cancellations and booking details.</p>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <a href="#faq-section" class="quick-help-card" onclick="filterFaqByCategory('payment')">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <i class="fa-solid fa-arrow-right arrow-icon fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Payment &amp; Billing</h6>
                    <p class="extra-small text-muted mb-0">Get help with payments, refunds and invoices.</p>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <a href="#faq-section" class="quick-help-card" onclick="filterFaqByCategory('hotel')">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box">
                            <i class="fa-solid fa-hotel"></i>
                        </div>
                        <i class="fa-solid fa-arrow-right arrow-icon fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Hotel &amp; Rooms</h6>
                    <p class="extra-small text-muted mb-0">Questions about rooms, facilities and hotel policies.</p>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <a href="#faq-section" class="quick-help-card" onclick="filterFaqByCategory('account')">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <i class="fa-solid fa-arrow-right arrow-icon fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Account &amp; Security</h6>
                    <p class="extra-small text-muted mb-0">Manage your account, password and security.</p>
                </a>
            </div>

        </div>
    </div>

    <!-- =========================================================
         4. FAQ ACCORDION SECTION (Item #6 & #15)
       ========================================================= -->
    <div class="mb-5" id="faq-section">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="font-serif-title fw-bold text-navy mb-1">Frequently Asked Questions</h5>
                <p class="small text-muted mb-0">Find quick answers to common queries regarding reservations, billing, and hotel stays.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 extra-small font-semibold d-none" id="resetFaqFilterBtn" onclick="resetFaqFilter()">
                <i class="fa-solid fa-rotate-left me-1"></i> Show All FAQs
            </button>
        </div>

        <div class="accordion faq-accordion" id="faqAccordion">
            <?php if (!empty($dbFaqs)): ?>
                <?php foreach ($dbFaqs as $index => $faq): ?>
                    <?php 
                        $faqIdStr = 'collapseFaq' . $faq['faq_id'];
                        $headIdStr = 'headingFaq' . $faq['faq_id'];
                        $keywords = strtolower($faq['question'] . ' ' . $faq['answer'] . ' ' . $faq['category']);
                    ?>
                    <div class="accordion-item faq-item" data-keywords="<?php echo htmlspecialchars($keywords); ?>">
                        <h2 class="accordion-header" id="<?php echo $headIdStr; ?>">
                            <button class="accordion-button collapsed d-flex justify-content-between align-items-center w-100" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $faqIdStr; ?>" aria-expanded="false" aria-controls="<?php echo $faqIdStr; ?>">
                                <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                <i class="fa-solid fa-plus faq-icon-toggle ms-auto text-primary"></i>
                            </button>
                        </h2>
                        <div id="<?php echo $faqIdStr; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headIdStr; ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-secondary lh-lg">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-4 text-center bg-white rounded-4 border">
                    <i class="fa-solid fa-circle-question text-muted fs-3 mb-2"></i>
                    <p class="mb-0 text-muted">No FAQs are currently available. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- No FAQ Results Found Message -->
        <div id="noFaqResults" class="p-4 text-center bg-white rounded-4 border d-none">
            <i class="fa-solid fa-magnifying-glass text-muted fs-3 mb-2"></i>
            <h6 class="fw-bold text-navy mb-1">No matching help topics found</h6>
            <p class="small text-muted mb-3">Try searching with different keywords or submit a direct ticket to our support team below.</p>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 font-semibold" onclick="resetFaqFilter()">Clear Search</button>
        </div>
    </div>

    <!-- =========================================================
         5. CONTACT SUPPORT CARD (Item #7)
       ========================================================= -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <div class="text-center max-w-xl mx-auto mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill extra-small font-bold mb-2">STILL NEED HELP?</span>
            <h4 class="font-serif-title fw-bold text-navy mb-1">Our support team is ready to assist you.</h4>
            <p class="small text-muted mb-0">Choose your preferred contact channel below for instant assistance.</p>
        </div>

        <div class="row g-4">
            <!-- Email Support Card -->
            <div class="col-md-4">
                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="contact-method-card h-100 d-flex flex-column align-items-center text-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-envelope fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Email Support</h6>
                    <p class="extra-small text-muted mb-2">Get response within 2-4 hours</p>
                    <span class="text-primary font-bold small text-break"><?php echo SITE_EMAIL; ?></span>
                </a>
            </div>

            <!-- Phone Support Card -->
            <div class="col-md-4">
                <a href="tel:<?php echo SITE_PHONE; ?>" class="contact-method-card h-100 d-flex flex-column align-items-center text-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-phone fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Phone Support</h6>
                    <p class="extra-small text-muted mb-2">Available 24 hours a day, 7 days a week</p>
                    <span class="text-success font-bold small"><?php echo SITE_PHONE; ?></span>
                </a>
            </div>

            <!-- Live Chat / Concierge Desk Card -->
            <div class="col-md-4">
                <a href="#support-form-section" class="contact-method-card h-100 d-flex flex-column align-items-center text-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-headset fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-navy mb-1 font-serif-title">Live Concierge Desk</h6>
                    <p class="extra-small text-muted mb-2">Chat with our support team</p>
                    <span class="text-info font-bold small">Submit Online Ticket &rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <!-- =========================================================
         6. SUPPORT REQUEST FORM (Item #8 & #9)
       ========================================================= -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5" id="support-form-section">
        <div class="border-bottom pb-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="font-serif-title fw-bold text-navy mb-1">
                    <i class="fa-solid fa-paper-plane text-primary me-2"></i> Submit a Support Request
                </h5>
                <p class="small text-muted mb-0">Fill out the details below and our concierge support specialists will review your inquiry.</p>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill small font-semibold">
                <i class="fa-solid fa-shield-check me-1"></i> Verified Account Ticket
            </span>
        </div>

        <form method="POST" action="" id="supportForm">
            <input type="hidden" name="action" value="submit_support_request">

            <div class="row g-3 mb-3">
                <!-- Full Name -->
                <div class="col-md-6">
                    <label class="form-label small font-bold text-secondary mb-1">Full Name *</label>
                    <input type="text" name="name" class="form-control rounded-3 font-semibold bg-light" value="<?php echo htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? '')); ?>" readonly>
                </div>

                <!-- Email Address -->
                <div class="col-md-6">
                    <label class="form-label small font-bold text-secondary mb-1">Email Address *</label>
                    <input type="email" name="email" class="form-control rounded-3 font-semibold bg-light" value="<?php echo htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? '')); ?>" readonly>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Booking ID (Optional Dropdown) -->
                <div class="col-md-6">
                    <label class="form-label small font-bold text-secondary mb-1">Related Reservation / Booking ID</label>
                    <select name="booking_id" class="form-select rounded-3 font-semibold">
                        <option value="">-- General Inquiry (Not related to specific booking) --</option>
                        <?php foreach ($userBookings as $bk): ?>
                            <option value="<?php echo $bk['booking_id']; ?>">
                                Booking #<?php echo $bk['booking_id']; ?> – Room <?php echo htmlspecialchars($bk['room_no']); ?> (<?php echo htmlspecialchars($bk['type_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Issue Category Dropdown -->
                <div class="col-md-6">
                    <label class="form-label small font-bold text-secondary mb-1">Issue Category *</label>
                    <select name="category" class="form-select rounded-3 font-semibold" required>
                        <option value="Booking Issue">Booking Issue</option>
                        <option value="Cancellation">Cancellation</option>
                        <option value="Payment Issue">Payment Issue</option>
                        <option value="Refund">Refund</option>
                        <option value="Hotel/Room Issue">Hotel / Room Issue</option>
                        <option value="Account Issue">Account Issue</option>
                        <option value="Technical Problem">Technical Problem</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <!-- Subject -->
            <div class="mb-3">
                <label class="form-label small font-bold text-secondary mb-1">Subject *</label>
                <input type="text" name="subject" class="form-control rounded-3 font-semibold" placeholder="Brief summary of your issue or request..." required>
            </div>

            <!-- Message Textarea -->
            <div class="mb-4">
                <label class="form-label small font-bold text-secondary mb-1">Message *</label>
                <textarea name="message" class="form-control rounded-3 font-semibold" rows="5" placeholder="Describe your problem in detail..." required></textarea>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2">
                <span class="extra-small text-muted">
                    <i class="fa-solid fa-lock me-1 text-success"></i> Your request is transmitted securely using 256-bit SSL encryption.
                </span>
                <button type="submit" id="submitSupportBtn" class="btn btn-primary rounded-pill px-5 py-2.5 font-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span id="submitBtnText">Submit Request</span>
                </button>
            </div>
        </form>
    </div>

    <!-- =========================================================
         7. MY SUPPORT REQUESTS (Item #10 & #11)
       ========================================================= -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="font-serif-title fw-bold text-navy mb-1">My Support Requests</h5>
                <p class="small text-muted mb-0">Track the status and responses for your submitted support tickets.</p>
            </div>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill extra-small font-semibold">
                Total Tickets: <?php echo count($userTickets); ?>
            </span>
        </div>

        <?php if (empty($userTickets)): ?>
            <!-- EMPTY STATE (Item #11) -->
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                    <i class="fa-solid fa-ticket-simple fs-2"></i>
                </div>
                <h5 class="font-serif-title fw-bold text-navy mb-1">No support requests yet</h5>
                <p class="small text-muted mb-4 max-w-sm mx-auto">You haven't submitted any support requests. If you have questions about your stay, create a ticket below.</p>
                <div>
                    <a href="#support-form-section" class="btn btn-outline-primary rounded-pill px-4 py-2 font-bold shadow-xs">
                        <i class="fa-solid fa-plus me-1.5"></i> Create Support Request
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- SUPPORT REQUESTS TABLE (Item #10) -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light extra-small text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-4 py-3 text-secondary">Ticket ID</th>
                                <th class="py-3 text-secondary">Subject</th>
                                <th class="py-3 text-secondary">Category</th>
                                <th class="py-3 text-secondary">Date</th>
                                <th class="py-3 text-secondary">Status</th>
                                <th class="pe-4 py-3 text-end text-secondary">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userTickets as $tkt): ?>
                                <?php
                                $status = $tkt['status'] ?? 'Unread';
                                $badgeClass = 'bg-warning text-dark border-warning';
                                $statusLabel = 'Open';

                                if ($status === 'Replied' || $status === 'Resolved') {
                                    $badgeClass = 'bg-success bg-opacity-10 text-success border-success border-opacity-25';
                                    $statusLabel = 'Resolved';
                                } elseif ($status === 'Closed') {
                                    $badgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25';
                                    $statusLabel = 'Closed';
                                } else {
                                    $badgeClass = 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25';
                                    $statusLabel = 'Open';
                                }

                                $ticketDataJson = htmlspecialchars(json_encode([
                                    'id' => $tkt['message_id'],
                                    'ticket_no' => '#TKT-' . (1000 + $tkt['message_id']),
                                    'subject' => $tkt['subject'] ?? 'General Inquiry',
                                    'category' => $tkt['category'] ?? 'General Inquiry',
                                    'date' => date('d M Y, h:i A', strtotime($tkt['created_at'])),
                                    'status' => $statusLabel,
                                    'raw_status' => $status,
                                    'message' => $tkt['message'] ?? '',
                                    'admin_reply' => $tkt['admin_reply'] ?? ''
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="ps-4 font-bold text-primary">#TKT-<?php echo (1000 + $tkt['message_id']); ?></td>
                                    <td class="font-semibold text-navy max-w-xs text-truncate">
                                        <?php echo htmlspecialchars($tkt['subject']); ?>
                                        <?php if (!empty($tkt['booking_id'])): ?>
                                            <span class="badge bg-light text-secondary border extra-small ms-1">Bk #<?php echo $tkt['booking_id']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill extra-small font-semibold">
                                            <?php echo htmlspecialchars($tkt['category'] ?? 'General Inquiry'); ?>
                                        </span>
                                    </td>
                                    <td class="extra-small text-muted font-medium"><?php echo date('d M Y, h:i A', strtotime($tkt['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?> px-3 py-1.5 rounded-pill extra-small font-semibold border">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 font-semibold extra-small" data-ticket='<?php echo $ticketDataJson; ?>' onclick="openTicketDetailsModal(this)">
                                            View Details &rarr;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
</main>

<!-- =========================================================
     8. SUPPORT TICKET DETAILS MODAL & TIMELINE (Item #12)
   ========================================================= -->
<div class="modal fade" id="ticketDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-white">
            <div class="modal-header border-bottom p-4">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill extra-small font-bold mb-1" id="modalTicketNo">#TKT-1001</span>
                    <h5 class="modal-title font-serif-title fw-bold text-navy" id="modalTicketSubject">Support Ticket Details</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4 bg-light p-3 rounded-4 border">
                    <div class="col-sm-4">
                        <span class="extra-small text-muted d-block font-semibold">Category</span>
                        <strong class="text-navy small font-bold" id="modalTicketCategory">Booking Issue</strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="extra-small text-muted d-block font-semibold">Date Submitted</span>
                        <strong class="text-navy small font-bold" id="modalTicketDate">27 Sep 2026</strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="extra-small text-muted d-block font-semibold">Current Status</span>
                        <span id="modalTicketStatusBadge" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill extra-small font-semibold">Resolved</span>
                    </div>
                </div>

                <!-- Ticket Status Visual Timeline Indicator -->
                <h6 class="font-serif-title fw-bold text-navy mb-3">Ticket Status Timeline</h6>
                <div class="ticket-timeline mb-4">
                    <!-- Step 1: Submitted -->
                    <div class="timeline-step completed">
                        <div class="timeline-icon-dot"><i class="fa-solid fa-check"></i></div>
                        <strong class="d-block text-navy small font-bold">Ticket Submitted</strong>
                        <span class="extra-small text-muted d-block">Your inquiry was logged and assigned a tracking reference ID.</span>
                    </div>

                    <!-- Step 2: Under Review -->
                    <div class="timeline-step" id="timelineStepReview">
                        <div class="timeline-icon-dot"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <strong class="d-block text-navy small font-bold">Support Team Reviewing</strong>
                        <span class="extra-small text-muted d-block">Concierge specialist is reviewing details and checking reservation logs.</span>
                    </div>

                    <!-- Step 3: Response / Resolved -->
                    <div class="timeline-step" id="timelineStepResolved">
                        <div class="timeline-icon-dot"><i class="fa-solid fa-circle-check"></i></div>
                        <strong class="d-block text-navy small font-bold">Response &amp; Resolution</strong>
                        <span class="extra-small text-muted d-block">Support response provided and inquiry marked as resolved.</span>
                    </div>
                </div>

                <!-- User Message -->
                <div class="mb-4">
                    <h6 class="font-serif-title fw-bold text-navy mb-2"><i class="fa-solid fa-user me-1.5 text-primary"></i> Your Message</h6>
                    <div class="p-3 bg-light rounded-4 border font-medium text-dark small" id="modalUserMessage">
                        --
                    </div>
                </div>

                <!-- Support Response -->
                <div>
                    <h6 class="font-serif-title fw-bold text-navy mb-2"><i class="fa-solid fa-headset me-1.5 text-success"></i> Support Response</h6>
                    <div class="p-3.5 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 font-medium text-navy small" id="modalSupportReply">
                        <em>Our support desk is currently processing your request. You will receive an email update as soon as a resolution is posted.</em>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-3 justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close Window</button>
            </div>
        </div>
    </div>
</div>

<script>
// Live Search for FAQs & Help Topics
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('helpSearchInput');
    const searchBtn = document.getElementById('helpSearchBtn');
    const faqItems = document.querySelectorAll('.faq-item');
    const noResults = document.getElementById('noFaqResults');
    const resetBtn = document.getElementById('resetFaqFilterBtn');

    function performFaqSearch() {
        const query = (searchInput.value || '').toLowerCase().trim();
        let visibleCount = 0;

        if (query.length > 0) {
            if (resetBtn) resetBtn.classList.remove('d-none');
        } else {
            if (resetBtn) resetBtn.classList.add('d-none');
        }

        faqItems.forEach(item => {
            const text = (item.textContent || '').toLowerCase();
            const keywords = (item.getAttribute('data-keywords') || '').toLowerCase();
            if (text.includes(query) || keywords.includes(query)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && query.length > 0) {
            if (noResults) noResults.classList.remove('d-none');
        } else {
            if (noResults) noResults.classList.add('d-none');
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', performFaqSearch);
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performFaqSearch();
                document.getElementById('faq-section')?.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            performFaqSearch();
            document.getElementById('faq-section')?.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Toggle Plus/Minus Icon in Accordion via Bootstrap collapse events
    const faqAccordionElem = document.getElementById('faqAccordion');
    if (faqAccordionElem) {
        faqAccordionElem.addEventListener('shown.bs.collapse', function (e) {
            const btn = e.target.previousElementSibling?.querySelector('.accordion-button');
            const icon = btn?.querySelector('.faq-icon-toggle');
            if (icon) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            }
        });
        faqAccordionElem.addEventListener('hidden.bs.collapse', function (e) {
            const btn = e.target.previousElementSibling?.querySelector('.accordion-button');
            const icon = btn?.querySelector('.faq-icon-toggle');
            if (icon) {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        });
    }

    // Form Loading State
    const supportForm = document.getElementById('supportForm');
    const submitBtn = document.getElementById('submitSupportBtn');
    const submitBtnText = document.getElementById('submitBtnText');

    if (supportForm && submitBtn) {
        supportForm.addEventListener('submit', function () {
            submitBtn.disabled = true;
            if (submitBtnText) submitBtnText.textContent = 'Submitting Request...';
        });
    }
});

function filterFaqByCategory(cat) {
    const input = document.getElementById('helpSearchInput');
    if (input) {
        input.value = cat;
        input.dispatchEvent(new Event('input'));
    }
    document.getElementById('faq-section')?.scrollIntoView({ behavior: 'smooth' });
}

function resetFaqFilter() {
    const input = document.getElementById('helpSearchInput');
    if (input) {
        input.value = '';
        input.dispatchEvent(new Event('input'));
    }
}

// Open Ticket Details Modal
function openTicketDetailsModal(btn) {
    const raw = btn.getAttribute('data-ticket');
    if (!raw) return;
    try {
        const data = JSON.parse(raw);
        document.getElementById('modalTicketNo').textContent = data.ticket_no;
        document.getElementById('modalTicketSubject').textContent = data.subject;
        document.getElementById('modalTicketCategory').textContent = data.category;
        document.getElementById('modalTicketDate').textContent = data.date;
        document.getElementById('modalUserMessage').textContent = data.message;

        const statusBadge = document.getElementById('modalTicketStatusBadge');
        statusBadge.textContent = data.status;

        const replyBox = document.getElementById('modalSupportReply');
        if (data.admin_reply && data.admin_reply.trim().length > 0) {
            replyBox.innerHTML = '<strong class="d-block mb-1 text-success font-bold"><i class="fa-solid fa-reply me-1"></i> Concierge Response:</strong>' + data.admin_reply.replace(/\n/g, '<br>');
            replyBox.className = 'p-3.5 bg-success bg-opacity-10 rounded-4 border border-success border-opacity-25 font-medium text-navy small';
        } else {
            replyBox.innerHTML = '<em>Our concierge support desk is currently reviewing your ticket details. An official response will be posted here and emailed to you shortly.</em>';
            replyBox.className = 'p-3.5 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 font-medium text-navy small';
        }

        const stepReview = document.getElementById('timelineStepReview');
        const stepResolved = document.getElementById('timelineStepResolved');

        if (data.raw_status === 'Replied' || data.raw_status === 'Resolved') {
            if (stepReview) stepReview.className = 'timeline-step completed';
            if (stepResolved) stepResolved.className = 'timeline-step completed';
        } else {
            if (stepReview) stepReview.className = 'timeline-step active';
            if (stepResolved) stepResolved.className = 'timeline-step';
        }

        const modal = new bootstrap.Modal(document.getElementById('ticketDetailsModal'));
        modal.show();
    } catch (e) {
        console.error('Failed to parse ticket JSON:', e);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
