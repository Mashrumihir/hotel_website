/**
 * HotelEase Admin - Payments Management JavaScript
 */

let activePaymentData = null;
let allPaymentsDataset = [];
let activeStatFilter = 'all';

document.addEventListener('DOMContentLoaded', function () {
    initializeDataset();
    setupEventListeners();
    setupRecordPaymentCalculator();
    applySavedViewMode();
});

// Initialize Dataset from hidden JSON attributes
function initializeDataset() {
    allPaymentsDataset = [];
    const items = document.querySelectorAll('.payment-row-item, .payment-grid-item, [data-payment]');
    items.forEach(item => {
        const raw = item.getAttribute('data-payment');
        if (raw) {
            try {
                const p = JSON.parse(raw);
                if (!allPaymentsDataset.some(x => x.payment_id == p.payment_id)) {
                    allPaymentsDataset.push(p);
                }
            } catch (e) {
                console.error("Error parsing payment dataset item", e);
            }
        }
    });
}

// Setup Event Listeners
function setupEventListeners() {
    const searchInput = document.getElementById('searchPaymentInput');
    const searchClearBtn = document.getElementById('btnSearchClear');
    const statusSelect = document.getElementById('filterStatusSelect');
    const methodSelect = document.getElementById('filterMethodSelect');
    const dateSelect = document.getElementById('filterDateSelect');
    const fromDateInput = document.getElementById('filterFromDate');
    const toDateInput = document.getElementById('filterToDate');
    const minAmountInput = document.getElementById('filterMinAmount');
    const maxAmountInput = document.getElementById('filterMaxAmount');
    const sortSelect = document.getElementById('sortPaymentSelect');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (searchClearBtn) {
                searchClearBtn.style.display = this.value ? 'block' : 'none';
            }
            filterAndRenderPayments();
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            this.style.display = 'none';
            filterAndRenderPayments();
        });
    }

    if (statusSelect) statusSelect.addEventListener('change', filterAndRenderPayments);
    if (methodSelect) methodSelect.addEventListener('change', filterAndRenderPayments);
    if (dateSelect) {
        dateSelect.addEventListener('change', function () {
            const customBox = document.getElementById('customDateRangeBox');
            if (customBox) {
                customBox.style.display = this.value === 'custom' ? 'flex' : 'none';
            }
            filterAndRenderPayments();
        });
    }
    if (fromDateInput) fromDateInput.addEventListener('change', filterAndRenderPayments);
    if (toDateInput) toDateInput.addEventListener('change', filterAndRenderPayments);
    if (minAmountInput) minAmountInput.addEventListener('input', filterAndRenderPayments);
    if (maxAmountInput) maxAmountInput.addEventListener('input', filterAndRenderPayments);
    if (sortSelect) sortSelect.addEventListener('change', filterAndRenderPayments);

    // Select All Checkbox
    const selectAllCb = document.getElementById('selectAllPayments');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.payment-select-cb');
            checkboxes.forEach(cb => cb.checked = selectAllCb.checked);
            updateBulkActionButton();
        });
    }

    // Individual Checkbox listener delegate
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('payment-select-cb')) {
            updateBulkActionButton();
        }
    });
}

// Update Bulk Action Button
function updateBulkActionButton() {
    const checked = document.querySelectorAll('.payment-select-cb:checked');
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
function filterByStatCard(statusFilter, cardElement) {
    activeStatFilter = statusFilter;

    // Highlight selected card
    const allCards = document.querySelectorAll('.kpi-card');
    allCards.forEach(c => c.classList.remove('active-filter'));

    if (cardElement) {
        cardElement.classList.add('active-filter');
    }

    // Set filter status select
    const statusSelect = document.getElementById('filterStatusSelect');
    if (statusSelect) {
        statusSelect.value = statusFilter;
    }

    filterAndRenderPayments();

    // Smooth scroll to payment section
    const targetSection = document.getElementById('paymentsTableViewContainer') || document.getElementById('paymentsGridViewContainer');
    if (targetSection) {
        targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// View Mode Switcher (Table View Default)
function setViewMode(mode) {
    const tableView = document.getElementById('paymentsTableViewContainer');
    const gridView = document.getElementById('paymentsGridViewContainer');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');

    if (tableView) tableView.style.display = 'block';
    if (gridView) gridView.style.display = 'none';
    if (btnTable) btnTable.classList.add('active');
    if (btnGrid) btnGrid.classList.remove('active');
    localStorage.setItem('hotelease_payments_view', 'table');
}

function applySavedViewMode() {
    setViewMode('table');
}

// Live Search & Filter Logic
function filterAndRenderPayments() {
    const query = (document.getElementById('searchPaymentInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('filterStatusSelect')?.value || 'all';
    const methodVal = document.getElementById('filterMethodSelect')?.value || 'all';
    const dateVal = document.getElementById('filterDateSelect')?.value || 'all';
    const fromDateVal = document.getElementById('filterFromDate')?.value || '';
    const toDateVal = document.getElementById('filterToDate')?.value || '';
    const minAmount = parseFloat(document.getElementById('filterMinAmount')?.value || 0);
    const maxAmount = parseFloat(document.getElementById('filterMaxAmount')?.value || Infinity);
    const sortVal = document.getElementById('sortPaymentSelect')?.value || 'newest';

    const tableRows = document.querySelectorAll('.payment-row-item');
    const gridCards = document.querySelectorAll('.payment-grid-item');

    let visibleCount = 0;

    tableRows.forEach(row => {
        const raw = row.getAttribute('data-payment');
        if (!raw) return;
        const p = JSON.parse(raw);
        const matches = matchPayment(p, query, statusVal, methodVal, dateVal, fromDateVal, toDateVal, minAmount, maxAmount);

        if (matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    gridCards.forEach(card => {
        const raw = card.getAttribute('data-payment');
        if (!raw) return;
        const p = JSON.parse(raw);
        const matches = matchPayment(p, query, statusVal, methodVal, dateVal, fromDateVal, toDateVal, minAmount, maxAmount);

        if (matches) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    // Update Showing counter
    const counterText = document.getElementById('paymentsCounterText');
    if (counterText) {
        counterText.textContent = `Showing 1–${visibleCount} of ${allPaymentsDataset.length} payments`;
    }

    // Empty state visibility
    const emptyState = document.getElementById('paymentsEmptyState');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function matchPayment(p, query, statusVal, methodVal, dateVal, fromDateVal, toDateVal, minAmount, maxAmount) {
    // 1. Text Search
    if (query) {
        const txnStr = String(p.transaction_id || `PAY-00${p.payment_id}`).toLowerCase();
        const bkRef = `#he-${p.booking_id}`.toLowerCase();
        const bkIdStr = String(p.booking_id);
        const custName = (p.customer_name || '').toLowerCase();
        const custEmail = (p.customer_email || '').toLowerCase();
        const custMobile = (p.customer_mobile || '').toLowerCase();
        const roomNo = String(p.room_no || '').toLowerCase();

        const matchText = txnStr.includes(query) || bkRef.includes(query) || bkIdStr.includes(query) ||
            custName.includes(query) || custEmail.includes(query) || custMobile.includes(query) || roomNo.includes(query);

        if (!matchText) return false;
    }

    // 2. Status Filter
    if (statusVal !== 'all' && p.payment_status !== statusVal) {
        return false;
    }

    // 3. Method Filter
    if (methodVal !== 'all' && p.payment_mode !== methodVal) {
        return false;
    }

    // 4. Amount Range
    const amt = parseFloat(p.amount || 0);
    if (amt < minAmount || amt > maxAmount) {
        return false;
    }

    // 5. Date Filter
    if (dateVal !== 'all') {
        const pDate = new Date(p.payment_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (dateVal === 'today') {
            const pDateOnly = new Date(pDate.getFullYear(), pDate.getMonth(), pDate.getDate());
            if (pDateOnly.getTime() !== today.getTime()) return false;
        } else if (dateVal === 'yesterday') {
            const yest = new Date(today);
            yest.setDate(yest.getDate() - 1);
            const pDateOnly = new Date(pDate.getFullYear(), pDate.getMonth(), pDate.getDate());
            if (pDateOnly.getTime() !== yest.getTime()) return false;
        } else if (dateVal === 'this_week') {
            const firstDayOfWeek = new Date(today);
            firstDayOfWeek.setDate(today.getDate() - today.getDay());
            if (pDate < firstDayOfWeek) return false;
        } else if (dateVal === 'this_month') {
            if (pDate.getMonth() !== today.getMonth() || pDate.getFullYear() !== today.getFullYear()) return false;
        } else if (dateVal === 'custom') {
            if (fromDateVal) {
                const fDate = new Date(fromDateVal);
                if (pDate < fDate) return false;
            }
            if (toDateVal) {
                const tDate = new Date(toDateVal);
                tDate.setHours(23, 59, 59, 999);
                if (pDate > tDate) return false;
            }
        }
    }

    return true;
}

// Reset All Filters
function resetPaymentFilters() {
    const searchInput = document.getElementById('searchPaymentInput');
    const statusSelect = document.getElementById('filterStatusSelect');
    const methodSelect = document.getElementById('filterMethodSelect');
    const dateSelect = document.getElementById('filterDateSelect');
    const fromDateInput = document.getElementById('filterFromDate');
    const toDateInput = document.getElementById('filterToDate');
    const minAmountInput = document.getElementById('filterMinAmount');
    const maxAmountInput = document.getElementById('filterMaxAmount');
    const sortSelect = document.getElementById('sortPaymentSelect');

    if (searchInput) searchInput.value = '';
    if (statusSelect) statusSelect.value = 'all';
    if (methodSelect) methodSelect.value = 'all';
    if (dateSelect) dateSelect.value = 'all';
    if (fromDateInput) fromDateInput.value = '';
    if (toDateInput) toDateInput.value = '';
    if (minAmountInput) minAmountInput.value = '';
    if (maxAmountInput) maxAmountInput.value = '';
    if (sortSelect) sortSelect.value = 'newest';

    const customBox = document.getElementById('customDateRangeBox');
    if (customBox) customBox.style.display = 'none';

    // Remove KPI stat active class
    const allCards = document.querySelectorAll('.kpi-card');
    allCards.forEach(c => c.classList.remove('active-filter'));

    filterAndRenderPayments();
}

// Right-Side Drawer Management
function openPaymentDrawer(elemOrData) {
    let p = null;
    if (typeof elemOrData === 'object' && elemOrData.payment_id) {
        p = elemOrData;
    } else if (elemOrData) {
        const raw = elemOrData.getAttribute('data-payment') || elemOrData.closest('[data-payment]')?.getAttribute('data-payment');
        if (raw) {
            try { p = JSON.parse(raw); } catch (e) { console.error(e); }
        }
    }

    if (!p) return;
    activePaymentData = p;

    // Header & Ref
    const txnId = p.transaction_id || `PAY-00${p.payment_id}`;
    document.getElementById('drawerTxnId').textContent = `#${txnId}`;
    document.getElementById('drawerPaymentDate').textContent = `Logged: ${p.payment_date || 'N/A'}`;

    // Customer Info
    document.getElementById('drawerCustomerName').textContent = p.customer_name || 'Guest';
    document.getElementById('drawerCustomerEmail').textContent = p.customer_email || 'N/A';
    document.getElementById('drawerCustomerMobile').textContent = p.customer_mobile || 'N/A';
    document.getElementById('drawerCustomerIDProof').textContent = p.customer_id_proof || 'Govt ID Verified';

    const avatarImg = document.getElementById('drawerCustomerAvatar');
    if (avatarImg) {
        if (p.profile_pic) {
            avatarImg.src = `/hotel_website/${p.profile_pic}`;
            avatarImg.style.display = 'block';
            document.getElementById('drawerCustomerInitial').style.display = 'none';
        } else {
            avatarImg.style.display = 'none';
            document.getElementById('drawerCustomerInitial').style.display = 'flex';
            document.getElementById('drawerCustomerInitial').textContent = (p.customer_name || 'G').charAt(0).toUpperCase();
        }
    }

    // Booking Info
    document.getElementById('drawerBookingRef').textContent = `#HE-${p.booking_id}`;
    document.getElementById('drawerRoomNo').textContent = `Room ${p.room_no || '101'}`;
    document.getElementById('drawerRoomType').textContent = p.type_name || 'Standard Room';
    document.getElementById('drawerCheckIn').textContent = formatDate(p.check_in);
    document.getElementById('drawerCheckOut').textContent = formatDate(p.check_out);

    // Financial Breakdown
    const totalAmt = parseFloat(p.booking_total || p.amount || 0);
    const paidAmt = p.payment_status === 'Paid' ? parseFloat(p.amount || 0) : 0;
    const remainingAmt = Math.max(0, totalAmt - paidAmt);
    const subtotal = totalAmt / 1.18;
    const gst = totalAmt - subtotal;
    const discount = 0;

    const subEl = document.getElementById('drawerSubtotal');
    const taxEl = document.getElementById('drawerTax');
    const discEl = document.getElementById('drawerDiscount');
    const totEl = document.getElementById('drawerTotalAmount');

    if (subEl) subEl.textContent = `₹${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (taxEl) taxEl.textContent = `₹${gst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (discEl) discEl.textContent = `-₹${discount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (totEl) totEl.textContent = `₹${totalAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    document.getElementById('drawerPaidAmount').textContent = `₹${parseFloat(p.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    document.getElementById('drawerRemainingAmount').textContent = `₹${remainingAmt.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    document.getElementById('drawerPaymentMode').textContent = p.payment_mode || 'Card';
    document.getElementById('drawerPaymentNotes').textContent = p.payment_details || 'No specific payment notes recorded.';

    // Status Badges
    document.getElementById('drawerStatusBadge').innerHTML = getPaymentStatusBadgeHTML(p.payment_status);

    // Action buttons inside drawer
    renderDrawerActionButtons(p);

    // Show Drawer
    const drawer = document.getElementById('paymentDrawer');
    const backdrop = document.getElementById('paymentDrawerBackdrop');
    if (drawer) drawer.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
}

function closePaymentDrawer() {
    const drawer = document.getElementById('paymentDrawer');
    const backdrop = document.getElementById('paymentDrawerBackdrop');
    if (drawer) drawer.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
}

// Render Drawer Action Buttons dynamically
function renderDrawerActionButtons(p) {
    const container = document.getElementById('drawerActionButtons');
    if (!container) return;

    let html = '';

    // Logically valid actions
    if (p.payment_status === 'Pending') {
        html += `<button type="button" class="btn btn-sm btn-success rounded-3 font-semibold px-3" onclick="openMarkPaidModal(${p.payment_id}, '${escapeJS(p.transaction_id || ('PAY-00' + p.payment_id))}', '${escapeJS(p.customer_name)}', '${p.amount}')">
                    <i class="fa-solid fa-check-circle me-1.5"></i>Mark as Paid
                 </button>`;
    }

    if (p.payment_status === 'Paid') {
        html += `<button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-3 font-semibold px-3" onclick="openRefundModal(${p.payment_id}, '${escapeJS(p.transaction_id || ('PAY-00' + p.payment_id))}', '${escapeJS(p.customer_name)}', '${p.amount}')">
                    <i class="fa-solid fa-rotate-left me-1.5"></i>Refund
                 </button>`;
    }

    html += `<button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="printPaymentReceipt(${p.payment_id})">
                <i class="fa-solid fa-print me-1.5"></i>Print Receipt
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-outline-info rounded-3 font-semibold px-3" onclick="openViewBookingModalFromDrawer()">
                <i class="fa-solid fa-bookmark me-1.5"></i>View Booking
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-outline-secondary rounded-3 font-semibold px-3" onclick="openViewCustomerModalFromDrawer()">
                <i class="fa-solid fa-user me-1.5"></i>View Customer
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-semibold px-3" onclick="openEditPaymentModalFromData(this)" data-payment='${JSON.stringify(p)}'>
                <i class="fa-solid fa-pen-to-square me-1.5"></i>Edit
             </button>`;

    html += `<button type="button" class="btn btn-sm btn-outline-danger rounded-3 font-semibold px-3" onclick="openDeletePaymentModal(${p.payment_id}, '${escapeJS(p.transaction_id || ('PAY-00' + p.payment_id))}', '${escapeJS(p.customer_name)}')">
                <i class="fa-solid fa-trash-can me-1.5"></i>Delete
             </button>`;

    container.innerHTML = html;
}

// Setup Dynamic Calculator inside Record Payment Modal
function setupRecordPaymentCalculator() {
    const bookingSelect = document.getElementById('add_booking_id');
    if (!bookingSelect) return;

    bookingSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (!option) return;

        const total = parseFloat(option.getAttribute('data-total') || 0);
        const paid = parseFloat(option.getAttribute('data-paid') || 0);
        const remaining = Math.max(0, total - paid);

        const amountInput = document.getElementById('add_amount');
        if (amountInput) {
            amountInput.value = remaining > 0 ? remaining.toFixed(2) : total.toFixed(2);
        }

        document.getElementById('add_calc_total').textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('add_calc_paid').textContent = `₹${paid.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('add_calc_remaining').textContent = `₹${remaining.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    });
}

// Modal Trigger Helpers
function openMarkPaidModal(id, txnId, name, amount) {
    closePaymentDrawer();
    document.getElementById('mark_payment_id').value = id;
    document.getElementById('mark_txn_id').textContent = `#${txnId}`;
    document.getElementById('mark_customer_name').textContent = name;
    document.getElementById('mark_amount').textContent = `₹${parseFloat(amount).toLocaleString('en-IN')}`;

    const modalElem = document.getElementById('markPaidModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function openRefundModal(id, txnId, name, amount) {
    closePaymentDrawer();
    if (typeof id === 'object' && id !== null) {
        const elem = id;
        if (elem.getAttribute) {
            const raw = elem.getAttribute('data-payment') || elem.closest('[data-payment]')?.getAttribute('data-payment');
            if (raw) {
                try {
                    const p = JSON.parse(raw);
                    id = p.payment_id;
                    txnId = p.transaction_id || ('PAY-00' + id);
                    name = p.customer_name || 'Customer';
                    amount = p.amount;
                } catch(e) {}
            }
        }
    }
    if (!id) return;

    document.getElementById('refund_payment_id').value = id;
    document.getElementById('refund_txn_id').textContent = `#${txnId}`;
    document.getElementById('refund_customer_name').textContent = name;
    document.getElementById('refund_amount_display').textContent = `₹${parseFloat(amount || 0).toLocaleString('en-IN')}`;
    document.getElementById('refund_amount_input').value = amount || 0;

    const modalElem = document.getElementById('refundModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function openEditPaymentModalFromData(elem) {
    closePaymentDrawer();
    let p = null;
    if (typeof elem === 'object' && elem !== null) {
        if (elem.payment_id) {
            p = elem;
        } else if (elem.getAttribute) {
            const raw = elem.getAttribute('data-payment') || elem.closest('[data-payment]')?.getAttribute('data-payment');
            if (raw) {
                try { p = JSON.parse(raw); } catch (e) {}
            }
        }
    }
    if (!p) return;

    const idInput = document.getElementById('edit_payment_id');
    const modeSelect = document.getElementById('edit_payment_mode');
    const statusSelect = document.getElementById('edit_payment_status');
    const detailsText = document.getElementById('edit_payment_details');

    if (idInput) idInput.value = p.payment_id;
    if (modeSelect) modeSelect.value = p.payment_mode || 'Card';
    if (statusSelect) statusSelect.value = p.payment_status || 'Paid';
    if (detailsText) detailsText.value = p.payment_details || '';

    const modalElem = document.getElementById('editPaymentModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function openDeletePaymentModal(id, txnId, name) {
    closePaymentDrawer();
    if (typeof id === 'object' && id !== null) {
        const elem = id;
        if (elem.getAttribute) {
            const raw = elem.getAttribute('data-payment') || elem.closest('[data-payment]')?.getAttribute('data-payment');
            if (raw) {
                try {
                    const p = JSON.parse(raw);
                    id = p.payment_id;
                    txnId = p.transaction_id || ('PAY-00' + id);
                    name = p.customer_name || 'Customer';
                } catch(err) {}
            } else {
                id = elem.getAttribute('data-id') || elem.closest('[data-id]')?.getAttribute('data-id');
                txnId = elem.getAttribute('data-txn') || elem.closest('[data-txn]')?.getAttribute('data-txn') || ('PAY-00' + id);
                name = elem.getAttribute('data-name') || elem.closest('[data-name]')?.getAttribute('data-name') || 'Customer';
            }
        } else if (elem.payment_id) {
            id = elem.payment_id;
            txnId = elem.transaction_id || ('PAY-00' + id);
            name = elem.customer_name || 'Customer';
        }
    } else if (id) {
        const found = allPaymentsDataset.find(x => x.payment_id == id);
        if (found) {
            if (!txnId) txnId = found.transaction_id || ('PAY-00' + id);
            if (!name) name = found.customer_name || 'Customer';
        }
    }

    if (!id) return;

    const idInput = document.getElementById('delete_payment_id');
    const txnElem = document.getElementById('delete_txn_id');
    const nameElem = document.getElementById('delete_customer_name');

    if (idInput) idInput.value = id;
    if (txnElem) txnElem.textContent = `#${txnId}`;
    if (nameElem) nameElem.textContent = name || 'Customer';

    const modalElem = document.getElementById('deletePaymentModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function openViewBookingModalFromDrawer() {
    if (!activePaymentData) return;
    openViewBookingModal(activePaymentData);
}

function openViewCustomerModalFromDrawer() {
    if (!activePaymentData) return;
    openViewCustomerModal(activePaymentData);
}

function openViewBookingModal(p) {
    closePaymentDrawer();
    if (typeof p === 'object' && p !== null && !p.booking_id && p.getAttribute) {
        const raw = p.getAttribute('data-payment') || p.closest('[data-payment]')?.getAttribute('data-payment');
        if (raw) {
            try { p = JSON.parse(raw); } catch (e) {}
        }
    }
    if (!p) return;

    document.getElementById('vb_ref').textContent = `#HE-${p.booking_id}`;
    document.getElementById('vb_customer').textContent = p.customer_name || 'Guest';
    document.getElementById('vb_room').textContent = `Room ${p.room_no || '101'} (${p.type_name || 'Standard'})`;
    document.getElementById('vb_checkin').textContent = formatDate(p.check_in);
    document.getElementById('vb_checkout').textContent = formatDate(p.check_out);
    document.getElementById('vb_status').textContent = p.booking_status || 'Confirmed';
    document.getElementById('vb_amount').textContent = `₹${parseFloat(p.booking_total || p.amount || 0).toLocaleString('en-IN')}`;

    const modalElem = document.getElementById('viewBookingModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function openViewCustomerModal(p) {
    closePaymentDrawer();
    if (typeof p === 'object' && p !== null && !p.customer_name && p.getAttribute) {
        const raw = p.getAttribute('data-payment') || p.closest('[data-payment]')?.getAttribute('data-payment');
        if (raw) {
            try { p = JSON.parse(raw); } catch (e) {}
        }
    }
    if (!p) return;

    document.getElementById('vc_name').textContent = p.customer_name || 'Guest';
    document.getElementById('vc_email').textContent = p.customer_email || 'N/A';
    document.getElementById('vc_mobile').textContent = p.customer_mobile || 'N/A';
    document.getElementById('vc_idproof').textContent = p.customer_id_proof || 'Govt ID Verified';
    document.getElementById('vc_address').textContent = p.customer_address || 'No registered address.';
    document.getElementById('vc_reg_date').textContent = p.customer_created_at || 'N/A';

    const modalElem = document.getElementById('viewCustomerModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

// Printable Receipt Generator
function printPaymentReceipt(target) {
    closePaymentDrawer();
    let p = null;
    if (typeof target === 'object' && target !== null) {
        if (target.payment_id) {
            p = target;
        } else if (target.getAttribute) {
            const raw = target.getAttribute('data-payment') || target.closest('[data-payment]')?.getAttribute('data-payment');
            if (raw) {
                try { p = JSON.parse(raw); } catch (e) {}
            }
            if (!p) {
                const pId = target.getAttribute('data-id') || target.closest('[data-id]')?.getAttribute('data-id');
                if (pId) p = allPaymentsDataset.find(x => x.payment_id == pId);
            }
        }
    } else if (target) {
        p = allPaymentsDataset.find(x => x.payment_id == target) || activePaymentData;
    }

    if (!p) return;

    const txnId = p.transaction_id || `PAY-00${p.payment_id}`;
    document.getElementById('rcptTxnId').textContent = `#${txnId}`;
    document.getElementById('rcptRefNo').textContent = `#HE-${p.booking_id}`;
    document.getElementById('rcptDate').textContent = p.payment_date || new Date().toLocaleDateString('en-US');
    document.getElementById('rcptGuestName').textContent = p.customer_name || 'Guest';
    document.getElementById('rcptGuestEmail').textContent = p.customer_email || 'N/A';
    document.getElementById('rcptGuestMobile').textContent = p.customer_mobile || 'N/A';

    document.getElementById('rcptRoomNo').textContent = `Room ${p.room_no || '101'} (${p.type_name || 'Standard'})`;
    document.getElementById('rcptCheckIn').textContent = formatDate(p.check_in);
    document.getElementById('rcptCheckOut').textContent = formatDate(p.check_out);

    const paidAmt = parseFloat(p.amount || 0);

    document.getElementById('rcptItemDesc').textContent = `Room Accommodation & Services - Payment Ref #${txnId}`;
    document.getElementById('rcptItemMode').textContent = p.payment_mode || 'Card';
    document.getElementById('rcptItemAmount').textContent = `₹${paidAmt.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

    document.getElementById('rcptPaidTotal').textContent = `₹${paidAmt.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    
    document.getElementById('rcptStatusStamp').innerHTML = p.payment_status === 'Paid' ?
        '<span class="badge bg-success fs-6 px-3 py-2 rounded-3"><i class="fa-solid fa-circle-check me-1"></i>PAID IN FULL</span>' :
        `<span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-3"><i class="fa-solid fa-clock me-1"></i>${(p.payment_status || 'PENDING').toUpperCase()}</span>`;

    const modalElem = document.getElementById('receiptModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
            modal.show();
        } else {
            modalElem.classList.add('show');
            modalElem.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function triggerReceiptPrint() {
    window.print();
}

// CSV Export Handler
function exportPaymentsCSV() {
    if (allPaymentsDataset.length === 0) {
        alert('No payment records available to export.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Transaction ID,Booking Ref,Customer Name,Email,Mobile,Room No,Payment Date,Amount,Payment Method,Status,Payment Details\n";

    allPaymentsDataset.forEach(p => {
        const txnId = p.transaction_id || `PAY-00${p.payment_id}`;
        const row = [
            `"#${txnId}"`,
            `"#HE-${p.booking_id}"`,
            `"${escapeCSV(p.customer_name)}"`,
            `"${escapeCSV(p.customer_email)}"`,
            `"${escapeCSV(p.customer_mobile)}"`,
            `"${p.room_no}"`,
            `"${p.payment_date}"`,
            `"${p.amount}"`,
            `"${p.payment_mode}"`,
            `"${p.payment_status}"`,
            `"${escapeCSV(p.payment_details)}"`
        ].join(",");
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `hotelease_payments_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Refresh Data Handler with Spinner
function refreshPaymentsData() {
    const refreshBtn = document.getElementById('btnRefreshPayments');
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

function getPaymentStatusBadgeHTML(status) {
    switch (status) {
        case 'Paid':
            return `<span class="badge-payment-status badge-status-paid"><i class="fa-solid fa-circle-check"></i> Paid</span>`;
        case 'Pending':
            return `<span class="badge-payment-status badge-status-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>`;
        case 'Refunded':
            return `<span class="badge-payment-status badge-status-refunded"><i class="fa-solid fa-rotate-left"></i> Refunded</span>`;
        case 'Failed':
            return `<span class="badge-payment-status badge-status-failed"><i class="fa-solid fa-xmark-circle"></i> Failed</span>`;
        default:
            return `<span class="badge-payment-status badge-status-paid">${status}</span>`;
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
