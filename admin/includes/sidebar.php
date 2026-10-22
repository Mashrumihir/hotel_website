<?php
require_once __DIR__ . '/../../config.php';
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
function is_active_admin_nav($page, $current) {
    return ($page === $current) ? 'active' : '';
}
$loggedAdmin = get_logged_admin() ?? [
    'username' => $_SESSION['admin_username'] ?? 'Admin',
    'name' => $_SESSION['admin_name'] ?? 'Administrator'
];
?>

<style>
/* =========================================================
   HOTEL EASE - MIDNIGHT NAVY & GOLD ADMIN SIDEBAR
   ========================================================= */
:root {
    --admin-navy: #071A2F;
    --admin-deep: #0B2744;
    --admin-blue: #1769E0;
    --admin-gold: #D4AF5A;
    --sidebar-width: 260px;
}

/* SIDEBAR FIXED CONTAINER */
.admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    min-width: var(--sidebar-width);
    max-width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg, #071A2F 0%, #0B2744 100%);
    border-right: 1px solid rgba(220, 230, 240, 0.1);
    box-shadow: 4px 0 24px rgba(7, 26, 47, 0.35);
    backdrop-filter: blur(20px);
    display: flex;
    flex-direction: column;
    z-index: 1040;
    overflow-y: auto;
    overflow-x: hidden;
    box-sizing: border-box;
}

/* Scrollbar */
.admin-sidebar::-webkit-scrollbar {
    width: 5px;
}
.admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}
.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 90, 0.3);
    border-radius: 10px;
}

/* BRAND LOGO HEADER */
.admin-sidebar-brand {
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.25);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    box-sizing: border-box;
}
.brand-logo-admin {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    width: 100%;
}
.brand-logo-admin img {
    max-height: 68px;
    width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.6));
}

/* NAVIGATION LINKS */
.admin-sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 14px 12px;
    gap: 4px;
}
.admin-sidebar-link {
    position: relative;
    min-height: 44px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 14px;
    border-radius: 12px;
    text-decoration: none;
    color: rgba(255, 255, 255, 0.72);
    font-size: 13.5px;
    font-weight: 600;
    border: 1px solid transparent;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
}
.admin-sidebar-link i {
    width: 22px;
    min-width: 22px;
    text-align: center;
    font-size: 17px;
    color: #56CCF2 !important;
    transition: transform 0.2s ease, color 0.2s ease;
}
.admin-sidebar-link span {
    white-space: nowrap;
}

/* HOVER STATE */
.admin-sidebar-link:hover {
    background: linear-gradient(135deg, rgba(11, 39, 68, 0.9) 0%, rgba(23, 105, 224, 0.6) 100%);
    color: #FFFFFF;
    border-color: rgba(23, 105, 224, 0.3);
    transform: translateX(4px);
}
.admin-sidebar-link:hover i {
    color: #FFFFFF !important;
}

/* ACTIVE STATE */
.admin-sidebar-link.active {
    background: linear-gradient(135deg, #1769E0 0%, #2F80ED 100%) !important;
    color: #FFFFFF !important;
    border-radius: 12px !important;
    box-shadow: 0 6px 20px rgba(23, 105, 224, 0.4);
}
.admin-sidebar-link.active i {
    color: #FFFFFF !important;
}
.admin-sidebar-link.active:hover {
    transform: translateX(0);
}

/* BOTTOM LOGOUT SECTION */
.admin-sidebar-bottom {
    margin-top: auto;
    padding: 12px 14px 16px;
    background: rgba(0, 0, 0, 0.25);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.admin-logout-link {
    color: #DC3545 !important;
    border: 1px solid rgba(220, 53, 69, 0.3) !important;
    background: rgba(220, 53, 69, 0.08) !important;
}
.admin-logout-link i {
    color: #DC3545 !important;
}
.admin-logout-link:hover {
    background: #DC3545 !important;
    color: #FFFFFF !important;
    border-color: #DC3545 !important;
}
.admin-logout-link:hover i {
    color: #FFFFFF !important;
}

/* MAIN CONTENT OFFSET */
.main-content,
.dashboard-main,
.page-content {
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    min-height: 100vh;
    box-sizing: border-box;
}

.top-sticky-navbar,
.hotel-navbar,
.top-navbar,
.dashboard-navbar {
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    position: sticky;
    top: 0;
    z-index: 1030;
}

.main-content .top-sticky-navbar,
.main-content .hotel-navbar,
.main-content .top-navbar,
.main-content .dashboard-navbar {
    margin-left: 0 !important;
    width: 100% !important;
}

/* MOBILE OVERLAY */
.admin-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(7, 26, 47, 0.75);
    z-index: 1035;
    opacity: 0;
    transition: opacity 0.25s ease;
}

/* TABLET RESPONSIVE */
@media (max-width: 991px) {
    :root {
        --sidebar-width: 80px;
    }
    .admin-sidebar {
        width: 80px;
        min-width: 80px;
        max-width: 80px;
    }
    .admin-sidebar-link span {
        display: none;
    }
    .admin-sidebar-brand {
        padding: 10px 6px;
    }
    .admin-sidebar-nav {
        padding: 8px 6px;
    }
    .admin-sidebar-link {
        justify-content: center;
        padding: 0;
        gap: 0;
    }
    .admin-sidebar-link i {
        width: auto;
    }
    .admin-sidebar-bottom {
        padding: 10px 6px;
    }
}

/* MOBILE RESPONSIVE DRAWER */
@media (max-width: 767px) {
    :root {
        --sidebar-width: 0px;
    }
    .admin-sidebar {
        width: 275px;
        min-width: 275px;
        max-width: 275px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1050;
    }
    .admin-sidebar.mobile-open {
        transform: translateX(0);
    }
    .admin-sidebar-link span {
        display: block;
    }
    .admin-sidebar-brand {
        padding: 16px;
    }
    .admin-sidebar-nav {
        padding: 12px;
    }
    .admin-sidebar-link {
        justify-content: flex-start;
        padding: 0 14px;
        gap: 12px;
    }
    .admin-sidebar-bottom {
        padding: 12px 14px 16px;
    }
    .main-content,
    .dashboard-main,
    .page-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .top-sticky-navbar,
    .hotel-navbar,
    .top-navbar,
    .dashboard-navbar {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .admin-sidebar-overlay {
        display: block;
        pointer-events: none;
    }
    .admin-sidebar-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }
}
</style>

<!-- ================= HOTEL EASE FIXED ADMIN SIDEBAR ================= -->
<aside class="admin-sidebar" id="adminSidebar">

    <!-- Logo & Brand Header -->
    <div class="admin-sidebar-brand">
        <a href="/hotel_website/admin/dashboard.php" class="brand-logo-admin">
            <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease Logo">
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="admin-sidebar-nav">
        <a href="/hotel_website/admin/dashboard.php" class="admin-sidebar-link <?php echo is_active_admin_nav('dashboard.php', $current_page); ?>">
            <i class="fa-solid fa-gauge"></i>
            <span>Dashboard</span>
        </a>

        <a href="/hotel_website/admin/customers.php" class="admin-sidebar-link <?php echo is_active_admin_nav('customers.php', $current_page); ?>">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>

        <a href="/hotel_website/admin/rooms.php" class="admin-sidebar-link <?php echo is_active_admin_nav('rooms.php', $current_page); ?>">
            <i class="fa-solid fa-bed"></i>
            <span>Rooms</span>
        </a>


        <a href="/hotel_website/admin/bookings.php" class="admin-sidebar-link <?php echo is_active_admin_nav('bookings.php', $current_page); ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Bookings</span>
        </a>

        <a href="/hotel_website/admin/payments.php" class="admin-sidebar-link <?php echo is_active_admin_nav('payments.php', $current_page); ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Payments</span>
        </a>

        <a href="/hotel_website/admin/employees.php" class="admin-sidebar-link <?php echo is_active_admin_nav('employees.php', $current_page); ?>">
            <i class="fa-solid fa-id-badge"></i>
            <span>Employees</span>
        </a>

        <a href="/hotel_website/admin/inquiries.php" class="admin-sidebar-link <?php echo is_active_admin_nav('inquiries.php', $current_page); ?>">
            <i class="fa-solid fa-headset"></i>
            <span>Inquiries</span>
        </a>

        <a href="/hotel_website/admin/faqs.php" class="admin-sidebar-link <?php echo is_active_admin_nav('faqs.php', $current_page); ?>">
            <i class="fa-solid fa-circle-question"></i>
            <span>FAQs</span>
        </a>

        <a href="/hotel_website/admin/settings.php" class="admin-sidebar-link <?php echo is_active_admin_nav('settings.php', $current_page); ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <!-- Bottom Logout Section -->
    <div class="admin-sidebar-bottom">
        <a href="/hotel_website/admin/logout.php" class="admin-sidebar-link admin-logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>

<!-- Mobile Dark Overlay -->
<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById("adminSidebarOverlay");
    const menuButton = document.getElementById("adminSidebarToggle");

    if (!sidebar || !overlay) return;

    function openSidebar() {
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        document.body.style.overflow = "";
    }

    if (menuButton) {
        menuButton.addEventListener("click", openSidebar);
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });
});
</script>
