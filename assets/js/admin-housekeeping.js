/* =========================================================
   HOTELEASE - HOUSEKEEPING MANAGEMENT JAVASCRIPT MODULE
   Real-Time AJAX Status Toggle, Delete, & Add Animations
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initHousekeepingFilters();
    initAddTaskFormAjax();
});

// Custom Luxury Toast Feedback System
function showHousekeepingToast(message, type = 'success') {
    let toastContainer = document.getElementById('hkToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'hkToastContainer';
        toastContainer.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#059669' : (type === 'danger' ? '#DC2626' : '#2563EB');
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info');
    toast.style.cssText = `background: ${bg}; color: #ffffff; padding: 12px 20px; border-radius: 12px; font-weight: 600; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.4); display: flex; align-items: center; gap: 10px; pointer-events: auto; opacity: 0; transform: translateY(-15px); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);`;
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Dynamic Interactive Tab & Search Filters
function initHousekeepingFilters() {
    const filterPills = document.querySelectorAll('.hk-filter-pill');
    const searchInput = document.getElementById('hkSearchInput');

    function filterCards() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const activePill = document.querySelector('.hk-filter-pill.active');
        const currentFilter = (activePill ? activePill.getAttribute('data-filter') : 'all').toLowerCase();

        const cards = document.querySelectorAll('.hk-card-wrapper');
        let visibleCount = 0;

        cards.forEach(card => {
            const status = (card.getAttribute('data-status') || '').toLowerCase();
            const searchData = (card.getAttribute('data-search') || '').toLowerCase();

            const matchesStatus = currentFilter === 'all' || status === currentFilter;
            const matchesQuery = !query || searchData.includes(query);

            if (matchesStatus && matchesQuery) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Toggle Empty State View
        const emptyState = document.getElementById('hkEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            filterCards();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }
}

// Single-Click Cycle Status directly from Badge
function cycleHousekeepingStatus(taskId, badgeElem) {
    if (!taskId || !badgeElem) return;
    const cardWrapper = badgeElem.closest('.hk-card-wrapper');
    if (!cardWrapper) return;

    const currentStatus = (cardWrapper.getAttribute('data-status') || 'dirty').toLowerCase();
    let nextStatus = 'Cleaning';
    let nextProgress = 50;

    if (currentStatus === 'dirty') {
        nextStatus = 'Cleaning';
        nextProgress = 50;
    } else if (currentStatus === 'cleaning') {
        nextStatus = 'Clean';
        nextProgress = 85;
    } else if (currentStatus === 'clean') {
        nextStatus = 'Inspected';
        nextProgress = 100;
    } else if (currentStatus === 'inspected') {
        nextStatus = 'Maintenance';
        nextProgress = 0;
    } else {
        nextStatus = 'Dirty';
        nextProgress = 0;
    }

    const actionBtn = cardWrapper.querySelector('.hk-btn-action');
    updateHousekeepingStatus(taskId, nextStatus, nextProgress, actionBtn || badgeElem);
}

// Single-Click AJAX Status Update with Pulse Animation & KPI updates
function updateHousekeepingStatus(taskId, nextStatus, nextProgress, buttonElem) {
    if (!taskId || !nextStatus) return;

    const cardWrapper = buttonElem ? buttonElem.closest('.hk-card-wrapper') : document.querySelector(`.hk-card-wrapper[data-task-id="${taskId}"]`);
    if (!cardWrapper) return;

    const originalButtonHtml = buttonElem ? buttonElem.innerHTML : '';
    if (buttonElem && buttonElem.tagName === 'BUTTON') {
        buttonElem.disabled = true;
        buttonElem.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...`;
    }

    const formData = new FormData();
    formData.append('action', 'update_task_status');
    formData.append('task_id', taskId);
    formData.append('status', nextStatus);
    formData.append('progress', nextProgress);
    formData.append('is_ajax', '1');

    fetch('/hotel_website/admin/housekeeping.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update Card Data Attribute
            const stLower = nextStatus.toLowerCase();
            cardWrapper.setAttribute('data-status', stLower);

            // Update Status Badge
            const badgeElem = cardWrapper.querySelector('.hk-badge[class*="hk-badge-status-"]');
            if (badgeElem) {
                badgeElem.className = `hk-badge hk-badge-clickable hk-badge-status-${stLower} hk-pulse`;
                badgeElem.textContent = nextStatus.toUpperCase();
                setTimeout(() => badgeElem.classList.remove('hk-pulse'), 400);
            }

            // Update Progress Bar
            const percentElem = cardWrapper.querySelector('.hk-progress-percent');
            if (percentElem) percentElem.textContent = `${nextProgress}%`;

            const fillElem = cardWrapper.querySelector('.hk-progress-bar-fill');
            if (fillElem) {
                fillElem.className = `hk-progress-bar-fill hk-fill-${stLower}`;
                fillElem.style.width = `${nextProgress}%`;
            }

            // Update Started Time if available
            if (['Cleaning', 'Clean', 'Inspected'].includes(nextStatus)) {
                const startTimeElem = cardWrapper.querySelector('.start-time-text');
                if (startTimeElem && !startTimeElem.textContent.includes('Started:')) {
                    const nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    startTimeElem.innerHTML = ` &middot; Started: <strong>${nowStr}</strong>`;
                }
            }

            // Update Action Button State & onclick handler
            if (buttonElem && buttonElem.tagName === 'BUTTON') {
                buttonElem.disabled = false;
                if (nextStatus === 'Dirty') {
                    buttonElem.className = 'hk-btn-action btn-start-cleaning';
                    buttonElem.innerHTML = 'Start Cleaning';
                    buttonElem.setAttribute('onclick', `updateHousekeepingStatus(${taskId}, 'Cleaning', 50, this)`);
                } else if (nextStatus === 'Cleaning') {
                    buttonElem.className = 'hk-btn-action btn-mark-clean';
                    buttonElem.innerHTML = 'Mark Clean';
                    buttonElem.setAttribute('onclick', `updateHousekeepingStatus(${taskId}, 'Clean', 85, this)`);
                } else if (nextStatus === 'Clean') {
                    buttonElem.className = 'hk-btn-action btn-inspected';
                    buttonElem.innerHTML = '<i class="fa-regular fa-circle-check"></i> Inspected';
                    buttonElem.setAttribute('onclick', `updateHousekeepingStatus(${taskId}, 'Inspected', 100, this)`);
                } else if (nextStatus === 'Inspected') {
                    buttonElem.className = 'hk-btn-action btn-room-ready';
                    buttonElem.innerHTML = '<i class="fa-solid fa-check"></i> Room Ready (Reset)';
                    buttonElem.setAttribute('onclick', `updateHousekeepingStatus(${taskId}, 'Dirty', 0, this)`);
                } else {
                    buttonElem.className = 'hk-btn-action btn-start-cleaning';
                    buttonElem.innerHTML = 'Start Service';
                    buttonElem.setAttribute('onclick', `updateHousekeepingStatus(${taskId}, 'Cleaning', 50, this)`);
                }
            }

            // Update KPI counters
            if (data.stats) {
                updateKPICounters(data.stats);
            } else {
                recalculateKPIsFromDOM();
            }

            // Re-trigger active tab filter if necessary
            initHousekeepingFilters();

            showHousekeepingToast(data.message || `Status updated to ${nextStatus}`, 'success');
        } else {
            showHousekeepingToast(data.message || 'Failed to update housekeeping status', 'danger');
            if (buttonElem && buttonElem.tagName === 'BUTTON') {
                buttonElem.disabled = false;
                buttonElem.innerHTML = originalButtonHtml;
            }
        }
    })
    .catch(err => {
        console.error('Error updating housekeeping status:', err);
        showHousekeepingToast('Error updating status', 'danger');
        if (buttonElem && buttonElem.tagName === 'BUTTON') {
            buttonElem.disabled = false;
            buttonElem.innerHTML = originalButtonHtml;
        }
    });
}

// Open Delete Confirmation Modal
function confirmDeleteTask(taskId, roomName) {
    const taskIdInput = document.getElementById('delete_task_id');
    const roomNameElem = document.getElementById('deleteTaskRoomName');
    const modalElem = document.getElementById('deleteTaskModal');

    if (taskIdInput) taskIdInput.value = taskId;
    if (roomNameElem) roomNameElem.textContent = roomName;

    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
            bsModal.show();
        } else {
            modalElem.style.display = 'block';
            modalElem.classList.add('show');
        }
    }
}

// Execute Task Deletion via AJAX
function executeDeleteTask() {
    const taskId = document.getElementById('delete_task_id')?.value;
    if (!taskId) return;

    const modalElem = document.getElementById('deleteTaskModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modalElem);
            if (bsModal) bsModal.hide();
        } else {
            modalElem.style.display = 'none';
            modalElem.classList.remove('show');
        }
    }

    const cardWrapper = document.querySelector(`.hk-card-wrapper[data-task-id="${taskId}"]`);
    if (cardWrapper) {
        cardWrapper.classList.add('card-deleting');
    }

    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('task_id', taskId);
    formData.append('is_ajax', '1');

    fetch('/hotel_website/admin/housekeeping.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => {
                if (cardWrapper) cardWrapper.remove();
                if (data.stats) updateKPICounters(data.stats);
                else recalculateKPIsFromDOM();
            }, 400);
            showHousekeepingToast(data.message || 'Housekeeping task removed.', 'info');
        } else {
            if (cardWrapper) cardWrapper.classList.remove('card-deleting');
            showHousekeepingToast(data.message || 'Deletion failed', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        if (cardWrapper) cardWrapper.classList.remove('card-deleting');
        showHousekeepingToast('Error deleting task', 'danger');
    });
}

// AJAX Form Submit for Adding Housekeeping Task
function initAddTaskFormAjax() {
    const form = document.getElementById('addTaskForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitAddTaskBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...`;
        }

        const formData = new FormData(this);
        formData.append('is_ajax', '1');

        fetch('/hotel_website/admin/housekeeping.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Task';
            }

            if (data.success && data.task) {
                const modalElem = document.getElementById('addTaskModal');
                if (modalElem) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bsModal = bootstrap.Modal.getInstance(modalElem);
                        if (bsModal) bsModal.hide();
                    }
                }
                form.reset();

                // Append New Card to DOM
                appendNewHousekeepingCard(data.task);

                if (data.stats) updateKPICounters(data.stats);
                else recalculateKPIsFromDOM();

                showHousekeepingToast(data.message || 'New task added successfully!', 'success');
            } else {
                showHousekeepingToast(data.message || 'Failed to add task', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Task';
            }
            showHousekeepingToast('Error adding housekeeping task', 'danger');
        });
    });
}

// Dynamically Render and Append New Task Card
function appendNewHousekeepingCard(t) {
    const grid = document.getElementById('hkGrid');
    if (!grid) return;

    const emptyState = document.getElementById('hkEmptyState');
    if (emptyState) emptyState.style.display = 'none';

    const status = t.status || 'Dirty';
    const priority = t.priority || 'MEDIUM';
    const progress = t.progress || 0;
    const stClass = status.toLowerCase();
    const prClass = priority.toLowerCase();
    const fillClass = `hk-fill-${stClass}`;
    const searchStr = `${t.room_no} ${t.room_type} ${t.floor} ${t.assigned_staff} ${status} ${priority}`.toLowerCase();

    let actionBtnHtml = '';
    if (status === 'Dirty') {
        actionBtnHtml = `<button type="button" class="hk-btn-action btn-start-cleaning" onclick="updateHousekeepingStatus(${t.task_id}, 'Cleaning', 50, this)">Start Cleaning</button>`;
    } else if (status === 'Cleaning') {
        actionBtnHtml = `<button type="button" class="hk-btn-action btn-mark-clean" onclick="updateHousekeepingStatus(${t.task_id}, 'Clean', 85, this)">Mark Clean</button>`;
    } else if (status === 'Clean') {
        actionBtnHtml = `<button type="button" class="hk-btn-action btn-inspected" onclick="updateHousekeepingStatus(${t.task_id}, 'Inspected', 100, this)"><i class="fa-regular fa-circle-check"></i> Inspected</button>`;
    } else if (status === 'Inspected') {
        actionBtnHtml = `<button type="button" class="hk-btn-action btn-room-ready" onclick="updateHousekeepingStatus(${t.task_id}, 'Dirty', 0, this)"><i class="fa-solid fa-check"></i> Room Ready (Reset)</button>`;
    } else {
        actionBtnHtml = `<button type="button" class="hk-btn-action btn-start-cleaning" onclick="updateHousekeepingStatus(${t.task_id}, 'Cleaning', 50, this)">Start Service</button>`;
    }

    const cardWrapper = document.createElement('div');
    cardWrapper.className = 'hk-card-wrapper card-entering';
    cardWrapper.setAttribute('data-task-id', t.task_id);
    cardWrapper.setAttribute('data-status', stClass);
    cardWrapper.setAttribute('data-search', searchStr);

    cardWrapper.innerHTML = `
        <div class="hk-card">
            <div>
                <div class="hk-card-header">
                    <h3 class="hk-room-title">Room ${escapeHtml(t.room_no)}</h3>
                    <div class="d-flex align-items-center gap-2">
                        <span class="hk-badge hk-badge-clickable hk-badge-status-${stClass}" onclick="cycleHousekeepingStatus(${t.task_id}, this)" title="Click to cycle status">
                            ${status.toUpperCase()}
                        </span>
                        <button type="button" class="hk-btn-delete" title="Delete Task" onclick="confirmDeleteTask(${t.task_id}, 'Room ${escapeHtml(t.room_no)}')">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="hk-room-subtitle mb-0">
                        ${escapeHtml(t.room_type)} &middot; ${escapeHtml(t.floor)}
                    </div>
                    <span class="hk-badge hk-badge-priority-${prClass}">
                        ${priority.toUpperCase()}
                    </span>
                </div>
                <div class="hk-details">
                    <div class="hk-details-row">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Assigned: <strong>${escapeHtml(t.assigned_staff)}</strong></span>
                    </div>
                    <div class="hk-details-row">
                        <i class="fa-regular fa-clock"></i>
                        <span>Due: <strong>${escapeHtml(t.due_time)}</strong>
                        <span class="start-time-text"></span>
                        </span>
                    </div>
                    ${t.notes ? `<div class="hk-notes">${escapeHtml(t.notes)}</div>` : ''}
                </div>
            </div>
            <div>
                <div class="hk-progress-wrapper">
                    <div class="hk-progress-header">
                        <span>Progress</span>
                        <span class="hk-progress-percent">${progress}%</span>
                    </div>
                    <div class="hk-progress-bar-bg">
                        <div class="hk-progress-bar-fill ${fillClass}" style="width: ${progress}%;"></div>
                    </div>
                </div>
                ${actionBtnHtml}
            </div>
        </div>
    `;

    grid.insertBefore(cardWrapper, grid.firstChild);
    setTimeout(() => cardWrapper.classList.remove('card-entering'), 400);
}

// Update KPI Summary Counters and Pill Badge Counts
function updateKPICounters(stats) {
    if (!stats) return;

    animateCounter('kpiDirty', stats.dirty || 0);
    animateCounter('kpiCleaning', stats.cleaning || 0);
    animateCounter('kpiClean', stats.clean || 0);
    animateCounter('kpiInspected', stats.inspected || 0);
    animateCounter('kpiMaintenance', stats.maintenance || 0);

    const total = stats.total || 0;
    const headerTotal = document.getElementById('hkTotalTaskHeaderCount');
    if (headerTotal) headerTotal.textContent = total;

    updateElemText('pillCountAll', total);
    updateElemText('pillCountDirty', stats.dirty || 0);
    updateElemText('pillCountCleaning', stats.cleaning || 0);
    updateElemText('pillCountClean', stats.clean || 0);
    updateElemText('pillCountInspected', stats.inspected || 0);
    updateElemText('pillCountMaintenance', stats.maintenance || 0);
}

// Recalculate KPI Counts directly from DOM elements
function recalculateKPIsFromDOM() {
    const cards = document.querySelectorAll('.hk-card-wrapper');
    const counts = { dirty: 0, cleaning: 0, clean: 0, inspected: 0, maintenance: 0, total: cards.length };

    cards.forEach(card => {
        const st = (card.getAttribute('data-status') || '').toLowerCase();
        if (counts.hasOwnProperty(st)) counts[st]++;
    });

    updateKPICounters(counts);
}

function animateCounter(id, val) {
    const elem = document.getElementById(id);
    if (!elem) return;
    if (parseInt(elem.textContent) !== val) {
        elem.textContent = val;
        elem.classList.add('kpi-bump');
        setTimeout(() => elem.classList.remove('kpi-bump'), 350);
    }
}

function updateElemText(id, text) {
    const elem = document.getElementById(id);
    if (elem) elem.textContent = text;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Reset Housekeeping Filters
function resetHousekeepingFilters() {
    const searchInput = document.getElementById('hkSearchInput');
    if (searchInput) searchInput.value = '';

    const firstPill = document.querySelector('.hk-filter-pill[data-filter="all"]');
    if (firstPill) firstPill.click();
}
