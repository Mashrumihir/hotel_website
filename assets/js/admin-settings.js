/**
 * HotelEase Admin - Settings Management JavaScript
 */

let hasUnsavedChanges = false;

document.addEventListener('DOMContentLoaded', function () {
    setupTabNavigation();
    setupSettingsSearch();
    setupImagePreviews();
    setupUnsavedChangesListener();
});

// Setup Client-side Tab Switcher
function setupTabNavigation() {
    const navLinks = document.querySelectorAll('.settings-nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetPaneId = this.getAttribute('data-tab');
            if (targetPaneId) {
                switchTab(targetPaneId);
            }
        });
    });

    // Restore tab from Hash if present
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(`pane-${hash}`)) {
        switchTab(hash);
    }
}

function switchTab(tabId) {
    const panes = document.querySelectorAll('.settings-pane');
    const navLinks = document.querySelectorAll('.settings-nav-link');

    panes.forEach(pane => pane.classList.remove('active'));
    navLinks.forEach(link => link.classList.remove('active'));

    const targetPane = document.getElementById(`pane-${tabId}`);
    const targetLink = document.querySelector(`.settings-nav-link[data-tab="${tabId}"]`);

    if (targetPane) targetPane.classList.add('active');
    if (targetLink) targetLink.classList.add('active');

    window.location.hash = tabId;
}

// Live Search Settings Box
function setupSettingsSearch() {
    const searchInput = document.getElementById('searchSettingsInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const navLinks = document.querySelectorAll('.settings-nav-link');

        navLinks.forEach(link => {
            const text = link.textContent.toLowerCase();
            const tabId = link.getAttribute('data-tab');
            if (query === '' || text.includes(query) || tabId.includes(query)) {
                link.style.display = 'flex';
            } else {
                link.style.display = 'none';
            }
        });
    });
}

// Instant Image Preview Handler
function setupImagePreviews() {
    // Logo preview
    const logoInput = document.getElementById('hotel_logo_input');
    const logoPreview = document.getElementById('hotel_logo_preview');

    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Admin Avatar preview
    const avatarInput = document.getElementById('admin_avatar_input');
    const avatarPreview = document.getElementById('admin_avatar_preview');
    const avatarInitial = document.getElementById('admin_avatar_initial');

    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file && avatarPreview) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                    if (avatarInitial) avatarInitial.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Password Visibility Toggle
function togglePasswordVisibility(fieldId, btnElement) {
    const input = document.getElementById(fieldId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        if (btnElement) btnElement.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        if (btnElement) btnElement.innerHTML = '<i class="fa-solid fa-eye"></i>';
    }
}

// Unsaved Changes Listener
function setupUnsavedChangesListener() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('change', function () {
            hasUnsavedChanges = true;
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}

function resetUnsavedChangesFlag() {
    hasUnsavedChanges = false;
}

// Action Trigger Functions
function openTestEmailModal() {
    const modalElem = document.getElementById('testEmailModal');
    if (modalElem) {
        const modal = new bootstrap.Modal(modalElem);
        modal.show();
    }
}

function triggerDatabaseBackup() {
    const btn = document.getElementById('btnTriggerBackup');
    if (btn) {
        btn.classList.add('disabled');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1.5"></i> Generating Backup...';
    }

    // Trigger backup form POST
    const form = document.getElementById('backupDbForm');
    if (form) {
        setTimeout(() => form.submit(), 400);
    }
}

function clearAppCache() {
    const btn = document.getElementById('btnClearCache');
    if (btn) {
        btn.classList.add('disabled');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1.5"></i> Clearing Cache...';
    }

    const form = document.getElementById('clearCacheForm');
    if (form) {
        setTimeout(() => form.submit(), 400);
    }
}

function confirmResetSettings() {
    if (confirm('Discard all unsaved configuration changes and reload defaults?')) {
        hasUnsavedChanges = false;
        window.location.reload();
    }
}
