<?php
// user/includes/navbar.php
require_once __DIR__ . '/../../config.php';
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}
$navUser = get_logged_user() ?? [
    'name' => $_SESSION['user_name'] ?? 'Mihir Mashru',
    'email' => $_SESSION['user_email'] ?? 'mashrumihir15@gmail.com'
];
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

/* User Nav Pill & Dropdown Menu Styling */
.user-nav-pill {
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    user-select: none;
}
.user-nav-pill:hover,
.user-nav-pill:focus,
.user-nav-pill[aria-expanded="true"] {
    background-color: #F8FAFC !important;
    border-color: #2563EB !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15) !important;
}

/* Dropdown Chevron rotation animation */
#userDropdownChevron {
    transition: transform 0.2s ease;
}

/* Dropdown Menu positioning & light theme defaults */
.user-dropdown-menu,
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
.user-dropdown-menu.show,
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

.user-dropdown-header-link {
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
.user-dropdown-header-link:hover {
    background: #EFF6FF;
}

/* Light Theme Dropdown Items */
.user-dropdown-menu .dropdown-item,
.topbar-nav-dropdown .dropdown-item {
    color: #1E293B !important;
    background-color: transparent;
    transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
    cursor: pointer;
}
.user-dropdown-menu .dropdown-item:hover,
.topbar-nav-dropdown .dropdown-item:hover,
.user-dropdown-menu .dropdown-item:focus,
.topbar-nav-dropdown .dropdown-item:focus {
    background-color: #EFF6FF !important;
    color: #2563EB !important;
    transform: translateX(3px);
}
.user-dropdown-menu .dropdown-item.active,
.topbar-nav-dropdown .dropdown-item.active {
    background-color: #2563EB !important;
    color: #FFFFFF !important;
    transform: none;
}
.user-dropdown-menu .dropdown-item-logout:hover {
    background-color: #FEF2F2 !important;
    color: #EF4444 !important;
    transform: translateX(3px);
}

/* Dark Theme Dropdown & Navbar Overrides */
[data-theme="dark"] .top-sticky-navbar,
[data-bs-theme="dark"] .top-sticky-navbar,
body.dark-theme .top-sticky-navbar {
    background: #0F172A !important;
    border-color: #1E293B !important;
}

[data-theme="dark"] .user-nav-pill,
[data-bs-theme="dark"] .user-nav-pill,
body.dark-theme .user-nav-pill {
    background-color: #1E293B !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .user-nav-pill span,
[data-bs-theme="dark"] .user-nav-pill span,
body.dark-theme .user-nav-pill span {
    color: #F1F5F9 !important;
}

[data-theme="dark"] .user-dropdown-menu,
[data-theme="dark"] .topbar-nav-dropdown,
[data-bs-theme="dark"] .user-dropdown-menu,
[data-bs-theme="dark"] .topbar-nav-dropdown,
body.dark-theme .user-dropdown-menu,
body.dark-theme .topbar-nav-dropdown {
    background: #1E293B !important;
    border-color: #334155 !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5) !important;
}

[data-theme="dark"] .user-dropdown-menu .dropdown-header,
[data-theme="dark"] .topbar-nav-dropdown .dropdown-header,
[data-bs-theme="dark"] .user-dropdown-menu .dropdown-header,
[data-bs-theme="dark"] .topbar-nav-dropdown .dropdown-header,
body.dark-theme .user-dropdown-menu .dropdown-header,
body.dark-theme .topbar-nav-dropdown .dropdown-header {
    color: #94A3B8 !important;
}

[data-theme="dark"] .user-dropdown-menu .dropdown-item,
[data-theme="dark"] .topbar-nav-dropdown .dropdown-item,
[data-bs-theme="dark"] .user-dropdown-menu .dropdown-item,
[data-bs-theme="dark"] .topbar-nav-dropdown .dropdown-item,
body.dark-theme .user-dropdown-menu .dropdown-item,
body.dark-theme .topbar-nav-dropdown .dropdown-item {
    color: #F1F5F9 !important;
    background-color: transparent !important;
}

[data-theme="dark"] .user-dropdown-menu .dropdown-item:hover,
[data-theme="dark"] .topbar-nav-dropdown .dropdown-item:hover,
[data-theme="dark"] .user-dropdown-menu .dropdown-item:focus,
[data-theme="dark"] .topbar-nav-dropdown .dropdown-item:focus,
[data-bs-theme="dark"] .user-dropdown-menu .dropdown-item:hover,
[data-bs-theme="dark"] .topbar-nav-dropdown .dropdown-item:hover,
[data-bs-theme="dark"] .user-dropdown-menu .dropdown-item:focus,
[data-bs-theme="dark"] .topbar-nav-dropdown .dropdown-item:focus,
body.dark-theme .user-dropdown-menu .dropdown-item:hover,
body.dark-theme .topbar-nav-dropdown .dropdown-item:hover,
body.dark-theme .user-dropdown-menu .dropdown-item:focus,
body.dark-theme .topbar-nav-dropdown .dropdown-item:focus {
    background-color: #334155 !important;
    color: #60A5FA !important;
}

[data-theme="dark"] .user-dropdown-menu .dropdown-item.active,
[data-theme="dark"] .topbar-nav-dropdown .dropdown-item.active,
[data-bs-theme="dark"] .user-dropdown-menu .dropdown-item.active,
[data-bs-theme="dark"] .topbar-nav-dropdown .dropdown-item.active,
body.dark-theme .user-dropdown-menu .dropdown-item.active,
body.dark-theme .topbar-nav-dropdown .dropdown-item.active {
    background-color: #2563EB !important;
    color: #FFFFFF !important;
}
</style>

<!-- TOP NAVBAR -->
<div class="top-sticky-navbar d-flex align-items-center mb-4">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile Sidebar Hamburger Toggle -->
            <button id="sidebarToggle" class="btn btn-light btn-sm me-2 d-lg-none border shadow-sm" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Mobile Only Brand Logo -->
            <a href="/hotel_website/index.php" class="text-decoration-none d-flex align-items-center d-lg-none">
                <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease Logo" style="max-height: 52px; object-fit: contain;">
            </a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Topbar Quick Currency Switcher -->
            <?php $activeCurr = get_current_currency(); ?>
            <div class="dropdown position-relative topbar-dropdown-container">
                <button id="currDropdownBtn" class="btn bg-white rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5 shadow-sm border small font-semibold user-nav-pill" type="button" aria-expanded="false" title="Change Display Currency" style="border-color: #E2E8F0 !important;">
                    <i class="fa-solid fa-coins text-warning me-1"></i>
                    <span style="color: #172554; font-size: 13.5px;"><?php echo htmlspecialchars($activeCurr); ?></span>
                    <i class="bi bi-chevron-down small text-muted ms-1 nav-chevron-icon" id="currChevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-4 p-2 border-0 mt-2 topbar-nav-dropdown" id="currDropdownMenu" style="min-width: 180px;">
                    <li><h6 class="dropdown-header small font-bold text-muted uppercase tracking-wider mb-1" style="font-size: 10px;">Select Currency</h6></li>
                    <li><button type="button" class="dropdown-item rounded-3 small py-1.5 font-semibold <?php echo $activeCurr === 'INR' ? 'active bg-primary text-white' : ''; ?>" onclick="changeGlobalCurrency('INR')">Indian Rupee (₹)</button></li>
                    <li><button type="button" class="dropdown-item rounded-3 small py-1.5 font-semibold <?php echo $activeCurr === 'USD' ? 'active bg-primary text-white' : ''; ?>" onclick="changeGlobalCurrency('USD')">US Dollar ($)</button></li>
                    <li><button type="button" class="dropdown-item rounded-3 small py-1.5 font-semibold <?php echo $activeCurr === 'EUR' ? 'active bg-primary text-white' : ''; ?>" onclick="changeGlobalCurrency('EUR')">Euro (€)</button></li>
                    <li><button type="button" class="dropdown-item rounded-3 small py-1.5 font-semibold <?php echo $activeCurr === 'GBP' ? 'active bg-primary text-white' : ''; ?>" onclick="changeGlobalCurrency('GBP')">British Pound (£)</button></li>
                    <li><button type="button" class="dropdown-item rounded-3 small py-1.5 font-semibold <?php echo $activeCurr === 'AED' ? 'active bg-primary text-white' : ''; ?>" onclick="changeGlobalCurrency('AED')">UAE Dirham (AED)</button></li>
                </ul>
            </div>

            <!-- Profile Dropdown Container -->
            <div class="dropdown position-relative topbar-dropdown-container" id="userProfileDropdownContainer">
                <button id="userProfileDropdownBtn" class="btn bg-white rounded-pill px-3 py-1.5 d-flex align-items-center gap-2 shadow-sm border user-nav-pill" type="button" aria-expanded="false" aria-haspopup="true" style="border-color: #E2E8F0 !important;">
                    <?php if (!empty($navUser['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($navUser['profile_pic']); ?>" class="rounded-circle object-fit-cover shadow-sm" style="width: 32px; height: 32px;" alt="Profile">
                    <?php else: ?>
                        <div class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 32px; height: 32px; font-size: 13px; background: linear-gradient(135deg, #2563EB 0%, #172554 100%);">
                            <?php echo strtoupper(substr($navUser['name'] ?? 'G', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="small fw-bold d-none d-sm-inline ms-1" style="color: #172554; font-size: 13.5px;"><?php echo htmlspecialchars($navUser['name']); ?></span>
                    <i class="bi bi-chevron-down small text-muted ms-1 nav-chevron-icon" id="userDropdownChevron"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-4 p-2 border-0 mt-2 user-dropdown-menu topbar-nav-dropdown" id="userProfileMenu">
                    <!-- User Header Info Box (Clickable -> Account Settings) -->
                    <a href="/hotel_website/user/settings.php#account" class="user-dropdown-header-link mb-1" title="View Account Settings">
                        <?php if (!empty($navUser['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($navUser['profile_pic']); ?>" class="rounded-circle object-fit-cover shadow-sm flex-shrink-0" style="width: 38px; height: 38px;" alt="Profile">
                        <?php else: ?>
                            <div class="avatar rounded-circle fw-bold d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; font-size: 14px; background: linear-gradient(135deg, #2563EB 0%, #172554 100%);">
                                <?php echo strtoupper(substr($navUser['name'] ?? 'G', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold mb-0 text-truncate" style="color: #172554; font-size: 13px;"><?php echo htmlspecialchars($navUser['name']); ?></h6>
                            <span class="text-muted text-truncate d-block" style="font-size: 11px;"><?php echo htmlspecialchars($navUser['email'] ?? 'Customer'); ?></span>
                        </div>
                    </a>

                    <div class="dropdown-divider my-1"></div>

                    <a class="dropdown-item rounded-3 small py-2 px-3 fw-semibold d-flex align-items-center gap-2" href="/hotel_website/user/settings.php">
                        <i class="bi bi-gear-fill text-primary fs-6"></i> Account Settings
                    </a>

                    <div class="dropdown-divider my-1"></div>

                    <a class="dropdown-item rounded-3 small py-2 px-3 fw-bold text-danger d-flex align-items-center gap-2 dropdown-item-logout" href="/hotel_website/user/logout.php">
                        <i class="bi bi-box-arrow-right fs-6"></i> Logout
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

            // Close all open topbar dropdowns
            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".user-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");

            if (!isOpen) {
                menu.classList.add("show");
                btn.setAttribute("aria-expanded", "true");
                if (chevron) chevron.style.transform = "rotate(180deg)";
            }
        });
    }

    setupNavDropdown("currDropdownBtn", "currDropdownMenu", "currChevron");
    setupNavDropdown("userProfileDropdownBtn", "userProfileMenu", "userDropdownChevron");

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".topbar-dropdown-container")) {
            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".user-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".topbar-nav-dropdown").forEach(m => m.classList.remove("show"));
            document.querySelectorAll(".user-nav-pill").forEach(b => b.setAttribute("aria-expanded", "false"));
            document.querySelectorAll(".nav-chevron-icon").forEach(c => c.style.transform = "rotate(0deg)");
        }
    });
});

function changeGlobalCurrency(curr) {
    fetch('/hotel_website/api/realtime.php?action=set_currency', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'currency=' + encodeURIComponent(curr)
    })
    .then(res => res.json())
    .then(data => {
        if (typeof showDynamicToast === 'function') {
            showDynamicToast('Currency updated to ' + curr, 'success');
        }
        setTimeout(() => { location.reload(); }, 350);
    });
}

function changeGlobalLanguage(lang) {
    fetch('/hotel_website/api/realtime.php?action=set_language', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'language=' + encodeURIComponent(lang)
    })
    .then(res => res.json())
    .then(data => {
        if (typeof showDynamicToast === 'function') {
            showDynamicToast('Language updated to ' + lang.toUpperCase(), 'success');
        }
        setTimeout(() => { location.reload(); }, 350);
    });
}
</script>
