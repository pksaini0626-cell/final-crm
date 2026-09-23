@extends('layouts.app')

@section('content')
<div x-data="{ saving: false, eticketTopText: '{{ addslashes(old('eticket_top_text', $booking->eticket_top_text ?? '')) }}', supportPhone: '{{ addslashes(old('support_phone', '+1-888-476-0932')) }}', customNote: '{{ addslashes(old('custom_note', '')) }}' }">
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
            
            <!-- Quick Save E-Ticket Top Header Text Form -->
            <form action="{{ route('manager.tickets.update-top-text', $booking) }}" method="POST" class="card bg-dark border-secondary shadow-sm mb-4">
                @csrf
                <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-card-heading text-info"></i> E-Ticket Top Header Text
                    </h2>
                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small">Top of E-Ticket</span>
                </div>
                <div class="card-body p-3">
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                        Header Text Line (Appears before Passenger Details)
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               name="eticket_top_text" 
                               x-model="eticketTopText" 
                               @input="$nextTick(() => syncHtml())"
                               class="form-control font-semibold" 
                               placeholder="e.g. IMPORTANT NOTICE: Flight schedule updated / Check-in 3 hours prior..." 
                               value="{{ old('eticket_top_text', $booking->eticket_top_text ?? '') }}">
                        <button type="submit" class="btn btn-primary fw-bold px-3 d-flex align-items-center gap-1">
                            <i class="bi bi-save2 me-1"></i> Save
                        </button>
                    </div>
                    <div class="form-text text-secondary extra-small mt-1">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Text appears live at the top of the e-ticket preview after the header and before Passenger &amp; Ticket Details.
                    </div>
                </div>
            </form>

            <!-- Quick Save Ticket Details Form -->
            <form action="{{ route('manager.tickets.update-ticket-details', $booking) }}" method="POST" class="card bg-dark border-secondary shadow-sm mb-4">
                @csrf
                <input type="hidden" name="eticket_top_text" :value="eticketTopText">
                <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> 1. Edit Passenger Names, Tickets &amp; Seats
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
                    <label class="form-label text-secondary small fw-bold text-uppercase mb-2">Passenger Roster &amp; Details</label>
                    <div class="table-responsive rounded border border-secondary">
                        <table class="table table-dark table-striped table-bordered align-middle mb-0 small">
                            <thead>
                                <tr class="text-secondary text-uppercase extra-small">
                                    <th style="width: 70px;">Title</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th style="width: 120px;">Ticket #</th>
                                    <th style="width: 70px;">Seat #</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->passengers as $pax)
                                    <tr>
                                        <td>
                                            <select name="passengers[{{ $pax->id }}][title]" class="form-select form-select-sm p-1 text-center font-monospace">
                                                <option value="MR" {{ old("passengers.{$pax->id}.title", strtoupper($pax->title ?? '')) === 'MR' ? 'selected' : '' }}>MR</option>
                                                <option value="MRS" {{ old("passengers.{$pax->id}.title", strtoupper($pax->title ?? '')) === 'MRS' ? 'selected' : '' }}>MRS</option>
                                                <option value="MS" {{ old("passengers.{$pax->id}.title", strtoupper($pax->title ?? '')) === 'MS' ? 'selected' : '' }}>MS</option>
                                                <option value="MISS" {{ old("passengers.{$pax->id}.title", strtoupper($pax->title ?? '')) === 'MISS' ? 'selected' : '' }}>MISS</option>
                                                <option value="MSTR" {{ old("passengers.{$pax->id}.title", strtoupper($pax->title ?? '')) === 'MSTR' ? 'selected' : '' }}>MSTR</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="passengers[{{ $pax->id }}][first_name]" value="{{ old("passengers.{$pax->id}.first_name", $pax->first_name) }}" required class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="text" name="passengers[{{ $pax->id }}][middle_name]" value="{{ old("passengers.{$pax->id}.middle_name", $pax->middle_name) }}" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="text" name="passengers[{{ $pax->id }}][last_name]" value="{{ old("passengers.{$pax->id}.last_name", $pax->last_name) }}" required class="form-control form-control-sm">
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
            <form id="eticket-send-form" action="{{ route('manager.tickets.send', $booking) }}" method="POST" class="card bg-dark border-secondary shadow-sm">
                @csrf
                <input type="hidden" name="custom_html" id="custom_html_input">
                <input type="hidden" name="eticket_top_text" :value="eticketTopText">

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
                        <input type="text" name="support_phone" x-model="supportPhone" class="form-control font-monospace">
                    </div>

                    <!-- Custom Instructions / Notes -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Need Assistance or Changes? / Please Note (Optional)</label>
                        <textarea name="custom_note" x-model="customNote" rows="3" class="form-control" placeholder="Add custom instructions, baggage notes, or assistance information for the customer..."></textarea>
                        <div class="form-text text-info small mt-1"><i class="bi bi-lightning-charge-fill me-1"></i> Appears live in real-time in the email preview panel.</div>
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

        <!-- RIGHT COLUMN: LIVE HTML EMAIL PREVIEW & RICH TEXT EDITOR -->
        <div class="col-lg-7">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-info"></i> Live Rich Text Editor &amp; Email Preview
                    </h2>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" onclick="resetTemplate()" class="btn btn-outline-warning btn-sm py-1 px-2 font-semibold" title="Revert to Original Template">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Template
                        </button>
                        <span class="badge bg-info-subtle text-info border border-info-subtle">Customer Email View</span>
                    </div>
                </div>

                <!-- Rich Text Editor Formatting Toolbar -->
                <div class="bg-secondary bg-opacity-25 border-bottom border-secondary p-2 d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Text Formatting">
                        <button type="button" onclick="formatText('bold')" class="btn btn-outline-light py-1 px-2" title="Bold (Ctrl+B)">
                            <i class="bi bi-type-bold"></i>
                        </button>
                        <button type="button" onclick="formatText('italic')" class="btn btn-outline-light py-1 px-2" title="Italic (Ctrl+I)">
                            <i class="bi bi-type-italic"></i>
                        </button>
                        <button type="button" onclick="formatText('underline')" class="btn btn-outline-light py-1 px-2" title="Underline (Ctrl+U)">
                            <i class="bi bi-type-underline"></i>
                        </button>
                        <button type="button" onclick="formatText('strikeThrough')" class="btn btn-outline-light py-1 px-2" title="Strikethrough">
                            <i class="bi bi-type-strikethrough"></i>
                        </button>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Font Size">
                        <button type="button" onclick="formatText('fontSize', '2')" class="btn btn-outline-light py-1 px-2" title="Small Text">Small</button>
                        <button type="button" onclick="formatText('fontSize', '3')" class="btn btn-outline-light py-1 px-2" title="Normal Text">Normal</button>
                        <button type="button" onclick="formatText('fontSize', '5')" class="btn btn-outline-light py-1 px-2" title="Large Text">Large</button>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Text Alignment">
                        <button type="button" onclick="formatText('justifyLeft')" class="btn btn-outline-light py-1 px-2" title="Align Left">
                            <i class="bi bi-text-left"></i>
                        </button>
                        <button type="button" onclick="formatText('justifyCenter')" class="btn btn-outline-light py-1 px-2" title="Align Center">
                            <i class="bi bi-text-center"></i>
                        </button>
                        <button type="button" onclick="formatText('justifyRight')" class="btn btn-outline-light py-1 px-2" title="Align Right">
                            <i class="bi bi-text-right"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-1 me-1">
                        <span class="text-light small me-1">Color:</span>
                        <input type="color" onchange="formatText('foreColor', this.value)" class="form-control form-control-color form-control-sm p-0 border-0" title="Text Color" style="width: 28px; height: 28px; cursor: pointer;">
                        <input type="color" value="#ffffaa" onchange="formatText('hiliteColor', this.value)" class="form-control form-control-color form-control-sm p-0 border-0 ms-1" title="Highlight Color" style="width: 28px; height: 28px; cursor: pointer;">
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Insert Link">
                        <button type="button" onclick="addLink()" class="btn btn-outline-info py-1 px-2" title="Insert Link">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                        <button type="button" onclick="formatText('removeFormat')" class="btn btn-outline-secondary py-1 px-2" title="Clear Formatting">
                            <i class="bi bi-eraser"></i>
                        </button>
                    </div>
                </div>

                <!-- Rich Text Editable Container -->
                <div class="card-body p-0 bg-white overflow-auto text-dark position-relative" style="min-height: 500px; max-height: 75vh; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                    <div id="email-preview-container" contenteditable="true" style="outline: none; min-height: 500px; padding: 0;">
                        @include('emails.customer_e_ticket', [
                            'booking' => $booking,
                            'supportPhone' => old('support_phone', '+1-888-476-0932'),
                            'customNote' => null,
                            'topText' => old('eticket_top_text', $booking->eticket_top_text ?? '')
                        ])
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    let defaultTemplateHtml = '';

    function formatText(command, value = null) {
        document.execCommand(command, false, value);
        syncHtml();
    }

    function addLink() {
        const url = prompt('Enter URL link for insertion:', 'https://');
        if (url) {
            formatText('createLink', url);
        }
    }

    function resetTemplate() {
        if (confirm('Revert all live edits back to the default original template?')) {
            const container = document.getElementById('email-preview-container');
            if (container) {
                container.innerHTML = defaultTemplateHtml;
                syncHtml();
            }
        }
    }

    function syncHtml() {
        const container = document.getElementById('email-preview-container');
        const hiddenInput = document.getElementById('custom_html_input');
        if (container && hiddenInput) {
            hiddenInput.value = container.innerHTML;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('email-preview-container');
        const form = document.getElementById('eticket-send-form');

        if (container) {
            defaultTemplateHtml = container.innerHTML;
            syncHtml();

            container.addEventListener('input', syncHtml);
            container.addEventListener('keyup', syncHtml);
            container.addEventListener('blur', syncHtml);
        }

        if (form) {
            form.addEventListener('submit', function () {
                syncHtml();
            });
        }
    });
</script>
@endsection
