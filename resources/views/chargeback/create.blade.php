@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 py-2" style="max-width: 1300px;">
    <!-- Top Nav Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary btn-sm px-2.5 py-1.5 rounded-3">
                    <i class="bi bi-arrow-left"></i> Back to Control Panel
                </a>
                <h1 class="h4 mb-0 fw-bold text-dark">Register New Chargeback Dispute</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Auto-fetch booking record via Booking ID or Airline PNR and log dispute parameters.</p>
        </div>
    </div>

    <!-- 1. AUTO-FETCH BOOKING LOOKUP SECTION -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white border-top border-4 border-danger">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-lightning-charge-fill text-danger"></i> 1. Booking Auto-Fetch Lookup
            </h6>
            <span class="badge bg-danger-subtle text-danger font-monospace">Quick Auto-Fill</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Enter Booking ID or Airline PNR / GK PNR</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary font-monospace"><i class="bi bi-search"></i></span>
                        <input type="text" id="lookup_input" placeholder="e.g. 7-digit Booking ID (ABC1234) or Airline PNR (e.g. W9Q7KL)" class="form-control font-monospace text-uppercase fw-bold">
                    </div>
                    <div class="form-text small text-muted">Type either the system Booking ID or the airline PNR and click 'Auto-Fetch Details'.</div>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="button" id="btn_lookup" onclick="fetchBookingDetails()" class="btn btn-danger btn-sm fw-bold px-4 py-2 flex-fill shadow-sm">
                        <i class="bi bi-cloud-arrow-down me-1"></i> Auto-Fetch Details
                    </button>
                    <button type="button" onclick="clearLookup()" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Fetch Feedback Alert -->
            <div id="lookup_feedback" class="mt-3 d-none"></div>
        </div>
    </div>

    <!-- MAIN DISPUTE FORM -->
    <form action="{{ route('chargeback.store') }}" method="POST" enctype="multipart/form-data" id="chargeback_form">
        @csrf
        <!-- Hidden Booking Association -->
        <input type="hidden" name="booking_id" id="form_booking_id" value="{{ old('booking_id') }}">

        <!-- 2. CASE & PORTAL IDENTIFICATION -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-shield-check text-primary"></i> 2. Portal &amp; Dispute Identification
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Case Number -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Case Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="case_number" value="{{ old('case_number') }}" required placeholder="e.g. CBK-982341" class="form-control font-monospace fw-bold">
                        @error('case_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Case Type -->
                    <div class="col-md-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Case Type <span class="text-danger">*</span>
                        </label>
                        <select name="case_type" required class="form-select">
                            <option value="new" {{ old('case_type', 'new') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="old" {{ old('case_type') === 'old' ? 'selected' : '' }}>Old</option>
                        </select>
                        @error('case_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Portal -->
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-0">
                                Portal <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-link p-0 text-primary small fw-semibold text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalAddPortal">
                                <i class="bi bi-plus-circle me-1"></i> Add New Portal
                            </button>
                        </div>
                        <select name="portal" id="portal_select" required class="form-select font-monospace fw-semibold">
                            <option value="">-- Select Portal --</option>
                            @foreach($portals as $portal)
                                <option value="{{ $portal->name }}" {{ old('portal') === $portal->name ? 'selected' : '' }}>{{ $portal->name }}</option>
                            @endforeach
                        </select>
                        @error('portal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Dispute Type -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Dispute Type <span class="text-danger">*</span>
                        </label>
                        <select name="dispute_type" required class="form-select font-monospace fw-bold">
                            <option value="CHARGEBACK" {{ strtoupper(old('dispute_type', 'CHARGEBACK')) === 'CHARGEBACK' ? 'selected' : '' }}>CHARGEBACK</option>
                            <option value="RDR" {{ strtoupper(old('dispute_type', '')) === 'RDR' ? 'selected' : '' }}>RDR</option>
                            <option value="ALERT" {{ strtoupper(old('dispute_type', '')) === 'ALERT' ? 'selected' : '' }}>ALERT</option>
                            <option value="RETRIEVAL" {{ strtoupper(old('dispute_type', '')) === 'RETRIEVAL' ? 'selected' : '' }}>RETRIEVAL</option>
                        </select>
                        @error('dispute_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Booking Reference -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Reference</label>
                        <input type="text" name="booking_reference" id="form_booking_ref" value="{{ old('booking_reference') }}" placeholder="Auto-filled from Booking" class="form-control font-monospace bg-light">
                    </div>

                    <!-- Airline PNR -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Airline PNR</label>
                        <input type="text" name="pnr" id="form_pnr" value="{{ old('pnr') }}" placeholder="Auto-filled from Booking" class="form-control font-monospace text-uppercase bg-light">
                    </div>

                    <!-- Agent Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent Name</label>
                        <input type="text" name="agent_name" id="form_agent_name" value="{{ old('agent_name') }}" placeholder="Auto-filled from Booking" class="form-control bg-light">
                    </div>

                    <!-- SDS -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SDS</label>
                        <input type="number" name="sds" value="{{ old('sds', 1) }}" class="form-control font-monospace">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. DATES TIMELINE -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-calendar3 text-warning"></i> 3. Key Dates &amp; Deadlines
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Received Date -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Received Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="received_date" id="form_received_date" value="{{ old('received_date', date('Y-m-d')) }}" required onchange="updateReceivedMonth(this.value)" class="form-control">
                        @error('received_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Received Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Received Month <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="received_month" id="form_received_month" value="{{ old('received_month', date('Y-m')) }}" required placeholder="YYYY-MM" class="form-control font-monospace">
                        @error('received_month')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Deadline Date (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1 d-flex justify-content-between">
                            <span>Deadline Date</span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Optional</span>
                        </label>
                        <input type="date" name="deadline_date" id="form_deadline_date" value="{{ old('deadline_date') }}" class="form-control">
                        @error('deadline_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Action Taken Date (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1 d-flex justify-content-between">
                            <span>Action Taken Date</span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Optional</span>
                        </label>
                        <input type="date" name="action_taken_date" id="form_action_date" value="{{ old('action_taken_date', date('Y-m-d')) }}" class="form-control">
                        @error('action_taken_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Booking Date -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Date</label>
                        <input type="date" name="booking_date" id="form_booking_date" value="{{ old('booking_date') }}" onchange="updateBookingMonth(this.value)" class="form-control bg-light">
                    </div>

                    <!-- Booking Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Month</label>
                        <input type="text" name="booking_month" id="form_booking_month" value="{{ old('booking_month') }}" placeholder="YYYY-MM" class="form-control font-monospace bg-light">
                    </div>

                    <!-- Shift Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Shift Month (Optional)</label>
                        <input type="text" name="shift_month" value="{{ old('shift_month') }}" placeholder="e.g. YYYY-MM or YYYY-MM-DD" class="form-control font-monospace">
                    </div>

                    <!-- Statement Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Statement Month (Optional)</label>
                        <input type="text" name="statement_month" value="{{ old('statement_month') }}" placeholder="e.g. YYYY-MM or YYYY-MM-DD" class="form-control font-monospace">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. STATUSES & FINANCIALS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-currency-dollar text-success"></i> 4. Statuses &amp; Financial Parameters
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Current Status (Combines All Statuses) -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Current Status <span class="text-danger">*</span>
                        </label>
                        <select name="current_status" required class="form-select font-semibold">
                            <option value="Chargeback received" {{ old('current_status', 'Chargeback received') === 'Chargeback received' ? 'selected' : '' }}>Chargeback received</option>
                            <option value="Proceed with chargeback" {{ old('current_status') === 'Proceed with chargeback' ? 'selected' : '' }}>Proceed with chargeback</option>
                            <option value="Represent" {{ old('current_status') === 'Represent' ? 'selected' : '' }}>Represent</option>
                            <option value="Accepted" {{ old('current_status') === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="Declined" {{ old('current_status') === 'Declined' ? 'selected' : '' }}>Declined</option>
                            <option value="Won" {{ old('current_status') === 'Won' ? 'selected' : '' }}>Won</option>
                            <option value="Lost" {{ old('current_status') === 'Lost' ? 'selected' : '' }}>Lost</option>
                            <option value="RDR-Lost" {{ old('current_status') === 'RDR-Lost' ? 'selected' : '' }}>RDR-Lost</option>
                            <option value="Recharge" {{ old('current_status') === 'Recharge' ? 'selected' : '' }}>Recharge</option>
                            <option value="Reversed" {{ old('current_status') === 'Reversed' ? 'selected' : '' }}>Reversed</option>
                            <option value="Refunded" {{ old('current_status') === 'Refunded' ? 'selected' : '' }}>Refunded</option>
                            <option value="Voided" {{ old('current_status') === 'Voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                        @error('current_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Disputed Amount -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Disputed Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold" id="currency_label">USD</span>
                            <input type="number" step="0.01" name="disputed_amount" id="form_disputed_amount" value="{{ old('disputed_amount') }}" required placeholder="0.00" class="form-control font-monospace fw-bold text-danger">
                        </div>
                        @error('disputed_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Total Booking Amount -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total Booking Amount</label>
                        <input type="number" step="0.01" name="total_booking_amount" id="form_total_amount" value="{{ old('total_booking_amount', '0.00') }}" placeholder="0.00" class="form-control font-monospace bg-light">
                    </div>

                    <!-- Currency (Hidden) -->
                    <input type="hidden" name="currency" id="form_currency" value="{{ old('currency', 'USD') }}">
                </div>
            </div>
        </div>

        <!-- 5. CARD, REASONS & OPERATIONAL DETAILS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-credit-card-2-front text-info"></i> 5. Card, Reasons &amp; Operational Info
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- CC Brand -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">CC Brand / Card Type</label>
                        <select name="cc_brand" id="form_cc_brand" class="form-select">
                            <option value="">-- Select Brand --</option>
                            <option value="Visa" {{ old('cc_brand') === 'Visa' ? 'selected' : '' }}>Visa</option>
                            <option value="MasterCard" {{ old('cc_brand') === 'MasterCard' ? 'selected' : '' }}>MasterCard</option>
                            <option value="American Express" {{ old('cc_brand') === 'American Express' ? 'selected' : '' }}>American Express</option>
                            <option value="Discover" {{ old('cc_brand') === 'Discover' ? 'selected' : '' }}>Discover</option>
                            <option value="Diners Club" {{ old('cc_brand') === 'Diners Club' ? 'selected' : '' }}>Diners Club</option>
                            <option value="Other" {{ old('cc_brand') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <!-- Card No (Last 4) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Card Last 4 Digits</label>
                        <input type="text" name="card_no" id="form_card_no" maxlength="4" value="{{ old('card_no') }}" placeholder="e.g. 4321" class="form-control font-monospace">
                    </div>

                    <!-- Vertical -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Vertical</label>
                        <input type="text" name="vertical" id="form_vertical" value="{{ old('vertical', 'Flight') }}" class="form-control">
                    </div>

                    <!-- Service Provided -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Service Provided</label>
                        <input type="text" name="service_provided" id="form_service_provided" value="{{ old('service_provided') }}" placeholder="e.g. Flight Booking" class="form-control">
                    </div>

                    <!-- Reason Code -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reason Code</label>
                        <input type="text" name="reason_code" value="{{ old('reason_code') }}" placeholder="e.g. 10.4, 4837, Fraud" class="form-control font-monospace">
                    </div>

                    <!-- Shift Time -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Shift Time</label>
                        <input type="time" name="shift_time" value="{{ old('shift_time') }}" class="form-control">
                    </div>

                    <!-- Reason Description -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reason Description for Chargeback</label>
                        <textarea name="reason_description" rows="2" placeholder="Provide detailed chargeback dispute description..." class="form-control">{{ old('reason_description') }}</textarea>
                    </div>

                    <!-- Image Attachments (Images Only) -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-images text-danger me-1"></i> Attach Dispute Images / Screenshots (Optional)</span>
                            <span class="badge bg-light text-danger border border-danger-subtle" style="font-size: 0.72rem;">Images Only (PNG, JPG, WEBP) &bull; No PDF</span>
                        </label>
                        <input type="file" name="attachments[]" id="form_attachments" multiple accept="image/png,image/jpeg,image/jpg,image/webp" onchange="handleImageFileSelection(this)" class="form-control">
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i>Usually the chargeback desk adds a single file, but you can attach multiple images if needed. Only image files (PNG, JPG, JPEG, WEBP) are allowed. PDF files are not accepted.
                        </div>
                        @error('attachments')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                        <!-- Live Preview Thumbnail Gallery -->
                        <div id="image_preview_gallery" class="row g-2 mt-2 d-none"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. LINKED BOOKING PASSENGERS & REMARKS PREVIEW -->
        <div id="booking_details_card" class="card border-0 shadow-sm rounded-3 mb-4 bg-white d-none">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-people-fill text-primary"></i> 6. Passengers &amp; Existing Booking Remarks
                </h6>
                <span class="badge bg-primary-subtle text-primary font-monospace" id="preview_pax_count">0 Passenger(s)</span>
            </div>
            <div class="card-body p-4">
                <!-- Passengers Table -->
                <div class="mb-4">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-2">Passengers on Booking</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>Passenger Full Name</th>
                                    <th>Gender</th>
                                    <th>Date of Birth</th>
                                    <th>Ticket Number</th>
                                    <th>Seat</th>
                                </tr>
                            </thead>
                            <tbody id="preview_passengers_tbody">
                                <tr><td colspan="6" class="text-center text-muted fst-italic">No passenger details available.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Existing Remarks List -->
                <div>
                    <h6 class="text-secondary small fw-bold text-uppercase mb-2">Existing Remarks on Booking</h6>
                    <div id="preview_remarks_list" class="vstack gap-2 bg-light p-3 rounded border border-light-subtle" style="max-height: 220px; overflow-y: auto;">
                        <span class="text-muted small fst-italic">No existing remarks found for this booking.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. ACTIONS & OPTIONS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="update_booking_status" id="update_booking_status" value="1" checked>
                            <label class="form-check-label fw-bold text-dark" for="update_booking_status">
                                Automatically update linked booking status to <span class="badge bg-danger text-white">CHARGEBACK</span>
                            </label>
                        </div>
                        <small class="text-muted d-block">If enabled, the linked booking record will immediately reflect the 'chargeback' booking status.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary fw-semibold px-4 py-2">Cancel</a>
                        <button type="submit" class="btn btn-danger fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-shield-lock-fill me-1"></i> Save Chargeback Record
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal: Add New Portal -->
<div class="modal fade" id="modalAddPortal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom border-light-subtle py-3">
                <h5 class="modal-title h6 fw-bold text-dark mb-0">
                    <i class="bi bi-plus-circle-fill text-primary me-1"></i> Add New Chargeback Portal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddPortalInline" onsubmit="submitNewPortalInline(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Portal Name <span class="text-danger">*</span></label>
                        <input type="text" id="inline_portal_name" required placeholder="e.g. AMEX - DISPUTE, TOM - ALERT" class="form-control">
                    </div>
                    <div id="inlinePortalAlert" class="alert d-none py-2 small mb-0"></div>
                </div>
                <div class="modal-footer bg-light border-top border-light-subtle py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitPortalInline" class="btn btn-primary btn-sm fw-bold px-3">
                        <i class="bi bi-save me-1"></i> Save &amp; Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateReceivedMonth(dateVal) {
        if (dateVal) {
            document.getElementById('form_received_month').value = dateVal.slice(0, 7);
        }
    }

    function updateBookingMonth(dateVal) {
        if (dateVal) {
            document.getElementById('form_booking_month').value = dateVal.slice(0, 7);
        }
    }

    function clearLookup() {
        document.getElementById('lookup_input').value = '';
        document.getElementById('lookup_feedback').className = 'mt-3 d-none';
        document.getElementById('booking_details_card').classList.add('d-none');
    }

    async function fetchBookingDetails() {
        const query = document.getElementById('lookup_input').value.trim();
        const feedback = document.getElementById('lookup_feedback');
        const btn = document.getElementById('btn_lookup');

        if (!query) {
            feedback.className = 'alert alert-warning py-2 small mb-0 mt-3 d-block';
            feedback.textContent = 'Please enter a Booking ID or Airline PNR to look up.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Fetching...';
        feedback.className = 'alert alert-info py-2 small mb-0 mt-3 d-block';
        feedback.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Searching booking database...';

        try {
            const url = `{{ route('chargeback.lookup-booking') }}?query=${encodeURIComponent(query)}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const res = await response.json();

            if (response.ok && res.success && res.booking) {
                const b = res.booking;

                // Auto-fill all mapped fields
                document.getElementById('form_booking_id').value = b.id;
                document.getElementById('form_booking_ref').value = b.booking_id;
                document.getElementById('form_pnr').value = b.pnr;
                document.getElementById('form_agent_name').value = b.agent_name;
                document.getElementById('form_currency').value = b.currency;
                document.getElementById('currency_label').textContent = b.currency;
                document.getElementById('form_total_amount').value = b.total_amount;
                
                // Disputed amount pre-filled with total amount if empty
                const currentDisputed = document.getElementById('form_disputed_amount').value;
                if (!currentDisputed || Number(currentDisputed) === 0) {
                    document.getElementById('form_disputed_amount').value = b.total_amount;
                }

                if (b.booking_date) {
                    document.getElementById('form_booking_date').value = b.booking_date;
                    document.getElementById('form_booking_month').value = b.booking_month;
                }

                if (b.card_no) {
                    document.getElementById('form_card_no').value = b.card_no;
                }

                if (b.cc_brand) {
                    const brandSelect = document.getElementById('form_cc_brand');
                    let found = false;
                    for (let i = 0; i < brandSelect.options.length; i++) {
                        if (brandSelect.options[i].value.toLowerCase() === b.cc_brand.toLowerCase()) {
                            brandSelect.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        brandSelect.value = 'Other';
                    }
                }

                if (b.vertical) {
                    document.getElementById('form_vertical').value = b.vertical;
                }

                if (b.service_provided) {
                    document.getElementById('form_service_provided').value = b.service_provided;
                }

                // Render Passengers Preview
                const paxTbody = document.getElementById('preview_passengers_tbody');
                if (b.passengers && b.passengers.length > 0) {
                    document.getElementById('preview_pax_count').textContent = `${b.passengers.length} Passenger(s)`;
                    paxTbody.innerHTML = b.passengers.map((p, idx) => `
                        <tr>
                            <td>${idx + 1}</td>
                            <td class="fw-bold text-dark">${p.name}</td>
                            <td>${p.gender}</td>
                            <td>${p.dob}</td>
                            <td class="font-monospace">${p.ticket_number}</td>
                            <td class="font-monospace">${p.seat_number}</td>
                        </tr>
                    `).join('');
                } else {
                    document.getElementById('preview_pax_count').textContent = '0 Passenger(s)';
                    paxTbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted fst-italic">No passenger details recorded.</td></tr>';
                }

                // Render Existing Remarks
                const remarksBox = document.getElementById('preview_remarks_list');
                if (b.remarks && b.remarks.length > 0) {
                    remarksBox.innerHTML = b.remarks.map(r => `
                        <div class="p-2 bg-white rounded border border-light-subtle small">
                            <div class="d-flex justify-content-between text-secondary mb-1">
                                <strong class="text-primary">${r.author}</strong>
                                <span class="font-monospace text-muted" style="font-size: 0.72rem;">${r.created_at}</span>
                            </div>
                            <div class="text-dark">${r.remark}</div>
                        </div>
                    `).join('');
                } else {
                    remarksBox.innerHTML = '<span class="text-muted small fst-italic">No existing remarks found for this booking.</span>';
                }

                document.getElementById('booking_details_card').classList.remove('d-none');

                feedback.className = 'alert alert-success py-2 small mb-0 mt-3 d-block';
                feedback.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Successfully fetched Booking <strong>#${b.booking_id}</strong> (PNR: ${b.pnr}) • Total: ${b.currency} ${b.total_amount}. All details auto-filled!`;
            } else {
                feedback.className = 'alert alert-danger py-2 small mb-0 mt-3 d-block';
                feedback.textContent = res.message || 'No booking found matching criteria.';
            }
        } catch (err) {
            feedback.className = 'alert alert-danger py-2 small mb-0 mt-3 d-block';
            feedback.textContent = 'Error connecting to server: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Auto-Fetch Details';
        }
    }

    async function submitNewPortalInline(event) {
        event.preventDefault();
        const input = document.getElementById('inline_portal_name');
        const alertBox = document.getElementById('inlinePortalAlert');
        const btn = document.getElementById('btnSubmitPortalInline');
        const val = input.value.trim();

        if (!val) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        try {
            const response = await fetch("{{ route('chargeback.portals.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ name: val })
            });

            const res = await response.json();

            if (response.ok && res.success && res.portal) {
                // Add new option to dropdown & select it
                const portalSelect = document.getElementById('portal_select');
                const opt = document.createElement('option');
                opt.value = res.portal.name;
                opt.textContent = res.portal.name;
                opt.selected = true;
                portalSelect.appendChild(opt);

                bootstrap.Modal.getInstance(document.getElementById('modalAddPortal')).hide();
                input.value = '';
                alertBox.className = 'alert d-none';
            } else {
                alertBox.className = 'alert alert-danger py-2 small mb-0 d-block';
                alertBox.textContent = res.message || 'Failed to save portal.';
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 small mb-0 d-block';
            alertBox.textContent = 'Server error: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Save & Select';
        }
    }
    function handleImageFileSelection(input) {
        const gallery = document.getElementById('image_preview_gallery');
        gallery.innerHTML = '';

        if (!input.files || input.files.length === 0) {
            gallery.classList.add('d-none');
            return;
        }

        const allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        let hasInvalidFile = false;
        let invalidFileName = '';

        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const ext = file.name.split('.').pop().toLowerCase();

            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(ext)) {
                hasInvalidFile = true;
                invalidFileName = file.name;
                break;
            }
        }

        if (hasInvalidFile) {
            alert(`File "${invalidFileName}" is not a supported image format.\n\nOnly images (PNG, JPG, JPEG, WEBP) are permitted. PDF files are strictly not allowed.`);
            input.value = '';
            gallery.classList.add('d-none');
            return;
        }

        gallery.classList.remove('d-none');
        Array.from(input.files).forEach((file, index) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-lg-2';

            const card = document.createElement('div');
            card.className = 'card h-100 border shadow-xs p-1 position-relative text-center';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'card-img-top rounded object-fit-cover';
            img.style.height = '90px';
            img.style.objectFit = 'cover';

            const label = document.createElement('div');
            label.className = 'small text-truncate px-1 mt-1 text-muted';
            label.style.fontSize = '0.70rem';
            label.textContent = file.name;
            label.title = file.name;

            const sizeBadge = document.createElement('span');
            sizeBadge.className = 'badge bg-light text-secondary border mt-1';
            sizeBadge.style.fontSize = '0.65rem';
            sizeBadge.textContent = (file.size / 1024).toFixed(1) + ' KB';

            card.appendChild(img);
            card.appendChild(label);
            card.appendChild(sizeBadge);
            col.appendChild(card);
            gallery.appendChild(col);
        });
    }
</script>
@endsection
