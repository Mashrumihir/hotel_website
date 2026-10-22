<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'] ?? 0;
$wishlistRooms = [];
if ($pdo && $userId > 0) {
    $stmt = $pdo->prepare("SELECT w.wishlist_id, r.*, rt.type_name FROM `wishlist` w JOIN `rooms` r ON w.room_id = r.room_id JOIN `room_types` rt ON r.room_type_id = rt.room_type_id WHERE w.customer_id = :uid ORDER BY w.wishlist_id DESC");
    $stmt->execute(['uid' => $userId]);
    $wishlistRooms = $stmt->fetchAll();
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
<?php
$page_title = 'Favorites';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-serif-title fw-bold text-navy mb-1">My Saved Favorite Rooms</h3>
            <p class="small text-muted mb-0">Quickly access rooms you have saved to your favorites wishlist.</p>
        </div>
    </div>

    <?php if (!empty($wishlistRooms)): ?>
        <div class="row g-4 mb-4" id="wishlistGridContainer">
            <?php foreach ($wishlistRooms as $w): ?>
                <div class="col-md-6 col-xl-4" id="wishlist-card-<?php echo $w['wishlist_id']; ?>">
                    <div class="card room-card-modern border-0 h-100">
                        <div class="room-card-img-wrap" style="cursor: pointer;" onclick="openRoomGalleryModal('<?php echo htmlspecialchars($w['room_no']); ?>', '<?php echo htmlspecialchars($w['type_name']); ?>', '<?php echo htmlspecialchars($w['image']); ?>')">
                            <img src="<?php echo htmlspecialchars($w['image']); ?>" alt="Room Image">
                            <div class="room-card-img-overlay"></div>
                            <span class="room-type-pill">
                                <?php echo htmlspecialchars($w['type_name']); ?>
                            </span>
                            <button type="button" 
                                    onclick="removeWishlistItem(event, <?php echo $w['room_id']; ?>, 'wishlist-card-<?php echo $w['wishlist_id']; ?>')" 
                                    class="fav-btn-glass text-danger" 
                                    title="Remove from favorites">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                            <span class="room-badge-bottom" onclick="event.stopPropagation(); openRoomGalleryModal('<?php echo htmlspecialchars($w['room_no']); ?>', '<?php echo htmlspecialchars($w['type_name']); ?>', '<?php echo htmlspecialchars($w['image']); ?>')">
                                <i class="fa-solid fa-camera me-1"></i> Photos
                            </span>
                            <div class="room-rating-pill">
                                <i class="fa-solid fa-star text-warning"></i> 5.0
                            </div>
                        </div>
                        <div class="p-4 d-flex flex-column justify-content-between flex-grow-1">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h5 class="font-serif-title fw-bold text-navy mb-0">Room <?php echo htmlspecialchars($w['room_no']); ?></h5>
                                    <span class="small text-muted font-medium"><i class="fa-solid fa-location-dot text-primary me-1"></i>Rajkot, Guj</span>
                                </div>
                                <p class="small text-muted mb-3 text-truncate-2" style="line-height: 1.5;"><?php echo htmlspecialchars($w['description']); ?></p>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                        <i class="fa-solid fa-ruler-combined text-primary me-1"></i> 50×50 sq.ft
                                    </span>
                                    <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                        <i class="fa-solid fa-wifi text-primary me-1"></i> Wi-Fi
                                    </span>
                                    <span class="badge bg-light text-secondary border font-medium px-2.5 py-1 rounded-pill small">
                                        <i class="fa-solid fa-snowflake text-primary me-1"></i> AC
                                    </span>
                                </div>
                            </div>
                            <div class="pt-3 border-top mt-auto">
                                <div class="d-flex align-items-center justify-content-between mb-2.5">
                                    <div>
                                        <span class="fs-4 fw-bold text-royal-blue font-serif-title"><?php echo format_price($w['price']); ?></span>
                                        <span class="small text-muted font-semibold">/ night</span>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill font-bold px-2.5 py-1 small">
                                        <i class="fa-solid fa-circle-check me-1"></i> Saved
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/hotel_website/user/book-room.php?room_id=<?php echo $w['room_id']; ?>" class="btn btn-outline-royal btn-sm w-50 rounded-pill font-bold py-2 text-center">
                                        View Details
                                    </a>
                                    <a href="/hotel_website/user/book-room.php?room_id=<?php echo $w['room_id']; ?>" class="btn btn-royal btn-sm w-50 rounded-pill font-bold py-2 shadow-sm text-center">
                                        Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
            <i class="fa-solid fa-heart-crack text-danger fs-1 mb-3 opacity-50"></i>
            <h4 class="fw-bold text-navy mb-2">No Saved Rooms Yet</h4>
            <p class="text-secondary small mb-4 max-w-md mx-auto">Explore our available luxury rooms and click the heart icon to save your favorite stays here.</p>
            <div>
                <a href="/hotel_website/user/rooms.php" class="btn btn-primary-blue px-4 py-2">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Explore Rooms
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>

<script>
function removeWishlistItem(e, roomId, cardId) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const cardEl = document.getElementById(cardId);
    if (cardEl) {
        cardEl.style.transition = 'all 0.3s ease';
        cardEl.style.opacity = '0';
        cardEl.style.transform = 'scale(0.85)';
        setTimeout(() => {
            cardEl.remove();
            const container = document.getElementById('wishlistGridContainer');
            if (container && container.children.length === 0) {
                location.reload();
            }
        }, 300);
    }
    fetch('/hotel_website/api/realtime.php?action=toggle_wishlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'room_id=' + encodeURIComponent(roomId)
    })
    .then(res => res.json())
    .then(data => {
        if (typeof showDynamicToast === 'function') {
            showDynamicToast('Room removed from your saved list.', 'success');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
