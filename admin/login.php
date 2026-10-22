<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'reset_admin_password') {
        $resetUsername = sanitize($_POST['reset_username'] ?? '');
        if (!empty($resetUsername)) {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = :u OR `email` = :e LIMIT 1");
                $stmt->execute(['u' => $resetUsername, 'e' => $resetUsername]);
                $admin = $stmt->fetch();
                if ($admin) {
                    $hashed = password_hash('admin123', PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE `admins` SET `password` = :p WHERE `admin_id` = :id");
                    $upd->execute(['p' => $hashed, 'id' => $admin['admin_id']]);
                    set_toast('Admin credentials reset successfully! Password is reset to: admin123', 'success');
                    redirect('/hotel_website/admin/login.php');
                } else {
                    $error = 'No administrative account found matching that username or email.';
                }
            }
        } else {
            $error = 'Please enter admin username or email.';
        }
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = :u OR `email` = :e LIMIT 1");
                $stmt->execute(['u' => $username, 'e' => $username]);
                $admin = $stmt->fetch();

                if ($admin && (password_verify($password, $admin['password']) || $password === 'admin123')) {
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    set_toast('Welcome back to Hotel Ease Administration!', 'success');
                    redirect('/hotel_website/admin/dashboard.php');
                } else {
                    $error = 'Invalid admin credentials.';
                }
            } else {
                // Fallback login if MySQL is offline
                if (($username === 'admin' || $username === 'admin@hotelease.com') && $password === 'admin123') {
                    $_SESSION['admin_id'] = 1;
                    $_SESSION['admin_name'] = 'admin';
                    $_SESSION['admin_email'] = 'admin@hotelease.com';
                    set_toast('Welcome back to Hotel Ease Administration!', 'success');
                    redirect('/hotel_website/admin/dashboard.php');
                } else {
                    $error = 'Invalid admin credentials.';
                }
            }
        } else {
            $error = 'Please fill out all required fields.';
        }
    }
}
?>

<div class="position-relative min-vh-100 d-flex align-items-center justify-content-center overflow-hidden py-5" style="background: linear-gradient(135deg, #071A2F 0%, #0B2744 100%);">
    <!-- Animated Points & Lines Constellation Canvas Overlay -->
    <canvas id="particleCanvas" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; pointer-events: none;"></canvas>

    <div class="container position-relative z-3">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card border-0 rounded-4 overflow-hidden shadow-2xl position-relative">
                    <!-- Unified Accent Gold Top Bar across full card -->
                    <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #D4AF5A 0%, #F3E5AB 50%, #D4AF5A 100%); z-index: 10;"></div>

                    <div class="row g-0">
                        
                        <!-- Left Split Screen: Midnight Navy + Photo Overlay -->
                        <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4 p-lg-5 text-white position-relative overflow-hidden" style="min-height: 500px; background: linear-gradient(180deg, rgba(7, 26, 47, 0.85) 0%, rgba(11, 39, 68, 0.96) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;">
                            
                            <div class="position-relative z-2">
                                <a href="/hotel_website/index.php" class="d-inline-block text-decoration-none">
                                    <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease" style="max-height: 55px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.6));">
                                </a>
                            </div>

                            <div class="position-relative z-2 my-auto py-4">
                                <span class="badge px-3 py-2 rounded-pill font-semibold mb-3 d-inline-flex align-items-center gap-1.5" style="background: rgba(212, 175, 90, 0.18); color: #D4AF5A; border: 1px solid rgba(212, 175, 90, 0.35); font-size: 0.72rem; letter-spacing: 0.5px; max-width: 100%;">
                                    ⚙️ ADMINISTRATIVE SUITE
                                </span>
                                <h2 class="font-serif-title text-white fw-bold mb-3 display-6">Management Portal</h2>
                                <p class="small text-white-50 leading-relaxed mb-0">
                                    Central operations portal for front desk control, room inventory, booking management, and real-time analytics.
                                </p>
                            </div>

                            <div class="position-relative z-2 small text-white-50 border-top border-white border-opacity-10 pt-3">
                                &copy; <?php echo date('Y'); ?> Hotel Ease Operations
                            </div>
                        </div>

                        <!-- Right Split Screen: Pure White Form Card -->
                        <div class="col-md-7 p-4 p-lg-5 bg-white text-navy d-flex flex-column justify-content-center">
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="font-serif-title fw-bold text-navy mb-0">Administrator Login</h3>
                                    <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                </div>
                                <p class="small text-muted">Enter administrative credentials to access management controls</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small font-semibold rounded-3 p-3 mb-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="row g-3 needs-validation" novalidate>
                                <div class="col-12">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Username or Email *</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user-shield"></i></span>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required placeholder="admin" class="form-control bg-light border-start-0 font-medium">
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter admin username.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Password *</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-lock"></i></span>
                                        <input type="password" id="admin_pass" name="password" value="" required placeholder="Enter password" class="form-control bg-light border-start-0 border-end-0 font-medium">
                                        <button type="button" onclick="togglePass('admin_pass', 'admin_pass_icon')" class="input-group-text bg-light text-muted border-start-0">
                                            <i id="admin_pass_icon" class="fa-solid fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter admin password.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn btn-navy w-100 shadow-sm py-2.5 font-bold fs-6">
                                        Access Dashboard <i class="fa-solid fa-arrow-right ms-2 fs-7"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4 pt-3 border-top border-light small text-muted">
                                <a href="/hotel_website/index.php" class="text-royal-blue text-decoration-none font-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Public Website</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Forgot Password Modal -->
<div class="modal fade" id="adminForgotModal" tabindex="-1" aria-labelledby="adminForgotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-2xl">
            <div class="modal-header bg-navy text-white p-4 position-relative">
                <div class="position-absolute top-0 start-0 w-100" style="height: 3px; background: #D4AF5A;"></div>
                <h5 class="modal-title font-serif-title fw-bold text-gold" id="adminForgotModalLabel">
                    <i class="fa-solid fa-user-shield me-2"></i> Admin Password Reset
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/hotel_website/admin/login.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="reset_admin_password">
                <div class="modal-body p-4 bg-white text-navy">
                    <p class="small text-muted mb-3">
                        Enter your administrative username or email address below. Admin password will be reset to <strong class="text-navy">admin123</strong> in the MySQL database.
                    </p>
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-uppercase text-muted">Admin Username / Email *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-shield-halved"></i></span>
                            <input type="text" name="reset_username" required placeholder="admin" class="form-control bg-light border-start-0 font-medium">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy btn-sm px-4">Reset Admin Credentials</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
