@extends('layouts.app')

@section('content')
<div x-data="{ saving: false }">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-ticket-perforated-fill text-dark"></i> Generate &amp; Email E-Ticket
                </h1>
                <span class="badge bg-dark t    ext-white border border-dark font-monospace fs-6">
                    #{{ $booking->booking_id }}
                </span>
            </div>
            <p class="text-secondary small mb-0">Edit passenger ticket/seat details, trip type, subject line, and send the official e-ticket to the customer.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('manager.tickets.preview', $booking) }}" target="_blank" class="btn btn-outline-info btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-pdf"></i> Preview PDF E-Ticket
            </a>
            <a href="{{ route('manager.tickets.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Queue
            </a>
        </div>
    </div>

    <!-- Error / Success Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please resolve the following errors:</div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        
        <!-- LEFT COLUMN: EDIT DETAILS & EMAIL SETTINGS -->
        <div class="col-lg-5">
            
            <!-- Quick Save Ticket Details Form -->
            <form action="{{ route('manager.tickets.update-ticket-details', $booking) }}" method="POST" class="card bg-dark border-secondary shadow-sm mb-4">
                @csrf
                <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> 1. Edit Ticket, Seats &amp; Trip Type
                    </h2>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes
                    </button>
                </div>
                <div class="card-body p-4">
                    <!-- Trip Type -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Trip Type</label>
                        <select name="trip_type" class="form-select border-warning text-warning font-semibold">
                            <option value="one_way" {{ $booking->trip_type === 'one_way' ? 'selected' : '' }}>One Way</option>
                            <option value="round_trip" {{ $booking->trip_type === 'round_trip' ? 'selected' : '' }}>Round Trip</option>
                            <option value="multi_city" {{ $booking->trip_type === 'multi_city' ? 'selected' : '' }}>Multi City</option>
                        </select>
                    </div>

                    <!-- Passenger Roster Edit Table -->
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-2">Passenger Ticket &amp; Seat Roster</label>
                    <div class="table-responsive rounded border border-secondary">
                        <table class="table table-dark table-striped table-bordered align-middle mb-0 small">
                            <thead>
                                <tr class="text-secondary text-uppercase">
                                    <th>Passenger</th>
                                    <th style="width: 140px;">Ticket #</th>
                                    <th style="width: 80px;">Seat #</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->passengers as $pax)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-white">{{ $pax->title }} {{ $pax->first_name }} {{ $pax->last_name }}</div>
                                            <small class="text-secondary font-monospace">{{ $pax->pax_index ?: 'P' . ($loop->index + 1) }}</small>
                                        </td>
                                        <td>
                                            <input type="text" name="passengers[{{ $pax->id }}][ticket_number]" value="{{ old("passengers.{$pax->id}.ticket_number", $pax->ticket_number) }}" placeholder="e.g. 0062451992" class="form-control form-control-sm font-monospace text-info">
                                        </td>
                                        <td>
                                            <input type="text" name="passengers[{{ $pax->id }}][seat_number]" value="{{ old("passengers.{$pax->id}.seat_number", $pax->seat_number) }}" placeholder="e.g. 14A" class="form-control form-control-sm font-monospace text-center">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            <!-- Send E-Ticket Form -->
            <form action="{{ route('manager.tickets.send', $booking) }}" method="POST" class="card bg-dark border-secondary shadow-sm">
                @csrf
                <div class="card-header bg-dark border-secondary py-3">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-send-fill text-success"></i> 2. Send E-Ticket Email Settings
                    </h2>
                </div>

                <div class="card-body p-4 vstack gap-3">
                    <!-- Recipient Email -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Recipient Email <span class="text-danger">*</span></label>
                        <input type="email" name="email_address" value="{{ old('email_address', $booking->email_address) }}" required class="form-control">
                    </div>

                    <!-- Subject Line -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Subject Line <span class="text-danger">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject', "Your E-Ticket Travel Itinerary - Ref: #{$booking->booking_id}") }}" required class="form-control">
                    </div>

                    <!-- 24/7 Support Contact Phone -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">24/7 Support Phone</label>
                        <input type="text" name="support_phone" value="{{ old('support_phone', '+1-888-476-0932') }}" class="form-control font-monospace">
                    </div>

                    <!-- Custom Instructions / Notes -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Custom Note to Customer (Optional)</label>
                        <textarea name="custom_note" rows="2" class="form-control" placeholder="Add custom instructions or baggage notes for the customer..."></textarea>
                    </div>

                    <!-- Booking Status Update -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Update Booking Status To <span class="text-danger">*</span></label>
                        <select name="booking_status" class="form-select border-success text-success fw-bold">
                            <option value="ticketed" {{ $booking->booking_status === 'ticketed' ? 'selected' : '' }}>Ticketed</option>
                            <option value="booking_complete" {{ $booking->booking_status === 'booking_complete' ? 'selected' : '' }}>Booking Complete</option>
                        </select>
                    </div>

                    <!-- Admin Notes for Internal Audit -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Internal Manager Notes (Optional)</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="Notes recorded in booking audit logs..."></textarea>
                    </div>

                    <!-- Submit Action Button -->
                    <div class="pt-2 vstack gap-2">
                        <button type="submit" class="btn btn-success fw-bold py-3 shadow d-flex align-items-center justify-content-center gap-2 fs-6">
                            <i class="bi bi-envelope-paper-fill"></i>
                            <span>Send E-Ticket &amp; Attach PDF</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RIGHT COLUMN: LIVE HTML EMAIL PREVIEW -->
        <div class="col-lg-7">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-eye text-info"></i> Live Email Template Preview
                    </h2>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">Customer Email View</span>
                </div>
                <div class="card-body p-0 bg-white overflow-hidden text-dark" style="border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                    @include('emails.customer_e_ticket', [
                        'booking' => $booking,
                        'supportPhone' => old('support_phone', '+1-888-476-0932'),
                        'customNote' => null
                    ])
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
