<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$pdo = getDBConnection();
$user = get_logged_user() ?? [];

$stmt = $pdo->prepare("SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = :id LIMIT 1");
$stmt->execute(['id' => $roomId]);
$room = $stmt->fetch();

if (!$room) {
    set_toast('Selected room not found.', 'danger');
    header('Location: /hotel_website/user/rooms.php');
    exit;
}

// Sample gallery photos array
$mainImg = !empty($room['image']) ? $room['image'] : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80';
$galleryPhotos = [
    $mainImg,
    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=800&q=80'
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_name = sanitize($_POST['guest_name'] ?? '');
    $guest_mobile = sanitize($_POST['guest_mobile'] ?? '');
    $guest_id_proof = sanitize($_POST['guest_id_proof'] ?? '');
    $guest_age = intval($_POST['guest_age'] ?? 0);
    $check_in = sanitize($_POST['check_in'] ?? '');
    $check_out = sanitize($_POST['check_out'] ?? '');
    $adults = intval($_POST['adults'] ?? 1);
    $children = intval($_POST['children'] ?? 0);
    $special_request = sanitize($_POST['special_request'] ?? '');

    $extra_names = $_POST['guest_names'] ?? [];
    $extra_ages = $_POST['guest_ages'] ?? [];
    $extra_mobiles = $_POST['guest_mobiles'] ?? [];
    $extra_proofs = $_POST['guest_id_proofs'] ?? [];

    $accompanying_details = [];
    if (is_array($extra_names)) {
        foreach ($extra_names as $i => $gName) {
            $gName = sanitize($gName);
            if (!empty($gName)) {
                $gAge = intval($extra_ages[$i] ?? 0);
                $gMobile = sanitize($extra_mobiles[$i] ?? '');
                $gProof = sanitize($extra_proofs[$i] ?? '');

                $detailStr = $gName;
                if ($gAge > 0) $detailStr .= " (Age: " . $gAge . "y)";
                if (!empty($gMobile)) $detailStr .= " [Ph: " . $gMobile . "]";
                if (!empty($gProof)) $detailStr .= " [ID: " . $gProof . "]";

                $accompanying_details[] = $detailStr;
            }
        }
    }
    
    $notes_parts = [];
    if ($guest_age > 0) {
        $notes_parts[] = "Primary Guest Age: " . $guest_age . " yrs";
    }
    if (!empty($accompanying_details)) {
        $notes_parts[] = "Accompanying Guests: " . implode('; ', $accompanying_details);
    }
    if (!empty($notes_parts)) {
        $combined_notes = implode(' | ', $notes_parts);
        $special_request = !empty($special_request) ? $special_request . " | " . $combined_notes : $combined_notes;
    }

    if (!empty($guest_name) && !empty($guest_mobile)) {
        $stmtUpd = $pdo->prepare("UPDATE `customers` SET `name` = :n, `mobile` = :m, `id_proof` = :idp WHERE `customer_id` = :uid");
        $stmtUpd->execute([
            'n' => $guest_name,
            'm' => $guest_mobile,
            'idp' => $guest_id_proof,
            'uid' => $_SESSION['user_id']
        ]);
        $_SESSION['user_name'] = $guest_name;
        $user = get_logged_user();
    }

    if (empty($check_in) || empty($check_out)) {
        $error = 'Please select valid check-in and check-out dates.';
    } elseif ($check_in >= $check_out) {
        $error = 'Check-out date must be after check-in date.';
    } else {
        if (!check_room_available($roomId, $check_in, $check_out)) {
            $error = 'Sorry, Room ' . htmlspecialchars($room['room_no']) . ' is already booked for the selected dates. Please choose different dates.';
        } else {
            $nights = calculate_nights($check_in, $check_out);
            if ($nights < 1) $nights = 1;
            $discount = ($nights >= 3) ? 1000 : 0;
            $breakdown = calculate_booking_total($room['price'], $nights, $discount, 18);

            $stmt = $pdo->prepare("INSERT INTO `bookings` (`customer_id`, `room_id`, `check_in`, `check_out`, `adults`, `children`, `special_request`, `booking_status`, `total_amount`) VALUES (:cid, :rid, :cin, :cout, :ad, :ch, :sr, 'Pending', :tot)");
            $stmt->execute([
                'cid' => $_SESSION['user_id'],
                'rid' => $roomId,
                'cin' => $check_in,
                'cout' => $check_out,
                'ad' => $adults,
                'ch' => $children,
                'sr' => $special_request,
                'tot' => $breakdown['grand_total']
            ]);

            $bookingId = $pdo->lastInsertId();
            set_toast('Reservation created! Please complete payment to confirm your booking.', 'success');
            header('Location: /hotel_website/user/payment.php?booking_id=' . $bookingId);
            exit;
        }
    }
}
?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <!-- Room Title & Header Bar -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                <div>
                    <span class="badge bg-primary px-3 py-1 rounded-pill small fw-bold mb-2">
                        <?php echo htmlspecialchars($room['type_name']); ?>
                    </span>
                    <h2 class="font-serif-title fw-bold text-navy mb-1">
                        Room <?php echo htmlspecialchars($room['room_no']); ?> &bull; Luxury Grand Hotel
                    </h2>
                    <p class="small text-muted mb-0">
                        <i class="fa-solid fa-location-dot text-primary me-1"></i> Rajkot, Gujarat, India &bull; ★ 4.8 (124 Verified Reviews)
                    </p>
                </div>
                <div class="fs-4 fw-bold text-blue">
                    <?php echo format_price($room['price']); ?> <small class="fs-6 text-muted font-normal">/ night</small>
                </div>
            </div>

            <!-- ROOM PHOTO GALLERY SHOWCASE -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white p-3">
                <div class="row g-2">
                    <div class="col-md-8">
                        <div class="gallery-main-container position-relative overflow-hidden rounded-3 shadow-sm" style="height: 376px;">
                            <img id="mainGalleryImage" src="<?php echo htmlspecialchars($galleryPhotos[0]); ?>" alt="Main Room Photo" onclick="openLightboxZoom(this.src)">
                            <span id="bookRoomPhotoCaption" class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill small font-semibold shadow-sm">
                                <i class="fa-solid fa-camera me-1.5 text-warning"></i> Featured Room View
                            </span>
                            <button type="button" onclick="openLightboxZoom(document.getElementById('mainGalleryImage').src)" class="position-absolute top-0 end-0 m-3 btn btn-light btn-sm rounded-circle p-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; z-index: 5;" title="Zoom Fullscreen">
                                <i class="fa-solid fa-expand text-navy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2">
                            <?php 
                            $captions = ['Featured Room View', 'Private Balcony View', 'Luxury Bathroom', 'King Suite Interior'];
                            foreach ($galleryPhotos as $idx => $photoUrl): 
                            ?>
                                <div class="col-6">
                                    <div class="gallery-thumb-item <?php echo $idx === 0 ? 'active-thumb' : ''; ?>" style="height: 184px;" onclick="swapGalleryPhoto('<?php echo htmlspecialchars($photoUrl); ?>', this, '<?php echo $captions[$idx] ?? 'Room View'; ?>')">
                                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Gallery Photo <?php echo $idx + 1; ?>">
                                        <div class="thumb-hover-overlay d-flex align-items-center justify-content-center text-white font-semibold small">
                                            <i class="fa-solid fa-magnifying-glass-plus me-1"></i> View
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <!-- ROOM DETAILS & PROPERTY SPECIFICATIONS CARD -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <div class="border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="font-serif-title fw-bold text-navy mb-1">
                            <i class="fa-solid fa-hotel text-primary me-2"></i> Room Overview &amp; Specifications
                        </h5>
                        <p class="small text-muted mb-0">Detailed dimensions, amenities, and room information.</p>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill small font-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i> Instant Confirmation
                    </span>
                </div>

                <!-- Property Specs Grid (Aligned in One Single Row) -->
                <div class="row g-2 g-md-3 mb-3 flex-nowrap overflow-x-auto pb-1">
                    <div class="col-3" style="min-width: 135px;">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <i class="fa-solid fa-ruler-combined text-primary fs-5 mb-1 d-block"></i>
                            <span class="d-block small text-muted font-medium">Property Size</span>
                            <strong class="text-navy small">50 × 50 sq.ft</strong>
                        </div>
                    </div>
                    <div class="col-3" style="min-width: 135px;">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <i class="fa-solid fa-user-group text-primary fs-5 mb-1 d-block"></i>
                            <span class="d-block small text-muted font-medium">Capacity</span>
                            <strong class="text-navy small">Up to <?php echo $room['capacity']; ?> Guests</strong>
                        </div>
                    </div>
                    <div class="col-3" style="min-width: 135px;">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <i class="fa-solid fa-bed text-primary fs-5 mb-1 d-block"></i>
                            <span class="d-block small text-muted font-medium">Bed Type</span>
                            <strong class="text-navy small"><?php echo htmlspecialchars($room['bed_type'] ?? '1 King Luxury Bed'); ?></strong>
                        </div>
                    </div>
                    <div class="col-3" style="min-width: 135px;">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <i class="fa-solid fa-bath text-primary fs-5 mb-1 d-block"></i>
                            <span class="d-block small text-muted font-medium">Bathroom</span>
                            <strong class="text-navy small">Ensuite Bathroom</strong>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <h6 class="fw-bold text-navy small mb-1">Description:</h6>
                    <p class="small text-muted mb-0" style="line-height: 1.6;"><?php echo htmlspecialchars($room['description']); ?></p>
                </div>

                <!-- Amenities Badges -->
                <div>
                    <h6 class="fw-bold text-navy small mb-2">Included Amenities:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-wifi text-primary me-1.5"></i> High-Speed Wi-Fi
                        </span>
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-snowflake text-primary me-1.5"></i> Climate Control AC
                        </span>
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-tv text-primary me-1.5"></i> Smart LED TV
                        </span>
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-mug-hot text-primary me-1.5"></i> Coffee &amp; Tea Maker
                        </span>
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-vault text-primary me-1.5"></i> Safety Locker
                        </span>
                        <span class="badge bg-light text-secondary border font-medium px-3 py-1.5 rounded-pill small">
                            <i class="fa-solid fa-utensils text-primary me-1.5"></i> 24/7 Room Service
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger font-semibold mb-4">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Booking Form -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <form method="POST" id="bookingForm" class="row g-3">
                            <div class="col-12 pb-2 border-bottom">
                                <h6 class="fw-bold text-navy font-serif-title mb-1">
                                    <i class="fa-solid fa-user-check me-2 text-primary"></i> Primary Guest Details
                                </h6>
                                <p class="small text-muted mb-0">Fill in guest details for this reservation.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">FULL NAME *</label>
                                <input type="text" name="guest_name" value="<?php echo htmlspecialchars($_GET['guest_name'] ?? ''); ?>" required class="form-control rounded-3 font-semibold" placeholder="Enter guest full name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">PHONE NUMBER *</label>
                                <input type="text" name="guest_mobile" value="<?php echo htmlspecialchars($_GET['guest_mobile'] ?? ''); ?>" required class="form-control rounded-3 font-semibold" placeholder="Enter phone number">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">AGE (YRS) *</label>
                                <input type="number" name="guest_age" min="18" max="120" value="" required class="form-control rounded-3 font-semibold" placeholder="e.g. 28">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">ID PROOF (Aadhar / Passport)</label>
                                <input type="text" name="guest_id_proof" value="" class="form-control rounded-3 font-semibold" placeholder="e.g. Aadhar / Passport No.">
                            </div>

                            <div class="col-12 pt-3 pb-2 border-bottom">
                                <h6 class="fw-bold text-navy font-serif-title mb-1">
                                    <i class="fa-solid fa-calendar-days me-2 text-primary"></i> Stay Dates &amp; Occupancy
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">CHECK-IN DATE *</label>
                                <input type="date" id="cin" name="check_in" value="<?php echo htmlspecialchars($_GET['check_in'] ?? date('Y-m-d')); ?>" required class="form-control rounded-3 font-semibold" onchange="recalcPricing()">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">CHECK-OUT DATE *</label>
                                <input type="date" id="cout" name="check_out" value="<?php echo htmlspecialchars($_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'))); ?>" required class="form-control rounded-3 font-semibold" onchange="recalcPricing()">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">ADULTS *</label>
                                <select id="adultsSelect" name="adults" required class="form-select rounded-3 font-semibold" onchange="updateGuestFields(); recalcPricing();">
                                    <option value="1" <?php echo (($_GET['guests'] ?? '1') === '1') ? 'selected' : ''; ?>>1 Adult</option>
                                    <option value="2" <?php echo (($_GET['guests'] ?? '') === '2') ? 'selected' : ''; ?>>2 Adults</option>
                                    <option value="3" <?php echo (($_GET['guests'] ?? '') === '3') ? 'selected' : ''; ?>>3 Adults</option>
                                    <option value="4" <?php echo (($_GET['guests'] ?? '') === '4') ? 'selected' : ''; ?>>4 Adults</option>
                                    <option value="5" <?php echo (($_GET['guests'] ?? '') === '5') ? 'selected' : ''; ?>>5 Adults</option>
                                    <option value="6" <?php echo (($_GET['guests'] ?? '') === '6') ? 'selected' : ''; ?>>6 Adults (Group Stay)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold small text-muted">CHILDREN</label>
                                <select id="childrenSelect" name="children" class="form-select rounded-3 font-semibold" onchange="updateGuestFields(); recalcPricing();">
                                    <option value="0" selected>0 Children</option>
                                    <option value="1">1 Child</option>
                                    <option value="2">2 Children</option>
                                    <option value="3">3 Children</option>
                                    <option value="4">4 Children</option>
                                </select>
                            </div>

                            <!-- Dynamic Accompanying Guests Input Section -->
                            <div class="col-12" id="additionalGuestsContainer" style="display: none;">
                                <div class="p-3 rounded-4 bg-light border shadow-sm">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label font-bold small text-navy mb-0">
                                            <i class="fa-solid fa-users text-primary me-1.5"></i> ACCOMPANYING GUESTS DETAILS
                                        </label>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3 rounded-pill font-semibold small" onclick="addManualGuestField()">
                                            <i class="fa-solid fa-plus me-1"></i> Add Guest Field
                                        </button>
                                    </div>
                                    <p class="small text-muted mb-3" style="font-size: 0.8rem;">Enter full names of all additional guests staying with you.</p>
                                    <div id="guestFieldsList" class="row g-2">
                                        <!-- Dynamic inputs inserted via updateGuestFields() -->
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 pt-3">
                                <button type="submit" class="btn btn-luxury-primary w-100 shadow py-2.5 font-bold">
                                    Confirm Reservation &amp; Proceed to Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Live Dynamic Pricing Calculator Box (Sticky Layout) -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-deep-navy text-white position-sticky" style="top: 85px; z-index: 10;">
                        <div class="d-flex align-items-center justify-content-between border-bottom border-secondary pb-3 mb-3">
                            <h5 class="font-serif-title fw-bold text-gold mb-0">
                                <i class="fa-solid fa-receipt me-2"></i> Pricing Breakdown
                            </h5>
                            <span class="badge bg-gold text-navy font-bold rounded-pill px-2.5 py-1 small">Live Summary</span>
                        </div>
                        
                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>Room Category:</span>
                            <span class="text-white font-semibold"><?php echo htmlspecialchars($room['type_name']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>Price Per Night:</span>
                            <span class="text-white font-semibold"><?php echo format_price($room['price']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>Number of Nights:</span>
                            <span id="calc_nights" class="text-white font-semibold">3 Nights</span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>Guest Occupancy:</span>
                            <span id="calc_occupancy" class="text-white font-semibold">2 Adults, 0 Children</span>
                        </div>

                        <div class="border-top border-secondary my-3"></div>

                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>Subtotal:</span>
                            <span id="calc_subtotal" class="text-white font-semibold">₹13,500.00</span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50 mb-2">
                            <span>GST Tax (18%):</span>
                            <span id="calc_gst" class="text-white font-semibold">₹2,430.00</span>
                        </div>
                        <div class="d-flex justify-content-between small text-success mb-3">
                            <span>Special Promo Discount:</span>
                            <span id="calc_discount" class="font-semibold">-₹1,000.00</span>
                        </div>

                        <div class="border-top border-secondary pt-3 mb-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold uppercase tracking-wider small">Total Amount:</span>
                            <span id="calc_total" class="fs-3 fw-bold text-gold font-serif-title">₹14,930.00</span>
                        </div>

                        <!-- Trust & Best Rate Guarantee Badge -->
                        <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 mt-2">
                            <div class="d-flex align-items-center gap-2 small text-white-50">
                                <i class="fa-solid fa-shield-halved text-success fs-5 flex-shrink-0"></i>
                                <div>
                                    <strong class="d-block text-white" style="font-size: 0.8rem;">Best Rate &amp; Instant Stay Guarantee</strong>
                                    <span style="font-size: 0.75rem;">Free cancellation up to 24h before check-in. No hidden booking fees.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gallery-thumb-box {
    transition: all 0.25s ease;
}
.gallery-thumb-box:hover {
    transform: scale(1.03);
}
.gallery-thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(11, 31, 58, 0.45);
    opacity: 0;
    transition: opacity 0.25s ease;
}
.gallery-thumb-box:hover .gallery-thumb-overlay {
    opacity: 1;
}
</style>

<script>
var pricePerNight = <?php echo (float)$room['price']; ?>;

function swapGalleryPhoto(imgUrl, thumbEl, caption) {
    const mainImg = document.getElementById('mainGalleryImage');
    const capEl = document.getElementById('bookRoomPhotoCaption');
    if (mainImg) {
        mainImg.style.transition = 'opacity 0.2s ease';
        mainImg.style.opacity = '0.3';
        setTimeout(() => {
            mainImg.src = imgUrl;
            mainImg.style.opacity = '1';
        }, 150);
    }
    if (capEl && caption) {
        capEl.innerHTML = '<i class="fa-solid fa-camera me-1.5 text-warning"></i> ' + caption;
    }
    document.querySelectorAll('.gallery-thumb-item').forEach(b => {
        b.classList.remove('active-thumb');
    });
    if (thumbEl) {
        thumbEl.classList.add('active-thumb');
    }
}

function recalcPricing() {
    var cinInput = document.getElementById('cin');
    var coutInput = document.getElementById('cout');
    
    var cinVal = cinInput ? cinInput.value : '';
    var coutVal = coutInput ? coutInput.value : '';
    
    var nights = 1;
    if (cinVal && coutVal) {
        var cin = new Date(cinVal);
        var cout = new Date(coutVal);
        if (cin && cout && cout > cin) {
            var diffTime = Math.abs(cout - cin);
            nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        } else if (cin && cout && cout.getTime() === cin.getTime()) {
            nights = 1;
        }
    }
    if (nights < 1) nights = 1;

    var adultsEl = document.getElementById('adultsSelect');
    var childrenEl = document.getElementById('childrenSelect');
    var occEl = document.getElementById('calc_occupancy');
    if (occEl && adultsEl && childrenEl) {
        var aVal = parseInt(adultsEl.value || '1', 10);
        if (isNaN(aVal) || aVal < 1) aVal = 1;
        var cVal = parseInt(childrenEl.value || '0', 10);
        if (isNaN(cVal) || cVal < 0) cVal = 0;
        occEl.innerText = aVal + ' Adult' + (aVal > 1 ? 's' : '') + ', ' + cVal + ' Child' + (cVal !== 1 ? 'ren' : '');
    }

    var subtotal = pricePerNight * nights;
    var gst = subtotal * 0.18;
    var discount = (nights >= 3) ? 1000.00 : 0.00;
    var total = Math.max(0, (subtotal + gst) - discount);

    var calcNightsEl = document.getElementById('calc_nights');
    var calcSubtotalEl = document.getElementById('calc_subtotal');
    var calcGstEl = document.getElementById('calc_gst');
    var calcDiscountEl = document.getElementById('calc_discount');
    var calcTotalEl = document.getElementById('calc_total');

    if (calcNightsEl) calcNightsEl.innerText = nights + ' Night' + (nights > 1 ? 's' : '');
    if (calcSubtotalEl) calcSubtotalEl.innerText = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    if (calcGstEl) calcGstEl.innerText = '₹' + gst.toLocaleString('en-IN', {minimumFractionDigits: 2});
    if (calcDiscountEl) calcDiscountEl.innerText = '-₹' + discount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    if (calcTotalEl) calcTotalEl.innerText = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
}

function renderGuestCard(title, isRemovable = false) {
    const col = document.createElement('div');
    col.className = 'col-12 mb-2';
    col.innerHTML = `
        <div class="card border rounded-3 p-3 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                <span class="fw-bold small text-navy">
                    <i class="fa-solid fa-user-tag text-primary me-1.5"></i> ${title}
                </span>
                ${isRemovable ? '<button type="button" class="btn-close small p-0" onclick="this.closest(\'.col-12\').remove()" title="Remove guest"></button>' : ''}
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label font-bold small text-muted mb-1">FULL NAME *</label>
                    <input type="text" name="guest_names[]" class="form-control rounded-3 font-semibold" placeholder="Enter Full Name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-bold small text-muted mb-1">AGE (YRS) *</label>
                    <input type="number" name="guest_ages[]" min="1" max="120" class="form-control rounded-3 font-semibold" placeholder="e.g. 25" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-bold small text-muted mb-1">PHONE NUMBER</label>
                    <input type="text" name="guest_mobiles[]" class="form-control rounded-3 font-semibold" placeholder="Enter Phone Number">
                </div>
                <div class="col-md-6">
                    <label class="form-label font-bold small text-muted mb-1">ID PROOF (Aadhar / Passport)</label>
                    <input type="text" name="guest_id_proofs[]" class="form-control rounded-3 font-semibold" placeholder="e.g. Aadhar / Passport No.">
                </div>
            </div>
        </div>
    `;
    return col;
}

function updateGuestFields() {
    const adultsEl = document.getElementById('adultsSelect');
    const childrenEl = document.getElementById('childrenSelect');
    const container = document.getElementById('additionalGuestsContainer');
    const list = document.getElementById('guestFieldsList');
    if (!adultsEl || !childrenEl || !container || !list) return;

    const adultsCount = parseInt(adultsEl.value) || 1;
    const childrenCount = parseInt(childrenEl.value) || 0;

    list.innerHTML = '';

    for (let i = 2; i <= adultsCount; i++) {
        list.appendChild(renderGuestCard(`Adult ${i} Details`));
    }

    for (let j = 1; j <= childrenCount; j++) {
        list.appendChild(renderGuestCard(`Child ${j} Details`));
    }

    if (adultsCount > 1 || childrenCount > 0) {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function addManualGuestField() {
    const container = document.getElementById('additionalGuestsContainer');
    const list = document.getElementById('guestFieldsList');
    if (!container || !list) return;
    container.style.display = 'block';

    const currentCount = list.children.length + 1;
    list.appendChild(renderGuestCard(`Extra Guest ${currentCount} Details`, true));
}

document.addEventListener('DOMContentLoaded', function() {
    recalcPricing();
    updateGuestFields();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>