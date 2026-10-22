<?php
require_once __DIR__ . '/../includes/header.php';
require_user();
header('Location: /hotel_website/user/settings.php#account');
exit;
?>

$pdo = getDBConnection();
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `profile_pic` VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {
        // column already exists
    }
}

$user = get_logged_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $id_proof_type = sanitize($_POST['id_proof_type'] ?? 'Aadhar Card');
    $id_proof_number = sanitize($_POST['id_proof_number'] ?? '');
    $id_proof = !empty($id_proof_number) ? ($id_proof_type . ' - ' . $id_proof_number) : sanitize($_POST['id_proof'] ?? '');

    $profile_pic_path = $user['profile_pic'] ?? null;

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmp = $_FILES['profile_pic']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $allowed)) {
            $fileName = 'user_' . ($_SESSION['user_id'] ?? 1) . '_' . time() . '.' . $ext;
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($fileTmp, $targetFile)) {
                $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
            }
        }
    }

    if (!empty($name) && !empty($mobile)) {
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `customers` SET `name` = :n, `mobile` = :m, `address` = :a, `id_proof` = :idp, `profile_pic` = :pic WHERE `customer_id` = :uid");
            $stmt->execute([
                'n' => $name,
                'm' => $mobile,
                'a' => $address,
                'idp' => $id_proof,
                'pic' => $profile_pic_path,
                'uid' => $_SESSION['user_id']
            ]);
        }
        $_SESSION['user_name'] = $name;
        $user = get_logged_user();
        set_toast('Profile updated successfully!', 'success');
    }
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
<?php
$page_title = 'Profile & Settings';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-serif-title fw-bold text-navy mb-1">Account Profile &amp; Settings</h3>
            <p class="small text-muted mb-0">Manage your profile photo, personal information, contact numbers, and identity verification details.</p>
        </div>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="font-serif-title fw-bold text-navy mb-4 border-bottom pb-2">Personal Details</h5>
                    
                    <!-- Profile Photo Upload Box -->
                    <div class="p-3 bg-light rounded-4 border mb-4 d-flex flex-column flex-sm-row align-items-center gap-3">
                        <div class="position-relative">
                            <?php if (!empty($user['profile_pic'])): ?>
                                <img id="photoPreview" src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="rounded-circle object-fit-cover shadow-sm border" style="width: 80px; height: 80px;" alt="Profile Photo">
                            <?php else: ?>
                                <div id="avatarFallback" class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 80px; height: 80px; font-size: 30px; background: linear-gradient(135deg, #0B5ED7 0%, #123B6D 100%);">
                                    <?php echo strtoupper(substr($user['name'] ?? 'G', 0, 1)); ?>
                                </div>
                                <img id="photoPreview" class="rounded-circle object-fit-cover shadow-sm border d-none" style="width: 80px; height: 80px;" alt="Profile Photo Preview">
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold text-dark mb-1">Profile Photo</label>
                            <input type="file" name="profile_pic" id="photoInput" accept="image/*" class="form-control form-control-sm rounded-3">
                            <span class="text-muted small d-block mt-1" style="font-size: 11px;">Supported formats: JPG, PNG, WEBP (Max 5MB)</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Full Name *</label>
                            <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Email Address (Read-Only)</label>
                            <input type="email" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control rounded-3" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">ID Proof (Aadhar / Passport)</label>
                            <input type="text" name="id_proof" class="form-control rounded-3" value="<?php echo htmlspecialchars($user['id_proof'] ?? ''); ?>" placeholder="Aadhar Card - 4829 1029 3841">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Address</label>
                            <textarea name="address" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary-blue px-4 py-2.5 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Changes
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                    <div class="position-relative mx-auto mb-3" style="width: 100px; height: 100px;">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-100 h-100 rounded-circle object-fit-cover shadow-sm border" alt="User Photo">
                        <?php else: ?>
                            <div class="w-100 h-100 rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm" style="font-size: 38px; background: linear-gradient(135deg, #0B5ED7 0%, #123B6D 100%);">
                                <?php echo strtoupper(substr($user['name'] ?? 'G', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h5>
                    <p class="small text-muted mb-3"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill small fw-bold">
                        <i class="fa-solid fa-shield-check me-1"></i> Verified Customer
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>
</main>

<script>
document.getElementById('photoInput')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (evt) {
            const preview = document.getElementById('photoPreview');
            const fallback = document.getElementById('avatarFallback');
            if (preview) {
                preview.src = evt.target.result;
                preview.classList.remove('d-none');
            }
            if (fallback) {
                fallback.classList.add('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
