@extends('layouts.app')

@section('content')
<div x-data="dashboardState()">
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i> Agent Dashboard
            </h1>
            <p class="text-secondary small mb-0">View and manage your bookings, update ticket details, and track authorization status.</p>
        </div>
        <a href="{{ route('bookings.create') }}" class="btn btn-primary fw-bold shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
            <i class="bi bi-plus-circle-fill"></i> New Booking
        </a>
    </div>

    <!-- Pending Customer Authorization Banner (Admin/Manager) -->
    @if(Auth::user()->hasAnyRole(['admin', 'manager']) && isset($pendingAuthBookings) && $pendingAuthBookings->count() > 0)
        <div class="card bg-dark border-warning shadow-sm mb-4">
            <div class="card-header bg-warning bg-opacity-10 border-warning py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status" aria-hidden="true"></span>
                    <h2 class="h6 font-bold text-warning mb-0 text-uppercase tracking-wider">
                        Pending Customer Authorization Notifications ({{ $pendingAuthBookings->count() }})
                    </h2>
                </div>
                <span class="badge bg-warning text-dark font-monospace">Awaiting Customer Reply / Approval</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($pendingAuthBookings as $pBooking)
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 bg-body-tertiary rounded border border-secondary shadow-sm h-100 d-flex flex-column justify-content-between">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary font-monospace fs-6">#{{ $pBooking->booking_id }}</span>
                                        <span class="badge bg-warning text-dark text-uppercase">Email Auth Sent</span>
                                    </div>
                                    <div class="small text-secondary">
                                        <div class="mb-1"><strong class="text-white">PNR:</strong> <span class="text-info font-monospace fw-bold">{{ $pBooking->airline_pnr ?: ($pBooking->gk_pnr ?: 'N/A') }}</span></div>
                                        <div class="mb-1"><strong class="text-white">Airline:</strong> {{ $pBooking->airline_name ?: 'N/A' }}</div>
                                        <div class="mb-1"><strong class="text-white">Email:</strong> <span class="text-white-50">{{ $pBooking->email_address }}</span></div>
                                        <div class="mb-1"><strong class="text-white">Agent:</strong> {{ $pBooking->agent ? $pBooking->agent->alias_name : 'Agent' }}</div>
                                        <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i> Dispatched {{ $pBooking->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary">
                                    <button type="button" @click="openDetails({{ json_encode($pBooking) }})" class="btn btn-outline-info btn-sm fw-semibold">
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>

                                    <form action="{{ route('bookings.approve-auth', $pBooking->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Confirm customer authorization reply received for Booking #{{ $pBooking->booking_id }}? Status will change to EMAIL AUTH DONE.')" class="btn btn-success btn-sm fw-bold">
                                            <i class="bi bi-check-lg me-1"></i> Approve Auth
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('bookings.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-7">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Search Bookings</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark-subtle border-secondary text-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Booking ID, Airline PNR, Email, or Passenger Name..." class="form-control">
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Status Filter</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="booking_generated" {{ request('status') === 'booking_generated' ? 'selected' : '' }}>Booking Generated</option>
                        <option value="email_auth_sent" {{ request('status') === 'email_auth_sent' ? 'selected' : '' }}>Email Auth Sent</option>
                        <option value="email_auth_done" {{ request('status') === 'email_auth_done' ? 'selected' : '' }}>Email Auth Done</option>
                        <option value="ticketed" {{ request('status') === 'ticketed' ? 'selected' : '' }}>Ticketed</option>
                        <option value="booking_complete" {{ request('status') === 'booking_complete' ? 'selected' : '' }}>Booking Complete</option>
                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                    </select>
                </div>

                <div class="col-md-2 col-lg-2">
                    <button type="submit" class="btn btn-outline-secondary w-100 fw-semibold">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table View -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Booking ID</th>
                        <th class="py-3">Airline PNR</th>
                        <th class="py-3">Customer Info</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">MCO</th>
                        <th class="py-3">Booking Date</th>
                        <th class="pe-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <!-- Booking ID -->
                            <td class="ps-3 fw-bold text-info font-monospace">
                                #{{ $booking->booking_id }}
                            </td>
                            <!-- PNR -->
                            <td>
                                <span class="badge bg-body-tertiary text-info border border-info border-opacity-25 font-monospace fs-6">
                                    {{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}
                                </span>
                            </td>
                            <!-- Customer Info -->
                            <td>
                                @if($booking->card_holder_name)
                                    <div class="fw-semibold text-white">{{ $booking->card_holder_name }}</div>
                                    <div class="small text-secondary">{{ $booking->email_address }}</div>
                                @elseif($booking->passengers->isNotEmpty())
                                    <div class="fw-semibold text-white">{{ $booking->passengers->first()->first_name }} {{ $booking->passengers->first()->last_name }}</div>
                                    <div class="small text-secondary">{{ $booking->email_address }}</div>
                                @else
                                    <span class="text-secondary small">No Passengers</span>
                                @endif
                            </td>
                            <!-- Status Badge -->
                            <td>
                                @php
                                    $badgeClass = match($booking->booking_status) {
                                        'booking_generated' => 'bg-info-subtle text-info border border-info-subtle',
                                        'email_auth_sent' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'email_auth_done' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'ticketed' => 'bg-purple-subtle text-purple border border-purple-subtle',
                                        'booking_complete' => 'bg-success-subtle text-success border border-success-subtle',
                                        'void' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                    $statusLabel = str_replace('_', ' ', ucfirst($booking->booking_status));
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2.5 py-1.5 text-uppercase">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <!-- Total MCO -->
                            <td class="fw-bold text-success font-monospace">
                                {{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}
                            </td>
                            <!-- Date -->
                            <td class="small text-secondary">
                                {{ $booking->booking_date ? $booking->booking_date->format('M d, Y') : '' }}
                            </td>
                            <!-- Actions -->
                            <td class="pe-3 text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('bookings.auth-email.preview', $booking->id) }}" class="btn btn-outline-warning" title="Auth Email">
                                        <i class="bi bi-envelope me-1"></i>
                                        {{ in_array($booking->booking_status, ['email_auth_sent', 'email_auth_done']) ? 'Resend Auth' : 'Auth Mail' }}
                                    </a>
                                    @if(in_array($booking->booking_status, ['email_auth_done', 'ticketed', 'booking_complete']))
                                        <button type="button" @click="openAssignModal({{ $booking->id }}, '{{ $booking->booking_id }}', {{ $booking->ticketing_user_id ?: 'null' }})" class="btn btn-outline-primary" title="Assign to Ticketing Agent">
                                            <i class="bi bi-person-check me-1"></i>
                                            {{ $booking->ticketingUser ? ($booking->ticketingUser->alias_name ?: $booking->ticketingUser->name) : 'Assign Ticketing' }}
                                        </button>
                                    @endif
                                     <a href="{{ route('bookings.request-change.create', $booking->id) }}" class="btn btn-outline-info" title="Request Changes from Changes Team">
                                         <i class="bi bi-arrow-repeat me-1"></i> Request Change
                                     </a>
                                     <a href="{{ route('bookings.create', ['duplicate' => $booking->id]) }}" class="btn btn-outline-secondary" title="Duplicate Booking">
                                         <i class="bi bi-files me-1"></i> Duplicate
                                     </a>
                                     <button type="button" @click="openDetails({{ json_encode($booking) }})" class="btn btn-outline-info" title="View Details & Update">
                                         <i class="bi bi-pencil-square me-1"></i> View / Edit
                                     </button>
                                    <button type="button" @click="openRemarkModal({{ $booking->id }})" class="btn btn-outline-success" title="Add Remark">
                                        <i class="bi bi-chat-text me-1"></i> Remark
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No bookings found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bookings->hasPages())
            <div class="card-footer bg-dark border-secondary p-3">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

    <!-- REMARK MODAL -->
    <div x-show="remarkModalOpen" class="modal fade" :class="{ 'show d-block': remarkModalOpen }" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary shadow-lg">
                <div class="modal-header border-secondary py-3">
                    <h5 class="modal-header-title h6 text-white mb-0 text-uppercase fw-bold">
                        <i class="bi bi-chat-left-text text-success me-2"></i> Add Booking Remark
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="remarkModalOpen = false"></button>
                </div>
                <form :action="getRemarkAction()" @submit="$el.action = getRemarkAction()" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Remark Note</label>
                            <textarea name="remark" required rows="4" class="form-control" placeholder="Type your remark update here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary py-2">
                        <button type="button" @click="remarkModalOpen = false" class="btn btn-outline-secondary btn-sm fw-bold">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold">Save Remark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DETAIL OFF-CANVAS / DRAWER -->
    <div x-show="slideoverOpen" class="offcanvas offcanvas-end bg-dark border-start border-secondary text-white" :class="{ 'show': slideoverOpen }" tabindex="-1" style="width: 650px; max-width: 90vw;" x-cloak>
        <div class="offcanvas-header bg-dark border-bottom border-secondary py-3">
            <div>
                <h5 class="offcanvas-title h6 text-white fw-bold mb-0 text-uppercase">
                    Booking Details <span class="text-info font-monospace ms-2" x-text="`#${booking.booking_id}`"></span>
                </h5>
                <small class="text-secondary" x-text="`Created on ${formatDate(booking.created_at)}`"></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a :href="'/bookings/create?duplicate=' + booking.id" class="btn btn-outline-info btn-sm fw-semibold me-1">
                    <i class="bi bi-files me-1"></i> Duplicate Booking
                </a>
                <button type="button" class="btn-close btn-close-white" @click="slideoverOpen = false"></button>
            </div>
        </div>

        <div class="offcanvas-body p-4 space-y-4">
            <!-- Details Grid -->
            <div class="card bg-body-tertiary border-secondary mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Airline PNR</div>
                            <div class="font-monospace fw-bold text-info fs-6" x-text="booking.airline_pnr || 'N/A'"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">GK PNR</div>
                            <div class="font-monospace fw-bold text-warning fs-6" x-text="booking.gk_pnr || 'N/A'"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Trip Type</div>
                            <div class="fw-semibold text-primary" x-text="capitalize(booking.trip_type || 'one_way')"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Status</div>
                            <div class="fw-semibold text-white" x-text="capitalize(booking.booking_status)"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Airline</div>
                            <div class="fw-semibold text-white" x-text="`${booking.airline_name || ''} (${booking.airline_code || ''})`"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Route</div>
                            <div class="fw-semibold text-white" x-text="`${booking.from_airport || ''} ➔ ${booking.to_airport || ''}`"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">MCO Amount</div>
                            <div class="fw-bold text-success font-monospace" x-text="`${booking.currency} ${parseFloat(booking.total_mco || 0).toFixed(2)}`"></div>
                        </div>
                        <div class="col-12 pt-2 border-top border-secondary">
                            <div class="small text-secondary text-uppercase fw-bold">Billing Address</div>
                            <div class="small text-white" x-text="booking.billing_address || 'No billing address recorded.'"></div>
                        </div>

                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <div class="col-12 pt-2 border-top border-secondary">
                            <div class="small text-warning text-uppercase fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i> Payment Info (Admin View)</div>
                            <div class="small text-warning font-monospace bg-dark p-2 rounded border border-warning border-opacity-25" x-text="booking.payment_info || 'No payment info recorded.'"></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Update Form -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-dark border-secondary py-2">
                    <h6 class="mb-0 text-white fw-bold small text-uppercase"><i class="bi bi-pencil me-1 text-info"></i> Update Ticket Details</h6>
                </div>
                <div class="card-body p-3">
                    <form :action="getUpdateTicketsAction()" @submit="$el.action = getUpdateTicketsAction()" method="POST" class="row g-3">
                        @csrf
                        <div class="col-sm-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Airline PNR</label>
                            <input type="text" name="airline_pnr" :value="booking.airline_pnr" placeholder="PNR" class="form-control form-control-sm font-monospace text-info fw-bold">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Trip Type</label>
                            <select name="trip_type" :value="booking.trip_type || 'one_way'" class="form-select form-select-sm text-white">
                                <option value="one_way">One Way</option>
                                <option value="round_trip">Round Trip</option>
                                <option value="multi_city">Multi City</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Card Last 4</label>
                            <input type="text" name="card_last_4" :value="booking.card_last_4" maxlength="4" minlength="4" placeholder="4321" class="form-control form-control-sm font-monospace">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Billing Address</label>
                            <input type="text" name="billing_address" :value="booking.billing_address" placeholder="Full Billing Address" class="form-control form-control-sm">
                        </div>

                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <div class="col-12">
                            <label class="form-label text-warning small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Payment Info Notes</span>
                                <span class="badge bg-warning-subtle text-warning">Admin Only</span>
                            </label>
                            <textarea name="payment_info" x-text="booking.payment_info || ''" rows="2" placeholder="Admin Payment Notes..." class="form-control form-control-sm border-warning border-opacity-50"></textarea>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-2">Passenger Tickets & Seats</label>
                            <div class="d-flex flex-column gap-2">
                                <template x-for="(pax, index) in booking.passengers" :key="pax.id">
                                    <div class="p-2 bg-body-tertiary rounded border border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                                        <div class="small fw-semibold text-white">
                                            <span x-text="`${pax.pax_index || ''} ${pax.title || ''} ${pax.first_name} ${pax.last_name}`"></span>
                                            <template x-if="pax.dob">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace ms-1" x-text="`DOB: ${String(pax.dob).slice(0, 10)}`"></span>
                                            </template>
                                            <input type="hidden" :name="`passengers[${index}][id]`" :value="pax.id">
                                        </div>
                                        <div class="d-flex gap-2">
                                            <input type="text" :name="`passengers[${index}][ticket_number]`" :value="pax.ticket_number" placeholder="Ticket #" class="form-control form-control-sm font-monospace" style="width: 140px;">
                                            <input type="text" :name="`passengers[${index}][seat_number]`" :value="pax.seat_number" placeholder="Seat" class="form-control form-control-sm font-monospace text-center" style="width: 80px;">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Add New Remark (Optional)</label>
                            <input type="text" name="new_remark" placeholder="Add update note..." class="form-control form-control-sm">
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                Save Updates
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Flight Segments -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-dark border-secondary py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-white fw-bold small text-uppercase"><i class="bi bi-airplane me-1 text-primary"></i> Flight Segments</h6>
                    <span class="badge bg-primary-subtle text-primary font-monospace" x-text="`${(booking.flight_segments || booking.booking_flights || []).length} Segments`"></span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        <template x-for="(flight, fIdx) in (booking.flight_segments && booking.flight_segments.length > 0 ? booking.flight_segments : (booking.booking_flights || []))" :key="flight.id || fIdx">
                            <div class="p-2 bg-body-tertiary rounded border border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold text-white small" x-text="`Segment #${flight.segment_number || (fIdx + 1)}: ${flight.operating_carrier || ''} ${flight.flight_number}`"></div>
                                    <div class="text-secondary" style="font-size: 0.75rem;" x-text="`${flight.origin_airport} ➔ ${flight.destination_airport} | Class: ${flight.booking_class || 'N/A'} ${flight.cabin ? '(' + flight.cabin + ')' : ''}`"></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-info font-monospace" style="font-size: 0.75rem;" x-text="formatDateTime(flight.departure_time)"></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Departure</div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Remarks History -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary py-2">
                    <h6 class="mb-0 text-white fw-bold small text-uppercase"><i class="bi bi-clock-history me-1 text-success"></i> Remarks History</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        <template x-for="remark in booking.booking_remarks" :key="remark.id">
                            <div class="p-2 bg-body-tertiary rounded border border-secondary">
                                <div class="d-flex justify-content-between align-items-center text-secondary mb-1" style="font-size: 0.75rem;">
                                    <span class="fw-bold text-info" x-text="remark.user ? remark.user.alias_name : 'System'"></span>
                                    <span x-text="formatDate(remark.created_at)"></span>
                                </div>
                                <div class="small text-white-50" x-text="remark.remark"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Ticketing Modal -->
<div x-show="assignModalOpen" x-cloak style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1055; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);" class="d-flex align-items-center justify-content-center" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered w-100" style="max-width: 500px;">
        <div class="modal-content card bg-dark border-primary shadow-lg w-100" style="pointer-events: auto;">
            <div class="card-header bg-dark border-primary d-flex justify-content-between align-items-center py-3">
                <h5 class="modal-title text-white fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-person-check text-primary"></i> Assign Booking to Ticketing Agent
                </h5>
                <button type="button" @click="assignModalOpen = false" class="btn-close btn-close-white"></button>
            </div>
            <form :action="getAssignAction()" @submit="$el.action = getAssignAction()" method="POST">
                @csrf
                <div class="card-body p-4">
                    <p class="text-secondary small mb-3">
                        Select a ticketing team member to generate and issue the e-ticket for Booking Reference <strong class="text-white" x-text="`#${assignBookingCode}`"></strong>. An assignment notification email will be dispatched to the selected agent.
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
                <div class="card-footer bg-dark border-secondary d-flex justify-content-end gap-2 py-3">
                    <button type="button" @click="assignModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                        <i class="bi bi-send me-1"></i> Assign &amp; Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    function dashboardState() {
        return {
            remarkModalOpen: false,
            remarkBookingId: null,
            assignModalOpen: false,
            assignBookingId: null,
            assignBookingCode: '',
            assignTicketingUserId: '',
            changeModalOpen: false,
            changeBookingId: null,
            changeBookingCode: '',
            slideoverOpen: false,
            booking: {},

            openRequestChangeModal(id, code) {
                this.changeBookingId = id;
                this.changeBookingCode = code;
                this.changeModalOpen = true;
            },

            openRemarkModal(bookingId) {
                this.remarkBookingId = bookingId;
                this.remarkModalOpen = true;
            },

            openAssignModal(id, code, currentAgentId = null) {
                this.assignBookingId = id;
                this.assignBookingCode = code;
                this.assignTicketingUserId = currentAgentId || '';
                this.assignModalOpen = true;
            },

            openDetails(booking) {
                this.booking = booking;
                this.slideoverOpen = true;
            },

            getRemarkAction() {
                if (!this.remarkBookingId) return '#';
                return `/bookings/${this.remarkBookingId}/remarks`;
            },

            getAssignAction() {
                if (!this.assignBookingId) return '#';
                return `/bookings/${this.assignBookingId}/assign-ticketing`;
            },

            getRequestChangeAction() {
                if (!this.changeBookingId) return '#';
                return `/bookings/${this.changeBookingId}/request-change`;
            },

            getUpdateTicketsAction() {
                if (!this.booking || !this.booking.id) return '#';
                return `/bookings/${this.booking.id}/update-tickets`;
            },

            formatDate(dStr) {
                if (!dStr) return '';
                const date = new Date(dStr);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },

            formatDateTime(dtStr) {
                if (!dtStr) return 'N/A';
                const date = new Date(dtStr);
                return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            capitalize(str) {
                if (!str) return '';
                return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
        };
    }
</script>
@endsection
