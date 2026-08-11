@extends('layouts.app')

@section('content')
<div x-data="bookingForm()" x-init="if (passengers.length === 0) addPassenger();" class="container-fluid px-0 pb-5">
    
    <!-- ============================================================ -->
    <!-- PAGE HEADER -->
    <!-- ============================================================ -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h4 fw-bold text-dark mb-0">Create New Flight Booking</h1>
                </div>
            </div>
            
            <div>
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    @if(isset($duplicateBooking) && $duplicateBooking)
        <!-- DUPLICATE BOOKING ALERT BANNER -->
        <div class="alert alert-info border-info d-flex align-items-center justify-content-between mb-4 py-3 px-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-files fs-3 text-info"></i>
                <div>
                    <div class="fw-bold text-dark fs-6">Duplicating Booking #{{ $duplicateBooking->booking_id }}</div>
                    <div class="small text-secondary">
                        Originally created by <strong>{{ $duplicateBooking->agent ? ($duplicateBooking->agent->alias_name ?: $duplicateBooking->agent->name) : 'Agent' }}</strong>. 
                        All data has been pre-filled. A new unique Booking ID will be generated upon saving and assigned to you.
                    </div>
                </div>
            </div>
            <span class="badge bg-info text-white font-monospace px-3 py-2">DUPLICATE MODE</span>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- PNR AUTO-FILL TOOLBAR (MULTI-LINE SMART IMPORT) -->
    <!-- ============================================================ -->
    <div class="card bg-white border-primary-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-9 col-lg-10">
                    <textarea x-model="rawPnr" rows="3" placeholder="Paste full GDS raw text lines here (e.g. 1 DL 450 Y 12OCT JFKLAX HK1 0800 1130...)" class="form-control font-monospace text-success small"></textarea>
                </div>
                <div class="col-md-3 col-lg-2 d-flex align-items-end">
                    <button type="button" @click="parsePnr()" :disabled="parsing" class="btn btn-primary w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <template x-if="parsing">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </template>
                        <span x-text="parsing ? 'Parsing...' : 'Auto-Fill Fields'"></span>
                        <i x-show="!parsing" class="bi bi-arrow-right-short fs-5"></i>
                    </button>
                </div>
            </div>

            <!-- Status Alert Messages -->
            <div x-show="parseError" class="alert alert-danger d-flex align-items-center gap-2 mb-0 mt-3 py-2 small d-none" x-transition>
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span x-text="parseError"></span>
            </div>
            <div x-show="parseSuccess" class="alert alert-success d-flex align-items-center gap-2 mb-0 mt-3 py-2 small d-none" x-transition>
                <i class="bi bi-check-circle-fill"></i>
                <span>PNR parsed successfully! Flight segments and passenger roster updated.</span>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN WORKSPACE FORM -->
    <!-- ============================================================ -->
    <form @submit.prevent="submitBooking()" action="{{ route('bookings.store') }}" method="POST">
        @csrf

        <!-- Hidden Global Form Inputs -->
        <input type="hidden" name="language" value="English">
        <input type="hidden" name="call_type" x-model="formData.call_type">

        <div class="row g-4">
            
            <!-- LEFT COLUMN: FORM SECTIONS (8 COLUMNS) -->
            <div class="col-lg-8">

                <!-- SECTION 1: Flight & PNR Information -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex align-items-center gap-2">
                        <i class="bi bi-airplane-fill text-primary"></i>
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase">1. Flight & PNR Information</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @if(Auth::check() && (Auth::user()->hasAnyRole(['admin', 'manager']) || in_array(Auth::user()->role, ['admin', 'manager'])))
                                <div class="col-md-3">
                                    <label class="form-label text-warning-emphasis small fw-bold text-uppercase">Assign Agent <span class="text-danger">*</span></label>
                                    <select name="agent_id" x-model="formData.agent_id" required class="form-select border-warning fw-semibold">
                                        <option value="">-- Select Agent --</option>
                                        @if(isset($agents))
                                            @foreach($agents as $ag)
                                                <option value="{{ $ag->id }}">{{ $ag->alias_name ?: $ag->name }} ({{ $ag->email }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Booking Date <span class="text-danger">*</span></label>
                                <input type="date" name="booking_date" x-model="formData.booking_date" required class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Call Type <span class="text-danger">*</span></label>
                                <select name="call_type" x-model="formData.call_type" required class="form-select">
                                    <option value="">-- Select Call Type * --</option>
                                    <option value="meta">Meta</option>
                                    <option value="ppc">PPC</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Service Provided <span class="text-danger">*</span></label>
                                <select name="service_provided" x-model="formData.service_provided" required class="form-select">
                                    <option value="new_booking">New Booking</option>
                                    <option value="exchange">Exchange</option>
                                    <option value="cancellation">Cancellation</option>
                                    <option value="refund">Refund</option>
                                    <option value="seat_selection">Seat Selection</option>
                                    <option value="baggage_addition">Baggage Edition</option>
                                    <option value="others">Others</option>
                                    <option value="cancel_and_refund">Cancel and Refund</option>
                                    <option value="name_correction">Name Correction</option>
                                    <option value="flight_upgrade">Flight Upgrade</option>
                                    <option value="dob_correction">D.O.B Correction</option>
                                    <option value="pet_in_cabin">Pet In Cabin</option>
                                    <option value="ancillary_refund">Ancillary Refund</option>
                                    <option value="infant_ticket">Infant Ticket</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Booking Portal <span class="text-danger">*</span></label>
                                <select name="booking_portal" x-model="formData.booking_portal" required class="form-select">
                                    <option value="website">Website</option>
                                    <option value="gds">GDS</option>
                                    <option value="amadeus">Amadeus</option>
                                    <option value="galileo">Galileo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Vertical <span class="text-danger">*</span></label>
                                <select name="vertical" x-model="formData.vertical" required class="form-select">
                                    <option value="flight">Flight</option>
                                    <option value="hotel">Hotel</option>
                                    <option value="car">Car</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Trip Type</label>
                                <select name="trip_type" x-model="formData.trip_type" class="form-select font-semibold text-primary">
                                    <option value="one_way">One Way</option>
                                    <option value="round_trip">Round Trip</option>
                                    <option value="multi_city">Multi City</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Airline PNR</label>
                                <input type="text" name="airline_pnr" x-model="formData.airline_pnr" class="form-control text-uppercase font-monospace fw-bold text-primary" placeholder="PNR123">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">GK PNR</label>
                                <input type="text" name="gk_pnr" x-model="formData.gk_pnr" class="form-control text-uppercase font-monospace fw-bold text-warning-emphasis" placeholder="GK7788">
                            </div>
                            <div class="col-md-3 d-flex align-items-center">
                                <span class="text-warning-emphasis small fst-italic"><i class="bi bi-info-circle me-1"></i> At least one of Airline PNR or GK PNR is required.</span>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Airline Name</label>
                                <input type="text" name="airline_name" x-model="formData.airline_name" class="form-control" placeholder="American Airlines">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Airline Code</label>
                                <input type="text" name="airline_code" x-model="formData.airline_code" class="form-control text-uppercase font-monospace" placeholder="AA">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Travel Date</label>
                                <input type="date" name="travel_date" x-model="formData.travel_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Customer & Billing Authorization -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-credit-card-2-front-fill text-success"></i>
                            <h2 class="h6 font-bold text-dark mb-0 text-uppercase">2. Customer &amp; Billing Authorization</h2>
                        </div>
                        <!-- Radio Toggle: Single Card vs Multiple Cards -->
                        <div class="d-flex align-items-center gap-3 bg-light px-3 py-1.5 rounded-pill border border-light-subtle">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="payment_mode_radio" id="pm_single" value="single" x-model="paymentMode">
                                <label class="form-check-label text-dark small fw-bold" for="pm_single">Single Card</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="payment_mode_radio" id="pm_multiple" value="multiple" x-model="paymentMode">
                                <label class="form-check-label text-primary small fw-bold" for="pm_multiple"><i class="bi bi-stack me-1"></i> Multiple Cards</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Customer Email <span class="text-danger">*</span></label>
                                <input type="email" name="email_address" x-model="formData.email_address" required class="form-control" placeholder="customer@example.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Primary Card Holder Name</label>
                                <input type="text" name="card_holder_name" x-model="formData.card_holder_name" class="form-control" placeholder="John Doe">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Card Type</label>
                                <select name="card_type" x-model="formData.card_type" class="form-select">
                                    <option value="">-- Select Card Type --</option>
                                    <option value="Visa">Visa</option>
                                    <option value="Mastercard">Mastercard</option>
                                    <option value="American Express">American Express</option>
                                    <option value="Discover">Discover</option>
                                    <option value="Diners Club">Diners Club</option>
                                    <option value="JCB">JCB</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Card Last 4 Digits <span class="text-danger">*</span></label>
                                <input type="text" name="card_last_4" x-model="formData.card_last_4" maxlength="4" minlength="4" required class="form-control font-monospace" placeholder="4321">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Expiration (MM/YY)</label>
                                <input type="text" name="card_expiration" x-model="formData.card_expiration" maxlength="7" class="form-control font-monospace" placeholder="12/28">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Calling Phone</label>
                                <input type="text" name="calling_number" x-model="callingNumber" @input="syncPhoneNumbers" class="form-control" placeholder="+15551234567">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Billing Phone</label>
                                <input type="text" name="billing_phone" x-model="billingPhone" class="form-control" placeholder="+15551234567">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Billing Address</label>
                                <input type="text" name="billing_address" x-model="formData.billing_address" class="form-control" placeholder="Full billing address (Street, City, State, Zip Code, Country)">
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check p-3 bg-light rounded border border-light-subtle w-100">
                                    <input type="hidden" name="email_auth_taken" value="0">
                                    <input type="checkbox" name="email_auth_taken" value="1" x-model="formData.email_auth_taken" id="email_auth_taken" class="form-check-input me-2">
                                    <label for="email_auth_taken" class="form-check-label text-dark small fw-semibold cursor-pointer">
                                        Email Auth Already Taken
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between align-items-center mb-1">
                                    <span>Payment Info Notes / Remarks</span>
                                </label>
                                <textarea name="payment_info" x-model="formData.payment_info" rows="2" class="form-control" placeholder="Enter payment info notes & remarks..."></textarea>
                            </div>
                        </div>

                        <!-- MULTIPLE CARDS ROSTER SECTION -->
                        <div x-show="paymentMode === 'multiple'" class="mt-4 pt-4 border-top border-light-subtle" x-cloak>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-primary fw-bold mb-0 text-uppercase d-flex align-items-center gap-2">
                                        <i class="bi bi-credit-card-2-back-fill"></i> Multiple Payment Cards Roster
                                    </h6>
                                    <small class="text-secondary">Manage all credit/debit cards authorized for this booking.</small>
                                </div>
                                <button type="button" @click="multiCardsModalOpen = true" class="btn btn-outline-primary btn-sm fw-bold">
                                    <i class="bi bi-plus-circle me-1"></i> Add Card to Roster
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-secondary border-bottom">
                                            <th>Card Holder Name</th>
                                            <th>Card Type</th>
                                            <th style="width: 120px;">Last 4</th>
                                            <th style="width: 140px;">Expiration</th>
                                            <th style="width: 70px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(card, cIndex) in bookingCards" :key="cIndex">
                                            <tr>
                                                <td>
                                                    <input type="text" :name="`booking_cards[${cIndex}][card_holder_name]`" x-model="card.card_holder_name" placeholder="Card Holder Name" class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <select :name="`booking_cards[${cIndex}][card_type]`" x-model="card.card_type" class="form-select form-select-sm">
                                                        <option value="Visa">Visa</option>
                                                        <option value="Mastercard">Mastercard</option>
                                                        <option value="American Express">American Express</option>
                                                        <option value="Discover">Discover</option>
                                                        <option value="Diners Club">Diners Club</option>
                                                        <option value="JCB">JCB</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" :name="`booking_cards[${cIndex}][card_last_4]`" x-model="card.card_last_4" maxlength="4" placeholder="4321" class="form-control form-control-sm font-monospace text-center">
                                                </td>
                                                <td>
                                                    <input type="text" :name="`booking_cards[${cIndex}][card_expiration]`" x-model="card.card_expiration" placeholder="12/28" class="form-control form-control-sm font-monospace text-center">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" @click="removeBookingCard(cIndex)" class="btn btn-outline-danger btn-sm p-1 px-2" title="Remove Card">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="bookingCards.length === 0">
                                            <td colspan="5" class="text-center text-secondary small py-3 fst-italic">
                                                No additional cards added yet. Click <strong>Add Card to Roster</strong> above to add card details.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Passenger Roster -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill text-primary"></i>
                            <h2 class="h6 font-bold text-dark mb-0 text-uppercase">3. Passenger Roster <span class="text-danger">*</span></h2>
                        </div>
                        <button type="button" @click="addPassenger()" class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="bi bi-plus-lg me-1"></i> Add Passenger
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-secondary border-bottom">
                                        <th style="width: 70px;" class="text-center">Pax</th>
                                        <th style="width: 90px;">Title</th>
                                        <th>First Name <span class="text-danger">*</span></th>
                                        <th>Middle Name <span class="text-secondary small font-normal">(Opt)</span></th>
                                        <th>Last Name</th>
                                        <th style="width: 140px;">DOB <span class="text-danger">*</span></th>
                                        <th>Ticket #</th>
                                        <th style="width: 90px;">Seat</th>
                                        <th style="width: 60px;" class="text-center">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(pax, index) in passengers" :key="index">
                                        <tr>
                                            <td class="text-center">
                                                <input type="text" :name="`passengers[${index}][pax_index]`" x-model="pax.pax_index" placeholder="P1" class="form-control form-control-sm text-center font-monospace fw-bold text-primary">
                                            </td>
                                            <td>
                                                <select :name="`passengers[${index}][title]`" x-model="pax.title" class="form-select form-select-sm">
                                                    <option value="">Title</option>
                                                    <option value="MR">MR</option>
                                                    <option value="MRS">MRS</option>
                                                    <option value="MS">MS</option>
                                                    <option value="CHD">CHD</option>
                                                    <option value="INF">INF</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" :name="`passengers[${index}][first_name]`" x-model="pax.first_name" required placeholder="First Name" class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <input type="text" :name="`passengers[${index}][middle_name]`" x-model="pax.middle_name" placeholder="Middle Name" class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <input type="text" :name="`passengers[${index}][last_name]`" x-model="pax.last_name" placeholder="Last Name" class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <input type="date" :name="`passengers[${index}][dob]`" x-model="pax.dob" required class="form-control form-control-sm font-monospace">
                                            </td>
                                            <td>
                                                <input type="text" :name="`passengers[${index}][ticket_number]`" x-model="pax.ticket_number" placeholder="Ticket #" class="form-control form-control-sm font-monospace">
                                            </td>
                                            <td>
                                                <input type="text" :name="`passengers[${index}][seat_number]`" x-model="pax.seat_number" placeholder="14B" class="form-control form-control-sm text-center font-monospace">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" @click="removePassenger(index)" class="btn btn-outline-danger btn-sm p-1 px-2" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3.5: Flight Segments Roster -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-airplane-engines-fill text-primary"></i>
                            <h2 class="h6 font-bold text-dark mb-0 text-uppercase">Itinerary Flight Segments</h2>
                            <span class="badge bg-primary-subtle text-primary font-monospace ms-2" x-text="`${flights.length} Segment(s)`"></span>
                        </div>
                        <button type="button" @click="addFlight()" class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="bi bi-plus-lg me-1"></i> Add Flight Segment
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-secondary border-bottom">
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="width: 85px;">Carrier</th>
                                        <th style="width: 95px;">Flight #</th>
                                        <th style="width: 80px;">Origin</th>
                                        <th style="width: 80px;">Dest</th>
                                        <th>Departure</th>
                                        <th>Arrival</th>
                                        <th style="width: 85px;">Class</th>
                                        <th style="width: 100px;">Status</th>
                                        <th style="width: 50px;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(fl, index) in flights" :key="index">
                                        <tr>
                                            <td class="text-center font-monospace fw-bold text-secondary" x-text="index + 1"></td>
                                            <td>
                                                <input type="text" x-model="fl.operating_carrier" placeholder="UA" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary">
                                            </td>
                                            <td>
                                                <input type="text" x-model="fl.flight_number" placeholder="354" class="form-control form-control-sm font-monospace text-uppercase">
                                            </td>
                                            <td>
                                                <input type="text" x-model="fl.origin_airport" placeholder="CMH" class="form-control form-control-sm font-monospace text-uppercase fw-bold">
                                            </td>
                                            <td>
                                                <input type="text" x-model="fl.destination_airport" placeholder="LAX" class="form-control form-control-sm font-monospace text-uppercase fw-bold">
                                            </td>
                                            <td>
                                                <input type="datetime-local" x-model="fl.departure_time" class="form-control form-control-sm small">
                                            </td>
                                            <td>
                                                <input type="datetime-local" x-model="fl.arrival_time" class="form-control form-control-sm small">
                                            </td>
                                            <td>
                                                <input type="text" x-model="fl.booking_class" placeholder="Y" class="form-control form-control-sm font-monospace text-uppercase">
                                            </td>
                                            <td>
                                                <input type="text" x-model="fl.status" placeholder="Confirmed" class="form-control form-control-sm">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" @click="removeFlight(index)" class="btn btn-outline-danger btn-sm p-1 px-2" title="Remove Segment">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="flights.length === 0">
                                        <td colspan="10" class="text-center py-3 text-secondary small fst-italic">
                                            No flight segments added yet. Click "Auto-Fill Fields" or "Add Flight Segment" above.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Financial Breakdown & MCO Calculation -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-currency-dollar text-warning-emphasis"></i>
                            <h2 class="h6 font-bold text-dark mb-0 text-uppercase">4. Financial Breakdown & MCO Calculation</h2>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-secondary fw-bold">MCO Profit:</span>
                            <span class="badge fs-6 font-monospace" :class="Number(formData.total_mco) >= 0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'" x-text="`${formData.currency} ${Number(formData.total_mco || 0).toFixed(2)}`"></span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Currency <span class="text-danger">*</span></label>
                                <input type="text" name="currency" x-model="formData.currency" required class="form-control font-monospace fw-bold" placeholder="USD">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Merchant <span class="text-danger">*</span></label>
                                <select name="merchant" x-model="formData.merchant" required class="form-select fw-semibold">
                                    <option value="">-- Select Merchant * --</option>
                                    @forelse($merchants as $m)
                                        <option value="{{ $m->name }}">{{ $m->name }}</option>
                                    @empty
                                        <option value="Travelomile">Travelomile</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Total Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="total_amount" x-model="formData.total_amount" @input="calculateMco()" required class="form-control font-monospace fw-bold text-dark" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Paid to Airline <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="paid_to_airline" x-model="formData.paid_to_airline" @input="calculateMco()" required class="form-control font-monospace" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Total MCO <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="total_mco" x-model="formData.total_mco" required class="form-control font-monospace fw-bold text-success bg-success bg-opacity-10 border-success border-opacity-50" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Payment Status <span class="text-danger">*</span></label>
                                <select name="payment_status" x-model="formData.payment_status" required class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="received">Received</option>
                                    <option value="refund">Refund</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Initial Remark</label>
                                <input type="text" name="initial_remark" x-model="initialRemark" placeholder="Add notes..." class="form-control mb-2">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Attachments (PDF/Images)</label>
                                <input type="file" name="initial_attachments[]" multiple accept=".pdf,image/png,image/jpeg,image/jpg,image/webp" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: STICKY SIDEBAR (4 COLUMNS) -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 80px;">

                    <!-- LIVE TICKET PREVIEW CARD -->
                    <div class="card bg-white border-light-subtle shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-1 rounded-circle bg-success d-inline-block"></span>
                                <h3 class="h6 font-bold text-dark mb-0 text-uppercase">Live e-Ticket Preview</h3>
                            </div>
                            <span class="badge bg-light text-primary border border-primary-subtle font-monospace" x-text="`PNR: ${formData.airline_pnr || formData.gk_pnr || 'NONE'}`"></span>
                        </div>
                        <div class="card-body p-3">
                            <div class="p-3 bg-light rounded border border-light-subtle mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small" x-text="formData.airline_name || 'Airline Not Set'"></span>
                                    <span class="badge bg-primary-subtle text-primary font-monospace" x-text="formData.airline_code || 'XX'"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-secondary fw-semibold">
                                    <div>
                                        <span class="text-primary fw-bold" x-text="formData.from_airport || 'ORIG'"></span>
                                        <span x-text="`(${formData.from_city || 'Origin'})`"></span>
                                    </div>
                                    <i class="bi bi-arrow-right text-primary"></i>
                                    <div>
                                        <span class="text-primary fw-bold" x-text="formData.to_airport || 'DEST'"></span>
                                        <span x-text="`(${formData.to_city || 'Destination'})`"></span>
                                    </div>
                                </div>
                            </div>

                            <div x-show="flights.length > 0">
                                <div class="small fw-bold text-secondary text-uppercase mb-2">Flight Segments (<span x-text="flights.length"></span>)</div>
                                <template x-for="(fl, idx) in flights" :key="idx">
                                    <div class="p-2.5 bg-light rounded border border-light-subtle mb-2 small">
                                        <div class="d-flex justify-content-between font-bold text-dark mb-1">
                                            <span x-text="`${fl.operating_carrier || formData.airline_code || ''} ${fl.flight_number}`"></span>
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" x-text="fl.cabin || 'Economy'"></span>
                                        </div>
                                        <div class="d-flex justify-content-between text-secondary">
                                            <span x-text="`${fl.origin_airport} → ${fl.destination_airport}`"></span>
                                            <span x-text="formatDateHeader(fl.departure_time)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- FINANCIAL SUMMARY CARD -->
                    <div class="card bg-white border-light-subtle shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3">
                            <h3 class="h6 font-bold text-dark mb-0 text-uppercase">Financial Summary</h3>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-group list-group-flush bg-transparent small mb-3">
                                <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between border-light-subtle py-2">
                                    <span>Passengers Count:</span>
                                    <strong class="text-dark" x-text="passengers.length"></strong>
                                </li>
                                <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between border-light-subtle py-2">
                                    <span>Total Customer Charge:</span>
                                    <strong class="text-dark font-monospace" x-text="`${formData.currency} ${Number(formData.total_amount || 0).toFixed(2)}`"></strong>
                                </li>
                                <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between border-light-subtle py-2">
                                    <span>Airline Cost:</span>
                                    <strong class="text-secondary font-monospace" x-text="`${formData.currency} ${Number(formData.paid_to_airline || 0).toFixed(2)}`"></strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between pt-3 border-0">
                                    <span class="fw-bold text-warning-emphasis text-uppercase">Calculated MCO Margin:</span>
                                    <strong class="text-success font-monospace fs-5" x-text="`${formData.currency} ${Number(formData.total_mco || 0).toFixed(2)}`"></strong>
                                </li>
                            </ul>

                            <button type="submit" :disabled="submitting" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <template x-if="!submitting">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check2-circle fs-5"></i>
                                        <span>Save & Generate Booking</span>
                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        <span>Processing Booking...</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Hidden Flight Form Serialization -->
        <template x-for="(fl, index) in flights" :key="index">
            <div>
                <input type="hidden" :name="`flights[${index}][flight_number]`" x-model="fl.flight_number">
                <input type="hidden" :name="`flights[${index}][operating_carrier]`" x-model="fl.operating_carrier">
                <input type="hidden" :name="`flights[${index}][airline_name]`" x-model="fl.airline_name">
                <input type="hidden" :name="`flights[${index}][airline_logo]`" x-model="fl.airline_logo">
                <input type="hidden" :name="`flights[${index}][operated_by]`" x-model="fl.operated_by">
                <input type="hidden" :name="`flights[${index}][operated_by_logo]`" x-model="fl.operated_by_logo">
                <input type="hidden" :name="`flights[${index}][origin_airport]`" x-model="fl.origin_airport">
                <input type="hidden" :name="`flights[${index}][origin_city]`" x-model="fl.origin_city">
                <input type="hidden" :name="`flights[${index}][origin_airport_name]`" x-model="fl.origin_airport_name">
                <input type="hidden" :name="`flights[${index}][destination_airport]`" x-model="fl.destination_airport">
                <input type="hidden" :name="`flights[${index}][destination_city]`" x-model="fl.destination_city">
                <input type="hidden" :name="`flights[${index}][destination_airport_name]`" x-model="fl.destination_airport_name">
                <input type="hidden" :name="`flights[${index}][departure_time]`" x-model="fl.departure_time">
                <input type="hidden" :name="`flights[${index}][arrival_time]`" x-model="fl.arrival_time">
                <input type="hidden" :name="`flights[${index}][booking_class]`" x-model="fl.booking_class">
                <input type="hidden" :name="`flights[${index}][cabin]`" x-model="fl.cabin">
                <input type="hidden" :name="`flights[${index}][aircraft_type]`" x-model="fl.aircraft_type">
                <input type="hidden" :name="`flights[${index}][status]`" x-model="fl.status">
                <input type="hidden" :name="`flights[${index}][flight_duration]`" x-model="fl.flight_duration">
                <input type="hidden" :name="`flights[${index}][day_offset]`" x-model="fl.day_offset">
                <input type="hidden" :name="`flights[${index}][transit_text]`" x-model="fl.transit_text">
            </div>
        </template>
    </form>

    <!-- Add Card Modal -->
    <div x-show="multiCardsModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': multiCardsModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card bg-white border-0 shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card text-primary"></i> Add Payment Card to Booking
                    </h5>
                    <button type="button" @click="multiCardsModalOpen = false" class="btn-close"></button>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Card Holder Name</label>
                        <input type="text" x-model="newCardHolderName" placeholder="John Doe" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Card Type</label>
                        <select x-model="newCardType" class="form-select">
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="American Express">American Express</option>
                            <option value="Discover">Discover</option>
                            <option value="Diners Club">Diners Club</option>
                            <option value="JCB">JCB</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Card Last 4 Digits <span class="text-danger">*</span></label>
                            <input type="text" x-model="newCardLast4" maxlength="4" placeholder="4321" class="form-control font-monospace">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Expiration (MM/YY)</label>
                            <input type="text" x-model="newCardExpiration" placeholder="12/28" class="form-control font-monospace">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-top border-light-subtle d-flex justify-content-end gap-2 py-3">
                    <button type="button" @click="multiCardsModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                    <button type="button" @click="saveModalCard()" class="btn btn-primary btn-sm px-4 fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Add Card
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function bookingForm() {
        const dup = @json($duplicateBooking ?? null);

        const initialFormData = dup ? {
            agent_id: '',
            booking_date: new Date().toISOString().split('T')[0],
            call_type: dup.call_type || '',
            vertical: dup.vertical || 'flight',
            trip_type: dup.trip_type || 'one_way',
            service_provided: dup.service_provided || 'new_booking',
            booking_portal: dup.booking_portal || 'website',
            language: dup.language || 'English',
            travel_date: dup.travel_date ? String(dup.travel_date).slice(0, 10) : '',
            gk_pnr: dup.gk_pnr || '',
            airline_pnr: dup.airline_pnr || '',
            airline_code: dup.airline_code || '',
            airline_name: dup.airline_name || '',
            from_airport: dup.from_airport || '',
            to_airport: dup.to_airport || '',
            from_city: dup.from_city || '',
            to_city: dup.to_city || '',
            card_holder_name: dup.card_holder_name || '',
            card_type: dup.card_type || '',
            billing_address: dup.billing_address || '',
            card_last_4: dup.card_last_4 || '',
            card_expiration: dup.card_expiration || '',
            email_address: dup.email_address || '',
            booking_status: 'booking_generated',
            case_status: dup.case_status || '',
            email_auth_taken: false,
            currency: dup.currency || 'USD',
            merchant: dup.merchant || @json($merchants->first()?->name ?? 'Travelomile'),
            total_amount: dup.total_amount ? parseFloat(dup.total_amount) : 0.00,
            paid_to_airline: dup.paid_to_airline ? parseFloat(dup.paid_to_airline) : 0.00,
            total_mco: dup.total_mco ? parseFloat(dup.total_mco) : 0.00,
            payment_status: 'pending',
            payment_info: dup.payment_info || ''
        } : {
            agent_id: '',
            booking_date: new Date().toISOString().split('T')[0],
            call_type: '',
            vertical: 'flight',
            trip_type: 'one_way',
            service_provided: 'new_booking',
            booking_portal: 'website',
            language: 'English',
            travel_date: '',
            gk_pnr: '',
            airline_pnr: '',
            airline_code: '',
            airline_name: '',
            from_airport: '',
            to_airport: '',
            from_city: '',
            to_city: '',
            card_holder_name: '',
            card_type: '',
            billing_address: '',
            card_last_4: '',
            card_expiration: '',
            email_address: '',
            booking_status: 'booking_generated',
            case_status: '',
            email_auth_taken: false,
            currency: 'USD',
            merchant: @json($merchants->first()?->name ?? 'Travelomile'),
            total_amount: 0.00,
            paid_to_airline: 0.00,
            total_mco: 0.00,
            payment_status: 'pending',
            payment_info: ''
        };

        const initialPax = [];
        if (dup && dup.passengers && dup.passengers.length > 0) {
            dup.passengers.forEach(p => {
                initialPax.push({
                    pax_index: p.pax_index || `P${initialPax.length + 1}`,
                    title: p.title || '',
                    first_name: p.first_name || '',
                    middle_name: p.middle_name || '',
                    last_name: p.last_name || '',
                    dob: p.dob ? String(p.dob).slice(0, 10) : '',
                    ticket_number: p.ticket_number || '',
                    seat_number: p.seat_number || ''
                });
            });
        }

        const initialFlights = [];
        const dupSegments = (dup && dup.flight_segments && dup.flight_segments.length > 0) 
            ? dup.flight_segments 
            : (dup && dup.booking_flights ? dup.booking_flights : []);

        if (dupSegments && dupSegments.length > 0) {
            dupSegments.forEach(fl => {
                initialFlights.push({
                    flight_number: fl.flight_number || '',
                    operating_carrier: fl.operating_carrier || '',
                    airline_name: fl.airline_name || '',
                    airline_logo: fl.airline_logo || '',
                    operated_by: fl.operated_by || '',
                    operated_by_logo: fl.operated_by_logo || '',
                    origin_airport: fl.origin_airport || fl.origin || '',
                    origin_city: fl.origin_city || '',
                    origin_airport_name: fl.origin_airport_name || '',
                    destination_airport: fl.destination_airport || fl.destination || '',
                    destination_city: fl.destination_city || '',
                    destination_airport_name: fl.destination_airport_name || '',
                    departure_time: fl.departure_time ? String(fl.departure_time).slice(0, 16) : '',
                    arrival_time: fl.arrival_time ? String(fl.arrival_time).slice(0, 16) : '',
                    day_offset: fl.day_offset || 0,
                    booking_class: fl.booking_class || '',
                    cabin: fl.cabin || '',
                    aircraft_type: fl.aircraft_type || '',
                    status: fl.status || 'Confirmed',
                    flight_duration: fl.flight_duration || '',
                    transit_text: fl.transit_text || ''
                });
            });
        }

        const initialBookingCards = [];
        if (dup && dup.booking_cards && dup.booking_cards.length > 0) {
            dup.booking_cards.forEach(c => {
                initialBookingCards.push({
                    card_holder_name: c.card_holder_name || '',
                    card_type: c.card_type || 'Visa',
                    card_last_4: c.card_last_4 || '',
                    card_expiration: c.card_expiration || ''
                });
            });
        }

        return {
            rawPnr: '',
            parsing: false,
            parseError: '',
            parseSuccess: false,
            hasParsedPnr: !!dup,
            callingNumber: dup ? (dup.calling_number || '') : '',
            billingPhone: dup ? (dup.billing_phone || '') : '',

            parsedSummary: {
                total_trip_duration: '',
                origin_city: dup ? (dup.from_city || '') : '',
                destination_city: dup ? (dup.to_city || '') : ''
            },

            formData: initialFormData,

            passengers: initialPax,
            flights: initialFlights,

            paymentMode: (initialBookingCards.length > 0) ? 'split' : 'single',
            bookingCards: initialBookingCards,
            multiCardsModalOpen: false,

            newCardHolderName: '',
            newCardType: 'Visa',
            newCardLast4: '',
            newCardExpiration: '',

            addBookingCard(card = {}) {
                this.bookingCards.push({
                    card_holder_name: card.card_holder_name || '',
                    card_type: card.card_type || 'Visa',
                    card_last_4: card.card_last_4 || '',
                    card_expiration: card.card_expiration || ''
                });
            },

            removeBookingCard(index) {
                this.bookingCards.splice(index, 1);
            },

            saveModalCard() {
                if (!this.newCardLast4 || this.newCardLast4.length !== 4) {
                    alert('Please enter a valid 4-digit card number.');
                    return;
                }
                this.addBookingCard({
                    card_holder_name: this.newCardHolderName || this.formData.card_holder_name,
                    card_type: this.newCardType,
                    card_last_4: this.newCardLast4,
                    card_expiration: this.newCardExpiration
                });
                this.newCardLast4 = '';
                this.newCardExpiration = '';
                this.multiCardsModalOpen = false;
            },

            get routeSummaryText() {
                const from = this.formData.from_city || this.formData.from_airport || 'Origin';
                const to = this.formData.to_city || this.formData.to_airport || 'Destination';
                return `${from} → ${to}`;
            },

            calculateMco() {
                const total = parseFloat(this.formData.total_amount) || 0;
                const airline = parseFloat(this.formData.paid_to_airline) || 0;
                this.formData.total_mco = (total - airline).toFixed(2);
            },

            addPassenger(pax = {}) {
                this.passengers.push({
                    pax_index: pax.pax_index || `P${this.passengers.length + 1}`,
                    title: pax.title || '',
                    first_name: pax.first_name || '',
                    middle_name: pax.middle_name || '',
                    last_name: pax.last_name || '',
                    dob: pax.dob ? String(pax.dob).slice(0, 10) : '',
                    ticket_number: pax.ticket_number || '',
                    seat_number: pax.seat_number || ''
                });
            },

            removePassenger(index) {
                if (this.passengers.length > 1) {
                    this.passengers.splice(index, 1);
                } else {
                    alert('At least one passenger is required for every booking.');
                }
            },

            addFlight(fl = {}) {
                this.flights.push({
                    flight_number: fl.flight_number || '',
                    operating_carrier: fl.operating_carrier || '',
                    airline_name: fl.airline_name || '',
                    airline_logo: fl.airline_logo || '',
                    operated_by: fl.operated_by || '',
                    operated_by_logo: fl.operated_by_logo || '',
                    origin_airport: fl.origin_airport || fl.origin || '',
                    origin_city: fl.origin_city || '',
                    origin_airport_name: fl.origin_airport_name || '',
                    destination_airport: fl.destination_airport || fl.destination || '',
                    destination_city: fl.destination_city || '',
                    destination_airport_name: fl.destination_airport_name || '',
                    departure_time: fl.departure_time ? fl.departure_time.slice(0, 16) : '',
                    arrival_time: fl.arrival_time ? fl.arrival_time.slice(0, 16) : '',
                    day_offset: fl.day_offset || 0,
                    booking_class: fl.booking_class || '',
                    cabin: fl.cabin || '',
                    aircraft_type: fl.aircraft_type || '',
                    status: fl.status || 'Confirmed',
                    flight_duration: fl.flight_duration || '',
                    transit_text: fl.transit_text || ''
                });
            },

            removeFlight(index) {
                this.flights.splice(index, 1);
            },

            syncPhoneNumbers() {
                if (!this.billingPhone) {
                    this.billingPhone = this.callingNumber;
                }
            },

            formatDateHeader(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
            },

            formatTime(dateTimeStr) {
                if (!dateTimeStr) return '';
                const d = new Date(dateTimeStr);
                if (isNaN(d.getTime())) return dateTimeStr;
                return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            },

            async parsePnr() {
                if (!this.rawPnr) {
                    this.parseError = 'Please paste raw PNR text first.';
                    return;
                }

                this.parsing = true;
                this.parseError = '';
                this.parseSuccess = false;

                try {
                    const response = await axios.post('/api/pnr/parse', {
                        raw_pnr: this.rawPnr
                    });

                    if (response.data && response.data.success) {
                        const parsed = response.data.data;
                        
                        this.formData.airline_code = parsed.airline_code || '';
                        this.formData.airline_name = parsed.airline_name || '';
                        this.formData.airline_pnr = parsed.airline_pnr || '';
                        this.formData.trip_type = parsed.trip_type || 'one_way';
                        this.formData.from_airport = parsed.origin_airport || '';
                        this.formData.to_airport = parsed.destination_airport || '';
                        this.formData.from_city = parsed.origin_city || '';
                        this.formData.to_city = parsed.destination_city || '';
                        this.formData.travel_date = parsed.travel_date ? parsed.travel_date.slice(0, 10) : '';

                        this.parsedSummary.total_trip_duration = parsed.total_trip_duration || '';
                        this.parsedSummary.origin_city = parsed.origin_city || '';
                        this.parsedSummary.destination_city = parsed.destination_city || '';

                        this.passengers = [];
                        if (parsed.passengers && parsed.passengers.length > 0) {
                            parsed.passengers.forEach(p => this.addPassenger(p));
                        } else {
                            this.addPassenger();
                        }

                        this.flights = [];
                        if (parsed.flights && parsed.flights.length > 0) {
                            parsed.flights.forEach(f => this.addFlight(f));
                        } else {
                            this.addFlight();
                        }

                        this.parseSuccess = true;
                        this.hasParsedPnr = true;
                    } else {
                        this.parseError = 'Failed to parse PNR. Service returned success = false.';
                    }
                } catch (error) {
                    console.error(error);
                    this.parseError = error.response?.data?.error || 'An error occurred while calling the PNR parser service.';
                } finally {
                    this.parsing = false;
                }
            },

            submitting: false,
            initialRemark: dup ? ('Duplicated from Booking #' + dup.booking_id) : '',

            async submitBooking() {
                if (this.submitting) return;
                this.submitting = true;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                    const formDataObj = new FormData();
                    formDataObj.append('_token', csrfToken);

                    Object.keys(this.formData).forEach(key => {
                        if (key === 'email_auth_taken') {
                            formDataObj.append(key, this.formData[key] ? '1' : '0');
                        } else if (this.formData[key] !== null && this.formData[key] !== undefined) {
                            formDataObj.append(key, this.formData[key]);
                        }
                    });
                    formDataObj.append('initial_remark', this.initialRemark || '');

                    this.passengers.forEach((pax, idx) => {
                        Object.keys(pax).forEach(pKey => {
                            if (pax[pKey] !== null && pax[pKey] !== undefined) {
                                formDataObj.append(`passengers[${idx}][${pKey}]`, pax[pKey]);
                            }
                        });
                    });

                    this.flights.forEach((flt, idx) => {
                        Object.keys(flt).forEach(fKey => {
                            if (flt[fKey] !== null && flt[fKey] !== undefined) {
                                formDataObj.append(`flights[${idx}][${fKey}]`, flt[fKey]);
                            }
                        });
                    });

                    if (this.bookingCards && this.bookingCards.length > 0) {
                        this.bookingCards.forEach((card, idx) => {
                            Object.keys(card).forEach(cKey => {
                                if (card[cKey] !== null && card[cKey] !== undefined) {
                                    formDataObj.append(`booking_cards[${idx}][${cKey}]`, card[cKey]);
                                }
                            });
                        });
                    }

                    const fileInput = document.querySelector('input[name="initial_attachments[]"]');
                    if (fileInput && fileInput.files.length > 0) {
                        Array.from(fileInput.files).forEach(file => {
                            formDataObj.append('initial_attachments[]', file);
                        });
                    }

                    const response = await axios.post('/bookings', formDataObj, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Booking Created!',
                            text: `Booking reference #${response.data.booking_id || ''} has been generated successfully.`,
                            confirmButtonColor: '#0d6efd',
                            background: '#ffffff',
                            color: '#1e293b'
                        }).then(() => {
                            window.location.href = response.data.redirect || '/bookings';
                        });
                    }
                } catch (error) {
                    this.submitting = false;
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors || {};
                        let errorMessages = [];
                        Object.values(errors).forEach(errList => {
                            if (Array.isArray(errList)) {
                                errList.forEach(msg => {
                                    if (!errorMessages.includes(msg)) {
                                        errorMessages.push(msg);
                                    }
                                });
                            }
                        });

                        const listHtml = errorMessages.map(msg => `<li class="mb-1.5">${msg}</li>`).join('');

                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Required',
                            html: `<div class="text-start text-warning small mt-2"><ul class="mb-0 ps-3">${listHtml}</ul></div>`,
                            confirmButtonColor: '#dc3545',
                            background: '#ffffff',
                            color: '#1e293b'
                        });
                    } else if (error.response && error.response.status === 419) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Session Expired',
                            text: 'Your security token or session expired. Please refresh the page to reload your session.',
                            showCancelButton: true,
                            confirmButtonText: 'Refresh Page',
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d',
                            background: '#ffffff',
                            color: '#1e293b'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Error',
                            text: error.response?.data?.message || 'An unexpected error occurred while saving the booking.',
                            confirmButtonColor: '#dc3545',
                            background: '#ffffff',
                            color: '#1e293b'
                        });
                    }
                }
            }
        };
    }
</script>
@endsection