<?php
// admin/includes/navbar.php
require_once __DIR__ . '/../../config.php';
if (!isset($page_title)) {
    $page_title = 'Admin Control Panel';
}
$loggedAdminNav = get_logged_admin() ?? [
    'username' => $_SESSION['admin_username'] ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? 'admin@hotelease.com'
];
$adminDisplayName = $loggedAdminNav['name'] ?? $loggedAdminNav['username'] ?? 'Admin';
?>

<style>
/* Top Sticky White Navbar */
.top-sticky-navbar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    box-shadow: 0 4px 20px rgba(18, 59, 109, 0.04);
    position: sticky;
    top: 0;
    z-index: 1020;
    height: 68px;
    min-height: 68px;
    max-height: 68px;
    box-sizing: border-box;
}

/* Admin Nav Pill & Dropdown Menu Styling */
.admin-nav-pill {
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    user-select: none;
}
.admin-nav-pill:hover,
.admin-nav-pill:focus,
.admin-nav-pill[aria-expanded="true"] {
    background-color: #F8FAFC !important;
    border-color: #2563EB !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2) !important;
}

/* Dropdown Chevron rotation animation */
#adminDropdownChevron {
    transition: transform 0.2s ease;
}

/* Dropdown Menu positioning & light theme defaults */
.admin-dropdown-menu,
.topbar-nav-dropdown {
    position: absolute !important;
    right: 0 !important;
    left: auto !important;
    top: 100% !important;
    margin-top: 8px !important;
    display: none;
    border: 1px solid #E2E8F0 !important;
    z-index: 1060 !important;
    background: #FFFFFF !important;
    box-shadow: 0 10px 25px rgba(18, 59, 109, 0.12) !important;
}
.admin-dropdown-menu.show,
.topbar-nav-dropdown.show {
    display: block !important;
    animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.admin-dropdown-header-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    background: #F8FAFC;
    transition: background 0.15s ease;
}
.admin-dropdown-header-link:hover {
    background: #EFF6FF;
}

/* Light Theme Dropdown Items */
.admin-dropdown-menu .dropdown-item,
.topbar-nav-dropdown .dropdown-item {
    color: #1E293B !important;
    background-color: transparent;
    transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
    cursor: pointer;
}
.admin-dropdown-menu .dropdown-item:hover,
.topbar-nav-dropdown .dropdown-item:hover,
.admin-dropdown-menu .dropdown-item:focus,
.topbar-nav-dropdown .dropdown-item:focus {
    background-color: #EFF6FF !important;
    color: #2563EB !important;
    transform: translateX(3px);
}
.admin-dropdown-menu .dropdown-item-logout:hover {
    background-color: #FEF2F2 !important;
    color: #EF4444 !important;
    transform: translateX(3px);
}

/* Dark Theme Overrides */
[data-theme="dark"] .top-sticky-navbar,
[data-bs-theme="dark"] .top-sticky-navbar,
body.dark-theme .top-sticky-navbar {
    background: #0F172A !important;
    border-color: #1E293B !important;
}
</style>

<!-- TOP NAVBAR -->
<div class="top-sticky-navbar d-flex align-items-center mb-4">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile Sidebar Hamburger Toggle -->
            <button id="adminSidebarToggle" class="btn btn-light btn-sm me-2 d-lg-none border shadow-sm" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Mobile Only Brand Logo -->
            <a href="/hotel_website/admin/dashboard.php" class="text-decoration-none d-flex align-items-center d-lg-none">
                <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease Logo" style="max-height: 52px; object-fit: contain;">
            </a>
            
            <h5 class="fw-bold mb-0 text-navy d-none d-lg-block font-serif-title">
                <?php echo htmlspecialchars($page_title); ?>
            </h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Profile Dropdown Container -->
            <div class="dropdown position-relative topbar-dropdown-container" id="adminProfileDropdownContainer">
                <button id="adminProfileDropdownBtn" class="btn bg-white rounded-pill px-3 py-1.5 d-flex align-items-center gap-2 shadow-sm border admin-nav-pill" type="button" aria-expanded="false" style="border-color: #E2E8F0 !important;">
                    <div class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 32px; height: 32px; font-size: 13px; background: linear-gradient(135deg, #2563EB 0%, #172554 100%); border: 1px solid #2563EB;">
                        A
                    </div>
                    <span class="small fw-bold d-none d-sm-inline ms-1" style="color: #172554; font-size: 13.5px;"><?php echo htmlspecialchars($adminDisplayName); ?></span>
                    <i class="bi bi-chevron-down small text-muted ms-1 nav-chevron-icon" id="adminDropdownChevron"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-4 p-2 border-0 mt-2 admin-dropdown-menu topbar-nav-dropdown" id="adminProfileMenu">
                    <a href="/hotel_website/admin/settings.php" class="admin-dropdown-header-link mb-1" title="View System Settings">
                        <div class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; font-size: 14px; background: linear-gradient(135deg, #2563EB 0%, #172554 100%);">
                            A
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold mb-0 text-truncate" style="color: #172554; font-size: 13px;"><?php echo htmlspecialchars($adminDisplayName); ?></h6>
                            <span class="text-muted text-truncate d-block" style="font-size: 11px;">Administrator</span>
                        </div>
                    </a>

                    <div class="dropdown-divider my-1"></div>

                    <a class="dropdown-item rounded-3 small py-2 px-3 fw-semibold d-flex align-items-center gap-2" href="/hotel_website/admin/settings.php">
                        <i class="fa-solid fa-gear text-primary fs-6"></i> System Settings
                    </a>

                    <div class="dropdown-divider my-1"></div>

                    <a class="dropdown-item rounded-3 small py-2 px-3 fw-bold text-danger d-flex align-items-center gap-2 dropdown-item-logout" href="/hotel_website/admin/logout.php">
                        <i class="fa-solid fa-right-from-bracket fs-6"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function setupNavDropdown(btnId, menuId, chevronId) {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        const chevron = document.getElementById(chevronId);
        if (!btn || !menu) return;

        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = menu.classList.contains("show");

            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".admin-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");

            if (!isOpen) {
                menu.classList.add("show");
                btn.setAttribute("aria-expanded", "true");
                if (chevron) chevron.style.transform = "rotate(180deg)";
            }
        });
    }

    setupNavDropdown("adminProfileDropdownBtn", "adminProfileMenu", "adminDropdownChevron");

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".topbar-dropdown-container")) {
            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".admin-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".admin-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");
        }
    });
});
</script>
