@extends('layouts.app')

@section('content')
<div x-data="managerState()">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-ticket-perforated text-primary"></i> Ticketing Queue &amp; Manager Approvals
            </h1>
            <p class="text-secondary small mb-0">Review authorized bookings, assign ticketing agents, and generate/issue e-tickets.</p>
        </div>
    </div>

    <!-- Error/Success Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show bg-danger-subtle text-danger border border-danger-subtle mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Scope Filter Bar -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('manager.tickets.index') }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="scope" value="{{ $scope ?? 'all' }}">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0" placeholder="Search by PNR, Reference, Customer, Email...">
                        @if(!empty($search))
                            <a href="{{ route('manager.tickets.index', ['scope' => $scope ?? 'all']) }}" class="btn btn-outline-secondary border-start-0" title="Clear Search">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary fw-semibold">Search</button>
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 d-flex justify-content-md-end align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Scope Filter">
                        <a href="{{ route('manager.tickets.index', array_merge(request()->except('page'), ['scope' => 'all'])) }}" class="btn {{ ($scope ?? 'all') === 'all' ? 'btn-primary active' : 'btn-outline-secondary' }} fw-semibold">
                            <i class="bi bi-list-task me-1"></i> All Tickets Queue
                        </a>
                        <a href="{{ route('manager.tickets.index', array_merge(request()->except('page'), ['scope' => 'my'])) }}" class="btn {{ ($scope ?? 'all') === 'my' ? 'btn-primary active' : 'btn-outline-secondary' }} fw-semibold">
                            <i class="bi bi-person-badge me-1"></i> My Bookings
                        </a>
                    </div>
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
                        <th class="px-3 py-3">Booking Reference</th>
                        <th class="px-3 py-3">Merchant Profile</th>
                        <th class="px-3 py-3">Customer Details</th>
                        <th class="px-3 py-3">Payment Status</th>
                        <th class="px-3 py-3">Booking Status</th>
                        <th class="px-3 py-3">Assigned Ticketing</th>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <!-- Booking ID -->
                            <td class="px-3 py-3">
                                <span class="fw-bold text-primary font-monospace fs-6 d-block"> {{ $booking->airline_pnr }}</span>
                                <span class="small text-secondary d-block">Agent: {{ $booking->agent ? $booking->agent->alias_name : 'N/A' }}</span>
                            </td>

                            <!-- Merchant -->
                            <td class="px-3 py-3">
                                @if($booking->merchantProfile)
                                    <div class="fw-semibold text-dark">{{ $booking->merchantProfile->name }}</div>
                                    <div class="small text-secondary font-monospace">{{ $booking->merchantProfile->merchant_code }}</div>
                                @else
                                    <span class="text-secondary fst-italic">None</span>
                                @endif
                            </td>

                            <!-- Customer Details -->
                            <td class="px-3 py-3">
                                <div class="fw-semibold text-dark">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                                <div class="small text-secondary">{{ $booking->email_address }}</div>
                            </td>

                            <!-- Payment Status Badge -->
                            <td class="px-3 py-3">
                                @php
                                    $paymentBadge = match($booking->payment_status) {
                                        'pending' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'received' => 'bg-success-subtle text-success border border-success-subtle',
                                        'refund' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                                    };
                                @endphp
                                <button type="button" @click="openApproveModal({{ $booking->id }}, '{{ $booking->booking_id }}')" class="badge {{ $paymentBadge }} text-uppercase px-2.5 py-1.5 btn btn-sm" title="Click to update payment status">
                                    {{ strtoupper($booking->payment_status) }} <i class="bi bi-pencil-square ms-1"></i>
                                </button>
                            </td>

                            <!-- Booking Status Badge -->
                            <td class="px-3 py-3">
                                @php
                                    $statusBadge = match($booking->booking_status) {
                                        'email_auth_done' => 'bg-info text-light border border-info',
                                        'ticketed' => 'bg-success text-light border border-success',
                                        'booking_complete' => 'bg-success text-light border border-success',
                                        'booking_generated' => 'bg-primary text-light border border-primary',
                                        'void' => 'bg-danger text-light border border-danger',
                                        'failed' => 'bg-danger text-light border border-danger',
                                        default => 'bg-secondary text-secondary'
                                    };
                                    $statusLabel = str_replace('_', ' ', ucfirst($booking->booking_status));
                                @endphp
                                <span class="badge {{ $statusBadge }} text-uppercase px-2.5 py-1.5">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <!-- Assigned Ticketing -->
                            <td class="px-3 py-3">
                                @if($booking->ticketingUser)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5">
                                        <i class="bi bi-person me-1"></i> {{ $booking->ticketingUser->alias_name ?: $booking->ticketingUser->name }}
                                    </span>
                                @else
                                    <span class="text-secondary small fst-italic">Unassigned</span>
                                @endif
                            </td>

                            <!-- Date -->
                            <td class="px-3 py-3 small text-secondary">
                                {{ $booking->booking_date->format('M d, Y') }}
                            </td>

                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-outline-warning btn-sm px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1" title="Edit Booking Parameters">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="{{ route('manager.tickets.preview-email', $booking) }}" class="btn btn-outline-success btn-sm px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-envelope-paper"></i> Preview &amp; Send E-Ticket
                                    </a>
                                    <a href="{{ route('manager.tickets.preview', $booking) }}" target="_blank" class="btn btn-outline-info btn-sm px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                    </a>
                                    @if(Auth::user()->hasAnyRole(['admin', 'manager', 'agent']) || in_array(Auth::user()->role, ['admin', 'manager', 'agent']))
                                        <button type="button" @click="openAssignModal({{ $booking->id }}, '{{ $booking->booking_id }}', {{ $booking->ticketing_user_id ?: 'null' }})" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1" title="Assign to Ticketing Agent">
                                            <i class="bi bi-person-check"></i> Assign
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-secondary fst-italic">
                                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                No bookings currently awaiting approval or ticketing.
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

    <!-- Assign Ticketing Modal -->
    <div x-show="assignModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': assignModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card bg-white border-primary shadow-lg w-100" style="pointer-events: auto;">
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
                    <div class="card-footer bg-white border-top border-light-subtle d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="assignModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="bi bi-send me-1"></i> Assign &amp; Send Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Approve Payment Status Modal -->
    <div x-show="approveModalOpen" style="display: none; z-index: 1055; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" class="modal fade" :class="{ 'show d-block': approveModalOpen }" tabindex="-1" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card bg-white border-warning shadow-lg w-100" style="pointer-events: auto;">
                <div class="card-header bg-white border-bottom border-light-subtle d-flex justify-content-between align-items-center py-3">
                    <h5 class="modal-title text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card-2-front text-warning"></i> Approve Payment Status
                    </h5>
                    <button type="button" @click="approveModalOpen = false" class="btn-close"></button>
                </div>
                <form :action="getApprovePaymentAction()" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-3">
                            Update payment status for Booking Reference <strong class="text-dark" x-text="`#${approveBookingRef}`"></strong>.
                        </p>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold text-uppercase">New Payment Status <span class="text-danger">*</span></label>
                            <select name="payment_status" required class="form-select border-warning">
                                <option value="received">Received</option>
                                <option value="refund">Refund</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top border-light-subtle d-flex justify-content-end gap-2 py-3">
                        <button type="button" @click="approveModalOpen = false" class="btn btn-outline-secondary btn-sm px-3">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm px-4 fw-bold text-dark">
                            <i class="bi bi-check-circle me-1"></i> Update Payment Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function managerState() {
        return {
            approveModalOpen: false,
            approveBookingId: null,
            approveBookingRef: '',

            assignModalOpen: false,
            assignBookingId: null,
            assignBookingCode: '',
            assignTicketingUserId: '',

            openApproveModal(id, ref) {
                this.approveBookingId = id;
                this.approveBookingRef = ref;
                this.approveModalOpen = true;
            },

            openAssignModal(id, code, currentAgentId = null) {
                this.assignBookingId = id;
                this.assignBookingCode = code;
                this.assignTicketingUserId = currentAgentId || '';
                this.assignModalOpen = true;
            },

            getApprovePaymentAction() {
                if (!this.approveBookingId) return '#';
                return `/manager/tickets/${this.approveBookingId}/approve-payment`;
            },

            getAssignAction() {
                if (!this.assignBookingId) return '#';
                return `/bookings/${this.assignBookingId}/assign-ticketing`;
            }
        };
    }
</script>
@endsection
