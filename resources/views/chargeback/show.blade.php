@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 py-2" style="max-width: 1300px;">
    <!-- Top Nav Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary btn-sm px-2.5 py-1.5 rounded-3">
                    <i class="bi bi-arrow-left"></i> Back to Control Panel
                </a>
                <h1 class="h4 mb-0 fw-bold text-dark">
                    Chargeback Case <span class="text-danger font-monospace">#{{ $chargeback->case_number }}</span>
                </h1>
                <span class="badge {{ $chargeback->case_type === 'new' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} text-uppercase">
                    {{ $chargeback->case_type }}
                </span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Dispute Record Details • Portal: <strong>{{ $chargeback->portal }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('chargeback.edit', $chargeback) }}" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm rounded-3">
                <i class="bi bi-pencil-square me-1"></i> Edit Case
            </a>
            @if($chargeback->booking_id)
                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 shadow-sm rounded-3" onclick="window.viewBookingDetails({{ $chargeback->booking_id }})">
                    <i class="bi bi-folder2-open me-1"></i> View Full Booking
                </button>
            @endif
        </div>
    </div>

    <!-- 1. CASE HEADER OVERVIEW CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-danger h-100">
                <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Disputed Amount</span>
                <div class="h4 mb-0 fw-bold text-danger font-monospace">
                    {{ $chargeback->currency }} {{ number_format($chargeback->disputed_amount, 2) }}
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Total Booking: {{ $chargeback->currency }} {{ number_format($chargeback->total_booking_amount, 2) }}</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-primary h-100">
                <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Dispute Type</span>
                <div class="h5 mb-0 fw-bold text-primary text-uppercase font-monospace">
                    {{ strtoupper($chargeback->dispute_type) }}
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Portal: {{ $chargeback->portal }}</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-success h-100">
                <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Current Status</span>
                <div class="h5 mb-0 fw-bold text-dark text-uppercase font-monospace">
                    {{ $chargeback->current_status }}
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Portal: {{ $chargeback->portal }}</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-warning h-100">
                <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Deadline Date</span>
                <div class="h5 mb-0 fw-bold {{ $chargeback->deadline_date && $chargeback->deadline_date->isPast() ? 'text-danger' : 'text-dark' }} font-monospace">
                    {{ $chargeback->deadline_date ? $chargeback->deadline_date->format('M d, Y') : 'N/A' }}
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Received: {{ $chargeback->received_date ? $chargeback->received_date->format('M d, Y') : 'N/A' }}</small>
            </div>
        </div>
    </div>

    <!-- 2. CASE & DISPUTE DETAILS TABLE -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                <i class="bi bi-info-circle-fill text-primary"></i> Dispute &amp; Operational Metadata
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Booking Reference</span>
                    <strong class="text-primary font-monospace fs-6">
                        {{ $chargeback->booking_reference ?: ($chargeback->booking ? $chargeback->booking->booking_id : 'N/A') }}
                    </strong>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Airline PNR</span>
                    <span class="badge bg-light text-primary border border-primary-subtle font-monospace fs-6">
                        {{ $chargeback->pnr ?: 'N/A' }}
                    </span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Booking Agent</span>
                    <span class="fw-bold text-dark">{{ $chargeback->agent_name ?: 'N/A' }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Card Brand &amp; Last 4</span>
                    <span class="fw-semibold text-dark">
                        <i class="bi bi-credit-card me-1"></i> {{ $chargeback->cc_brand ?: 'Card' }} •••• {{ $chargeback->card_no ?: '----' }}
                    </span>
                </div>

                <div class="col-md-3 col-6">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Received Date &amp; Month</span>
                    <span class="text-dark fw-semibold">
                        {{ $chargeback->received_date ? $chargeback->received_date->format('M d, Y') : 'N/A' }} ({{ $chargeback->received_month }})
                    </span>
                </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Action Taken Date</span>
                        <span class="text-dark fw-semibold">
                            {{ $chargeback->action_taken_date ? $chargeback->action_taken_date->format('M d, Y') : 'Not Set' }}
                        </span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Booking Date &amp; Month</span>
                        <span class="text-dark fw-semibold">
                            {{ $chargeback->booking_date ? $chargeback->booking_date->format('M d, Y') : 'N/A' }} ({{ $chargeback->booking_month ?: 'N/A' }})
                        </span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Vertical &amp; Service</span>
                        <span class="badge bg-light text-dark border">
                            {{ $chargeback->vertical }} / {{ $chargeback->service_provided ?: 'General' }}
                        </span>
                    </div>

                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Reason Code</span>
                        <span class="font-monospace fw-bold text-dark">{{ $chargeback->reason_code ?: 'None' }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Shift Time / Month</span>
                        <span class="text-dark">{{ $chargeback->shift_time ?: 'N/A' }} ({{ $chargeback->shift_month ? $chargeback->shift_month->format('M Y') : 'N/A' }})</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">Statement Month</span>
                        <span class="text-dark">{{ $chargeback->statement_month ? $chargeback->statement_month->format('M Y') : 'N/A' }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-secondary small text-uppercase fw-bold d-block">SDS</span>
                        <span class="font-monospace text-dark">{{ $chargeback->sds }}</span>
                    </div>

                    @if($chargeback->reason_description)
                        <div class="col-12 mt-2 pt-2 border-top border-light-subtle">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-1">Reason Description for Chargeback:</span>
                            <div class="p-3 bg-light rounded text-dark small border border-light-subtle">
                                {{ $chargeback->reason_description }}
                            </div>
                        </div>
                    @endif

                    <!-- Image Attachments Gallery -->
                    @if(!empty($chargeback->attachments) && is_array($chargeback->attachments) && count($chargeback->attachments) > 0)
                        <div class="col-12 mt-3 pt-3 border-top border-light-subtle">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-2">
                                <i class="bi bi-images text-danger me-1"></i> Attached Dispute Screenshots / Images ({{ count($chargeback->attachments) }}):
                            </span>
                            <div class="row g-3">
                                @foreach($chargeback->attachments as $attachment)
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="card h-100 border shadow-sm rounded-3 overflow-hidden text-center">
                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" title="Click to view full image">
                                                <img src="{{ asset('storage/' . $attachment) }}" alt="Dispute Image" class="card-img-top" style="height: 120px; object-fit: cover; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                                            </a>
                                            <div class="card-body p-2 bg-light border-top">
                                                <div class="small text-truncate font-monospace text-dark" style="font-size: 0.72rem;" title="{{ basename($attachment) }}">
                                                    {{ basename($attachment) }}
                                                </div>
                                                <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 mt-1 fw-semibold" style="font-size: 0.70rem;">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    <!-- 3. PASSENGERS LIST ON LINKED BOOKING -->
    @if($chargeback->booking && $chargeback->booking->passengers->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-people-fill text-primary"></i> Passengers on Booking
                </h6>
                <span class="badge bg-primary-subtle text-primary font-monospace">{{ $chargeback->booking->passengers->count() }} Passenger(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-2">#</th>
                            <th class="py-2">Passenger Name</th>
                            <th class="py-2">Gender</th>
                            <th class="py-2">Date of Birth</th>
                            <th class="py-2">Ticket Number</th>
                            <th class="py-2">Seat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chargeback->booking->passengers as $idx => $pax)
                            <tr>
                                <td class="ps-3 py-2 font-monospace">{{ $idx + 1 }}</td>
                                <td class="py-2 fw-bold text-dark">{{ $pax->first_name }} {{ $pax->middle_name }} {{ $pax->last_name }}</td>
                                <td class="py-2">{{ $pax->gender ?: 'N/A' }}</td>
                                <td class="py-2">{{ $pax->dob ? $pax->dob->format('M d, Y') : 'N/A' }}</td>
                                <td class="py-2 font-monospace fw-semibold text-primary">{{ $pax->ticket_number ?: 'N/A' }}</td>
                                <td class="py-2 font-monospace">{{ $pax->seat_number ?: 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 4. REMARKS TIMELINE & ADD REMARK SECTION -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                <i class="bi bi-chat-left-text-fill text-success"></i> Remarks History &amp; Add New Note
            </h6>
        </div>
        <div class="card-body p-4">
            <!-- Add New Remark Form -->
            @if($chargeback->booking)
                <form action="{{ route('chargeback.remarks.store', $chargeback) }}" method="POST" class="mb-4 p-3 bg-light rounded border border-light-subtle">
                    @csrf
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                        <i class="bi bi-plus-circle-fill text-success me-1"></i> Post Remark to Booking (Chargeback Log)
                    </label>
                    <div class="mb-2">
                        <textarea name="remark" required rows="2" placeholder="Write chargeback update or remark for this booking..." class="form-control"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                            <i class="bi bi-send me-1"></i> Add Remark
                        </button>
                    </div>
                </form>
            @endif

            <!-- Remarks List -->
            <div class="vstack gap-2" style="max-height: 400px; overflow-y: auto;">
                @if($chargeback->booking && $chargeback->booking->bookingRemarks->isNotEmpty())
                    @foreach($chargeback->booking->bookingRemarks->sortByDesc('created_at') as $remark)
                        <div class="p-3 bg-white rounded border border-light-subtle shadow-xs">
                            <div class="d-flex justify-content-between align-items-center text-secondary mb-1 small">
                                <div>
                                    <strong class="text-primary">{{ $remark->user ? ($remark->user->alias_name ?: $remark->user->name) : 'System' }}</strong>
                                    <span class="badge bg-light text-secondary border ms-1 font-monospace" style="font-size: 0.7rem;">{{ strtoupper($remark->type) }}</span>
                                </div>
                                <span class="font-monospace text-muted" style="font-size: 0.75rem;">{{ $remark->created_at ? $remark->created_at->format('M d, Y h:i A') : '' }}</span>
                            </div>
                            <div class="small text-dark font-medium">{{ $remark->remark }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted small fst-italic py-3 text-center">No remarks recorded on this booking.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
