<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$siteTheme = $_COOKIE['hotel_ease_theme'] ?? 'light';
if (isset($_SESSION['user_id'])) {
    $uData = get_logged_user();
    if (!empty($uData['theme_preference'])) {
        $siteTheme = $uData['theme_preference'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> — <?php echo SITE_TAGLINE; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- html2pdf.js for Client-Side Invoice PDF Generation & Download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <!-- Bootstrap Icons 1.11 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Hotel Ease Blue & White Luxury Design System CSS & JS -->
    <link rel="stylesheet" href="/hotel_website/assets/css/hotel-ease.css">
    <script src="/hotel_website/assets/js/hotel-ease.js?v=20261007" defer></script>
    <style>
        :root {
            --deep-navy: #0B1F3A;
            --royal-blue: #2563EB;
            --sky-blue: #38BDF8;
            --light-bg: #F5F8FC;
            --dark-text: #111827;
            --gold-accent: #C9A227;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        .font-serif-title {
            font-family: 'Playfair Display', serif;
        }

        .bg-deep-navy { background-color: var(--deep-navy) !important; }
        .bg-royal-blue { background-color: var(--royal-blue) !important; }
        .bg-light-bg { background-color: var(--light-bg) !important; }

        .text-gold { color: var(--gold-accent) !important; }
        .text-navy { color: var(--deep-navy) !important; }
        .text-blue { color: var(--royal-blue) !important; }

        /* Hide native browser password reveal & clear icons (MS Edge / IE) */
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* High-Contrast Input Placeholders */
        ::placeholder,
        .form-control::placeholder,
        .form-select::placeholder {
            color: #64748b !important;
            opacity: 0.85 !important;
        }

        [data-theme="dark"] ::placeholder,
        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] .form-select::placeholder,
        [data-bs-theme="dark"] ::placeholder,
        [data-bs-theme="dark"] .form-control::placeholder,
        [data-bs-theme="dark"] .form-select::placeholder,
        body.dark-theme ::placeholder,
        body.dark-theme .form-control::placeholder,
        body.dark-theme .form-select::placeholder {
            color: #94a3b8 !important;
            opacity: 0.85 !important;
        }

        /* Red Input Validation Styling */
        .was-validated .form-control:invalid,
        .form-control.is-invalid,
        .was-validated .form-select:invalid,
        .form-select.is-invalid {
            border-color: #EF4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
        }

        .was-validated .input-group:has(:invalid) .input-group-text,
        .was-validated .input-group:has(:invalid) .form-control {
            border-color: #EF4444 !important;
        }

        .invalid-feedback,
        .invalid-feedback-custom {
            color: #EF4444 !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            margin-top: 5px !important;
            width: 100% !important;
            flex-basis: 100% !important;
        }

        .was-validated :invalid ~ .invalid-feedback,
        .was-validated :invalid ~ .invalid-feedback-custom,
        .was-validated .has-validation > :invalid ~ .invalid-feedback,
        .was-validated .has-validation > :invalid ~ .invalid-feedback-custom,
        .is-invalid ~ .invalid-feedback,
        .is-invalid ~ .invalid-feedback-custom {
            display: block !important;
        }

        /* Luxury Card Styling per spec #38 */
        .luxury-card {
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 30px rgba(11, 31, 58, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .luxury-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(11, 31, 58, 0.12);
            border-color: var(--sky-blue);
        }

        .luxury-card img {
            border-radius: 14px;
        }

        .btn-luxury-primary {
            background-color: var(--royal-blue);
            color: #FFFFFF;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 26px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-luxury-primary:hover {
            background-color: var(--deep-navy);
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-gold {
            border: 1px solid var(--gold-accent);
            color: var(--gold-accent) !important;
            background: rgba(201, 162, 39, 0.1);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background-color: var(--gold-accent) !important;
            color: #0B1F3A !important;
            border-color: var(--gold-accent);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(201, 162, 39, 0.35);
        }

        .btn-luxury-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #C9A227 100%);
            color: #0B1F3A !important;
            font-weight: 700;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-luxury-gold:hover {
            background: linear-gradient(135deg, #E5BE48 0%, #D4AF37 100%);
            color: #0B1F3A !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(201, 162, 39, 0.4);
        }

        /* Sticky Glass Navbar */
        .navbar-luxury {
            background-color: rgba(11, 31, 58, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Animated Luxury Background System */
        .bg-auth-animated {
            position: relative;
            background: linear-gradient(135deg, #050E1B 0%, #0B1F3A 50%, #07172E 100%);
            overflow: hidden;
        }

        .bg-auth-animated::before {
            content: '';
            position: absolute;
            width: 650px;
            height: 650px;
            top: -180px;
            left: -180px;
            background: radial-gradient(circle, rgba(201, 162, 39, 0.22) 0%, rgba(201, 162, 39, 0) 70%);
            border-radius: 50%;
            animation: luxuryFloatOne 14s infinite alternate ease-in-out;
            pointer-events: none;
        }

        .bg-auth-animated::after {
            content: '';
            position: absolute;
            width: 750px;
            height: 750px;
            bottom: -220px;
            right: -220px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.28) 0%, rgba(37, 99, 235, 0) 70%);
            border-radius: 50%;
            animation: luxuryFloatTwo 18s infinite alternate ease-in-out;
            pointer-events: none;
        }

        @keyframes luxuryFloatOne {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 0.7;
            }
            50% {
                transform: translate(120px, 80px) scale(1.18);
                opacity: 0.9;
            }
            100% {
                transform: translate(-60px, 140px) scale(0.9);
                opacity: 0.6;
            }
        }

        @keyframes luxuryFloatTwo {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }
            50% {
                transform: translate(-140px, -90px) scale(1.22);
                opacity: 1;
            }
            100% {
                transform: translate(70px, -150px) scale(0.95);
                opacity: 0.7;
            }
        }
        /* Custom Luxury Toast Animations & Styling */
        @keyframes toastBounceIn {
            0% {
                transform: translateY(-30px) scale(0.88);
                opacity: 0;
            }
            70% {
                transform: translateY(6px) scale(1.02);
                opacity: 1;
            }
            100% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        .toast-progress-bar {
            height: 3px;
            background: linear-gradient(90deg, #C9A227, #38BDF8);
            animation: toastProgress 4s linear forwards;
        }

        .btn-close-luxury {
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: rgba(255, 255, 255, 0.8);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
            flex-shrink: 0;
        }

        .btn-primary-blue,
        .btn-primary {
            background-color: #2563EB !important;
            border-color: #2563EB !important;
            color: #FFFFFF !important;
            transition: all 0.2s ease !important;
        }
        .btn-primary-blue:hover,
        .btn-primary:hover {
            background-color: #1D4ED8 !important;
            border-color: #1D4ED8 !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
        }

        /* =========================================================
           HOTEL EASE - ELEGANT DARK NAVY THEME SYSTEM
           ========================================================= */
        [data-theme="dark"],
        body.dark-theme {
            --primary-blue: #2563EB;
            --dark-blue: #F8FAFC;
            --light-blue: #1E3A8A;
            --bg-color: #0B172A;
            --text-color: #F8FAFC;
            --text-secondary: #94A3B8;
            --border-color: #334155;
            background-color: #0B172A !important;
            color: #F8FAFC !important;
        }

        /* Dark Theme Main Content & Cards */
        [data-theme="dark"] .main-content,
        [data-theme="dark"] .page-content,
        [data-theme="dark"] .dashboard-main,
        body.dark-theme .main-content,
        body.dark-theme .page-content,
        body.dark-theme .dashboard-main {
            background-color: #0B172A !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .bg-white,
        [data-theme="dark"] .bg-light,
        [data-theme="dark"] .card,
        [data-theme="dark"] .card-saas,
        [data-theme="dark"] .stat-card-saas,
        [data-theme="dark"] .stat-card-luxury,
        [data-theme="dark"] .settings-menu-card,
        [data-theme="dark"] .hotel-card,
        [data-theme="dark"] .room-card,
        body.dark-theme .bg-white,
        body.dark-theme .bg-light,
        body.dark-theme .card,
        body.dark-theme .card-saas,
        body.dark-theme .stat-card-saas,
        body.dark-theme .stat-card-luxury,
        body.dark-theme .settings-menu-card,
        body.dark-theme .hotel-card,
        body.dark-theme .room-card {
            background-color: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        /* Fix for stat cards numbers & text */
        [data-theme="dark"] .stat-card-saas h3,
        [data-theme="dark"] .stat-card-luxury h3,
        body.dark-theme .stat-card-saas h3,
        body.dark-theme .stat-card-luxury h3 {
            color: #38BDF8 !important;
        }

        [data-theme="dark"] .stat-card-saas span,
        [data-theme="dark"] .stat-card-luxury span,
        body.dark-theme .stat-card-saas span,
        body.dark-theme .stat-card-luxury span {
            color: #94A3B8 !important;
        }

        /* Dark Theme Headings & Text */
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, 
        [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
        [data-theme="dark"] .text-navy, [data-theme="dark"] .text-dark,
        [data-theme="dark"] .font-serif-title, [data-theme="dark"] .card-title,
        body.dark-theme h1, body.dark-theme h2, body.dark-theme h3, 
        body.dark-theme h4, body.dark-theme h5, body.dark-theme h6,
        body.dark-theme .text-navy, body.dark-theme .text-dark,
        body.dark-theme .font-serif-title, body.dark-theme .card-title {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .text-secondary,
        [data-theme="dark"] small,
        body.dark-theme .text-muted,
        body.dark-theme .text-secondary,
        body.dark-theme small {
            color: #94A3B8 !important;
        }

        /* Table styling for Recent Bookings in Dark Theme */
        [data-theme="dark"] .table,
        [data-theme="dark"] table,
        body.dark-theme .table,
        body.dark-theme table {
            background-color: #1E293B !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .table thead,
        [data-theme="dark"] table thead,
        [data-theme="dark"] .table thead tr,
        [data-theme="dark"] .table thead th,
        body.dark-theme .table thead,
        body.dark-theme table thead,
        body.dark-theme .table thead tr,
        body.dark-theme .table thead th {
            background-color: #0F172A !important;
            color: #38BDF8 !important;
            border-bottom: 1px solid #334155 !important;
        }

        [data-theme="dark"] .table tbody tr,
        [data-theme="dark"] .table tbody td,
        body.dark-theme .table tbody tr,
        body.dark-theme .table tbody td {
            background-color: #1E293B !important;
            color: #F8FAFC !important;
            border-color: #334155 !important;
        }

        /* Dark Theme Top Navbar */
        [data-theme="dark"] .top-sticky-navbar,
        body.dark-theme .top-sticky-navbar {
            background-color: #0F172A !important;
            border-bottom-color: #1E293B !important;
        }

        [data-theme="dark"] .top-sticky-navbar h5,
        body.dark-theme .top-sticky-navbar h5 {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .user-nav-pill,
        body.dark-theme .user-nav-pill {
            background-color: #1E293B !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .user-nav-pill span,
        body.dark-theme .user-nav-pill span {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .user-dropdown-menu,
        body.dark-theme .user-dropdown-menu {
            background-color: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .user-dropdown-header-link,
        body.dark-theme .user-dropdown-header-link {
            background-color: #0F172A !important;
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .user-dropdown-menu .dropdown-item,
        body.dark-theme .user-dropdown-menu .dropdown-item {
            color: #E2E8F0 !important;
        }
        [data-theme="dark"] .user-dropdown-menu .dropdown-item:hover,
        body.dark-theme .user-dropdown-menu .dropdown-item:hover {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
        }

        /* Dark Theme Sidebar */
        [data-theme="dark"] .hotel-sidebar,
        body.dark-theme .hotel-sidebar {
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%) !important;
            border-right-color: #1E293B !important;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.3) !important;
        }

        [data-theme="dark"] .sidebar-brand,
        [data-theme="dark"] .sidebar-bottom,
        body.dark-theme .sidebar-brand,
        body.dark-theme .sidebar-bottom {
            background-color: #0F172A !important;
            border-color: #1E293B !important;
        }

        [data-theme="dark"] .brand-logo,
        body.dark-theme .brand-logo {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .sidebar-link,
        body.dark-theme .sidebar-link {
            color: #CBD5E1 !important;
        }

        [data-theme="dark"] .sidebar-link i,
        body.dark-theme .sidebar-link i {
            color: #38BDF8 !important;
        }

        [data-theme="dark"] .sidebar-link:hover,
        body.dark-theme .sidebar-link:hover {
            background: rgba(56, 189, 248, 0.12) !important;
            color: #38BDF8 !important;
        }

        [data-theme="dark"] .sidebar-link.active,
        body.dark-theme .sidebar-link.active {
            background: #2563EB !important;
            color: #FFFFFF !important;
        }

        [data-theme="dark"] .sidebar-link.active i,
        body.dark-theme .sidebar-link.active i {
            color: #FFFFFF !important;
        }

        /* Dark Theme Logout Link Red Color */
        [data-theme="dark"] .sidebar-link.logout-link,
        [data-theme="dark"] .logout-link,
        body.dark-theme .sidebar-link.logout-link,
        body.dark-theme .logout-link {
            color: #EF4444 !important;
        }

        [data-theme="dark"] .sidebar-link.logout-link i,
        [data-theme="dark"] .logout-link i,
        body.dark-theme .sidebar-link.logout-link i,
        body.dark-theme .logout-link i {
            color: #EF4444 !important;
        }

        [data-theme="dark"] .sidebar-link.logout-link:hover,
        [data-theme="dark"] .logout-link:hover,
        body.dark-theme .sidebar-link.logout-link:hover,
        body.dark-theme .logout-link:hover {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #F87171 !important;
        }

        [data-theme="dark"] .sidebar-link.logout-link:hover i,
        [data-theme="dark"] .logout-link:hover i,
        body.dark-theme .sidebar-link.logout-link:hover i,
        body.dark-theme .logout-link:hover i {
            color: #F87171 !important;
        }

        /* Dark Theme Settings Navigation Buttons */
        [data-theme="dark"] .settings-nav-btn,
        body.dark-theme .settings-nav-btn {
            color: #CBD5E1 !important;
        }

        [data-theme="dark"] .settings-nav-btn:hover,
        body.dark-theme .settings-nav-btn:hover {
            background: #0F172A !important;
            color: #38BDF8 !important;
        }

        [data-theme="dark"] .settings-nav-btn.active,
        body.dark-theme .settings-nav-btn.active {
            background: #1E3A8A !important;
            color: #60A5FA !important;
            border-left-color: #3B82F6 !important;
        }

        [data-theme="dark"] .btn-outline-primary,
        [data-bs-theme="dark"] .btn-outline-primary,
        body.dark-theme .btn-outline-primary {
            color: #38BDF8 !important;
            border-color: #38BDF8 !important;
            background-color: transparent !important;
        }
        [data-theme="dark"] .btn-outline-primary:hover,
        [data-bs-theme="dark"] .btn-outline-primary:hover,
        body.dark-theme .btn-outline-primary:hover {
            background-color: #2563EB !important;
            border-color: #2563EB !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
        }

        /* Dark Theme Form Controls & Select Options */
        [data-theme="dark"],
        [data-bs-theme="dark"],
        body.dark-theme {
            color-scheme: dark !important;
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] input,
        [data-theme="dark"] textarea,
        [data-theme="dark"] select,
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select,
        [data-bs-theme="dark"] input,
        [data-bs-theme="dark"] textarea,
        [data-bs-theme="dark"] select,
        body.dark-theme .form-control,
        body.dark-theme .form-select,
        body.dark-theme input,
        body.dark-theme textarea,
        body.dark-theme select {
            background-color: #0F172A !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
            color-scheme: dark !important;
        }

        [data-theme="dark"] select option,
        [data-bs-theme="dark"] select option,
        body.dark-theme select option,
        [data-theme="dark"] .form-select option,
        [data-bs-theme="dark"] .form-select option,
        body.dark-theme .form-select option {
            background-color: #0F172A !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] select option:checked,
        [data-theme="dark"] select option:hover,
        [data-theme="dark"] select option:focus,
        [data-bs-theme="dark"] select option:checked,
        [data-bs-theme="dark"] select option:hover,
        [data-bs-theme="dark"] select option:focus,
        body.dark-theme select option:checked,
        body.dark-theme select option:hover,
        body.dark-theme select option:focus {
            background-color: #1E293B !important;
            color: #38BDF8 !important;
        }

        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] input::placeholder,
        body.dark-theme .form-control::placeholder,
        body.dark-theme input::placeholder {
            color: #64748B !important;
        }

        [data-theme="dark"] .form-control:read-only,
        [data-theme="dark"] .form-control:disabled,
        body.dark-theme .form-control:read-only,
        body.dark-theme .form-control:disabled {
            background-color: #1E293B !important;
            color: #94A3B8 !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] input[type="file"],
        body.dark-theme input[type="file"] {
            background-color: #0F172A !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] input[type="file"]::file-selector-button,
        body.dark-theme input[type="file"]::file-selector-button {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            border: none !important;
            padding: 4px 12px !important;
            border-radius: 6px !important;
            margin-right: 10px !important;
        }

        /* Modal dark theme support */
        [data-theme="dark"] .modal-content,
        [data-bs-theme="dark"] .modal-content,
        body.dark-theme .modal-content {
            background-color: #1E293B !important;
            color: #F8FAFC !important;
            border: 1px solid #334155 !important;
        }

        [data-theme="dark"] .modal-header .btn-close,
        [data-bs-theme="dark"] .modal-header .btn-close,
        body.dark-theme .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-theme="dark"] .modal-body .bg-danger.bg-opacity-10,
        [data-bs-theme="dark"] .modal-body .bg-danger.bg-opacity-10,
        body.dark-theme .modal-body .bg-danger.bg-opacity-10 {
            background-color: rgba(239, 68, 68, 0.2) !important;
            color: #FCA5A5 !important;
        }

        /* Theme Option Cards */
        .theme-option-card {
            transition: all 0.25s ease;
            cursor: pointer;
            border: 2px solid #E2E8F0 !important;
            background-color: #FFFFFF !important;
            color: #172554 !important;
        }
        .theme-option-card .small.text-muted {
            color: #64748B !important;
        }

        /* Light Mode Active Card */
        .theme-option-card.active-theme {
            border-color: #2563EB !important;
            background-color: #EFF6FF !important;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.18) !important;
        }

        /* Dark Theme Mode Option Cards */
        [data-theme="dark"] .theme-option-card,
        body.dark-theme .theme-option-card {
            background-color: #0F172A !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .theme-option-card .text-navy,
        body.dark-theme .theme-option-card .text-navy {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .theme-option-card .small.text-muted,
        body.dark-theme .theme-option-card .small.text-muted {
            color: #94A3B8 !important;
        }

        [data-theme="dark"] .theme-option-card.active-theme,
        body.dark-theme .theme-option-card.active-theme {
            border-color: #3B82F6 !important;
            background-color: #1E3A8A !important;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3) !important;
        }
        [data-theme="dark"] .theme-option-card.active-theme .text-navy,
        body.dark-theme .theme-option-card.active-theme .text-navy {
            color: #FFFFFF !important;
        }
        /* Modern Luxury Property Room Card Styling */
        .room-card-modern {
            background: #FFFFFF;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }

        .room-card-modern:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(11, 31, 58, 0.12);
            border-color: rgba(37, 99, 235, 0.35);
        }

        .room-card-img-wrap {
            position: relative;
            height: 220px;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
        }

        .room-card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .room-card-modern:hover .room-card-img-wrap img {
            transform: scale(1.08);
        }

        .room-card-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0) 40%, rgba(0,0,0,0.45) 100%);
            pointer-events: none;
        }

        .fav-btn-glass {
            position: absolute;
            top: 14px;
            left: 14px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748B;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.25s ease;
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .fav-btn-glass:hover {
            background: #FFFFFF;
            transform: scale(1.12);
            color: #EF4444;
        }

        .fav-btn-glass.text-danger {
            color: #EF4444 !important;
            background: rgba(255, 255, 255, 0.95);
        }

        .room-type-pill {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(11, 31, 58, 0.85);
            backdrop-filter: blur(8px);
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 13px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .room-badge-bottom {
            position: absolute;
            bottom: 12px;
            left: 14px;
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(6px);
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 8px;
            z-index: 5;
        }

        .room-rating-pill {
            position: absolute;
            bottom: 12px;
            right: 14px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
            color: #0F172A;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        /* Dark Theme Support for Room Cards */
        [data-theme="dark"] .room-card-modern,
        [data-bs-theme="dark"] .room-card-modern,
        body.dark-theme .room-card-modern {
            background: #1E293B !important;
            border-color: #334155 !important;
        }

        [data-theme="dark"] .fav-btn-glass,
        [data-bs-theme="dark"] .fav-btn-glass,
        body.dark-theme .fav-btn-glass {
            background: rgba(15, 23, 42, 0.85) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #94A3B8 !important;
        }

        [data-theme="dark"] .fav-btn-glass.text-danger,
        [data-bs-theme="dark"] .fav-btn-glass.text-danger,
        body.dark-theme .fav-btn-glass.text-danger {
            color: #F87171 !important;
            background: rgba(15, 23, 42, 0.95) !important;
        }

        [data-theme="dark"] .room-rating-pill,
        [data-bs-theme="dark"] .room-rating-pill,
        body.dark-theme .room-rating-pill {
            background: rgba(15, 23, 42, 0.9) !important;
            color: #F8FAFC !important;
        }

        /* Interactive Room Photo Gallery & Zoom Modal Styles */
        .gallery-thumb-item {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            border: 3.5px solid transparent;
            transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }

        .gallery-thumb-item:hover {
            transform: translateY(-2px) scale(1.02);
            opacity: 1 !important;
        }

        .gallery-thumb-item.active-thumb {
            border-color: #2563EB !important;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4) !important;
            transform: scale(1.02);
            opacity: 1 !important;
        }

        .gallery-thumb-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-thumb-item:hover img {
            transform: scale(1.08);
        }

        .thumb-hover-overlay {
            position: absolute;
            inset: 0;
            background: rgba(11, 31, 58, 0.45);
            backdrop-filter: blur(2px);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .gallery-thumb-item:hover .thumb-hover-overlay {
            opacity: 1;
        }

        .gallery-main-container {
            position: relative;
            height: 380px;
            border-radius: 18px;
            overflow: hidden;
            background: #0B1F3A;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.2);
        }

        .gallery-main-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease, filter 0.3s ease;
            cursor: zoom-in;
        }

        .gallery-main-container img.zoomed-mode {
            transform: scale(1.6);
            cursor: zoom-out;
        }

        /* Lightbox Fullscreen Modal */
        #lightboxZoomOverlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 14, 27, 0.96);
            backdrop-filter: blur(16px);
            z-index: 1200;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
            animation: fadeInLightbox 0.3s ease forwards;
        }

        @keyframes fadeInLightbox {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        #lightboxZoomOverlay img {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            object-fit: contain;
            transition: transform 0.3s ease;
            cursor: zoom-in;
        }

        #lightboxZoomOverlay img.zoomed-in {
            transform: scale(1.5);
            cursor: zoom-out;
        }

        /* Price Formatting & Currency Symbol Collision Fix */
        .price-amount {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif !important;
            letter-spacing: 0.2px;
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
        }

        .currency-symbol {
            font-family: system-ui, -apple-system, sans-serif !important;
            margin-right: 3px;
            font-weight: 700;
        }

        /* Lightbox Left & Right Navigation Arrow Buttons */
        .lightbox-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: #0B1F3A;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            z-index: 1210;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .lightbox-nav-btn:hover {
            background: #FFFFFF;
            color: #2563EB;
            transform: translateY(-50%) scale(1.12);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.45);
        }

        .nav-btn-left {
            left: 30px;
        }

        .nav-btn-right {
            right: 30px;
        }

        @media (max-width: 768px) {
            .lightbox-nav-btn {
                width: 44px;
                height: 44px;
            }
            .nav-btn-left { left: 12px; }
            .nav-btn-right { right: 12px; }
        }
    </style>
    <script>
        (function() {
            const phpTheme = "<?php echo htmlspecialchars($siteTheme ?? 'light'); ?>";
            let theme = localStorage.getItem('hotel_ease_theme') || phpTheme || 'light';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                document.documentElement.style.colorScheme = 'dark';
                document.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.add('dark-theme');
                });
            } else {
                document.documentElement.removeAttribute('data-theme');
                document.documentElement.setAttribute('data-bs-theme', 'light');
                document.documentElement.style.colorScheme = 'light';
                document.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.remove('dark-theme');
                });
            }
        })();

        function closeLuxuryToast() {
            const toastEl = document.getElementById('luxuryToast');
            if (toastEl) {
                toastEl.style.transition = 'all 0.4s ease';
                toastEl.style.opacity = '0';
                toastEl.style.transform = 'translateY(-20px)';
                setTimeout(() => toastEl.remove(), 400);
            }
        }

        function showDynamicToast(message, type = 'success') {
            let existing = document.getElementById('luxuryToast');
            if (existing) existing.remove();

            const toastDiv = document.createElement('div');
            toastDiv.id = 'luxuryToast';
            toastDiv.className = 'position-fixed top-0 end-0 p-3 p-md-4';
            toastDiv.style.cssText = 'z-index: 1090; margin-top: 65px;';
            const borderLeftColor = type === 'success' ? '#10B981' : (type === 'danger' ? '#EF4444' : '#38BDF8');
            const iconClass = type === 'success' ? 'fa-circle-check text-success' : (type === 'danger' ? 'fa-circle-xmark text-danger' : 'fa-circle-info text-sky-blue');

            const cleanMsg = (typeof message === 'string') ? message.replace(/<[^>]*>?/gm, '') : message;

            toastDiv.innerHTML = `
                <div onclick="closeLuxuryToast()" class="shadow-lg rounded-4 overflow-hidden position-relative" style="background: rgba(11, 31, 58, 0.95); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.15); border-left: 5px solid ${borderLeftColor}; min-width: 300px; max-width: 420px; animation: toastBounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; cursor: pointer;" title="Click to dismiss">
                    <div class="d-flex align-items-center p-3 text-white">
                        <div class="fs-4 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fa-solid ${iconClass}"></i>
                        </div>
                        <div class="font-semibold small me-3 flex-grow-1" style="line-height: 1.4;">
                            ${cleanMsg}
                        </div>
                        <button type="button" class="btn-close-luxury ms-auto" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="toast-progress-bar"></div>
                </div>
            `;
            document.body.appendChild(toastDiv);
            setTimeout(() => { closeLuxuryToast(); }, 4000);
        }

        function toggleWishlistRoom(e, roomId, btn) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (!roomId) return;

            if (btn) btn.style.transform = 'scale(1.25)';
            setTimeout(() => { if (btn) btn.style.transform = 'scale(1)'; }, 200);

            fetch('/hotel_website/api/realtime.php?action=toggle_wishlist', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'room_id=' + encodeURIComponent(roomId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const icon = btn ? btn.querySelector('i') : null;
                    if (data.saved) {
                        if (btn) {
                            btn.classList.remove('text-secondary');
                            btn.classList.add('text-danger');
                            btn.setAttribute('title', 'Remove from favorites');
                            btn.setAttribute('data-saved', '1');
                        }
                        if (icon) {
                            icon.className = 'fa-solid fa-heart';
                        }
                    } else {
                        if (btn) {
                            btn.classList.remove('text-danger');
                            btn.classList.add('text-secondary');
                            btn.setAttribute('title', 'Save to favorites');
                            btn.setAttribute('data-saved', '0');
                        }
                        if (icon) {
                            icon.className = 'fa-regular fa-heart';
                        }
                    }

                    const statSaved = document.getElementById('stat-saved-rooms');
                    if (statSaved && data.total_saved !== undefined) {
                        statSaved.textContent = data.total_saved;
                    }

                    showDynamicToast(data.message || (data.saved ? 'Added to favorites!' : 'Removed from favorites!'));
                } else {
                    showDynamicToast(data.message || 'Please log in to save favorites.', 'info');
                }
            })
            .catch(err => {
                console.error('Wishlist error:', err);
                showDynamicToast('Failed to update favorites', 'danger');
            });
        }
    </script>
</head>
<body>

    <!-- Luxury Toast Notification Banner -->
    <?php if (isset($_SESSION['toast'])): ?>
        <?php
            $toast = $_SESSION['toast'];
            $type = $toast['type'] ?? 'success';
            $iconMap = [
                'success' => 'fa-circle-check text-success',
                'danger'  => 'fa-circle-xmark text-danger',
                'warning' => 'fa-triangle-exclamation text-warning',
                'info'    => 'fa-circle-info text-sky-blue'
            ];
            $colorMap = [
                'success' => '#10B981',
                'danger'  => '#EF4444',
                'warning' => '#F59E0B',
                'info'    => '#38BDF8'
            ];
            $toastIcon = $iconMap[$type] ?? 'fa-circle-info text-gold';
            $accentColor = $colorMap[$type] ?? '#C9A227';
        ?>
        <div id="luxuryToast" class="position-fixed top-0 end-0 p-3 p-md-4" style="z-index: 1090; margin-top: 65px;">
            <div onclick="closeLuxuryToast()" class="shadow-lg rounded-4 overflow-hidden position-relative" style="background: rgba(11, 31, 58, 0.95); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.15); border-left: 5px solid <?php echo $accentColor; ?>; min-width: 300px; max-width: 420px; animation: toastBounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; cursor: pointer;" title="Click to dismiss">
                <div class="d-flex align-items-center p-3 text-white">
                    <div class="fs-4 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fa-solid <?php echo $toastIcon; ?>"></i>
                    </div>
                    <div class="font-semibold small me-3 flex-grow-1" style="line-height: 1.4;">
                        <?php echo htmlspecialchars(strip_tags($toast['message'])); ?>
                    </div>
                    <button type="button" class="btn-close-luxury ms-auto" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="toast-progress-bar"></div>
            </div>
        </div>
        <script>
            function closeLuxuryToast() {
                const toastEl = document.getElementById('luxuryToast');
                if (toastEl) {
                    toastEl.style.transition = 'all 0.4s ease';
                    toastEl.style.opacity = '0';
                    toastEl.style.transform = 'translateY(-20px)';
                    setTimeout(() => toastEl.remove(), 400);
                }
            }
            setTimeout(() => {
                closeLuxuryToast();
            }, 4000);
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
