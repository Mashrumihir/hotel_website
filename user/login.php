<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'reset_password') {
        $resetEmail = strtolower(trim((string) ($_POST['reset_email'] ?? '')));
        if (!empty($resetEmail)) {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE LOWER(`email`) = :email LIMIT 1");
                $stmt->execute(['email' => $resetEmail]);
                $user = $stmt->fetch();
                if ($user) {
                    $newPass = 'user123';
                    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE `customers` SET `password` = :p WHERE `customer_id` = :id");
                    $upd->execute(['p' => $hashed, 'id' => $user['customer_id']]);
                    set_toast('Password reset successfully! Your password is reset to: user123', 'success');
                    redirect('/hotel_website/user/login.php');
                } else {
                    $error = 'No account registered with that email address.';
                }
            }
        } else {
            $error = 'Please enter your registered email address.';
        }
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (empty($password)) {
            $error = 'Please enter your password.';
        } else {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE LOWER(`email`) = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                $storedPassword = (string) ($user['password'] ?? '');
                $plainPassword = (string) ($user['plain_password'] ?? '');
                $passwordMatches = $user && (
                    password_verify($password, $storedPassword)
                    || ($plainPassword !== '' && hash_equals($plainPassword, $password))
                );

                if ($passwordMatches) {
                    $_SESSION['user_id'] = $user['customer_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    set_toast('Welcome back to Hotel Ease, ' . $user['name'] . '!', 'success');
                    redirect('/hotel_website/user/dashboard.php');
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                // Fallback login if MySQL service is offline in XAMPP
                if (($email === 'mashrumihir15@gmail.com' || $email === 'mihir@example.com') && $password === 'user123') {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['user_name'] = 'Mihir Mashru';
                    $_SESSION['user_email'] = $email;
                    set_toast('Welcome back to Hotel Ease, Mihir Mashru!', 'success');
                    redirect('/hotel_website/user/dashboard.php');
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }
    }
}
?>

<div class="position-relative min-vh-100 d-flex align-items-center justify-content-center overflow-hidden py-4 py-md-5" style="background: linear-gradient(135deg, #071A2F 0%, #0B2744 100%);">
    <!-- Animated Points & Lines Constellation Canvas Overlay -->
    <canvas id="particleCanvas" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; pointer-events: none;"></canvas>
    
    <div class="container position-relative z-3">
        <div class="row justify-content-center">
            <div class="col-11 col-md-10 col-lg-9 col-xl-8">
                <div class="card border-0 rounded-4 overflow-hidden shadow-2xl position-relative" style="max-width: 920px; margin: 0 auto;">
                    <!-- Unified Accent Gold Top Bar across full card -->
                    <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #D4AF5A 0%, #F3E5AB 50%, #D4AF5A 100%); z-index: 10;"></div>
                    
                    <div class="row g-0">
                        
                        <!-- Left Split Screen: Midnight Navy + Photo Overlay -->
                        <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4 p-lg-5 text-white position-relative overflow-hidden" style="min-height: 520px; background: linear-gradient(180deg, rgba(7, 26, 47, 0.82) 0%, rgba(11, 39, 68, 0.96) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;">
                            
                            <div class="position-relative z-2">
                                <a href="/hotel_website/index.php" class="d-inline-block text-decoration-none">
                                    <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease" style="max-height: 55px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.6));">
                                </a>
                            </div>

                            <div class="position-relative z-2 my-auto py-4">
                                <span class="badge px-3 py-2 rounded-pill font-semibold mb-3 d-inline-flex align-items-center gap-1.5" style="background: rgba(212, 175, 90, 0.18); color: #D4AF5A; border: 1px solid rgba(212, 175, 90, 0.35); font-size: 0.72rem; letter-spacing: 0.5px; max-width: 100%;">
                                    👑 LUXURY GUEST PORTAL
                                </span>
                                <h2 class="font-serif-title text-white fw-bold mb-3 display-6">Welcome Back</h2>
                                <p class="small text-white-50 leading-relaxed mb-0">
                                    Sign in to view your upcoming stays, manage bookings, request concierge services, and unlock member rates.
                                </p>
                            </div>

                            <div class="position-relative z-2 small text-white-50 border-top border-white border-opacity-10 pt-3">
                                &copy; <?php echo date('Y'); ?> Hotel Ease. All rights reserved.
                            </div>
                        </div>

                        <!-- Right Split Screen: Pure White Form Card -->
                        <div class="col-md-7 p-4 p-lg-5 bg-white text-navy d-flex flex-column justify-content-center">
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="font-serif-title fw-bold text-navy mb-0">Customer Login</h3>
                                    <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                </div>
                                <p class="small text-muted">Enter your registered email and password to access your portal</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small font-semibold rounded-3 p-3 mb-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="row g-3 needs-validation" novalidate>
                                <div class="col-12">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Email Address *</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-envelope"></i></span>
<?php
$defaultEmailVal = $_SESSION['reset_success_email'] ?? ($email ?? '');
unset($_SESSION['reset_success_email']);
?>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($defaultEmailVal); ?>" required placeholder="name@example.com" class="form-control bg-light border-start-0 font-medium">
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter a valid email address.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label font-semibold small text-uppercase text-navy mb-0">Password *</label>
                                        <a href="/hotel_website/user/forgot-password.php" class="small text-royal-blue font-semibold text-decoration-none">Forgot?</a>
                                    </div>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-lock"></i></span>
                                        <input type="password" id="user_pass" name="password" value="" required placeholder="Enter password" class="form-control bg-light border-start-0 border-end-0 font-medium">
                                        <button type="button" onclick="togglePass('user_pass', 'user_pass_icon')" class="input-group-text bg-light text-muted border-start-0">
                                            <i id="user_pass_icon" class="fa-solid fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter your password.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                                        <label class="form-check-label small text-muted font-medium" for="rememberMe">Remember me on this browser</label>
                                    </div>
                                </div>

                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn btn-royal w-100 shadow-sm py-2.5 font-bold fs-6">
                                        Sign In to Portal <i class="fa-solid fa-arrow-right ms-2 fs-7"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4 pt-3 border-top border-light small text-muted">
                                Don't have an account? <a href="/hotel_website/user/register.php" class="text-royal-blue fw-bold text-decoration-none ms-1">Create Account</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Luxury Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-2xl">
            <div class="modal-header bg-navy text-white p-4 position-relative">
                <div class="position-absolute top-0 start-0 w-100" style="height: 3px; background: #D4AF5A;"></div>
                <h5 class="modal-title font-serif-title fw-bold text-gold" id="forgotModalLabel">
                    <i class="fa-solid fa-key me-2"></i> Reset Account Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/hotel_website/user/login.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="reset_password">
                <div class="modal-body p-4 bg-white text-navy">
                    <p class="small text-muted mb-3">
                        Enter your registered email address below. Your password will be reset to <strong class="text-navy">user123</strong> in the MySQL database.
                    </p>
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-uppercase text-muted">Registration Email *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="reset_email" required placeholder="name@example.com" class="form-control bg-light border-start-0 font-medium">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-royal btn-sm px-4">Reset Password</button>
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
