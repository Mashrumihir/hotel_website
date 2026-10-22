<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();

// Handle Form Submissions (Add, Edit, Change Status, Change Position, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // 1. ADD NEW EMPLOYEE
    if ($action === 'add_employee') {
        $name = sanitize($_POST['name'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0);
        $joining_date = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $status = sanitize($_POST['status'] ?? 'Active');

        // Handle Photo Upload
        $profile_pic_path = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $fileName = 'emp_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
                }
            }
        }

        if (!empty($name) && !empty($email)) {
            // Check for duplicate email
            $chk = $pdo->prepare("SELECT `employee_id` FROM `employees` WHERE `email` = :e LIMIT 1");
            $chk->execute(['e' => $email]);
            if ($chk->fetch()) {
                set_toast('An employee with this email address already exists.', 'warning');
            } else {
                $ins = $pdo->prepare("INSERT INTO `employees` (`name`, `position`, `mobile`, `email`, `profile_pic`, `salary`, `joining_date`, `status`) VALUES (:n, :p, :m, :e, :pic, :s, :j, :st)");
                $ins->execute([
                    'n' => $name,
                    'p' => $position,
                    'm' => $mobile,
                    'e' => $email,
                    'pic' => $profile_pic_path,
                    's' => $salary,
                    'j' => $joining_date,
                    'st' => $status
                ]);
                $new_id = $pdo->lastInsertId();
                set_toast('Employee #' . $new_id . ' (' . htmlspecialchars($name) . ') added to staff roster!', 'success');
            }
        } else {
            set_toast('Please fill in required fields (Name and Email).', 'danger');
        }
        redirect('/hotel_website/admin/employees.php');
    }

    // 2. EDIT EMPLOYEE
    elseif ($action === 'edit_employee') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0);
        $joining_date = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $status = sanitize($_POST['status'] ?? 'Active');

        // Handle Photo Upload
        $profile_pic_path = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $fileName = 'emp_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
                }
            }
        }

        if ($employee_id > 0 && !empty($name) && !empty($email)) {
            if (!empty($profile_pic_path)) {
                $upd = $pdo->prepare("UPDATE `employees` SET `name` = :n, `position` = :p, `mobile` = :m, `email` = :e, `profile_pic` = :pic, `salary` = :s, `joining_date` = :j, `status` = :st WHERE `employee_id` = :eid");
                $upd->execute([
                    'n' => $name,
                    'p' => $position,
                    'm' => $mobile,
                    'e' => $email,
                    'pic' => $profile_pic_path,
                    's' => $salary,
                    'j' => $joining_date,
                    'st' => $status,
                    'eid' => $employee_id
                ]);
            } else {
                $upd = $pdo->prepare("UPDATE `employees` SET `name` = :n, `position` = :p, `mobile` = :m, `email` = :e, `salary` = :s, `joining_date` = :j, `status` = :st WHERE `employee_id` = :eid");
                $upd->execute([
                    'n' => $name,
                    'p' => $position,
                    'm' => $mobile,
                    'e' => $email,
                    's' => $salary,
                    'j' => $joining_date,
                    'st' => $status,
                    'eid' => $employee_id
                ]);
            }
            set_toast('Employee profile for ' . htmlspecialchars($name) . ' updated successfully!', 'success');
        } else {
            set_toast('Please fill in required fields.', 'danger');
        }
        redirect('/hotel_website/admin/employees.php');
    }

    // 3. CHANGE STATUS
    elseif ($action === 'change_status') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Active');

        if ($employee_id > 0) {
            $upd = $pdo->prepare("UPDATE `employees` SET `status` = :st WHERE `employee_id` = :eid");
            $upd->execute(['st' => $status, 'eid' => $employee_id]);
            set_toast('Employee status updated to ' . strtoupper($status) . '!', 'success');
        }
        redirect('/hotel_website/admin/employees.php');
    }

    // 4. CHANGE POSITION / ROLE
    elseif ($action === 'change_position') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $position = sanitize($_POST['position'] ?? '');

        if ($employee_id > 0 && !empty($position)) {
            $upd = $pdo->prepare("UPDATE `employees` SET `position` = :pos WHERE `employee_id` = :eid");
            $upd->execute(['pos' => $position, 'eid' => $employee_id]);
            set_toast('Employee position updated to ' . htmlspecialchars($position) . '!', 'success');
        }
        redirect('/hotel_website/admin/employees.php');
    }

    // 5. DELETE EMPLOYEE
    elseif ($action === 'delete_employee') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        if ($employee_id > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM `employees` WHERE `employee_id` = :eid");
                $del->execute(['eid' => $employee_id]);
                set_toast('Employee removed from staff roster.', 'info');
            } catch (Exception $e) {
                set_toast('Error deleting employee: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/hotel_website/admin/employees.php');
    }
}

// Fetch All Employees and Distinct Positions
$employees = [];
$distinctPositions = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `employees` ORDER BY `employee_id` DESC");
    $employees = $stmt->fetchAll();

    $stmtP = $pdo->query("SELECT DISTINCT `position` FROM `employees` WHERE `position` IS NOT NULL AND `position` != '' ORDER BY `position` ASC");
    $distinctPositions = $stmtP->fetchAll(PDO::FETCH_COLUMN);
}

// Calculate 6 Real-time KPI Stats
$total_emp_count = count($employees);
$active_emp_count = 0;
$inactive_emp_count = 0;
$leave_emp_count = 0;
$manager_emp_count = 0;
$staff_emp_count = 0;

foreach ($employees as $e) {
    $st = $e['status'] ?? 'Active';
    $pos = strtolower($e['position'] ?? '');

    if ($st === 'Active') $active_emp_count++;
    elseif ($st === 'Inactive') $inactive_emp_count++;
    elseif ($st === 'On Leave') $leave_emp_count++;

    if (strpos($pos, 'manager') !== false) {
        $manager_emp_count++;
    } else {
        $staff_emp_count++;
    }
}
?>

<!-- Include Admin Employees Stylesheet -->
<link rel="stylesheet" href="../assets/css/admin-employees.css?v=20261007">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

<?php 
$page_title = 'Employee Management';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">
    
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 extra-small text-muted font-bold text-uppercase mb-0.5">
                    <span>Dashboard</span> / <span class="text-primary">Employees</span>
                </div>
                <h3 class="font-serif-title fw-bold text-navy mb-0">Employee Management</h3>
                <p class="small text-muted mb-0">Manage HotelEase staff, roles, departments, status and employee information.</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 employee-page-actions">
            <button type="button" class="btn btn-outline-primary rounded-3 font-semibold px-3" onclick="exportEmployeesCSV()">
                <i class="fa-solid fa-file-csv me-1.5"></i> Export CSV
            </button>
            <button type="button" class="btn btn-primary rounded-3 font-semibold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmpModal" onclick="openAddEmpModal()">
                <i class="fa-solid fa-user-plus me-1.5"></i> Add Employee
            </button>
        </div>
    </div>

    <!-- Toolbar & Filters Section -->
    <div class="toolbar-card mb-4">
        <div class="row g-2 align-items-center">
            
            <!-- Live Search Box -->
            <div class="col-12 col-md-4 col-xl-3">
                <div class="search-box-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchEmpInput" class="form-control" placeholder="Search Name, Emp ID, Email, Position...">
                    <button type="button" id="btnSearchClear" class="btn-search-clear" title="Clear Search">&times;</button>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="filterStatusSelect" class="form-select filter-select">
                    <option value="all">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="On Leave">On Leave</option>
                </select>
            </div>

            <!-- Position / Role Filter -->
            <div class="col-6 col-md-2 col-xl-3">
                <select id="filterPositionSelect" class="form-select filter-select">
                    <option value="all">All Roles / Positions</option>
                    <option value="Manager">Managers Only</option>
                    <option value="Staff">Staff Only</option>
                    <?php foreach ($distinctPositions as $pos): ?>
                        <option value="<?php echo htmlspecialchars($pos); ?>"><?php echo htmlspecialchars($pos); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sort By Select -->
            <div class="col-6 col-md-2 col-xl-2">
                <select id="sortEmpSelect" class="form-select filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="name_asc">Name: A to Z</option>
                    <option value="name_desc">Name: Z to A</option>
                    <option value="salary_high">Salary: High to Low</option>
                </select>
            </div>


        </div>

        <!-- Action Bar Footer -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top extra-small text-muted">
            <div id="empCounterText" class="font-semibold">Showing 1–<?php echo count($employees); ?> of <?php echo count($employees); ?> staff members</div>
            <div>
                <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none p-0 extra-small" onclick="resetEmpFilters()">
                    <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE VIEW CONTAINER -->
    <div id="empTableViewContainer" class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table employees-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th style="width: 150px;">Position / Role</th>
                        <th style="width: 130px;">Mobile</th>
                        <th style="width: 160px;">Email</th>
                        <th style="width: 110px;">Monthly Salary</th>
                        <th style="width: 110px;">Joining Date</th>
                        <th style="width: 110px;">Status</th>
                        <th class="text-center" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $e): ?>
                            <?php 
                            $e_json = htmlspecialchars(json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $initial = strtoupper(substr($e['name'] ?? 'E', 0, 1));
                            ?>
                            <tr class="emp-row-item" data-emp="<?php echo $e_json; ?>" onclick="openEmpDrawer(this)">
                                
                                <!-- Employee Profile -->
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-3 text-nowrap">
                                        <?php if (!empty($e['profile_pic'])): ?>
                                            <img src="<?php echo htmlspecialchars($e['profile_pic']); ?>" alt="Profile" class="emp-avatar-thumb flex-shrink-0" style="object-fit: cover;" onclick="event.stopPropagation(); openPhotoPreview('<?php echo htmlspecialchars($e['profile_pic']); ?>', '<?php echo htmlspecialchars(addslashes($e['name'])); ?> Photo');">
                                        <?php else: ?>
                                            <div class="emp-avatar-thumb flex-shrink-0" onclick="event.stopPropagation(); openProfilePhotoPreview('<?php echo $initial; ?>', '<?php echo htmlspecialchars(addslashes($e['name'])); ?>');">
                                                <?php echo $initial; ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="fw-bold text-navy text-nowrap"><?php echo htmlspecialchars($e['name']); ?></span>
                                    </div>
                                </td>

                                <!-- Position -->
                                <td class="fw-semibold text-primary text-nowrap">
                                    <?php echo htmlspecialchars($e['position']); ?>
                                </td>

                                <!-- Mobile -->
                                <td class="text-nowrap">
                                    <i class="fa-solid fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($e['mobile']); ?>
                                </td>

                                <!-- Email -->
                                <td class="text-nowrap">
                                    <i class="fa-solid fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($e['email']); ?>
                                </td>

                                <!-- Salary -->
                                <td class="fw-bold text-navy text-nowrap">
                                    ₹<?php echo number_format($e['salary'], 2); ?>
                                </td>

                                <!-- Joining Date -->
                                <td class="text-nowrap">
                                    <?php echo date('M d, Y', strtotime($e['joining_date'])); ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="text-nowrap">
                                    <?php 
                                    $st = $e['status'] ?? 'Active';
                                    if ($st === 'Active') echo '<span class="badge-emp-status badge-status-active"><i class="fa-solid fa-circle-check"></i> Active</span>';
                                    elseif ($st === 'Inactive') echo '<span class="badge-emp-status badge-status-inactive"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>';
                                    elseif ($st === 'On Leave') echo '<span class="badge-emp-status badge-status-leave"><i class="fa-solid fa-clock"></i> On Leave</span>';
                                    ?>
                                </td>

                                <!-- Actions Column -->
                                <td class="text-center text-nowrap" style="width: 90px;" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDeleteEmpModal(<?php echo $e['employee_id']; ?>, '<?php echo htmlspecialchars(addslashes($e['name'])); ?>')" title="Delete Employee">
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
    <div id="empEmptyState" class="text-center py-5 bg-white rounded-4 border p-4" style="display: none;">
        <div class="mb-3 text-muted" style="font-size: 3rem;">
            <i class="fa-solid fa-users-slash"></i>
        </div>
        <h5 class="fw-bold text-navy mb-1">No Staff Members Found</h5>
        <p class="small text-muted mb-3">There are no employees matching your current search or filter criteria.</p>
        <div class="d-flex align-items-center justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="resetEmpFilters()">
                Clear Filters
            </button>
            <button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold px-3" data-bs-toggle="modal" data-bs-target="#addEmpModal" onclick="openAddEmpModal()">
                <i class="fa-solid fa-user-plus me-1"></i> Add Employee
            </button>
        </div>
    </div>

</div>

</main>

<!-- ================================================== -->
<!-- RIGHT-SIDE DRAWER (EMPLOYEE DETAILS PANEL) -->
<!-- ================================================== -->
<div id="empDrawerBackdrop" class="emp-drawer-backdrop" onclick="closeEmpDrawer()"></div>
<div id="empDrawer" class="emp-drawer">
    
    <!-- Drawer Header -->
    <div class="drawer-header">
        <div class="d-flex align-items-center gap-2.5">
            <div class="bg-primary bg-opacity-25 text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-user-id-card fs-5 text-primary-blue"></i>
            </div>
            <div>
                <div id="drawerEmpName" class="drawer-title text-white fw-bold fs-6 mb-0">Staff Profile</div>
                <div id="drawerEmpId" class="extra-small text-white-50 font-semibold">#EMP-000</div>
            </div>
        </div>
        <button type="button" class="btn-drawer-close" onclick="closeEmpDrawer()" title="Close Drawer">&times;</button>
    </div>

    <!-- Drawer Body -->
    <div class="drawer-body">
        
        <!-- Section 1: Profile -->
        <div class="drawer-section">
            <div class="drawer-section-title mb-3">
                <span><i class="fa-solid fa-user text-primary me-1.5"></i> Staff Profile</span>
                <div id="drawerStatusBadge"></div>
            </div>
            <div class="d-flex align-items-center gap-3 p-2 bg-white rounded-3 border">
                <img id="drawerAvatarPic" src="" alt="Avatar" class="emp-avatar-thumb flex-shrink-0" style="width: 56px; height: 56px; display: none; object-fit: cover;">
                <div id="drawerAvatarInitial" class="emp-avatar-thumb flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.4rem;">E</div>
                <div>
                    <h5 id="drawerProfileName" class="fw-bold text-navy mb-0.5 fs-6">Full Name</h5>
                    <div id="drawerProfilePosition" class="small text-primary font-semibold">Position / Role</div>
                    <div id="drawerProfileSalary" class="extra-small text-muted font-bold mt-0.5">₹0.00/mo</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Information -->
        <div class="drawer-section">
            <div class="drawer-section-title mb-3">
                <span><i class="fa-solid fa-address-book text-primary me-1.5"></i> Contact Information</span>
            </div>
            <div class="d-flex flex-column gap-2 extra-small">
                <div class="p-2.5 bg-white rounded-3 border">
                    <div class="text-muted font-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-envelope me-1 text-primary"></i> Email Address
                    </div>
                    <div id="drawerEmpEmail" class="fw-bold text-navy fs-6 text-break">email@example.com</div>
                </div>
                <div class="p-2.5 bg-white rounded-3 border">
                    <div class="text-muted font-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-phone me-1 text-primary"></i> Mobile / Phone
                    </div>
                    <div id="drawerEmpMobile" class="fw-bold text-navy fs-6">+91 9876543210</div>
                </div>
            </div>
        </div>

        <!-- Section 3: Employment Information -->
        <div class="drawer-section">
            <div class="drawer-section-title mb-3">
                <span><i class="fa-solid fa-briefcase text-primary me-1.5"></i> Employment Details</span>
            </div>
            <div class="row g-2 extra-small">
                <div class="col-6">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Employee ID</div>
                        <div id="drawerEmpRefId" class="fw-bold text-primary fs-6">#EMP-001</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Designation</div>
                        <div id="drawerEmpPosition" class="fw-bold text-navy fs-6 text-truncate">Position</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-2.5 bg-white rounded-3 border">
                        <div class="text-muted font-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-calendar-check me-1 text-primary"></i> Joining Date
                        </div>
                        <div id="drawerEmpJoiningDate" class="fw-bold text-navy fs-6">Oct 12, 2026</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Drawer Footer Actions -->
    <div class="drawer-footer p-3">
        <div id="drawerActionButtons" class="w-100"></div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 1: ADD NEW EMPLOYEE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="addEmpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-user-plus text-primary me-2"></i>Add New Staff Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_employee">
                <div class="modal-body space-y-3 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">FULL NAME *</label>
                            <input type="text" name="name" required placeholder="e.g. Rajesh Kumar" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">POSITION / ROLE *</label>
                            <input type="text" name="position" required placeholder="e.g. General Manager / Receptionist" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PHONE / MOBILE *</label>
                            <input type="text" name="mobile" required placeholder="+91 9823456789" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">EMAIL ADDRESS *</label>
                            <input type="email" name="email" required placeholder="rajesh@hotelease.com" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PROFILE PHOTO</label>
                            <input type="file" name="profile_pic" accept="image/*" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">MONTHLY SALARY (₹) *</label>
                            <input type="number" step="0.01" name="salary" value="45000" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">JOINING DATE *</label>
                            <input type="date" name="joining_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">STATUS *</label>
                            <select name="status" class="form-select rounded-3 font-semibold">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 2: EDIT EMPLOYEE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="editEmpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-user-pen text-primary me-2"></i>Edit Staff Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_employee">
                <input type="hidden" name="employee_id" id="edit_employee_id">

                <div class="modal-body space-y-3 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">FULL NAME *</label>
                            <input type="text" name="name" id="edit_name" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">POSITION / ROLE *</label>
                            <input type="text" name="position" id="edit_position" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PHONE / MOBILE *</label>
                            <input type="text" name="mobile" id="edit_mobile" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">EMAIL ADDRESS *</label>
                            <input type="email" name="email" id="edit_email" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PROFILE PHOTO</label>
                            <input type="file" name="profile_pic" accept="image/*" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">MONTHLY SALARY (₹) *</label>
                            <input type="number" step="0.01" name="salary" id="edit_salary" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">JOINING DATE *</label>
                            <input type="date" name="joining_date" id="edit_joining_date" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">STATUS *</label>
                            <select name="status" id="edit_status" class="form-select rounded-3 font-semibold">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                            </select>
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
<!-- MODAL 3: CHANGE STATUS MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-user-tag text-success me-2"></i>Change Employee Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="employee_id" id="status_employee_id">

                <div class="modal-body space-y-3 pt-3">
                    <p class="mb-2">Update status for employee <strong id="status_emp_name" class="text-navy">Employee Name</strong>:</p>
                    <div>
                        <label class="form-label font-bold small text-muted">EMPLOYMENT STATUS</label>
                        <select name="status" id="status_select" class="form-select rounded-3 font-semibold">
                            <option value="Active">Active 🟢</option>
                            <option value="Inactive">Inactive ⚪</option>
                            <option value="On Leave">On Leave 🟡</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 font-semibold px-4">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 4: CHANGE POSITION / ROLE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="changePositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-briefcase text-purple me-2"></i>Change Designation / Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_position">
                <input type="hidden" name="employee_id" id="pos_employee_id">

                <div class="modal-body space-y-3 pt-3">
                    <p class="mb-2">Update position for <strong id="pos_emp_name" class="text-navy">Employee Name</strong>:</p>
                    <div>
                        <label class="form-label font-bold small text-muted">POSITION / ROLE *</label>
                        <input type="text" name="position" id="pos_input" required class="form-control rounded-3 font-semibold" placeholder="e.g. Front Desk Manager">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">Update Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL 5: DELETE EMPLOYEE MODAL -->
<!-- ================================================== -->
<div class="modal fade" id="deleteEmpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-trash-can text-danger me-2"></i>Delete Staff Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_employee">
                <input type="hidden" name="employee_id" id="delete_employee_id">

                <div class="modal-body pt-3">
                    <p class="mb-2">Are you sure you want to delete employee <strong id="delete_emp_name">Employee Name</strong> (<strong id="delete_emp_id_ref" class="text-primary">#EMP-000</strong>)?</p>
                    <div class="alert alert-danger border-0 rounded-3 extra-small mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> This action cannot be undone. The staff member will be removed from roster.
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
<!-- MODAL 6: PROFILE PHOTO LIGHTBOX PREVIEW -->
<!-- ================================================== -->
<div class="modal fade" id="profilePhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-navy border-0 text-white rounded-4 shadow p-4 text-center">
            <div class="modal-header border-0 pb-0 justify-content-between">
                <h6 id="lightboxEmpName" class="modal-title font-serif-title fw-bold text-white">Employee Avatar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="lightboxInitial" class="emp-avatar-thumb mx-auto mb-3 shadow" style="width: 120px; height: 120px; font-size: 3rem;">
                    E
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Admin Employees JavaScript -->
<script src="../assets/js/admin-employees.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
