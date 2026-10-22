<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT p.*, b.booking_id, r.room_no FROM `payments` p JOIN `bookings` b ON p.booking_id = b.booking_id JOIN `rooms` r ON b.room_id = r.room_id WHERE b.customer_id = :uid ORDER BY p.payment_id DESC");
$stmt->execute(['uid' => $userId]);
$payments = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h3 class="font-serif-title fw-bold text-navy mb-4">Payment Transactions History</h3>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="table-responsive">
                    <table class="table align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Txn ID</th>
                                <th>Booking Ref</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Payment Mode &amp; Details</th>
                                <th>Date &amp; Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payments)): ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td class="fw-bold text-blue">#<?php echo htmlspecialchars($p['transaction_id'] ?? ('PAY-00' . $p['payment_id'])); ?></td>
                                        <td class="fw-bold text-navy">#HE-<?php echo $p['booking_id']; ?></td>
                                        <td>Room <?php echo htmlspecialchars($p['room_no']); ?></td>
                                        <td class="fw-bold text-navy"><?php echo format_price($p['amount']); ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($p['payment_mode']); ?></span>
                                            <?php if (!empty($p['payment_details'])): ?>
                                                <small class="d-block text-muted" style="font-size: 0.78rem;"><?php echo htmlspecialchars($p['payment_details']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($p['payment_date'])); ?></td>
                                        <td>
                                            <?php if ($p['payment_status'] === 'Paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending Check-In</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No payment transactions recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
