<?php
require_once __DIR__ . '/includes/header.php';

// Fetch Logged-in Customer Info for Auto-filling forms
$loggedInCustomer = null;
if (is_user_logged_in() && isset($_SESSION['user_id'])) {
    $pdoUser = getDBConnection();
    if ($pdoUser) {
        $stmtUser = $pdoUser->prepare("SELECT * FROM `customers` WHERE `customer_id` = :uid LIMIT 1");
        $stmtUser->execute(['uid' => $_SESSION['user_id']]);
        $loggedInCustomer = $stmtUser->fetch();
    }
}

// Handle Contact Form Submission to MySQL Database
$contactSuccess = false;
$contactError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    $cName = sanitize($_POST['name'] ?? '');
    $cEmail = sanitize($_POST['email'] ?? '');
    $cPhone = sanitize($_POST['phone'] ?? '');
    $cSubject = sanitize($_POST['subject'] ?? 'General Inquiry');
    $cMessage = sanitize($_POST['message'] ?? '');

    if (empty($cName) || empty($cEmail) || empty($cMessage)) {
        $contactError = 'Please fill out all required fields (Name, Email, and Message).';
        set_toast($contactError, 'danger');
    } else {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $pdo->exec("ALTER TABLE `contact_messages` ADD COLUMN `subject` VARCHAR(255) DEFAULT NULL");
            } catch (Exception $e) {}

            $stmt = $pdo->prepare("INSERT INTO `contact_messages` (`name`, `email`, `phone`, `subject`, `message`) VALUES (:n, :e, :p, :s, :m)");
            $stmt->execute(['n' => $cName, 'e' => $cEmail, 'p' => $cPhone, 's' => $cSubject, 'm' => $cMessage]);
            $contactSuccess = true;
            set_toast('Thank you for contacting HotelEase! Our concierge will reach out to you shortly.', 'success');
        }
    }
}

// Fetch Dynamic Rooms & Room Types from Database
$pdo = getDBConnection();
$rooms = [];
$roomTypes = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id ORDER BY r.room_id ASC");
    $rooms = $stmt->fetchAll();

    $stmtTypes = $pdo->query("SELECT * FROM `room_types` ORDER BY `room_type_id` ASC");
    $roomTypes = $stmtTypes->fetchAll();
}
?>

<!-- 1. LUXURY STICKY NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-luxury sticky-top shadow-lg">
    <div class="container">
        <!-- Left: HotelEase Logo -->
        <a class="navbar-brand d-flex align-items-center py-1" href="/hotel_website/index.php">
            <img src="/hotel_website/uploads/logo.png" alt="HotelEase Logo" style="max-height: 48px; object-fit: contain;">
        </a>

        <!-- Mobile Toggler (Triggers Offcanvas Drawer on Mobile) -->
        <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Desktop Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold text-uppercase small gap-lg-1">
                <li class="nav-item"><a class="nav-link active" href="#hero">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">ABOUT</a></li>
                <li class="nav-item"><a class="nav-link" href="#rooms">ROOMS</a></li>
                <li class="nav-item"><a class="nav-link" href="#quick-booking">BOOKINGS</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">SERVICES</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">CONTACT</a></li>
            </ul>

            <!-- Right Action Buttons (Login State Detection) -->
            <div class="d-flex align-items-center gap-2">
                <?php if (is_user_logged_in()): ?>
                    <a href="/hotel_website/user/dashboard.php" class="btn btn-outline-light btn-sm rounded-pill font-bold px-3.5 py-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-gauge-high text-gold"></i> Dashboard
                    </a>
                    <a href="/hotel_website/user/profile.php" class="btn btn-royal btn-sm rounded-pill font-bold px-3.5 py-2 d-flex align-items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                    <a href="/hotel_website/user/logout.php" class="btn btn-outline-danger btn-sm rounded-pill font-bold px-3 py-2 d-flex align-items-center gap-1.5" title="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="/hotel_website/user/login.php" class="btn btn-outline-light btn-sm rounded-pill font-semibold px-3.5 py-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-user text-gold"></i> Login
                    </a>
                    <a href="/hotel_website/user/register.php" class="btn btn-royal btn-sm rounded-pill font-bold px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Offcanvas Drawer -->
<div class="offcanvas offcanvas-end rounded-start-4 border-0 text-white" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel" style="width: min(340px, 85vw); background-color: #071C32;">
    <div class="offcanvas-header border-bottom border-white border-opacity-10 p-3.5">
        <div class="d-flex align-items-center gap-2">
            <img src="/hotel_website/uploads/logo.png" alt="HotelEase Logo" style="max-height: 40px; object-fit: contain;">
        </div>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4 d-flex flex-column justify-content-between">
        <div>
            <span class="text-uppercase font-bold extra-small-label text-gold mb-3 d-block">Navigation Menu</span>
            <ul class="nav flex-column gap-2 mb-4 font-semibold">
                <li class="nav-item">
                    <a class="nav-link text-white rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 bg-white bg-opacity-10" href="#hero" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-house text-gold width-20"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 hover-white" href="#about" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-hotel text-gold width-20"></i> Hotels
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 hover-white" href="#rooms" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-bed text-gold width-20"></i> Rooms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 hover-white" href="#quick-booking" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-calendar-check text-gold width-20"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 hover-white" href="#services" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-bell-concierge text-gold width-20"></i> Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 rounded-3 px-3 py-2.5 d-flex align-items-center gap-3 hover-white" href="#contact" data-bs-dismiss="offcanvas">
                        <i class="fa-solid fa-envelope text-gold width-20"></i> Contact
                    </a>
                </li>
            </ul>
        </div>

        <div class="pt-3 border-top border-white border-opacity-10">
            <?php if (is_user_logged_in()): ?>
                <a href="/hotel_website/user/dashboard.php" class="btn btn-royal w-100 font-bold py-2.5 rounded-3 mb-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="/hotel_website/user/logout.php" class="btn btn-outline-danger w-100 font-bold py-2.5 rounded-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php else: ?>
                <a href="/hotel_website/user/login.php" class="btn btn-outline-light w-100 font-bold py-2.5 rounded-3 mb-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-circle-user text-gold"></i> Login
                </a>
                <a href="/hotel_website/user/register.php" class="btn btn-royal w-100 font-bold py-2.5 rounded-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 2. LUXURY HERO SECTION (Section 4 of Specs) -->
<section id="hero" class="hero-section position-relative d-flex align-items-center text-white overflow-hidden">
    <!-- Background Animation Video with Poster Fallback -->
    <video autoplay muted playsinline preload="auto" poster="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=2000&q=80" class="hero-bg-video" id="heroBackgroundVideo" aria-hidden="true" data-video-sources='["/hotel_website/assets/videos/hero-bg.mp4","/hotel_website/assets/videos/hero-room-0.mp4","/hotel_website/assets/videos/hero-room-9.mp4","/hotel_website/assets/videos/video_8.mp4"]'>
        <source src="/hotel_website/assets/videos/hero-bg.mp4" type="video/mp4">
    </video>
    
    <!-- Dark Gradient Overlay for readability -->
    <div class="hero-overlay"></div>

    <div class="container text-center position-relative z-2 hero-content-wrap" style="z-index: 3;">
        <div class="mb-3 fade-in-up">
            <span class="hero-badge">
                ✨ WELCOME TO HOTELEASE
            </span>
        </div>
        <h1 class="font-serif-title display-title fw-bold mb-3 tracking-tight text-white fade-in-up delay-1" style="font-size: clamp(2.2rem, 5vw, 3.8rem); line-height: 1.15;">
            Luxury Stay. Effortless Experience.
        </h1>
        <p class="lead text-white max-w-2xl mx-auto mb-4 font-sans fs-5 font-light fade-in-up delay-2" style="max-width: 720px; color: rgba(255, 255, 255, 0.92);">
            Discover comfortable rooms, premium amenities and seamless hotel booking with HotelEase.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap fade-in-up delay-3">
            <a href="#rooms" class="btn btn-primary-blue btn-lg font-bold px-4 py-2.5 rounded-pill shadow-lg hover-scale">Explore Rooms</a>
            <a href="#quick-booking" class="btn btn-outline-white btn-lg rounded-pill font-bold px-4 py-2.5 hover-scale">Book Your Stay</a>
        </div>
    </div>
</section>

<!-- 3. HOTEL SEARCH / BOOKING BOX (Section 5 & 17 of Specs) -->
<section id="quick-booking" class="hero-booking-wrapper">
    <div class="container">
        <div class="joined-booking-bar fade-in-up delay-4 bg-white rounded-4 shadow-lg border p-3 p-lg-4" style="border-radius: 20px !important;">
            <form action="/hotel_website/user/rooms.php" method="GET" class="row g-3 align-items-end">
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-uppercase font-bold extra-small-label mb-1 d-block text-muted">
                        <i class="fa-solid fa-calendar-day text-primary-blue me-1.5"></i> CHECK IN
                    </label>
                    <input type="date" name="check_in" value="<?php echo date('Y-m-d'); ?>" class="form-control rounded-3 font-semibold text-navy">
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-uppercase font-bold extra-small-label mb-1 d-block text-muted">
                        <i class="fa-solid fa-calendar-check text-primary-blue me-1.5"></i> CHECK OUT
                    </label>
                    <input type="date" name="check_out" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" class="form-control rounded-3 font-semibold text-navy">
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label class="form-label text-uppercase font-bold extra-small-label mb-1 d-block text-muted">
                        <i class="fa-solid fa-users text-primary-blue me-1.5"></i> GUESTS
                    </label>
                    <select name="guests" class="form-select rounded-3 font-semibold text-navy" style="cursor: pointer;">
                        <option value="1">1 Guest</option>
                        <option value="2" selected>2 Guests</option>
                        <option value="3">3 Guests</option>
                        <option value="4">4+ Guests</option>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label class="form-label text-uppercase font-bold extra-small-label mb-1 d-block text-muted">
                        <i class="fa-solid fa-bed text-primary-blue me-1.5"></i> ROOM TYPE
                    </label>
                    <select name="room_type" class="form-select rounded-3 font-semibold text-navy" style="cursor: pointer;">
                        <option value="">All Room Types</option>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?php echo $rt['room_type_id']; ?>"><?php echo htmlspecialchars($rt['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-12">
                    <button type="submit" class="btn btn-primary-blue w-100 py-2.5 font-bold shadow-sm rounded-3 text-nowrap d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Search Rooms
                    </button>
                </div>

            </form>
        </div>
    </div>
</section>

<!-- 4. QUICK FEATURES / WHY CHOOSE HOTELEASE (Section 6 of Specs) -->
<section id="why-choose-us" class="py-5 mt-4">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Our Core Values</span>
            <h2 class="font-serif-title fs-1 fw-bold text-navy">Why Choose HotelEase?</h2>
            <p class="text-muted small mb-0">Designed for your comfort, safety, and seamless hotel booking experience.</p>
            <div class="mx-auto mt-2 bg-primary-blue" style="width: 50px; height: 3px; border-radius: 2px;"></div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 shadow-sm text-center border hover-lift h-100">
                    <div class="icon-circle bg-light-blue text-primary-blue rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-size: 26px; background-color: #EFF6FF;">
                        <i class="fa-solid fa-bed"></i>
                    </div>
                    <h5 class="fw-bold text-navy fs-5 mb-2">Best Rooms</h5>
                    <p class="small text-muted mb-0">Comfortable rooms for every type of traveler, designed with luxury bedding and amenities.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 shadow-sm text-center border hover-lift h-100">
                    <div class="icon-circle bg-light-blue text-primary-blue rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-size: 26px; background-color: #EFF6FF;">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold text-navy fs-5 mb-2">Easy Booking</h5>
                    <p class="small text-muted mb-0">Book your stay in just a few clicks with instant confirmation and transparent pricing.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 shadow-sm text-center border hover-lift h-100">
                    <div class="icon-circle bg-light-blue text-primary-blue rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-size: 26px; background-color: #EFF6FF;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h5 class="fw-bold text-navy fs-5 mb-2">Secure Payment</h5>
                    <p class="small text-muted mb-0">Safe and reliable booking process with encrypted payment processing and flexible payment methods.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white rounded-4 shadow-sm text-center border hover-lift h-100">
                    <div class="icon-circle bg-light-blue text-primary-blue rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-size: 26px; background-color: #EFF6FF;">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h5 class="fw-bold text-navy fs-5 mb-2">24/7 Support</h5>
                    <p class="small text-muted mb-0">We're always here to help. Professional front desk assistance and concierge services round-the-clock.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. FEATURED ROOMS SECTION (Section 7 & 8 of Specs) -->
<section id="rooms" class="py-5 bg-very-light-blue" style="background-color: #F8FAFC;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Accommodation</span>
            <h2 class="font-serif-title fs-1 fw-bold text-navy">Featured Rooms</h2>
            <p class="text-muted small mb-0">Explore our most popular rooms and luxury suites.</p>
            <div class="mx-auto mt-2 bg-primary-blue" style="width: 50px; height: 3px; border-radius: 2px;"></div>
        </div>

        <div class="row g-4">
            <?php if (!empty($rooms)): ?>
                <?php foreach ($rooms as $r): ?>
                    <?php 
                        $imageArray = !empty($r['image']) ? explode(',', $r['image']) : [];
                        $primaryImage = !empty($imageArray[0]) ? trim($imageArray[0]) : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=80';
                        $statusBadge = ($r['status'] === 'Available') ? 'bg-success' : (($r['status'] === 'Reserved') ? 'bg-primary' : 'bg-secondary');
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border rounded-4 overflow-hidden shadow-sm hover-lift h-100 bg-white">
                            <!-- Image Container with Hover Zoom & Badges -->
                            <div class="position-relative overflow-hidden" style="height: 230px; cursor: pointer;" onclick="openRoomGalleryModal('<?php echo htmlspecialchars($r['room_no']); ?>', '<?php echo htmlspecialchars($r['type_name']); ?>', '<?php echo htmlspecialchars($primaryImage); ?>')">
                                <img src="<?php echo htmlspecialchars($primaryImage); ?>" alt="Room <?php echo htmlspecialchars($r['room_no']); ?>" class="w-100 h-100 object-fit-cover" style="transition: transform 0.5s ease;">
                                
                                <!-- Status Badge (Top-Left) -->
                                <span class="position-absolute top-0 start-0 badge <?php echo $statusBadge; ?> text-white font-bold uppercase tracking-wider extra-small-label px-3 py-1.5 m-3 rounded-pill shadow-sm">
                                    <?php echo htmlspecialchars(strtoupper($r['status'] ?? 'AVAILABLE')); ?>
                                </span>

                                <!-- Price Badge (Top-Right) -->
                                <span class="position-absolute top-0 end-0 bg-navy text-white fw-bold px-3 py-1.5 m-3 rounded-pill small shadow-sm" style="background-color: #0B2545 !important;">
                                    <?php echo format_price($r['price']); ?> <span class="fw-normal text-white-50" style="font-size: 11px;">/ night</span>
                                </span>

                                <!-- Bottom Photo Count Trigger Badge -->
                                <span class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white px-2.5 py-1.5 rounded-pill small font-semibold shadow-sm" onclick="event.stopPropagation(); openRoomGalleryModal('<?php echo htmlspecialchars($r['room_no']); ?>', '<?php echo htmlspecialchars($r['type_name']); ?>', '<?php echo htmlspecialchars($primaryImage); ?>')">
                                    <i class="fa-solid fa-camera me-1 text-gold"></i> Photos
                                </span>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 d-flex flex-column justify-content-between flex-grow-1">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="small text-muted font-semibold"><i class="fa-solid fa-layer-group text-primary-blue me-1"></i> <?php echo htmlspecialchars($r['floor'] ?? 'Floor 1'); ?></span>
                                    </div>
                                    
                                    <h4 class="font-serif-title fw-bold text-navy fs-5 mb-2">Room <?php echo htmlspecialchars($r['room_no']); ?> — <?php echo htmlspecialchars($r['type_name']); ?></h4>
                                    <p class="small text-muted mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;"><?php echo htmlspecialchars($r['description'] ?? 'Comfortable luxury room equipped with premium furnishings, high-speed Wi-Fi, and en-suite facilities.'); ?></p>
                                    
                                    <!-- Amenities Pills -->
                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <span class="badge bg-ice-blue text-primary-blue font-semibold px-2.5 py-1.5 rounded-pill small" style="background-color: #EFF6FF;">
                                            <i class="fa-solid fa-bed me-1"></i> <?php echo htmlspecialchars($r['bed_type'] ?? 'King Bed'); ?>
                                        </span>
                                        <span class="badge bg-ice-blue text-primary-blue font-semibold px-2.5 py-1.5 rounded-pill small" style="background-color: #EFF6FF;">
                                            <i class="fa-solid fa-users me-1"></i> Max <?php echo $r['capacity']; ?> Guests
                                        </span>
                                        <span class="badge bg-ice-blue text-primary-blue font-semibold px-2.5 py-1.5 rounded-pill small" style="background-color: #EFF6FF;">
                                            <i class="fa-solid fa-wifi me-1"></i> Free Wi-Fi
                                        </span>
                                    </div>
                                </div>

                                <!-- Card Action Buttons -->
                                <div class="pt-3 border-top border-light d-flex gap-2">
                                    <a href="/hotel_website/user/book-room.php?room_id=<?php echo $r['room_id']; ?>" class="btn btn-outline-primary btn-sm w-50 rounded-pill font-bold py-2">
                                        View Details
                                    </a>
                                    <a href="/hotel_website/user/book-room.php?room_id=<?php echo $r['room_id']; ?>" class="btn btn-primary-blue btn-sm w-50 rounded-pill font-bold py-2 shadow-sm">
                                        Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted fs-5">No rooms currently listed in database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 6. HOTEL SERVICES SECTION (Section 9 of Specs) -->
<section id="services" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Resort Amenities</span>
            <h2 class="font-serif-title fs-1 fw-bold text-navy">Our Services</h2>
            <p class="text-muted small mb-0">Experience world-class hospitality and premium amenities at HotelEase.</p>
            <div class="mx-auto mt-2 bg-primary-blue" style="width: 50px; height: 3px; border-radius: 2px;"></div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-hotel"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">Luxury Rooms</h5>
                        <p class="small text-muted mb-0">Elegantly appointed rooms with plush bedding, climate control, and luxury en-suite bathrooms.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">Restaurant</h5>
                        <p class="small text-muted mb-0">Gourmet dining featuring international cuisine, buffet breakfast, and 24/7 in-room dining options.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-person-swimming"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">Swimming Pool</h5>
                        <p class="small text-muted mb-0">Rooftop temperature-controlled infinity pool with sun lounger deck and poolside bar.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-square-parking"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">Valet Parking</h5>
                        <p class="small text-muted mb-0">Complimentary 24-hour valet parking and secure indoor garage parking for all guests.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-wifi"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">Free Wi-Fi</h5>
                        <p class="small text-muted mb-0">High-speed fiber optic Wi-Fi internet coverage in all guest rooms, lobby, and resort facilities.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex gap-3 align-items-start">
                    <div class="rounded-circle text-primary-blue d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #EFF6FF; font-size: 24px;">
                        <i class="fa-solid fa-bell-concierge"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy fs-5 mb-1.5">24/7 Room Service</h5>
                        <p class="small text-muted mb-0">Round-the-clock room service, housekeeping, and dedicated concierge desk assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. ABOUT HOTELEASE SECTION (Section 10 of Specs) -->
<section id="about" class="py-5" style="background-color: #F8FAFC;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1000&q=80" class="w-100 shadow-lg rounded-4 border" alt="HotelEase Resort">
                    <div class="position-absolute bottom-0 end-0 text-white p-4 rounded-4 shadow m-4 text-center border" style="background-color: #0B2545;">
                        <span class="display-6 fw-bold text-white d-block">4.9 / 5</span>
                        <span class="small uppercase tracking-wider text-white-50 font-semibold">Guest Rating</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">About HotelEase</span>
                <h2 class="font-serif-title fs-1 fw-bold text-navy mb-3">Luxury Stay. Effortless Experience.</h2>
                <p class="text-muted leading-relaxed mb-4" style="font-size: 1.05rem;">
                    HotelEase provides a simple and comfortable way to discover rooms, manage reservations and enjoy a seamless hotel experience. Whether you are traveling for executive business or a family vacation, our luxury suites and dedicated concierge team ensure an unforgettable stay.
                </p>
                <div class="row g-3 text-center mb-4">
                    <div class="col-6 col-sm-3">
                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                            <h3 class="fw-bold text-primary-blue mb-0">38+</h3>
                            <span class="small text-muted fw-semibold">Luxury Rooms</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                            <h3 class="fw-bold text-primary-blue mb-0">24/7</h3>
                            <span class="small text-muted fw-semibold">Front Desk</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                            <h3 class="fw-bold text-primary-blue mb-0">1200+</h3>
                            <span class="small text-muted fw-semibold">Happy Guests</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                            <h3 class="fw-bold text-primary-blue mb-0">4.9★</h3>
                            <span class="small text-muted fw-semibold">Star Rating</span>
                        </div>
                    </div>
                </div>
                <a href="#why-choose-us" class="btn btn-primary-blue font-bold px-4 py-2.5 rounded-pill shadow-sm">
                    Learn More <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 8. SPECIAL OFFER SECTION (Section 11 of Specs) -->
<section class="py-5 position-relative overflow-hidden text-white" style="background: linear-gradient(rgba(11, 37, 69, 0.78), rgba(11, 37, 69, 0.88)), url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;">
    <div class="container text-center py-5 position-relative z-2">
        <span class="badge bg-white text-navy font-bold uppercase tracking-wider px-3 py-1.5 rounded-pill mb-3 shadow-sm">Exclusive Experience</span>
        <h2 class="font-serif-title display-5 fw-bold mb-3 text-white">Plan Your Perfect Stay</h2>
        <p class="lead text-white-50 max-w-2xl mx-auto mb-4 font-light" style="max-width: 680px;">
            Enjoy comfortable rooms, premium facilities and effortless booking with HotelEase.
        </p>
        <a href="#rooms" class="btn btn-primary-blue btn-lg rounded-pill font-bold px-5 shadow-lg">
            Explore Rooms
        </a>
    </div>
</section>

<!-- 9. CUSTOMER REVIEWS (Section 12 of Specs) -->
<section id="reviews" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Guest Testimonials</span>
            <h2 class="font-serif-title fs-1 fw-bold text-navy">What Our Guests Say</h2>
            <p class="text-muted small mb-0">Read real experiences from our satisfied visitors.</p>
            <div class="mx-auto mt-2 bg-primary-blue" style="width: 50px; height: 3px; border-radius: 2px;"></div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-warning mb-3 fs-6">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-muted small leading-relaxed italic mb-4">
                            "Excellent room and very smooth booking experience. The front desk concierge was polite and check-in took less than two minutes."
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80" alt="Rahul Sharma" class="rounded-circle object-fit-cover" style="width: 46px; height: 46px;">
                        <div>
                            <h6 class="fw-bold text-navy mb-0">Rahul Sharma</h6>
                            <span class="extra-small-label text-muted">Verified Guest • Rajkot</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-warning mb-3 fs-6">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-muted small leading-relaxed italic mb-4">
                            "The ocean view suite was breathtaking and immaculate. Booking via HotelEase was seamless and hassle-free."
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80" alt="Priya Patel" class="rounded-circle object-fit-cover" style="width: 46px; height: 46px;">
                        <div>
                            <h6 class="fw-bold text-navy mb-0">Priya Patel</h6>
                            <span class="extra-small-label text-muted">Verified Guest • Ahmedabad</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-white shadow-sm hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-warning mb-3 fs-6">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-muted small leading-relaxed italic mb-4">
                            "Highly recommended for business travel! Ultra high-speed Wi-Fi, great food, and instant check-out assistance."
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80" alt="Amit Verma" class="rounded-circle object-fit-cover" style="width: 46px; height: 46px;">
                        <div>
                            <h6 class="fw-bold text-navy mb-0">Amit Verma</h6>
                            <span class="extra-small-label text-muted">Executive Guest • Mumbai</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 10. FREQUENTLY ASKED QUESTIONS (Section 13 of Specs) -->
<section id="faq" class="py-5" style="background-color: #F8FAFC;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Help & Guidance</span>
            <h2 class="font-serif-title fs-1 fw-bold text-navy">Frequently Asked Questions</h2>
            <p class="text-muted small mb-0">Everything you need to know about booking with HotelEase.</p>
            <div class="mx-auto mt-2 bg-primary-blue" style="width: 50px; height: 3px; border-radius: 2px;"></div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion accordion-custom shadow-sm rounded-4 overflow-hidden border" id="faqAccordion">
                    
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button font-bold text-navy fs-6 py-3.5" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                                <i class="fa-solid fa-circle-question text-primary-blue me-2.5"></i> How do I book a room?
                            </button>
                        </h2>
                        <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted leading-relaxed small">
                                Booking a room with HotelEase is simple: select your check-in and check-out dates in our search bar, choose your preferred room from our available options, click <strong>Book Now</strong>, and follow the simple checkout steps to confirm your reservation.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed font-bold text-navy fs-6 py-3.5" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                <i class="fa-solid fa-circle-question text-primary-blue me-2.5"></i> Can I cancel my booking?
                            </button>
                        </h2>
                        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted leading-relaxed small">
                                Yes! You can easily cancel your booking through your user dashboard under <strong>My Bookings</strong>. Free cancellation is supported up to 24 hours prior to check-in.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed font-bold text-navy fs-6 py-3.5" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                <i class="fa-solid fa-circle-question text-primary-blue me-2.5"></i> What payment methods are available?
                            </button>
                        </h2>
                        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted leading-relaxed small">
                                We support multiple payment methods including Credit/Debit Cards, Net Banking, instant UPI payments (Google Pay, PhonePe, Paytm), and Cash upon Check-in.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="faqHeading4">
                            <button class="accordion-button collapsed font-bold text-navy fs-6 py-3.5" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                                <i class="fa-solid fa-circle-question text-primary-blue me-2.5"></i> How can I modify my reservation?
                            </button>
                        </h2>
                        <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted leading-relaxed small">
                                You can view and manage your reservation details in your account dashboard under <strong>My Bookings</strong>, or reach out to our concierge desk for date adjustments.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0">
                        <h2 class="accordion-header" id="faqHeading5">
                            <button class="accordion-button collapsed font-bold text-navy fs-6 py-3.5" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                                <i class="fa-solid fa-circle-question text-primary-blue me-2.5"></i> How do I contact HotelEase support?
                            </button>
                        </h2>
                        <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted leading-relaxed small">
                                You can fill out our concierge contact form below, call our desk directly at <strong><?php echo SITE_PHONE; ?></strong>, or send an email to <strong><?php echo SITE_EMAIL; ?></strong>.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- 11. CALL TO ACTION SECTION (Section 14 of Specs) -->
<section class="py-5 text-white" style="background-color: #0B2545;">
    <div class="container text-center py-4">
        <h2 class="font-serif-title display-6 fw-bold mb-3 text-white">Ready for a Comfortable Stay?</h2>
        <p class="lead text-white-50 max-w-2xl mx-auto mb-4 font-light" style="max-width: 650px;">
            Book your room with HotelEase today and experience luxury stay with an effortless experience.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="#rooms" class="btn btn-light btn-lg rounded-pill font-bold px-5 text-primary-blue shadow-lg">
                Explore Rooms
            </a>
            <a href="#contact" class="btn btn-outline-white btn-lg rounded-pill font-bold px-5">
                Contact Support
            </a>
        </div>
    </div>
</section>

<!-- 12. CONTACT SECTION WITH MYSQL STORAGE (Section 15 & 23 of Specs) -->
<section id="contact" class="py-5 bg-white">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-6">
                <span class="text-primary-blue font-bold uppercase tracking-wider small d-block mb-1">Reach Out</span>
                <h2 class="font-serif-title fs-1 fw-bold text-navy mb-4">Contact HotelEase Concierge</h2>
                
                <?php if ($contactSuccess): ?>
                    <div class="alert alert-success font-semibold mb-4 rounded-3 shadow-sm border-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                        <div>Thank you! Your message has been sent to our concierge team.</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($contactError)): ?>
                    <div class="alert alert-danger font-semibold mb-4 rounded-3 shadow-sm border-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
                        <div><?php echo htmlspecialchars($contactError); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php#contact" class="row g-3 needs-validation" novalidate>
                    <input type="hidden" name="action" value="contact">
                    <div class="col-md-6">
                        <label class="form-label font-bold small text-muted">YOUR NAME *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($loggedInCustomer['name'] ?? ''); ?>" required placeholder="e.g. John Doe" class="form-control rounded-3 font-semibold">
                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter your full name.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-bold small text-muted">EMAIL ADDRESS *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($loggedInCustomer['email'] ?? ''); ?>" required placeholder="e.g. john@example.com" class="form-control rounded-3 font-semibold">
                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter a valid email address.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-bold small text-muted">PHONE NUMBER</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($loggedInCustomer['mobile'] ?? ''); ?>" placeholder="e.g. +91 9876543210" class="form-control rounded-3 font-semibold">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-bold small text-muted">SUBJECT / REASON</label>
                        <input type="text" name="subject" placeholder="e.g. Room Inquiry / Special Request" class="form-control rounded-3 font-semibold">
                    </div>
                    <div class="col-12">
                        <label class="form-label font-bold small text-muted">MESSAGE *</label>
                        <textarea name="message" rows="4" required placeholder="Type your message or concierge inquiry here..." class="form-control rounded-3 font-semibold"></textarea>
                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter your message.
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-blue px-4 py-2.5 font-bold shadow-sm rounded-pill d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-6">
                <div class="text-white p-4 p-lg-5 rounded-4 h-100 d-flex flex-column justify-between shadow-lg position-relative overflow-hidden" style="background-color: #0B2545;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <h4 class="font-serif-title fw-bold text-white fs-3 mb-0">HotelEase Concierge</h4>
                            <div class="rounded-circle ms-1" style="width: 8px; height: 8px; background-color: #2563EB;"></div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3.5">
                            <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <span class="d-block small text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">HOTEL ADDRESS</span>
                                <strong class="text-white font-medium fs-6"><?php echo SITE_ADDRESS; ?></strong>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3.5">
                            <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <span class="d-block small text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">CONCIERGE DESK</span>
                                <strong class="text-white font-medium fs-6"><?php echo SITE_PHONE; ?></strong>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3.5">
                            <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <span class="d-block small text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">EMAIL RESERVATIONS</span>
                                <strong class="text-white font-medium fs-6"><?php echo SITE_EMAIL; ?></strong>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <span class="d-block small text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">GUEST TIMINGS</span>
                                <strong class="text-white font-medium fs-6">Check-in: <?php echo date('g:i A', strtotime(CHECKIN_TIME)); ?> | Check-out: <?php echo date('g:i A', strtotime(CHECKOUT_TIME)); ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php
                    $mapRaw = trim(SITE_MAP_IFRAME);
                    $embedSrc = '';

                    if (preg_match('/src=["\']([^"\']+)["\']/i', $mapRaw, $matches)) {
                        $embedSrc = $matches[1];
                    } elseif (!empty($mapRaw)) {
                        if (strpos($mapRaw, 'https://www.google.com/maps/embed') === 0 || strpos($mapRaw, 'output=embed') !== false) {
                            $embedSrc = $mapRaw;
                        } else {
                            $embedSrc = 'https://maps.google.com/maps?q=' . urlencode($mapRaw) . '&output=embed';
                        }
                    } else {
                        $embedSrc = 'https://maps.google.com/maps?q=' . urlencode(SITE_ADDRESS) . '&output=embed';
                    }
                    ?>
                    <div class="mt-4 rounded-3 overflow-hidden border border-white border-opacity-15 shadow-sm" style="height: 200px;">
                        <iframe src="<?php echo htmlspecialchars($embedSrc, ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
