@extends('layouts.app')

@section('content')
<div x-data="adminBookingState()">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-primary"></i> Global Booking Management
            </h1>
            <p class="text-secondary small mb-0">Search, filter, export, and manage system-wide booking records &amp; case statuses.</p>
        </div>
        <div>
            <a href="{{ route('admin.bookings.export', request()->query()) }}" class="btn btn-success fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export to CSV
            </a>
        </div>
    </div>

    <!-- Pending Customer Authorization Notifications Banner for Admin/Manager -->
    @if(isset($pendingAuthBookings) && $pendingAuthBookings->count() > 0)
        <div class="card bg-white border-warning-subtle shadow-sm mb-4">
            <div class="card-header bg-warning-subtle border-warning-subtle py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                    <h2 class="h6 font-bold text-warning-emphasis mb-0 text-uppercase">
                        Pending Customer Authorization Notifications ({{ $pendingAuthBookings->count() }})
                    </h2>
                </div>
                <span class="badge bg-warning text-dark border border-warning-subtle small">Customer Authorization Sent — Awaiting Reply &amp; Admin Approval</span>
            </div>

            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($pendingAuthBookings as $pBooking)
                        <div class="col-md-6">
                            <div class="card bg-light border-warning-subtle shadow-sm h-100">
                                <div class="card-body p-3 vstack justify-content-between gap-3">
                                    <div class="vstack gap-1 text-secondary small">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark font-monospace">Booking #{{ $pBooking->booking_id }}</span>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-uppercase">Email Auth Sent</span>
                                        </div>
                                        <div><strong class="text-secondary">PNR:</strong> <span class="text-warning-emphasis font-monospace fw-bold">{{ $pBooking->airline_pnr ?: ($pBooking->gk_pnr ?: 'N/A') }}</span> | <strong class="text-secondary">Airline:</strong> {{ $pBooking->airline_name ?: 'Airline' }}</div>
                                        <div><strong class="text-secondary">Customer Email:</strong> <span class="text-dark fw-semibold">{{ $pBooking->email_address }}</span></div>
                                        <div><strong class="text-secondary">Created By Agent:</strong> <span class="text-dark fw-semibold">{{ $pBooking->agent ? $pBooking->agent->name : 'Agent' }}</span></div>
                                        <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i> Dispatched: {{ $pBooking->updated_at->diffForHumans() }}</div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                                        <a href="{{ route('bookings.index') }}?q={{ $pBooking->booking_id }}" class="btn btn-link btn-sm text-info p-0 text-decoration-none fw-semibold">
                                            <i class="bi bi-eye me-1"></i> View Booking
                                        </a>

                                        <form action="{{ route('bookings.approve-auth', $pBooking->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Confirm customer authorization reply received for Booking #{{ $pBooking->booking_id }}? Status will change to EMAIL AUTH DONE.')" class="btn btn-success btn-sm fw-bold px-3">
                                                <i class="bi bi-check-lg me-1"></i> Approve Auth
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Advanced Multi-Filter Bar -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-white border-light-subtle py-3">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i> Filter &amp; Search Records
            </h2>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.bookings.index') }}" method="GET">
                <div class="row g-3">
                    <!-- Search Query -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Search Query</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Booking ID, PNR, Customer, Email..." class="form-control">
                    </div>

                    <!-- Agent Filter -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">All Agents</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->alias_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>

                    <!-- Date To -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>

                    <!-- Booking Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Status</label>
                        <select name="booking_status" class="form-select">
                            <option value="">All Booking Statuses</option>
                            <option value="booking_generated" {{ request('booking_status') === 'booking_generated' ? 'selected' : '' }}>Booking Generated</option>
                            <option value="email_auth_done" {{ request('booking_status') === 'email_auth_done' ? 'selected' : '' }}>Email Auth Done</option>
                            <option value="ticketed" {{ request('booking_status') === 'ticketed' ? 'selected' : '' }}>Ticketed</option>
                            <option value="booking_complete" {{ request('booking_status') === 'booking_complete' ? 'selected' : '' }}>Booking Complete</option>
                            <option value="void" {{ request('booking_status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All Payment Statuses</option>
                            <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="received" {{ request('payment_status') === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="refund" {{ request('payment_status') === 'refund' ? 'selected' : '' }}>Refund</option>
                            <option value="cancelled" {{ request('payment_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Merchant Filter -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Merchant Profile</label>
                        <select name="merchant_id" class="form-select">
                            <option value="">All Merchants</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}" {{ request('merchant_id') == $merchant->id ? 'selected' : '' }}>{{ $merchant->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Service Provided Filter -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Service Provided</label>
                        <select name="service_provided" class="form-select">
                            <option value="">All Services</option>
                            <option value="new_booking" {{ request('service_provided') === 'new_booking' ? 'selected' : '' }}>New Booking</option>
                            <option value="cancellation" {{ request('service_provided') === 'cancellation' ? 'selected' : '' }}>Cancellation</option>
                            <option value="date_change" {{ request('service_provided') === 'date_change' ? 'selected' : '' }}>Date Change</option>
                            <option value="seat_assignment" {{ request('service_provided') === 'seat_assignment' ? 'selected' : '' }}>Seat Assignment</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top border-light-subtle">
                    <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-search me-1"></i> Apply Filters</button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary fw-semibold px-4">Clear All</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 text-nowrap">
                <thead class="table-light text-secondary small text-uppercase border-bottom">
                    <tr>
                        <th class="px-3 py-3">ID / Agent</th>
                        <th class="px-3 py-3">Customer Details</th>
                        <th class="px-3 py-3">Financials</th>
                        <th class="px-3 py-3">Booking Status</th>
                        <th class="px-3 py-3">Payment Status</th>
                        <th class="px-3 py-3">Case Status</th>
                        <th class="px-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <!-- Booking ID / Agent -->
                            <td class="px-3 py-3">
                                <span class="fw-bold text-primary font-monospace fs-6 d-block">{{ $booking->booking_id }}</span>
                                <span class="small text-secondary d-block">Agent: {{ $booking->agent ? $booking->agent->alias_name : 'N/A' }}</span>
                                <span class="text-muted small font-monospace d-block">{{ $booking->booking_date->format('M d, Y') }}</span>
                            </td>
                            <!-- Customer Details -->
                            <td class="px-3 py-3">
                                <div class="fw-semibold text-dark">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                                <div class="small text-secondary">{{ $booking->email_address }}</div>
                            </td>
                            <!-- Financials -->
                            <td class="px-3 py-3 small">
                                <div class="fw-semibold text-dark">Total: {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</div>
                                <div class="text-secondary">Airline: {{ $booking->currency }} {{ number_format($booking->paid_to_airline, 2) }}</div>
                                <div class="fw-bold text-success font-monospace">MCO: {{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}</div>
                            </td>
                            <!-- Booking Status -->
                            <td class="px-3 py-3">
                                @php
                                    $bStatusBadge = match($booking->booking_status) {
                                        'booking_generated' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'email_auth_done' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'ticketed' => 'bg-secondary text-white',
                                        'booking_complete' => 'bg-success-subtle text-success border border-success-subtle',
                                        'void' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-light text-dark border border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $bStatusBadge }} text-capitalize px-2 py-1">
                                    {{ str_replace('_', ' ', ucfirst($booking->booking_status)) }}
                                </span>
                            </td>
                            <!-- Payment Status -->
                            <td class="px-3 py-3">
                                @php
                                    $payBadge = match($booking->payment_status) {
                                        'received' => 'bg-success-subtle text-success border border-success-subtle',
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'refund' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $payBadge }} text-uppercase px-2 py-1">
                                    {{ strtoupper($booking->payment_status) }}
                                </span>
                            </td>
                            <!-- Case Status -->
                            <td class="px-3 py-3">
                                @if($booking->case_status)
                                    <button type="button" @click="openCaseModal({{ $booking->id }}, '{{ $booking->booking_id }}', '{{ $booking->case_status }}')" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3">
                                        {{ strtoupper($booking->case_status) }}
                                    </button>
                                @else
                                    <button type="button" @click="openCaseModal({{ $booking->id }}, '{{ $booking->booking_id }}', '')" class="btn btn-sm btn-link text-secondary text-decoration-none fst-italic">
                                        + Set Case
                                    </button>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-2">
                                    @if(in_array($booking->booking_status, ['email_auth_done', 'ticketed', 'booking_complete']))
                                        <button type="button" @click="openAssignModal({{ $booking->id }}, '{{ $booking->booking_id }}', {{ $booking->ticketing_user_id ?: 'null' }})" class="btn btn-outline-primary btn-sm px-2 py-1" title="Assign to Ticketing Agent">
                                            <i class="bi bi-person-check"></i>
                                            <span>{{ $booking->ticketingUser ? ($booking->ticketingUser->alias_name ?: $booking->ticketingUser->name) : 'Assign' }}</span>
                                        </button>
                                    @endif
                                    <a href="{{ route('bookings.request-change.create', $booking->id) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Request Changes from Changes Team">
                                        Changes
                                    </a>
                                    <a href="{{ route('bookings.create', ['duplicate' => $booking->id]) }}" class="btn btn-outline-secondary btn-sm px-2 py-1" title="Duplicate Booking">
                                        <i class="bi bi-files"></i>
                                    </a>
                                    <button type="button" onclick="showBookingDetailModal({{ $booking->id }})" class="btn btn-outline-primary btn-sm px-2 py-1" title="View Complete Booking Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Edit Booking">
                                        <i class="bi bi-pencil-square"></i> 
                                    </a>
                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this booking record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-secondary fst-italic">
                                No bookings matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <!-- CASE STATUS MODAL (Alpine controlled) -->
    <div x-show="caseModalOpen" x-cloak class="modal fade" :class="{ 'show d-block': caseModalOpen }" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-white border-light-subtle text-dark shadow-lg">
                <div class="modal-header border-light-subtle py-3">
                    <h5 class="modal-title font-bold h6 text-uppercase text-danger d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon"></i> Manage Case Status (#<span x-text="caseBookingRef"></span>)
                    </h5>
                    <button type="button" class="btn-close" @click="caseModalOpen = false"></button>
                </div>
                <form :action="`/admin/bookings/${caseBookingId}/update-case-status`" method="POST">
                    @csrf
                    <div class="modal-body p-4 vstack gap-3">
                        <div>
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Case Status <span class="text-danger">*</span></label>
                            <select name="case_status" x-model="caseStatusValue" required class="form-select border-danger">
                                <option value="rdr">RDR</option>
                                <option value="retrieval">Retrieval</option>
                                <option value="chargeback">Chargeback</option>
                                <option value="refund">Refund</option>
                                <option value="void">Void</option>
                            </select>
                        </div>

                        <div class="form-check pt-2">
                            <input type="checkbox" name="update_booking_status_to_void" id="update_booking_status_to_void" value="1" class="form-check-input">
                            <label for="update_booking_status_to_void" class="form-check-label text-secondary small">Set Booking Status to VOID as well</label>
                        </div>
                    </div>
                    <div class="modal-footer border-light-subtle">
                        <button type="button" @click="caseModalOpen = false" class="btn btn-outline-secondary btn-sm fw-semibold">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm fw-bold">Save Case Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Ticketing Modal -->
    <div x-show="assignModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': assignModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered w-100" style="max-width: 500px;">
            <div class="modal-content card bg-white border-primary-subtle shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-primary-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-person-check text-primary"></i> Assign Booking to Ticketing Agent
                    </h5>
                    <button type="button" @click="assignModalOpen = false" class="btn-close"></button>
                </div>
                <form :action="getAssignAction()" @submit="$el.action = getAssignAction()" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-3">
                            Select a ticketing team member to generate and issue the e-ticket for Booking Reference <strong class="text-dark" x-text="`#${assignBookingCode}`"></strong>. An assignment notification email will be dispatched to the selected agent.
                        </p>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Select Ticketing Agent <span class="text-danger">*</span></label>
                            <select name="ticketing_user_id" required x-model="assignTicketingUserId" class="form-select border-primary">
                                <option value="">-- Choose Ticketing Team Member --</option>
                                @foreach($ticketingAgents as $tAgent)
                                    <option value="{{ $tAgent->id }}">
                                        {{ $tAgent->alias_name ?: $tAgent->name }} ({{ $tAgent->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-light-subtle d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="assignModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="bi bi-send me-1"></i> Assign &amp; Send Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function adminBookingState() {
        return {
            caseModalOpen: false,
            caseBookingId: null,
            caseBookingRef: '',
            caseStatusValue: 'rdr',
            assignModalOpen: false,
            assignBookingId: null,
            assignBookingCode: '',
            assignTicketingUserId: '',
            changeModalOpen: false,
            changeBookingId: null,
            changeBookingCode: '',

            openCaseModal(id, ref, status) {
                this.caseBookingId = id;
                this.caseBookingRef = ref;
                this.caseStatusValue = status || 'rdr';
                this.caseModalOpen = true;
            },

            openAssignModal(id, code, currentAgentId = null) {
                this.assignBookingId = id;
                this.assignBookingCode = code;
                this.assignTicketingUserId = currentAgentId || '';
                this.assignModalOpen = true;
            },

            openRequestChangeModal(id, code) {
                this.changeBookingId = id;
                this.changeBookingCode = code;
                this.changeModalOpen = true;
            },

            getAssignAction() {
                if (!this.assignBookingId) return '#';
                return `/bookings/${this.assignBookingId}/assign-ticketing`;
            },

            getRequestChangeAction() {
                if (!this.changeBookingId) return '#';
                return `/bookings/${this.changeBookingId}/request-change`;
            }
        };
    }
</script>
@endsection
