@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" style="max-width: 900px;">

    <!-- Header & Back Button -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 font-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-info"></i> Submit Change Request
            </h1>
            <p class="text-secondary small mb-0">Request ticket changes, flight date modifications, or name corrections from the Changes Team.</p>
        </div>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border-success mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Booking Summary Card -->
    <div class="card bg-dark border-info shadow-sm mb-4">
        <div class="card-header bg-dark border-info py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-info"></i> Booking Overview (#{{ $booking->booking_id }})
            </h2>
            <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace text-uppercase">
                {{ str_replace('_', ' ', ucfirst($booking->booking_status)) }}
            </span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Booking Reference</div>
                    <div class="fw-bold text-primary font-monospace fs-5">#{{ $booking->booking_id }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Airline PNR</div>
                    <div class="fw-bold text-info font-monospace fs-5">{{ $booking->airline_pnr ?: 'N/A' }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Creation Date</div>
                    <div class="text-white fw-semibold">{{ $booking->created_at ? $booking->created_at->format('M d, Y') : 'N/A' }}</div>
                </div>

                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Customer Email</div>
                    <div class="text-white small">{{ $booking->email_address }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Card Holder Name</div>
                    <div class="text-white fw-semibold">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">MCO Amount</div>
                    <div class="fw-bold text-success font-monospace">{{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}</div>
                </div>

                @if($booking->passengers && $booking->passengers->count() > 0)
                <div class="col-12 pt-3 border-top border-secondary">
                    <div class="text-secondary small fw-bold text-uppercase mb-2">Passengers</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($booking->passengers as $pax)
                            <span class="badge bg-body-tertiary text-white border border-secondary p-2 font-monospace">
                                <i class="bi bi-person me-1"></i>{{ $pax->pax_index ?: 'P' }} {{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }}
                                @if($pax->ticket_number) | Tkt: {{ $pax->ticket_number }} @endif
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Change Request Form Card -->
    <div class="card bg-dark border-secondary shadow-lg">
        <div class="card-header bg-dark border-secondary py-3">
            <h3 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i> Change Request Details
            </h3>
        </div>
        <form action="{{ route('bookings.request-change', $booking->id) }}" method="POST">
            @csrf
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase">
                        Full Change Request Description <span class="text-danger">*</span>
                    </label>
                    <textarea name="change_request_text" required rows="6" placeholder="Describe in complete detail what changes are needed for this booking (e.g., Change departure date from Aug 10 to Aug 15 on flight AA100, correct spelling of passenger name, update seat preferences)..." class="form-control bg-dark border-secondary text-white p-3"></textarea>
                    <div class="form-text text-secondary small mt-1">
                        Provide exact flight numbers, dates, or passenger names to help the Changes Team process your request efficiently.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase">
                        Agent Remarks / Urgency Notes (Optional)
                    </label>
                    <textarea name="agent_remark" rows="3" placeholder="Enter any additional instructions or urgency notes..." class="form-control bg-dark border-secondary text-white p-3"></textarea>
                </div>
            </div>
            <div class="card-footer bg-dark border-secondary d-flex justify-content-between align-items-center py-3 px-4">
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm px-4 fw-semibold">
                    Cancel
                </a>
                <button type="submit" class="btn btn-info btn-sm px-4 fw-bold text-dark shadow-sm">
                    <i class="bi bi-send-fill me-1.5"></i> Submit Change Request to Changes Desk
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
