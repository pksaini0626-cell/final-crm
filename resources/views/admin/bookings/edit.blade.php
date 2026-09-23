@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i> Edit Booking <span class="font-monospace text-primary">#{{ $booking->booking_id }}</span>
            </h1>
            <p class="text-secondary small mb-0">Full override controls for all booking parameters.</p>
        </div>
        <a href="{{ in_array(Auth::user()->role, ['ticketing', 'agent']) ? route('manager.tickets.index') : route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="vstack gap-4">
        @csrf
        @method('PUT')

        <!-- Core System Parameters -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-info"></i> Core Parameters
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Agent -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Assigned Agent <span class="text-danger">*</span></label>
                        <select name="agent_id" required class="form-select">
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ old('agent_id', $booking->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->alias_name }} ({{ $agent->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Merchant -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Merchant Profile</label>
                        <select name="merchant_id" class="form-select">
                            <option value="">No Merchant Assigned</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}" {{ old('merchant_id', $booking->merchant_id) == $merchant->id ? 'selected' : '' }}>{{ $merchant->name }} ({{ $merchant->merchant_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Booking Date -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}" required class="form-control">
                    </div>

                    <!-- Call Type -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Call Type <span class="text-danger">*</span></label>
                        <select name="call_type" required class="form-select">
                            <option value="meta" {{ old('call_type', $booking->call_type) === 'meta' ? 'selected' : '' }}>Meta</option>
                            <option value="ppc" {{ old('call_type', $booking->call_type) === 'ppc' ? 'selected' : '' }}>PPC</option>
                            <option value="other" {{ old('call_type', $booking->call_type) === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <!-- Vertical -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Vertical <span class="text-danger">*</span></label>
                        <input type="text" name="vertical" value="{{ old('vertical', $booking->vertical) }}" required class="form-control">
                    </div>

                    <!-- Trip Type -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Trip Type</label>
                        <select name="trip_type" class="form-select border-info border-opacity-50 text-white fw-bold">
                            <option value="one_way" {{ old('trip_type', $booking->trip_type) === 'one_way' ? 'selected' : '' }}>One Way</option>
                            <option value="round_trip" {{ old('trip_type', $booking->trip_type) === 'round_trip' ? 'selected' : '' }}>Round Trip</option>
                            <option value="multi_city" {{ old('trip_type', $booking->trip_type) === 'multi_city' ? 'selected' : '' }}>Multi City</option>
                        </select>
                    </div>

                    <!-- Service Provided -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Service Provided <span class="text-danger">*</span></label>
                        <input type="text" name="service_provided" value="{{ old('service_provided', $booking->service_provided) }}" required class="form-control">
                    </div>

                    <!-- Portal -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Portal <span class="text-danger">*</span></label>
                        <input type="text" name="booking_portal" value="{{ old('booking_portal', $booking->booking_portal) }}" required class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Statuses & Workflow -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3 text-warning"></i> Statuses & Workflow
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Booking Status -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Status <span class="text-danger">*</span></label>
                        <select name="booking_status" required class="form-select">
                            <option value="booking_generated" {{ old('booking_status', $booking->booking_status) === 'booking_generated' ? 'selected' : '' }}>Booking Generated</option>
                            <option value="email_auth_done" {{ old('booking_status', $booking->booking_status) === 'email_auth_done' ? 'selected' : '' }}>Email Auth Done</option>
                            <option value="ticketed" {{ old('booking_status', $booking->booking_status) === 'ticketed' ? 'selected' : '' }}>Ticketed</option>
                            <option value="booking_complete" {{ old('booking_status', $booking->booking_status) === 'booking_complete' ? 'selected' : '' }}>Booking Complete</option>
                            <option value="void" {{ old('booking_status', $booking->booking_status) === 'void' ? 'selected' : '' }}>Void</option>
                            <option value="failed" {{ old('booking_status', $booking->booking_status) === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="chargeback" {{ old('booking_status', $booking->booking_status) === 'chargeback' ? 'selected' : '' }}>Chargeback</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Payment Status <span class="text-danger">*</span></label>
                        <select name="payment_status" required class="form-select">
                            <option value="pending" {{ old('payment_status', $booking->payment_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="received" {{ old('payment_status', $booking->payment_status) === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="refund" {{ old('payment_status', $booking->payment_status) === 'refund' ? 'selected' : '' }}>Refund</option>
                            <option value="cancelled" {{ old('payment_status', $booking->payment_status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Case Status -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Case Status</label>
                        <select name="case_status" class="form-select border-danger border-opacity-50">
                            <option value="">None</option>
                            <option value="rdr" {{ old('case_status', $booking->case_status) === 'rdr' ? 'selected' : '' }}>RDR</option>
                            <option value="retrieval" {{ old('case_status', $booking->case_status) === 'retrieval' ? 'selected' : '' }}>Retrieval</option>
                            <option value="chargeback" {{ old('case_status', $booking->case_status) === 'chargeback' ? 'selected' : '' }}>Chargeback</option>
                            <option value="refund" {{ old('case_status', $booking->case_status) === 'refund' ? 'selected' : '' }}>Refund</option>
                            <option value="void" {{ old('case_status', $booking->case_status) === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>

                    <!-- Dispute Type (Chargeback Exclusive) -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Dispute Type
                            <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">CHARGEBACK ONLY</span>
                        </label>
                        <div class="p-2 rounded bg-dark border border-secondary text-white font-monospace fw-bold d-flex align-items-center justify-content-between">
                            <span class="text-danger">{{ $booking->dispute_type ? strtoupper($booking->dispute_type) : 'None' }}</span>
                            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Managed Exclusively by Chargeback</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Parameters -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-currency-dollar text-success"></i> Financial Breakdowns
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Currency -->
                    <div class="col-md-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency" value="{{ old('currency', $booking->currency) }}" required class="form-control font-monospace fw-bold">
                    </div>

                    <!-- Total Amount -->
                    <div class="col-md-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}" required class="form-control font-monospace">
                    </div>

                    <!-- Paid to Airline -->
                    <div class="col-md-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Paid to Airline <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid_to_airline" value="{{ old('paid_to_airline', $booking->paid_to_airline) }}" required class="form-control font-monospace">
                    </div>

                    <!-- Total MCO -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total MCO <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_mco" value="{{ old('total_mco', $booking->total_mco) }}" required class="form-control font-monospace fw-bold text-success">
                    </div>

                    <!-- Company Card -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1 d-flex justify-content-between align-items-center">
                            <span>Company Card</span>
                            <span id="company_card_status_badge" class="badge {{ old('company_card_used', $booking->company_card_used) ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-light text-muted border' }}">
                                {{ old('company_card_used', $booking->company_card_used) ? 'Used' : 'Not Used' }}
                            </span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-text bg-white">
                                <input class="form-check-input mt-0" type="checkbox" name="company_card_used" id="edit_company_card_check" value="1" {{ old('company_card_used', $booking->company_card_used) ? 'checked' : '' }} onchange="onCompanyCardCheckChange(this)">
                            </div>
                            <input type="number" step="0.01" min="0" name="company_card_amount" id="edit_company_card_amount" value="{{ old('company_card_amount', number_format((float)$booking->company_card_amount, 2, '.', '')) }}" class="form-control font-monospace fw-bold text-warning-emphasis" placeholder="0.00">
                            <button type="button" class="btn btn-outline-warning" onclick="openEditCompanyCardModal()" title="Open Company Card Popup">
                                <i class="bi bi-credit-card-2-front"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passenger Names & Roster Details -->
        <div class="card bg-white border-light-subtle shadow-sm">
            <div class="card-header bg-white border-bottom border-light-subtle py-3">
                <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary"></i> Passenger Names &amp; Details
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="vstack gap-3">
                    @foreach($booking->passengers as $idx => $pax)
                        <div class="p-3 bg-light rounded border border-light-subtle">
                            <input type="hidden" name="passengers[{{ $idx }}][id]" value="{{ $pax->id }}">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold">
                                    Passenger #{{ $idx + 1 }}
                                </span>
                            </div>
                            <div class="row g-3">
                                <!-- Title -->
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Title</label>
                                    <select name="passengers[{{ $idx }}][title]" class="form-select">
                                        <option value="MR" {{ old("passengers.{$idx}.title", strtoupper($pax->title ?? '')) === 'MR' ? 'selected' : '' }}>MR</option>
                                        <option value="MRS" {{ old("passengers.{$idx}.title", strtoupper($pax->title ?? '')) === 'MRS' ? 'selected' : '' }}>MRS</option>
                                        <option value="MS" {{ old("passengers.{$idx}.title", strtoupper($pax->title ?? '')) === 'MS' ? 'selected' : '' }}>MS</option>
                                        <option value="MISS" {{ old("passengers.{$idx}.title", strtoupper($pax->title ?? '')) === 'MISS' ? 'selected' : '' }}>MISS</option>
                                        <option value="MSTR" {{ old("passengers.{$idx}.title", strtoupper($pax->title ?? '')) === 'MSTR' ? 'selected' : '' }}>MSTR</option>
                                    </select>
                                </div>

                                <!-- First Name -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="passengers[{{ $idx }}][first_name]" value="{{ old("passengers.{$idx}.first_name", $pax->first_name) }}" required class="form-control">
                                </div>

                                <!-- Middle Name -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Middle Name</label>
                                    <input type="text" name="passengers[{{ $idx }}][middle_name]" value="{{ old("passengers.{$idx}.middle_name", $pax->middle_name) }}" class="form-control">
                                </div>

                                <!-- Last Name -->
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="passengers[{{ $idx }}][last_name]" value="{{ old("passengers.{$idx}.last_name", $pax->last_name) }}" required class="form-control">
                                </div>

                                <!-- Gender -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Gender</label>
                                    <select name="passengers[{{ $idx }}][gender]" class="form-select">
                                        <option value="M" {{ old("passengers.{$idx}.gender", $pax->gender) === 'M' ? 'selected' : '' }}>Male (M)</option>
                                        <option value="F" {{ old("passengers.{$idx}.gender", $pax->gender) === 'F' ? 'selected' : '' }}>Female (F)</option>
                                        <option value="O" {{ old("passengers.{$idx}.gender", $pax->gender) === 'O' ? 'selected' : '' }}>Other (O)</option>
                                    </select>
                                </div>

                                <!-- DOB -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date of Birth (DOB)</label>
                                    <input type="date" name="passengers[{{ $idx }}][dob]" value="{{ old("passengers.{$idx}.dob", $pax->dob ? ($pax->dob instanceof \DateTimeInterface ? $pax->dob->format('Y-m-d') : substr($pax->dob, 0, 10)) : '') }}" class="form-control font-monospace">
                                </div>

                                <!-- Ticket Number -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Ticket Number</label>
                                    <input type="text" name="passengers[{{ $idx }}][ticket_number]" value="{{ old("passengers.{$idx}.ticket_number", $pax->ticket_number) }}" class="form-control font-monospace text-primary fw-bold">
                                </div>

                                <!-- Seat Number -->
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Seat Number</label>
                                    <input type="text" name="passengers[{{ $idx }}][seat_number]" value="{{ old("passengers.{$idx}.seat_number", $pax->seat_number) }}" class="form-control font-monospace">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Customer & Airline Identifiers -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-indigo-400"></i> Customer & Airline Identifiers
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Customer Name -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Card Holder Name</label>
                        <input type="text" name="card_holder_name" value="{{ old('card_holder_name', $booking->card_holder_name) }}" class="form-control">
                    </div>

                    <!-- Email -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Customer Email <span class="text-danger">*</span></label>
                        <input type="email" name="email_address" value="{{ old('email_address', $booking->email_address) }}" required class="form-control">
                    </div>

                    <!-- Card Last 4 -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Card Last 4</label>
                        <input type="text" name="card_last_4" value="{{ old('card_last_4', $booking->card_last_4) }}" maxlength="4" class="form-control font-monospace">
                    </div>

                    <!-- Card Type -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Card Type</label>
                        <select name="card_type" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="Visa" {{ old('card_type', $booking->card_type) === 'Visa' ? 'selected' : '' }}>Visa</option>
                            <option value="Mastercard" {{ old('card_type', $booking->card_type) === 'Mastercard' ? 'selected' : '' }}>Mastercard</option>
                            <option value="American Express" {{ old('card_type', $booking->card_type) === 'American Express' ? 'selected' : '' }}>American Express</option>
                            <option value="Discover" {{ old('card_type', $booking->card_type) === 'Discover' ? 'selected' : '' }}>Discover</option>
                            <option value="Diners Club" {{ old('card_type', $booking->card_type) === 'Diners Club' ? 'selected' : '' }}>Diners Club</option>
                            <option value="JCB" {{ old('card_type', $booking->card_type) === 'JCB' ? 'selected' : '' }}>JCB</option>
                            <option value="Other" {{ old('card_type', $booking->card_type) === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <!-- Card Expiration -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Expiration (MM/YY)</label>
                        <input type="text" name="card_expiration" value="{{ old('card_expiration', $booking->card_expiration) }}" placeholder="12/28" class="form-control font-monospace">
                    </div>

                    <!-- Calling Phone -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Calling Phone</label>
                        <input type="text" name="calling_number" value="{{ old('calling_number', $booking->calling_number) }}" class="form-control">
                    </div>

                    <!-- Billing Phone -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Billing Phone</label>
                        <input type="text" name="billing_phone" value="{{ old('billing_phone', $booking->billing_phone) }}" class="form-control">
                    </div>

                    <!-- Billing Address -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Billing Address</label>
                        <input type="text" name="billing_address" value="{{ old('billing_address', $booking->billing_address) }}" class="form-control" placeholder="Full billing address">
                    </div>

                    <!-- Airline PNR -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Airline PNR</label>
                        <input type="text" name="airline_pnr" value="{{ old('airline_pnr', $booking->airline_pnr) }}" class="form-control font-monospace text-uppercase fw-bold text-info">
                    </div>

                    <!-- GK PNR -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">GK PNR</label>
                        <input type="text" name="gk_pnr" value="{{ old('gk_pnr', $booking->gk_pnr) }}" class="form-control font-monospace text-uppercase fw-bold text-warning">
                    </div>

                    <!-- Payment Info (Admin Confidential) -->
                    <div class="col-md-12">
                        <label class="form-label text-warning small fw-bold text-uppercase mb-1">
                            Payment Info Notes <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-2">Admin Only Access</span>
                        </label>
                        <textarea name="payment_info" rows="3" class="form-control border-warning border-opacity-50" placeholder="Confidential admin payment notes...">{{ old('payment_info', $booking->payment_info) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary fw-semibold px-4">Cancel</a>
            <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                <i class="bi bi-save me-1"></i> Save All Changes
            </button>
        </div>
    </form>
</div>

<!-- Company Card Amount Popup Modal -->
<div class="modal fade" id="companyCardEditModal" tabindex="-1" aria-labelledby="companyCardEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card-2-front-fill text-warning"></i> Company Card Used
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-secondary mb-3">
                    Add or update the amount charged on the agency company card for this booking.
                </p>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Company Card Amount ({{ $booking->currency }}) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text font-monospace fw-bold">{{ $booking->currency }}</span>
                        <input type="number" step="0.01" min="0" id="modal_company_card_input" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); applyAndSaveCompanyCard({{ $booking->id }}); }" class="form-control form-control-lg font-monospace fw-bold text-dark" placeholder="0.00">
                    </div>
                </div>
                <div id="company_card_modal_alert" class="alert alert-success py-2 px-3 small d-none mb-0"></div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCompanyCard({{ $booking->id }})">Remove Card</button>
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-bold text-dark" onclick="applyAndSaveCompanyCard({{ $booking->id }})">
                        <i class="bi bi-check-circle me-1"></i> Submit &amp; Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function onCompanyCardCheckChange(checkbox) {
        const amtInput = document.getElementById('edit_company_card_amount');
        const badge = document.getElementById('company_card_status_badge');
        if (checkbox.checked) {
            badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle';
            badge.textContent = 'Used';
            if (!parseFloat(amtInput.value)) {
                openEditCompanyCardModal();
            }
        } else {
            badge.className = 'badge bg-light text-muted border';
            badge.textContent = 'Not Used';
            amtInput.value = '0.00';
        }
    }

    function openEditCompanyCardModal() {
        const amtInput = document.getElementById('edit_company_card_amount');
        const modalInput = document.getElementById('modal_company_card_input');
        modalInput.value = parseFloat(amtInput.value) > 0 ? amtInput.value : '';
        const modalEl = document.getElementById('companyCardEditModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        setTimeout(() => {
            modalInput.focus();
            modalInput.select();
        }, 300);
    }

    function removeCompanyCard(bookingId) {
        document.getElementById('edit_company_card_check').checked = false;
        document.getElementById('edit_company_card_amount').value = '0.00';
        const badge = document.getElementById('company_card_status_badge');
        if (badge) {
            badge.className = 'badge bg-light text-muted border';
            badge.textContent = 'Not Used';
        }
        const modalEl = document.getElementById('companyCardEditModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        if (bookingId) {
            fetch(`/bookings/${bookingId}/company-card`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    company_card_used: 0,
                    company_card_amount: 0
                })
            }).catch(console.error);
        }
    }

    function applyAndSaveCompanyCard(bookingId) {
        const modalInput = document.getElementById('modal_company_card_input');
        const val = parseFloat(modalInput.value);
        if (isNaN(val) || val <= 0) {
            alert('Please enter a valid amount greater than 0, or click Remove Card.');
            return;
        }

        const amtStr = val.toFixed(2);
        document.getElementById('edit_company_card_check').checked = true;
        document.getElementById('edit_company_card_amount').value = amtStr;
        const badge = document.getElementById('company_card_status_badge');
        if (badge) {
            badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle';
            badge.textContent = 'Used';
        }

        // Submit via AJAX to immediately persist if desired
        fetch(`/bookings/${bookingId}/company-card`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                company_card_used: 1,
                company_card_amount: val
            })
        }).then(res => res.json()).then(data => {
            const alertBox = document.getElementById('company_card_modal_alert');
            alertBox.textContent = 'Company card amount saved successfully!';
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                const modalEl = document.getElementById('companyCardEditModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                alertBox.classList.add('d-none');
            }, 600);
        }).catch(err => {
            const modalEl = document.getElementById('companyCardEditModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    }
</script>
@endsection
