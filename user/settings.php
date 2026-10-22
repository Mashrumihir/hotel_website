<?php
require_once __DIR__ . '/../includes/header.php';
require_user();

$pdo = getDBConnection();
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `profile_pic` VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `theme_preference` VARCHAR(20) DEFAULT 'light'");
    } catch (Exception $e) {}
}

$user = get_logged_user();
$booking_count = 0;
if ($pdo && !empty($user['customer_id'])) {
    try {
        $b_stmt = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `customer_id` = ?");
        $b_stmt->execute([$user['customer_id']]);
        $booking_count = (int)$b_stmt->fetchColumn();
    } catch (Exception $e) {
        $booking_count = 0;
    }
}
$error = '';
$success = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? 'update_profile');

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $id_proof = sanitize($_POST['id_proof'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        $profile_pic_path = $user['profile_pic'] ?? null;

        // Handle Photo Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileTmp = $_FILES['profile_pic']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $fileName = 'user_' . ($_SESSION['user_id'] ?? 1) . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    $profile_pic_path = '/hotel_website/uploads/profiles/' . $fileName;
                }
            } else {
                $error = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.';
            }
        }

        if (empty($error) && !empty($name) && !empty($mobile)) {
            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE `customers` SET `name` = :n, `gender` = :g, `mobile` = :m, `id_proof` = :idp, `address` = :a, `profile_pic` = :pic WHERE `customer_id` = :uid");
                $stmt->execute([
                    'n' => $name,
                    'g' => $gender,
                    'm' => $mobile,
                    'idp' => $id_proof,
                    'a' => $address,
                    'pic' => $profile_pic_path,
                    'uid' => $_SESSION['user_id']
                ]);
            }
            $_SESSION['user_name'] = $name;
            $user = get_logged_user();
            set_toast('✓ Profile updated successfully.', 'success');
            $success = '✓ Profile updated successfully.';
        }
    } elseif ($action === 'update_preferences') {
        $pref_lang = sanitize($_POST['pref_language'] ?? 'en');
        $pref_curr = sanitize($_POST['pref_currency'] ?? 'INR');
        $pref_tz   = sanitize($_POST['pref_timezone'] ?? 'Asia/Kolkata');
        $pref_fmt  = sanitize($_POST['pref_date_format'] ?? 'DD/MM/YYYY');

        $save_info  = isset($_POST['save_booking_info']) ? 1 : 0;
        $show_rec   = isset($_POST['show_recommended']) ? 1 : 0;
        $remember_r = isset($_POST['remember_room_type']) ? 1 : 0;
        $enable_rec = isset($_POST['enable_personalized']) ? 1 : 0;

        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `customers` SET 
                `pref_language` = :lang, 
                `pref_currency` = :curr, 
                `pref_timezone` = :tz, 
                `pref_date_format` = :fmt, 
                `save_booking_info` = :sinfo, 
                `show_recommended` = :srec, 
                `remember_room_type` = :rroom, 
                `enable_personalized` = :erec 
                WHERE `customer_id` = :uid");
            $stmt->execute([
                'lang' => $pref_lang,
                'curr' => $pref_curr,
                'tz'   => $pref_tz,
                'fmt'  => $pref_fmt,
                'sinfo' => $save_info,
                'srec'  => $show_rec,
                'rroom' => $remember_r,
                'erec'  => $enable_rec,
                'uid'   => $_SESSION['user_id']
            ]);
        }

        $_SESSION['pref_language'] = $pref_lang;
        $_SESSION['pref_currency'] = $pref_curr;
        setcookie('hotel_ease_language', $pref_lang, time() + (86400 * 365), "/");
        setcookie('hotel_ease_currency', $pref_curr, time() + (86400 * 365), "/");

        $user = get_logged_user();
        set_toast('✓ Settings updated successfully.', 'success');
        $success = '✓ Preferences updated successfully.';
    } elseif ($action === 'update_notifications') {
        $notif_email   = isset($_POST['notif_email']) ? 1 : 0;
        $notif_remind  = isset($_POST['notif_reminders']) ? 1 : 0;
        $notif_offers  = isset($_POST['notif_offers']) ? 1 : 0;
        $notif_alerts  = isset($_POST['notif_price_alerts']) ? 1 : 0;

        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `customers` SET 
                `notif_email` = :ne, 
                `notif_reminders` = :nr, 
                `notif_offers` = :no, 
                `notif_price_alerts` = :na 
                WHERE `customer_id` = :uid");
            $stmt->execute([
                'ne' => $notif_email,
                'nr' => $notif_remind,
                'no' => $notif_offers,
                'na' => $notif_alerts,
                'uid' => $_SESSION['user_id']
            ]);
        }

        $user = get_logged_user();
        set_toast('✓ Notification preferences updated successfully.', 'success');
        $success = '✓ Notification preferences updated successfully.';
    } elseif ($action === 'update_appearance') {
        $theme = sanitize($_POST['theme_preference'] ?? 'light');

        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `customers` SET `theme_preference` = :th WHERE `customer_id` = :uid");
            $stmt->execute([
                'th' => $theme,
                'uid' => $_SESSION['user_id']
            ]);
        }

        setcookie('hotel_ease_theme', $theme, time() + (86400 * 365), "/");
        $user = get_logged_user();
        set_toast('✓ Appearance theme updated successfully.', 'success');
        $success = '✓ Appearance theme updated successfully.';
    } elseif ($action === 'change_password') {
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($new_pass) || empty($confirm_pass)) {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'New password and confirmation do not match.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE `customers` SET `password` = :p, `plain_password` = :pp WHERE `customer_id` = :uid");
                $stmt->execute([
                    'p' => $new_hash,
                    'pp' => $new_pass,
                    'uid' => $_SESSION['user_id']
                ]);
            }
            $user = get_logged_user();
            set_toast('✓ Security password updated successfully.', 'success');
            $success = '✓ Security password updated successfully.';
        }
    } elseif ($action === 'delete_account') {
        if ($pdo) {
            $stmt = $pdo->prepare("DELETE FROM `customers` WHERE `customer_id` = :uid");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
        }
        session_destroy();
        set_toast('Account deleted successfully.', 'info');
        redirect('/hotel_website/index.php');
    }
}
?>

<style>
/* =========================================================
   HOTEL EASE - LUXURY USER SETTINGS DASHBOARD CSS
   ========================================================= */
.settings-nav-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(11, 31, 58, 0.04);
    padding: 12px;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 13px 16px;
    border: 1.5px solid transparent;
    background: transparent;
    color: #475569;
    border-radius: 14px;
    text-align: left;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    margin-bottom: 6px;
}

.settings-nav-item:last-child {
    margin-bottom: 0;
}

.settings-nav-item i {
    font-size: 18px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563EB;
    transition: all 0.25s ease;
}

.settings-nav-item .nav-title {
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
    display: block;
    line-height: 1.2;
}

.settings-nav-item .nav-desc {
    font-size: 11px;
    color: #64748B;
    display: block;
    margin-top: 2px;
    font-weight: 500;
}

.settings-nav-item:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    transform: translateX(4px);
}

.settings-nav-item.active {
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%) !important;
    border-color: #2563EB !important;
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.28) !important;
}

.settings-nav-item.active .nav-title,
.settings-nav-item.active .nav-desc,
.settings-nav-item.active i {
    color: #FFFFFF !important;
}

/* User Profile Summary Card */
.luxury-profile-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(11, 31, 58, 0.05);
}

/* Modern Selectable Appearance Card */
.appearance-select-card {
    border: 2px solid #E2E8F0;
    border-radius: 16px;
    background: #FFFFFF;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    height: 100%;
}

.appearance-select-card:hover {
    border-color: #94A3B8;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.appearance-select-card.selected {
    border-color: #2563EB !important;
    background: #EFF6FF !important;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15), 0 8px 24px rgba(37, 99, 235, 0.12) !important;
}

.appearance-select-card .selected-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #2563EB;
    color: #FFFFFF;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.appearance-select-card.selected .selected-badge {
    display: flex;
}

/* Floating Sticky Save Bar */
.sticky-save-bar {
    position: sticky;
    bottom: 20px;
    z-index: 1020;
    background: rgba(11, 31, 58, 0.94);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 18px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.28);
    padding: 1rem 1.5rem;
    margin-top: 2rem;
    animation: stickySlideUp 0.3s ease-out forwards;
}

@keyframes stickySlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Modern Toggle Switch */
.form-switch .form-check-input {
    width: 3rem;
    height: 1.5rem;
    cursor: pointer;
    background-color: #CBD5E1;
    border-color: #CBD5E1;
}

.form-switch .form-check-input:checked {
    background-color: #2563EB;
    border-color: #2563EB;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
}

/* Dark Mode Overrides */
[data-theme="dark"] .settings-nav-card,
[data-theme="dark"] .luxury-profile-card,
[data-bs-theme="dark"] .settings-nav-card,
[data-bs-theme="dark"] .luxury-profile-card,
body.dark-theme .settings-nav-card,
body.dark-theme .luxury-profile-card {
    background: #0F172A !important;
    border-color: #1E293B !important;
}

[data-theme="dark"] .settings-nav-item,
[data-bs-theme="dark"] .settings-nav-item,
body.dark-theme .settings-nav-item {
    color: #CBD5E1 !important;
}

[data-theme="dark"] .settings-nav-item .nav-title,
[data-bs-theme="dark"] .settings-nav-item .nav-title,
body.dark-theme .settings-nav-item .nav-title {
    color: #F8FAFC !important;
}

[data-theme="dark"] .settings-nav-item:hover,
[data-bs-theme="dark"] .settings-nav-item:hover,
body.dark-theme .settings-nav-item:hover {
    background: #1E293B !important;
}

[data-theme="dark"] .appearance-select-card,
[data-bs-theme="dark"] .appearance-select-card,
body.dark-theme .appearance-select-card {
    background: #1E293B !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .appearance-select-card h6,
[data-bs-theme="dark"] .appearance-select-card h6,
body.dark-theme .appearance-select-card h6 {
    color: #F8FAFC !important;
}

[data-theme="dark"] .appearance-select-card p,
[data-bs-theme="dark"] .appearance-select-card p,
body.dark-theme .appearance-select-card p {
    color: #94A3B8 !important;
}

[data-theme="dark"] .appearance-select-card.selected,
[data-bs-theme="dark"] .appearance-select-card.selected,
body.dark-theme .appearance-select-card.selected {
    background: rgba(37, 99, 235, 0.2) !important;
    border: 2px solid #3B82F6 !important;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.25) !important;
}

/* Account Info Cards Layout & Spacing Fixes */
.account-info-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 1.1rem 1.25rem;
    transition: all 0.2s ease;
}
.account-info-box:hover {
    border-color: #CBD5E1;
    background: #F1F5F9;
}
.account-info-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #64748B;
    margin-bottom: 0.35rem;
    line-height: 1.2;
    display: block;
}
.account-info-value {
    font-size: 0.98rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.35;
    margin: 0;
    word-break: break-word;
}

[data-theme="dark"] .account-info-box,
[data-bs-theme="dark"] .account-info-box,
body.dark-theme .account-info-box {
    background: #1E293B !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .account-info-box:hover,
[data-bs-theme="dark"] .account-info-box:hover,
body.dark-theme .account-info-box:hover {
    background: #334155 !important;
}
[data-theme="dark"] .account-info-label,
[data-bs-theme="dark"] .account-info-label,
body.dark-theme .account-info-label {
    color: #94A3B8 !important;
}
[data-theme="dark"] .account-info-value,
[data-bs-theme="dark"] .account-info-value,
body.dark-theme .account-info-value {
    color: #F8FAFC !important;
}
</style>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
<?php
$page_title = 'Settings';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">

    <!-- PAGE HEADER & BREADCRUMB -->
    <div class="mb-4">
        <nav aria-label="breadcrumb" class="mb-1.5">
            <ol class="breadcrumb mb-1 extra-small text-muted font-semibold">
                <li class="breadcrumb-item"><a href="/hotel_website/user/dashboard.php" class="text-decoration-none text-secondary">Dashboard</a></li>
                <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Settings</li>
            </ol>
        </nav>
        <h3 class="font-serif-title fw-bold text-navy mb-1">Settings</h3>
        <p class="small text-muted mb-0">Manage your HotelEase account and customize your experience.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>



    <!-- TWO-COLUMN SETTINGS LAYOUT (Item #2) -->
    <div class="row g-4 mb-4">

        <!-- LEFT SIDE: COMPACT SETTINGS NAVIGATION CARD -->
        <div class="col-lg-4 col-xl-3">
            <div class="settings-nav-card">
                <div class="nav flex-column">
                    
                    <button type="button" class="settings-nav-item active" id="tab-nav-profile" onclick="switchSettingsTab('profile')">
                        <i class="fa-solid fa-user"></i>
                        <div>
                            <span class="nav-title">Profile</span>
                            <span class="nav-desc">Personal information</span>
                        </div>
                    </button>

                    <button type="button" class="settings-nav-item" id="tab-nav-account" onclick="switchSettingsTab('account')">
                        <i class="fa-solid fa-id-card"></i>
                        <div>
                            <span class="nav-title">Account</span>
                            <span class="nav-desc">Account preferences</span>
                        </div>
                    </button>



                    <button type="button" class="settings-nav-item" id="tab-nav-security" onclick="switchSettingsTab('security')">
                        <i class="fa-solid fa-shield-halved"></i>
                        <div>
                            <span class="nav-title">Privacy &amp; Security</span>
                            <span class="nav-desc">Password and security</span>
                        </div>
                    </button>

                    <button type="button" class="settings-nav-item" id="tab-nav-appearance" onclick="switchSettingsTab('appearance')">
                        <i class="fa-solid fa-palette"></i>
                        <div>
                            <span class="nav-title">Appearance</span>
                            <span class="nav-desc">Theme and display</span>
                        </div>
                    </button>

                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: SETTINGS CONTENT AREA -->
        <div class="col-lg-8 col-xl-9">

            <!-- ================================================== -->
            <!-- 1. PROFILE SECTION (#profile) -->
            <!-- ================================================== -->
            <div class="settings-pane" id="pane-profile">
                <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="border-bottom pb-3 mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="font-serif-title fw-bold text-navy mb-1">Personal Information</h5>
                                <p class="small text-muted mb-0">Update your primary identity, contact details, and profile photo.</p>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill small font-semibold">
                                <i class="fa-solid fa-user-check me-1"></i> Verified Profile
                            </span>
                        </div>

                        <!-- Profile Photo Upload Box -->
                        <div class="p-3.5 bg-light rounded-4 border mb-4 d-flex flex-column flex-sm-row align-items-center gap-3.5">
                            <div class="position-relative flex-shrink-0 cursor-pointer" title="Click to view photo" onclick="openPhotoModal(document.getElementById('photoPreview')?.src || '', '<?php echo htmlspecialchars(addslashes($user['name'] ?? 'User Profile')); ?>')">
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <img id="photoPreview" src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="rounded-circle object-fit-cover shadow-sm border border-2 border-primary" style="width: 80px; height: 80px;" alt="Profile Photo">
                                <?php else: ?>
                                    <div id="avatarFallback" class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 80px; height: 80px; font-size: 30px; background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);">
                                        <?php echo strtoupper(substr($user['name'] ?? 'M', 0, 1)); ?>
                                    </div>
                                    <img id="photoPreview" class="rounded-circle object-fit-cover shadow-sm border border-2 border-primary d-none" style="width: 80px; height: 80px;" alt="Profile Photo Preview">
                                <?php endif; ?>
                                <span class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px; font-size: 11px;" title="View Full Photo">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1 w-100">
                                <label class="form-label small font-bold text-navy mb-1">Profile Photo</label>
                                <input type="file" name="profile_pic" id="photoInput" accept="image/*" class="form-control form-control-sm rounded-3 font-medium">
                                <span class="text-muted extra-small d-block mt-1">Recommended: Square JPG, PNG, or WEBP image up to 5MB. Click photo avatar to enlarge.</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Full Name *</label>
                                <input type="text" name="name" class="form-control rounded-3 font-semibold" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Gender</label>
                                <select name="gender" class="form-select rounded-3 font-semibold">
                                    <option value="Male" <?php echo ($user['gender'] ?? 'Male') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Phone Number *</label>
                                <input type="text" name="mobile" class="form-control rounded-3 font-semibold" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Email Address</label>
                                <input type="email" class="form-control rounded-3 bg-light font-semibold" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">ID Proof (Aadhar / Passport)</label>
                                <input type="text" name="id_proof" class="form-control rounded-3 font-semibold" value="<?php echo htmlspecialchars($user['id_proof'] ?? ''); ?>" placeholder="e.g. Aadhar / Passport No.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Customer Account ID</label>
                                <input type="text" class="form-control rounded-3 bg-light font-semibold" value="#CUST-<?php echo htmlspecialchars($user['customer_id'] ?? '101'); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label small font-bold text-secondary mb-1">Residential Address</label>
                                <textarea name="address" rows="3" class="form-control rounded-3 font-semibold" placeholder="Enter street address, city, state, pincode"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-luxury-primary rounded-pill px-4 py-2 font-bold shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ================================================== -->
            <!-- 2. ACCOUNT SECTION (#account) -->
            <!-- ================================================== -->
            <div class="settings-pane d-none" id="pane-account">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="border-bottom pb-3 mb-4">
                        <h5 class="font-serif-title fw-bold text-navy mb-1">Account Overview</h5>
                        <p class="small text-muted mb-0">Review your account status and membership details.</p>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-hashtag me-1 opacity-75"></i> Account Ref</span>
                                <div class="account-info-value font-serif-title text-primary">#CUST-<?php echo htmlspecialchars($user['customer_id'] ?? '101'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-regular fa-calendar me-1 opacity-75"></i> Member Since</span>
                                <div class="account-info-value font-serif-title"><?php echo !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'Jan 2026'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-shield-halved me-1 opacity-75"></i> Account Status</span>
                                <div class="mt-1">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 font-bold extra-small d-inline-flex align-items-center gap-1.5">
                                        <i class="fa-solid fa-circle-check"></i> Active Member
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-user me-1 opacity-75"></i> Full Name</span>
                                <div class="account-info-value"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-envelope me-1 opacity-75"></i> Email Address</span>
                                <div class="account-info-value text-truncate"><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-phone me-1 opacity-75"></i> Contact Phone</span>
                                <div class="account-info-value"><?php echo htmlspecialchars($user['mobile'] ?? 'Not set'); ?></div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-venus-mars me-1 opacity-75"></i> Gender</span>
                                <div class="account-info-value"><?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-id-card me-1 opacity-75"></i> ID Proof</span>
                                <div class="account-info-value"><?php echo !empty($user['id_proof']) ? htmlspecialchars($user['id_proof']) : 'Not Provided'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="account-info-box h-100">
                                <span class="account-info-label"><i class="fa-solid fa-hotel me-1 opacity-75"></i> Total Stays</span>
                                <div class="account-info-value text-primary"><?php echo (int)($booking_count ?? 0); ?> Bookings</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="account-info-box">
                                <span class="account-info-label"><i class="fa-solid fa-location-dot me-1 opacity-75"></i> Residential Address</span>
                                <div class="account-info-value small font-medium"><?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : 'No address specified'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone Card -->
                    <div class="p-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-4">
                        <h5 class="fw-bold text-danger mb-2 font-serif-title">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Danger Zone
                        </h5>
                        <p class="small text-muted mb-3">Irreversible actions regarding your HotelEase account.</p>

                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center justify-content-between pt-3 border-top border-danger border-opacity-25">
                            <div>
                                <strong class="d-block text-dark small font-bold">Logout From All Devices</strong>
                                <span class="text-muted extra-small">Sign out of all active web sessions across browsers.</span>
                            </div>
                            <a href="/hotel_website/user/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3.5 py-1.5 font-semibold" onclick="return confirm('Are you sure you want to log out?');">
                                Logout Sessions
                            </a>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center justify-content-between pt-3 mt-3 border-top border-danger border-opacity-25">
                            <div>
                                <strong class="d-block text-danger small font-bold">Delete Account</strong>
                                <span class="text-muted extra-small">Permanently delete your profile and booking history.</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3.5 py-1.5 font-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>



            <!-- ================================================== -->
            <!-- 5. PRIVACY & SECURITY SECTION (#security) -->
            <!-- ================================================== -->
            <div class="settings-pane d-none" id="pane-security">
                <form method="POST" action="" id="securityForm">
                    <input type="hidden" name="action" value="change_password">

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="font-serif-title fw-bold text-navy mb-1">Privacy &amp; Security</h5>
                            <p class="small text-muted mb-0">Update password and manage account security settings.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">New Password (Min 8 Characters) *</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="newPass" class="form-control rounded-start-3 font-semibold" minlength="8" placeholder="Enter new password" required>
                                    <button class="btn btn-outline-secondary rounded-end-3 px-3" type="button" onclick="togglePasswordVisibility('newPass', 'newPassIcon')" title="Toggle password visibility">
                                        <i class="fa-solid fa-eye" id="newPassIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-bold text-secondary mb-1">Confirm New Password *</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confPass" class="form-control rounded-start-3 font-semibold" minlength="8" placeholder="Re-enter new password" required>
                                    <button class="btn btn-outline-secondary rounded-end-3 px-3" type="button" onclick="togglePasswordVisibility('confPass', 'confPassIcon')" title="Toggle password visibility">
                                        <i class="fa-solid fa-eye" id="confPassIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-luxury-primary rounded-pill px-4 py-2 font-bold shadow-sm">
                                <i class="fa-solid fa-key me-1.5"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ================================================== -->
            <!-- 6. APPEARANCE SECTION (#appearance) (Item #5) -->
            <!-- ================================================== -->
            <div class="settings-pane d-none" id="pane-appearance">
                <form method="POST" action="" id="appearanceForm">
                    <input type="hidden" name="action" value="update_appearance">
                    <input type="hidden" name="theme_preference" id="themeInputHidden" value="<?php echo htmlspecialchars($user['theme_preference'] ?? 'light'); ?>">

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="font-serif-title fw-bold text-navy mb-1">Appearance</h5>
                            <p class="small text-muted mb-0">Select your preferred color theme and display interface.</p>
                        </div>

                        <div class="row g-4">
                            <!-- Light Mode Card -->
                            <div class="col-md-4">
                                <div class="appearance-select-card <?php echo ($user['theme_preference'] ?? 'light') === 'light' ? 'selected' : ''; ?>" id="cardLight" onclick="selectAppearanceTheme('light')">
                                    <div class="selected-badge"><i class="fa-solid fa-check"></i></div>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="p-2.5 bg-warning bg-opacity-10 text-warning rounded-circle">
                                            <i class="fa-solid fa-sun fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold text-navy mb-0 font-serif-title">Light Mode</h6>
                                    </div>
                                    <p class="small text-muted mb-0">Clean &amp; bright white layout with high contrast.</p>
                                </div>
                            </div>

                            <!-- Dark Mode Card -->
                            <div class="col-md-4">
                                <div class="appearance-select-card <?php echo ($user['theme_preference'] ?? '') === 'dark' ? 'selected' : ''; ?>" id="cardDark" onclick="selectAppearanceTheme('dark')">
                                    <div class="selected-badge"><i class="fa-solid fa-check"></i></div>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="p-2.5 bg-primary bg-opacity-10 text-primary rounded-circle">
                                            <i class="fa-solid fa-moon fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold text-navy mb-0 font-serif-title">Dark Mode</h6>
                                    </div>
                                    <p class="small text-muted mb-0">Elegant midnight navy layout for nighttime browsing.</p>
                                </div>
                            </div>

                            <!-- System Default Card -->
                            <div class="col-md-4">
                                <div class="appearance-select-card <?php echo ($user['theme_preference'] ?? '') === 'system' ? 'selected' : ''; ?>" id="cardSystem" onclick="selectAppearanceTheme('system')">
                                    <div class="selected-badge"><i class="fa-solid fa-check"></i></div>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="p-2.5 bg-secondary bg-opacity-10 text-secondary rounded-circle">
                                            <i class="fa-solid fa-desktop fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold text-navy mb-0 font-serif-title">System Default</h6>
                                    </div>
                                    <p class="small text-muted mb-0">Automatically match your operating system theme settings.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-luxury-primary rounded-pill px-4 py-2 font-bold shadow-sm">
                                <i class="fa-solid fa-palette me-1.5"></i> Apply Theme
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
</main>

<!-- DELETE ACCOUNT CONFIRMATION MODAL -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title font-serif-title fw-bold text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Delete Account Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-2">
                <p class="text-dark font-medium mb-2">Are you sure you want to delete your HotelEase account?</p>
                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 small">
                    <i class="fa-solid fa-circle-info me-1"></i> This action is permanent and cannot be undone. All your profile information and stay history will be deleted.
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-bold shadow-sm">Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     PHOTO PREVIEW LIGHTBOX MODAL
   ========================================================= -->
<div class="modal fade" id="userPhotoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-dark text-white">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title font-serif-title fw-bold text-white" id="userPhotoPreviewTitle">
                    <i class="fa-solid fa-image text-info me-2"></i>Profile Photo Preview
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <!-- Interactive Zoom Toolbar Controls -->
                <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-3 bg-secondary bg-opacity-25 rounded-pill px-3 py-1.5 border border-secondary border-opacity-25 shadow-sm">
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="zoomUserPhotoModal(-0.25)" title="Zoom Out (-)">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </button>
                    <span id="userPhotoZoomLevelText" class="extra-small font-semibold px-2 text-white-50" style="min-width: 48px;">100%</span>
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="zoomUserPhotoModal(0.25)" title="Zoom In (+)">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-dark rounded-circle p-0 d-inline-flex align-items-center justify-content-center ms-1" style="width: 32px; height: 32px;" onclick="resetUserPhotoModalZoom()" title="Reset Zoom">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

                <div id="userPhotoPreviewImgWrapper" class="d-flex align-items-center justify-content-center overflow-hidden" style="min-height: 250px; max-height: 65vh;">
                    <img id="userPhotoPreviewImg" src="" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 60vh; object-fit: contain; transition: transform 0.2s ease;">
                </div>

                <div id="userPhotoPreviewSubtext" class="fw-bold fs-6 text-white mt-3"></div>
                <div class="extra-small text-white-50 mt-1"><i class="fa-solid fa-circle-info me-1"></i> Use zoom controls to inspect photo in high resolution</div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <a id="userPhotoDownloadBtn" href="#" download class="btn btn-primary btn-sm rounded-pill px-4 font-semibold me-2 d-none">
                    <i class="fa-solid fa-download me-1"></i> Download Image
                </a>
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-4 font-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
<script>
// Tab Switching & URL Hash Anchor Handler (#security, #profile, etc.)
function switchSettingsTab(tabName) {
    const validTabs = ['profile', 'account', 'security', 'appearance'];
    if (!validTabs.includes(tabName)) tabName = 'profile';

    // Hide all panes
    document.querySelectorAll('.settings-pane').forEach(p => p.classList.add('d-none'));
    // Deactivate all nav buttons
    document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));

    // Show target pane & activate button
    const targetPane = document.getElementById('pane-' + tabName);
    const targetNav = document.getElementById('tab-nav-' + tabName);

    if (targetPane) targetPane.classList.remove('d-none');
    if (targetNav) targetNav.classList.add('active');

    // Update URL hash without forcing jump scroll
    if (history.pushState) {
        history.pushState(null, null, '#' + tabName);
    } else {
        window.location.hash = tabName;
    }
}

// Appearance Theme Selection Handler
function selectAppearanceTheme(theme) {
    const hiddenInput = document.getElementById('themeInputHidden');
    if (hiddenInput) hiddenInput.value = theme;
    
    document.querySelectorAll('.appearance-select-card').forEach(c => c.classList.remove('selected'));
    
    if (theme === 'light') document.getElementById('cardLight')?.classList.add('selected');
    if (theme === 'dark') document.getElementById('cardDark')?.classList.add('selected');
    if (theme === 'system') document.getElementById('cardSystem')?.classList.add('selected');

    applyTheme(theme);
}

function applyTheme(theme) {
    try {
        localStorage.setItem('hotel_ease_theme', theme);
    } catch(e){}

    const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        document.documentElement.style.colorScheme = 'dark';
        document.body.classList.add('dark-theme');
    } else {
        document.documentElement.removeAttribute('data-theme');
        document.documentElement.setAttribute('data-bs-theme', 'light');
        document.documentElement.style.colorScheme = 'light';
        document.body.classList.remove('dark-theme');
    }
}

// Toggle Password Eye Visibility JS
function togglePasswordVisibility(inputId, iconId) {
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

let currentPhotoZoom = 1;

function openPhotoModal(imgSrc, titleName) {
    if (!imgSrc || imgSrc.endsWith('#') || imgSrc.length < 5) return;
    const imgElem = document.getElementById('userPhotoPreviewImg');
    const titleElem = document.getElementById('userPhotoPreviewSubtext');
    const downloadBtn = document.getElementById('userPhotoDownloadBtn');
    
    imgElem.src = imgSrc;
    if (titleElem) titleElem.textContent = titleName || 'Profile Photo';
    if (downloadBtn) {
        downloadBtn.href = imgSrc;
        downloadBtn.classList.remove('d-none');
    }
    resetUserPhotoModalZoom();
    
    const modal = new bootstrap.Modal(document.getElementById('userPhotoPreviewModal'));
    modal.show();
}

function zoomUserPhotoModal(delta) {
    const img = document.getElementById('userPhotoPreviewImg');
    if (!img) return;
    currentPhotoZoom = Math.min(Math.max(0.5, currentPhotoZoom + delta), 3);
    img.style.transform = `scale(${currentPhotoZoom})`;
    const text = document.getElementById('userPhotoZoomLevelText');
    if (text) text.textContent = Math.round(currentPhotoZoom * 100) + '%';
}

function resetUserPhotoModalZoom() {
    currentPhotoZoom = 1;
    const img = document.getElementById('userPhotoPreviewImg');
    if (img) img.style.transform = 'scale(1)';
    const text = document.getElementById('userPhotoZoomLevelText');
    if (text) text.textContent = '100%';
}

// Photo Upload Live Preview
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
            if (fallback) fallback.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    }
});

// Auto activate tab on page load based on URL hash (e.g. settings.php#account)
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.replace('#', '');
    if (['profile', 'account', 'security', 'appearance'].includes(hash)) {
        switchSettingsTab(hash);
    } else {
        switchSettingsTab('profile');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
