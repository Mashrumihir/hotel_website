<?php
require_once __DIR__ . '/../includes/header.php';
require_user();
?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h3 class="font-serif-title fw-bold text-navy mb-4">Notifications & Alerts</h3>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white space-y-3">
                <div class="p-3 bg-light-bg rounded-3 border d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-navy mb-1"><i class="fa-solid fa-circle-check text-success me-2"></i> Reservation Confirmed</h6>
                        <p class="small text-muted mb-0">Your booking for Room 102 (Executive Suite) has been confirmed.</p>
                    </div>
                    <span class="small text-muted">Today</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
