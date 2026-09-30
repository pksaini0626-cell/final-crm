@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" style="max-width: 920px;">

    <!-- Header & Back Button -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-counterclockwise text-danger"></i> Submit Refund / Void Request
            </h1>
            <p class="text-secondary small mb-0">Raise a full or partial refund, void, or partial void request for ticketed bookings to Admin &amp; MIS team.</p>
        </div>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show bg-danger-subtle text-danger border border-danger-subtle mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Booking Financial & Overview Card -->
    <div class="card bg-white border-danger-subtle shadow-sm mb-4">
        <div class="card-header bg-danger-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-danger-emphasis mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-danger"></i> Booking Details (#{{ $booking->booking_id }})
            </h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark border border-secondary-subtle font-monospace text-uppercase">
                    Booking: {{ str_replace('_', ' ', ucfirst($booking->booking_status)) }}
                </span>
                <span class="badge bg-danger text-white font-monospace text-uppercase">
                    Payment: {{ str_replace('_', ' ', ucfirst($booking->payment_status ?: 'received')) }}
                </span>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Booking Reference</div>
                    <div class="fw-bold text-primary font-monospace fs-5">#{{ $booking->booking_id }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Airline PNR</div>
                    <div class="fw-bold text-dark font-monospace fs-5">{{ $booking->airline_pnr ?: ($booking->gk_pnr ?: 'N/A') }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Booking Date</div>
                    <div class="text-dark fw-semibold">{{ $booking->booking_date ? $booking->booking_date->format('M d, Y') : 'N/A' }}</div>
                </div>

                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Customer / Card Holder</div>
                    <div class="text-dark fw-semibold">{{ $booking->card_holder_name ?: 'N/A' }}</div>
                    <div class="text-secondary small">{{ $booking->email_address }}</div>
                    <div class="text-secondary small">{{ $booking->billing_phone ?: $booking->calling_number }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Merchant Profile</div>
                    <div class="fw-bold text-dark">
                        {{ $booking->merchantProfile ? $booking->merchantProfile->name : ($booking->merchant ?: 'Default') }}
                        @if($booking->merchantProfile && ($booking->merchantProfile->merchant_code || $booking->merchantProfile->code))
                            <span class="badge bg-light text-secondary font-monospace border ms-1">
                                {{ $booking->merchantProfile->merchant_code ?: $booking->merchantProfile->code }}
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary small">Card: **** {{ $booking->card_last_4 ?: 'N/A' }}</div>
                </div>
                <div class="col-sm-4 bg-light rounded p-2 border border-light-subtle">
                    <div class="text-secondary small fw-bold text-uppercase mb-1">Merchant MCO Amount</div>
                    <div class="fw-bold text-success font-monospace fs-4">
                        {{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}
                    </div>
                    <div class="text-danger small fw-semibold mt-1">
                        <i class="bi bi-info-circle me-1"></i>Max refundable merchant value
                    </div>
                </div>

                @if($booking->passengers && $booking->passengers->count() > 0)
                <div class="col-12 pt-3 border-top border-light-subtle">
                    <div class="text-secondary small fw-bold text-uppercase mb-2">Passengers</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($booking->passengers as $pax)
                            <span class="badge bg-light text-dark border border-light-subtle p-2 font-monospace">
                                <i class="bi bi-person me-1"></i>{{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }}
                                @if($pax->ticket_number) | Tkt: {{ $pax->ticket_number }} @endif
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Refund Request Form -->
    <div class="card bg-white border-light-subtle shadow-lg">
        <div class="card-header bg-white border-light-subtle py-3">
            <h3 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-cash-stack text-danger"></i> Refund / Void Request Information
            </h3>
        </div>
        <form action="{{ route('bookings.refund-request.store', $booking->id) }}" method="POST">
            @csrf
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Request Type Field -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark small fw-bold text-uppercase">
                            Request Type <span class="text-danger">*</span>
                        </label>
                        <select name="request_type" id="request_type" required class="form-select bg-white border-light-subtle text-dark fw-semibold" onchange="handleRequestTypeChange(this.value)">
                            <option value="refund" {{ old('request_type') === 'refund' ? 'selected' : '' }}>Refund (Full or Partial MCO Refund)</option>
                            <option value="void" {{ old('request_type') === 'void' ? 'selected' : '' }}>Void (Full MCO Void)</option>
                            <option value="partial_void" {{ old('request_type') === 'partial_void' ? 'selected' : '' }}>Partial Void (Adjusted / Partial Amount)</option>
                        </select>
                        <div class="form-text text-muted small mt-1">Select whether this case is a Refund, Void, or Partial Void.</div>
                    </div>

                    <!-- Refund Date Field -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark small fw-bold text-uppercase">
                            Refund Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="refund_date" required value="{{ old('refund_date', date('Y-m-d')) }}" class="form-control bg-white border-light-subtle text-dark">
                        <div class="form-text text-muted small mt-1">Date when customer called / refund is requested.</div>
                    </div>

                    <!-- Refund Amount Field -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark small fw-bold text-uppercase">
                            Refund Amount ({{ $booking->currency }}) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">{{ $booking->currency }}</span>
                            <input type="number" step="0.01" min="0.01" max="{{ (float)$booking->total_mco }}" name="refund_amount" id="refund_amount" required value="{{ old('refund_amount', number_format((float)$booking->total_mco, 2, '.', '')) }}" class="form-control bg-white border-light-subtle text-dark fw-bold">
                        </div>
                        <div class="form-text text-muted small mt-1">
                            Cannot exceed total MCO of <strong>{{ $booking->currency }} {{ number_format($booking->total_mco, 2) }}</strong>. Full or adjusted amount.
                        </div>
                    </div>

                    <!-- Reason for Refund -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark small fw-bold text-uppercase">
                            Reason for Refund <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="reason_for_refund" required list="common_reasons" placeholder="Select or type reason..." value="{{ old('reason_for_refund') }}" class="form-control bg-white border-light-subtle text-dark">
                        <datalist id="common_reasons">
                            <option value="Customer Cancellation Request">
                            <option value="Flight Cancelled by Airline">
                            <option value="Schedule Change Inconvenience">
                            <option value="Customer Medical Emergency">
                            <option value="Duplicate Charge Correction">
                            <option value="Customer Satisfaction / Retention Offer">
                            <option value="Fare Adjustment Offered">
                        </datalist>
                        <div class="form-text text-muted small mt-1">Provide clear reason (e.g. airline schedule change, passenger illness, cancellation).</div>
                    </div>

                    <!-- Remarks Field -->
                    <div class="col-12 mb-3">
                        <label class="form-label text-dark small fw-bold text-uppercase">
                            Remarks <span class="text-danger">*</span> <span class="badge bg-danger-subtle text-danger ms-1">Mandatory</span>
                        </label>
                        <textarea name="remarks" required rows="4" placeholder="Enter complete remarks. For partial refunds: include details of e-credits added to passenger's wallet or agreed terms..." class="form-control bg-white border-light-subtle text-dark p-3">{{ old('remarks') }}</textarea>
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i> For partial refunds/voids, describe any wallet credits or compensation offered to the passenger.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-light-subtle d-flex justify-content-between align-items-center py-3 px-4">
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm px-4 fw-semibold">
                    Cancel
                </a>
                <button type="submit" class="btn btn-danger btn-sm px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-send-check-fill"></i> Submit Request to Admin &amp; MIS
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function handleRequestTypeChange(val) {
    const totalMco = {{ (float)$booking->total_mco }};
    const amountInput = document.getElementById('refund_amount');
    if (val === 'void') {
        amountInput.value = totalMco.toFixed(2);
    }
}
</script>
@endsection
