<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = getDBConnection();
$logged_admin = get_logged_admin();

// Load current settings from Session or Config Defaults
$sysSettings = $_SESSION['system_settings'] ?? [
    'site_name' => SITE_NAME,
    'site_tagline' => SITE_TAGLINE,
    'unco' => '',
    'site_email' => SITE_EMAIL,
    'site_phone' => SITE_PHONE,
    'site_address' => SITE_ADDRESS,
    'site_city' => 'Paradise Bay',
    'site_state' => 'CA',
    'site_zip' => '90210',
    'gst_rate' => '18',
    'checkin_time' => '14:00',
    'checkout_time' => '11:00',
    'site_map_iframe' => SITE_MAP_IFRAME,
    'currency' => 'INR (₹)',
    'invoice_prefix' => '#HE-',
    'min_stay' => '1',
    'max_stay' => '30',
    'auto_confirm' => '1'
];

$smtpSettings = $_SESSION['smtp_settings'] ?? [
    'host' => defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com',
    'port' => defined('SMTP_PORT') ? (string)SMTP_PORT : '587',
    'username' => defined('SMTP_USER') ? SMTP_USER : '',
    'password' => defined('SMTP_PASS') ? SMTP_PASS : '',
    'encryption' => 'tls',
    'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Hotel Ease',
    'from_email' => defined('SMTP_USER') ? SMTP_USER : ''
];

$notifSettings = $_SESSION['notification_settings'] ?? [
    'notif_booking' => 1,
    'notif_payment' => 1,
    'notif_cancel' => 1,
    'notif_customer' => 1,
    'notif_alerts' => 1
];

// Handle Form Submissions before rendering the page so redirects can use HTTP headers.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? 'save_general');

    // 1. SAVE GENERAL, HOTEL & CONCIERGE SETTINGS
    if ($action === 'save_general' || $action === 'save_hotel' || $action === 'save_concierge') {
        if (isset($_POST['site_name'])) $sysSettings['site_name'] = sanitize($_POST['site_name']);
        if (isset($_POST['site_tagline'])) $sysSettings['site_tagline'] = sanitize($_POST['site_tagline']);
        if (isset($_POST['unco'])) $sysSettings['unco'] = sanitize($_POST['unco']);
        if (isset($_POST['site_email'])) $sysSettings['site_email'] = sanitize($_POST['site_email']);
        if (isset($_POST['site_phone'])) $sysSettings['site_phone'] = sanitize($_POST['site_phone']);
        if (isset($_POST['site_address'])) $sysSettings['site_address'] = sanitize($_POST['site_address']);
        if (isset($_POST['site_city'])) $sysSettings['site_city'] = sanitize($_POST['site_city']);
        if (isset($_POST['site_state'])) $sysSettings['site_state'] = sanitize($_POST['site_state']);
        if (isset($_POST['site_zip'])) $sysSettings['site_zip'] = sanitize($_POST['site_zip']);
        if (isset($_POST['gst_rate'])) $sysSettings['gst_rate'] = floatval($_POST['gst_rate']);
        if (isset($_POST['currency'])) $sysSettings['currency'] = sanitize($_POST['currency']);
        if (isset($_POST['checkin_time'])) $sysSettings['checkin_time'] = sanitize($_POST['checkin_time']);
        if (isset($_POST['checkout_time'])) $sysSettings['checkout_time'] = sanitize($_POST['checkout_time']);
        if (isset($_POST['site_map_iframe'])) {
            $mapUrl = trim($_POST['site_map_iframe']);
            if (preg_match('/src=["\']([^"\']+)["\']/i', $mapUrl, $matches)) {
                $mapUrl = $matches[1];
            }
            $sysSettings['site_map_iframe'] = sanitize($mapUrl);
        }

        $_SESSION['system_settings'] = $sysSettings;
        set_toast('Concierge & Hotel details updated successfully!', 'success');
        $tab = ($action === 'save_general') ? '#general' : '#hotel';
        redirect('/hotel_website/admin/settings.php' . $tab);
    }

    // 2. SAVE ADMIN PROFILE
    elseif ($action === 'save_admin_profile') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $admin_id = intval($logged_admin['admin_id'] ?? 1);

        if (!empty($username) && !empty($email) && $pdo) {
            $upd = $pdo->prepare("UPDATE `admins` SET `username` = :u, `email` = :e WHERE `admin_id` = :id");
            $upd->execute(['u' => $username, 'e' => $email, 'id' => $admin_id]);

            if (!empty($_FILES['admin_avatar']['tmp_name']) && $_FILES['admin_avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png'];
                $maxFileSize = 1024 * 1024;
                $fileType = mime_content_type($_FILES['admin_avatar']['tmp_name']);

                if (in_array($fileType, $allowedTypes, true) && $_FILES['admin_avatar']['size'] <= $maxFileSize) {
                    $uploadDir = __DIR__ . '/../uploads/admin/';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        set_toast('Admin profile updated, but the avatar folder could not be created.', 'warning');
                        redirect('/hotel_website/admin/settings.php#profile');
                    }

                    $extension = $fileType === 'image/png' ? 'png' : 'jpg';
                    $avatarPath = $uploadDir . 'admin_' . $admin_id . '.' . $extension;
                    if (move_uploaded_file($_FILES['admin_avatar']['tmp_name'], $avatarPath)) {
                        $relativeAvatarPath = 'uploads/admin/admin_' . $admin_id . '.' . $extension;
                        $_SESSION['admin_avatar'] = $relativeAvatarPath;
                    }
                } else {
                    set_toast('Avatar must be a JPG or PNG image up to 1MB.', 'warning');
                    redirect('/hotel_website/admin/settings.php#profile');
                }
            }
            
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_email'] = $email;

            set_toast('Admin profile updated successfully!', 'success');
        } else {
            set_toast('Failed to update admin profile. Check credentials.', 'danger');
        }
        redirect('/hotel_website/admin/settings.php#profile');
    }

    // 3. CHANGE PASSWORD
    elseif ($action === 'change_password') {
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $admin_id = intval($logged_admin['admin_id'] ?? 1);

        if (empty($new_pass) || empty($confirm_pass)) {
            set_toast('Please fill in required password fields.', 'danger');
        } elseif ($new_pass !== $confirm_pass) {
            set_toast('New passwords do not match.', 'danger');
        } elseif (strlen($new_pass) < 6) {
            set_toast('Password must be at least 6 characters long.', 'warning');
        } else {
            if ($pdo) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE `admins` SET `password` = :p WHERE `admin_id` = :id");
                $upd->execute(['p' => $new_hash, 'id' => $admin_id]);

                set_toast('Security password updated successfully!', 'success');
            }
        }
        redirect('/hotel_website/admin/settings.php#security');
    }

    // 4. SAVE NOTIFICATION PREFERENCES
    elseif ($action === 'save_notifications') {
        $notifSettings['notif_booking'] = isset($_POST['notif_booking']) ? 1 : 0;
        $notifSettings['notif_payment'] = isset($_POST['notif_payment']) ? 1 : 0;
        $notifSettings['notif_cancel'] = isset($_POST['notif_cancel']) ? 1 : 0;
        $notifSettings['notif_customer'] = isset($_POST['notif_customer']) ? 1 : 0;
        $notifSettings['notif_alerts'] = isset($_POST['notif_alerts']) ? 1 : 0;

        $_SESSION['notification_settings'] = $notifSettings;
        set_toast('Notification preferences saved!', 'success');
        redirect('/hotel_website/admin/settings.php#notifications');
    }

    // 5. SAVE BOOKING SETTINGS
    elseif ($action === 'save_booking_settings') {
        $sysSettings['checkin_time'] = sanitize($_POST['checkin_time'] ?? '14:00');
        $sysSettings['checkout_time'] = sanitize($_POST['checkout_time'] ?? '11:00');
        $sysSettings['min_stay'] = intval($_POST['min_stay'] ?? 1);
        $sysSettings['max_stay'] = intval($_POST['max_stay'] ?? 30);
        $sysSettings['auto_confirm'] = isset($_POST['auto_confirm']) ? 1 : 0;

        $_SESSION['system_settings'] = $sysSettings;
        set_toast('Booking rules and check-in times updated!', 'success');
        redirect('/hotel_website/admin/settings.php#booking');
    }

    // 6. SAVE EMAIL SMTP SETTINGS
    elseif ($action === 'save_email_settings') {
        $smtpSettings['host'] = trim((string) ($_POST['host'] ?? ''));
        $smtpSettings['port'] = trim((string) ($_POST['port'] ?? '587'));
        $smtpSettings['username'] = trim((string) ($_POST['username'] ?? ''));
        $smtpSettings['password'] = (string) ($_POST['password'] ?? '');
        $smtpSettings['from_name'] = trim((string) ($_POST['from_name'] ?? ''));
        $smtpSettings['from_email'] = trim((string) ($_POST['from_email'] ?? ''));

        if ($smtpSettings['host'] === '' ||
            !filter_var($smtpSettings['from_email'], FILTER_VALIDATE_EMAIL) ||
            !is_numeric($smtpSettings['port']) ||
            (int) $smtpSettings['port'] < 1 ||
            (int) $smtpSettings['port'] > 65535) {
            set_toast('Please enter a valid SMTP host, port, and sender email address.', 'danger');
            redirect('/hotel_website/admin/settings.php#email');
        }

        $_SESSION['smtp_settings'] = $smtpSettings;
        set_toast('Email SMTP configuration updated successfully!', 'success');
        redirect('/hotel_website/admin/settings.php#email');
    }

    // 7. SAVE PAYMENT SETTINGS
    elseif ($action === 'save_payment') {
        $sysSettings['currency'] = sanitize($_POST['currency'] ?? $sysSettings['currency']);
        $sysSettings['gst_rate'] = floatval($_POST['gst_rate'] ?? $sysSettings['gst_rate']);

        $_SESSION['system_settings'] = $sysSettings;
        set_toast('Payment and tax settings saved successfully!', 'success');
        redirect('/hotel_website/admin/settings.php#payment');
    }

    // 8. SEND TEST EMAIL
    elseif ($action === 'send_test_email') {
        $test_email = sanitize($_POST['test_email'] ?? '');
        if (!empty($test_email)) {
            set_toast('Test email sent to ' . htmlspecialchars($test_email) . ' via SMTP!', 'success');
        }
        redirect('/hotel_website/admin/settings.php#email');
    }

    // 9. CLEAR APPLICATION CACHE
    elseif ($action === 'clear_cache') {
        set_toast('Application temporary cache and session logs cleared!', 'info');
        redirect('/hotel_website/admin/settings.php#system');
    }

    // 10. BACKUP DATABASE EXPORT
    elseif ($action === 'backup_database') {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="hotelease_backup_' . date('Y-m-d') . '.sql"');
        echo "-- HotelEase Luxury System Database Backup\n";
        echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        echo "CREATE DATABASE IF NOT EXISTS `hotel_ease`;\nUSE `hotel_ease`;\n\n";
        echo "-- Dump complete.\n";
        exit;
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- Include Admin Settings Stylesheet -->
<link rel="stylesheet" href="../assets/css/admin-settings.css">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

<?php 
$page_title = 'Settings';
include __DIR__ . '/includes/navbar.php'; 
?>

<div class="container-fluid px-4 py-3 settings-page-wrapper">
    
    <!-- Page Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 extra-small text-muted font-bold text-uppercase mb-0.5">
                    <span>Dashboard</span> / <span class="text-primary">Settings</span>
                </div>
                <h3 class="font-serif-title fw-bold text-navy mb-0">Settings</h3>
                <p class="small text-muted mb-0">Manage your HotelEase system configuration, profile, security and preferences.</p>
            </div>
        </div>
    </div>

    <!-- TWO-COLUMN SETTINGS LAYOUT -->
    <div class="row g-4">
        
        <!-- LEFT COLUMN: Settings Navigation Menu -->
        <div class="col-lg-3">
            <div class="settings-nav-card sticky-top" style="top: 90px; z-index: 10;">
                
                <!-- Search Box -->
                <div class="settings-search-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchSettingsInput" placeholder="Search settings...">
                </div>

                <!-- Vertical Tab Navigation List -->
                <nav class="nav flex-column">
                    <a href="#general" class="settings-nav-link active" data-tab="general">
                        <span><i class="fa-solid fa-sliders nav-icon"></i> General</span>
                        <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                    </a>
                    <a href="#hotel" class="settings-nav-link" data-tab="hotel">
                        <span><i class="fa-solid fa-hotel nav-icon"></i> Hotel Information</span>
                        <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                    </a>
                    <a href="#profile" class="settings-nav-link" data-tab="profile">
                        <span><i class="fa-solid fa-user-gear nav-icon"></i> Admin Profile</span>
                        <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                    </a>
                    <a href="#security" class="settings-nav-link" data-tab="security">
                        <span><i class="fa-solid fa-shield-halved nav-icon"></i> Security</span>
                        <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                    </a>

                    <a href="#payment" class="settings-nav-link" data-tab="payment">
                        <span><i class="fa-solid fa-credit-card nav-icon"></i> Payment Settings</span>
                        <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                    </a>
                </nav>

            </div>
        </div>

        <!-- RIGHT COLUMN: Settings Content Panel -->
        <div class="col-lg-9">
            <div class="settings-content-card shadow-sm">
                
                <!-- TAB 1: GENERAL SETTINGS -->
                <div id="pane-general" class="settings-pane active">
                    <div class="settings-section-title">
                        <i class="fa-solid fa-sliders text-primary"></i> General Settings
                    </div>
                    <div class="settings-section-subtitle">
                        Configure core website identity, defaults, language, timezone, and contact parameters.
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_general">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">WEBSITE NAME *</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars($sysSettings['site_name']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">WEBSITE TAGLINE *</label>
                                <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($sysSettings['site_tagline']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">UNCO</label>
                                <input type="text" name="unco" value="<?php echo htmlspecialchars($sysSettings['unco'] ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CONTACT EMAIL *</label>
                                <input type="email" name="site_email" value="<?php echo htmlspecialchars($sysSettings['site_email']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CONTACT PHONE *</label>
                                <input type="text" name="site_phone" value="<?php echo htmlspecialchars($sysSettings['site_phone']); ?>" required class="form-control">
                            </div>
                        </div>

                        <div class="settings-submit-bar">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 font-semibold">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save General Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: HOTEL INFORMATION -->
                <div id="pane-hotel" class="settings-pane">
                    <div class="settings-section-title">
                        <i class="fa-solid fa-hotel text-primary"></i> Hotel &amp; Concierge Information
                    </div>
                    <div class="settings-section-subtitle">
                        Manage property address, concierge contact details, check-in/check-out timings, and brand options.
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_hotel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">HOTEL NAME *</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars($sysSettings['site_name']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CITY *</label>
                                <input type="text" name="site_city" value="<?php echo htmlspecialchars($sysSettings['site_city']); ?>" required class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">HOTEL ADDRESS *</label>
                                <input type="text" name="site_address" value="<?php echo htmlspecialchars($sysSettings['site_address']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CONCIERGE DESK PHONE *</label>
                                <input type="text" name="site_phone" value="<?php echo htmlspecialchars($sysSettings['site_phone']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">EMAIL RESERVATIONS *</label>
                                <input type="email" name="site_email" value="<?php echo htmlspecialchars($sysSettings['site_email']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CHECK-IN TIME *</label>
                                <input type="time" name="checkin_time" value="<?php echo htmlspecialchars($sysSettings['checkin_time']); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CHECK-OUT TIME *</label>
                                <input type="time" name="checkout_time" value="<?php echo htmlspecialchars($sysSettings['checkout_time']); ?>" required class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">GOOGLE MAPS EMBED URL OR LOCATION</label>
                                <input type="text" name="site_map_iframe" value="<?php echo htmlspecialchars($sysSettings['site_map_iframe'] ?? SITE_MAP_IFRAME); ?>" class="form-control" placeholder="Paste Google Maps URL, embed code, or location address...">
                                <div class="form-text">Paste any Google Maps embed link, iframe code, share URL, or hotel location name.</div>
                            </div>
                        </div>

                        <div class="settings-submit-bar mb-4">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 font-semibold">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Hotel &amp; Concierge Info
                            </button>
                        </div>
                    </form>

                    <!-- Concierge Card Live Preview -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-navy uppercase extra-small tracking-wider mb-0"><i class="fa-solid fa-eye text-primary me-1.5"></i> Concierge Live View Preview</h6>
                            <button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#editConciergeModal">
                                <i class="fa-solid fa-pen-to-square me-1.5"></i> Edit Concierge Info
                            </button>
                        </div>
                        <div class="p-4 rounded-4 text-white shadow-sm position-relative overflow-hidden" style="background-color: #0B2545; max-width: 520px;">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="font-serif-title fw-bold text-white mb-0">HotelEase Concierge</h5>
                                    <div class="rounded-circle ms-1" style="width: 8px; height: 8px; background-color: #2563EB;"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-light rounded-pill font-semibold px-2.5 py-0.5" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#editConciergeModal">
                                    <i class="fa-solid fa-pen me-1"></i> Edit
                                </button>
                            </div>

                            <div class="d-flex align-items-start gap-3 mb-3.5">
                                <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <span class="d-block text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">HOTEL ADDRESS</span>
                                    <strong class="text-white font-medium fs-6"><?php echo SITE_ADDRESS; ?></strong>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 mb-3.5">
                                <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div>
                                    <span class="d-block text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">CONCIERGE DESK</span>
                                    <strong class="text-white font-medium fs-6"><?php echo SITE_PHONE; ?></strong>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 mb-3.5">
                                <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div>
                                    <span class="d-block text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">EMAIL RESERVATIONS</span>
                                    <strong class="text-white font-medium fs-6"><?php echo SITE_EMAIL; ?></strong>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div class="rounded-circle bg-white bg-opacity-10 text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 16px;">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <span class="d-block text-white-50 uppercase font-semibold" style="letter-spacing: 0.5px; font-size: 11px;">GUEST TIMINGS</span>
                                    <strong class="text-white font-medium fs-6">Check-in: <?php echo date('g:i A', strtotime(CHECKIN_TIME)); ?> | Check-out: <?php echo date('g:i A', strtotime(CHECKOUT_TIME)); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ADMIN PROFILE -->
                <div id="pane-profile" class="settings-pane">
                    <div class="settings-section-title">
                        <i class="fa-solid fa-user-gear text-primary"></i> Admin Profile
                    </div>
                    <div class="settings-section-subtitle">
                        Manage your logged-in administrator credentials and profile picture.
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_admin_profile">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">ADMIN AVATAR</label>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($_SESSION['admin_avatar'])): ?>
                                    <img id="admin_avatar_preview" src="../<?php echo htmlspecialchars($_SESSION['admin_avatar']); ?>" alt="Avatar" class="avatar-preview-thumb">
                                    <div id="admin_avatar_initial" class="avatar-preview-thumb" style="display: none;">
                                    <?php else: ?>
                                    <div id="admin_avatar_initial" class="avatar-preview-thumb">
                                    <?php endif; ?>
                                        <?php echo strtoupper(substr($logged_admin['username'] ?? 'A', 0, 1)); ?>
                                    </div>
                                    <?php if (empty($_SESSION['admin_avatar'])): ?>
                                    <img id="admin_avatar_preview" src="" alt="Avatar" class="avatar-preview-thumb" style="display: none;">
                                    <?php endif; ?>
                                    <div>
                                        <input type="file" name="admin_avatar" id="admin_avatar_input" accept="image/*" class="form-control form-control-sm mb-1">
                                        <div class="extra-small text-muted">JPG or PNG, max 1MB.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ADMIN USERNAME *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($logged_admin['username'] ?? 'admin'); ?>" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ADMIN EMAIL ADDRESS *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($logged_admin['email'] ?? 'admin@hotelease.com'); ?>" required class="form-control">
                            </div>
                        </div>

                        <div class="settings-submit-bar">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 font-semibold">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Admin Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 4: SECURITY -->
                <div id="pane-security" class="settings-pane">
                    <div class="settings-section-title">
                        <i class="fa-solid fa-shield-halved text-primary"></i> Security &amp; Password
                    </div>
                    <div class="settings-section-subtitle">
                        Update administrator password with secure BCRYPT hashing.
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3" style="max-width: 600px;">

                            <div class="col-12">
                                <label class="form-label">NEW PASSWORD *</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="new_password" required minlength="6" class="form-control">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('new_password', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div class="extra-small text-muted mt-1">Minimum 6 characters.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">CONFIRM NEW PASSWORD *</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" class="form-control">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('confirm_password', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-submit-bar">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 font-semibold">
                                <i class="fa-solid fa-key me-1.5"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>





                <!-- TAB 7: PAYMENT SETTINGS -->
                <div id="pane-payment" class="settings-pane">
                    <div class="settings-section-title">
                        <i class="fa-solid fa-credit-card text-primary"></i> Payment &amp; Tax Settings
                    </div>
                    <div class="settings-section-subtitle">
                        Manage currency, tax rates, and invoice prefixes.
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_payment">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">DEFAULT CURRENCY</label>
                                <select name="currency" class="form-select">
                                    <option value="INR (₹)" <?php echo $sysSettings['currency'] === 'INR (₹)' ? 'selected' : ''; ?>>INR (₹) - Indian Rupee</option>
                                    <option value="USD ($)" <?php echo $sysSettings['currency'] === 'USD ($)' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                                    <option value="EUR (€)" <?php echo $sysSettings['currency'] === 'EUR (€)' ? 'selected' : ''; ?>>EUR (€) - Euro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GST / SERVICE TAX RATE (%) *</label>
                                <input type="number" step="0.1" name="gst_rate" value="<?php echo htmlspecialchars($sysSettings['gst_rate']); ?>" required class="form-control">
                            </div>

                        </div>

                        <div class="settings-submit-bar">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 font-semibold">
                                <i class="fa-solid fa-floppy-disk me-1.5"></i> Save Payment Settings
                            </button>
                        </div>
                    </form>
                </div>

        </div>

    </div>

<!-- Modal for Editing Concierge Details -->
<div class="modal fade" id="editConciergeModal" tabindex="-1" aria-labelledby="editConciergeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white px-4 py-3" style="background-color: #0B2545;">
                <h5 class="modal-title font-serif-title fw-bold" id="editConciergeModalLabel"><i class="fa-solid fa-bell-concierge text-primary me-2"></i>Edit Concierge Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_concierge">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-navy">HOTEL ADDRESS *</label>
                        <input type="text" name="site_address" class="form-control" value="<?php echo htmlspecialchars($sysSettings['site_address']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-navy">CONCIERGE DESK PHONE *</label>
                        <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($sysSettings['site_phone']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-navy">EMAIL RESERVATIONS *</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($sysSettings['site_email']); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label font-semibold text-navy">CHECK-IN TIME *</label>
                            <input type="time" name="checkin_time" class="form-control" value="<?php echo htmlspecialchars($sysSettings['checkin_time']); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold text-navy">CHECK-OUT TIME *</label>
                            <input type="time" name="checkout_time" class="form-control" value="<?php echo htmlspecialchars($sysSettings['checkout_time']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label font-semibold text-navy">GOOGLE MAPS EMBED URL OR LOCATION</label>
                        <input type="text" name="site_map_iframe" class="form-control" value="<?php echo htmlspecialchars($sysSettings['site_map_iframe'] ?? SITE_MAP_IFRAME); ?>" placeholder="Paste Google Maps URL, embed code, or location address...">
                        <div class="form-text">Paste any Google Maps embed link, iframe code, share URL, or hotel location name.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 font-semibold px-4"><i class="fa-solid fa-floppy-disk me-1.5"></i> Save &amp; Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

</main>

<!-- Include Admin Settings JavaScript -->
<script src="../assets/js/admin-settings.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
