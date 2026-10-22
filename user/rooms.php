<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();
$type_filter = isset($_GET['type']) ? intval($_GET['type']) : 0;
$query = "SELECT r.*, rt.type_name FROM `rooms` r JOIN `room_types` rt ON r.room_type_id = rt.room_type_id";
if ($type_filter > 0) {
    $query .= " WHERE r.room_type_id = " . $type_filter;
}
$query .= " ORDER BY r.price ASC";
$stmt = $pdo->query($query);
$rooms = $stmt->fetchAll();

$stmtTypes = $pdo->query("SELECT * FROM `room_types`");
$roomTypes = $stmtTypes->fetchAll();
$userId = $_SESSION['user_id'] ?? 0;
$userWishlistRoomIds = [];
if ($pdo && $userId > 0) {
    $stmtW = $pdo->prepare("SELECT `room_id` FROM `wishlist` WHERE `customer_id` = :uid");
    $stmtW->execute(['uid' => $userId]);
    $userWishlistRoomIds = $stmtW->fetchAll(PDO::FETCH_COLUMN);
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
<?php
$page_title = 'Search Rooms';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="font-serif-title fw-bold text-navy mb-1">Browse Luxury Rooms</h3>
            <p class="small text-muted mb-0">Select your preferred room to check live availability and book.</p>
        </div>
        <form method="GET" action="" class="d-flex align-items-center gap-2">
            <select name="type" class="form-select rounded-3 small font-semibold" onchange="this.form.submit()">
                <option value="0">All Room Types</option>
                <?php foreach ($roomTypes as $t): ?>
                    <option value="<?php echo $t['room_type_id']; ?>" <?php echo $type_filter == $t['room_type_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['type_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <?php foreach ($rooms as $r): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card room-card-modern border-0 h-100">
                    <div class="room-card-img-wrap" style="cursor: pointer;" onclick="openRoomGalleryModal('<?php echo htmlspecialchars($r['room_no']); ?>', '<?php echo htmlspecialchars($r['type_name']); ?>', '<?php echo htmlspecialchars($r['image']); ?>')">
                        <img src="<?php echo htmlspecialchars($r['image']); ?>" alt="Room Image">
                        <div class="room-card-img-overlay"></div>
                        <span class="room-type-pill">
                            <?php echo htmlspecialchars($r['type_name']); ?>
                        </span>
                        <?php $isFav = in_array($r['room_id'], $userWishlistRoomIds); ?>
                        <button type="button" 
                                onclick="toggleWishlistRoom(event, <?php echo $r['room_id']; ?>, this)" 
                                class="fav-btn-glass <?php echo $isFav ? 'text-danger' : ''; ?>" 
                                title="<?php echo $isFav ? 'Remove from favorites' : 'Save to favorites'; ?>"
                                data-room-id="<?php echo $r['room_id']; ?>"
                                data-saved="<?php echo $isFav ? '1' : '0'; ?>">
                            <i class="<?php echo $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart'; ?>"></i>
                        </button>
                        <span class="room-badge-bottom" onclick="event.stopPropagation(); openRoomGalleryModal('<?php echo htmlspecialchars($r['room_no']); ?>', '<?php echo htmlspecialchars($r['type_name']); ?>', '<?php echo htmlspecialchars($r['image']); ?>')">
                            <i class="fa-solid fa-camera me-1"></i> Photos
                        </span>
                        <div class="room-rating-pill">
                            <i class="fa-solid fa-star text-warning"></i> 5.0
                        </div>
                    </div>
                    <div class="p-4 d-flex flex-column justify-content-between flex-grow-1">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <a href="/hotel_website/user/book-room.php?id=<?php echo $r['room_id']; ?>" class="text-decoration-none">
                                    <h5 class="font-serif-title fw-bold text-navy mb-0">Room <?php echo htmlspecialchars($r['room_no']); ?></h5>
                                </a>
                                <span class="small text-muted font-medium"><i class="fa-solid fa-location-dot text-primary me-1"></i>Rajkot, Guj</span>
                            </div>
                            <p class="small text-muted mb-3 text-truncate-2" style="line-height: 1.5;"><?php echo htmlspecialchars($r['description']); ?></p>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                    <i class="fa-solid fa-ruler-combined text-primary me-1"></i> 50×50 sq.ft
                                </span>
                                <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                    <i class="fa-solid fa-wifi text-primary me-1"></i> Wi-Fi
                                </span>
                                <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                    <i class="fa-solid fa-user-group text-primary me-1"></i> Max <?php echo $r['capacity']; ?> Guests
                                </span>
                                <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                    <i class="fa-solid fa-snowflake text-primary me-1"></i> AC
                                </span>
                            </div>
                        </div>
                        <div class="pt-3 border-top mt-auto">
                            <div class="d-flex align-items-center justify-content-between mb-2.5">
                                <div>
                                    <span class="fs-4 fw-bold text-royal-blue font-serif-title"><?php echo format_price($r['price']); ?></span>
                                    <span class="small text-muted font-semibold">/ night</span>
                                </div>
                                <?php 
                                    $st = strtolower($r['status'] ?? 'available');
                                    $badgeClass = 'bg-success bg-opacity-10 text-success border-success';
                                    if ($st === 'reserved') $badgeClass = 'bg-warning bg-opacity-10 text-warning border-warning';
                                    elseif ($st === 'occupied') $badgeClass = 'bg-danger bg-opacity-10 text-danger border-danger';
                                    elseif ($st === 'maintenance' || $st === 'cleaning') $badgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?> border border-opacity-25 rounded-pill font-bold px-2.5 py-1 small">
                                    <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/hotel_website/user/book-room.php?room_id=<?php echo $r['room_id']; ?>" class="btn btn-outline-royal btn-sm w-50 rounded-pill font-bold py-2 text-center">
                                    View Details
                                </a>
                                <a href="/hotel_website/user/book-room.php?room_id=<?php echo $r['room_id']; ?>" class="btn btn-royal btn-sm w-50 rounded-pill font-bold py-2 shadow-sm text-center">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
