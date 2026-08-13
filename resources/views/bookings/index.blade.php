@extends('layouts.app')

@section('content')
<div x-data="dashboardState()">
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
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
        <div class="card bg-white border-warning-subtle shadow-sm mb-4">
            <div class="card-header bg-warning-subtle py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status" aria-hidden="true"></span>
                    <h2 class="h6 font-bold text-warning-emphasis mb-0 text-uppercase tracking-wider">
                        Pending Customer Authorization Notifications ({{ $pendingAuthBookings->count() }})
                    </h2>
                </div>
                <span class="badge bg-warning text-dark font-monospace">Awaiting Customer Reply / Approval</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($pendingAuthBookings as $pBooking)
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 bg-light rounded border border-light-subtle shadow-sm h-100 d-flex flex-column justify-content-between">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary font-monospace fs-6">#{{ $pBooking->booking_id }}</span>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-uppercase">Email Auth Sent</span>
                                    </div>
                                    <div class="small text-secondary">
                                        <div class="mb-1"><strong class="text-dark">PNR:</strong> <span class="text-primary font-monospace fw-bold">{{ $pBooking->airline_pnr ?: ($pBooking->gk_pnr ?: 'N/A') }}</span></div>
                                        <div class="mb-1"><strong class="text-dark">Airline:</strong> {{ $pBooking->airline_name ?: 'N/A' }}</div>
                                        <div class="mb-1"><strong class="text-dark">Email:</strong> <span class="text-secondary">{{ $pBooking->email_address }}</span></div>
                                        <div class="mb-1"><strong class="text-dark">Agent:</strong> {{ $pBooking->agent ? $pBooking->agent->alias_name : 'Agent' }}</div>
                                        <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i> Dispatched {{ $pBooking->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                                    <button type="button" @click="openDetails({{ json_encode($pBooking) }})" class="btn btn-outline-primary btn-sm fw-semibold">
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

    <!-- Approved Customer Authorization Banner (For Agents) -->
    @if(isset($approvedAuthBookings) && $approvedAuthBookings->count() > 0)
        <div class="card bg-white border-success-subtle shadow-sm mb-4">
            <div class="card-header bg-success-subtle py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <h2 class="h6 font-bold text-success-emphasis mb-0 text-uppercase tracking-wider">
                        Approved Customer Authorizations ({{ $approvedAuthBookings->count() }})
                    </h2>
                </div>
                <span class="badge bg-success text-white font-monospace">Auth Mail Approved — Status: EMAIL AUTH DONE</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($approvedAuthBookings as $aBooking)
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 bg-light rounded border border-success-subtle shadow-sm h-100 d-flex flex-column justify-content-between">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary font-monospace fs-6">#{{ $aBooking->booking_id }}</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase">EMAIL AUTH DONE</span>
                                    </div>
                                    <div class="small text-secondary">
                                        <div class="mb-1"><strong class="text-dark">PNR:</strong> <span class="text-primary font-monospace fw-bold">{{ $aBooking->airline_pnr ?: ($aBooking->gk_pnr ?: 'N/A') }}</span></div>
                                        <div class="mb-1"><strong class="text-dark">Customer:</strong> <span class="text-dark fw-semibold">{{ $aBooking->card_holder_name ?: 'N/A' }}</span></div>
                                        <div class="mb-1"><strong class="text-dark">Email:</strong> {{ $aBooking->email_address }}</div>
                                        <div class="mb-1"><strong class="text-dark">Amount:</strong> <span class="text-success font-monospace fw-bold">{{ $aBooking->currency }} {{ number_format($aBooking->total_amount, 2) }}</span></div>
                                        <div class="text-muted small mt-1"><i class="bi bi-check2-circle text-success me-1"></i> Approved: {{ $aBooking->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                                    <button type="button" @click="openDetails({{ json_encode($aBooking) }})" class="btn btn-outline-success btn-sm fw-semibold">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </button>
                                    @if(in_array($aBooking->booking_status, ['email_auth_done', 'ticketed', 'booking_complete']))
                                        <button type="button" @click="openAssignModal({{ $aBooking->id }}, '{{ $aBooking->booking_id }}', {{ $aBooking->ticketing_user_id ?: 'null' }})" class="btn btn-primary btn-sm fw-bold">
                                            <i class="bi bi-person-check me-1"></i> Assign Ticketing
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('bookings.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-7">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Search Bookings</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Booking ID, Airline PNR, Email, or Passenger Name..." class="form-control border-start-0 ps-0">
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
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase border-bottom">
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
                            <td class="ps-3 fw-bold text-primary font-monospace">
                                #{{ $booking->booking_id }}
                            </td>
                            <!-- PNR -->
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle font-monospace fs-6">
                                    {{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}
                                </span>
                            </td>
                            <!-- Customer Info -->
                            <td>
                                @if($booking->card_holder_name)
                                    <div class="fw-semibold text-dark">{{ $booking->card_holder_name }}</div>
                                    <div class="small text-secondary">{{ $booking->email_address }}</div>
                                @elseif($booking->passengers->isNotEmpty())
                                    <div class="fw-semibold text-dark">{{ $booking->passengers->first()->first_name }} {{ $booking->passengers->first()->last_name }}</div>
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
                                        'email_auth_sent' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'email_auth_done' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'ticketed' => 'bg-secondary text-light',
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
                                     @if($booking->booking_status !== 'void')
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
                                     <button type="button" @click="openDetails({{ json_encode($booking) }})" class="btn btn-outline-primary" title="View Details & Update">
                                         <i class="bi bi-pencil-square me-1"></i> View / Edit
                                     </button>
                                    <button type="button" @click="openRemarkModal({{ $booking->id }})" class="btn btn-outline-success" title="Add Remark with Attachment">
                                        <i class="bi bi-paperclip me-1"></i> Remark
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
            <div class="card-footer bg-white border-top border-light-subtle p-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <!-- REMARK MODAL POPUP (MULTIPLE ATTACHMENT SUPPORT) -->
    <div x-show="remarkModalOpen" class="modal fade" :class="{ 'show d-block': remarkModalOpen }" tabindex="-1" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card bg-white border-0 shadow-lg w-100">
                <div class="modal-header border-bottom border-light-subtle py-3">
                    <h5 class="modal-header-title h6 text-dark mb-0 text-uppercase fw-bold">
                        <i class="bi bi-chat-left-text text-success me-2"></i> Add Remark &amp; Attachments
                    </h5>
                    <button type="button" class="btn-close" @click="remarkModalOpen = false"></button>
                </div>
                <form :action="getRemarkAction()" @submit="$el.action = getRemarkAction()" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Remark Note</label>
                            <textarea name="remark" rows="3" class="form-control" placeholder="Type your remark update here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Attach PDF or Images</span>
                                <span class="text-muted fw-normal">Optional</span>
                            </label>
                            <input type="file" name="attachments[]" multiple accept=".pdf,image/png,image/jpeg,image/jpg,image/webp" class="form-control form-control-sm">
                            <div class="form-text small text-muted">Upload PDF documents or images (PNG, JPG, WEBP). Select multiple files if needed.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-light-subtle bg-light py-2">
                        <button type="button" @click="remarkModalOpen = false" class="btn btn-outline-secondary btn-sm fw-bold">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-paperclip me-1"></i> Save Remark &amp; Attachments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DETAIL OFF-CANVAS / DRAWER POPUP -->
    <div x-show="slideoverOpen" class="offcanvas offcanvas-end bg-white border-start border-light-subtle text-dark" :class="{ 'show': slideoverOpen }" tabindex="-1" style="width: 650px; max-width: 90vw;" x-cloak>
        <div class="offcanvas-header bg-white border-bottom border-light-subtle py-3">
            <div>
                <h5 class="offcanvas-title h6 text-dark fw-bold mb-0 text-uppercase">
                    Booking Details <span class="text-primary font-monospace ms-2" x-text="`#${booking.booking_id}`"></span>
                </h5>
                <small class="text-secondary" x-text="`Created on ${formatDate(booking.created_at)}`"></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a :href="'/bookings/create?duplicate=' + booking.id" class="btn btn-outline-primary btn-sm fw-semibold me-1">
                    <i class="bi bi-files me-1"></i> Duplicate Booking
                </a>
                <button type="button" class="btn-close" @click="slideoverOpen = false"></button>
            </div>
        </div>

        <div class="offcanvas-body p-4 space-y-4">
            <!-- Details Grid -->
            <div class="card bg-light border-light-subtle mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Airline PNR</div>
                            <div class="font-monospace fw-bold text-primary fs-6" x-text="booking.airline_pnr || 'N/A'"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">GK PNR</div>
                            <div class="font-monospace fw-bold text-warning-emphasis fs-6" x-text="booking.gk_pnr || 'N/A'"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Trip Type</div>
                            <div class="fw-semibold text-primary" x-text="capitalize(booking.trip_type || 'one_way')"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Status</div>
                            <div class="fw-semibold text-dark" x-text="capitalize(booking.booking_status)"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Airline</div>
                            <div class="fw-semibold text-dark" x-text="`${booking.airline_name || ''} (${booking.airline_code || ''})`"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">Route</div>
                            <div class="fw-semibold text-dark" x-text="`${booking.from_airport || ''} ➔ ${booking.to_airport || ''}`"></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="small text-secondary text-uppercase fw-bold">MCO Amount</div>
                            <div class="fw-bold text-success font-monospace" x-text="`${booking.currency} ${parseFloat(booking.total_mco || 0).toFixed(2)}`"></div>
                        </div>
                        <div class="col-12 pt-2 border-top border-light-subtle">
                            <div class="small text-secondary text-uppercase fw-bold">Billing Address</div>
                            <div class="small text-dark" x-text="booking.billing_address || 'No billing address recorded.'"></div>
                        </div>

                        <div class="col-12 pt-2 border-top border-light-subtle">
                            <div class="small text-primary text-uppercase fw-bold mb-1"><i class="bi bi-journal-text me-1"></i> Payment Info Notes / Remarks</div>
                            <div class="small text-dark font-monospace bg-white p-2 rounded border border-light-subtle" x-text="booking.payment_info || 'No payment info notes recorded.'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Form -->
            <div class="card bg-white border-light-subtle shadow-sm mb-4">
                <div class="card-header bg-white border-bottom border-light-subtle py-2">
                    <h6 class="mb-0 text-dark fw-bold small text-uppercase"><i class="bi bi-pencil me-1 text-primary"></i> Update Ticket Details</h6>
                </div>
                <div class="card-body p-3">
                    <form :action="getUpdateTicketsAction()" @submit="$el.action = getUpdateTicketsAction()" method="POST" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-sm-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Airline PNR</label>
                            <input type="text" name="airline_pnr" :value="booking.airline_pnr" placeholder="PNR" class="form-control form-control-sm font-monospace text-primary fw-bold">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Trip Type</label>
                            <select name="trip_type" :value="booking.trip_type || 'one_way'" class="form-select form-select-sm">
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

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Payment Info Notes / Remarks</span>
                            </label>
                            <textarea name="payment_info" x-text="booking.payment_info || ''" rows="2" placeholder="Payment info & remarks notes..." class="form-control form-control-sm"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-2">Passenger Tickets & Seats</label>
                            <div class="d-flex flex-column gap-2">
                                <template x-for="(pax, index) in booking.passengers" :key="pax.id">
                                    <div class="p-2 bg-light rounded border border-light-subtle d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                                        <div class="small fw-semibold text-dark">
                                            <span x-text="`${pax.pax_index || ''} ${pax.title || ''} ${pax.first_name} ${pax.last_name}`"></span>
                                            <template x-if="pax.dob">
                                                <span class="badge bg-light text-secondary border border-secondary-subtle font-monospace ms-1" x-text="`DOB: ${String(pax.dob).slice(0, 10)}`"></span>
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

                        <!-- Itinerary Flight Segments -->
                        <div class="col-12">
                            <div class="card bg-white border-light-subtle shadow-sm my-2">
                                <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-airplane-engines-fill text-primary"></i>
                                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase">Itinerary Flight Segments</h2>
                                        <span class="badge bg-primary-subtle text-primary font-monospace ms-2" x-text="`${flights.length} Segment(s)`">0 Segment(s)</span>
                                    </div>
                                    <button type="button" @click="addFlight()" class="btn btn-outline-primary btn-sm fw-bold">
                                        <i class="bi bi-plus-lg me-1"></i> Add Flight Segment
                                    </button>
                                </div>
                                <div class="card-body p-3 border-bottom border-light-subtle bg-light">
                                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Paste GDS Raw Text (Auto-Fill)</label>
                                    <textarea x-model="rawPnr" rows="2" placeholder="Paste full GDS raw text lines here (e.g. 1 DL 450 Y 12OCT JFKLAX HK1 0800 1130...)" class="form-control font-monospace text-success small mb-2 bg-white"></textarea>
                                    <button type="button" @click="parsePnrText()" class="btn btn-outline-success btn-sm fw-bold">
                                        <i class="bi bi-magic me-1"></i> Parse &amp; Auto-Fill Flight Segments
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
                                                            <input type="text" :name="`flights[${index}][operating_carrier]`" x-model="fl.operating_carrier" placeholder="UA" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="`flights[${index}][flight_number]`" x-model="fl.flight_number" placeholder="354" class="form-control form-control-sm font-monospace text-uppercase">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="`flights[${index}][origin_airport]`" x-model="fl.origin_airport" placeholder="CMH" class="form-control form-control-sm font-monospace text-uppercase fw-bold">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="`flights[${index}][destination_airport]`" x-model="fl.destination_airport" placeholder="LAX" class="form-control form-control-sm font-monospace text-uppercase fw-bold">
                                                        </td>
                                                        <td>
                                                            <input type="datetime-local" :name="`flights[${index}][departure_time]`" x-model="fl.departure_time" class="form-control form-control-sm small">
                                                        </td>
                                                        <td>
                                                            <input type="datetime-local" :name="`flights[${index}][arrival_time]`" x-model="fl.arrival_time" class="form-control form-control-sm small">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="`flights[${index}][booking_class]`" x-model="fl.booking_class" placeholder="Y" class="form-control form-control-sm font-monospace text-uppercase">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="`flights[${index}][status]`" x-model="fl.status" placeholder="Confirmed" class="form-control form-control-sm">
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
                                                        No flight segments added yet. Click "Parse &amp; Auto-Fill Flight Segments" or "Add Flight Segment" above.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold text-uppercase">Add New Remark (Optional)</label>
                            <input type="text" name="new_remark" placeholder="Add update note..." class="form-control form-control-sm mb-2">
                            <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Attach PDF or Images</span>
                                <span class="text-muted fw-normal">Optional</span>
                            </label>
                            <input type="file" name="new_remark_attachments[]" multiple accept=".pdf,image/png,image/jpeg,image/jpg,image/webp" class="form-control form-control-sm">
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                Save Updates
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Remarks History -->
            <div class="card bg-white border-light-subtle shadow-sm mb-4">
                <div class="card-header bg-white border-bottom border-light-subtle py-2">
                    <h6 class="mb-0 text-dark fw-bold small text-uppercase"><i class="bi bi-clock-history me-1 text-success"></i> Remarks History</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        <template x-for="remark in booking.booking_remarks" :key="remark.id">
                            <div class="p-2 bg-light rounded border border-light-subtle">
                                <div class="d-flex justify-content-between align-items-center text-secondary mb-1" style="font-size: 0.75rem;">
                                    <span class="fw-bold text-primary" x-text="remark.user ? remark.user.alias_name : 'System'"></span>
                                    <span x-text="formatDate(remark.created_at)"></span>
                                </div>
                                <div class="small text-dark fw-medium" x-text="remark.remark"></div>

                                <!-- File Attachments Display -->
                                <template x-if="remark.attachments_data && remark.attachments_data.length > 0">
                                    <div class="mt-2 pt-2 border-top border-light-subtle d-flex flex-wrap gap-2">
                                        <template x-for="(file, fIdx) in remark.attachments_data" :key="fIdx">
                                            <div>
                                                <template x-if="isImageFile(file)">
                                                    <a :href="getFileUrl(file)" target="_blank" class="d-inline-block text-decoration-none me-1 mb-1" title="Click to open image preview">
                                                        <img :src="getFileUrl(file)" :alt="file.original_name" class="rounded border shadow-sm" style="max-height: 100px; max-width: 140px; object-fit: cover;">
                                                    </a>
                                                </template>
                                                <template x-if="!isImageFile(file)">
                                                    <a :href="getFileUrl(file)" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 font-monospace small d-inline-flex align-items-center gap-1 shadow-sm me-1 mb-1" title="Click to view PDF document">
                                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-6"></i>
                                                        <span x-text="file.original_name || 'Document.pdf'"></span>
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Ticketing Modal Popup -->
<div x-show="assignModalOpen" x-cloak style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="d-flex align-items-center justify-content-center" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered w-100" style="max-width: 500px;">
        <div class="modal-content card bg-white border-0 shadow-lg w-100" style="pointer-events: auto;">
            <div class="card-header bg-white border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3">
                <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-person-check text-primary"></i> Assign Booking to Ticketing Agent
                </h5>
                <button type="button" @click="assignModalOpen = false" class="btn-close"></button>
            </div>
            <form :action="getAssignAction()" @submit="$el.action = getAssignAction()" method="POST">
                @csrf
                <div class="card-body p-4">
                    <p class="text-secondary small mb-3">
                        Select a ticketing team member to generate and issue the e-ticket for Booking Reference <strong class="text-primary" x-text="`#${assignBookingCode}`"></strong>. An assignment notification email will be dispatched to the selected agent.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Select Ticketing Agent <span class="text-danger">*</span></label>
                        <select name="ticketing_user_id" required x-model="assignTicketingUserId" class="form-select">
                            <option value="">-- Choose Ticketing Team Member --</option>
                            @foreach($ticketingAgents as $tAgent)
                                <option value="{{ $tAgent->id }}">
                                    {{ $tAgent->alias_name ?: $tAgent->name }} ({{ $tAgent->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer bg-light border-top border-light-subtle d-flex justify-content-end gap-2 py-3">
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
            rawPnr: '',
            flights: [],

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
                this.rawPnr = '';
                const existingSegments = (booking.flight_segments && booking.flight_segments.length > 0)
                    ? booking.flight_segments
                    : (booking.booking_flights || []);

                this.flights = existingSegments.map(fl => ({
                    operating_carrier: fl.operating_carrier || '',
                    flight_number: fl.flight_number || '',
                    origin_airport: fl.origin_airport || fl.origin || '',
                    destination_airport: fl.destination_airport || fl.destination || '',
                    departure_time: fl.departure_time ? String(fl.departure_time).replace(' ', 'T').slice(0, 16) : '',
                    arrival_time: fl.arrival_time ? String(fl.arrival_time).replace(' ', 'T').slice(0, 16) : '',
                    booking_class: fl.booking_class || '',
                    status: fl.status || 'Confirmed'
                }));
                this.slideoverOpen = true;
            },

            addFlight(fl = {}) {
                this.flights.push({
                    operating_carrier: fl.operating_carrier || '',
                    flight_number: fl.flight_number || '',
                    origin_airport: fl.origin_airport || fl.origin || '',
                    destination_airport: fl.destination_airport || fl.destination || '',
                    departure_time: fl.departure_time ? String(fl.departure_time).replace(' ', 'T').slice(0, 16) : '',
                    arrival_time: fl.arrival_time ? String(fl.arrival_time).replace(' ', 'T').slice(0, 16) : '',
                    booking_class: fl.booking_class || '',
                    status: fl.status || 'Confirmed'
                });
            },

            removeFlight(index) {
                this.flights.splice(index, 1);
            },

            async parsePnrText() {
                if (!this.rawPnr || !this.rawPnr.trim()) {
                    alert('Please paste GDS raw text into the textarea.');
                    return;
                }
                try {
                    const response = await fetch('/pnr/parse', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ raw_pnr: this.rawPnr })
                    });

                    const responseText = await response.text();
                    let res;
                    try {
                        res = JSON.parse(responseText);
                    } catch (jsonErr) {
                        console.error('Non-JSON response from server:', responseText);
                        alert('Unable to parse server response. Please refresh the page and try again.');
                        return;
                    }

                    if (!response.ok || res.success === false) {
                        alert('Error parsing GDS text: ' + (res.error || res.message || 'Parsing failed.'));
                        return;
                    }

                    const data = res.data || res;
                    if (data.flights && data.flights.length > 0) {
                        // Clear old itinerary flight segments first
                        this.flights = [];
                        data.flights.forEach(fl => this.addFlight(fl));
                        if (data.airline_pnr && (!this.booking.airline_pnr || this.booking.airline_pnr === 'N/A')) {
                            this.booking.airline_pnr = data.airline_pnr;
                        }
                    } else {
                        alert('Parsed GDS text, but no flight segments were recognized.');
                    }
                } catch (e) {
                    alert('Error parsing GDS text: ' + e.message);
                }
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
            },

            isImageFile(file) {
                if (!file) return false;
                if (file.file_type === 'image') return true;
                const name = file.original_name || file.file_path || '';
                return /\.(png|jpe?g|webp|gif|svg)$/i.test(name);
            },

            getFileUrl(file) {
                if (!file) return '#';
                if (file.file_url && file.file_url !== '#') return file.file_url;
                if (file.file_path) return '/storage/' + file.file_path.replace(/^\/+/, '');
                return '#';
            }
        };
    }
</script>
@endsection
