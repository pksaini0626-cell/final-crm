<!-- GLOBAL REUSABLE BOOKING DETAIL MODAL PARTIAL (partials/show.blade.php) -->
<div id="globalBookingShowModal" class="modal fade" tabindex="-1" aria-labelledby="globalBookingShowModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-white border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="p-1 px-2 rounded bg-primary text-white font-monospace fw-bold fs-6">
                        <i class="bi bi-ticket-perforated-fill me-1"></i> <span id="modal_booking_id">#0000000</span>
                    </span>
                    <span class="badge bg-warning text-dark font-monospace fw-bold fs-6" id="modal_pnr_badge">
                        PNR: N/A
                    </span>
                    <span class="badge bg-info text-dark text-uppercase px-2.5 py-1" id="modal_booking_status_badge">
                        Status
                    </span>
                    <span class="badge bg-secondary text-uppercase px-2.5 py-1" id="modal_payment_status_badge">
                        Payment: Pending
                    </span>
                    <span class="badge bg-danger text-uppercase px-2.5 py-1 d-none" id="modal_case_status_badge">
                        Case: N/A
                    </span>
                    <span class="badge bg-danger text-white font-monospace text-uppercase px-2.5 py-1 d-none" id="modal_dispute_type_badge">
                        DISPUTE: NONE
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="modal_edit_btn" class="btn btn-outline-light btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                    <button type="button" onclick="window.printModalBooking()" class="btn btn-outline-light btn-sm fw-semibold d-inline-flex align-items-center gap-1" title="Print Booking">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Loading Spinner Container -->
            <div id="modal_loading_spinner" class="py-5 text-center bg-light">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading Booking Details...</span>
                </div>
                <p class="text-secondary small fw-bold mt-2">Fetching Complete Booking Record...</p>
            </div>

            <!-- Main Modal Body Content -->
            <div id="modal_booking_content" class="modal-body p-4 vstack gap-4 d-none" style="background-color: #f8fafc;">
                
                <!-- 1. GENERAL & AGENT SUMMARY -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-primary"></i> 1. General &amp; Agent Info
                        </h6>
                        <span class="text-muted small font-monospace" id="modal_created_at">Created: N/A</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Booking Reference</span>
                                <strong class="text-primary font-monospace fs-6" id="modal_ref_id">N/A</strong>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Booking Date</span>
                                <span class="fw-semibold text-dark font-monospace" id="modal_booking_date">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Booking Agent</span>
                                <span class="fw-bold text-dark" id="modal_agent_name">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Ticketing Agent</span>
                                <span class="fw-semibold text-dark" id="modal_ticketing_agent">Unassigned</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Merchant Profile</span>
                                <span class="badge bg-light text-dark border border-secondary-subtle font-monospace" id="modal_merchant">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Booking Portal</span>
                                <span class="fw-semibold text-dark" id="modal_portal">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Call Type / Vertical</span>
                                <span class="text-dark fw-semibold" id="modal_call_vertical">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Trip Type</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize" id="modal_trip_type">N/A</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Dispute Type</span>
                                @if(Auth::check() && (Auth::user()->role === 'chargeback' || Auth::user()->hasRole('chargeback')))
                                    <form id="modal_dispute_type_form" method="POST" action="" class="d-flex align-items-center gap-1 mt-1">
                                        @csrf
                                        <select name="dispute_type" id="modal_dispute_type_select" class="form-select form-select-sm font-monospace fw-bold text-danger py-0.5 border-danger-subtle">
                                            <option value="">None</option>
                                            <option value="CHARGEBACK">CHARGEBACK</option>
                                            <option value="RDR">RDR</option>
                                            <option value="ALERT">ALERT</option>
                                            <option value="RETRIEVAL">RETRIEVAL</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-2 py-0.5" title="Save Dispute Type">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="badge bg-danger text-white font-monospace mt-1 px-2 py-1 d-none" id="modal_dispute_type_badge_label">NONE</span>
                                    <span class="text-muted small mt-1 d-block" id="modal_dispute_type_none_label">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. CUSTOMER & BILLING INFORMATION -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard-fill text-success"></i> 2. Customer &amp; Billing Details
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Customer / Cardholder</span>
                                <div class="fw-bold text-dark fs-6" id="modal_customer_name">N/A</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Email Address</span>
                                <div class="fw-semibold text-primary" id="modal_customer_email">N/A</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Phone / Calling Number</span>
                                <div class="fw-semibold text-dark" id="modal_customer_phone">N/A</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Billing Address</span>
                                <div class="text-secondary small" id="modal_billing_address">N/A</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Payment Card Details</span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-dark text-white font-monospace text-uppercase" id="modal_card_type">CARD</span>
                                    <span class="font-monospace fw-bold text-dark">•••• •••• •••• <span id="modal_card_last4">0000</span></span>
                                    <span class="badge bg-light text-secondary border font-monospace" id="modal_card_exp">Exp: N/A</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FINANCIAL BREAKDOWN -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-warning"></i> 3. Financial Summary
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 text-center text-md-start">
                            <div class="col-md-2 col-6 border-end border-light-subtle">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Currency</span>
                                <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1" id="modal_currency">USD</span>
                            </div>
                            <div class="col-md-2 col-6 border-end border-light-subtle">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Total Booking</span>
                                <span class="h5 fw-bold text-dark font-monospace mb-0" id="modal_total_amount">$0.00</span>
                            </div>
                            <div class="col-md-2 col-6 border-end border-light-subtle">
                                <span class="text-secondary small text-uppercase fw-bold d-block">On Merchant</span>
                                <span class="h6 fw-bold text-primary font-monospace mb-0" id="modal_merchant_amount">$0.00</span>
                            </div>
                            <div class="col-md-2 col-6 border-end border-light-subtle">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Paid to Airline</span>
                                <span class="h6 fw-semibold text-secondary font-monospace mb-0" id="modal_paid_airline">$0.00</span>
                            </div>
                            <div class="col-md-2 col-6 border-end border-light-subtle">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Total MCO</span>
                                <span class="h5 fw-bold text-success font-monospace mb-0" id="modal_total_mco">$0.00</span>
                            </div>
                            <div class="col-md-2 col-6">
                                <span class="text-secondary small text-uppercase fw-bold d-block">Company Card</span>
                                <span class="h6 fw-bold text-warning-emphasis font-monospace mb-0" id="modal_company_card">None</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top border-light-subtle d-none" id="modal_payment_info_row">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Payment Info / Notes</span>
                            <div class="p-2 bg-light rounded text-dark small font-monospace" id="modal_payment_info"></div>
                        </div>
                    </div>
                </div>

                <!-- 4. PASSENGERS LIST -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill text-info"></i> 4. Passenger Details
                        </h6>
                        <span class="badge bg-info-subtle text-info-emphasis font-monospace" id="modal_pax_count">0 Passenger(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="px-3 py-2">#</th>
                                        <th class="px-3 py-2">Title &amp; Full Name</th>
                                        <th class="px-3 py-2">Gender</th>
                                        <th class="px-3 py-2">Date of Birth</th>
                                        <th class="px-3 py-2">Ticket Number</th>
                                        <th class="px-3 py-2">Seat Number</th>
                                    </tr>
                                </thead>
                                <tbody id="modal_passengers_list">
                                    <tr>
                                        <td colspan="6" class="px-3 py-3 text-center text-secondary fst-italic">No passenger details recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 5. ITINERARY FLIGHT SEGMENTS -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-airplane-engines-fill text-primary"></i> 5. Flight Itinerary Segments
                        </h6>
                        <span class="badge bg-primary-subtle text-primary font-monospace" id="modal_flights_count">0 Segment(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="px-3 py-2">Seg</th>
                                        <th class="px-3 py-2">Carrier &amp; Flight</th>
                                        <th class="px-3 py-2">Route (From &rarr; To)</th>
                                        <th class="px-3 py-2">Departure Date / Time</th>
                                        <th class="px-3 py-2">Arrival Date / Time</th>
                                        <th class="px-3 py-2">Class</th>
                                        <th class="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="modal_flights_list">
                                    <tr>
                                        <td colspan="7" class="px-3 py-3 text-center text-secondary fst-italic">No flight itinerary segments attached.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 6. REMARKS HISTORY & QUICK REMARK POST -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-bold text-uppercase small d-flex align-items-center gap-2">
                            <i class="bi bi-chat-left-text-fill text-success"></i> 6. Booking Remarks &amp; History
                        </h6>
                        <button type="button" class="btn btn-outline-success btn-sm py-1 px-2.5 fw-semibold" onclick="document.getElementById('modal_quick_remark_box').classList.toggle('d-none')">
                            <i class="bi bi-plus-lg me-1"></i> Add Quick Remark
                        </button>
                    </div>
                    
                    <!-- Quick Remark Form Container -->
                    <div id="modal_quick_remark_box" class="card-body p-3 bg-light border-bottom border-light-subtle d-none">
                        <form id="modal_quick_remark_form" method="POST" action="">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Add Admin / Agent Remark</label>
                                <textarea name="remark" required rows="2" placeholder="Write remark note for this booking..." class="form-control form-control-sm"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                                    <i class="bi bi-send me-1"></i> Submit Remark
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card-body p-3">
                        <div id="modal_remarks_list" class="vstack gap-2">
                            <div class="text-secondary small fst-italic">No remarks logged yet.</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2.5 px-4 border-top border-light-subtle d-flex justify-content-between align-items-center">
                <span class="text-secondary small font-monospace">Flight CRM Unified Booking Inspector</span>
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.currentModalBooking = null;

    /**
     * Unified Global Function to open & load full booking details in the modal.
     * Can accept a booking ID (integer/string) OR a booking object.
     */
    window.showBookingDetailModal = function(bookingOrId) {
        const modalElement = document.getElementById('globalBookingShowModal');
        if (!modalElement) return;

        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();

        const spinner = document.getElementById('modal_loading_spinner');
        const content = document.getElementById('modal_booking_content');

        spinner.classList.remove('d-none');
        content.classList.add('d-none');

        let bookingId = null;
        if (typeof bookingOrId === 'object' && bookingOrId !== null) {
            bookingId = bookingOrId.id;
        } else {
            bookingId = bookingOrId;
        }

        if (!bookingId) {
            alert('Invalid booking ID provided.');
            return;
        }

        // Fetch complete booking details from JSON API route
        fetch(`/bookings/${bookingId}/json`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.booking) {
                window.renderBookingModalData(data.booking);
                spinner.classList.add('d-none');
                content.classList.remove('d-none');
            } else {
                alert('Unable to load booking details.');
            }
        })
        .catch(err => {
            console.error('Error fetching booking details:', err);
            // Fallback if object was passed
            if (typeof bookingOrId === 'object' && bookingOrId !== null) {
                window.renderBookingModalData(bookingOrId);
                spinner.classList.add('d-none');
                content.classList.remove('d-none');
            } else {
                alert('Failed to load booking details: ' + err.message);
            }
        });
    };

    /**
     * Populate modal elements with full booking object.
     */
    window.renderBookingModalData = function(b) {
        window.currentModalBooking = b;

        // Header & Badges
        document.getElementById('modal_booking_id').textContent = '#' + (b.booking_id || b.id);
        const pnrStr = b.airline_pnr ? `Airline: ${b.airline_pnr}` : (b.gk_pnr ? `GK: ${b.gk_pnr}` : 'PNR: N/A');
        document.getElementById('modal_pnr_badge').textContent = pnrStr;
        
        const bStatus = (b.booking_status || 'booking_generated').replace(/_/g, ' ').toUpperCase();
        document.getElementById('modal_booking_status_badge').textContent = bStatus;
        
        const pStatus = (b.payment_status || 'pending').toUpperCase();
        document.getElementById('modal_payment_status_badge').textContent = 'PAYMENT: ' + pStatus;

        const caseBadge = document.getElementById('modal_case_status_badge');
        if (b.case_status) {
            caseBadge.textContent = 'CASE: ' + b.case_status.toUpperCase();
            caseBadge.classList.remove('d-none');
        } else {
            caseBadge.classList.add('d-none');
        }

        const disputeBadge = document.getElementById('modal_dispute_type_badge');
        if (disputeBadge) {
            if (b.dispute_type) {
                disputeBadge.textContent = 'DISPUTE: ' + b.dispute_type.toUpperCase();
                disputeBadge.classList.remove('d-none');
            } else {
                disputeBadge.classList.add('d-none');
            }
        }

        const disputeSelect = document.getElementById('modal_dispute_type_select');
        const disputeForm = document.getElementById('modal_dispute_type_form');
        if (disputeSelect && disputeForm) {
            disputeSelect.value = b.dispute_type ? b.dispute_type.toUpperCase() : '';
            disputeForm.action = `/bookings/${b.id}/update-dispute-type`;
        }

        const dispLabel = document.getElementById('modal_dispute_type_badge_label');
        const dispNone = document.getElementById('modal_dispute_type_none_label');
        if (dispLabel && dispNone) {
            if (b.dispute_type) {
                dispLabel.textContent = b.dispute_type.toUpperCase();
                dispLabel.classList.remove('d-none');
                dispNone.classList.add('d-none');
            } else {
                dispLabel.classList.add('d-none');
                dispNone.classList.remove('d-none');
            }
        }

        document.getElementById('modal_edit_btn').href = `/admin/bookings/${b.id}/edit`;

        // Quick Remark Form Action
        const remarkForm = document.getElementById('modal_quick_remark_form');
        if (remarkForm) {
            remarkForm.action = `/admin/bookings/${b.id}/add-admin-remark`;
        }

        // Section 1: General
        document.getElementById('modal_created_at').textContent = 'Created: ' + (b.created_at ? new Date(b.created_at).toLocaleString() : 'N/A');
        document.getElementById('modal_ref_id').textContent = b.booking_id || 'N/A';
        document.getElementById('modal_booking_date').textContent = b.booking_date ? String(b.booking_date).slice(0, 10) : 'N/A';
        
        const agentName = b.agent ? (b.agent.alias_name || b.agent.name) : 'N/A';
        document.getElementById('modal_agent_name').textContent = agentName;
        
        const ticketingAgent = b.ticketing_user ? (b.ticketing_user.alias_name || b.ticketing_user.name) : 'Unassigned';
        document.getElementById('modal_ticketing_agent').textContent = ticketingAgent;

        document.getElementById('modal_merchant').textContent = b.merchant_profile ? b.merchant_profile.name : (b.merchant || 'N/A');
        document.getElementById('modal_portal').textContent = b.booking_portal || 'N/A';
        document.getElementById('modal_call_vertical').textContent = `${(b.call_type || 'N/A').toUpperCase()} / ${(b.vertical || 'N/A').toUpperCase()}`;
        document.getElementById('modal_trip_type').textContent = (b.trip_type || 'N/A').replace(/_/g, ' ');

        // Section 2: Customer & Billing
        document.getElementById('modal_customer_name').textContent = b.card_holder_name || (b.passengers && b.passengers.length > 0 ? `${b.passengers[0].first_name} ${b.passengers[0].last_name}` : 'N/A');
        document.getElementById('modal_customer_email').textContent = b.email_address || 'N/A';
        document.getElementById('modal_customer_phone').textContent = b.billing_phone || b.calling_number || 'N/A';
        document.getElementById('modal_billing_address').textContent = b.billing_address || 'N/A';
        document.getElementById('modal_card_type').textContent = (b.card_type || 'CARD').toUpperCase();
        document.getElementById('modal_card_last4').textContent = b.card_last_4 || '0000';
        document.getElementById('modal_card_exp').textContent = 'Exp: ' + (b.card_expiration || 'N/A');

        // Section 3: Financials
        const curr = b.currency || 'USD';
        document.getElementById('modal_currency').textContent = curr;
        document.getElementById('modal_total_amount').textContent = `${curr} ${Number(b.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        const merchantAmtElem = document.getElementById('modal_merchant_amount');
        if (merchantAmtElem) {
            const totalAmt = Number(b.total_amount || 0);
            const paidAir = Number(b.paid_to_airline || 0);
            const mAmount = b.company_card_used ? totalAmt : Math.max(0, totalAmt - paidAir);
            merchantAmtElem.textContent = `${curr} ${mAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` + (b.company_card_used ? ' (Full)' : '');
        }
        document.getElementById('modal_paid_airline').textContent = `${curr} ${Number(b.paid_to_airline || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modal_total_mco').textContent = `${curr} ${Number(b.total_mco || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        const coCardElem = document.getElementById('modal_company_card');
        if (coCardElem) {
            if (b.company_card_used && Number(b.company_card_amount || 0) > 0) {
                coCardElem.textContent = `${curr} ${Number(b.company_card_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                coCardElem.className = 'h6 fw-bold text-warning-emphasis font-monospace mb-0';
            } else {
                coCardElem.textContent = 'None';
                coCardElem.className = 'small text-muted font-monospace mb-0';
            }
        }

        const infoRow = document.getElementById('modal_payment_info_row');
        const infoText = document.getElementById('modal_payment_info');
        if (b.payment_info) {
            infoText.textContent = b.payment_info;
            infoRow.classList.remove('d-none');
        } else {
            infoRow.classList.add('d-none');
        }

        // Section 4: Passengers
        const paxList = document.getElementById('modal_passengers_list');
        paxList.innerHTML = '';
        const passengers = b.passengers || [];
        document.getElementById('modal_pax_count').textContent = `${passengers.length} Passenger(s)`;

        if (passengers.length === 0) {
            paxList.innerHTML = `<tr><td colspan="6" class="px-3 py-3 text-center text-secondary fst-italic">No passenger details recorded.</td></tr>`;
        } else {
            passengers.forEach((p, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 py-2 text-secondary font-monospace fw-bold">${idx + 1}</td>
                    <td class="px-3 py-2 fw-bold text-dark">${p.title || ''} ${p.first_name || ''} ${p.middle_name || ''} ${p.last_name || ''}</td>
                    <td class="px-3 py-2"><span class="badge bg-light text-dark border">${p.gender || 'N/A'}</span></td>
                    <td class="px-3 py-2 font-monospace small">${p.dob ? String(p.dob).slice(0, 10) : 'N/A'}</td>
                    <td class="px-3 py-2 font-monospace fw-bold text-primary">${p.ticket_number || 'N/A'}</td>
                    <td class="px-3 py-2 font-monospace text-success fw-bold">${p.seat_number || 'N/A'}</td>
                `;
                paxList.appendChild(tr);
            });
        }

        // Section 5: Flight Segments
        const flightsList = document.getElementById('modal_flights_list');
        flightsList.innerHTML = '';
        const flights = (b.flight_segments && b.flight_segments.length > 0) ? b.flight_segments : (b.booking_flights || []);
        document.getElementById('modal_flights_count').textContent = `${flights.length} Segment(s)`;

        if (flights.length === 0) {
            flightsList.innerHTML = `<tr><td colspan="7" class="px-3 py-3 text-center text-secondary fst-italic">No flight itinerary segments attached.</td></tr>`;
        } else {
            flights.forEach((fl, idx) => {
                const tr = document.createElement('tr');
                const dep = fl.departure_time ? new Date(fl.departure_time).toLocaleString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : 'N/A';
                const arr = fl.arrival_time ? new Date(fl.arrival_time).toLocaleString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : 'N/A';
                tr.innerHTML = `
                    <td class="px-3 py-2 text-secondary font-monospace fw-bold">${idx + 1}</td>
                    <td class="px-3 py-2"><strong class="text-primary font-monospace">${fl.operating_carrier || ''} ${fl.flight_number || ''}</strong></td>
                    <td class="px-3 py-2 fw-bold text-dark font-monospace">${fl.origin_airport || fl.origin || 'N/A'} &rarr; ${fl.destination_airport || fl.destination || 'N/A'}</td>
                    <td class="px-3 py-2 small text-dark">${dep}</td>
                    <td class="px-3 py-2 small text-dark">${arr}</td>
                    <td class="px-3 py-2 font-monospace"><span class="badge bg-light text-dark border">${fl.booking_class || 'Y'}</span></td>
                    <td class="px-3 py-2 small"><span class="badge bg-success-subtle text-success border border-success-subtle">${fl.status || 'Confirmed'}</span></td>
                `;
                flightsList.appendChild(tr);
            });
        }

        // Section 6: Remarks
        const remarksList = document.getElementById('modal_remarks_list');
        remarksList.innerHTML = '';
        const remarks = b.booking_remarks || [];

        if (remarks.length === 0) {
            remarksList.innerHTML = `<div class="text-secondary small fst-italic">No remarks logged yet.</div>`;
        } else {
            remarks.forEach(r => {
                const div = document.createElement('div');
                div.className = 'p-2.5 bg-light rounded border border-light-subtle';
                const author = r.user ? (r.user.alias_name || r.user.name) : 'System';
                const dt = r.created_at ? new Date(r.created_at).toLocaleString() : '';
                
                let attachmentsHtml = '';
                if (r.attachments_data && r.attachments_data.length > 0) {
                    attachmentsHtml = '<div class="mt-2 pt-2 border-top border-light-subtle d-flex flex-wrap gap-2">';
                    r.attachments_data.forEach(file => {
                        const fileUrl = file.file_url || (file.file_path ? '/storage/' + file.file_path.replace(/^\/+/, '') : '#');
                        const isImg = file.file_type === 'image' || /\.(png|jpe?g|webp|gif|svg)$/i.test(file.original_name || file.file_path || '');
                        if (isImg) {
                            attachmentsHtml += `<a href="${fileUrl}" target="_blank"><img src="${fileUrl}" alt="Attachment" class="rounded border shadow-sm" style="max-height: 90px; max-width: 130px; object-fit: cover;"></a>`;
                        } else {
                            attachmentsHtml += `<a href="${fileUrl}" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 font-monospace small"><i class="bi bi-file-earmark-pdf-fill me-1"></i> ${file.original_name || 'Document.pdf'}</a>`;
                        }
                    });
                    attachmentsHtml += '</div>';
                }

                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center text-secondary mb-1 small">
                        <strong class="text-primary">${author}</strong>
                        <span class="font-monospace text-muted" style="font-size: 0.75rem;">${dt}</span>
                    </div>
                    <div class="small text-dark font-medium">${r.remark}</div>
                    ${attachmentsHtml}
                `;
                remarksList.appendChild(div);
            });
        }
    };

    /**
     * Print handler for modal content.
     */
    window.printModalBooking = function() {
        const content = document.getElementById('modal_booking_content');
        if (!content) return;
        const printWin = window.open('', '_blank');
        printWin.document.write(`
            <html>
                <head>
                    <title>Booking Details Print</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>body { font-family: sans-serif; padding: 20px; }</style>
                </head>
                <body>
                    <h3>Booking Detail Record - ${document.getElementById('modal_booking_id').textContent}</h3>
                    <hr>
                    ${content.innerHTML}
                    <script>window.onload = function() { window.print(); window.close(); }<\/script>
                </body>
            </html>
        `);
        printWin.document.close();
    };
</script>
