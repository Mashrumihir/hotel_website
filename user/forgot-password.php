<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDBConnection();
$error = '';
$success = '';

// Session step tracker for multi-step recovery flow
if (!isset($_SESSION['reset_step'])) {
    $_SESSION['reset_step'] = 1;
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'send_otp') {
        $email = sanitize($_POST['email'] ?? '');
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $custName = '';
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT `name` FROM `customers` WHERE LOWER(`email`) = LOWER(:e) LIMIT 1");
                $stmt->execute(['e' => $email]);
                $custName = $stmt->fetchColumn() ?: '';
            }

            if ($custName === '') {
                $error = 'This email is not registered. Please enter the email used for your Hotel Ease account.';
            } else {
            $otpCode = sprintf('%06d', rand(100000, 999999));

            $sent = send_otp_email($email, $otpCode, $custName);
            if ($sent) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp']   = $otpCode;
                $_SESSION['reset_step']  = 2;
                set_toast('Verification code sent to your email (' . htmlspecialchars($email) . ') via SMTP!', 'success');
            } else {
                $error = 'Failed to send OTP email via SMTP. Please check your email address or connection.';
            }
            }
        } else {
            $error = 'Please enter a valid email address.';
        }
    } elseif ($action === 'verify_otp') {
        $otp_digits = $_POST['otp'] ?? [];
        $otp = is_array($otp_digits) ? implode('', $otp_digits) : sanitize($_POST['otp_code'] ?? '');
        $currentSessionOtp = $_SESSION['reset_otp'] ?? '';

        if (!empty($otp) && !empty($currentSessionOtp) && $otp === $currentSessionOtp) {
            $_SESSION['reset_step'] = 3;
            set_toast('Identity verified! Please set your new password.', 'success');
        } else {
            $error = 'Invalid verification code. Please enter the 6-digit OTP code sent to your email inbox.';
        }
    } elseif ($action === 'resend_otp') {
        $email = $_SESSION['reset_email'] ?? '';
        if (!empty($email)) {
            $otpCode = sprintf('%06d', rand(100000, 999999));
            $custName = '';
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT `name` FROM `customers` WHERE LOWER(`email`) = LOWER(:e) LIMIT 1");
                $stmt->execute(['e' => $email]);
                $custName = $stmt->fetchColumn() ?: '';
            }

            $sent = send_otp_email($email, $otpCode, $custName);
            if ($sent) {
                $_SESSION['reset_otp'] = $otpCode;
                set_toast('New verification code sent to ' . htmlspecialchars($email) . ' via SMTP!', 'info');
            } else {
                $error = 'Failed to resend OTP email via SMTP. Please try again.';
            }
        }
    } elseif ($action === 'change_email') {
        $_SESSION['reset_step'] = 1;
    } elseif ($action === 'reset_password') {
        $pass1 = $_POST['new_password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';

        if (strlen($pass1) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($pass1 !== $pass2) {
            $error = 'Passwords do not match. Please try again.';
        } else {
            $email = $_SESSION['reset_email'] ?? '';
            if (!empty($email)) {
                $hashed = password_hash($pass1, PASSWORD_BCRYPT);
                if ($pdo) {
                    $stmt = $pdo->prepare("SELECT `customer_id` FROM `customers` WHERE LOWER(`email`) = LOWER(:email) LIMIT 1");
                    $stmt->execute(['email' => $email]);
                    $exists = $stmt->fetchColumn();

                    if ($exists) {
                        $upd = $pdo->prepare("UPDATE `customers` SET `password` = :p, `plain_password` = :pp WHERE LOWER(`email`) = LOWER(:email)");
                        $upd->execute(['p' => $hashed, 'pp' => $pass1, 'email' => $email]);
                    } else {
                        $namePart = ucfirst(explode('@', $email)[0]);
                        $ins = $pdo->prepare("INSERT INTO `customers` (`name`, `email`, `password`, `plain_password`, `mobile`) VALUES (:n, :e, :p, :pp, '9999999999')");
                        $ins->execute([
                            'n' => $namePart,
                            'e' => $email,
                            'p' => $hashed,
                            'pp' => $pass1
                        ]);
                    }
                }
                $_SESSION['reset_success_email'] = $email;
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_step']);
                set_toast('Password updated successfully! Please sign in with your new password.', 'success');
                redirect('/hotel_website/user/login.php');
            }
        }
    }
}

$step = $_SESSION['reset_step'] ?? 1;
$currentEmail = $_SESSION['reset_email'] ?? '';
?>

<div class="position-relative min-vh-100 d-flex align-items-center justify-content-center overflow-hidden py-4 py-md-5" style="background: linear-gradient(135deg, #071A2F 0%, #0B2744 100%);">
    <!-- Animated Points & Lines Constellation Canvas Overlay -->
    <canvas id="particleCanvas" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; pointer-events: none;"></canvas>

    <div class="container position-relative z-3">
        <div class="row justify-content-center">
            <div class="col-11 col-md-10 col-lg-9 col-xl-8">
                <div class="card border-0 rounded-4 overflow-hidden shadow-2xl position-relative" style="max-width: 880px; margin: 0 auto;">
                    <!-- Unified Accent Gold Top Bar across full card -->
                    <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #D4AF5A 0%, #F3E5AB 50%, #D4AF5A 100%); z-index: 10;"></div>
                    
                    <div class="row g-0">
                        <!-- Left Split Screen: Midnight Navy + Photo Overlay (Identical to Login & Register) -->
                        <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4 p-lg-5 text-white position-relative overflow-hidden" style="min-height: 520px; background: linear-gradient(180deg, rgba(7, 26, 47, 0.84) 0%, rgba(11, 39, 68, 0.96) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;">
                            
                            <div class="position-relative z-2">
                                <a href="/hotel_website/index.php" class="d-inline-block text-decoration-none">
                                    <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease" style="max-height: 55px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.6));">
                                </a>
                            </div>

                            <div class="position-relative z-2 my-auto py-4">
                                <span class="badge px-3 py-2 rounded-pill font-semibold mb-3 d-inline-flex align-items-center gap-2 text-nowrap align-self-start" style="background: rgba(212, 175, 90, 0.18); color: #D4AF5A; border: 1px solid rgba(212, 175, 90, 0.35); font-size: 0.72rem; letter-spacing: 0.5px; line-height: 1.2;">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                    <span>SECURE ACCOUNT RECOVERY</span>
                                </span>
                                <h2 class="font-serif-title text-white fw-bold mb-3 display-6">Back to Your Stay</h2>
                                <p class="small text-white-50 leading-relaxed mb-4">
                                    Verify your identity with a one-time passcode to set a new password safely.
                                </p>
                                <div class="space-y-2 extra-small text-white-50">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-solid fa-key text-gold"></i> <span>One-time codes expire automatically</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-gold"></i> <span>Your booking details stay protected</span>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative z-2 small text-white-50 border-top border-white border-opacity-10 pt-3">
                                &copy; <?php echo date('Y'); ?> Hotel Ease. All rights reserved.
                            </div>
                        </div>

                        <!-- Right Split Screen: Pure Clean White Form Card -->
                        <div class="col-md-7 p-4 p-lg-5 bg-white text-navy d-flex flex-column justify-content-between">
                            
                            <div>
                                <!-- Multi-Step Progress Indicators -->
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="flex-grow-1 rounded-pill" style="height: 4px; background: <?php echo $step >= 1 ? '#2563EB' : '#E2E8F0'; ?>; transition: all 0.3s ease;"></div>
                                    <div class="flex-grow-1 rounded-pill" style="height: 4px; background: <?php echo $step >= 2 ? '#2563EB' : '#E2E8F0'; ?>; transition: all 0.3s ease;"></div>
                                    <div class="flex-grow-1 rounded-pill" style="height: 4px; background: <?php echo $step >= 3 ? '#2563EB' : '#E2E8F0'; ?>; transition: all 0.3s ease;"></div>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small font-semibold rounded-3 p-3 mb-4 d-flex align-items-center">
                                        <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    </div>
                                <?php endif; ?>

                                <!-- STEP 1: EMAIL ENTRY -->
                                <?php if ($step == 1): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h3 class="font-serif-title fw-bold text-navy mb-0">Forgot Password?</h3>
                                            <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                        </div>
                                        <p class="small text-muted mb-0">Enter your registered email address to receive a 6-digit OTP verification code.</p>
                                    </div>

                                    <form method="POST" class="row g-3 needs-validation" novalidate>
                                        <input type="hidden" name="action" value="send_otp">
                                        <div class="col-12">
                                            <label class="form-label font-semibold small text-uppercase text-navy">Email Address *</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                                <input type="email" name="email" required placeholder="you@example.com" value="<?php echo htmlspecialchars($currentEmail); ?>" class="form-control bg-light font-medium border-start-0">
                                            </div>
                                            <div class="invalid-feedback text-danger small font-semibold mt-1">
                                                <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter your registered email.
                                            </div>
                                        </div>
                                        <div class="col-12 pt-2">
                                            <button type="submit" class="btn btn-primary-blue w-100 py-2.5 font-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                                <span>Send Verification Code</span> <i class="fa-solid fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </form>

                                <!-- STEP 2: OTP VERIFICATION -->
                                <?php elseif ($step == 2): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h3 class="font-serif-title fw-bold text-navy mb-0">Enter OTP Code</h3>
                                            <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                        </div>
                                        <p class="small text-muted mb-0">We sent a 6-digit code to <strong class="text-navy"><?php echo htmlspecialchars($currentEmail); ?></strong></p>
                                    </div>

                                    <form method="POST" class="row g-3">
                                        <input type="hidden" name="action" value="verify_otp">
                                        <div class="col-12">
                                            <div class="d-flex gap-2 justify-content-center mb-3">
                                                <?php for ($i = 0; $i < 6; $i++): ?>
                                                    <input type="text" name="otp[]" maxlength="1" class="form-control text-center text-navy font-bold fs-4 rounded-3 bg-light border otp-input" style="width: 44px; height: 50px;" required <?php echo $i === 0 ? 'autofocus' : ''; ?>>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="p-2.5 rounded-3 bg-light border text-muted extra-small text-center mb-3">
                                                <i class="fa-solid fa-envelope-circle-check text-success me-1"></i> A 6-digit security code has been sent directly to your email address via SMTP.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary-blue w-100 py-2.5 font-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                                <i class="fa-solid fa-shield-check"></i> <span>Verify OTP</span>
                                            </button>
                                        </div>
                                    </form>

                                    <div class="text-center mt-3 extra-small">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="resend_otp">
                                            <span class="text-muted">Didn't receive the code?</span>
                                            <button type="submit" class="btn btn-link p-0 extra-small text-primary-blue font-bold text-decoration-none ms-1">Resend OTP</button>
                                        </form>
                                        <form method="POST" class="d-block mt-1">
                                            <input type="hidden" name="action" value="change_email">
                                            <button type="submit" class="btn btn-link p-0 extra-small text-muted text-decoration-none">Change email address</button>
                                        </form>
                                    </div>

                                <!-- STEP 3: RESET PASSWORD -->
                                <?php elseif ($step == 3): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h3 class="font-serif-title fw-bold text-navy mb-0">Create New Password</h3>
                                            <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                        </div>
                                        <p class="small text-muted mb-0">Enter your new account password below.</p>
                                    </div>

                                    <form method="POST" class="row g-3 needs-validation" novalidate>
                                        <input type="hidden" name="action" value="reset_password">
                                        <div class="col-12">
                                            <label class="form-label font-semibold small text-uppercase text-navy">New Password *</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                                <input type="password" id="new_pass_input" name="new_password" required minlength="8" placeholder="Minimum 8 characters" class="form-control bg-light font-medium border-start-0 border-end-0">
                                                <button type="button" onclick="togglePass('new_pass_input', this)" class="input-group-text bg-light text-muted border-start-0">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label font-semibold small text-uppercase text-navy">Confirm New Password *</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                                <input type="password" id="conf_pass_input" name="confirm_password" required minlength="8" placeholder="Repeat your password" class="form-control bg-light font-medium border-start-0 border-end-0">
                                                <button type="button" onclick="togglePass('conf_pass_input', this)" class="input-group-text bg-light text-muted border-start-0">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12 pt-2">
                                            <button type="submit" class="btn btn-primary-blue w-100 py-2.5 font-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                                <i class="fa-solid fa-floppy-disk"></i> <span>Reset Password</span>
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Back to Login Footer -->
                            <div class="text-center mt-4 pt-3 border-top extra-small">
                                <a href="/hotel_website/user/login.php" class="text-muted text-decoration-none font-semibold d-inline-flex align-items-center gap-1.5 hover-navy">
                                    <i class="fa-solid fa-arrow-left text-primary-blue"></i> Back to Login
                                </a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus next OTP digit input
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
