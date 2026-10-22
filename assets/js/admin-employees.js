/**
 * HotelEase Admin - Employees Management JavaScript
 */

let activeEmpData = null;
let allEmployeesDataset = [];
let activeStatFilter = 'all';

function showEmployeeModal(modalElem) {
    if (!modalElem) return;

    closeEmpDrawer();
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElem).show();
        return;
    }

    document.querySelectorAll('.modal.emp-modal-open').forEach(openModal => {
        if (openModal !== modalElem) closeEmployeeModal(openModal);
    });

    modalElem.classList.add('show', 'emp-modal-open');
    modalElem.style.display = 'block';
    modalElem.removeAttribute('aria-hidden');
    modalElem.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');

    if (!document.querySelector('.employee-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show employee-modal-backdrop';
        backdrop.addEventListener('click', () => closeEmployeeModal(modalElem));
        document.body.appendChild(backdrop);
    }
}

function closeEmployeeModal(modalElem) {
    if (!modalElem) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(modalElem);
        if (instance) instance.hide();
    }

    modalElem.classList.remove('show', 'emp-modal-open');
    modalElem.style.display = 'none';
    modalElem.setAttribute('aria-hidden', 'true');
    modalElem.removeAttribute('aria-modal');
    document.querySelectorAll('.employee-modal-backdrop').forEach(backdrop => backdrop.remove());
    if (!document.querySelector('.modal.show, .emp-modal-open')) {
        document.body.classList.remove('modal-open');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initializeDataset();
    setupEventListeners();
    applySavedViewMode();
});

// Initialize Dataset from hidden JSON attributes
function initializeDataset() {
    allEmployeesDataset = [];
    const rows = document.querySelectorAll('.emp-row-item');
    rows.forEach(row => {
        const raw = row.getAttribute('data-emp');
        if (raw) {
            try {
                const emp = JSON.parse(raw);
                allEmployeesDataset.push(emp);
            } catch (err) {
                console.error("Error parsing employee dataset row", err);
            }
        }
    });
}

// Setup Event Listeners
function setupEventListeners() {
    const searchInput = document.getElementById('searchEmpInput');
    const searchClearBtn = document.getElementById('btnSearchClear');
    const statusSelect = document.getElementById('filterStatusSelect');
    const positionSelect = document.getElementById('filterPositionSelect');
    const dateSelect = document.getElementById('filterDateSelect');
    const sortSelect = document.getElementById('sortEmpSelect');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (searchClearBtn) {
                searchClearBtn.style.display = this.value ? 'block' : 'none';
            }
            filterAndRenderEmployees();
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            this.style.display = 'none';
            filterAndRenderEmployees();
        });
    }

    if (statusSelect) statusSelect.addEventListener('change', filterAndRenderEmployees);
    if (positionSelect) positionSelect.addEventListener('change', filterAndRenderEmployees);
    if (dateSelect) dateSelect.addEventListener('change', filterAndRenderEmployees);
    if (sortSelect) sortSelect.addEventListener('change', filterAndRenderEmployees);

    // Select All Checkbox
    const selectAllCb = document.getElementById('selectAllEmployees');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.emp-select-cb');
            checkboxes.forEach(cb => cb.checked = selectAllCb.checked);
            updateBulkActionButton();
        });
    }

    // Individual Checkbox listener delegate
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('emp-select-cb')) {
            updateBulkActionButton();
        }
    });
}

// Update Bulk Action Button State
function updateBulkActionButton() {
    const checked = document.querySelectorAll('.emp-select-cb:checked');
    const bulkBtn = document.getElementById('bulkActionBtn');
    if (bulkBtn) {
        if (checked.length > 0) {
            bulkBtn.classList.remove('disabled');
            bulkBtn.innerText = `Bulk Action (${checked.length})`;
        } else {
            bulkBtn.classList.add('disabled');
            bulkBtn.innerText = 'Bulk Action';
        }
    }
}

// Stat Card Click Handler
function filterByStatCard(statType, cardElement) {
    activeStatFilter = statType;

    // Highlight selected card
    const allCards = document.querySelectorAll('.kpi-card');
    allCards.forEach(c => c.classList.remove('active-filter'));

    if (cardElement) {
        cardElement.classList.add('active-filter');
    }

    const statusSelect = document.getElementById('filterStatusSelect');
    const positionSelect = document.getElementById('filterPositionSelect');

    if (statType === 'Active' || statType === 'Inactive' || statType === 'On Leave') {
        if (statusSelect) statusSelect.value = statType;
        if (positionSelect) positionSelect.value = 'all';
    } else if (statType === 'Managers') {
        if (statusSelect) statusSelect.value = 'all';
        if (positionSelect) positionSelect.value = 'Manager';
    } else if (statType === 'Staff') {
        if (statusSelect) statusSelect.value = 'all';
        if (positionSelect) positionSelect.value = 'Staff';
    } else {
        if (statusSelect) statusSelect.value = 'all';
        if (positionSelect) positionSelect.value = 'all';
    }

    filterAndRenderEmployees();

    // Smooth scroll to table section
    const targetSection = document.getElementById('empTableViewContainer') || document.getElementById('empGridViewContainer');
    if (targetSection) {
        targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// View Mode Switcher (Table View Default)
function setViewMode(mode) {
    const tableView = document.getElementById('empTableViewContainer');
    const gridView = document.getElementById('empGridViewContainer');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');

    if (tableView) tableView.style.display = 'block';
    if (gridView) gridView.style.display = 'none';
    if (btnTable) btnTable.classList.add('active');
    if (btnGrid) btnGrid.classList.remove('active');
    localStorage.setItem('hotelease_employees_view', 'table');
}

function applySavedViewMode() {
    setViewMode('table');
}

// Live Search & Filter Logic
function filterAndRenderEmployees() {
    const query = (document.getElementById('searchEmpInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('filterStatusSelect')?.value || 'all';
    const positionVal = document.getElementById('filterPositionSelect')?.value || 'all';
    const sortVal = document.getElementById('sortEmpSelect')?.value || 'newest';

    const tableRows = document.querySelectorAll('.emp-row-item');
    const gridCards = document.querySelectorAll('.emp-grid-item');

    let visibleCount = 0;

    tableRows.forEach(row => {
        const raw = row.getAttribute('data-emp');
        if (!raw) return;
        const e = JSON.parse(raw);
        const matches = matchEmployee(e, query, statusVal, positionVal);

        if (matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    gridCards.forEach(card => {
        const raw = card.getAttribute('data-emp');
        if (!raw) return;
        const e = JSON.parse(raw);
        const matches = matchEmployee(e, query, statusVal, positionVal);

        if (matches) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    // Update Counter
    const counterText = document.getElementById('empCounterText');
    if (counterText) {
        counterText.textContent = `Showing 1–${visibleCount} of ${allEmployeesDataset.length} staff members`;
    }

    // Empty state visibility
    const emptyState = document.getElementById('empEmptyState');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function matchEmployee(e, query, statusVal, positionVal) {
    // 1. Text Search
    if (query) {
        const idRef = `#emp-00${e.employee_id}`.toLowerCase();
        const idStr = String(e.employee_id);
        const nameStr = (e.name || '').toLowerCase();
        const emailStr = (e.email || '').toLowerCase();
        const mobileStr = (e.mobile || '').toLowerCase();
        const posStr = (e.position || '').toLowerCase();

        const matchText = idRef.includes(query) || idStr.includes(query) ||
            nameStr.includes(query) || emailStr.includes(query) ||
            mobileStr.includes(query) || posStr.includes(query);

        if (!matchText) return false;
    }

    // 2. Status Filter
    if (statusVal !== 'all' && e.status !== statusVal) {
        return false;
    }

    // 3. Position Filter
    if (positionVal !== 'all') {
        const pos = (e.position || '').toLowerCase();
        if (positionVal === 'Manager' && !pos.includes('manager')) {
            return false;
        } else if (positionVal === 'Staff' && pos.includes('manager')) {
            return false;
        } else if (positionVal !== 'Manager' && positionVal !== 'Staff') {
            if (!pos.includes(positionVal.toLowerCase())) {
                return false;
            }
        }
    }

    return true;
}

// Reset All Filters
function resetEmpFilters() {
    const searchInput = document.getElementById('searchEmpInput');
    const statusSelect = document.getElementById('filterStatusSelect');
    const positionSelect = document.getElementById('filterPositionSelect');
    const sortSelect = document.getElementById('sortEmpSelect');

    if (searchInput) searchInput.value = '';
    if (statusSelect) statusSelect.value = 'all';
    if (positionSelect) positionSelect.value = 'all';
    if (sortSelect) sortSelect.value = 'newest';

    const allCards = document.querySelectorAll('.kpi-card');
    allCards.forEach(c => c.classList.remove('active-filter'));

    filterAndRenderEmployees();
}

// Right-Side Drawer Management
function openEmpDrawer(elemOrData) {
    let e = null;
    if (typeof elemOrData === 'object' && elemOrData.employee_id) {
        e = elemOrData;
    } else if (elemOrData) {
        const raw = elemOrData.getAttribute('data-emp') || elemOrData.closest('[data-emp]')?.getAttribute('data-emp');
        if (raw) {
            try { e = JSON.parse(raw); } catch (err) { console.error(err); }
        }
    }

    if (!e) return;
    activeEmpData = e;

    // Header & Ref
    document.getElementById('drawerEmpName').textContent = e.name || 'Staff Member';
    document.getElementById('drawerEmpId').textContent = `#EMP-00${e.employee_id}`;

    // Profile Section
    document.getElementById('drawerProfileName').textContent = e.name || 'Staff Member';
    document.getElementById('drawerProfilePosition').textContent = e.position || 'Staff';
    document.getElementById('drawerProfileSalary').textContent = `₹${parseFloat(e.salary || 0).toLocaleString('en-IN')}/mo`;

    const drawerPic = document.getElementById('drawerAvatarPic');
    const drawerInitial = document.getElementById('drawerAvatarInitial');

    if (e.profile_pic) {
        if (drawerPic) {
            drawerPic.src = e.profile_pic;
            drawerPic.style.display = 'inline-flex';
        }
        if (drawerInitial) drawerInitial.style.display = 'none';
    } else {
        if (drawerPic) drawerPic.style.display = 'none';
        if (drawerInitial) {
            drawerInitial.textContent = (e.name || 'E').charAt(0).toUpperCase();
            drawerInitial.style.display = 'inline-flex';
        }
    }

    // Contact Section
    document.getElementById('drawerEmpEmail').textContent = e.email || 'N/A';
    document.getElementById('drawerEmpMobile').textContent = e.mobile || 'N/A';

    // Employment Section
    document.getElementById('drawerEmpRefId').textContent = `#EMP-00${e.employee_id}`;
    document.getElementById('drawerEmpPosition').textContent = e.position || 'Staff';
    document.getElementById('drawerEmpJoiningDate').textContent = formatDate(e.joining_date);

    // Status Badge
    document.getElementById('drawerStatusBadge').innerHTML = getEmpStatusBadgeHTML(e.status);

    // Action buttons inside drawer
    renderDrawerActionButtons(e);

    // Show Drawer & Reset Body Scroll position to top
    const drawer = document.getElementById('empDrawer');
    const backdrop = document.getElementById('empDrawerBackdrop');
    if (drawer) {
        drawer.classList.add('active');
        const drawerBody = drawer.querySelector('.drawer-body');
        if (drawerBody) drawerBody.scrollTop = 0;
    }
    if (backdrop) backdrop.classList.add('active');
}

function closeEmpDrawer() {
    const drawer = document.getElementById('empDrawer');
    const backdrop = document.getElementById('empDrawerBackdrop');
    if (drawer) drawer.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
}

// Render Action Buttons inside Drawer
function renderDrawerActionButtons(e) {
    const container = document.getElementById('drawerActionButtons');
    if (!container) return;

    const empJson = JSON.stringify(e).replace(/'/g, "&#39;");

    let html = `
        <div class="row g-2 w-100 m-0">
            <div class="col-6 p-1">
                <button type="button" class="btn btn-primary w-100 rounded-3 font-semibold py-2 text-nowrap extra-small shadow-sm d-flex align-items-center justify-content-center gap-1.5" onclick="openEditEmpModalFromData(this)" data-emp='${empJson}'>
                    <i class="fa-solid fa-pen-to-square"></i> <span>Edit Profile</span>
                </button>
            </div>
            <div class="col-6 p-1">
                <button type="button" class="btn btn-outline-info w-100 rounded-3 font-semibold py-2 text-nowrap extra-small shadow-2xs d-flex align-items-center justify-content-center gap-1.5" onclick="openChangeStatusModal(${e.employee_id}, '${escapeJS(e.name)}', '${e.status || 'Active'}')">
                    <i class="fa-solid fa-user-tag"></i> <span>Status</span>
                </button>
            </div>
            <div class="col-6 p-1">
                <button type="button" class="btn btn-outline-secondary w-100 rounded-3 font-semibold py-2 text-nowrap extra-small shadow-2xs d-flex align-items-center justify-content-center gap-1.5" onclick="openChangePositionModal(${e.employee_id}, '${escapeJS(e.name)}', '${escapeJS(e.position)}')">
                    <i class="fa-solid fa-briefcase"></i> <span>Change Role</span>
                </button>
            </div>
            <div class="col-6 p-1">
                <button type="button" class="btn btn-outline-danger w-100 rounded-3 font-semibold py-2 text-nowrap extra-small shadow-2xs d-flex align-items-center justify-content-center gap-1.5" onclick="openDeleteEmpModal(${e.employee_id}, '${escapeJS(e.name)}')">
                    <i class="fa-solid fa-trash-can"></i> <span>Delete</span>
                </button>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

// Profile Photo Lightbox Preview
function openProfilePhotoPreview(initialText, name) {
    document.getElementById('lightboxInitial').textContent = initialText || 'E';
    document.getElementById('lightboxEmpName').textContent = name || 'Employee Profile';

    const modalElem = document.getElementById('profilePhotoModal');
    showEmployeeModal(modalElem);
}

// Modal Trigger Functions
function openAddEmpModal() {
    const addForm = document.querySelector('#addEmpModal form');
    if (addForm) addForm.reset();
    const modalElem = document.getElementById('addEmpModal');
    showEmployeeModal(modalElem);
}

function openEditEmpModalFromData(elem) {
    if (!elem) return;
    let raw = elem.getAttribute ? (elem.getAttribute('data-emp') || elem.closest('[data-emp]')?.getAttribute('data-emp')) : null;
    if (!raw && activeEmpData) {
        openEditEmpModal(activeEmpData);
        return;
    }
    if (!raw) return;
    try {
        const e = JSON.parse(raw);
        openEditEmpModal(e);
    } catch (err) {
        console.error("Error parsing emp data", err);
        if (activeEmpData) openEditEmpModal(activeEmpData);
    }
}

function openEditEmpModal(e) {
    closeEmpDrawer();
    document.getElementById('edit_employee_id').value = e.employee_id;
    document.getElementById('edit_name').value = e.name || '';
    document.getElementById('edit_position').value = e.position || '';
    document.getElementById('edit_mobile').value = e.mobile || '';
    document.getElementById('edit_email').value = e.email || '';
    document.getElementById('edit_salary').value = e.salary || 0;
    document.getElementById('edit_joining_date').value = e.joining_date || '';
    document.getElementById('edit_status').value = e.status || 'Active';

    const modalElem = document.getElementById('editEmpModal');
    showEmployeeModal(modalElem);
}

function openChangeStatusModal(id, name, currentStatus) {
    closeEmpDrawer();
    document.getElementById('status_employee_id').value = id;
    document.getElementById('status_emp_name').textContent = name || 'Employee';
    document.getElementById('status_select').value = currentStatus || 'Active';

    const modalElem = document.getElementById('changeStatusModal');
    showEmployeeModal(modalElem);
}

function openChangePositionModal(id, name, currentPosition) {
    closeEmpDrawer();
    document.getElementById('pos_employee_id').value = id;
    document.getElementById('pos_emp_name').textContent = name || 'Employee';
    document.getElementById('pos_input').value = currentPosition || '';

    const modalElem = document.getElementById('changePositionModal');
    showEmployeeModal(modalElem);
}

function openDeleteEmpModal(id, name) {
    closeEmpDrawer();
    if (typeof id === 'object' && id !== null) {
        const elem = id;
        if (elem.getAttribute) {
            const raw = elem.getAttribute('data-emp') || elem.closest('[data-emp]')?.getAttribute('data-emp');
            if (raw) {
                try {
                    const e = JSON.parse(raw);
                    id = e.employee_id;
                    name = e.name;
                } catch(err) {}
            } else {
                id = elem.getAttribute('data-id') || elem.closest('[data-id]')?.getAttribute('data-id');
                name = elem.getAttribute('data-name') || elem.closest('[data-name]')?.getAttribute('data-name');
            }
        } else if (elem.employee_id) {
            name = elem.name;
            id = elem.employee_id;
        }
    }

    if (!id) return;

    const idInput = document.getElementById('delete_employee_id');
    const nameElem = document.getElementById('delete_emp_name');
    const refElem = document.getElementById('delete_emp_id_ref');

    if (idInput) idInput.value = id;
    if (nameElem) nameElem.textContent = name || 'Employee';
    if (refElem) refElem.textContent = `#EMP-00${id}`;

    const modalElem = document.getElementById('deleteEmpModal');
    showEmployeeModal(modalElem);
}

// CSV Export Handler
function exportEmployeesCSV() {
    if (allEmployeesDataset.length === 0) {
        alert('No employee records available to export.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Employee ID,Full Name,Position / Role,Mobile,Email,Monthly Salary,Joining Date,Status\n";

    allEmployeesDataset.forEach(e => {
        const row = [
            `"#EMP-00${e.employee_id}"`,
            `"${escapeCSV(e.name)}"`,
            `"${escapeCSV(e.position)}"`,
            `"${escapeCSV(e.mobile)}"`,
            `"${escapeCSV(e.email)}"`,
            `"${e.salary}"`,
            `"${e.joining_date}"`,
            `"${e.status}"`
        ].join(",");
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `hotelease_employees_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Refresh Data Handler with Spinner
function refreshEmployeesData() {
    const refreshBtn = document.getElementById('btnRefreshEmployees');
    if (refreshBtn) {
        refreshBtn.classList.add('disabled');
        refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Refreshing...';
    }
    setTimeout(() => {
        window.location.reload();
    }, 400);
}

// Utilities
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getEmpStatusBadgeHTML(status) {
    switch (status) {
        case 'Active':
            return `<span class="badge-emp-status badge-status-active"><i class="fa-solid fa-circle-check"></i> Active</span>`;
        case 'Inactive':
            return `<span class="badge-emp-status badge-status-inactive"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>`;
        case 'On Leave':
            return `<span class="badge-emp-status badge-status-leave"><i class="fa-solid fa-clock"></i> On Leave</span>`;
        default:
            return `<span class="badge-emp-status badge-status-active">${status}</span>`;
    }
}

function escapeJS(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function escapeCSV(str) {
    if (!str) return '';
    return str.replace(/"/g, '""');
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('button, a');
    if (!button) return;

    const text = (button.textContent || '').trim().toLowerCase();
    if (!button.hasAttribute('data-bs-dismiss') &&
        !button.classList.contains('btn-close') &&
        text !== 'cancel' && text !== 'close') {
        return;
    }

    const modal = button.closest('.modal');
    if (modal) closeEmployeeModal(modal);
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    const modal = document.querySelector('.modal.emp-modal-open, .modal.show');
    if (modal) closeEmployeeModal(modal);
});
