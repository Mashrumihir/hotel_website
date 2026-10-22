<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = getDBConnection();

// Ensure profile_pic and status columns exist in DB
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `profile_pic` VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {
        // column already exists
    }
    try {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `status` VARCHAR(20) DEFAULT 'Active'");
    } catch (Exception $e) {
        // column already exists
    }
}

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (($_POST['is_ajax'] ?? '0') === '1');

    if ($action === 'add_customer') {
        $name = sanitize($_POST['name'] ?? '');
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $id_proof = sanitize($_POST['id_proof'] ?? '');
        $password = $_POST['password'] ?? '';

        $profile_pic_path = null;

        // Handle Photo Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed)) {
                $fileName = 'cust_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
                }
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid image file format. Only JPG, PNG, WEBP, and GIF are allowed.']);
                    exit;
                }
            }
        }

        if (!empty($name) && !empty($email) && !empty($mobile) && !empty($password)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
                    exit;
                }
                set_toast('Please enter a valid email address.', 'danger');
            } else {
                // Check if email exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE `email` = :e");
                $checkStmt->execute(['e' => $email]);
                if ($checkStmt->fetchColumn() > 0) {
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
                        exit;
                    }
                    set_toast('A customer with this email address already exists.', 'danger');
                } else {
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare("INSERT INTO `customers` (`name`, `gender`, `mobile`, `email`, `address`, `id_proof`, `password`, `plain_password`, `profile_pic`) VALUES (:n, :g, :m, :e, :a, :idp, :p, :pp, :pic)");
                    $ins->execute([
                        'n' => $name,
                        'g' => $gender,
                        'm' => $mobile,
                        'e' => $email,
                        'a' => $address,
                        'idp' => $id_proof,
                        'p' => $passHash,
                        'pp' => $password,
                        'pic' => $profile_pic_path
                    ]);
                    $newId = $pdo->lastInsertId();

                    if ($is_ajax) {
                        // Fetch new customer record
                        $fetchStmt = $pdo->prepare("SELECT c.*, COUNT(b.booking_id) as total_stays FROM `customers` c LEFT JOIN `bookings` b ON c.customer_id = b.customer_id WHERE c.customer_id = :cid GROUP BY c.customer_id");
                        $fetchStmt->execute(['cid' => $newId]);
                        $newCustomer = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                        // Fetch updated total stats
                        $totalCustomers = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
                        $newCustomers30D = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Customer account created successfully!',
                            'customer' => $newCustomer,
                            'stats' => [
                                'totalCustomers' => intval($totalCustomers),
                                'newCustomers30D' => intval($newCustomers30D)
                            ]
                        ]);
                        exit;
                    }

                    set_toast('Customer account created successfully!', 'success');
                }
            }
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            set_toast('Please fill in all required fields.', 'danger');
        }
        redirect('/hotel_website/admin/customers.php');
    }

    elseif ($action === 'edit_customer') {
        $customerId = intval($_POST['customer_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $id_proof = sanitize($_POST['id_proof'] ?? '');
        $password = $_POST['password'] ?? '';
        $existingPic = $_POST['existing_profile_pic'] ?? null;

        $profile_pic_path = $existingPic;

        // Handle Photo Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed)) {
                $fileName = 'cust_' . $customerId . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
                }
            }
        }

        if ($customerId > 0 && !empty($name) && !empty($email) && !empty($mobile)) {
            // Check if email exists for another user
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE `email` = :e AND `customer_id` != :cid");
            $checkStmt->execute(['e' => $email, 'cid' => $customerId]);
            if ($checkStmt->fetchColumn() > 0) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Another customer is already using this email address.']);
                    exit;
                }
                set_toast('Another customer is already using this email address.', 'danger');
            } else {
                if (!empty($password)) {
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE `customers` SET `name` = :n, `gender` = :g, `mobile` = :m, `email` = :e, `address` = :a, `id_proof` = :idp, `password` = :p, `plain_password` = :pp, `profile_pic` = :pic WHERE `customer_id` = :cid");
                    $upd->execute([
                        'n' => $name,
                        'g' => $gender,
                        'm' => $mobile,
                        'e' => $email,
                        'a' => $address,
                        'idp' => $id_proof,
                        'p' => $passHash,
                        'pp' => $password,
                        'pic' => $profile_pic_path,
                        'cid' => $customerId
                    ]);
                } else {
                    $upd = $pdo->prepare("UPDATE `customers` SET `name` = :n, `gender` = :g, `mobile` = :m, `email` = :e, `address` = :a, `id_proof` = :idp, `profile_pic` = :pic WHERE `customer_id` = :cid");
                    $upd->execute([
                        'n' => $name,
                        'g' => $gender,
                        'm' => $mobile,
                        'e' => $email,
                        'a' => $address,
                        'idp' => $id_proof,
                        'pic' => $profile_pic_path,
                        'cid' => $customerId
                    ]);
                }

                if ($is_ajax) {
                    // Fetch updated customer record
                    $fetchStmt = $pdo->prepare("SELECT c.*, COUNT(b.booking_id) as total_stays FROM `customers` c LEFT JOIN `bookings` b ON c.customer_id = b.customer_id WHERE c.customer_id = :cid GROUP BY c.customer_id");
                    $fetchStmt->execute(['cid' => $customerId]);
                    $updatedCustomer = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Customer profile updated successfully!',
                        'customer' => $updatedCustomer
                    ]);
                    exit;
                }

                set_toast('Customer profile updated successfully!', 'success');
            }
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            set_toast('Please fill in all required fields.', 'danger');
        }
        redirect('/hotel_website/admin/customers.php');
    }

    elseif ($action === 'delete_customer') {
        $customerId = intval($_POST['customer_id'] ?? 0);
        if ($customerId > 0) {
            try {
                $pdo->exec("DELETE FROM `payments` WHERE `booking_id` IN (SELECT `booking_id` FROM `bookings` WHERE `customer_id` = $customerId)");
                $pdo->exec("DELETE FROM `bookings` WHERE `customer_id` = $customerId");
                $del = $pdo->prepare("DELETE FROM `customers` WHERE `customer_id` = :cid");
                $del->execute(['cid' => $customerId]);

                $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
                $activeCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Active' OR `status` IS NULL OR `status` = ''")->fetchColumn();
                $inactiveCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Inactive'")->fetchColumn();

                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => "Customer account #CST-00{$customerId} deleted.",
                        'customer_id' => $customerId,
                        'stats' => [
                            'totalCustomers' => $totalCustomers,
                            'activeCustomers' => $activeCustomers,
                            'inactiveCustomers' => $inactiveCustomers
                        ]
                    ]);
                    exit;
                }
                set_toast('Customer account deleted from system database.', 'info');
            } catch (Exception $e) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                set_toast('Error deleting customer account: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/hotel_website/admin/customers.php');
    }

    elseif ($action === 'change_customer_status') {
        $customerId = intval($_POST['customer_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'Active');
        if ($customerId > 0 && in_array($newStatus, ['Active', 'Inactive'])) {
            try {
                $stmt = $pdo->prepare("UPDATE `customers` SET `status` = :status WHERE `customer_id` = :cid");
                $stmt->execute(['status' => $newStatus, 'cid' => $customerId]);

                $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
                $activeCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Active' OR `status` IS NULL OR `status` = ''")->fetchColumn();
                $inactiveCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Inactive'")->fetchColumn();

                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => "Customer status updated to {$newStatus}.",
                        'status' => $newStatus,
                        'stats' => [
                            'totalCustomers' => $totalCustomers,
                            'activeCustomers' => $activeCustomers,
                            'inactiveCustomers' => $inactiveCustomers
                        ]
                    ]);
                    exit;
                }
                set_toast("Customer status updated to {$newStatus}.", 'success');
            } catch (Exception $e) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating customer status: ' . $e->getMessage()]);
                    exit;
                }
                set_toast('Error updating customer status.', 'danger');
            }
        }
        redirect('/hotel_website/admin/customers.php');
    }
}

// Fetch Dynamic Statistics & Customer Records
$totalCustomers = 0;
$activeCustomers = 0;
$inactiveCustomers = 0;
$newCustomers30D = 0;
$totalStaysBooked = 0;
$customers = [];
$customerBookings = [];

if ($pdo) {
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
    $activeCustomers = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Active' OR `status` IS NULL OR `status` = ''")->fetchColumn();
    $inactiveCustomers = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status` = 'Inactive'")->fetchColumn();
    $newCustomers30D = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $totalStaysBooked = $pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();

    // Fetch Customers with Total Stays
    $stmt = $pdo->query("SELECT c.*, COUNT(b.booking_id) as total_stays FROM `customers` c LEFT JOIN `bookings` b ON c.customer_id = b.customer_id GROUP BY c.customer_id ORDER BY c.customer_id DESC");
    $customers = $stmt->fetchAll();

    // Fetch all bookings for viewing details history
    $stmtBk = $pdo->query("SELECT b.booking_id, b.customer_id, b.check_in, b.check_out, b.booking_status, b.total_amount, r.room_no, rt.type_name FROM `bookings` b JOIN `rooms` r ON b.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id ORDER BY b.booking_id DESC");
    $allBookings = $stmtBk->fetchAll();
    foreach ($allBookings as $bk) {
        $customerBookings[$bk['customer_id']][] = $bk;
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- Custom Admin Customers Stylesheet -->
<link rel="stylesheet" href="/hotel_website/assets/css/admin-customers.css?v=<?php echo time(); ?>">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content">

<?php 
$page_title = 'Customers Management';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">

    <!-- 1. PAGE HEADER CARD & BREADCRUMB -->
    <div class="card customers-header-card p-4 mb-4" style="background: linear-gradient(135deg, #071A2F 0%, #0B2744 45%, #1769E0 100%); color: #ffffff; border: 1px solid rgba(23, 105, 224, 0.25); border-radius: 16px; box-shadow: 0 10px 30px rgba(7, 26, 47, 0.18);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-custom mb-1">
                        <li class="breadcrumb-item"><a href="/hotel_website/admin/dashboard.php" class="text-white-50 text-decoration-none"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">Customers</li>
                    </ol>
                </nav>
                <h3 class="font-serif-title fw-bold text-white fs-3 mb-1">Customers Inventory Management</h3>
                <p class="text-white-50 small mb-0">Manage guest accounts, view profiles, specifications, and booking histories.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" onclick="openAddCustomerDrawer()" class="btn btn-primary rounded-pill font-semibold px-3.5 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm" style="background-color: #2F80ED; border: none;">
                    <i class="fa-solid fa-user-plus"></i> Add New Customer
                </button>
                <button type="button" onclick="exportCustomerCSV()" class="btn btn-outline-light rounded-pill px-3.5 py-2 font-semibold shadow-sm d-inline-flex align-items-center gap-1.5" style="border-color: rgba(255,255,255,0.4); color: #fff;">
                    <i class="fa-solid fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- 2. CUSTOMER STATISTICS -->
    <section class="customer-stat-grid mb-4" aria-label="Customer statistics">
        <article class="customer-stat-card">
            <div class="customer-stat-icon customer-stat-icon-blue"><i class="fa-solid fa-users"></i></div>
            <div><span class="customer-stat-label">Total Customers</span><strong id="kpiTotalCustomers"><?php echo number_format((int) $totalCustomers); ?></strong><small>Registered accounts</small></div>
        </article>
        <article class="customer-stat-card">
            <div class="customer-stat-icon customer-stat-icon-green"><i class="fa-solid fa-user-check"></i></div>
            <div><span class="customer-stat-label">Active Customers</span><strong id="kpiActiveCustomers"><?php echo number_format((int) $activeCustomers); ?></strong><small>Currently active</small></div>
        </article>
        <article class="customer-stat-card">
            <div class="customer-stat-icon customer-stat-icon-amber"><i class="fa-solid fa-user-plus"></i></div>
            <div><span class="customer-stat-label">New Customers</span><strong><?php echo number_format((int) $newCustomers30D); ?></strong><small>Joined in 30 days</small></div>
        </article>
        <article class="customer-stat-card">
            <div class="customer-stat-icon customer-stat-icon-slate"><i class="fa-solid fa-user-minus"></i></div>
            <div><span class="customer-stat-label">Inactive Customers</span><strong id="kpiInactiveCustomers"><?php echo number_format((int) $inactiveCustomers); ?></strong><small>Not currently active</small></div>
        </article>
    </section>

    <!-- 2. SEARCH & FILTERS TOOLBAR CARD -->
    <div class="filter-toolbar-card mb-4">
        <div class="d-flex align-items-center justify-content-between gap-2.5 flex-nowrap w-100">
            
            <!-- Search Box (Expands to fill remaining space) -->
            <div class="search-input-wrapper flex-grow-1" style="min-width: 200px;">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="customerSearchInput" class="form-control" placeholder="Search by name, email, phone, or ID...">
                <button type="button" id="searchClearBtn" class="search-clear-btn" title="Clear Search"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Gender Filter -->
            <div style="width: 140px; flex-shrink: 0;">
                <select id="filterStatus" class="form-select form-select-sm rounded-3 font-semibold text-truncate" style="height: 42px;">
                    <option value="all">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <!-- Account Status Filter -->
            <div style="width: 140px; flex-shrink: 0;">
                <select id="filterAccountStatus" class="form-select form-select-sm rounded-3 font-semibold text-truncate" style="height: 42px;">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Reset Controls Button -->
            <div style="flex-shrink: 0;">
                <button type="button" onclick="resetCustomerFilters()" class="btn btn-light btn-sm rounded-3 font-semibold text-muted border d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" title="Reset Filters">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. CUSTOMER INVENTORY SECTION HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
        <div>
            <h5 class="font-serif-title fw-bold text-navy mb-0">Registered Guests Inventory</h5>
            <small class="text-muted">Manage guest accounts, view profiles, and update specifications.</small>
        </div>
        <div>
            <span id="recordCountDisplay" class="badge bg-white text-muted border font-semibold px-3 py-2 shadow-2xs" style="font-size: 0.82rem;">Showing <?php echo count($customers); ?> customer(s)</span>
        </div>
    </div>

    <!-- 4. TABLE VIEW CONTAINER -->
    <div id="customerTableContainer" class="table-card mb-4">

        <!-- Desktop & Tablet Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-customers align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="min-width: 200px;">Customer</th>
                        <th style="width: 130px; min-width: 130px;">Mobile</th>
                        <th style="min-width: 170px;">Email</th>
                        <th style="width: 130px; min-width: 130px;">Password</th>
                        <th style="width: 90px; min-width: 90px;">Gender</th>
                        <th style="width: 125px; min-width: 125px;">Status</th>
                        <th class="text-end" style="min-width: 175px; width: 175px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $c): 
                            $cBookings = $customerBookings[$c['customer_id']] ?? [];
                            $cDataPayload = array_merge($c, ['bookings' => $cBookings]);
                            $jsonPayload = htmlspecialchars(json_encode($cDataPayload, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $jsonCustOnly = htmlspecialchars(json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $gender = $c['gender'] ?? 'Male';
                            $stVal = !empty($c['status']) ? $c['status'] : 'Active';
                            $stClass = (strtolower($stVal) === 'inactive') ? 'customer-status-inactive' : 'customer-status-active';
                        ?>
                            <tr class="customer-data-row" 
                                data-customer="<?php echo $jsonPayload; ?>" 
                                data-id="<?php echo $c['customer_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($c['name']); ?>" 
                                data-email="<?php echo htmlspecialchars($c['email']); ?>" 
                                data-phone="<?php echo htmlspecialchars($c['mobile']); ?>" 
                                data-gender="<?php echo strtolower($gender); ?>" 
                                data-status="<?php echo strtolower($stVal); ?>"
                                data-stays="<?php echo $c['total_stays']; ?>" 
                                data-date="<?php echo date('Y-m-d', strtotime($c['created_at'])); ?>"
                                onclick="openCustomerDrawer(this)">
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($c['profile_pic'])): ?>
                                            <img src="<?php echo htmlspecialchars($c['profile_pic']); ?>" class="customer-avatar customer-photo-trigger" alt="Profile" data-src="<?php echo htmlspecialchars($c['profile_pic']); ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>" data-initials="<?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?>" onclick="event.stopPropagation(); triggerPhotoPreviewFromElem(this);" title="Click to view full profile photo">
                                        <?php else: ?>
                                            <div class="customer-avatar-initials customer-photo-trigger" data-name="<?php echo htmlspecialchars($c['name']); ?>" data-initials="<?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?>" onclick="event.stopPropagation(); triggerPhotoPreviewFromElem(this);" title="Click to view full photo">
                                                <?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-navy leading-snug"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <small class="text-muted extra-small">ID: #<?php echo $c['customer_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-nowrap fw-semibold text-navy">
                                    <?php echo htmlspecialchars($c['mobile']); ?>
                                </td>
                                <td>
                                    <span class="cell-truncate text-muted d-inline-block" style="max-width: 170px;" title="<?php echo htmlspecialchars($c['email']); ?>">
                                        <?php echo htmlspecialchars($c['email']); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap" onclick="event.stopPropagation();">
                                    <div class="d-inline-flex align-items-center justify-content-between gap-1.5 bg-light px-2.5 py-1 rounded-3 border text-nowrap" style="min-width: 120px;">
                                        <span id="custPassText_<?php echo $c['customer_id']; ?>" class="font-mono text-navy font-semibold small" data-plain="<?php echo htmlspecialchars($c['plain_password'] ?? 'user123'); ?>">••••••••</span>
                                        <button type="button" class="btn btn-link p-0 text-muted hover-primary ms-1" onclick="event.stopPropagation(); toggleShowCustomerPassword(<?php echo $c['customer_id']; ?>)" title="Toggle Show Password">
                                            <i id="custPassIcon_<?php echo $c['customer_id']; ?>" class="fa-solid fa-eye extra-small-label"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    <?php echo htmlspecialchars($gender); ?>
                                </td>
                                <td class="text-nowrap" onclick="event.stopPropagation();">
                                    <button type="button" 
                                            class="status-pill-btn <?php echo (strtolower($stVal) === 'inactive') ? 'status-pill-inactive' : 'status-pill-active'; ?> border-0 text-nowrap" 
                                            onclick="event.stopPropagation(); toggleCustomerStatus(<?php echo $c['customer_id']; ?>, '<?php echo $stVal; ?>')"
                                            id="custStatusBadge_<?php echo $c['customer_id']; ?>"
                                            title="Click to toggle status"
                                            style="<?php echo (strtolower($stVal) === 'inactive') ? 'color: #334155 !important; background-color: #F1F5F9 !important; border: 1px solid #CBD5E1 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;' : 'color: #047857 !important; background-color: #ECFDF5 !important; border: 1px solid #6EE7B7 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;'; ?>">
                                        <i class="fa-solid <?php echo (strtolower($stVal) === 'inactive') ? 'fa-circle-xmark text-secondary' : 'fa-circle-check text-success'; ?> me-1" style="font-size: 0.85rem !important;"></i>
                                        <span style="color: inherit !important; font-weight: 700 !important; border: none !important; outline: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; display: inline !important;"><?php echo htmlspecialchars($stVal); ?></span>
                                    </button>
                                </td>
                                <td class="text-end text-nowrap" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-id="<?php echo $c['customer_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                                onclick="event.stopPropagation(); event.preventDefault(); openDeleteCustomerModal(this);" title="Delete Customer">
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

        <!-- Mobile Card List View (< 768px) -->
        <div id="customerMobileCardList" class="d-md-none p-3 space-y-3 bg-light">
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $c): 
                    $cBookings = $customerBookings[$c['customer_id']] ?? [];
                    $cDataPayload = array_merge($c, ['bookings' => $cBookings]);
                    $jsonPayload = htmlspecialchars(json_encode($cDataPayload, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                    $jsonCustOnly = htmlspecialchars(json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                    $gender = $c['gender'] ?? 'Male';
                    $stVal = !empty($c['status']) ? $c['status'] : 'Active';
                    $stClass = (strtolower($stVal) === 'inactive') ? 'customer-status-inactive' : 'customer-status-active';
                ?>
                    <div class="customer-mobile-card mobile-customer-card" 
                         data-customer="<?php echo $jsonPayload; ?>" 
                         data-id="<?php echo $c['customer_id']; ?>" 
                         data-name="<?php echo htmlspecialchars($c['name']); ?>" 
                         data-email="<?php echo htmlspecialchars($c['email']); ?>" 
                         data-phone="<?php echo htmlspecialchars($c['mobile']); ?>" 
                         data-gender="<?php echo strtolower($gender); ?>" 
                         data-status="<?php echo strtolower($stVal); ?>"
                         data-stays="<?php echo $c['total_stays']; ?>" 
                         data-date="<?php echo date('Y-m-d', strtotime($c['created_at'])); ?>"
                         onclick="openCustomerDrawer(this)">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom" onclick="event.stopPropagation();">
                            <span class="fw-bold text-primary">#<?php echo $c['customer_id']; ?></span>
                            <button type="button" 
                                    class="status-pill-btn <?php echo (strtolower($stVal) === 'inactive') ? 'status-pill-inactive' : 'status-pill-active'; ?> border-0 text-nowrap" 
                                    onclick="event.stopPropagation(); toggleCustomerStatus(<?php echo $c['customer_id']; ?>, '<?php echo $stVal; ?>')"
                                    id="custStatusBadge_mob_<?php echo $c['customer_id']; ?>"
                                    title="Click to toggle status"
                                    style="<?php echo (strtolower($stVal) === 'inactive') ? 'color: #334155 !important; background-color: #F1F5F9 !important; border: 1px solid #CBD5E1 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;' : 'color: #047857 !important; background-color: #ECFDF5 !important; border: 1px solid #6EE7B7 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;'; ?>">
                                <i class="fa-solid <?php echo (strtolower($stVal) === 'inactive') ? 'fa-circle-xmark text-secondary' : 'fa-circle-check text-success'; ?> me-1" style="font-size: 0.85rem !important;"></i>
                                <span style="color: inherit !important; font-weight: 700 !important; border: none !important; outline: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; display: inline !important;"><?php echo htmlspecialchars($stVal); ?></span>
                            </button>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <?php if (!empty($c['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($c['profile_pic']); ?>" class="customer-avatar me-2.5 customer-photo-trigger" alt="View <?php echo htmlspecialchars($c['name']); ?> profile photo" role="button" tabindex="0" data-src="<?php echo htmlspecialchars($c['profile_pic']); ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>" data-initials="<?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?>" onclick="event.stopPropagation(); triggerPhotoPreviewFromElem(this);" title="View profile photo">
                            <?php else: ?>
                                <div class="customer-avatar-initials me-2.5 customer-photo-trigger" data-name="<?php echo htmlspecialchars($c['name']); ?>" data-initials="<?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?>" onclick="event.stopPropagation(); triggerPhotoPreviewFromElem(this);" role="button" tabindex="0" title="View profile photo"><?php echo strtoupper(substr($c['name'] ?? 'C', 0, 1)); ?></div>
                            <?php endif; ?>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-navy text-truncate"><?php echo htmlspecialchars($c['name']); ?></div>
                                <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($c['email']); ?></small>
                                <small class="text-muted d-block"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($c['mobile']); ?></small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end pt-2 border-top gap-2" onclick="event.stopPropagation();">
                            <button type="button" class="btn btn-action-delete flex-fill text-nowrap" 
                                    data-id="<?php echo $c['customer_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                    onclick="event.stopPropagation(); event.preventDefault(); openDeleteCustomerModal(this);">
                                <i class="fa-solid fa-trash-can me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 5. EMPTY STATE -->
        <div id="customerEmptyState" class="text-center py-5 bg-white" style="display: <?php echo empty($customers) ? 'block' : 'none'; ?>;">
            <div class="mb-3 text-muted" style="font-size: 3rem;">
                <i class="fa-solid fa-users-slash text-light"></i>
            </div>
            <h5 class="fw-bold text-navy mb-1">No Customers Found</h5>
            <p class="text-muted small mb-3">There are currently no customer accounts matching your search or applied filter criteria.</p>
            <button type="button" onclick="resetCustomerFilters()" class="btn btn-outline-primary btn-sm rounded-pill px-4 font-semibold">
                <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters &amp; Reset
            </button>
        </div>
    </div>

</div>

</main>

<!-- =========================================================
     6. CUSTOMER PROFILE MODAL
   ========================================================= -->
<div class="modal fade customer-profile-modal" id="customerDetailsDrawer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-navy font-bold">Customer Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="profile-summary">
                    <img id="drawer_avatar_img" src="" class="drawer-avatar-lg" alt="Avatar" style="display: none; cursor: pointer;" onclick="openDrawerPhotoPreview()">
                    <div id="drawer_avatar_initials" class="drawer-avatar-initials-lg" onclick="openDrawerPhotoPreview()">C</div>
                    <div class="profile-summary-copy">
                        <h4 id="drawer_name">Guest Name</h4>
                        <div class="profile-meta"><span id="drawer_gender">Male</span><span>Member since <span id="drawer_joined">N/A</span></span></div>
                    </div>
                </div>
                <div class="booking-history-heading mt-3"><strong>Booking History</strong><span id="drawer_id">#CST-000</span></div>
                <div id="drawer_bookings_list" class="profile-bookings-list"></div>
                <div class="d-none" aria-hidden="true"><span id="drawer_id_proof"></span><span id="drawer_address"></span><span id="drawer_completed_stays"></span><span id="drawer_pending_stays"></span><span id="drawer_cancelled_stays"></span></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" id="drawerEditBtn" class="btn btn-outline-primary rounded-3 font-semibold px-3.5"><i class="fa-solid fa-user-pen me-1"></i> Edit Profile</button>
                <button type="button" id="drawerDeleteBtn" class="btn btn-outline-danger rounded-3 font-semibold px-3.5"><i class="fa-solid fa-trash-can me-1"></i> Delete</button>
                <button type="button" class="btn btn-secondary rounded-3 font-semibold px-3.5" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     7. ADD CUSTOMER RIGHT-SIDE DRAWER (OFFCANVAS)
   ========================================================= -->
<div class="offcanvas offcanvas-end offcanvas-add-customer-drawer" tabindex="-1" id="addCustomerDrawer" data-bs-backdrop="static">
    <div class="offcanvas-header bg-white border-bottom">
        <div class="d-flex align-items-center gap-3">
            <div class="drawer-header-icon-box">
                <i class="fa-solid fa-user-plus text-primary"></i>
            </div>
            <div>
                <h5 class="offcanvas-title font-serif-title fw-bold text-navy mb-0">Add New Customer</h5>
                <p class="extra-small text-muted mb-0">Create a new customer account and manage their information.</p>
            </div>
        </div>
        <button type="button" class="btn-close" onclick="closeAddCustomerDrawerWithCheck()" title="Close Drawer"></button>
    </div>

    <form id="addCustomerForm" method="POST" enctype="multipart/form-data" onsubmit="handleAddCustomerFormSubmit(event)" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="action" value="add_customer">
        <input type="hidden" name="is_ajax" value="1">

        <div class="offcanvas-body space-y-4 p-4 bg-light flex-grow-1 overflow-y-auto">
            
            <!-- Error Alert Banner -->
            <div id="addCustomerErrorAlert" class="alert alert-danger border-0 rounded-3 shadow-sm d-none mb-3" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
                    <div>
                        <strong class="d-block small">Unable to create customer</strong>
                        <span id="addCustomerErrorMessage" class="extra-small"></span>
                    </div>
                </div>
            </div>

            <!-- Profile Photo Section -->
            <div class="card border rounded-4 p-4 bg-white text-center shadow-sm mb-4 overflow-hidden">
                <div class="mb-3 position-relative d-inline-block mx-auto">
                    <img id="add_avatar_preview" src="" class="add-customer-avatar-lg" style="display: none;" alt="Preview">
                    <div id="add_avatar_initials" class="add-customer-avatar-initials-lg">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 p-0 d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px;" onclick="triggerAddPhotoUpload()" title="Upload Photo">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>
                <div>
                    <button type="button" onclick="triggerAddPhotoUpload()" class="btn btn-outline-primary btn-sm rounded-pill font-semibold px-3 py-1">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Photo
                    </button>
                    <button type="button" id="removeAddPhotoBtn" onclick="clearAddPhotoPreview()" class="btn btn-outline-danger btn-sm rounded-pill font-semibold px-3 py-1 ms-1" style="display: none;">
                        <i class="fa-solid fa-trash-can me-1"></i> Remove
                    </button>
                    <a id="addDownloadPhotoBtn" class="btn btn-outline-secondary btn-sm rounded-pill font-semibold px-3 py-1 ms-1" style="display: none;" download title="Download profile photo">
                        <i class="fa-solid fa-download me-1"></i> Download
                    </a>
                    <input type="file" id="add_profile_pic_input" name="profile_pic" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none" onchange="previewAddCustomerPhoto(this)">
                    <div class="extra-small text-muted mt-2">Allowed: JPG, PNG, WEBP, GIF (Max 5MB)</div>
                    <small id="photo_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                </div>
            </div>

            <!-- Section 1: Personal Information -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-4 overflow-hidden">
                <h6 class="font-serif-title fw-bold text-navy mb-3 border-bottom pb-2.5 d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                    <i class="fa-solid fa-user-gear text-primary"></i> <span>Personal Information</span>
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">FULL NAME *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" name="name" id="add_name" placeholder="John Doe" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                        <small id="name_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">GENDER *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-venus-mars input-icon"></i>
                            <select name="gender" id="add_gender" class="form-select rounded-3 font-semibold add-cust-input">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">MOBILE NUMBER *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="text" name="mobile" id="add_mobile" placeholder="+1 234 567 890" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                        <small id="mobile_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">EMAIL ADDRESS *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" name="email" id="add_email" placeholder="name@example.com" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                        <small id="email_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-bold small text-muted mb-1">ADDRESS</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-location-dot input-icon"></i>
                            <input type="text" name="address" id="add_address" placeholder="Street Address, City, Country" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-bold small text-muted mb-1">ID PROOF (AADHAR / PASSPORT)</label>
                        <div class="input-group">
                            <select id="add_id_proof_type" class="form-select font-semibold add-cust-input" style="max-width: 155px; height: 44px; border-color: #E2E8F0; border-top-left-radius: 10px; border-bottom-left-radius: 10px;" onchange="updateCombinedIdProof('add')">
                                <option value="Aadhar Card">Aadhar Card</option>
                                <option value="Passport">Passport</option>
                                <option value="Driving License">Driving License</option>
                                <option value="Voter ID">Voter ID</option>
                            </select>
                            <input type="text" id="add_id_proof_num" placeholder="Enter ID Number" class="form-control font-semibold add-cust-input" style="height: 44px; border-color: #E2E8F0; border-top-right-radius: 10px; border-bottom-right-radius: 10px;" oninput="updateCombinedIdProof('add')">
                            <input type="hidden" name="id_proof" id="add_id_proof">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Account Security -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-2 overflow-hidden">
                <h6 class="font-serif-title fw-bold text-navy mb-3 border-bottom pb-2.5 d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                    <i class="fa-solid fa-lock text-primary"></i> <span>Account Security</span>
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">PASSWORD *</label>
                        <div class="input-icon-wrapper position-relative">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" name="password" id="add_password" placeholder="Create password" class="form-control rounded-3 font-semibold add-cust-input pe-5">
                            <button type="button" class="btn btn-link p-0 position-absolute end-0 top-50 translate-middle-y me-3 text-muted" onclick="togglePasswordVisibility('add_password', this)" title="Show/Hide Password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small id="password_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">CONFIRM PASSWORD *</label>
                        <div class="input-icon-wrapper position-relative">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" name="confirm_password" id="add_confirm_password" placeholder="Re-enter password" class="form-control rounded-3 font-semibold add-cust-input pe-5">
                            <button type="button" class="btn btn-link p-0 position-absolute end-0 top-50 translate-middle-y me-3 text-muted" onclick="togglePasswordVisibility('add_confirm_password', this)" title="Show/Hide Password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small id="confirm_password_error_msg" class="text-danger error-msg extra-small d-block mt-1" style="display:none;"></small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="offcanvas-footer add-customer-drawer-footer d-flex align-items-center justify-content-between p-3 px-4 bg-white border-top">
            <button type="button" onclick="resetAddCustomerFormWithConfirm()" class="btn btn-link text-muted p-0 small font-semibold text-decoration-none">
                <i class="fa-solid fa-rotate-left me-1"></i> Clear Form
            </button>
            <div class="d-flex align-items-center gap-2">
                <button type="button" onclick="closeAddCustomerDrawerWithCheck()" class="btn btn-outline-primary rounded-3 font-semibold px-3.5 py-2">
                    Cancel
                </button>
                <button type="submit" id="addCustomerSubmitBtn" class="btn btn-primary rounded-3 font-semibold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> <span id="addCustomerSubmitBtnText">Create Customer</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Unsaved Changes Confirmation Modal -->
<div class="modal fade" id="unsavedChangesModal" tabindex="-1" style="z-index: 1095;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg p-3 text-center">
            <div class="text-warning mb-2" style="font-size: 2.5rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h6 class="fw-bold text-navy mb-1">Unsaved Changes</h6>
            <p class="extra-small text-muted mb-3">You have entered customer information. Are you sure you want to discard it and close?</p>
            <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-light btn-sm rounded-3 font-semibold flex-grow-1" data-bs-dismiss="modal">Continue Editing</button>
                <button type="button" onclick="confirmDiscardAddCustomer()" class="btn btn-danger btn-sm rounded-3 font-semibold flex-grow-1">Discard</button>
            </div>
        </div>
    </div>
</div>

<!-- Premium Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="premiumToast" class="toast border-0 shadow-lg rounded-4 overflow-hidden" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center p-3 bg-white border-start border-4 border-success">
            <div class="toast-icon-wrapper text-success bg-success bg-opacity-10 rounded-circle me-3 p-2">
                <i class="fa-solid fa-circle-check fs-4"></i>
            </div>
            <div class="flex-grow-1 me-2">
                <h6 id="toastTitle" class="fw-bold text-navy mb-0 small">Customer Added Successfully</h6>
                <small id="toastMessage" class="text-muted extra-small">Customer profile created.</small>
            </div>
            <button type="button" class="btn-close me-1" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- =========================================================
     8. EDIT CUSTOMER RIGHT-SIDE DRAWER (OFFCANVAS)
   ========================================================= -->
<div class="offcanvas offcanvas-end offcanvas-edit-customer-drawer" tabindex="-1" id="editCustomerDrawer" data-bs-backdrop="static">
    <div class="offcanvas-header bg-white border-bottom">
        <div class="d-flex align-items-center gap-3">
            <div class="drawer-header-icon-box">
                <i class="fa-solid fa-user-pen text-primary"></i>
            </div>
            <div>
                <h5 class="offcanvas-title font-serif-title fw-bold text-navy mb-0">Edit Customer Profile</h5>
                <p class="extra-small text-muted mb-0">Update account specifications and residential info.</p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" title="Close Drawer"></button>
    </div>

    <form id="editCustomerForm" method="POST" enctype="multipart/form-data" onsubmit="handleEditCustomerFormSubmit(event)" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="action" value="edit_customer">
        <input type="hidden" name="is_ajax" value="1">
        <input type="hidden" name="customer_id" id="edit_customer_id">
        <input type="hidden" name="existing_profile_pic" id="existing_profile_pic">

        <div class="offcanvas-body space-y-4 p-4 bg-light flex-grow-1 overflow-y-auto">
            
            <!-- Error Alert Banner -->
            <div id="editCustomerErrorAlert" class="alert alert-danger border-0 rounded-3 shadow-sm d-none mb-3" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
                    <div>
                        <strong class="d-block small">Unable to update customer</strong>
                        <span id="editCustomerErrorMessage" class="extra-small"></span>
                    </div>
                </div>
            </div>

            <!-- Profile Photo Section -->
            <div class="card border rounded-4 p-4 bg-white text-center shadow-sm mb-4 overflow-hidden">
                <div class="mb-3 position-relative d-inline-block mx-auto">
                    <img id="edit_avatar_preview" src="" class="add-customer-avatar-lg" style="display: none;" alt="Preview">
                    <div id="edit_avatar_initials" class="add-customer-avatar-initials-lg">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 p-0 d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px;" onclick="triggerEditPhotoUpload()" title="Upload Photo">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>
                <div>
                    <button type="button" onclick="triggerEditPhotoUpload()" class="btn btn-outline-primary btn-sm rounded-pill font-semibold px-3 py-1">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Update Photo
                    </button>
                    <button type="button" id="removeEditPhotoBtn" onclick="clearEditPhotoPreview()" class="btn btn-outline-danger btn-sm rounded-pill font-semibold px-3 py-1 ms-1" style="display: none;">
                        <i class="fa-solid fa-trash-can me-1"></i> Remove
                    </button>
                    <a id="editDownloadPhotoBtn" class="btn btn-outline-secondary btn-sm rounded-pill font-semibold px-3 py-1 ms-1" style="display: none;" download title="Download profile photo">
                        <i class="fa-solid fa-download me-1"></i> Download
                    </a>
                    <input type="file" id="edit_profile_pic_input" name="profile_pic" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none" onchange="previewEditCustomerPhoto(this)">
                    <div class="extra-small text-muted mt-2">Allowed: JPG, PNG, WEBP, GIF (Max 5MB)</div>
                </div>
            </div>

            <!-- Section 1: Personal Information -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-4 overflow-hidden">
                <h6 class="font-serif-title fw-bold text-navy mb-3 border-bottom pb-2.5 d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                    <i class="fa-solid fa-user-gear text-primary"></i> <span>Personal Information</span>
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">FULL NAME *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" name="name" id="edit_name" required placeholder="John Doe" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">GENDER *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-venus-mars input-icon"></i>
                            <select name="gender" id="edit_gender" class="form-select rounded-3 font-semibold add-cust-input">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">MOBILE NUMBER *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="text" name="mobile" id="edit_mobile" required placeholder="+1 234 567 890" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label font-bold small text-muted mb-1">EMAIL ADDRESS *</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" name="email" id="edit_email" required placeholder="name@example.com" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-bold small text-muted mb-1">ADDRESS</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-location-dot input-icon"></i>
                            <input type="text" name="address" id="edit_address" placeholder="Street Address, City, Country" class="form-control rounded-3 font-semibold add-cust-input">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-bold small text-muted mb-1">ID PROOF (AADHAR / PASSPORT)</label>
                        <div class="input-group">
                            <select id="edit_id_proof_type" class="form-select font-semibold add-cust-input" style="max-width: 155px; height: 44px; border-color: #E2E8F0; border-top-left-radius: 10px; border-bottom-left-radius: 10px;" onchange="updateCombinedIdProof('edit')">
                                <option value="Aadhar Card">Aadhar Card</option>
                                <option value="Passport">Passport</option>
                                <option value="Driving License">Driving License</option>
                                <option value="Voter ID">Voter ID</option>
                            </select>
                            <input type="text" id="edit_id_proof_num" placeholder="Enter ID Number" class="form-control font-semibold add-cust-input" style="height: 44px; border-color: #E2E8F0; border-top-right-radius: 10px; border-bottom-right-radius: 10px;" oninput="updateCombinedIdProof('edit')">
                            <input type="hidden" name="id_proof" id="edit_id_proof">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Account Security -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-2 overflow-hidden">
                <h6 class="font-serif-title fw-bold text-navy mb-3 border-bottom pb-2.5 d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                    <i class="fa-solid fa-lock text-primary"></i> <span>Account Security</span>
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-bold small text-muted mb-1">NEW PASSWORD (LEAVE BLANK TO KEEP CURRENT)</label>
                        <div class="input-icon-wrapper position-relative">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" name="password" id="edit_password" placeholder="Create new password" class="form-control rounded-3 font-semibold add-cust-input pe-5">
                            <button type="button" class="btn btn-link p-0 position-absolute end-0 top-50 translate-middle-y me-3 text-muted" onclick="togglePasswordVisibility('edit_password', this)" title="Show/Hide Password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="offcanvas-footer edit-customer-drawer-footer d-flex align-items-center justify-content-between p-3 px-4 bg-white border-top">
            <button type="button" data-bs-dismiss="offcanvas" class="btn btn-outline-secondary rounded-3 font-semibold px-3.5 py-2">
                Cancel
            </button>
            <button type="submit" id="editCustomerSubmitBtn" class="btn btn-primary rounded-3 font-semibold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> <span id="editCustomerSubmitBtnText">Save Profile Changes</span>
            </button>
        </div>
    </form>
</div>

<!-- =========================================================
     9. DELETE CONFIRMATION MODAL
   ========================================================= -->
<!-- 9. ANIMATED DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deleteModalTitle">Delete Customer Account?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently remove this customer profile?</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-user-xmark me-2 text-danger"></i>
                    <span id="delete_customer_name_display">Guest Account</span>
                </div>

                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 extra-small rounded-3 text-start mb-3 p-2.5" style="color: #DC2626 !important; width: 100% !important; display: block !important;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Warning:</strong> Associated booking logs and payment records for this guest will also be permanently deleted.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="delete_customer">
                    <input type="hidden" name="customer_id" id="delete_customer_id">

                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-light rounded-3 px-4 font-semibold" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </button>
                        <button type="submit" id="btnConfirmCustomerDelete" class="btn btn-action-delete rounded-3 px-4 font-bold shadow-sm">
                            <i class="fa-solid fa-trash-can me-1.5"></i> Confirm Delete
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     10. PHOTO PREVIEW LIGHTBOX MODAL
   ========================================================= -->
<div class="modal fade photo-preview-modal" id="photoPreviewModal" tabindex="-1" aria-labelledby="photoPreviewTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-white text-navy">
            <div class="modal-header border-bottom p-3.5 bg-white">
                <h6 class="modal-title font-serif-title fw-bold text-navy mb-0" id="photoPreviewTitle">
                    <i class="fa-solid fa-image text-primary me-2"></i>Customer Profile Photo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                
                <!-- Interactive Zoom Toolbar Controls -->
                <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-3 bg-light rounded-pill px-3 py-1.5 border shadow-2xs">
                    <button type="button" class="btn btn-sm btn-white rounded-circle p-0 d-inline-flex align-items-center justify-content-center border" style="width: 32px; height: 32px;" onclick="zoomPhotoModal(-0.25)" title="Zoom Out (-)">
                        <i class="fa-solid fa-magnifying-glass-minus text-muted"></i>
                    </button>
                    <span id="photoZoomLevelText" class="extra-small font-semibold px-2 text-navy" style="min-width: 48px;">100%</span>
                    <button type="button" class="btn btn-sm btn-white rounded-circle p-0 d-inline-flex align-items-center justify-content-center border" style="width: 32px; height: 32px;" onclick="zoomPhotoModal(0.25)" title="Zoom In (+)">
                        <i class="fa-solid fa-magnifying-glass-plus text-muted"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-white rounded-circle p-0 d-inline-flex align-items-center justify-content-center ms-1 border" style="width: 32px; height: 32px;" onclick="resetPhotoModalZoom()" title="Reset Zoom">
                        <i class="fa-solid fa-rotate-left text-muted"></i>
                    </button>
                </div>

                <div id="photoPreviewImgWrapper" class="photo-preview-stage d-flex align-items-center justify-content-center overflow-hidden bg-light rounded-4 border">
                    <img id="photoPreviewImg" src="" class="img-fluid rounded-4 shadow-sm border" style="max-height: 60vh; object-fit: contain; transition: transform 0.2s ease; cursor: grab;">
                    <div id="photoPreviewInitials" class="drawer-avatar-initials-lg" style="width: 140px; height: 140px; font-size: 56px; display: none;">C</div>
                </div>

                <div id="photoPreviewSubtext" class="fw-bold fs-5 text-navy mt-3"></div>
                <div class="extra-small text-muted mt-1"><i class="fa-solid fa-circle-info me-1"></i> Use wheel scroll or buttons to Zoom In / Zoom Out photo</div>
            </div>
            <div class="modal-footer border-top p-3 bg-light justify-content-center">
                <a id="downloadPhotoBtn" class="btn btn-primary btn-sm rounded-pill px-4 font-semibold" href="#" download aria-label="Download customer profile photo">
                    <i class="fa-solid fa-download me-1"></i> Download Photo
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close Preview</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Customers Module JS -->
<script src="/hotel_website/assets/js/admin-customers.js?v=<?php echo time(); ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
