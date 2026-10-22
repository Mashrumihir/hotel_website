/* =========================================================
   HOTELEASE - CUSTOMERS MANAGEMENT JAVASCRIPT MODULE
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initCustomerSearchAndFilters();
    initCustomerProfileModalFallback();
    initDeleteFormAjax();
});

function toggleShowCustomerPassword(id) {
    const textEl = document.getElementById('custPassText_' + id);
    const iconEl = document.getElementById('custPassIcon_' + id);
    if (!textEl || !iconEl) return;

    const plain = textEl.getAttribute('data-plain') || 'user123';
    if (textEl.textContent === '••••••••') {
        textEl.textContent = plain;
        iconEl.classList.remove('fa-eye');
        iconEl.classList.add('fa-eye-slash');
    } else {
        textEl.textContent = '••••••••';
        iconEl.classList.remove('fa-eye-slash');
        iconEl.classList.add('fa-eye');
    }
}

function initDeleteFormAjax() {
    const deleteForm = document.querySelector('#deleteCustomerModal form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const customerId = document.getElementById('delete_customer_id')?.value;
            if (!customerId) return;

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnHTML = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1.5"></i> Deleting...';
            }

            const formData = new FormData(this);
            formData.append('is_ajax', '1');

            fetch('/hotel_website/admin/customers.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHTML;
                }

                if (data.success) {
                    const modalElem = document.getElementById('deleteCustomerModal');
                    if (modalElem) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem);
                            if (bsModal) bsModal.hide();
                        }
                        modalElem.style.display = 'none';
                        modalElem.classList.remove('show', 'is-open');
                    }

                    // Remove backdrops & scroll lock
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open', 'profile-modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';

                    // Smooth row & card removal animation
                    const row = document.querySelector(`.customer-data-row[data-id="${customerId}"]`);
                    if (row) {
                        row.classList.add('row-delete-animating');
                        setTimeout(() => row.remove(), 420);
                    }

                    const card = document.querySelector(`.customer-mobile-card[data-id="${customerId}"]`);
                    if (card) {
                        card.classList.add('card-delete-animating');
                        setTimeout(() => card.remove(), 420);
                    }

                    // Update KPI counters
                    if (data.stats) {
                        const kpiTotal = document.getElementById('kpiTotalCustomers');
                        if (kpiTotal) kpiTotal.textContent = data.stats.totalCustomers.toLocaleString();
                        const kpiActive = document.getElementById('kpiActiveCustomers');
                        if (kpiActive) kpiActive.textContent = data.stats.activeCustomers.toLocaleString();
                        const kpiInactive = document.getElementById('kpiInactiveCustomers');
                        if (kpiInactive) kpiInactive.textContent = data.stats.inactiveCustomers.toLocaleString();
                    }

                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    else if (typeof showDynamicToast === 'function') showDynamicToast(data.message, 'success');
                } else {
                    alert('Deletion failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error(err);
                deleteForm.submit();
            });
        });
    }
}

function initCustomerProfileModalFallback() {
    document.querySelectorAll('.modal [data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function () {
            const modal = button.closest('.modal');
            if (!modal || (typeof bootstrap !== 'undefined' && bootstrap.Modal)) return;
            hideModalFallback(modal);
        });
    });

    document.querySelectorAll('.offcanvas [data-bs-dismiss="offcanvas"]').forEach(button => {
        button.addEventListener('click', function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) return;
            const drawer = button.closest('.offcanvas');
            if (!drawer) return;
            if (drawer.id === 'addCustomerDrawer') {
                closeAddCustomerDrawerWithCheck();
            } else {
                hideCustomerDrawerFallback(drawer);
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) return;
        if (event.target.classList.contains('customer-modal-backdrop')) {
            const modal = document.querySelector('.modal.is-open');
            if (modal) hideModalFallback(modal);
        }
    });
}

function closeCustomerProfileModal() {
    const modal = document.getElementById('customerDetailsDrawer');
    if (!modal) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
    }
    modal.classList.remove('is-open', 'show');
    modal.style.display = 'none';
    document.body.classList.remove('profile-modal-open', 'modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.querySelectorAll('.customer-modal-backdrop').forEach(el => el.remove());
}

function showCustomerDrawerFallback(drawerElem, closeHandler) {
    let backdrop = document.getElementById('customerDrawerBackdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'customerDrawerBackdrop';
        backdrop.className = 'customer-drawer-backdrop';
        document.body.appendChild(backdrop);
    }
    backdrop.onclick = closeHandler;
    drawerElem.classList.add('show');
    backdrop.classList.add('show');
    document.body.classList.add('offcanvas-open');
}

function showModalFallback(modalElem) {
    modalElem.classList.add('is-open', 'show');
    modalElem.style.display = 'block';
    modalElem.removeAttribute('aria-hidden');
    modalElem.setAttribute('aria-modal', 'true');
    document.body.classList.add('profile-modal-open');

    if (!document.querySelector('.customer-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show customer-modal-backdrop';
        document.body.appendChild(backdrop);
    }
}

function hideModalFallback(modalElem) {
    modalElem.classList.remove('is-open', 'show');
    modalElem.style.display = 'none';
    modalElem.setAttribute('aria-hidden', 'true');
    modalElem.removeAttribute('aria-modal');
    document.body.classList.remove('profile-modal-open');
    const backdrop = document.querySelector('.customer-modal-backdrop');
    if (backdrop) backdrop.remove();
}

function hideCustomerDrawerFallback(drawerElem) {
    const backdrop = document.getElementById('customerDrawerBackdrop');
    drawerElem.classList.remove('show');
    if (backdrop) backdrop.remove();
    document.body.classList.remove('offcanvas-open');
}

// Search, Filter & Sorting System
function initCustomerSearchAndFilters() {
    const searchInput = document.getElementById('customerSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const statusFilter = document.getElementById('filterStatus');
    const accountStatusFilter = document.getElementById('filterAccountStatus');
    const dateFilter = document.getElementById('filterDate');
    const bookingsFilter = document.getElementById('filterBookings');
    const sortSelect = document.getElementById('sortCustomers');

    if (!searchInput) return;

    function filterAndSortRows() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = (statusFilter ? statusFilter.value : 'all').toLowerCase();
        const selectedAccountStatus = (accountStatusFilter ? accountStatusFilter.value : 'all').toLowerCase();
        const selectedDate = (dateFilter ? dateFilter.value : 'all').toLowerCase();
        const selectedBookings = (bookingsFilter ? bookingsFilter.value : 'all').toLowerCase();
        const sortVal = sortSelect ? sortSelect.value : 'newest';

        if (searchClearBtn) {
            searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
        }

        const rows = Array.from(document.querySelectorAll('.customer-data-row'));
        const mobileCards = Array.from(document.querySelectorAll('.customer-mobile-card'));

        let visibleCount = 0;

        rows.forEach((row, idx) => {
            const name = (row.getAttribute('data-name') || '').toLowerCase();
            const email = (row.getAttribute('data-email') || '').toLowerCase();
            const phone = (row.getAttribute('data-phone') || '').toLowerCase();
            const idStr = (row.getAttribute('data-id') || '').toLowerCase();
            const gender = (row.getAttribute('data-gender') || '').toLowerCase();
            const accountStatus = (row.getAttribute('data-status') || 'active').toLowerCase();
            const stays = parseInt(row.getAttribute('data-stays') || '0', 10);

            // Search Match
            const matchesSearch = !query || name.includes(query) || email.includes(query) || phone.includes(query) || idStr.includes(query);

            // Gender / Status Filter
            let matchesStatus = true;
            if (selectedStatus !== 'all') {
                matchesStatus = (gender === selectedStatus);
            }

            const matchesAccountStatus = selectedAccountStatus === 'all' || accountStatus === selectedAccountStatus;

            // Bookings Filter
            let matchesBookings = true;
            if (selectedBookings === 'has_bookings') {
                matchesBookings = (stays > 0);
            } else if (selectedBookings === 'no_bookings') {
                matchesBookings = (stays === 0);
            }

            const isVisible = matchesSearch && matchesStatus && matchesAccountStatus && matchesBookings;

            row.style.display = isVisible ? '' : 'none';
            if (mobileCards[idx]) {
                mobileCards[idx].style.display = isVisible ? '' : 'none';
            }

            if (isVisible) visibleCount++;
        });

        // Sorting Logic for Desktop Table
        const tbody = document.getElementById('customerTableBody');

        const sortComparator = (a, b) => {
            if (sortVal === 'name_asc') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            } else if (sortVal === 'name_desc') {
                return (b.getAttribute('data-name') || '').localeCompare(a.getAttribute('data-name') || '');
            } else if (sortVal === 'stays_desc') {
                return parseInt(b.getAttribute('data-stays') || '0') - parseInt(a.getAttribute('data-stays') || '0');
            } else if (sortVal === 'oldest') {
                return parseInt(a.getAttribute('data-id') || '0') - parseInt(b.getAttribute('data-id') || '0');
            } else {
                // newest
                return parseInt(b.getAttribute('data-id') || '0') - parseInt(a.getAttribute('data-id') || '0');
            }
        };

        if (tbody) {
            const visibleRows = rows.filter(r => r.style.display !== 'none');
            visibleRows.sort(sortComparator);
            visibleRows.forEach(r => tbody.appendChild(r));
        }

        // Empty State & Counter
        const countDisplay = document.getElementById('recordCountDisplay');
        if (countDisplay) {
            countDisplay.textContent = `Showing ${visibleCount} customer(s)`;
        }

        const emptyState = document.getElementById('customerEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    // Event Listeners
    searchInput.addEventListener('input', filterAndSortRows);
    if (statusFilter) statusFilter.addEventListener('change', filterAndSortRows);
    if (accountStatusFilter) accountStatusFilter.addEventListener('change', filterAndSortRows);
    if (dateFilter) dateFilter.addEventListener('change', filterAndSortRows);
    if (bookingsFilter) bookingsFilter.addEventListener('change', filterAndSortRows);
    if (sortSelect) sortSelect.addEventListener('change', filterAndSortRows);

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            filterAndSortRows();
            searchInput.focus();
        });
    }
}

// Reset Filters Function
function resetCustomerFilters() {
    const searchInput = document.getElementById('customerSearchInput');
    const statusFilter = document.getElementById('filterStatus');
    const accountStatusFilter = document.getElementById('filterAccountStatus');
    const dateFilter = document.getElementById('filterDate');
    const bookingsFilter = document.getElementById('filterBookings');
    const sortSelect = document.getElementById('sortCustomers');

    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = 'all';
    if (accountStatusFilter) accountStatusFilter.value = 'all';
    if (dateFilter) dateFilter.value = 'all';
    if (bookingsFilter) bookingsFilter.value = 'all';
    if (sortSelect) sortSelect.value = 'newest';

    const event = new Event('input');
    if (searchInput) searchInput.dispatchEvent(event);
}

// Open Customer Side Drawer
function openCustomerDrawer(elem) {
    let rawData = null;
    if (typeof elem === 'string') {
        rawData = elem;
    } else if (elem && typeof elem === 'object' && elem.customer_id) {
        rawData = elem;
    } else if (elem && elem.getAttribute) {
        rawData = elem.getAttribute('data-customer') || elem.closest('[data-customer]')?.getAttribute('data-customer');
    }

    if (!rawData) return;

    try {
        const c = typeof rawData === 'object' ? rawData : JSON.parse(rawData);

        // Fill Header & Hero Profile
        document.getElementById('drawer_name').textContent = c.name || 'Guest User';
        document.getElementById('drawer_id').textContent = '#' + c.customer_id;
        document.getElementById('drawer_joined').textContent = 'Joined: ' + (c.created_at ? c.created_at.split(' ')[0] : 'N/A');

        // Avatar Handling
        const avatarImg = document.getElementById('drawer_avatar_img');
        const avatarInitials = document.getElementById('drawer_avatar_initials');
        if (c.profile_pic) {
            avatarImg.src = c.profile_pic;
            avatarImg.style.display = 'block';
            avatarInitials.style.display = 'none';
        } else {
            avatarImg.style.display = 'none';
            avatarInitials.style.display = 'flex';
            avatarInitials.textContent = (c.name || 'C').charAt(0).toUpperCase();
        }

        // Contact Info & Meta
        const elEmail = document.getElementById('drawer_email');
        if (elEmail) elEmail.textContent = c.email || 'N/A';
        const elEmailLink = document.getElementById('drawer_email_link');
        if (elEmailLink) elEmailLink.href = 'mailto:' + (c.email || '');
        
        const elMobile = document.getElementById('drawer_mobile');
        if (elMobile) elMobile.textContent = c.mobile || 'N/A';
        const elMobileLink = document.getElementById('drawer_mobile_link');
        if (elMobileLink) elMobileLink.href = 'tel:' + (c.mobile || '');

        const elGender = document.getElementById('drawer_gender');
        if (elGender) elGender.textContent = c.gender || 'Male';

        const elIdProof = document.getElementById('drawer_id_proof');
        if (elIdProof) elIdProof.textContent = c.id_proof || 'N/A';

        const elAddress = document.getElementById('drawer_address');
        if (elAddress) elAddress.textContent = c.address || 'No primary residence address recorded.';

        // Metrics & Stays
        const bookings = c.bookings || [];
        const elStays = document.getElementById('drawer_total_stays');
        if (elStays) elStays.textContent = bookings.length;

        let completed = 0, pending = 0, cancelled = 0, totalSpent = 0;
        bookings.forEach(b => {
            const st = b.booking_status;
            if (st === 'Checked-Out' || st === 'Confirmed') completed++;
            else if (st === 'Pending') pending++;
            else if (st === 'Cancelled') cancelled++;

            if (st !== 'Cancelled') {
                totalSpent += parseFloat(b.total_amount || 0);
            }
        });

        const elCompleted = document.getElementById('drawer_completed_stays');
        if (elCompleted) elCompleted.textContent = completed;
        const elPending = document.getElementById('drawer_pending_stays');
        if (elPending) elPending.textContent = pending;
        const elCancelled = document.getElementById('drawer_cancelled_stays');
        if (elCancelled) elCancelled.textContent = cancelled;
        const elSpent = document.getElementById('drawer_total_spent');
        if (elSpent) elSpent.textContent = '₹' + totalSpent.toLocaleString('en-IN');
        const elLast = document.getElementById('drawer_last_booking');
        if (elLast) elLast.textContent = bookings.length && bookings[0].check_in ? bookings[0].check_in : 'N/A';

        // Render Recent Bookings
        const listContainer = document.getElementById('drawer_bookings_list');
        if (listContainer) {
            if (bookings.length > 0) {
                let html = '';
                bookings.forEach(b => {
                    let stBadge = '<span class="badge bg-success bg-opacity-10 text-success font-semibold px-2 py-1 rounded-pill">Checked-Out</span>';
                    if (b.booking_status === 'Pending') stBadge = '<span class="badge bg-warning bg-opacity-10 text-warning font-semibold px-2 py-1 rounded-pill">Pending</span>';
                    else if (b.booking_status === 'Confirmed') stBadge = '<span class="badge bg-primary bg-opacity-10 text-primary font-semibold px-2 py-1 rounded-pill">Confirmed</span>';
                    else if (b.booking_status === 'Cancelled') stBadge = '<span class="badge bg-danger bg-opacity-10 text-danger font-semibold px-2 py-1 rounded-pill">Cancelled</span>';

                    html += `
                        <div class="p-2.5 border rounded-3 bg-white mb-2 shadow-2xs">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary small">#HE-${b.booking_id}</span>
                                ${stBadge}
                            </div>
                            <div class="small fw-semibold text-navy mb-1"><i class="fa-solid fa-bed me-1.5 text-muted"></i>Room ${b.room_no || ''} (${b.type_name || ''})</div>
                            <div class="d-flex justify-content-between align-items-center extra-small text-muted">
                                <span>${b.check_in} &rarr; ${b.check_out}</span>
                                <span class="fw-bold text-navy">₹${parseFloat(b.total_amount).toLocaleString('en-IN')}</span>
                            </div>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            } else {
                listContainer.innerHTML = '<div class="text-center text-muted py-3 small"><i class="fa-solid fa-calendar-xmark me-1"></i>No stay reservations recorded yet.</div>';
            }
        }

        // Attach Quick Edit / Delete Buttons in Drawer
        const editBtn = document.getElementById('drawerEditBtn');
        if (editBtn) {
            editBtn.onclick = function() {
                const drawerElem = document.getElementById('customerDetailsDrawer');
                closeCustomerProfileModal();
                openEditCustomerDrawer(c);
            };
        }

        const deleteBtn = document.getElementById('drawerDeleteBtn');
        if (deleteBtn) {
            deleteBtn.onclick = function() {
                const drawerElem = document.getElementById('customerDetailsDrawer');
                closeCustomerProfileModal();
                openDeleteCustomerModal(c.customer_id, c.name);
            };
        }

        // Show Drawer
        const drawerElem = document.getElementById('customerDetailsDrawer');
        if (drawerElem) drawerElem.style.display = '';
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(drawerElem);
            bsModal.show();
        } else {
            showModalFallback(drawerElem);
        }

    } catch (e) {
        console.error("Invalid customer data payload", e);
    }
}

// Open Add Customer Modal
function openAddCustomerModal() {
    openAddCustomerDrawer();
}

// Show Photo Preview Lightbox Modal & Zoom Controls
let currentCustomerPhotoZoom = 1.0;

function zoomPhotoModal(delta) {
    const img = document.getElementById('photoPreviewImg');
    const zoomText = document.getElementById('photoZoomLevelText');
    if (!img || img.style.display === 'none') return;

    currentCustomerPhotoZoom += delta;
    if (currentCustomerPhotoZoom < 0.5) currentCustomerPhotoZoom = 0.5;
    if (currentCustomerPhotoZoom > 3.5) currentCustomerPhotoZoom = 3.5;

    img.style.transform = `scale(${currentCustomerPhotoZoom})`;
    img.style.transition = 'transform 0.2s ease';

    if (zoomText) {
        zoomText.textContent = Math.round(currentCustomerPhotoZoom * 100) + '%';
    }
}

function resetPhotoModalZoom() {
    currentCustomerPhotoZoom = 1.0;
    const img = document.getElementById('photoPreviewImg');
    const zoomText = document.getElementById('photoZoomLevelText');
    if (img) {
        img.style.transform = 'scale(1)';
    }
    if (zoomText) {
        zoomText.textContent = '100%';
    }
}

function showPhotoPreview(src, name, initials) {
    const modalElem = document.getElementById('photoPreviewModal');
    if (!modalElem) return;

    resetPhotoModalZoom();

    const titleElem = document.getElementById('photoPreviewTitle');
    const subtextElem = document.getElementById('photoPreviewSubtext');
    const img = document.getElementById('photoPreviewImg');
    const initialsBox = document.getElementById('photoPreviewInitials');
    const downloadBtn = document.getElementById('downloadPhotoBtn');

    if (titleElem) titleElem.innerHTML = `<i class="fa-solid fa-id-card text-info me-2"></i>${name || 'Guest'} - Profile Photo`;
    if (subtextElem) subtextElem.textContent = name || '';

    if (src) {
        img.src = src;
        img.style.display = 'block';
        initialsBox.style.display = 'none';
        if (downloadBtn) {
            downloadBtn.href = src;
            downloadBtn.download = `${(name || 'customer').replace(/[^a-z0-9]+/gi, '-').toLowerCase()}-profile-photo`;
            downloadBtn.classList.remove('disabled');
            downloadBtn.removeAttribute('aria-disabled');
        }
        
        // Attach Mouse Wheel Zoom
        img.onwheel = function(e) {
            e.preventDefault();
            if (e.deltaY < 0) zoomPhotoModal(0.15);
            else zoomPhotoModal(-0.15);
        };
    } else {
        img.style.display = 'none';
        initialsBox.style.display = 'flex';
        initialsBox.textContent = initials || (name ? name.charAt(0).toUpperCase() : 'C');
        if (downloadBtn) {
            downloadBtn.href = '#';
            downloadBtn.classList.add('disabled');
            downloadBtn.setAttribute('aria-disabled', 'true');
        }
    }

    if (modalElem) modalElem.style.display = '';
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem);
        bsModal.show();
    } else {
        showModalFallback(modalElem);
    }
}

function triggerPhotoPreviewFromElem(elem) {
    if (!elem) return;
    const src = elem.getAttribute('data-src') || elem.getAttribute('src') || null;
    const name = elem.getAttribute('data-name') || 'Guest';
    const initials = elem.getAttribute('data-initials') || (name ? name.charAt(0).toUpperCase() : 'C');
    showPhotoPreview(src, name, initials);
}

function updateCustomerPhotoActions(prefix, src, name) {
    const viewBtn = document.getElementById(`${prefix}ViewPhotoBtn`);
    const downloadBtn = document.getElementById(`${prefix}DownloadPhotoBtn`);
    const hasPhoto = Boolean(src);

    if (viewBtn) {
        viewBtn.style.display = hasPhoto ? 'inline-flex' : 'none';
        viewBtn.onclick = hasPhoto ? () => showPhotoPreview(src, name) : null;
    }
    if (downloadBtn) {
        downloadBtn.style.display = hasPhoto ? 'inline-flex' : 'none';
        downloadBtn.href = hasPhoto ? src : '#';
        downloadBtn.download = hasPhoto ? `${(name || 'customer').replace(/[^a-z0-9]+/gi, '-').toLowerCase()}-profile-photo` : '';
    }
}

function updateCombinedIdProof(prefix) {
    const typeElem = document.getElementById(prefix + '_id_proof_type');
    const numElem = document.getElementById(prefix + '_id_proof_num');
    const hiddenElem = document.getElementById(prefix + '_id_proof');
    if (hiddenElem) {
        const typeVal = typeElem ? typeElem.value : 'Aadhar Card';
        const numVal = numElem ? numElem.value.trim() : '';
        hiddenElem.value = numVal ? (typeVal + ' - ' + numVal) : '';
    }
}

function setIdProofSelect(selectId, value) {
    const hiddenElem = document.getElementById(selectId);
    if (hiddenElem) hiddenElem.value = value || '';

    const prefix = selectId.startsWith('edit') ? 'edit' : 'add';
    const typeElem = document.getElementById(prefix + '_id_proof_type');
    const numElem = document.getElementById(prefix + '_id_proof_num');

    const proofStr = value || '';
    if (proofStr.includes(' - ')) {
        const parts = proofStr.split(' - ');
        if (typeElem) typeElem.value = parts[0] || 'Aadhar Card';
        if (numElem) numElem.value = parts.slice(1).join(' - ') || '';
    } else {
        if (numElem) numElem.value = proofStr;
    }
}

function openDrawerPhotoPreview() {
    const avatarImg = document.getElementById('drawer_avatar_img');
    const avatarInitials = document.getElementById('drawer_avatar_initials');
    const name = document.getElementById('drawer_name')?.textContent || 'Guest';

    let src = null;
    if (avatarImg && avatarImg.style.display !== 'none') {
        src = avatarImg.src;
    }
    let initials = avatarInitials?.textContent || 'C';

    showPhotoPreview(src, name, initials);
}

// Open Edit Customer Drawer (Right-side Offcanvas)
function openEditCustomerDrawer(elem) {
    let rawData = null;
    if (typeof elem === 'string') {
        rawData = elem;
    } else if (elem && typeof elem === 'object' && elem.customer_id) {
        rawData = elem;
    } else if (elem && elem.getAttribute) {
        rawData = elem.getAttribute('data-customer') || elem.closest('[data-customer]')?.getAttribute('data-customer');
    }

    if (!rawData) return;

    try {
        const c = typeof rawData === 'object' ? rawData : JSON.parse(rawData);

        // Populate Form Fields
        document.getElementById('edit_customer_id').value = c.customer_id;
        document.getElementById('edit_name').value = c.name || '';
        document.getElementById('edit_gender').value = c.gender || 'Male';
        document.getElementById('edit_mobile').value = c.mobile || '';
        document.getElementById('edit_email').value = c.email || '';
        setIdProofSelect('edit_id_proof', c.id_proof || '');
        document.getElementById('edit_address').value = c.address || '';
        document.getElementById('existing_profile_pic').value = c.profile_pic || '';

        const passInput = document.getElementById('edit_password');
        if (passInput) passInput.value = '';

        // Reset Photo Preview
        const previewImg = document.getElementById('edit_avatar_preview');
        const initialsBox = document.getElementById('edit_avatar_initials');
        const removeBtn = document.getElementById('removeEditPhotoBtn');

        if (c.profile_pic) {
            if (previewImg) {
                previewImg.src = c.profile_pic;
                previewImg.style.display = 'block';
            }
            if (initialsBox) initialsBox.style.display = 'none';
            if (removeBtn) removeBtn.style.display = 'inline-block';
            updateCustomerPhotoActions('edit', c.profile_pic, c.name);
        } else {
            if (previewImg) {
                previewImg.src = '';
                previewImg.style.display = 'none';
            }
            if (initialsBox) {
                initialsBox.style.display = 'flex';
                initialsBox.textContent = (c.name || 'C').charAt(0).toUpperCase();
            }
            if (removeBtn) removeBtn.style.display = 'none';
            updateCustomerPhotoActions('edit', null, c.name);
        }

        // Hide error alert
        const errorAlert = document.getElementById('editCustomerErrorAlert');
        if (errorAlert) errorAlert.classList.add('d-none');

        // Show Edit Drawer
        const drawerElem = document.getElementById('editCustomerDrawer');
        if (drawerElem) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(drawerElem);
                bsOffcanvas.show();
            } else {
                showCustomerDrawerFallback(drawerElem, () => hideCustomerDrawerFallback(drawerElem));
            }
        }
    } catch (e) {
        console.error("Failed to populate edit customer drawer", e);
    }
}

// Fallback alias for backward compatibility
function openEditCustomerModal(elem) {
    openEditCustomerDrawer(elem);
}

// Photo Upload Handling for Edit Drawer
function triggerEditPhotoUpload() {
    const input = document.getElementById('edit_profile_pic_input');
    if (input) input.click();
}

function previewEditCustomerPhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImg = document.getElementById('edit_avatar_preview');
            const initialsBox = document.getElementById('edit_avatar_initials');
            const removeBtn = document.getElementById('removeEditPhotoBtn');

            if (previewImg && initialsBox) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                initialsBox.style.display = 'none';
            }
            if (removeBtn) removeBtn.style.display = 'inline-block';
            updateCustomerPhotoActions('edit', e.target.result, document.getElementById('edit_name')?.value || 'Customer');
        };
        reader.readAsDataURL(file);
    }
}

function clearEditPhotoPreview() {
    const input = document.getElementById('edit_profile_pic_input');
    if (input) input.value = '';

    document.getElementById('existing_profile_pic').value = '';

    const previewImg = document.getElementById('edit_avatar_preview');
    const initialsBox = document.getElementById('edit_avatar_initials');
    const removeBtn = document.getElementById('removeEditPhotoBtn');

    if (previewImg) {
        previewImg.src = '';
        previewImg.style.display = 'none';
    }
    if (initialsBox) initialsBox.style.display = 'flex';
    if (removeBtn) removeBtn.style.display = 'none';
    updateCustomerPhotoActions('edit', null, document.getElementById('edit_name')?.value || 'Customer');
}

// Edit Form Submission Handler via AJAX
function handleEditCustomerFormSubmit(e) {
    e.preventDefault();

    const errorAlert = document.getElementById('editCustomerErrorAlert');
    if (errorAlert) errorAlert.classList.add('d-none');

    const submitBtn = document.getElementById('editCustomerSubmitBtn');
    const submitBtnText = document.getElementById('editCustomerSubmitBtnText');
    const form = document.getElementById('editCustomerForm');
    const formData = new FormData(form);

    if (submitBtn) submitBtn.disabled = true;
    if (submitBtnText) submitBtnText.textContent = 'Saving Changes...';

    fetch('/hotel_website/admin/customers.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned status ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            console.error("Server response was not JSON:", text);
            throw new Error("Server response format error.");
        }

        if (data.success) {
            const drawerElem = document.getElementById('editCustomerDrawer');
            if (drawerElem) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(drawerElem);
                    if (bsOffcanvas) bsOffcanvas.hide();
                } else {
                    hideCustomerDrawerFallback(drawerElem);
                }
            }

            showAddCustomerToast('Customer Profile Updated', data.message || 'Customer details saved successfully.');

            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            if (errorAlert) {
                const errMsg = document.getElementById('editCustomerErrorMessage');
                if (errMsg) errMsg.textContent = data.message || 'Unable to update customer details.';
                errorAlert.classList.remove('d-none');
            }
        }
    })
    .catch(err => {
        console.error("Edit Customer AJAX Error:", err);
        if (errorAlert) {
            const errMsg = document.getElementById('editCustomerErrorMessage');
            if (errMsg) errMsg.textContent = err.message || 'Network or server error. Please try again.';
            errorAlert.classList.remove('d-none');
        }
    })
    .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
        if (submitBtnText) submitBtnText.textContent = 'Save Profile Changes';
    });
}

// Open Delete Modal
function openDeleteCustomerModal(id, name) {
    if (typeof closeCustomerProfileModal === 'function') closeCustomerProfileModal();
    if (typeof forceCloseAddCustomerDrawer === 'function') forceCloseAddCustomerDrawer();

    if (typeof id === 'object' && id !== null) {
        const elem = id;
        if (elem.getAttribute) {
            id = elem.getAttribute('data-id') || elem.closest('[data-id]')?.getAttribute('data-id');
            name = elem.getAttribute('data-name') || elem.closest('[data-name]')?.getAttribute('data-name');

            if (!id) {
                const raw = elem.getAttribute('data-customer') || elem.closest('[data-customer]')?.getAttribute('data-customer');
                if (raw) {
                    try {
                        const c = JSON.parse(raw);
                        id = c.customer_id;
                        name = c.name;
                    } catch(e) {}
                }
            }
        } else if (elem.customer_id) {
            name = elem.name;
            id = elem.customer_id;
        }
    }

    if (!id) return;

    const idInput = document.getElementById('delete_customer_id');
    const nameDisplay = document.getElementById('delete_customer_name_display');

    if (idInput) idInput.value = id;
    if (nameDisplay) nameDisplay.textContent = `#CST-00${id} (${name || 'Guest'})`;

    const modalElem = document.getElementById('deleteCustomerModal');
    if (modalElem) {
        modalElem.style.display = '';
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem);
            bsModal.show();
        } else {
            showModalFallback(modalElem);
        }
    }
}

// Export Customers CSV
function exportCustomerCSV() {
    const rows = document.querySelectorAll('.customer-data-row');
    if (!rows.length) {
        alert("No customer records available to export.");
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,ID,Customer Name,Gender,Mobile,Email,ID Proof,Joined Date,Total Stays\n";
    rows.forEach(r => {
        const id = r.getAttribute('data-id') || '';
        const name = `"${(r.getAttribute('data-name') || '').replace(/"/g, '""')}"`;
        const gender = r.getAttribute('data-gender') || '';
        const mobile = r.getAttribute('data-phone') || '';
        const email = r.getAttribute('data-email') || '';
        const date = r.getAttribute('data-date') || '';
        const stays = r.getAttribute('data-stays') || '0';

        csvContent += `${id},${name},${gender},${mobile},${email},"",${date},${stays}\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `HotelEase_Customers_Report_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/* =========================================================
   ADD CUSTOMER RIGHT-SIDE DRAWER & AJAX CONTROLLER
   ========================================================= */

let isAddFormDirty = false;

document.addEventListener('DOMContentLoaded', function () {
    initAddCustomerFormListeners();
});

function initAddCustomerFormListeners() {
    const form = document.getElementById('addCustomerForm');
    if (!form) return;

    // Listen for changes to mark form as dirty
    form.addEventListener('input', function () {
        isAddFormDirty = true;
    });
    form.addEventListener('change', function () {
        isAddFormDirty = true;
    });

    // ESC key listener for closing drawer safely
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const drawerElem = document.getElementById('addCustomerDrawer');
            if (drawerElem && drawerElem.classList.contains('show')) {
                closeAddCustomerDrawerWithCheck();
            }
        }
    });
}

// Open Add Customer Drawer
function openAddCustomerDrawer() {
    const drawerElem = document.getElementById('addCustomerDrawer');
    if (!drawerElem) return;

    resetAddCustomerForm();

    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(drawerElem);
        bsOffcanvas.show();
    } else {
        showCustomerDrawerFallback(drawerElem, closeAddCustomerDrawerWithCheck);
    }

    setTimeout(() => {
        const nameInput = document.getElementById('add_name');
        if (nameInput) nameInput.focus();
    }, 300);
}

// Close Drawer with Unsaved Changes Check
function closeAddCustomerDrawerWithCheck() {
    const drawerElem = document.getElementById('addCustomerDrawer');
    if (!drawerElem) return;

    if (isAddFormDirty) {
        const modalElem = document.getElementById('unsavedChangesModal');
        if (modalElem) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem);
                bsModal.show();
            } else {
                showModalFallback(modalElem);
            }
            return;
        }
    }

    forceCloseAddCustomerDrawer();
}

// Force close without prompt
function forceCloseAddCustomerDrawer() {
    const drawerElem = document.getElementById('addCustomerDrawer');
    if (!drawerElem) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(drawerElem);
        if (bsOffcanvas) bsOffcanvas.hide();
    } else {
        hideCustomerDrawerFallback(drawerElem);
    }
    resetAddCustomerForm();
}

// Confirm Discard Unsaved Changes
function confirmDiscardAddCustomer() {
    const modalElem = document.getElementById('unsavedChangesModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modalElem);
            if (bsModal) bsModal.hide();
        } else {
            hideModalFallback(modalElem);
        }
    }
    isAddFormDirty = false;
    forceCloseAddCustomerDrawer();
}

// Reset Add Form
function resetAddCustomerForm() {
    const form = document.getElementById('addCustomerForm');
    if (form) form.reset();

    clearAddPhotoPreview();

    // Clear error messages
    const errorAlert = document.getElementById('addCustomerErrorAlert');
    if (errorAlert) errorAlert.classList.add('d-none');

    const errorMsgs = document.querySelectorAll('.error-msg');
    errorMsgs.forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });

    isAddFormDirty = false;
}

function resetAddCustomerFormWithConfirm() {
    if (isAddFormDirty) {
        if (confirm("Are you sure you want to clear all entered values?")) {
            resetAddCustomerForm();
        }
    } else {
        resetAddCustomerForm();
    }
}

// Photo Upload Handling & Preview
function triggerAddPhotoUpload() {
    const input = document.getElementById('add_profile_pic_input');
    if (input) input.click();
}

function previewAddCustomerPhoto(input) {
    const photoErr = document.getElementById('photo_error_msg');
    if (photoErr) {
        photoErr.textContent = '';
        photoErr.style.display = 'none';
    }

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Type Validation
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            if (photoErr) {
                photoErr.textContent = 'Invalid file format. Please select a JPG, PNG, WEBP, or GIF image.';
                photoErr.style.display = 'block';
            }
            input.value = '';
            return;
        }

        // Size Validation (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            if (photoErr) {
                photoErr.textContent = 'File size exceeds 5MB limit. Please choose a smaller image.';
                photoErr.style.display = 'block';
            }
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImg = document.getElementById('add_avatar_preview');
            const initialsBox = document.getElementById('add_avatar_initials');
            const removeBtn = document.getElementById('removeAddPhotoBtn');

            if (previewImg && initialsBox) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                initialsBox.style.display = 'none';
            }
            if (removeBtn) removeBtn.style.display = 'inline-block';
            updateCustomerPhotoActions('add', e.target.result, document.getElementById('add_name')?.value || 'Customer');
        };
        reader.readAsDataURL(file);
        isAddFormDirty = true;
    }
}

function clearAddPhotoPreview() {
    const input = document.getElementById('add_profile_pic_input');
    if (input) input.value = '';

    const previewImg = document.getElementById('add_avatar_preview');
    const initialsBox = document.getElementById('add_avatar_initials');
    const removeBtn = document.getElementById('removeAddPhotoBtn');

    if (previewImg) {
        previewImg.src = '';
        previewImg.style.display = 'none';
    }
    if (initialsBox) {
        initialsBox.style.display = 'flex';
    }
    if (removeBtn) {
        removeBtn.style.display = 'none';
    }
    updateCustomerPhotoActions('add', null, document.getElementById('add_name')?.value || 'Customer');
}

// Password Show/Hide Toggle
function togglePasswordVisibility(inputId, btnElem) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const icon = btnElem.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

// Client-Side Validation
function validateAddCustomerForm() {
    let isValid = true;

    // Clear previous errors
    const errorMsgs = document.querySelectorAll('.error-msg');
    errorMsgs.forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });

    const nameInput = document.getElementById('add_name');
    const emailInput = document.getElementById('add_email');
    const mobileInput = document.getElementById('add_mobile');
    const passwordInput = document.getElementById('add_password');
    const confirmPasswordInput = document.getElementById('add_confirm_password');

    // Name Validation
    if (!nameInput || !nameInput.value.trim()) {
        showFieldError('name_error_msg', 'Please enter customer full name.');
        isValid = false;
    }

    // Email Validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailInput || !emailInput.value.trim() || !emailRegex.test(emailInput.value.trim())) {
        showFieldError('email_error_msg', 'Please enter a valid email address.');
        isValid = false;
    }

    // Phone Validation
    const phoneRegex = /^[0-9+\s\-()]{7,20}$/;
    if (!mobileInput || !mobileInput.value.trim() || !phoneRegex.test(mobileInput.value.trim())) {
        showFieldError('mobile_error_msg', 'Please enter a valid phone number (at least 7 digits).');
        isValid = false;
    }

    // Password Validation
    if (!passwordInput || !passwordInput.value || passwordInput.value.length < 6) {
        showFieldError('password_error_msg', 'Password must be at least 6 characters long.');
        isValid = false;
    }

    // Confirm Password Validation
    if (!confirmPasswordInput || confirmPasswordInput.value !== passwordInput.value) {
        showFieldError('confirm_password_error_msg', 'Passwords do not match.');
        isValid = false;
    }

    return isValid;
}

function showFieldError(elementId, msg) {
    const elem = document.getElementById(elementId);
    if (elem) {
        elem.textContent = '⚠ ' + msg;
        elem.style.display = 'block';
    }
}

// Form Submission Handler via AJAX
function handleAddCustomerFormSubmit(e) {
    e.preventDefault();

    const errorAlert = document.getElementById('addCustomerErrorAlert');
    if (errorAlert) errorAlert.classList.add('d-none');

    if (!validateAddCustomerForm()) {
        return;
    }

    const submitBtn = document.getElementById('addCustomerSubmitBtn');
    const submitBtnText = document.getElementById('addCustomerSubmitBtnText');
    const form = document.getElementById('addCustomerForm');
    const formData = new FormData(form);

    // Set Loading State
    if (submitBtn) submitBtn.disabled = true;
    if (submitBtnText) submitBtnText.textContent = 'Creating Customer...';

    fetch('/hotel_website/admin/customers.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success Flow
            isAddFormDirty = false;
            forceCloseAddCustomerDrawer();
            showAddCustomerToast('Customer Added Successfully', data.message || 'Customer profile created.');

            // Dynamically Prepend New Row to Customer Table
            if (data.customer) {
                addNewCustomerRowToTable(data.customer);
            }
        } else {
            // Failure Flow - Keep Drawer Open & Show Error
            if (errorAlert) {
                const errMsg = document.getElementById('addCustomerErrorMessage');
                if (errMsg) errMsg.textContent = data.message || 'An error occurred while creating the customer.';
                errorAlert.classList.remove('d-none');
            }
        }
    })
    .catch(err => {
        console.error("AJAX Error:", err);
        if (errorAlert) {
            const errMsg = document.getElementById('addCustomerErrorMessage');
            if (errMsg) errMsg.textContent = 'Network error or server error. Please try again.';
            errorAlert.classList.remove('d-none');
        }
    })
    .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
        if (submitBtnText) submitBtnText.textContent = 'Create Customer';
    });
}

// Show Premium Toast
function showAddCustomerToast(title, message) {
    const toastElem = document.getElementById('premiumToast');
    if (!toastElem) return;

    const titleEl = document.getElementById('toastTitle');
    const msgEl = document.getElementById('toastMessage');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;

    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const bsToast = bootstrap.Toast.getOrCreateInstance(toastElem, { delay: 3500 });
        bsToast.show();
    } else {
        toastElem.classList.add('show');
        window.setTimeout(() => toastElem.classList.remove('show'), 3500);
    }
}

// Add New Customer Row Dynamically to Table
function addNewCustomerRowToTable(c) {
    const tbody = document.getElementById('customerTableBody');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.className = 'customer-data-row table-success-highlight';
    tr.setAttribute('data-customer', JSON.stringify(c));
    tr.setAttribute('data-id', c.customer_id);
    tr.setAttribute('data-name', c.name || '');
    tr.setAttribute('data-email', c.email || '');
    tr.setAttribute('data-phone', c.mobile || '');
    tr.setAttribute('data-gender', (c.gender || 'Male').toLowerCase());
    tr.setAttribute('data-stays', c.total_stays || 0);
    tr.setAttribute('data-date', c.created_at ? c.created_at.split(' ')[0] : new Date().toISOString().split('T')[0]);
    tr.onclick = function() { openCustomerDrawer(this); };

    const avatarHtml = c.profile_pic 
        ? `<img src="${c.profile_pic}" class="customer-avatar" alt="Profile" onclick="event.stopPropagation(); showPhotoPreview('${c.profile_pic}', '${escapeJs(c.name)}')" title="Click to view full photo">`
        : `<div class="customer-avatar-initials" onclick="event.stopPropagation(); showPhotoPreview(null, '${escapeJs(c.name)}', '${(c.name || 'C').charAt(0).toUpperCase()}')" title="Click to view full photo">${(c.name || 'C').charAt(0).toUpperCase()}</div>`;

    tr.innerHTML = `
        <td class="fw-bold text-primary text-nowrap">#CST-00${c.customer_id}</td>
        <td>
            <div class="d-flex align-items-center">
                ${avatarHtml}
                <div class="flex-grow-1">
                    <div class="fw-bold text-navy leading-snug">${escapeHtml(c.name)}</div>
                    <small class="text-muted extra-small">Customer #CUS00${c.customer_id}</small>
                </div>
            </div>
        </td>
        <td class="text-nowrap fw-semibold text-navy">${escapeHtml(c.mobile)}</td>
        <td><span class="cell-truncate text-muted" title="${escapeHtml(c.email)}">${escapeHtml(c.email)}</span></td>
        <td class="text-muted small">${escapeHtml(c.gender || 'Male')}</td>
        <td onclick="event.stopPropagation();">
            <select class="form-select form-select-sm customer-status-select-badge ${(c.status || 'Active').toLowerCase() === 'inactive' ? 'customer-status-inactive' : 'customer-status-active'}" onchange="quickUpdateCustomerStatus(${c.customer_id}, this.value, this)" onclick="event.stopPropagation();">
                <option value="Active" ${(c.status || 'Active') === 'Active' ? 'selected' : ''}>Active</option>
                <option value="Inactive" ${(c.status || 'Active') === 'Inactive' ? 'selected' : ''}>Inactive</option>
            </select>
        </td>
        <td class="text-end text-nowrap" onclick="event.stopPropagation();">
            <div class="d-flex align-items-center justify-content-end gap-1.5">
                <button type="button" class="btn btn-sm btn-outline-danger" data-id="${c.customer_id}" data-name="${escapeHtml(c.name)}" onclick="event.stopPropagation(); event.preventDefault(); openDeleteCustomerModal(this);" title="Delete Customer">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </td>
    `;

    tbody.insertBefore(tr, tbody.firstChild);

    // Hide empty state if visible
    const emptyState = document.getElementById('customerEmptyState');
    if (emptyState) emptyState.style.display = 'none';

    // Update count display & KPI total
    const rows = document.querySelectorAll('.customer-data-row');
    const countDisplay = document.getElementById('recordCountDisplay');
    if (countDisplay) {
        countDisplay.textContent = `Showing ${rows.length} customer(s)`;
    }
    const kpiTotal = document.getElementById('kpiTotalCustomers');
    if (kpiTotal) {
        kpiTotal.textContent = rows.length.toLocaleString();
    }
}

function toggleCustomerStatus(customerId, currentStatus) {
    const badge = document.getElementById(`custStatusBadge_${customerId}`) || document.getElementById(`custStatusBadge_mob_${customerId}`);
    let targetStatus = 'Active';
    if (badge) {
        const textSpan = badge.querySelector('span');
        const currentText = textSpan ? textSpan.textContent.trim() : currentStatus;
        targetStatus = (currentText.toLowerCase() === 'active') ? 'Inactive' : 'Active';
    } else {
        targetStatus = (currentStatus.toLowerCase() === 'active') ? 'Inactive' : 'Active';
    }
    quickUpdateCustomerStatus(customerId, targetStatus);
}

function quickUpdateCustomerStatus(customerId, newStatus, element) {
    if (!customerId || !newStatus) return;

    const isInactive = newStatus.toLowerCase() === 'inactive';

    // Update all badge buttons for this customer with inline style overrides & pulse animation
    const badgeBtns = document.querySelectorAll(`#custStatusBadge_${customerId}, #custStatusBadge_mob_${customerId}, .cust-status-badge-btn-${customerId}`);
    badgeBtns.forEach(btn => {
        btn.className = `status-pill-btn ${isInactive ? 'status-pill-inactive' : 'status-pill-active'} border-0 text-nowrap status-badge-pulse`;
        if (isInactive) {
            btn.style.cssText = 'color: #334155 !important; background-color: #F1F5F9 !important; border: 1px solid #CBD5E1 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;';
        } else {
            btn.style.cssText = 'color: #047857 !important; background-color: #ECFDF5 !important; border: 1px solid #6EE7B7 !important; border-radius: 50px !important; padding: 4px 14px !important; font-weight: 700 !important; font-size: 0.78rem !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; outline: none !important; cursor: pointer !important;';
        }
        btn.innerHTML = `<i class="fa-solid ${isInactive ? 'fa-circle-xmark text-secondary' : 'fa-circle-check text-success'} me-1" style="font-size: 0.85rem !important;"></i><span style="color: inherit !important; font-weight: 700 !important; border: none !important; outline: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; display: inline !important;">${newStatus}</span>`;
        
        btn.setAttribute('onclick', `event.stopPropagation(); toggleCustomerStatus(${customerId}, '${newStatus}')`);

        setTimeout(() => btn.classList.remove('status-badge-pulse'), 400);
    });

    // Also support select elements if present
    if (element && element.tagName === 'SELECT') {
        element.className = 'form-select form-select-sm customer-status-select-badge ' + (isInactive ? 'customer-status-inactive' : 'customer-status-active');
        element.value = newStatus;
    }

    // Update row & card data-status attributes
    const row = document.querySelector(`.customer-data-row[data-id="${customerId}"]`);
    if (row) row.setAttribute('data-status', newStatus.toLowerCase());

    const card = document.querySelector(`.customer-mobile-card[data-id="${customerId}"]`);
    if (card) card.setAttribute('data-status', newStatus.toLowerCase());

    const formData = new FormData();
    formData.append('action', 'change_customer_status');
    formData.append('customer_id', customerId);
    formData.append('status', newStatus);
    formData.append('is_ajax', '1');

    fetch('/hotel_website/admin/customers.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update KPI stats if returned
            if (data.stats) {
                const kpiActive = document.getElementById('kpiActiveCustomers');
                if (kpiActive && data.stats.activeCustomers !== undefined) {
                    kpiActive.textContent = data.stats.activeCustomers.toLocaleString();
                }
                const kpiInactive = document.getElementById('kpiInactiveCustomers');
                if (kpiInactive && data.stats.inactiveCustomers !== undefined) {
                    kpiInactive.textContent = data.stats.inactiveCustomers.toLocaleString();
                }
            }

            // Sync other select elements for the same customer (desktop & mobile)
            document.querySelectorAll('.customer-status-select-badge').forEach(sel => {
                const onchangeAttr = sel.getAttribute('onchange') || '';
                if (onchangeAttr.includes(`(${customerId},`)) {
                    sel.value = newStatus;
                    sel.className = 'form-select form-select-sm customer-status-select-badge ' +
                        (newStatus.toLowerCase() === 'inactive' ? 'customer-status-inactive' : 'customer-status-active');
                }
            });

            if (typeof showToast === 'function') {
                showToast(data.message || 'Status updated successfully', 'success');
            }
        } else {
            // Revert back on error
            if (selectElem) {
                selectElem.value = originalValue;
                selectElem.className = 'form-select form-select-sm customer-status-select-badge ' +
                    (originalValue.toLowerCase() === 'inactive' ? 'customer-status-inactive' : 'customer-status-active');
            }
            if (typeof showToast === 'function') {
                showToast(data.message || 'Failed to update status', 'danger');
            } else {
                alert(data.message || 'Failed to update status');
            }
        }
    })
    .catch(err => {
        console.error('Error updating status:', err);
        if (selectElem) {
            selectElem.value = originalValue;
            selectElem.className = 'form-select form-select-sm customer-status-select-badge ' +
                (originalValue.toLowerCase() === 'inactive' ? 'customer-status-inactive' : 'customer-status-active');
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
