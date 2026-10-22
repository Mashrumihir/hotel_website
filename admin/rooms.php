<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = getDBConnection();

// Ensure floor and bed_type columns exist in DB
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `floor` VARCHAR(50) DEFAULT 'Floor 1'");
        $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `bed_type` VARCHAR(50) DEFAULT 'King Bed'");
    } catch (Exception $e) {
        // Columns already exist
    }
}

// Handle POST actions (Add, Edit, Delete, Change Status, Bulk Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // 1. ADD ROOM
    if ($action === 'add_room') {
        $room_no = sanitize($_POST['room_no'] ?? '');
        $type_id = intval($_POST['room_type_id'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 2);
        $floor = sanitize($_POST['floor'] ?? 'Floor 1');
        $bed_type = sanitize($_POST['bed_type'] ?? 'King Bed');
        $description = sanitize($_POST['description'] ?? '');
        $amenities = sanitize($_POST['amenities'] ?? '');
        $status = sanitize($_POST['status'] ?? 'Available');

        $room_image_path = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1000&q=80';

        $uploaded_paths = [];
        $uploadDir = __DIR__ . '/../uploads/rooms/';
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        // File Upload (Multiple)
        if (isset($_FILES['room_photos']) && !empty($_FILES['room_photos']['name'][0])) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $count = count($_FILES['room_photos']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['room_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['room_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $fileName = 'room_' . time() . '_' . $i . '_' . rand(100, 999) . '.' . $ext;
                        $targetFile = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['room_photos']['tmp_name'][$i], $targetFile)) {
                            $uploaded_paths[] = '/hotel_website/uploads/rooms/' . $fileName;
                        }
                    }
                }
            }
        } elseif (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['room_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $fileName = 'room_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['room_photo']['tmp_name'], $targetFile)) {
                    $uploaded_paths[] = '/hotel_website/uploads/rooms/' . $fileName;
                }
            }
        }

        if (!empty($uploaded_paths)) {
            $room_image_path = implode(',', $uploaded_paths);
        }

        if (!empty($room_no) && $price > 0) {
            // Check for duplicate room number
            $chk = $pdo->prepare("SELECT COUNT(*) FROM `rooms` WHERE `room_no` = :rno");
            $chk->execute(['rno' => $room_no]);
            if ($chk->fetchColumn() > 0) {
                set_toast('Room number ' . $room_no . ' already exists in inventory.', 'danger');
            } else {
                $stmt = $pdo->prepare("INSERT INTO `rooms` (`room_no`, `room_type_id`, `price`, `capacity`, `floor`, `bed_type`, `description`, `amenities`, `image`, `status`) VALUES (:rno, :rtid, :pr, :cap, :flr, :bed, :des, :am, :img, :st)");
                $stmt->execute([
                    'rno' => $room_no,
                    'rtid' => $type_id,
                    'pr' => $price,
                    'cap' => $capacity,
                    'flr' => $floor,
                    'bed' => $bed_type,
                    'des' => $description,
                    'am' => $amenities,
                    'img' => $room_image_path,
                    'st' => $status
                ]);
                set_toast('Room ' . $room_no . ' added successfully to inventory!', 'success');
            }
        } else {
            set_toast('Please fill in required fields (Room Number & Price).', 'danger');
        }
        redirect('/hotel_website/admin/rooms.php');
    }

    // 2. EDIT ROOM
    elseif ($action === 'edit_room') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $room_no = sanitize($_POST['room_no'] ?? '');
        $type_id = intval($_POST['room_type_id'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 2);
        $floor = sanitize($_POST['floor'] ?? 'Floor 1');
        $bed_type = sanitize($_POST['bed_type'] ?? 'King Bed');
        $description = sanitize($_POST['description'] ?? '');
        $amenities = sanitize($_POST['amenities'] ?? '');
        $status = sanitize($_POST['status'] ?? 'Available');
        $existing_image = sanitize($_POST['existing_image'] ?? '');

        // Parse existing remaining image URLs (if any)
        $existing_list = array_filter(array_map('trim', explode(',', $existing_image)));

        $uploaded_paths = [];
        $uploadDir = __DIR__ . '/../uploads/rooms/';
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (isset($_FILES['room_photos']) && !empty($_FILES['room_photos']['name'][0])) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $count = count($_FILES['room_photos']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['room_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['room_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $fileName = 'room_' . $room_id . '_' . time() . '_' . $i . '.' . $ext;
                        $targetFile = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['room_photos']['tmp_name'][$i], $targetFile)) {
                            $uploaded_paths[] = '/hotel_website/uploads/rooms/' . $fileName;
                        }
                    }
                }
            }
        } elseif (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['room_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $fileName = 'room_' . $room_id . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                $tmp = $_FILES['room_photo']['tmp_name'];
                if (move_uploaded_file($tmp, $targetFile)) {
                    $uploaded_paths[] = '/hotel_website/uploads/rooms/' . $fileName;
                }
            }
        }

        // Combine existing saved images with newly uploaded images
        $final_images = array_merge($existing_list, $uploaded_paths);

        if (!empty($final_images)) {
            $room_image_path = implode(',', $final_images);
        } else {
            $room_image_path = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1000&q=80';
        }

        if ($room_id > 0 && !empty($room_no) && $price > 0) {
            $stmt = $pdo->prepare("UPDATE `rooms` SET `room_no` = :rno, `room_type_id` = :rtid, `price` = :pr, `capacity` = :cap, `floor` = :flr, `bed_type` = :bed, `description` = :des, `amenities` = :am, `image` = :img, `status` = :st WHERE `room_id` = :rid");
            $stmt->execute([
                'rno' => $room_no,
                'rtid' => $type_id,
                'pr' => $price,
                'cap' => $capacity,
                'flr' => $floor,
                'bed' => $bed_type,
                'des' => $description,
                'am' => $amenities,
                'img' => $room_image_path,
                'st' => $status,
                'rid' => $room_id
            ]);
            set_toast('Room ' . $room_no . ' details updated successfully!', 'success');
        } else {
            set_toast('Please fill in required fields.', 'danger');
        }
        redirect('/hotel_website/admin/rooms.php');
    }

    // 3. CHANGE SINGLE ROOM STATUS
    elseif ($action === 'change_room_status') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Available');
        if ($room_id > 0) {
            $stmt = $pdo->prepare("UPDATE `rooms` SET `status` = :st WHERE `room_id` = :rid");
            $stmt->execute(['st' => $status, 'rid' => $room_id]);
            set_toast('Room status updated to ' . $status, 'success');
        }
        redirect('/hotel_website/admin/rooms.php');
    }

    // 4. DELETE ROOM
    elseif ($action === 'delete_room') {
        $room_id = intval($_POST['room_id'] ?? 0);
        if ($room_id > 0) {
            try {
                $pdo->exec("DELETE FROM `payments` WHERE `booking_id` IN (SELECT `booking_id` FROM `bookings` WHERE `room_id` = $room_id)");
                $pdo->exec("DELETE FROM `bookings` WHERE `room_id` = $room_id");
                $stmt = $pdo->prepare("DELETE FROM `rooms` WHERE `room_id` = :rid");
                $stmt->execute(['rid' => $room_id]);
                set_toast('Room deleted from inventory.', 'info');
            } catch (Exception $e) {
                set_toast('Error deleting room: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/hotel_website/admin/rooms.php');
    }

    // 5. BULK ACTIONS
    elseif ($action === 'bulk_action') {
        $bulk_type = sanitize($_POST['bulk_type'] ?? '');
        $bulk_ids = array_filter(array_map('intval', explode(',', $_POST['bulk_ids'] ?? '')));

        if (!empty($bulk_ids)) {
            $inClause = implode(',', $bulk_ids);
            if ($bulk_type === 'bulk_delete') {
                $pdo->exec("DELETE FROM `rooms` WHERE `room_id` IN ($inClause)");
                set_toast(count($bulk_ids) . ' room(s) deleted from inventory.', 'info');
            } elseif ($bulk_type === 'bulk_status') {
                $st = sanitize($_POST['bulk_status'] ?? 'Available');
                $pdo->exec("UPDATE `rooms` SET `status` = '$st' WHERE `room_id` IN ($inClause)");
                set_toast(count($bulk_ids) . ' room(s) status updated to ' . $st, 'success');
            }
        }
        redirect('/hotel_website/admin/rooms.php');
    }
}

// Fetch Rooms, Categories, Statistics & Bookings
$rooms = [];
$types = [];
$totalCount = 0;
$availableCount = 0;
$occupiedCount = 0;
$reservedCount = 0;
$cleaningCount = 0;
$maintCount = 0;

$roomBookingsMap = [];

if ($pdo) {
    // 1. Fetch Room Types
    $stmtTypes = $pdo->query("SELECT * FROM `room_types` ORDER BY `room_type_id` ASC");
    $types = $stmtTypes->fetchAll();

    // 2. Fetch All Bookings to map to rooms
    $stmtBk = $pdo->query("SELECT b.*, c.name as customer_name, c.email as customer_email, c.mobile as customer_mobile FROM `bookings` b JOIN `customers` c ON b.customer_id = c.customer_id ORDER BY b.booking_id DESC");
    $allBookings = $stmtBk->fetchAll();
    foreach ($allBookings as $bk) {
        $roomBookingsMap[$bk['room_id']][] = $bk;
    }

    // 3. Fetch Rooms with Categories
    $stmt = $pdo->query("SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id ORDER BY r.room_id ASC");
    $rawRooms = $stmt->fetchAll();

    foreach ($rawRooms as $r) {
        $rBookings = $roomBookingsMap[$r['room_id']] ?? [];
        $activeBk = null;

        // Find current active booking if occupied/reserved
        foreach ($rBookings as $b) {
            if (in_array($b['booking_status'], ['Confirmed', 'Checked-In', 'Pending'])) {
                $activeBk = $b;
                break;
            }
        }

        $rData = array_merge($r, [
            'all_bookings' => $rBookings,
            'active_booking' => $activeBk
        ]);

        $rooms[] = $rData;

        // Count Statistics
        $totalCount++;
        $st = strtolower($r['status']);
        if ($st === 'available') $availableCount++;
        elseif ($st === 'occupied') $occupiedCount++;
        elseif ($st === 'reserved') $reservedCount++;
        elseif ($st === 'cleaning') $cleaningCount++;
        elseif ($st === 'maintenance') $maintCount++;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom Admin Rooms Stylesheet -->
<link rel="stylesheet" href="/hotel_website/assets/css/admin-rooms.css?v=20260928-4">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content">

<?php 
$page_title = 'Rooms Management';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">

    <!-- PRINT HEADER REPORT (Only Visible When Printing / Saving PDF) -->
    <div class="d-none d-print-block mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">HOTELEASE — ROOMS INVENTORY REPORT</h3>
                <p class="small text-muted mb-0">Official Hotel Room Inventory Summary &amp; Status Sheet</p>
            </div>
            <div class="text-end">
                <strong class="d-block small text-dark">Date: <?php echo date('d M Y, h:i A'); ?></strong>
                <span class="small text-muted">Total Rooms: <?php echo count($rooms); ?></span>
            </div>
        </div>
    </div>

    <!-- 1. PAGE HEADER CARD & BREADCRUMB -->
    <div class="card rooms-header-card p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-custom mb-1">
                        <li class="breadcrumb-item"><a href="/hotel_website/admin/dashboard.php"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Rooms</li>
                    </ol>
                </nav>
                <h3 class="font-serif-title fw-bold text-white fs-3 mb-1">Rooms Inventory Management</h3>
                <p class="text-white-50 small mb-0">Manage hotel room inventory, rates, floor plans, availability and active guest reservations.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="/hotel_website/admin/add-room.php" class="btn btn-add-room-header text-decoration-none d-inline-flex align-items-center gap-1.5" aria-label="Add a new hotel room">
                    <i class="fa-solid fa-plus"></i> Add Room
                </a>

                <button type="button" id="btnExportRooms" onclick="exportRoomsCSV()" class="btn btn-outline-white btn-sm rounded-pill px-3.5 py-2 font-semibold shadow-sm d-inline-flex align-items-center gap-1.5" style="border-color: rgba(255,255,255,0.3); color: #fff;">
                    <i class="fa-solid fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- 3. SEARCH & STATUS FILTER TOOLBAR CARD -->
    <div class="filter-toolbar-card mb-3 p-3 bg-white rounded-4 border shadow-sm">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-nowrap w-100 mb-3">
            
            <!-- Search Box (Expands to fill remaining space) -->
            <div class="search-input-wrapper flex-grow-1">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="roomSearchInput" class="form-control" placeholder="Search room...">
                <button type="button" id="searchClearBtn" class="search-clear-btn" title="Clear Search"><i class="fa-solid fa-xmark"></i></button>
            </div>

        </div>

        <!-- Status Filter Pills Row -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn room-status-pill active" data-status="all" onclick="filterByStatusPill('all', this)">
                All (<span class="room-status-count"><?php echo $totalCount; ?></span>)
            </button>
            <button type="button" class="btn room-status-pill" data-status="available" onclick="filterByStatusPill('available', this)">
                Available (<span class="room-status-count"><?php echo $availableCount; ?></span>)
            </button>
            <button type="button" class="btn room-status-pill" data-status="occupied" onclick="filterByStatusPill('occupied', this)">
                Occupied (<span class="room-status-count"><?php echo $occupiedCount; ?></span>)
            </button>
            <button type="button" class="btn room-status-pill" data-status="reserved" onclick="filterByStatusPill('reserved', this)">
                Reserved (<span class="room-status-count"><?php echo $reservedCount; ?></span>)
            </button>
            <button type="button" class="btn room-status-pill" data-status="cleaning" onclick="filterByStatusPill('cleaning', this)">
                Cleaning (<span class="room-status-count"><?php echo $cleaningCount; ?></span>)
            </button>
            <button type="button" class="btn room-status-pill" data-status="maintenance" onclick="filterByStatusPill('maintenance', this)">
                Maintenance (<span class="room-status-count"><?php echo $maintCount; ?></span>)
            </button>
        </div>
    </div>

    <!-- 4. ROOMS TABLE INVENTORY CARD -->
    <div id="roomsListView" class="table-card mb-4" style="display: block;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3" style="width: 40px;" onclick="event.stopPropagation();">
                            <input type="checkbox" id="selectAllRoomsCheckbox" class="form-check-input" onclick="event.stopPropagation(); toggleSelectAllRooms(this);" title="Select All Rooms">
                        </th>
                        <th class="ps-2" style="width: 110px;">ROOM NO</th>
                        <th>TYPE</th>
                        <th>PRICE/NIGHT</th>
                        <th>STATUS</th>
                        <th>CAPACITY</th>
                        <th>FLOOR</th>
                        <th class="text-end pe-4" style="width: 140px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="roomsTableBody">
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $r): 
                            $jsonRoom = htmlspecialchars(json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            $statusClass = 'status-available';
                            $st = strtolower($r['status']);
                            if ($st === 'occupied') $statusClass = 'status-occupied';
                            elseif ($st === 'reserved') $statusClass = 'status-reserved';
                            elseif ($st === 'maintenance') $statusClass = 'status-maintenance';
                            elseif ($st === 'cleaning') $statusClass = 'status-cleaning';
                            $imgPath = $r['image'] ?: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1000&q=80';
                            $capText = $r['capacity'] . ' guest' . ($r['capacity'] > 1 ? 's' : '');
                        ?>
                            <tr class="room-table-row" 
                                data-id="<?php echo $r['room_id']; ?>" 
                                data-room-no="<?php echo htmlspecialchars($r['room_no']); ?>"
                                data-type-id="<?php echo $r['room_type_id']; ?>"
                                data-type-name="<?php echo htmlspecialchars($r['type_name']); ?>"
                                data-price="<?php echo $r['price']; ?>"
                                data-capacity="<?php echo $r['capacity']; ?>"
                                data-floor="<?php echo htmlspecialchars($r['floor'] ?? 'Floor 1'); ?>"
                                data-bed-type="<?php echo htmlspecialchars($r['bed_type'] ?? 'King Bed'); ?>"
                                data-status="<?php echo htmlspecialchars($r['status']); ?>"
                                data-amenities="<?php echo htmlspecialchars($r['amenities']); ?>"
                                data-room="<?php echo $jsonRoom; ?>" 
                                onclick="openRoomDrawer(this, event)"
                                style="cursor: pointer;">
                                <td class="ps-3" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="form-check-input room-select-cb" value="<?php echo $r['room_id']; ?>" onclick="event.stopPropagation(); handleRoomCheckboxChange();" title="Select Room">
                                </td>
                                <td class="ps-2 fw-bold text-navy text-nowrap">
                                    #<?php echo htmlspecialchars($r['room_no']); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" class="rounded-3 shadow-2xs flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;" alt="Room">
                                        <span class="fw-bold text-navy text-nowrap"><?php echo htmlspecialchars($r['type_name']); ?></span>
                                    </div>
                                </td>
                                <td class="fw-bold text-primary text-nowrap">
                                    ₹<?php echo number_format($r['price']); ?>
                                </td>
                                <td class="text-nowrap" onclick="event.stopPropagation();">
                                    <select class="form-select form-select-sm status-select-badge <?php echo $statusClass; ?>"
                                            data-room-id="<?php echo $r['room_id']; ?>"
                                            onclick="event.stopPropagation();"
                                            title="Click to update room status">
                                        <option value="Available" <?php echo ($r['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="Occupied" <?php echo ($r['status'] === 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="Reserved" <?php echo ($r['status'] === 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                                        <option value="Cleaning" <?php echo ($r['status'] === 'Cleaning') ? 'selected' : ''; ?>>Cleaning</option>
                                        <option value="Maintenance" <?php echo ($r['status'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </td>
                                <td class="text-nowrap text-navy font-semibold small">
                                    <?php echo $capText; ?>
                                </td>
                                <td class="text-nowrap text-muted small font-semibold">
                                    <?php echo htmlspecialchars($r['floor'] ?? 'Floor 1'); ?>
                                </td>
                                <td class="text-end pe-4 text-nowrap" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-room="<?php echo $jsonRoom; ?>" onclick="event.stopPropagation(); confirmDeleteRoom(this, event)" title="Delete Room">
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

        <!-- Empty State List -->
        <div id="roomsEmptyStateList" class="text-center py-5 bg-white rounded-4 border" style="display: <?php echo empty($rooms) ? 'block' : 'none'; ?>;">
            <div class="mb-3 text-muted" style="font-size: 3rem;">
                <i class="fa-solid fa-hotel text-light"></i>
            </div>
            <h5 class="fw-bold text-navy mb-1">No Rooms Found</h5>
            <p class="text-muted small mb-3">There are currently no hotel rooms matching your search or applied filter criteria.</p>
    </div>

</div>

</main>

<!-- FLOATING BULK ACTION BAR -->
<div id="bulkActionsBar" class="bulk-actions-bar">
    <span id="bulkSelectedCount" class="font-bold small">0 rooms selected</span>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-success rounded-pill font-semibold px-3" onclick="submitBulkStatus('Available')">
            <i class="fa-solid fa-check me-1"></i> Mark Available
        </button>
        <button type="button" class="btn btn-sm btn-warning rounded-pill font-semibold px-3 text-dark" onclick="submitBulkStatus('Maintenance')">
            <i class="fa-solid fa-wrench me-1"></i> Maintenance
        </button>
        <button type="button" class="btn btn-sm btn-danger rounded-pill font-semibold px-3" onclick="submitBulkDelete()">
            <i class="fa-solid fa-trash-can me-1"></i> Delete Selected
        </button>
    </div>
</div>

<!-- =========================================================
     6. ROOM DETAIL SIDE DRAWER (OFFCANVAS)
   ========================================================= -->
<div class="offcanvas offcanvas-end offcanvas-room-drawer" tabindex="-1" id="roomDetailsDrawer">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title font-serif-title fw-bold text-white mb-0">
            <i class="fa-solid fa-hotel text-info me-2"></i>Room Specification Details
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body space-y-4 p-4 bg-light">
        
        <!-- Hero Image Box -->
        <div class="drawer-hero-img-box">
            <img id="drawer_hero_img" src="" class="drawer-hero-img" alt="Room Photo">
        </div>

        <!-- Room Title & Pricing -->
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 id="drawer_room_no" class="fw-bold text-navy mb-0">Room 101</h4>
                <div id="drawer_room_type" class="small text-primary font-semibold">Standard Room</div>
            </div>
            <div class="text-end">
                <div id="drawer_price" class="fs-4 fw-bold text-navy">₹0</div>
                <div id="drawer_status_badge" class="badge-status status-available">Available</div>
            </div>
        </div>

        <!-- Specifications Grid -->
        <div>
            <h6 class="font-serif-title fw-bold text-navy mb-2.5 border-bottom pb-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-sliders text-primary me-1.5"></i>Room Specifications
            </h6>
            <div class="row g-2">
                <div class="col-4">
                    <div class="spec-info-box text-center">
                        <div class="label">Floor Level</div>
                        <div id="drawer_spec_floor" class="value">Floor 1</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="spec-info-box text-center">
                        <div class="label">Bed Type</div>
                        <div id="drawer_spec_bed" class="value">King Bed</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="spec-info-box text-center">
                        <div class="label">Max Capacity</div>
                        <div id="drawer_spec_capacity" class="value">2 Guests</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Amenities -->
        <div>
            <h6 class="font-serif-title fw-bold text-navy mb-2.5 border-bottom pb-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-concierge-bell text-primary me-1.5"></i>Included Amenities
            </h6>
            <div id="drawer_amenities_list" class="d-flex flex-wrap gap-1.5"></div>
        </div>

        <!-- Overview Description -->
        <div>
            <h6 class="font-serif-title fw-bold text-navy mb-2 border-bottom pb-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-align-left text-primary me-1.5"></i>Overview &amp; Features
            </h6>
            <p id="drawer_description" class="small text-muted mb-0 leading-relaxed"></p>
        </div>

        <!-- Current Booking Card -->
        <div>
            <h6 class="font-serif-title fw-bold text-navy mb-2.5 border-bottom pb-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-user-check text-primary me-1.5"></i>Current Active Reservation
            </h6>
            <div id="drawer_booking_card" class="current-booking-card"></div>
        </div>

        <!-- Drawer Footer Action Buttons -->
        <div class="pt-3 border-top d-flex flex-wrap gap-2">
            <button type="button" id="drawerEditBtn" class="btn btn-primary rounded-3 flex-grow-1 font-semibold">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Room
            </button>
            <button type="button" id="drawerBookingsBtn" class="btn btn-outline-primary rounded-3 font-semibold">
                <i class="fa-solid fa-calendar-check"></i> Bookings
            </button>
            <button type="button" id="drawerStatusBtn" class="btn btn-outline-warning rounded-3 font-semibold text-dark">
                <i class="fa-solid fa-arrows-rotate"></i> Status
            </button>
            <button type="button" id="drawerDeleteBtn" class="btn btn-outline-danger rounded-3 font-semibold">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </div>

    </div>
</div>

<!-- =========================================================
     7. ADD NEW ROOM MODAL
   ========================================================= -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-plus text-primary me-2"></i>Add New Hotel Room
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_room">
                <div class="modal-body space-y-3 pt-3">
                    
                    <!-- Photos Upload Section -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label font-bold small text-navy mb-1">
                            <i class="fa-solid fa-images text-primary me-1.5"></i> UPLOAD ROOM PHOTOS (SELECT MULTIPLE)
                        </label>
                        <div class="extra-small text-muted mb-2">Allowed: JPG, PNG, WEBP, GIF (Max 5MB each)</div>
                        <input type="file" name="room_photos[]" id="addRoomPhotoInput" class="form-control rounded-3 font-semibold mb-2 bg-white" accept="image/*" multiple onchange="renderAddRoomNewPhotoPreviews(this)">
                        <div id="addRoomPhotosPreviewGallery" class="row g-2 mt-2" style="display: none;"></div>
                    </div>

                    <!-- Room Specifications -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ROOM NUMBER *</label>
                            <input type="text" name="room_no" required placeholder="e.g. 106" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ROOM CATEGORY / TYPE *</label>
                            <select name="room_type_id" class="form-select rounded-3 font-semibold">
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t['room_type_id']; ?>"><?php echo htmlspecialchars($t['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PRICE PER NIGHT (₹) *</label>
                            <input type="number" step="0.01" name="price" required placeholder="4500.00" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">MAX CAPACITY (GUESTS)</label>
                            <input type="number" name="capacity" value="2" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">FLOOR LEVEL</label>
                            <input type="text" name="floor" value="Floor 1" placeholder="e.g. Floor 2" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">BED TYPE</label>
                            <input type="text" name="bed_type" value="King Bed" placeholder="e.g. Double Queen Bed" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">STATUS *</label>
                            <select name="status" class="form-select rounded-3 font-semibold">
                                <option value="Available">Available</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Cleaning">Cleaning</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">AMENITIES (COMMA SEPARATED)</label>
                            <input type="text" name="amenities" value="Wi-Fi, Smart TV, Air Conditioning, Balcony, Mini Bar" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">DESCRIPTION</label>
                            <textarea name="description" rows="3" class="form-control rounded-3 font-semibold" placeholder="Enter room features, view, and bed details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-plus me-1.5"></i> Create Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     8. EDIT ROOM MODAL
   ========================================================= -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Room Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_room">
                <input type="hidden" name="room_id" id="edit_room_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">

                <div class="modal-body space-y-3 pt-3">
                    
                    <!-- Photos Management Section -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label font-bold small text-navy mb-1">
                            <i class="fa-solid fa-images text-primary me-1.5"></i> ROOM PHOTO GALLERY MANAGEMENT
                        </label>
                        <div class="extra-small text-muted mb-3">View existing room photos, remove unwanted images, or upload new photos to add to gallery.</div>

                        <!-- Current Saved Photos Container -->
                        <div class="mb-3">
                            <label class="form-label font-semibold extra-small text-muted mb-1.5">CURRENT SAVED PHOTOS</label>
                            <div id="editRoomExistingPhotosGallery" class="row g-2"></div>
                            <div id="editRoomNoPhotosNotice" class="text-muted extra-small fst-italic p-2 border rounded-3 bg-white" style="display: none;">No existing photos currently saved.</div>
                        </div>

                        <!-- Upload New Photos Field -->
                        <div class="pt-2 border-top">
                            <label class="form-label font-bold extra-small text-navy mb-1">
                                <i class="fa-solid fa-cloud-arrow-up text-primary me-1"></i> UPLOAD / ADD MORE PHOTOS
                            </label>
                            <input type="file" name="room_photos[]" id="editRoomPhotoInput" class="form-control rounded-3 font-semibold mb-2 bg-white" accept="image/*" multiple onchange="renderEditRoomNewPhotoPreviews(this)">
                            <div id="editRoomNewPhotosGallery" class="row g-2 mt-1" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Room Specifications -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ROOM NUMBER *</label>
                            <input type="text" name="room_no" id="edit_room_no" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">ROOM CATEGORY / TYPE *</label>
                            <select name="room_type_id" id="edit_room_type_id" class="form-select rounded-3 font-semibold">
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t['room_type_id']; ?>"><?php echo htmlspecialchars($t['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">PRICE PER NIGHT (₹) *</label>
                            <input type="number" step="0.01" name="price" id="edit_price" required class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">MAX CAPACITY (GUESTS)</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">FLOOR LEVEL</label>
                            <input type="text" name="floor" id="edit_floor" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">BED TYPE</label>
                            <input type="text" name="bed_type" id="edit_bed_type" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted">STATUS *</label>
                            <select name="status" id="edit_status" class="form-select rounded-3 font-semibold">
                                <option value="Available">Available</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Cleaning">Cleaning</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">AMENITIES (COMMA SEPARATED)</label>
                            <input type="text" name="amenities" id="edit_amenities" class="form-control rounded-3 font-semibold">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold small text-muted">DESCRIPTION</label>
                            <textarea name="description" id="edit_description" rows="3" class="form-control rounded-3 font-semibold"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">
                        <i class="fa-solid fa-floppy-disk me-1.5"></i> Update Room Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     9. CHANGE ROOM STATUS MODAL
   ========================================================= -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy">
                    <i class="fa-solid fa-arrows-rotate text-warning me-2"></i>Change Room Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_room_status">
                <input type="hidden" name="room_id" id="status_change_room_id">
                <div class="modal-body text-center py-3">
                    <p class="small text-muted mb-2">Select new status for <span id="status_change_room_no" class="fw-bold text-navy"></span>:</p>
                    <select name="status" id="status_select_val" class="form-select form-select-lg rounded-3 font-semibold text-center mb-3">
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Cleaning">Cleaning</option>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light rounded-3 font-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4">Save Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     10. DELETE ROOM CONFIRMATION MODAL
   ========================================================= -->
<!-- 10. ANIMATED DELETE ROOM CONFIRMATION MODAL -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0 shadow-lg" style="border-top: 4px solid #DC2626 !important;">
            <div class="modal-body text-center pt-4 pb-2">
                
                <!-- Animated Danger Aura Icon -->
                <div class="delete-modal-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <h4 class="font-serif-title fw-bold text-navy mb-1" id="deleteRoomModalTitle">Delete Hotel Room?</h4>
                <p class="small text-muted mb-2">Are you sure you want to permanently delete room record:</p>

                <!-- Item Preview Box -->
                <div class="delete-item-preview-card">
                    <i class="fa-solid fa-bed me-2 text-danger"></i>
                    <span id="delete_room_no_display">Room Record</span>
                </div>

                <div id="delete_booking_warning" class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning-emphasis extra-small rounded-3 text-start mb-3" style="display: none;"></div>

                <p class="extra-small text-muted mb-3">This action will remove room details from system inventory and cannot be undone.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete_room">
                    <input type="hidden" name="room_id" id="delete_room_id">
                    
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-light rounded-3 px-4 font-semibold" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-action-delete rounded-3 px-4 font-bold shadow-sm">
                            <i class="fa-solid fa-trash-can me-1.5"></i> Delete Room
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     11. VIEW ROOM BOOKINGS MODAL
   ========================================================= -->
<div class="modal fade" id="roomBookingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 p-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-serif-title fw-bold text-navy" id="roomBookingsTitle">
                    <i class="fa-solid fa-calendar-check text-primary me-2"></i>Bookings History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="roomBookingsList" class="space-y-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-3 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     12. IMAGE LIGHTBOX MODAL
   ========================================================= -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-dark text-white">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title font-serif-title fw-bold text-white" id="lightboxTitle">
                    <i class="fa-solid fa-image text-info me-2"></i>Room Image Preview
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                
                <!-- Interactive Zoom Toolbar Controls -->
                <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-3 bg-secondary bg-opacity-25 rounded-pill px-3 py-1.5 border border-secondary border-opacity-25 shadow-sm">
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="zoomLightboxModal(-0.25)" title="Zoom Out (-)">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </button>
                    <span id="lightboxZoomLevelText" class="extra-small font-semibold px-2 text-white-50" style="min-width: 48px;">100%</span>
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="zoomLightboxModal(0.25)" title="Zoom In (+)">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center ms-1" style="width: 32px; height: 32px;" onclick="resetLightboxZoom()" title="Reset Zoom">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-center overflow-hidden" style="min-height: 250px; max-height: 65vh;">
                    <img id="lightboxImg" src="" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 60vh; object-fit: contain; transition: transform 0.2s ease; cursor: grab;">
                </div>

                <div class="extra-small text-white-50 mt-3"><i class="fa-solid fa-circle-info me-1"></i> Use wheel scroll or buttons to Zoom In / Zoom Out photo</div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close Preview</button>
            </div>
        </div>
    </div>
</div>

<!-- HIDDEN BULK ACTION FORM -->
<form id="bulkActionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="bulk_type" id="bulk_action_type">
    <input type="hidden" name="bulk_status" id="bulk_action_status">
    <input type="hidden" name="bulk_ids" id="bulk_action_ids">
</form>

<!-- DYNAMIC TOAST CONTAINER -->
<div id="toastNotificationContainer" class="toast-container-custom"></div>

<!-- Image Preview Scripts for Modals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupMultiPhotoPreview(inputId, galleryId) {
        const input = document.getElementById(inputId);
        const gallery = document.getElementById(galleryId);
        if (input && gallery) {
            input.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                gallery.innerHTML = '';
                if (files.length > 0) {
                    gallery.style.display = 'flex';
                    files.forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            const col = document.createElement('div');
                            col.className = 'col-6 col-sm-4 col-md-3 position-relative';
                            col.innerHTML = `
                                <div class="card border rounded-3 overflow-hidden shadow-2xs h-100">
                                    <img src="${evt.target.result}" class="w-100 object-fit-cover" style="height: 85px;" alt="Photo ${index + 1}">
                                    <div class="p-1 text-center extra-small font-bold bg-white border-top text-navy">
                                        Photo ${index + 1} ${index === 0 ? '<span class="badge bg-primary ms-1">Cover</span>' : ''}
                                    </div>
                                </div>
                            `;
                            gallery.appendChild(col);
                        };
                        reader.readAsDataURL(file);
                    });
                } else {
                    gallery.style.display = 'none';
                }
            });
        }
    }

    setupMultiPhotoPreview('addRoomPhotoInput', 'addRoomPhotosPreviewGallery');
    setupMultiPhotoPreview('editRoomPhotoInput', 'editRoomPhotosPreviewGallery');
});
</script>

<!-- Custom Rooms Module JS -->
<script src="/hotel_website/assets/js/admin-rooms.js?v=20260928-3"></script>
<script>
if (new URLSearchParams(window.location.search).get('open') === 'add') {
    window.setTimeout(function () {
        if (typeof openAddRoomModal === 'function') openAddRoomModal();
    }, 0);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
