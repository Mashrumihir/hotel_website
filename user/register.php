<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Male');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $id_proof_type = sanitize($_POST['id_proof_type'] ?? 'Aadhar Card');
    $id_proof_number = sanitize($_POST['id_proof_number'] ?? '');
    $id_proof = !empty($id_proof_number) ? ($id_proof_type . ' - ' . $id_proof_number) : sanitize($_POST['id_proof'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = getDBConnection();
            if ($pdo) {
                // Check duplicate email
                $chk = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE `email` = :email");
                $chk->execute(['email' => $email]);
                if ($chk->fetchColumn() > 0) {
                    $error = 'An account with this email address already exists.';
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO `customers` (`name`, `gender`, `mobile`, `email`, `address`, `id_proof`, `password`, `plain_password`) VALUES (:name, :gender, :mobile, :email, :address, :id_proof, :password, :plain_password)");
                    $stmt->execute([
                        'name' => $name,
                        'gender' => $gender,
                        'mobile' => $mobile,
                        'email' => $email,
                        'address' => $address,
                        'id_proof' => $id_proof,
                        'password' => $hashed,
                        'plain_password' => $password
                    ]);

                    set_toast('Account created successfully! Please sign in to your account.', 'success');
                    redirect('/hotel_website/user/login.php');
                }
            }
        }
    } else {
        $error = 'Please fill out all required fields.';
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
                        <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4 p-lg-5 text-white position-relative overflow-hidden" style="min-height: 540px; background: linear-gradient(180deg, rgba(7, 26, 47, 0.82) 0%, rgba(11, 39, 68, 0.96) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;">
                            
                            <div class="position-relative z-2">
                                <a href="/hotel_website/index.php" class="d-inline-block text-decoration-none">
                                    <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease" style="max-height: 55px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.6));">
                                </a>
                            </div>

                            <div class="position-relative z-2 my-auto py-4">
                                <span class="badge px-3 py-2 rounded-pill font-semibold mb-3 d-inline-flex align-items-center gap-1.5" style="background: rgba(212, 175, 90, 0.18); color: #D4AF5A; border: 1px solid rgba(212, 175, 90, 0.35); font-size: 0.72rem; letter-spacing: 0.5px; max-width: 100%;">
                                    👑 VIP GUEST REGISTRATION
                                </span>
                                <h2 class="font-serif-title text-white fw-bold mb-3 display-6">Begin Your Journey</h2>
                                <p class="small text-white-50 leading-relaxed mb-0">
                                    Join Hotel Ease to enjoy instant reservations, exclusive member tariffs, priority concierge assistance, and guest benefits.
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
                                    <h3 class="font-serif-title fw-bold text-navy mb-0">Guest Registration</h3>
                                    <div class="rounded-circle" style="width: 8px; height: 8px; background-color: #D4AF5A;"></div>
                                </div>
                                <p class="small text-muted">Create a Hotel Ease account to unlock seamless luxury bookings</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small font-semibold rounded-3 p-3 mb-3 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="row g-3 needs-validation" novalidate>
                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Full Name *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required placeholder="John Doe" class="form-control bg-light font-medium">
                                    <div class="invalid-feedback text-danger small font-semibold mt-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter full name.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Gender *</label>
                                    <select name="gender" required class="form-select bg-light font-medium">
                                        <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select Gender</option>
                                        <option value="Male" <?php echo (($gender ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($gender ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($gender ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="invalid-feedback text-danger small font-semibold mt-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> Please select gender.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Mobile Number *</label>
                                    <input type="text" name="mobile" value="<?php echo htmlspecialchars($mobile ?? ''); ?>" required placeholder="+1 234 567 890" class="form-control bg-light font-medium">
                                    <div class="invalid-feedback text-danger small font-semibold mt-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter mobile number.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Email Address *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="name@example.com" class="form-control bg-light font-medium">
                                    <div class="invalid-feedback text-danger small font-semibold mt-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> Please enter a valid email.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Address</label>
                                    <input type="text" name="address" value="<?php echo htmlspecialchars($address ?? ''); ?>" placeholder="Street Address, City, Country" class="form-control bg-light font-medium">
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold small text-uppercase text-navy">ID Proof (Aadhar / Passport)</label>
                                    <div class="input-group">
                                        <select name="id_proof_type" class="form-select bg-light font-medium border-end-0" style="max-width: 170px;">
                                            <option value="Aadhar Card" <?php echo (($id_proof_type ?? '') === 'Aadhar Card') ? 'selected' : ''; ?>>Aadhar Card</option>
                                            <option value="Passport" <?php echo (($id_proof_type ?? '') === 'Passport') ? 'selected' : ''; ?>>Passport</option>
                                        </select>
                                        <input type="text" name="id_proof_number" value="<?php echo htmlspecialchars($id_proof_number ?? ''); ?>" placeholder="Enter ID Number" class="form-control bg-light font-medium">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Password *</label>
                                    <div class="input-group has-validation">
                                        <input type="password" id="reg_pass" name="password" required placeholder="Create password" class="form-control bg-light border-end-0 font-medium">
                                        <button type="button" onclick="togglePass('reg_pass', 'reg_pass_icon')" class="input-group-text bg-light text-muted border-start-0">
                                            <i id="reg_pass_icon" class="fa-solid fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Please create a password.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold small text-uppercase text-navy">Confirm Password *</label>
                                    <div class="input-group has-validation">
                                        <input type="password" id="reg_confirm_pass" name="confirm_password" required placeholder="Re-enter password" class="form-control bg-light border-end-0 font-medium">
                                        <button type="button" onclick="togglePass('reg_confirm_pass', 'reg_confirm_pass_icon')" class="input-group-text bg-light text-muted border-start-0">
                                            <i id="reg_confirm_pass_icon" class="fa-solid fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback text-danger small font-semibold mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Confirm password.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 pt-3">
                                    <button type="submit" class="btn btn-royal w-100 shadow-sm py-2.5 font-bold fs-6">
                                        Create Account <i class="fa-solid fa-arrow-right ms-2 fs-7"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4 pt-3 border-top border-light small text-muted">
                                Already have an account? <a href="/hotel_website/user/login.php" class="text-royal-blue fw-bold text-decoration-none ms-1">Sign In Here</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
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
