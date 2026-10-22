/* =========================================================
   HOTELEASE - ROOMS MANAGEMENT JAVASCRIPT MODULE
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initViewMode();
    initRoomFiltersAndSearch();
    initBulkSelection();
    initKeyboardShortcuts();
});

function showRoomModal(modalElem) {
    if (!modalElem) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElem).show();
    } else {
        modalElem.classList.add('show', 'room-modal-open');
        modalElem.style.display = 'block';
        modalElem.removeAttribute('aria-hidden');
        modalElem.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open', 'room-modal-lock');
        if (!document.querySelector('.room-modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show room-modal-backdrop';
            document.body.appendChild(backdrop);
        }
    }
}

function showRoomOffcanvas(drawerElem) {
    if (!drawerElem) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        bootstrap.Offcanvas.getOrCreateInstance(drawerElem).show();
        return;
    }
    drawerElem.classList.add('show');
    drawerElem.style.visibility = 'visible';
    document.body.classList.add('offcanvas-open');
    if (!document.querySelector('.room-offcanvas-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'offcanvas-backdrop fade show room-offcanvas-backdrop';
        backdrop.onclick = () => hideRoomOffcanvas(drawerElem);
        document.body.appendChild(backdrop);
    }
}

function hideRoomOffcanvas(drawerElem) {
    if (!drawerElem) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        const instance = bootstrap.Offcanvas.getInstance(drawerElem);
        if (instance) instance.hide();
    }
    drawerElem.classList.remove('show', 'active');
    drawerElem.style.visibility = '';
    document.body.classList.remove('offcanvas-open');
    document.querySelectorAll('.room-offcanvas-backdrop').forEach(el => el.remove());
}

// 1. VIEW MODE SWITCHER (List/Table View Default)
function setViewMode(mode) {
    const gridView = document.getElementById('roomsGridView');
    const listView = document.getElementById('roomsListView');
    const btnGrid = document.getElementById('btnViewGrid');
    const btnList = document.getElementById('btnViewList');

    if (gridView) gridView.style.display = 'none';
    if (listView) listView.style.display = 'block';
    if (btnGrid) btnGrid.classList.remove('active');
    if (btnList) btnList.classList.add('active');
    localStorage.setItem('hotel_ease_room_view', 'list');
}

function initViewMode() {
    setViewMode('list');
}

// 2. SEARCH, FILTER & SORTING SYSTEM
let currentActiveKpi = 'all';

function filterByStatusPill(status, pillElem) {
    currentActiveKpi = status;

    document.querySelectorAll('.room-status-pill').forEach(btn => {
        btn.classList.remove('active');
        btn.style.backgroundColor = '#F1F5F9';
        btn.style.color = '#334155';
    });

    if (pillElem) {
        pillElem.classList.add('active');
        pillElem.style.backgroundColor = '#2563EB';
        pillElem.style.color = '#ffffff';
    }

    const statusFilter = document.getElementById('filterStatus');
    if (statusFilter) {
        statusFilter.value = status;
    }

    applyRoomFilters();
}

function filterByStatusKpi(status, kpiCardElem) {
    currentActiveKpi = status;

    // Toggle Active Outline on KPI Cards
    document.querySelectorAll('.kpi-room-card').forEach(card => card.classList.remove('active-kpi'));
    if (kpiCardElem) {
        kpiCardElem.classList.add('active-kpi');
    }

    const statusFilter = document.getElementById('filterStatus');
    if (statusFilter) {
        statusFilter.value = status;
    }

    applyRoomFilters();
}

function applyRoomFilters() {
    const searchInput = document.getElementById('roomSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const statusFilter = document.getElementById('filterStatus');
    const typeFilter = document.getElementById('filterRoomType');
    const floorFilter = document.getElementById('filterFloor');
    const sortSelect = document.getElementById('sortRooms');

    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const statusVal = (currentActiveKpi || (statusFilter ? statusFilter.value : 'all')).toLowerCase();

    // Sync Pills UI with status value
    document.querySelectorAll('.room-status-pill').forEach(btn => {
        const pillStatus = (btn.getAttribute('data-status') || '').toLowerCase();
        if (pillStatus === statusVal) {
            btn.classList.add('active');
            btn.style.backgroundColor = '#2563EB';
            btn.style.color = '#ffffff';
        } else {
            btn.classList.remove('active');
            btn.style.backgroundColor = '#F1F5F9';
            btn.style.color = '#334155';
        }
    });
    const typeVal = (typeFilter ? typeFilter.value : 'all').toLowerCase();
    const floorVal = (floorFilter ? floorFilter.value : 'all').toLowerCase();
    const sortVal = sortSelect ? sortSelect.value : 'room_asc';

    if (searchClearBtn) {
        searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
    }

    const listRows = Array.from(document.querySelectorAll('.room-table-row'));
    const gridCards = Array.from(document.querySelectorAll('.room-grid-card-wrapper'));
    const filterItems = gridCards.length > 0 ? gridCards : listRows;
    const scopedCounts = {
        all: 0,
        available: 0,
        occupied: 0,
        reserved: 0,
        cleaning: 0,
        maintenance: 0
    };

    filterItems.forEach(item => {
        const itemQuery = [
            item.getAttribute('data-room-no') || '',
            item.getAttribute('data-type-name') || '',
            item.getAttribute('data-status') || '',
            item.getAttribute('data-floor') || '',
            item.getAttribute('data-amenities') || ''
        ].join(' ').toLowerCase();
        const itemTypeId = (item.getAttribute('data-type-id') || '').toLowerCase();
        const itemTypeName = (item.getAttribute('data-type-name') || '').toLowerCase();
        const itemFloor = (item.getAttribute('data-floor') || '').toLowerCase();
        const itemStatus = (item.getAttribute('data-status') || '').toLowerCase();
        const matchesScope = (!query || itemQuery.includes(query))
            && (typeVal === 'all' || itemTypeId === typeVal || itemTypeName.includes(typeVal))
            && (floorVal === 'all' || itemFloor.includes(floorVal));

        if (matchesScope) {
            scopedCounts.all++;
            if (Object.prototype.hasOwnProperty.call(scopedCounts, itemStatus)) {
                scopedCounts[itemStatus]++;
            }
        }
    });

    document.querySelectorAll('.room-status-pill').forEach(btn => {
        const status = (btn.getAttribute('data-status') || 'all').toLowerCase();
        const count = scopedCounts[status] || 0;
        const countElement = btn.querySelector('.room-status-count');
        if (countElement) countElement.textContent = count;
    });

    let visibleCount = 0;

    filterItems.forEach((cardWrap, idx) => {
        const row = gridCards.length > 0 ? listRows[idx] : cardWrap;
        
        const roomNo = (cardWrap.getAttribute('data-room-no') || '').toLowerCase();
        const typeName = (cardWrap.getAttribute('data-type-name') || '').toLowerCase();
        const typeId = (cardWrap.getAttribute('data-type-id') || '').toLowerCase();
        const floor = (cardWrap.getAttribute('data-floor') || '').toLowerCase();
        const status = (cardWrap.getAttribute('data-status') || '').toLowerCase();
        const price = parseFloat(cardWrap.getAttribute('data-price') || '0');
        const capacity = parseInt(cardWrap.getAttribute('data-capacity') || '0', 10);
        const amenities = (cardWrap.getAttribute('data-amenities') || '').toLowerCase();

        // Search Match
        const matchesQuery = !query || 
            roomNo.includes(query) || 
            typeName.includes(query) || 
            status.includes(query) || 
            floor.includes(query) || 
            amenities.includes(query);

        // Status Match
        let matchesStatus = true;
        if (statusVal !== 'all') {
            matchesStatus = (status === statusVal);
        }

        // Type Match
        let matchesType = true;
        if (typeVal !== 'all') {
            matchesType = (typeId === typeVal || typeName.includes(typeVal));
        }

        // Floor Match
        let matchesFloor = true;
        if (floorVal !== 'all') {
            matchesFloor = floor.includes(floorVal);
        }

        const isVisible = matchesQuery && matchesStatus && matchesType && matchesFloor;

        if (gridCards.length > 0) {
            cardWrap.style.display = isVisible ? 'block' : 'none';
            if (row) row.style.display = isVisible ? '' : 'none';
        } else {
            row.style.display = isVisible ? '' : 'none';
        }

        if (isVisible) visibleCount++;
    });

    // SORTING LOGIC
    const gridContainer = document.getElementById('roomsGridView');
    const tableBody = document.getElementById('roomsTableBody');

    if (tableBody) {
        const visibleRows = listRows.filter(r => r.style.display !== 'none');
        
        visibleRows.sort((a, b) => {
            const pA = parseFloat(a.getAttribute('data-price') || '0');
            const pB = parseFloat(b.getAttribute('data-price') || '0');
            const rA = parseInt(a.getAttribute('data-room-no') || '0', 10);
            const rB = parseInt(b.getAttribute('data-room-no') || '0', 10);
            const capA = parseInt(a.getAttribute('data-capacity') || '0', 10);
            const capB = parseInt(b.getAttribute('data-capacity') || '0', 10);
            const idA = parseInt(a.getAttribute('data-id') || '0', 10);
            const idB = parseInt(b.getAttribute('data-id') || '0', 10);

            if (sortVal === 'price_asc') return pA - pB;
            if (sortVal === 'price_desc') return pB - pA;
            if (sortVal === 'cap_desc') return capB - capA;
            if (sortVal === 'newest') return idB - idA;
            if (sortVal === 'oldest') return idA - idB;
            return rA - rB; // room_asc
        });

        visibleRows.forEach((r) => {
            tableBody.appendChild(r);
        });
    }

    // Display Count & Empty State
    const countDisplay = document.getElementById('roomRecordCountDisplay');
    if (countDisplay) {
        countDisplay.textContent = `Showing ${visibleCount} room(s)`;
    }

    const emptyStateGrid = document.getElementById('roomsEmptyStateGrid');
    const emptyStateList = document.getElementById('roomsEmptyStateList');

    if (emptyStateGrid) emptyStateGrid.style.display = visibleCount === 0 ? 'block' : 'none';
    if (emptyStateList) emptyStateList.style.display = visibleCount === 0 ? 'block' : 'none';
}

function initRoomFiltersAndSearch() {
    const searchInput = document.getElementById('roomSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const statusFilter = document.getElementById('filterStatus');
    const typeFilter = document.getElementById('filterRoomType');
    const floorFilter = document.getElementById('filterFloor');
    const sortSelect = document.getElementById('sortRooms');

    if (searchInput) searchInput.addEventListener('input', applyRoomFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyRoomFilters);
    if (typeFilter) typeFilter.addEventListener('change', applyRoomFilters);
    if (floorFilter) floorFilter.addEventListener('change', applyRoomFilters);
    if (sortSelect) sortSelect.addEventListener('change', applyRoomFilters);

    document.addEventListener('click', function (event) {
        const statusSelect = event.target.closest('.status-select-badge');
        if (statusSelect) event.stopPropagation();
    });

    document.addEventListener('change', function (event) {
        const statusSelect = event.target.closest('.status-select-badge');
        if (!statusSelect) return;

        const roomId = statusSelect.getAttribute('data-room-id');
        if (roomId) quickUpdateRoomStatus(roomId, statusSelect.value);
    });

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            applyRoomFilters();
            searchInput.focus();
        });
    }
}

function resetRoomFilters() {
    const searchInput = document.getElementById('roomSearchInput');
    const statusFilter = document.getElementById('filterStatus');
    const typeFilter = document.getElementById('filterRoomType');
    const floorFilter = document.getElementById('filterFloor');
    const sortSelect = document.getElementById('sortRooms');

    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = 'all';
    if (typeFilter) typeFilter.value = 'all';
    if (floorFilter) floorFilter.value = 'all';
    if (sortSelect) sortSelect.value = 'room_asc';

    // Clear KPI active state
    document.querySelectorAll('.kpi-room-card').forEach(c => c.classList.remove('active-kpi'));
    const allKpiCard = document.querySelector('.kpi-room-card.kpi-total-card');
    if (allKpiCard) allKpiCard.classList.add('active-kpi');

    applyRoomFilters();
}

// Helper: Robust room data parser
function parseRoomData(r) {
    if (!r) return null;
    if (typeof r === 'object' && r.room_id) return r;
    if (typeof r === 'string') {
        try { return JSON.parse(r); } catch (e) { return null; }
    }
    if (typeof r === 'object' && r.getAttribute) {
        const raw = r.getAttribute('data-room') || r.closest('[data-room]')?.getAttribute('data-room');
        if (raw) {
            try { return JSON.parse(raw); } catch (e) {}
        }
        const id = r.getAttribute('data-id') || r.closest('[data-id]')?.getAttribute('data-id');
        if (id) {
            return {
                room_id: parseInt(id, 10),
                room_no: r.getAttribute('data-room-no') || r.closest('[data-room-no]')?.getAttribute('data-room-no') || id,
                type_name: r.getAttribute('data-type-name') || r.closest('[data-type-name]')?.getAttribute('data-type-name') || 'Standard Room',
                floor: r.getAttribute('data-floor') || r.closest('[data-floor]')?.getAttribute('data-floor') || 'Floor 1',
                price: parseFloat(r.getAttribute('data-price') || r.closest('[data-price]')?.getAttribute('data-price') || '0'),
                capacity: parseInt(r.getAttribute('data-capacity') || r.closest('[data-capacity]')?.getAttribute('data-capacity') || '2', 10),
                status: r.getAttribute('data-status') || r.closest('[data-status]')?.getAttribute('data-status') || 'Available',
                bed_type: r.getAttribute('data-bed-type') || r.closest('[data-bed-type]')?.getAttribute('data-bed-type') || 'King Bed',
                amenities: r.getAttribute('data-amenities') || r.closest('[data-amenities]')?.getAttribute('data-amenities') || ''
            };
        }
    }
    return null;
}

// 3. OPEN ROOM DETAILS SIDE DRAWER
function openRoomDrawer(r, event) {
    if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

    r = parseRoomData(r);
    if (!r) return;

        // Header & Image
        const elRoomNo = document.getElementById('drawer_room_no');
        if (elRoomNo) elRoomNo.textContent = 'Room ' + (r.room_no || '');
        const elRoomType = document.getElementById('drawer_room_type');
        if (elRoomType) elRoomType.textContent = r.type_name || 'Standard Room';
        const elPrice = document.getElementById('drawer_price');
        if (elPrice) elPrice.textContent = '₹' + parseFloat(r.price || 0).toLocaleString('en-IN') + ' / night';
        
        const heroImg = document.getElementById('drawer_hero_img');
        if (heroImg) {
            const imgPath = r.image || 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1000&q=80';
            heroImg.src = imgPath;
            heroImg.onclick = function() { showImageLightbox(imgPath, 'Room ' + r.room_no); };
        }

        // Specifications Grid
        const elSpecId = document.getElementById('drawer_spec_id');
        if (elSpecId) elSpecId.textContent = '#RM-00' + r.room_id;
        const elFloor = document.getElementById('drawer_spec_floor');
        if (elFloor) elFloor.textContent = r.floor || ('Floor ' + (r.room_no ? r.room_no.charAt(0) : '1'));
        const elBed = document.getElementById('drawer_spec_bed');
        if (elBed) elBed.textContent = r.bed_type || 'King Bed';
        const elCapacity = document.getElementById('drawer_spec_capacity');
        if (elCapacity) elCapacity.textContent = (r.capacity || 2) + ' Guests';
        
        // Status Badge
        const statusContainer = document.getElementById('drawer_status_badge');
        if (statusContainer) {
            let stClass = 'status-available';
            if (r.status === 'Occupied') stClass = 'status-occupied';
            else if (r.status === 'Reserved') stClass = 'status-reserved';
            else if (r.status === 'Maintenance') stClass = 'status-maintenance';
            else if (r.status === 'Cleaning') stClass = 'status-cleaning';
            
            statusContainer.className = 'badge-status ' + stClass;
            statusContainer.innerHTML = `<i class="fa-solid fa-circle" style="font-size: 6px;"></i> ${r.status || 'Available'}`;
        }

        // Description
        const elDesc = document.getElementById('drawer_description');
        if (elDesc) elDesc.textContent = r.description || 'No detailed room description recorded.';

        // Amenities Pills
        const amenitiesContainer = document.getElementById('drawer_amenities_list');
        if (amenitiesContainer) {
            amenitiesContainer.innerHTML = '';
            const list = (r.amenities || 'Wi-Fi, Smart TV, Air Conditioning').split(',');
            list.forEach(am => {
                const item = am.trim();
                if (item) {
                    amenitiesContainer.innerHTML += `
                        <span class="badge bg-light text-navy border px-3 py-1.5 rounded-pill font-semibold small">
                            <i class="fa-solid fa-check text-success me-1.5"></i>${item}
                        </span>
                    `;
                }
            });
        }

        // Current Active Booking Information
        const bookingCard = document.getElementById('drawer_booking_card');
        if (bookingCard) {
            const activeBooking = r.active_booking;

            if (activeBooking) {
                bookingCard.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary px-2.5 py-1 rounded-pill font-semibold">#HE-${activeBooking.booking_id}</span>
                        <span class="badge bg-success bg-opacity-10 text-success font-semibold px-2.5 py-1 rounded-pill">${activeBooking.booking_status}</span>
                    </div>
                    <div class="fw-bold text-navy fs-6 mb-1"><i class="fa-solid fa-user me-2 text-primary"></i>${activeBooking.customer_name || 'Guest User'}</div>
                    <div class="small text-muted mb-2"><i class="fa-solid fa-calendar-days me-1.5 text-muted"></i>${activeBooking.check_in} &rarr; ${activeBooking.check_out}</div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top extra-small text-muted">
                        <span>${activeBooking.adults || 1} Adult(s), ${activeBooking.children || 0} Child</span>
                        <span class="fw-bold text-navy fs-6">₹${parseFloat(activeBooking.total_amount || 0).toLocaleString('en-IN')}</span>
                    </div>
                `;
            } else {
                bookingCard.innerHTML = `
                    <div class="text-center py-2 text-muted small">
                        <i class="fa-solid fa-calendar-xmark fs-4 mb-1 text-light d-block"></i>
                        No active reservation recorded for Room ${r.room_no}.
                    </div>
                `;
            }
        }

        // Attach Action Buttons inside Drawer
        const elEditBtn = document.getElementById('drawerEditBtn');
        if (elEditBtn) {
            elEditBtn.onclick = function() {
                hideRoomOffcanvas(document.getElementById('roomDetailsDrawer'));
                openEditRoomModal(r);
            };
        }
        const elBookingsBtn = document.getElementById('drawerBookingsBtn');
        if (elBookingsBtn) {
            elBookingsBtn.onclick = function() {
                hideRoomOffcanvas(document.getElementById('roomDetailsDrawer'));
                openRoomBookings(r);
            };
        }
        const elStatusBtn = document.getElementById('drawerStatusBtn');
        if (elStatusBtn) {
            elStatusBtn.onclick = function() {
                hideRoomOffcanvas(document.getElementById('roomDetailsDrawer'));
                openChangeStatusModal(r);
            };
        }
        const elDeleteBtn = document.getElementById('drawerDeleteBtn');
        if (elDeleteBtn) {
            elDeleteBtn.onclick = function() {
                hideRoomOffcanvas(document.getElementById('roomDetailsDrawer'));
                confirmDeleteRoom(r);
            };
        }

        // Open Drawer
        const drawerElem = document.getElementById('roomDetailsDrawer');
        showRoomOffcanvas(drawerElem);

}

// 4. IMAGE LIGHTBOX MODAL & ZOOM CONTROLS
let currentRoomPhotoZoom = 1.0;

function zoomLightboxModal(delta) {
    const img = document.getElementById('lightboxImg');
    const zoomText = document.getElementById('lightboxZoomLevelText');
    if (!img) return;

    currentRoomPhotoZoom += delta;
    if (currentRoomPhotoZoom < 0.5) currentRoomPhotoZoom = 0.5;
    if (currentRoomPhotoZoom > 3.5) currentRoomPhotoZoom = 3.5;

    img.style.transform = `scale(${currentRoomPhotoZoom})`;
    img.style.transition = 'transform 0.2s ease';

    if (zoomText) {
        zoomText.textContent = Math.round(currentRoomPhotoZoom * 100) + '%';
    }
}

function resetLightboxZoom() {
    currentRoomPhotoZoom = 1.0;
    const img = document.getElementById('lightboxImg');
    const zoomText = document.getElementById('lightboxZoomLevelText');
    if (img) {
        img.style.transform = 'scale(1)';
    }
    if (zoomText) {
        zoomText.textContent = '100%';
    }
}

function showImageLightbox(src, title, event) {
    if (event) event.stopPropagation();

    const modalElem = document.getElementById('imageLightboxModal');
    if (!modalElem) return;

    const editModal = document.getElementById('editRoomModal');
    if (editModal && editModal !== modalElem && editModal.classList.contains('show')) {
        closeRoomModal(editModal);
    }

    resetLightboxZoom();

    document.getElementById('lightboxTitle').textContent = title || 'Room Image Preview';
    const img = document.getElementById('lightboxImg');
    img.src = src;
    img.onerror = function () {
        img.alt = 'Room image could not be loaded';
    };

    // Attach Mouse Wheel Zoom
    img.onwheel = function(e) {
        e.preventDefault();
        if (e.deltaY < 0) zoomLightboxModal(0.15);
        else zoomLightboxModal(-0.15);
    };

    showRoomModal(modalElem);
}

// 5. ADD ROOM MODAL
function openAddRoomModal() {
    const modalElem = document.getElementById('addRoomModal');
    if (!modalElem) return;

    const form = modalElem.querySelector('form');
    if (form) form.reset();

    const preview = document.getElementById('addRoomPhotoPreview');
    const fallback = document.getElementById('addRoomPhotoFallback');
    if (preview) preview.classList.add('d-none');
    if (fallback) fallback.classList.remove('d-none');

    showRoomModal(modalElem);

    setTimeout(() => {
        const input = modalElem.querySelector('input[name="room_no"]');
        if (input) input.focus();
    }, 300);
}

let currentEditRoomImages = [];

function normalizeRoomImageUrl(url) {
    return String(url || '').replace(/&amp;/g, '&');
}

function renderEditRoomExistingGallery() {
    const gallery = document.getElementById('editRoomExistingPhotosGallery');
    const notice = document.getElementById('editRoomNoPhotosNotice');
    const existingImgElem = document.getElementById('edit_existing_image');

    if (existingImgElem) {
        existingImgElem.value = currentEditRoomImages.join(',');
    }

    if (!gallery) return;

    if (currentEditRoomImages.length === 0) {
        gallery.innerHTML = '';
        if (notice) notice.style.display = 'block';
        return;
    }

    if (notice) notice.style.display = 'none';

    let html = '';
    currentEditRoomImages.forEach((rawImgUrl, index) => {
        const imgUrl = normalizeRoomImageUrl(rawImgUrl);
        const isPrimary = (index === 0);
        const safeImgUrl = imgUrl.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        html += `
            <div class="col-6 col-sm-4 col-md-3">
                <div class="card h-100 border rounded-3 p-1.5 bg-white position-relative shadow-2xs">
                    <div class="position-relative overflow-hidden rounded-2" style="height: 85px;">
                        <img src="${safeImgUrl}" class="w-100 h-100" style="object-fit: cover;" alt="Room photo">
                        ${isPrimary ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1 extra-small font-semibold shadow-2xs">Primary</span>' : ''}
                        
                        <div class="position-absolute top-0 end-0 m-1 d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-dark bg-opacity-75 p-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 26px; height: 26px;" data-room-photo="${safeImgUrl}" onclick="showImageLightbox(this.getAttribute('data-room-photo'), 'Room Photo Preview', event)" title="Preview Full Image">
                                <i class="fa-solid fa-eye" style="font-size: 0.7rem;"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger p-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 26px; height: 26px;" onclick="removeEditRoomExistingPhoto(${index})" title="Remove Photo">
                                <i class="fa-solid fa-xmark" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    gallery.innerHTML = html;
}

function removeEditRoomExistingPhoto(index) {
    if (index >= 0 && index < currentEditRoomImages.length) {
        currentEditRoomImages.splice(index, 1);
        renderEditRoomExistingGallery();
    }
}

function renderEditRoomNewPhotoPreviews(inputElem) {
    const gallery = document.getElementById('editRoomNewPhotosGallery');
    if (!gallery) return;

    gallery.innerHTML = '';
    if (!inputElem.files || inputElem.files.length === 0) {
        gallery.style.display = 'none';
        return;
    }

    gallery.style.display = 'flex';
    Array.from(inputElem.files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-6 col-sm-4 col-md-3';
            col.innerHTML = `
                <div class="card h-100 border border-info rounded-3 p-1.5 bg-white position-relative shadow-2xs">
                    <div class="position-relative overflow-hidden rounded-2" style="height: 85px;">
                        <img src="${e.target.result}" class="w-100 h-100" style="object-fit: cover;" alt="New photo preview">
                        <span class="badge bg-info text-white position-absolute top-0 start-0 m-1 extra-small font-semibold shadow-2xs">New Upload</span>
                    </div>
                    <div class="extra-small text-muted text-truncate mt-1 text-center" title="${file.name}">${file.name}</div>
                </div>
            `;
            gallery.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

function renderAddRoomNewPhotoPreviews(inputElem) {
    const gallery = document.getElementById('addRoomPhotosPreviewGallery');
    if (!gallery) return;

    gallery.innerHTML = '';
    if (!inputElem.files || inputElem.files.length === 0) {
        gallery.style.display = 'none';
        return;
    }

    gallery.style.display = 'flex';
    Array.from(inputElem.files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-6 col-sm-4 col-md-3';
            col.innerHTML = `
                <div class="card h-100 border border-primary rounded-3 p-1.5 bg-white position-relative shadow-2xs">
                    <div class="position-relative overflow-hidden rounded-2" style="height: 85px;">
                        <img src="${e.target.result}" class="w-100 h-100" style="object-fit: cover;" alt="New photo preview">
                        <span class="badge bg-primary position-absolute top-0 start-0 m-1 extra-small font-semibold shadow-2xs">New Upload</span>
                    </div>
                    <div class="extra-small text-muted text-truncate mt-1 text-center" title="${file.name}">${file.name}</div>
                </div>
            `;
            gallery.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

// 6. EDIT ROOM MODAL
function openEditRoomModal(r, event) {
    if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

    r = parseRoomData(r);
    if (!r) return;

    const idElem = document.getElementById('edit_room_id');
    if (idElem) idElem.value = r.room_id;

    const roomNoElem = document.getElementById('edit_room_no');
    if (roomNoElem) roomNoElem.value = r.room_no || '';

    const typeIdElem = document.getElementById('edit_room_type_id');
    if (typeIdElem) typeIdElem.value = r.room_type_id || 1;

    const priceElem = document.getElementById('edit_price');
    if (priceElem) priceElem.value = r.price || 0;

    const capElem = document.getElementById('edit_capacity');
    if (capElem) capElem.value = r.capacity || 2;

    const floorElem = document.getElementById('edit_floor');
    if (floorElem) floorElem.value = r.floor || 'Floor 1';

    const bedElem = document.getElementById('edit_bed_type');
    if (bedElem) bedElem.value = r.bed_type || 'King Bed';

    const statusElem = document.getElementById('edit_status');
    if (statusElem) statusElem.value = r.status || 'Available';

    const amElem = document.getElementById('edit_amenities');
    if (amElem) amElem.value = r.amenities || '';

    const descElem = document.getElementById('edit_description');
    if (descElem) descElem.value = r.description || '';

    // Reset Photo Inputs & Render Saved Gallery
    const photoInput = document.getElementById('editRoomPhotoInput');
    if (photoInput) photoInput.value = '';

    const newPhotoGallery = document.getElementById('editRoomNewPhotosGallery');
    if (newPhotoGallery) {
        newPhotoGallery.innerHTML = '';
        newPhotoGallery.style.display = 'none';
    }

    const rawImgStr = r.image || '';
    currentEditRoomImages = rawImgStr.split(',').map(s => normalizeRoomImageUrl(s.trim())).filter(s => s.length > 0);
    renderEditRoomExistingGallery();

    const modalElem = document.getElementById('editRoomModal');
    if (modalElem) {
        showRoomModal(modalElem);
    }
}

// 7. DUPLICATE ROOM
function duplicateRoom(r, event) {
    if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

    r = parseRoomData(r);
    if (!r) return;

    openAddRoomModal();

    setTimeout(() => {
        const addModal = document.getElementById('addRoomModal');
        if (!addModal) return;

        const nextRoomNo = parseInt(r.room_no || '100', 10) + 1;
        const roomNoInp = addModal.querySelector('input[name="room_no"]');
        if (roomNoInp) roomNoInp.value = nextRoomNo;

        const typeSel = addModal.querySelector('select[name="room_type_id"]');
        if (typeSel) typeSel.value = r.room_type_id || 1;

        const priceInp = addModal.querySelector('input[name="price"]');
        if (priceInp) priceInp.value = r.price || 0;

        const capInp = addModal.querySelector('input[name="capacity"]');
        if (capInp) capInp.value = r.capacity || 2;

        const floorInp = addModal.querySelector('input[name="floor"]');
        if (floorInp) floorInp.value = r.floor || 'Floor 1';

        const bedInp = addModal.querySelector('input[name="bed_type"]');
        if (bedInp) bedInp.value = r.bed_type || 'King Bed';

        const statusSel = addModal.querySelector('select[name="status"]');
        if (statusSel) statusSel.value = 'Available';

        const amInp = addModal.querySelector('input[name="amenities"]');
        if (amInp) amInp.value = r.amenities || '';

        const descText = addModal.querySelector('textarea[name="description"]');
        if (descText) descText.value = r.description || '';

        showToast(`Duplicating Room ${r.room_no} into new Room ${nextRoomNo}`, 'info');
    }, 350);
}

// 8. CHANGE ROOM STATUS MODAL
function openChangeStatusModal(r, event) {
    if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

    r = parseRoomData(r);
    if (!r) return;

    const idInput = document.getElementById('status_change_room_id');
    if (idInput) idInput.value = r.room_id;

    const noDisplay = document.getElementById('status_change_room_no');
    if (noDisplay) noDisplay.textContent = 'Room ' + r.room_no;

    const selVal = document.getElementById('status_select_val');
    if (selVal) selVal.value = r.status || 'Available';

    const modalElem = document.getElementById('changeStatusModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            showRoomModal(modalElem);
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

// 9. DELETE ROOM CONFIRMATION
function confirmDeleteRoom(r, event) {
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }

    r = parseRoomData(r);
    if (!r || !r.room_id) return;

    const roomId = r.room_id;
    const roomNo = r.room_no || ('#' + roomId);
    const typeName = r.type_name || '';
    const hasBookings = (r.all_bookings && r.all_bookings.length > 0) || Boolean(r.active_booking);

    const idInput = document.getElementById('delete_room_id');
    const displayElem = document.getElementById('delete_room_no_display');

    if (idInput) idInput.value = roomId;
    if (displayElem) displayElem.textContent = `Room ${roomNo}${typeName ? ' (' + typeName + ')' : ''}`;

    const warningBox = document.getElementById('delete_booking_warning');
    if (warningBox) {
        if (hasBookings) {
            warningBox.style.display = 'block';
            warningBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Note: This room has reservation booking records. Deleting it will clear related inventory references.`;
        } else {
            warningBox.style.display = 'none';
        }
    }

    const modalElem = document.getElementById('deleteRoomModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem);
            bsModal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

// 10. VIEW ROOM BOOKINGS MODAL
function openRoomBookings(r, event) {
    if (event) event.stopPropagation();

    if (typeof r === 'string') {
        try { r = JSON.parse(r); } catch (e) { return; }
    } else if (r && r.getAttribute) {
        const raw = r.getAttribute('data-room') || r.closest('[data-room]')?.getAttribute('data-room');
        try { r = JSON.parse(raw); } catch (e) { return; }
    }

    document.getElementById('roomBookingsTitle').textContent = `Bookings History for Room ${r.room_no}`;
    const listContainer = document.getElementById('roomBookingsList');

    const bookings = r.all_bookings || [];
    if (bookings.length > 0) {
        let html = '';
        bookings.forEach(b => {
            let stBadge = '<span class="badge bg-success bg-opacity-10 text-success font-semibold px-2.5 py-1 rounded-pill">Checked-Out</span>';
            if (b.booking_status === 'Pending') stBadge = '<span class="badge bg-warning bg-opacity-10 text-warning font-semibold px-2.5 py-1 rounded-pill">Pending</span>';
            else if (b.booking_status === 'Confirmed') stBadge = '<span class="badge bg-primary bg-opacity-10 text-primary font-semibold px-2.5 py-1 rounded-pill">Confirmed</span>';
            else if (b.booking_status === 'Cancelled') stBadge = '<span class="badge bg-danger bg-opacity-10 text-danger font-semibold px-2.5 py-1 rounded-pill">Cancelled</span>';

            html += `
                <div class="p-3 border rounded-3 bg-white mb-2 shadow-2xs">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <span class="fw-bold text-primary font-mono">#HE-${b.booking_id}</span>
                        ${stBadge}
                    </div>
                    <div class="fw-bold text-navy small mb-1"><i class="fa-solid fa-user text-muted me-1.5"></i>${b.customer_name || 'Guest'}</div>
                    <div class="d-flex justify-content-between align-items-center extra-small text-muted">
                        <span><i class="fa-solid fa-calendar-days me-1"></i>${b.check_in} &rarr; ${b.check_out}</span>
                        <span class="fw-bold text-navy fs-6">₹${parseFloat(b.total_amount || 0).toLocaleString('en-IN')}</span>
                    </div>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    } else {
        listContainer.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-calendar-xmark fs-2 mb-2 text-light d-block"></i>
                No reservation bookings found for Room ${r.room_no}.
            </div>
        `;
    }

    const modalElem = document.getElementById('roomBookingsModal');
    showRoomModal(modalElem);
}

// 11. BULK SELECTION LOGIC
function initBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAllRoomsCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            document.querySelectorAll('.room-select-checkbox').forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkActionBar();
        });
    }
}

function updateBulkActionBar() {
    const selectedCbs = document.querySelectorAll('.room-select-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countDisplay = document.getElementById('bulkSelectedCount');

    if (bulkBar && countDisplay) {
        if (selectedCbs.length > 0) {
            countDisplay.textContent = `${selectedCbs.length} room(s) selected`;
            bulkBar.style.display = 'flex';
        } else {
            bulkBar.style.display = 'none';
        }
    }
}

function submitBulkDelete() {
    const selectedCbs = document.querySelectorAll('.room-select-checkbox:checked');
    if (selectedCbs.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedCbs.length} selected room(s)? This action cannot be undone.`)) {
        const ids = Array.from(selectedCbs).map(cb => cb.value);
        document.getElementById('bulk_action_type').value = 'bulk_delete';
        document.getElementById('bulk_action_ids').value = ids.join(',');
        document.getElementById('bulkActionForm').submit();
    }
}

function submitBulkStatus(status) {
    const selectedCbs = document.querySelectorAll('.room-select-checkbox:checked');
    if (selectedCbs.length === 0) return;

    const ids = Array.from(selectedCbs).map(cb => cb.value);
    document.getElementById('bulk_action_type').value = 'bulk_status';
    document.getElementById('bulk_action_status').value = status;
    document.getElementById('bulk_action_ids').value = ids.join(',');
    document.getElementById('bulkActionForm').submit();
}

// 12. CHECKBOX SELECTION & SMART CSV EXPORT (AVAILABLE / OCCUPIED STATUS)
function toggleSelectAllRooms(masterCB) {
    const isChecked = masterCB.checked;
    document.querySelectorAll('.room-select-cb').forEach(cb => {
        cb.checked = isChecked;
    });
    updateBulkRoomActionsUI();
}

function handleRoomCheckboxChange() {
    const allCBs = document.querySelectorAll('.room-select-cb');
    const checkedCBs = document.querySelectorAll('.room-select-cb:checked');
    const masterCB = document.getElementById('selectAllRoomsCheckbox');
    if (masterCB) {
        masterCB.checked = allCBs.length > 0 && checkedCBs.length === allCBs.length;
    }
    updateBulkRoomActionsUI();
}

function updateBulkRoomActionsUI() {
    const checkedCBs = document.querySelectorAll('.room-select-cb:checked');
    const exportBtn = document.getElementById('btnExportRooms');
    if (exportBtn) {
        if (checkedCBs.length > 0) {
            exportBtn.innerHTML = `<i class="fa-solid fa-download"></i> Export Selected (${checkedCBs.length})`;
        } else {
            exportBtn.innerHTML = `<i class="fa-solid fa-download"></i> Export`;
        }
    }
}

function exportRoomsCSV() {
    const selectedCBs = Array.from(document.querySelectorAll('.room-select-cb:checked'));
    let targetRows = [];

    if (selectedCBs.length > 0) {
        targetRows = selectedCBs.map(cb => cb.closest('tr, [data-room]')).filter(Boolean);
    } else {
        // Export visible room rows
        targetRows = Array.from(document.querySelectorAll('.room-table-row')).filter(r => r.style.display !== 'none');
        if (!targetRows.length) {
            targetRows = Array.from(document.querySelectorAll('.room-grid-card-wrapper')).filter(c => c.style.display !== 'none');
        }
    }

    if (!targetRows.length) {
        alert("No room records available to export.");
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,ID,Room Number,Category,Floor,Price,Capacity,Bed Type,Availability Status,Detailed Status,Amenities\n";
    targetRows.forEach(row => {
        const id = row.getAttribute('data-id') || '';
        const roomNo = `"${(row.getAttribute('data-room-no') || '').replace(/"/g, '""')}"`;
        const type = `"${(row.getAttribute('data-type-name') || '').replace(/"/g, '""')}"`;
        const floor = `"${(row.getAttribute('data-floor') || '').replace(/"/g, '""')}"`;
        const price = row.getAttribute('data-price') || '0';
        const capacity = row.getAttribute('data-capacity') || '2';
        const bed = `"${(row.getAttribute('data-bed-type') || '').replace(/"/g, '""')}"`;
        const rawStatus = row.getAttribute('data-status') || 'Available';
        const isAvailable = rawStatus.toLowerCase() === 'available';
        const availStatus = isAvailable ? '"Available"' : `"${'Not Available (' + rawStatus + ')'}"`;
        const status = `"${rawStatus}"`;
        const amenities = `"${(row.getAttribute('data-amenities') || '').replace(/"/g, '""')}"`;

        csvContent += `${id},${roomNo},${type},${floor},${price},${capacity},${bed},${availStatus},${status},${amenities}\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `HotelEase_Rooms_Inventory_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast(`Exported ${targetRows.length} room(s) to CSV file.`, 'success');
}

// 13. TOAST NOTIFICATION SYSTEM
function showToast(message, type = 'success') {
    const container = document.getElementById('toastNotificationContainer');
    if (!container) return;

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'danger' ? 'bg-danger text-white' : (type === 'info' ? 'bg-info text-white' : 'bg-dark text-white');
    const icon = type === 'danger' ? 'fa-circle-xmark text-danger' : (type === 'info' ? 'fa-circle-info text-info' : 'fa-circle-check text-success');

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center border-0 shadow-lg rounded-3 mb-2" role="alert" style="min-width: 280px; background: #0F172A; color: #fff;">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2 small font-semibold">
                    <i class="fa-solid ${icon} fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const elem = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(elem, { delay: 4000 });
    bsToast.show();
}

// Helper: Close Modal or Offcanvas
function closeRoomModal(modalId) {
    let elem = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (!elem && modalId && modalId.closest) elem = modalId.closest('.modal, .offcanvas');
    if (!elem) return;

    if (elem.classList.contains('offcanvas')) {
        hideRoomOffcanvas(elem);
    } else {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const instance = bootstrap.Modal.getInstance(elem);
            if (instance) instance.hide();
        }
        elem.classList.remove('show', 'room-modal-open');
        elem.style.display = 'none';
        document.body.classList.remove('modal-open', 'room-modal-lock');
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.querySelectorAll('.room-modal-backdrop').forEach(b => b.remove());
    }
}

// 14. KEYBOARD SHORTCUTS & CLICK TO CLOSE HANDLERS
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show, .modal.room-modal-open').forEach(m => closeRoomModal(m));
            document.querySelectorAll('.offcanvas.show, .offcanvas.active').forEach(o => closeRoomModal(o));
        }
    });

    // Delegated click listener for all close X and Cancel buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('button, a, .btn');
        if (!btn) return;

        const btnText = (btn.textContent || '').trim().toLowerCase();
        const isDismiss = btn.hasAttribute('data-bs-dismiss') || 
                          btn.classList.contains('btn-close') || 
                          btn.classList.contains('btn-cancel') || 
                          btnText === 'cancel' || 
                          btnText === 'close' ||
                          btnText === 'close preview';

        if (isDismiss) {
            const container = btn.closest('.modal, .offcanvas');
            if (container) {
                closeRoomModal(container);
            }
        }
    });
}

// 15. QUICK INLINE ROOM STATUS UPDATE
function quickUpdateRoomStatus(roomId, newStatus) {
    if (!roomId || !newStatus) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/hotel_website/admin/rooms.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'change_room_status';
    form.appendChild(actionInput);

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'room_id';
    idInput.value = roomId;
    form.appendChild(idInput);

    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = newStatus;
    form.appendChild(statusInput);

    document.body.appendChild(form);
    form.submit();
}
