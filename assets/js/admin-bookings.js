/**
 * HotelEase Admin - Bookings Management JavaScript
 */

let currentPhotoScale = 1.0;
let activeBookingData = null;
let allBookingsDataset = [];

function showBookingModal(modalElem) {
    if (!modalElem) return;

    const drawer = document.getElementById('bookingDrawer');
    if (drawer && (drawer.classList.contains('active') || drawer.classList.contains('show'))) {
        closeBookingDrawer();
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElem).show();
        return;
    }

    document.querySelectorAll('.modal.show, .modal.booking-modal-open').forEach(openModal => {
        if (openModal !== modalElem) closeBookingModal(openModal);
    });
    modalElem.classList.add('show', 'booking-modal-open');
    modalElem.style.display = 'block';
    modalElem.removeAttribute('aria-hidden');
    modalElem.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');

    if (!document.querySelector('.booking-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show booking-modal-backdrop';
        backdrop.addEventListener('click', () => closeBookingModal(modalElem));
        document.body.appendChild(backdrop);
    }
}

function closeBookingModal(modalElem) {
    if (!modalElem) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(modalElem);
        if (instance) instance.hide();
    }
    modalElem.classList.remove('show', 'booking-modal-open');
    modalElem.style.display = 'none';
    modalElem.setAttribute('aria-hidden', 'true');
    modalElem.removeAttribute('aria-modal');
    document.querySelectorAll('.booking-modal-backdrop').forEach(backdrop => backdrop.remove());
    if (!document.querySelector('.modal.show, .booking-modal-open')) {
        document.body.classList.remove('modal-open');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initializeDataset();
    setupEventListeners();
    setupLiveCalculator();
    setupPhotoLightboxZoom();
    applySavedViewMode();
    filterAndRenderBookings(false);
});

function formatImgUrl(path) {
    if (!path) return '';
    path = path.trim();
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
        return path;
    }
    if (path.startsWith('/hotel_website/')) {
        return path;
    }
    return '/hotel_website/' + path.replace(/^\/+/, '');
}

// Robust JSON Parsing Helper for Data Attributes
function safeParseBookingData(raw) {
    if (!raw) return null;
    if (typeof raw === 'object') return raw;
    try {
        return JSON.parse(raw);
    } catch (e) {
        try {
            const txt = document.createElement('textarea');
            txt.innerHTML = raw;
            return JSON.parse(txt.value);
        } catch (e2) {
            try {
                const unescaped = raw.replace(/\\u0022/g, '"').replace(/\\u0027/g, "'").replace(/&quot;/g, '"');
                return JSON.parse(unescaped);
            } catch (e3) {
                console.error("Failed parsing booking JSON:", raw, e3);
                return null;
            }
        }
    }
}

// Initialize Dataset from hidden JSON or table attributes
function initializeDataset() {
    allBookingsDataset = [];
    const items = document.querySelectorAll('.booking-row-item, .booking-grid-item, [data-booking]');
    items.forEach(item => {
        const raw = item.getAttribute('data-booking');
        if (raw) {
            const b = safeParseBookingData(raw);
            if (b && b.booking_id && !allBookingsDataset.some(x => x.booking_id == b.booking_id)) {
                allBookingsDataset.push(b);
            }
        }
    });
}

// Setup Event Listeners for Filters, Search, View Switcher
function setupEventListeners() {
    const searchInput = document.getElementById('searchBookingInput');
    const statusSelect = document.getElementById('filterStatusSelect');
    const paymentSelect = document.getElementById('filterPaymentSelect');
    const sortSelect = document.getElementById('sortBookingSelect');
    const dateInput = document.getElementById('filterDateInput');

    if (searchInput) searchInput.addEventListener('input', filterAndRenderBookings);
    if (statusSelect) statusSelect.addEventListener('change', filterAndRenderBookings);
    if (paymentSelect) paymentSelect.addEventListener('change', filterAndRenderBookings);
    if (sortSelect) sortSelect.addEventListener('change', filterAndRenderBookings);
    if (dateInput) dateInput.addEventListener('change', filterAndRenderBookings);

    // Select All Checkbox
    const selectAllCb = document.getElementById('selectAllBookings');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.booking-select-cb');
            checkboxes.forEach(cb => cb.checked = selectAllCb.checked);
            updateBulkActionButton();
        });
    }

    // Individual Checkboxes delegate
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('booking-select-cb')) {
            updateBulkActionButton();
        }
    });
}

// Update Bulk Actions Button state
function updateBulkActionButton() {
    const checked = document.querySelectorAll('.booking-select-cb:checked');
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

// View Mode Switcher (Table View Default)
function setViewMode(mode) {
    const tableView = document.getElementById('bookingsTableViewContainer');
    const gridView = document.getElementById('bookingsGridViewContainer');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');

    if (tableView) tableView.style.display = 'block';
    if (gridView) gridView.style.display = 'none';
    if (btnTable) btnTable.classList.add('active');
    if (btnGrid) btnGrid.classList.remove('active');
    localStorage.setItem('hotelease_bookings_view', 'table');
}

function applySavedViewMode() {
    setViewMode('table');
}

let currentBookingPage = 1;
const bookingsPerPage = 20;
let currentActiveStatus = 'all';

function filterByStatusPill(status, pillElem) {
    currentActiveStatus = status;

    document.querySelectorAll('.booking-status-pill').forEach(btn => {
        btn.classList.remove('active');
        btn.style.backgroundColor = '#F1F5F9';
        btn.style.color = '#334155';
    });

    if (pillElem) {
        pillElem.classList.add('active');
        pillElem.style.backgroundColor = '#2563EB';
        pillElem.style.color = '#ffffff';
    }

    filterAndRenderBookings();
}

// Live Filter & Sort & Paginate Handler
function filterAndRenderBookings(resetPage = true) {
    if (resetPage === true || typeof resetPage === 'object') {
        currentBookingPage = 1;
    }

    const query = (document.getElementById('searchBookingInput')?.value || '').toLowerCase().trim();
    const statusVal = currentActiveStatus || document.getElementById('filterStatusSelect')?.value || 'all';
    const paymentVal = document.getElementById('filterPaymentSelect')?.value || 'all';
    const sortVal = document.getElementById('sortBookingSelect')?.value || 'newest';
    const dateVal = document.getElementById('filterDateInput')?.value || '';

    const tableTbody = document.querySelector('.bookings-table tbody');
    const gridContainer = document.getElementById('bookingsGridViewContainer');

    const tableRows = Array.from(document.querySelectorAll('.booking-row-item'));
    const gridCards = Array.from(document.querySelectorAll('.booking-grid-item'));

    let matchedTableRows = [];
    let matchedGridCards = [];

    tableRows.forEach(row => {
        const raw = row.getAttribute('data-booking');
        let b = safeParseBookingData(raw);
        if (!b) {
            const rowText = (row.textContent || '').toLowerCase();
            if (!query || rowText.includes(query)) {
                matchedTableRows.push({ elem: row, data: { booking_id: 0, customer_name: '', booking_status: 'Confirmed' } });
            } else {
                row.style.display = 'none';
            }
            return;
        }
        if (matchBooking(b, query, statusVal, paymentVal, dateVal)) {
            matchedTableRows.push({ elem: row, data: b });
        } else {
            row.style.display = 'none';
        }
    });

    gridCards.forEach(card => {
        const raw = card.getAttribute('data-booking');
        if (!raw) return;
        const b = safeParseBookingData(raw);
        if (!b) return;
        if (matchBooking(b, query, statusVal, paymentVal, dateVal)) {
            matchedGridCards.push({ elem: card, data: b });
        } else {
            card.style.display = 'none';
        }
    });

    // Sort matching items
    const sortFn = getSortComparator(sortVal);
    matchedTableRows.sort((a, b) => sortFn(a.data, b.data));
    matchedGridCards.sort((a, b) => sortFn(a.data, b.data));

    // Re-append to DOM in sorted order
    if (tableTbody) {
        matchedTableRows.forEach(item => tableTbody.appendChild(item.elem));
    }
    if (gridContainer) {
        matchedGridCards.forEach(item => gridContainer.appendChild(item.elem));
    }

    const totalMatched = matchedTableRows.length;
    const totalPages = Math.ceil(totalMatched / bookingsPerPage) || 1;
    if (currentBookingPage > totalPages) currentBookingPage = totalPages;

    const startIdx = (currentBookingPage - 1) * bookingsPerPage;
    const endIdx = startIdx + bookingsPerPage;

    // Apply pagination visibility
    matchedTableRows.forEach((item, idx) => {
        item.elem.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
    });
    matchedGridCards.forEach((item, idx) => {
        item.elem.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
    });

    // Update Pagination Counters
    const pStart = document.getElementById('paginationStart');
    const pEnd = document.getElementById('paginationEnd');
    const pTotal = document.getElementById('paginationTotal');

    if (pStart) pStart.textContent = totalMatched === 0 ? '0' : (startIdx + 1);
    if (pEnd) pEnd.textContent = Math.min(endIdx, totalMatched);
    if (pTotal) pTotal.textContent = totalMatched;

    // Render Pagination Controls
    renderPaginationButtons(totalPages);

    // Empty State visibility
    const emptyState = document.getElementById('bookingsEmptyState');
    if (emptyState) {
        emptyState.style.display = totalMatched === 0 ? 'block' : 'none';
    }
}

function getSortComparator(sortVal) {
    return function(a, b) {
        switch (sortVal) {
            case 'oldest':
                return (parseInt(a.booking_id) || 0) - (parseInt(b.booking_id) || 0);
            case 'checkin_asc':
                return new Date(a.check_in || 0) - new Date(b.check_in || 0);
            case 'checkin_desc':
                return new Date(b.check_in || 0) - new Date(a.check_in || 0);
            case 'amount_low':
                return (parseFloat(a.total_amount) || 0) - (parseFloat(b.total_amount) || 0);
            case 'amount_high':
                return (parseFloat(b.total_amount) || 0) - (parseFloat(a.total_amount) || 0);
            case 'name_asc':
                return (a.customer_name || '').localeCompare(b.customer_name || '');
            case 'name_desc':
                return (b.customer_name || '').localeCompare(a.customer_name || '');
            case 'newest':
            default:
                return (parseInt(b.booking_id) || 0) - (parseInt(a.booking_id) || 0);
        }
    };
}

function renderPaginationButtons(totalPages) {
    const ul = document.getElementById('paginationPages');
    if (!ul) return;

    let html = '';

    const prevDisabled = currentBookingPage === 1 ? 'disabled' : '';
    html += `<li class="page-item ${prevDisabled}">
        <a class="page-link rounded-3 px-3" href="javascript:void(0)" onclick="changeBookingPage(${currentBookingPage - 1})" aria-label="Previous">
            <i class="fa-solid fa-chevron-left me-1"></i> Previous
        </a>
    </li>`;

    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7) {
            if (p !== 1 && p !== totalPages && Math.abs(p - currentBookingPage) > 2) {
                if (p === 2 || p === totalPages - 1) {
                    html += `<li class="page-item disabled"><span class="page-link border-0">...</span></li>`;
                }
                continue;
            }
        }
        const activeClass = p === currentBookingPage ? 'active' : '';
        html += `<li class="page-item ${activeClass}">
            <a class="page-link rounded-3 px-3" href="javascript:void(0)" onclick="changeBookingPage(${p})">${p}</a>
        </li>`;
    }

    const nextDisabled = currentBookingPage >= totalPages ? 'disabled' : '';
    html += `<li class="page-item ${nextDisabled}">
        <a class="page-link rounded-3 px-3" href="javascript:void(0)" onclick="changeBookingPage(${currentBookingPage + 1})" aria-label="Next">
            Next <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </li>`;

    ul.innerHTML = html;
}

function changeBookingPage(page) {
    if (page < 1) return;
    currentBookingPage = page;
    filterAndRenderBookings(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function matchBooking(b, query, statusVal, paymentVal, dateVal) {
    if (!b) return false;
    // 1. Text Search
    if (query) {
        const refStr = `#HE-${b.booking_id}`.toLowerCase();
        const idStr = String(b.booking_id);
        const guestName = (b.customer_name || '').toLowerCase();
        const guestEmail = (b.customer_email || '').toLowerCase();
        const guestPhone = (b.customer_mobile || '').toLowerCase();
        const roomNo = String(b.room_no || '').toLowerCase();
        const roomType = (b.type_name || '').toLowerCase();

        const matchText = refStr.includes(query) || idStr.includes(query) || 
                          guestName.includes(query) || guestEmail.includes(query) || 
                          guestPhone.includes(query) || roomNo.includes(query) || 
                          roomType.includes(query);
        if (!matchText) return false;
    }

    // 2. Status Filter
    if (statusVal !== 'all' && b.booking_status !== statusVal) {
        return false;
    }

    // 3. Payment Filter
    if (paymentVal !== 'all') {
        const pStatus = b.payment_status || 'Paid';
        if (pStatus !== paymentVal) return false;
    }

    // 4. Date Filter
    if (dateVal) {
        if (b.check_in !== dateVal && b.check_out !== dateVal) {
            return false;
        }
    }

    return true;
}

// Right-side Drawer Management
function openBookingDrawer(elemOrData) {
    let b = null;
    if (typeof elemOrData === 'number' || (typeof elemOrData === 'string' && !isNaN(elemOrData) && elemOrData.trim() !== '')) {
        b = allBookingsDataset.find(x => x.booking_id == elemOrData);
    } else if (typeof elemOrData === 'object' && elemOrData !== null) {
        if (elemOrData.booking_id) {
            b = elemOrData;
        } else if (elemOrData.getAttribute) {
            const raw = elemOrData.getAttribute('data-booking') || elemOrData.closest('[data-booking]')?.getAttribute('data-booking');
            if (raw) {
                b = safeParseBookingData(raw);
            }
        }
    }

    if (!b && elemOrData && typeof elemOrData === 'object' && elemOrData.closest) {
        const item = elemOrData.closest('[data-booking]');
        if (item) {
            b = safeParseBookingData(item.getAttribute('data-booking'));
        }
    }

    if (!b) {
        console.error("Booking data not found for openBookingDrawer:", elemOrData);
        return;
    }
    activeBookingData = b;

    // Set Drawer Title & Ref
    const refElem = document.getElementById('drawerBookingRef');
    if (refElem) refElem.textContent = `#HE-${b.booking_id}`;

    const dateElem = document.getElementById('drawerBookingDate');
    if (dateElem) dateElem.textContent = `Created: ${b.created_at || 'N/A'}`;

    // Guest Info
    const gName = document.getElementById('drawerGuestName');
    if (gName) gName.textContent = b.customer_name || 'Guest';

    const gEmail = document.getElementById('drawerGuestEmail');
    if (gEmail) gEmail.textContent = b.customer_email || 'N/A';

    const gMobile = document.getElementById('drawerGuestMobile');
    if (gMobile) gMobile.textContent = b.customer_mobile || 'N/A';

    if (document.getElementById('drawerGuestID')) {
        document.getElementById('drawerGuestID').textContent = b.customer_id_proof || 'Govt ID Verified';
    }

    // Guest Avatar / Photo
    const avatarImg = document.getElementById('drawerGuestAvatar');
    if (avatarImg) {
        if (b.profile_pic) {
            avatarImg.src = formatImgUrl(b.profile_pic);
            avatarImg.style.display = 'block';
            avatarImg.onerror = function() {
                this.style.display = 'none';
                const initEl = document.getElementById('drawerGuestInitial');
                if (initEl) initEl.style.display = 'flex';
            };
            const initEl = document.getElementById('drawerGuestInitial');
            if (initEl) initEl.style.display = 'none';
        } else {
            avatarImg.style.display = 'none';
            const initEl = document.getElementById('drawerGuestInitial');
            if (initEl) {
                initEl.style.display = 'flex';
                initEl.textContent = (b.customer_name || 'G').charAt(0).toUpperCase();
            }
        }
    }

    // Room Info
    const rmNo = document.getElementById('drawerRoomNo');
    if (rmNo) rmNo.textContent = `Room ${b.room_no}`;

    const rmType = document.getElementById('drawerRoomType');
    if (rmType) rmType.textContent = b.type_name || 'Standard Room';

    const rmPrice = document.getElementById('drawerRoomPrice');
    if (rmPrice) rmPrice.textContent = `₹${parseFloat(b.room_price || 0).toLocaleString('en-IN')}/night`;

    const roomImg = document.getElementById('drawerRoomPhoto');
    if (roomImg) {
        if (b.room_image) {
            roomImg.src = formatImgUrl(b.room_image);
            roomImg.style.display = 'block';
            roomImg.onerror = function() {
                this.src = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=500&auto=format&fit=crop&q=60';
            };
        } else {
            roomImg.src = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=500&auto=format&fit=crop&q=60';
            roomImg.style.display = 'block';
        }
    }

    // Stay Details
    const cinElem = document.getElementById('drawerCheckIn');
    if (cinElem) cinElem.textContent = formatDate(b.check_in);

    const coutElem = document.getElementById('drawerCheckOut');
    if (coutElem) coutElem.textContent = formatDate(b.check_out);

    const nights = calculateNights(b.check_in, b.check_out);
    const adultsNum = parseInt(b.adults || 1, 10);
    const childrenNum = parseInt(b.children || 0, 10);

    const nightsEl = document.getElementById('drawerNightsCount');
    if (nightsEl) nightsEl.textContent = `${nights} ${nights === 1 ? 'Night' : 'Nights'}`;

    const guestsEl = document.getElementById('drawerGuestsCount');
    if (guestsEl) guestsEl.textContent = `${adultsNum} ${adultsNum === 1 ? 'Adult' : 'Adults'}, ${childrenNum} ${childrenNum === 1 ? 'Child' : 'Children'}`;

    const reqElem = document.getElementById('drawerSpecialRequests');
    if (reqElem) {
        if (b.special_request) {
            reqElem.innerHTML = escapeHtml(b.special_request).replace(/ \| /g, '<br><i class="fa-solid fa-angle-right text-primary me-1"></i>');
        } else {
            reqElem.textContent = 'No special requests or additional guest details recorded.';
        }
    }

    // Financial Breakdown Calculation
    const roomRate = parseFloat(b.room_price || 0);
    const subtotal = roomRate * nights;
    const gst = subtotal * 0.18;
    const total = parseFloat(b.total_amount || 0);
    const discount = Math.max(0, (subtotal + gst) - total);

    const subEl = document.getElementById('drawerSubtotal');
    const taxEl = document.getElementById('drawerTax');
    const discEl = document.getElementById('drawerDiscount');
    const totEl = document.getElementById('drawerTotalAmount');

    if (subEl) subEl.textContent = `₹${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (taxEl) taxEl.textContent = `₹${gst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (discEl) discEl.textContent = `-₹${discount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (totEl) totEl.textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // Badges
    const stBadge = document.getElementById('drawerBookingStatusBadge');
    if (stBadge) stBadge.innerHTML = getStatusBadgeHTML(b.booking_status);

    const payBadge = document.getElementById('drawerPaymentStatusBadge');
    if (payBadge) payBadge.innerHTML = getPaymentBadgeHTML(b.payment_status || 'Paid', b.payment_mode || 'Card');

    // Update Timeline Step Indicator
    updateDrawerTimeline(b.booking_status);

    // Update Quick Action Buttons inside Drawer
    renderDrawerActionButtons(b);

    // Show Drawer & Backdrop
    const drawer = document.getElementById('bookingDrawer');
    const backdrop = document.getElementById('bookingDrawerBackdrop');
    if (drawer) {
        drawer.classList.add('active', 'show');
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            const bsOff = bootstrap.Offcanvas.getOrCreateInstance(drawer);
            bsOff.show();
        }
    }
    if (backdrop) backdrop.classList.add('active', 'show');
}

function closeBookingDrawer() {
    const drawer = document.getElementById('bookingDrawer');
    const backdrop = document.getElementById('bookingDrawerBackdrop');
    if (drawer) {
        drawer.classList.remove('active', 'show');
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            const bsOff = bootstrap.Offcanvas.getInstance(drawer);
            if (bsOff) bsOff.hide();
        }
    }
    if (backdrop) backdrop.classList.remove('active', 'show');
}

// Timeline Step Status Updater
function updateDrawerTimeline(status) {
    const step1 = document.getElementById('tlStepCreated');
    const step2 = document.getElementById('tlStepConfirmed');
    const step3 = document.getElementById('tlStepCheckIn');
    const step4 = document.getElementById('tlStepCheckOut');

    // Reset classes
    [step1, step2, step3, step4].forEach(step => {
        if (step) {
            step.className = 'timeline-step';
        }
    });

    if (status === 'Cancelled') {
        if (step1) step1.className = 'timeline-step completed';
        if (step2) step2.className = 'timeline-step cancelled';
        if (step3) step3.className = 'timeline-step';
        if (step4) step4.className = 'timeline-step';
        return;
    }

    if (step1) step1.className = 'timeline-step completed';

    if (status === 'Pending') {
        if (step2) step2.className = 'timeline-step active';
    } else if (status === 'Confirmed') {
        if (step2) step2.className = 'timeline-step completed';
        if (step3) step3.className = 'timeline-step active';
    } else if (status === 'Checked-In') {
        if (step2) step2.className = 'timeline-step completed';
        if (step3) step3.className = 'timeline-step completed';
        if (step4) step4.className = 'timeline-step active';
    } else if (status === 'Checked-Out') {
        if (step2) step2.className = 'timeline-step completed';
        if (step3) step3.className = 'timeline-step completed';
        if (step4) step4.className = 'timeline-step completed';
    }
}

// Render Action Buttons inside Drawer
function renderDrawerActionButtons(b) {
    const container = document.getElementById('drawerActionButtons');
    if (!container) return;

    let html = '';

    if (b.booking_status === 'Pending') {
        html += `<button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openConfirmBookingModal(${b.booking_id}, '#HE-${b.booking_id}', '${escapeJS(b.customer_name)}')">
                    <i class="fa-solid fa-circle-check me-1.5"></i>Confirm Booking
                 </button>`;
    }

    if (b.booking_status === 'Confirmed' || b.booking_status === 'Pending') {
        html += `<button type="button" class="btn btn-sm btn-success rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openCheckInModal(${b.booking_id}, '#HE-${b.booking_id}', '${escapeJS(b.customer_name)}', '${b.room_no}')">
                    <i class="fa-solid fa-right-to-bracket me-1.5"></i>Check-In Guest
                 </button>`;
    }

    if (b.booking_status === 'Checked-In') {
        html += `<button type="button" class="btn btn-sm btn-warning text-dark rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openCheckOutModal(${b.booking_id}, '#HE-${b.booking_id}', '${escapeJS(b.customer_name)}', '${b.total_amount}', '${b.payment_status || 'Paid'}')">
                    <i class="fa-solid fa-right-from-bracket me-1.5"></i>Check-Out Guest
                 </button>`;
    }

    if (b.booking_status !== 'Cancelled' && b.booking_status !== 'Checked-Out') {
        html += `<button type="button" class="btn btn-sm btn-outline-danger rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openCancelModal(${b.booking_id}, '#HE-${b.booking_id}', '${escapeJS(b.customer_name)}')">
                    <i class="fa-solid fa-ban me-1.5"></i>Cancel
                 </button>`;
    }

    html += `<button type="button" class="btn btn-sm btn-outline-secondary rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openAdminInvoiceModal(${b.booking_id})">
                <i class="fa-solid fa-print me-1.5"></i>Print Invoice
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openEditBookingModalFromData(${b.booking_id})">
                <i class="fa-solid fa-pen-to-square me-1.5"></i>Edit
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-danger rounded-3 font-semibold px-3" onclick="event.stopPropagation(); openDeleteBookingModal(${b.booking_id}, '#HE-${b.booking_id}', '${escapeJS(b.customer_name)}')">
                <i class="fa-solid fa-trash-can me-1.5"></i>Delete
             </button>`;

    container.innerHTML = html;
}

// Setup Live Price Calculator in New & Edit Booking Modals
function setupLiveCalculator() {
    const newCheckIn = document.getElementById('new_check_in');
    const newCheckOut = document.getElementById('new_check_out');
    const newRoomSelect = document.getElementById('new_room_id');

    if (newCheckIn) newCheckIn.addEventListener('change', updateNewBookingCalculation);
    if (newCheckOut) newCheckOut.addEventListener('change', updateNewBookingCalculation);
    if (newRoomSelect) newRoomSelect.addEventListener('change', updateNewBookingCalculation);

    const editCheckIn = document.getElementById('edit_check_in');
    const editCheckOut = document.getElementById('edit_check_out');
    const editRoomSelect = document.getElementById('edit_room_id');

    if (editCheckIn) editCheckIn.addEventListener('change', updateEditBookingCalculation);
    if (editCheckOut) editCheckOut.addEventListener('change', updateEditBookingCalculation);
    if (editRoomSelect) editRoomSelect.addEventListener('change', updateEditBookingCalculation);
}

function updateNewBookingCalculation() {
    const checkInVal = document.getElementById('new_check_in')?.value;
    const checkOutVal = document.getElementById('new_check_out')?.value;
    const roomSelect = document.getElementById('new_room_id');

    if (!checkInVal || !checkOutVal || !roomSelect) return;

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const pricePerNight = parseFloat(selectedOption?.getAttribute('data-price') || 0);

    const nights = calculateNights(checkInVal, checkOutVal);
    const subtotal = pricePerNight * nights;
    const tax = subtotal * 0.10;
    const grandTotal = subtotal + tax;

    const nEl = document.getElementById('new_calc_nights');
    if (nEl) nEl.textContent = `${nights} Night(s)`;

    const rEl = document.getElementById('new_calc_rate');
    if (rEl) rEl.textContent = `₹${pricePerNight.toLocaleString('en-IN')}`;

    const sEl = document.getElementById('new_calc_subtotal');
    if (sEl) sEl.textContent = `₹${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

    const tEl = document.getElementById('new_calc_tax');
    if (tEl) tEl.textContent = `₹${tax.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

    const totEl = document.getElementById('new_calc_total');
    if (totEl) totEl.textContent = `₹${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

    const totalInput = document.getElementById('new_total_amount');
    if (totalInput) totalInput.value = grandTotal.toFixed(2);
}

function updateEditBookingCalculation() {
    const checkInVal = document.getElementById('edit_check_in')?.value;
    const checkOutVal = document.getElementById('edit_check_out')?.value;
    const roomSelect = document.getElementById('edit_room_id');

    if (!checkInVal || !checkOutVal || !roomSelect) return;

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const pricePerNight = parseFloat(selectedOption?.getAttribute('data-price') || 0);

    if (pricePerNight > 0) {
        const nights = calculateNights(checkInVal, checkOutVal);
        const subtotal = pricePerNight * nights;
        const tax = subtotal * 0.10;
        const grandTotal = subtotal + tax;

        const totalInput = document.getElementById('edit_total_amount');
        if (totalInput) {
            totalInput.value = grandTotal.toFixed(2);
        }
    }
}

// Photo Preview Lightbox Zoom Controls
function openPhotoPreview(imageSrc, captionTitle) {
    const modalImg = document.getElementById('lightboxZoomImg');
    const modalCaption = document.getElementById('lightboxZoomCaption');
    
    if (modalImg) {
        modalImg.src = imageSrc;
        currentPhotoScale = 1.0;
        modalImg.style.transform = `scale(${currentPhotoScale})`;
    }
    if (modalCaption) {
        modalCaption.textContent = captionTitle || 'Photo Preview';
    }

    const modalElem = document.getElementById('bookingPhotoModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function setupPhotoLightboxZoom() {
    const zoomInBtn = document.getElementById('btnZoomIn');
    const zoomOutBtn = document.getElementById('btnZoomOut');
    const resetZoomBtn = document.getElementById('btnResetZoom');
    const imgWrapper = document.getElementById('lightboxWrapper');

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function () {
            zoomImage(0.2);
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function () {
            zoomImage(-0.2);
        });
    }

    if (resetZoomBtn) {
        resetZoomBtn.addEventListener('click', function () {
            currentPhotoScale = 1.0;
            applyZoomTransform();
        });
    }

    if (imgWrapper) {
        imgWrapper.addEventListener('wheel', function (e) {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.15 : -0.15;
            zoomImage(delta);
        });
    }
}

function zoomImage(delta) {
    currentPhotoScale += delta;
    if (currentPhotoScale < 0.5) currentPhotoScale = 0.5;
    if (currentPhotoScale > 3.0) currentPhotoScale = 3.0;
    applyZoomTransform();
}

function applyZoomTransform() {
    const modalImg = document.getElementById('lightboxZoomImg');
    if (modalImg) {
        modalImg.style.transform = `scale(${currentPhotoScale})`;
    }
}

// Modal Trigger Functions
function openConfirmBookingModal(id, ref, guestName) {
    document.getElementById('confirm_booking_id').value = id;
    document.getElementById('confirm_booking_ref').textContent = ref;
    document.getElementById('confirm_guest_name').textContent = guestName;

    const modalElem = document.getElementById('confirmBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function openCheckInModal(id, ref, guestName, roomNo) {
    document.getElementById('checkin_booking_id').value = id;
    document.getElementById('checkin_booking_ref').textContent = ref;
    document.getElementById('checkin_guest_name').textContent = guestName;
    document.getElementById('checkin_room_no').textContent = `Room ${roomNo}`;

    const modalElem = document.getElementById('checkInBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function openCheckOutModal(id, ref, guestName, totalAmount, paymentStatus) {
    document.getElementById('checkout_booking_id').value = id;
    document.getElementById('checkout_booking_ref').textContent = ref;
    document.getElementById('checkout_guest_name').textContent = guestName;
    document.getElementById('checkout_total_amount').textContent = `₹${parseFloat(totalAmount).toLocaleString('en-IN')}`;
    document.getElementById('checkout_payment_status').value = paymentStatus || 'Paid';

    const modalElem = document.getElementById('checkOutBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function openCancelModal(id, ref, guestName) {
    document.getElementById('cancel_booking_id').value = id;
    document.getElementById('cancel_booking_ref').textContent = ref;
    document.getElementById('cancel_guest_name').textContent = guestName;

    const modalElem = document.getElementById('cancelBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function openUpdatePaymentModal(id, ref, guestName, currentPaymentStatus, currentPaymentMode, totalAmount) {
    document.getElementById('pay_booking_id').value = id;
    document.getElementById('pay_booking_ref').textContent = ref;
    document.getElementById('pay_guest_name').textContent = guestName;
    document.getElementById('pay_amount').value = totalAmount || 0;
    document.getElementById('pay_status').value = currentPaymentStatus || 'Paid';
    document.getElementById('pay_mode').value = currentPaymentMode || 'Card';

    const modalElem = document.getElementById('updatePaymentModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

// Edit Booking Modal Handler (Accepts ID, DOM element, or data object)
function openEditBookingModalFromData(elemOrData) {
    let b = null;
    if (typeof elemOrData === 'number' || (typeof elemOrData === 'string' && !isNaN(elemOrData) && elemOrData.trim() !== '')) {
        b = allBookingsDataset.find(x => x.booking_id == elemOrData);
    } else if (typeof elemOrData === 'object' && elemOrData !== null) {
        if (elemOrData.booking_id) {
            b = elemOrData;
        } else if (elemOrData.getAttribute) {
            const raw = elemOrData.getAttribute('data-booking') || elemOrData.closest('[data-booking]')?.getAttribute('data-booking');
            if (raw) {
                b = safeParseBookingData(raw);
            }
        }
    }

    if (!b && elemOrData && typeof elemOrData === 'object' && elemOrData.closest) {
        const card = elemOrData.closest('[data-booking]');
        if (card) {
            b = safeParseBookingData(card.getAttribute('data-booking'));
        }
    }

    if (!b) {
        b = activeBookingData;
    }

    if (!b) return;

    document.getElementById('edit_booking_id').value = b.booking_id;
    const stSel = document.getElementById('edit_booking_status');
    if (stSel) stSel.value = b.booking_status || 'Pending';

    const rmSel = document.getElementById('edit_room_id');
    if (rmSel) rmSel.value = b.room_id;

    const cinIn = document.getElementById('edit_check_in');
    if (cinIn) cinIn.value = b.check_in;

    const coutIn = document.getElementById('edit_check_out');
    if (coutIn) coutIn.value = b.check_out;

    const adIn = document.getElementById('edit_adults');
    if (adIn) adIn.value = b.adults || 1;

    const chIn = document.getElementById('edit_children');
    if (chIn) chIn.value = b.children || 0;

    const amtIn = document.getElementById('edit_total_amount');
    if (amtIn) amtIn.value = parseFloat(b.total_amount || 0).toFixed(2);

    // Populate detailed financial breakdown fields
    const nights = calculateNights(b.check_in, b.check_out);
    const roomRate = parseFloat(b.room_price || 0);
    const subtotal = roomRate * nights;
    const gst = subtotal * 0.18;
    const discount = Math.max(0, (subtotal + gst) - parseFloat(b.total_amount || 0));

    const subIn = document.getElementById('edit_subtotal');
    if (subIn) subIn.value = subtotal.toFixed(2);

    const gstIn = document.getElementById('edit_gst_tax');
    if (gstIn) gstIn.value = gst.toFixed(2);

    const discIn = document.getElementById('edit_discount');
    if (discIn) discIn.value = discount.toFixed(2);

    const reqIn = document.getElementById('edit_special_request');
    if (reqIn) reqIn.value = b.special_request || '';

    const modalElem = document.getElementById('editBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

function calculateEditTotals() {
    const subIn = document.getElementById('edit_subtotal');
    const gstIn = document.getElementById('edit_gst_tax');
    const discIn = document.getElementById('edit_discount');
    const totalIn = document.getElementById('edit_total_amount');

    const sub = parseFloat(subIn?.value || 0);
    const gst = parseFloat(gstIn?.value || 0);
    const disc = parseFloat(discIn?.value || 0);

    const calcTotal = Math.max(0, (sub + gst) - disc);
    if (totalIn) {
        totalIn.value = calcTotal.toFixed(2);
    }
}

function openDeleteBookingModal(id, ref, guestName) {
    if (typeof id === 'object' && id !== null) {
        const elem = id;
        if (elem.getAttribute) {
            const raw = elem.getAttribute('data-booking') || elem.closest('[data-booking]')?.getAttribute('data-booking');
            if (raw) {
                const b = safeParseBookingData(raw);
                if (b) {
                    id = b.booking_id;
                    ref = '#HE-' + id;
                    guestName = b.customer_name || 'Guest';
                }
            }
        } else if (elem.booking_id) {
            id = elem.booking_id;
            ref = '#HE-' + id;
            guestName = elem.customer_name || 'Guest';
        }
    } else if (id) {
        const found = allBookingsDataset.find(x => x.booking_id == id);
        if (found) {
            if (!ref) ref = '#HE-' + found.booking_id;
            if (!guestName) guestName = found.customer_name || 'Guest';
        }
    }

    if (!id) return;

    const idInput = document.getElementById('delete_booking_id');
    const refElem = document.getElementById('delete_booking_ref');
    const nameElem = document.getElementById('delete_guest_name');

    if (idInput) idInput.value = id;
    if (refElem) refElem.textContent = ref || ('#HE-' + id);
    if (nameElem) nameElem.textContent = guestName || 'Guest';

    const modalElem = document.getElementById('deleteBookingModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

// Printable Luxury Invoice Population & Modal Trigger
function openAdminInvoiceModal(target, secondaryTarget) {
    if (typeof target === 'object' && target !== null && (target.stopPropagation || target.preventDefault)) {
        if (target.stopPropagation) target.stopPropagation();
        target = secondaryTarget;
    }

    if (!allBookingsDataset || allBookingsDataset.length === 0) {
        initializeDataset();
    }

    let b = null;
    if (typeof target === 'number' || (typeof target === 'string' && !isNaN(target) && target.trim() !== '')) {
        b = allBookingsDataset.find(x => x.booking_id == target);
    } else if (typeof target === 'object' && target !== null) {
        if (target.booking_id) {
            b = target;
        } else if (target.getAttribute) {
            const raw = target.getAttribute('data-booking') || target.closest('[data-booking]')?.getAttribute('data-booking');
            if (raw) b = safeParseBookingData(raw);
        }
    }
    if (!b) b = activeBookingData;
    if (!b && target) {
        const item = document.querySelector(`[data-booking*='"booking_id":${target}']`) || document.querySelector(`[data-booking*='"booking_id":"${target}"']`);
        if (item) b = safeParseBookingData(item.getAttribute('data-booking'));
    }
    if (!b) return;

    const setTxt = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.textContent = txt || '';
    };

    setTxt('adminInvRefNo', '#INV-2026-00' + b.booking_id);
    setTxt('adminInvDate', new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }));
    setTxt('adminInvGuestName', b.customer_name || 'Guest');
    setTxt('adminInvGuestEmail', b.customer_email || 'N/A');
    setTxt('adminInvGuestMobile', b.customer_mobile || 'N/A');
    setTxt('adminInvRoomNo', 'Room ' + b.room_no + ' (' + (b.type_name || 'Standard Room') + ')');
    setTxt('adminInvCheckIn', formatDate(b.check_in));
    setTxt('adminInvCheckOut', formatDate(b.check_out));

    const nights = calculateNights(b.check_in, b.check_out);
    const nightsBadge = document.getElementById('adminInvNightsBadge');
    if (nightsBadge) {
        nightsBadge.innerHTML = '<i class="fa-solid fa-moon text-warning me-1"></i> ' + nights + ' ' + (nights == 1 ? 'Night' : 'Nights') + ' Stay';
    }

    const roomRate = parseFloat(b.room_price || (b.total_amount / (nights || 1)));
    const subtotal = roomRate * (nights || 1);
    const tax = b.total_amount - subtotal > 0 ? (b.total_amount - subtotal) : (subtotal * 0.18);
    const total = parseFloat(b.total_amount || 0);

    setTxt('adminInvItemDesc', 'Room ' + b.room_no + ' (' + (b.type_name || 'Standard Room') + ') Accommodations');
    setTxt('adminInvItemNights', nights);
    setTxt('adminInvItemRate', '₹' + roomRate.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    setTxt('adminInvItemTotal', '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    setTxt('adminInvTaxRow', '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2}));

    setTxt('adminInvSubtotal', '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    setTxt('adminInvTax', '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    setTxt('adminInvDiscount', '-₹0.00');
    setTxt('adminInvGrandTotal', '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));

    setTxt('adminInvTxnId', '#CARD-' + Math.random().toString(36).substring(2, 10).toUpperCase());

    const stampElem = document.getElementById('adminInvStatusStamp');
    if (stampElem) {
        const pStatus = (b.payment_status || 'Paid').toLowerCase();
        const pMode = b.payment_mode || 'Card';
        if (pStatus === 'paid') {
            stampElem.innerHTML = '<span class="badge bg-success bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-circle-check me-1"></i> PAID IN FULL (' + pMode + ')</span>';
        } else {
            stampElem.innerHTML = '<span class="badge bg-warning text-dark bg-gradient px-3 py-1.5 rounded-pill font-bold uppercase"><i class="fa-solid fa-clock me-1"></i> PAYMENT PENDING</span>';
        }
    }

    const modalElem = document.getElementById('adminInvoiceModal');
    if (modalElem) {
        showBookingModal(modalElem);
    }
}

// Bulk Action Execution Handler
function executeBulkAction(actionType) {
    const checked = document.querySelectorAll('.booking-select-cb:checked');
    if (checked.length === 0) {
        alert('Please select at least one booking using the checkboxes.');
        return;
    }

    let actionLabel = '';
    if (actionType === 'delete') actionLabel = 'delete';
    else if (actionType === 'confirm') actionLabel = 'confirm';
    else if (actionType === 'cancel') actionLabel = 'cancel';

    const count = checked.length;
    const msg = actionType === 'delete' 
        ? `Are you sure you want to PERMANENTLY DELETE ${count} selected booking(s)? This action cannot be undone.`
        : `Are you sure you want to ${actionLabel} ${count} selected booking(s)?`;

    if (!confirm(msg)) return;

    // Create & submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/hotel_website/admin/bookings.php';

    const actInput = document.createElement('input');
    actInput.type = 'hidden';
    actInput.name = 'action';
    actInput.value = 'bulk_action';
    form.appendChild(actInput);

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'bulk_type';
    typeInput.value = actionType;
    form.appendChild(typeInput);

    checked.forEach(cb => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'booking_ids[]';
        idInput.value = cb.value;
        form.appendChild(idInput);
    });

    document.body.appendChild(form);
    form.submit();
}

// CSV Export Handler
function exportBookingsCSV() {
    const query = (document.getElementById('searchBookingInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('filterStatusSelect')?.value || 'all';
    const paymentVal = document.getElementById('filterPaymentSelect')?.value || 'all';
    const dateVal = document.getElementById('filterDateInput')?.value || '';

    const exportData = allBookingsDataset.filter(b => matchBooking(b, query, statusVal, paymentVal, dateVal));

    if (exportData.length === 0) {
        alert('No booking data available to export based on your current filters.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Booking Ref,Customer Name,Email,Mobile,Room No,Room Type,Check-In,Check-Out,Nights,Adults,Children,Total Amount,Status,Payment Status,Payment Mode,Created At\n";

    exportData.forEach(b => {
        const nights = calculateNights(b.check_in, b.check_out);
        const row = [
            `"#HE-${b.booking_id}"`,
            `"${escapeCSV(b.customer_name)}"`,
            `"${escapeCSV(b.customer_email)}"`,
            `"${escapeCSV(b.customer_mobile)}"`,
            `"${b.room_no}"`,
            `"${escapeCSV(b.type_name)}"`,
            `"${b.check_in}"`,
            `"${b.check_out}"`,
            `"${nights}"`,
            `"${b.adults || 1}"`,
            `"${b.children || 0}"`,
            `"${b.total_amount}"`,
            `"${b.booking_status}"`,
            `"${b.payment_status || 'Paid'}"`,
            `"${b.payment_mode || 'Card'}"`,
            `"${b.created_at}"`
        ].join(",");
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `hotelease_bookings_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Helper Utilities
function calculateNights(checkInStr, checkOutStr) {
    if (!checkInStr || !checkOutStr) return 1;
    const d1 = new Date(checkInStr);
    const d2 = new Date(checkOutStr);
    const diffTime = d2.getTime() - d1.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 1;
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getStatusBadgeHTML(status) {
    switch (status) {
        case 'Pending':
            return `<span class="badge-status badge-status-pending"><i class="fa-solid fa-clock"></i> Pending</span>`;
        case 'Confirmed':
            return `<span class="badge-status badge-status-confirmed"><i class="fa-solid fa-circle-check"></i> Confirmed</span>`;
        case 'Checked-In':
            return `<span class="badge-status badge-status-checked-in"><i class="fa-solid fa-bed"></i> Checked-In</span>`;
        case 'Checked-Out':
            return `<span class="badge-status badge-status-checked-out"><i class="fa-solid fa-right-from-bracket"></i> Checked-Out</span>`;
        case 'Cancelled':
            return `<span class="badge-status badge-status-cancelled"><i class="fa-solid fa-ban"></i> Cancelled</span>`;
        default:
            return `<span class="badge-status badge-status-checked-out">${status}</span>`;
    }
}

function getPaymentBadgeHTML(paymentStatus, paymentMode) {
    const modeStr = paymentMode ? ` (${paymentMode})` : '';
    switch (paymentStatus) {
        case 'Paid':
            return `<span class="badge-payment badge-payment-paid"><i class="fa-solid fa-check-circle"></i> Paid${modeStr}</span>`;
        case 'Pending':
            return `<span class="badge-payment badge-payment-pending"><i class="fa-solid fa-hourglass-half"></i> Payment Pending</span>`;
        case 'Failed':
            return `<span class="badge-payment badge-payment-failed"><i class="fa-solid fa-xmark-circle"></i> Failed</span>`;
        case 'Refunded':
            return `<span class="badge-payment badge-payment-refunded"><i class="fa-solid fa-rotate-left"></i> Refunded</span>`;
        default:
            return `<span class="badge-payment badge-payment-paid"><i class="fa-solid fa-check-circle"></i> Paid</span>`;
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Delegated click listener for all close X and Cancel buttons in Bookings Module
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
        const modal = btn.closest('.modal');
        const offcanvas = btn.closest('.offcanvas, #bookingDrawer');
        if (modal) {
            closeBookingModal(modal);
        }
        if (offcanvas) {
            if (typeof closeBookingDrawer === 'function') {
                closeBookingDrawer();
            }
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                const inst = bootstrap.Offcanvas.getInstance(offcanvas);
                if (inst) inst.hide();
            }
            offcanvas.classList.remove('active', 'show');
            const bd = document.getElementById('bookingDrawerBackdrop');
            if (bd) bd.classList.remove('active', 'show');
        }
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;

    const openModal = document.querySelector('.modal.show, .modal.booking-modal-open');
    if (openModal) {
        closeBookingModal(openModal);
    }
});
