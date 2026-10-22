<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = getDBConnection();
$stmtTypes = $pdo->query("SELECT * FROM `room_types` ORDER BY `room_type_id` ASC");
$types = $stmtTypes->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Handle Multiple Files Upload for Room Photos
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
    } 
    // Single File Fallback
    elseif (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
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
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `rooms` WHERE `room_no` = :rno");
        $chk->execute(['rno' => $room_no]);
        if ($chk->fetchColumn() > 0) {
            $error = 'Room number ' . $room_no . ' already exists in inventory.';
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
            header('Location: /hotel_website/admin/rooms.php');
            exit;
        }
    } else {
        $error = 'Please fill out required fields (Room Number & Price).';
    }
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- Custom Admin Rooms CSS -->
<link rel="stylesheet" href="/hotel_website/assets/css/admin-rooms.css?v=<?php echo time(); ?>">

<main class="main-content">

<?php 
$page_title = 'Add New Hotel Room';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small text-muted">
                        <li class="breadcrumb-item"><a href="/hotel_website/admin/dashboard.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="/hotel_website/admin/rooms.php" class="text-decoration-none text-muted">Rooms</a></li>
                        <li class="breadcrumb-item active text-navy font-bold" aria-current="page">Add Room</li>
                    </ol>
                </nav>
                <h3 class="font-serif-title fw-bold text-navy mb-1">Add New Hotel Room</h3>
                <p class="small text-muted mb-0">Create new room entries, assign category types, prices, floor plans, and upload multiple room photos.</p>
            </div>
            <div>
                <a href="/hotel_website/admin/rooms.php" class="btn btn-outline-secondary btn-sm rounded-pill font-semibold px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1.5"></i> Back to Rooms
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-semibold mb-4 rounded-3 shadow-sm border-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-exclamation fs-5"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            
            <!-- LEFT COLUMN: Multi-Photo Upload & Preview Gallery -->
            <div class="col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="font-serif-title fw-bold text-navy mb-3">
                        <i class="fa-solid fa-images text-primary me-2"></i>Room Photos
                    </h5>
                    
                    <div class="p-3.5 bg-light rounded-4 border mb-3 text-center">
                        <label for="roomPhotosInput" class="form-label font-bold small text-navy mb-1 cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up text-primary fs-3 d-block mb-2"></i>
                            Select Multiple Room Photos
                        </label>
                        <div class="extra-small text-muted mb-3">Allowed: JPG, JPEG, PNG, WEBP, GIF (Max 5MB each)</div>
                        <input type="file" name="room_photos[]" id="roomPhotosInput" class="form-control form-control-sm rounded-3 font-semibold bg-white" accept="image/*" multiple>
                    </div>

                    <div id="roomPhotoFallback" class="p-4 bg-light rounded-3 border text-center text-muted extra-small">
                        <i class="fa-solid fa-image me-1 fs-4 d-block mb-1 text-primary"></i> No photos selected yet. Choose one or multiple files above to preview gallery.
                    </div>

                    <!-- Dynamic Multi-Photo Thumbnail Preview Grid -->
                    <div id="roomPhotosPreviewGallery" class="row g-2 mt-2" style="display: none;"></div>

                    <!-- Room Live Preview Box -->
                    <div class="mt-4 pt-3 border-top">
                        <span class="text-uppercase font-bold extra-small-label text-muted d-block mb-2">Live Room Badge Preview</span>
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <strong id="previewRoomNoDisplay" class="text-navy font-bold fs-6">Room 101</strong>
                                <span class="badge bg-success font-semibold small rounded-pill">Available</span>
                            </div>
                            <div id="previewRoomTypeDisplay" class="small text-primary font-semibold mb-2">Standard Room</div>
                            <div class="d-flex align-items-center justify-content-between text-muted extra-small">
                                <span id="previewPriceDisplay" class="fw-bold text-navy fs-6">₹4,500 <span class="fw-normal text-muted" style="font-size: 11px;">/ night</span></span>
                                <span id="previewFloorDisplay">Floor 1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Room Details Form Fields -->
            <div class="col-lg-7 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="font-serif-title fw-bold text-navy mb-4">
                        <i class="fa-solid fa-sliders text-primary me-2"></i>Room Details &amp; Configuration
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">ROOM NUMBER *</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-hashtag input-icon"></i>
                                <input type="text" name="room_no" id="inputRoomNo" required placeholder="e.g. 107" class="form-control rounded-3 font-semibold" onkeyup="updateLivePreview()">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">ROOM CATEGORY / TYPE *</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-layer-group input-icon"></i>
                                <select name="room_type_id" id="inputRoomType" class="form-select rounded-3 font-semibold" onchange="updateLivePreview()">
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo $t['room_type_id']; ?>"><?php echo htmlspecialchars($t['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">PRICE PER NIGHT (₹) *</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-indian-rupee-sign input-icon"></i>
                                <input type="number" step="0.01" name="price" id="inputPrice" required placeholder="4500.00" class="form-control rounded-3 font-semibold" onkeyup="updateLivePreview()">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">MAX CAPACITY (GUESTS)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-users input-icon"></i>
                                <input type="number" name="capacity" value="2" min="1" max="10" class="form-control rounded-3 font-semibold">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">FLOOR LEVEL</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-building input-icon"></i>
                                <input type="text" name="floor" id="inputFloor" value="Floor 1" placeholder="e.g. Floor 1, Penthouse, Executive Floor" class="form-control rounded-3 font-semibold" onkeyup="updateLivePreview()">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">BED TYPE</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-bed input-icon"></i>
                                <select name="bed_type" class="form-select rounded-3 font-semibold">
                                    <option value="King Bed">King Bed</option>
                                    <option value="Queen Bed">Queen Bed</option>
                                    <option value="Double Queen Bed">Double Queen Bed</option>
                                    <option value="Twin Beds">Twin Beds</option>
                                    <option value="Presidential Suite Bed">Presidential Suite Bed</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-bold small text-muted mb-1">ROOM STATUS</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-circle-info input-icon"></i>
                                <select name="status" class="form-select rounded-3 font-semibold">
                                    <option value="Available">Available</option>
                                    <option value="Reserved">Reserved</option>
                                    <option value="Occupied">Occupied</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Cleaning">Cleaning</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-bold small text-muted mb-1">AMENITIES (COMMA SEPARATED)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-list-check input-icon"></i>
                                <input type="text" name="amenities" value="Wi-Fi, Smart TV, Air Conditioning, Balcony, Mini Bar" class="form-control rounded-3 font-semibold">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font-bold small text-muted mb-1">DESCRIPTION</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-align-left input-icon"></i>
                                <textarea name="description" rows="3" class="form-control rounded-3 font-semibold" placeholder="Enter room features, view, and bed details..."></textarea>
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="/hotel_website/admin/rooms.php" class="btn btn-outline-primary rounded-3 font-semibold px-4 py-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 py-2 font-bold rounded-3 shadow-sm d-inline-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-plus"></i> <span>Save Room Inventory</span>
                            </button>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

</main>

<script>
function updateLivePreview() {
    const roomNo = document.getElementById('inputRoomNo')?.value || '101';
    const price = document.getElementById('inputPrice')?.value || '4500';
    const floor = document.getElementById('inputFloor')?.value || 'Floor 1';
    const typeSelect = document.getElementById('inputRoomType');
    const typeText = typeSelect ? typeSelect.options[typeSelect.selectedIndex].text : 'Standard Room';

    const pRoomNo = document.getElementById('previewRoomNoDisplay');
    const pPrice = document.getElementById('previewPriceDisplay');
    const pFloor = document.getElementById('previewFloorDisplay');
    const pType = document.getElementById('previewRoomTypeDisplay');

    if (pRoomNo) pRoomNo.textContent = 'Room ' + roomNo;
    if (pPrice) pPrice.innerHTML = '₹' + parseFloat(price || 0).toLocaleString('en-IN') + ' <span class="fw-normal text-muted" style="font-size: 11px;">/ night</span>';
    if (pFloor) pFloor.textContent = floor;
    if (pType) pType.textContent = typeText;
}

document.getElementById('roomPhotosInput')?.addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const gallery = document.getElementById('roomPhotosPreviewGallery');
    const fallback = document.getElementById('roomPhotoFallback');
    
    if (gallery) {
        gallery.innerHTML = '';
        if (files.length > 0) {
            gallery.style.display = 'flex';
            if (fallback) fallback.classList.add('d-none');
            
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-sm-4 position-relative';
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
            if (fallback) fallback.classList.remove('d-none');
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
