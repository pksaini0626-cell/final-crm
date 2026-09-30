@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 py-2" style="max-width: 1300px;">
    <!-- Top Nav Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary btn-sm px-2.5 py-1.5 rounded-3">
                    <i class="bi bi-arrow-left"></i> Back to Control Panel
                </a>
                <h1 class="h4 mb-0 fw-bold text-dark">Edit Chargeback Case #{{ $chargeback->case_number }}</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Modify dispute tracking parameters, dates, status, or append a progress update note.</p>
        </div>
        <div>
            <a href="{{ route('chargeback.show', $chargeback) }}" class="btn btn-outline-info btn-sm fw-semibold">
                <i class="bi bi-eye me-1"></i> View Case Record
            </a>
        </div>
    </div>

    <!-- MAIN DISPUTE EDIT FORM -->
    <form action="{{ route('chargeback.update', $chargeback) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- 1. CASE & PORTAL IDENTIFICATION -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white border-top border-4 border-primary">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-shield-check text-primary"></i> 1. Portal &amp; Dispute Identification
                </h6>
                <span class="badge bg-primary-subtle text-primary font-monospace">Case #{{ $chargeback->case_number }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Case Number -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Case Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="case_number" value="{{ old('case_number', $chargeback->case_number) }}" required class="form-control font-monospace fw-bold">
                        @error('case_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Case Type -->
                    <div class="col-md-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Case Type <span class="text-danger">*</span>
                        </label>
                        <select name="case_type" required class="form-select">
                            <option value="new" {{ old('case_type', $chargeback->case_type) === 'new' ? 'selected' : '' }}>New</option>
                            <option value="old" {{ old('case_type', $chargeback->case_type) === 'old' ? 'selected' : '' }}>Old</option>
                        </select>
                        @error('case_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Portal -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Portal <span class="text-danger">*</span>
                        </label>
                        <select name="portal" required class="form-select font-monospace fw-semibold">
                            @foreach($portals as $portal)
                                <option value="{{ $portal->name }}" {{ old('portal', $chargeback->portal) === $portal->name ? 'selected' : '' }}>{{ $portal->name }}</option>
                            @endforeach
                        </select>
                        @error('portal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Dispute Type -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Dispute Type <span class="text-danger">*</span>
                        </label>
                        <select name="dispute_type" required class="form-select font-monospace fw-bold">
                            <option value="CHARGEBACK" {{ strtoupper(old('dispute_type', $chargeback->dispute_type)) === 'CHARGEBACK' ? 'selected' : '' }}>CHARGEBACK</option>
                            <option value="RDR" {{ strtoupper(old('dispute_type', $chargeback->dispute_type)) === 'RDR' ? 'selected' : '' }}>RDR</option>
                            <option value="ALERT" {{ strtoupper(old('dispute_type', $chargeback->dispute_type)) === 'ALERT' ? 'selected' : '' }}>ALERT</option>
                            <option value="RETRIEVAL" {{ strtoupper(old('dispute_type', $chargeback->dispute_type)) === 'RETRIEVAL' ? 'selected' : '' }}>RETRIEVAL</option>
                        </select>
                        @error('dispute_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Booking Reference -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Reference</label>
                        <input type="text" readonly value="{{ $chargeback->booking_reference ?: ($chargeback->booking ? $chargeback->booking->booking_id : 'N/A') }}" class="form-control font-monospace bg-light">
                    </div>

                    <!-- Airline PNR -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Airline PNR</label>
                        <input type="text" name="pnr" value="{{ old('pnr', $chargeback->pnr) }}" class="form-control font-monospace text-uppercase">
                    </div>

                    <!-- Agent Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent Name</label>
                        <input type="text" name="agent_name" value="{{ old('agent_name', $chargeback->agent_name) }}" class="form-control">
                    </div>

                    <!-- SDS -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SDS</label>
                        <input type="number" name="sds" value="{{ old('sds', $chargeback->sds) }}" class="form-control font-monospace">
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. DATES TIMELINE -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-calendar3 text-warning"></i> 2. Key Dates &amp; Deadlines
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Received Date -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Received Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="received_date" value="{{ old('received_date', $chargeback->received_date instanceof \DateTimeInterface ? $chargeback->received_date->format('Y-m-d') : ($chargeback->received_date ?: '')) }}" required class="form-control">
                    </div>

                    <!-- Received Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Received Month <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="received_month" value="{{ old('received_month', $chargeback->received_month) }}" required placeholder="YYYY-MM" class="form-control font-monospace">
                    </div>

                    <!-- Deadline Date (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1 d-flex justify-content-between">
                            <span>Deadline Date</span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Optional</span>
                        </label>
                        <input type="date" name="deadline_date" value="{{ old('deadline_date', $chargeback->deadline_date instanceof \DateTimeInterface ? $chargeback->deadline_date->format('Y-m-d') : ($chargeback->deadline_date ?: '')) }}" class="form-control">
                    </div>

                    <!-- Action Taken Date (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1 d-flex justify-content-between">
                            <span>Action Taken Date</span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Optional</span>
                        </label>
                        <input type="date" name="action_taken_date" value="{{ old('action_taken_date', $chargeback->action_taken_date instanceof \DateTimeInterface ? $chargeback->action_taken_date->format('Y-m-d') : ($chargeback->action_taken_date ?: '')) }}" class="form-control">
                    </div>

                    <!-- Booking Date -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Date</label>
                        <input type="date" name="booking_date" value="{{ old('booking_date', $chargeback->booking_date instanceof \DateTimeInterface ? $chargeback->booking_date->format('Y-m-d') : ($chargeback->booking_date ?: '')) }}" class="form-control">
                    </div>

                    <!-- Booking Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Booking Month</label>
                        <input type="text" name="booking_month" value="{{ old('booking_month', $chargeback->booking_month) }}" placeholder="YYYY-MM" class="form-control font-monospace">
                    </div>

                    <!-- Shift Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Shift Month (Optional)</label>
                        <input type="text" name="shift_month" value="{{ old('shift_month', $chargeback->shift_month ? ($chargeback->shift_month instanceof \DateTimeInterface ? $chargeback->shift_month->format('Y-m-d') : $chargeback->shift_month) : '') }}" placeholder="e.g. YYYY-MM or YYYY-MM-DD" class="form-control font-monospace">
                    </div>

                    <!-- Statement Month -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Statement Month (Optional)</label>
                        <input type="text" name="statement_month" value="{{ old('statement_month', $chargeback->statement_month ? ($chargeback->statement_month instanceof \DateTimeInterface ? $chargeback->statement_month->format('Y-m-d') : $chargeback->statement_month) : '') }}" placeholder="e.g. YYYY-MM or YYYY-MM-DD" class="form-control font-monospace">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. STATUSES & FINANCIALS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-currency-dollar text-success"></i> 3. Statuses &amp; Financial Parameters
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Current Status (Combines All Statuses) -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Current Status <span class="text-danger">*</span>
                        </label>
                        <select name="current_status" required class="form-select font-semibold">
                            @foreach([
                                'Chargeback received', 'Proceed with chargeback', 'Represent', 'Accepted',
                                'Declined', 'Won', 'Lost', 'RDR-Lost', 'Recharge', 'Reversed', 'Refunded', 'Voided'
                            ] as $cs)
                                <option value="{{ $cs }}" {{ old('current_status', $chargeback->current_status) === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Disputed Amount -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Disputed Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">{{ $chargeback->currency }}</span>
                            <input type="number" step="0.01" name="disputed_amount" value="{{ old('disputed_amount', $chargeback->disputed_amount) }}" required class="form-control font-monospace fw-bold text-danger">
                        </div>
                    </div>

                    <!-- Total Booking Amount -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total Booking Amount</label>
                        <input type="number" step="0.01" name="total_booking_amount" value="{{ old('total_booking_amount', $chargeback->total_booking_amount) }}" class="form-control font-monospace">
                    </div>

                    <input type="hidden" name="currency" value="{{ $chargeback->currency }}">
                </div>
            </div>
        </div>

        <!-- 4. CARD, REASONS & OPERATIONAL DETAILS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2 text-uppercase small">
                    <i class="bi bi-credit-card-2-front text-info"></i> 4. Card, Reasons &amp; Operational Details
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- CC Brand -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">CC Brand / Card Type</label>
                        <input type="text" name="cc_brand" value="{{ old('cc_brand', $chargeback->cc_brand) }}" class="form-control">
                    </div>

                    <!-- Card No (Last 4) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Card Last 4 Digits</label>
                        <input type="text" name="card_no" maxlength="4" value="{{ old('card_no', $chargeback->card_no) }}" class="form-control font-monospace">
                    </div>

                    <!-- Vertical -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Vertical</label>
                        <input type="text" name="vertical" value="{{ old('vertical', $chargeback->vertical) }}" class="form-control">
                    </div>

                    <!-- Service Provided -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Service Provided</label>
                        <input type="text" name="service_provided" value="{{ old('service_provided', $chargeback->service_provided) }}" class="form-control">
                    </div>

                    <!-- Reason Code -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reason Code</label>
                        <input type="text" name="reason_code" value="{{ old('reason_code', $chargeback->reason_code) }}" class="form-control font-monospace">
                    </div>

                    <!-- Shift Time -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Shift Time</label>
                        <input type="time" name="shift_time" value="{{ old('shift_time', $chargeback->shift_time) }}" class="form-control">
                    </div>

                    <!-- Reason Description -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reason Description for Chargeback</label>
                        <textarea name="reason_description" rows="2" class="form-control">{{ old('reason_description', $chargeback->reason_description) }}</textarea>
                    </div>

                    <!-- Attachments (Images Only) -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-images text-danger me-1"></i> Attach Dispute Images / Screenshots (Optional)</span>
                            <span class="badge bg-light text-danger border border-danger-subtle" style="font-size: 0.72rem;">Images Only (PNG, JPG, WEBP) &bull; No PDF</span>
                        </label>

                        <!-- Existing Images if any -->
                        @if(!empty($chargeback->attachments) && is_array($chargeback->attachments) && count($chargeback->attachments) > 0)
                            <div class="mb-3 p-3 bg-light rounded border">
                                <span class="small fw-bold text-muted text-uppercase d-block mb-2">
                                    <i class="bi bi-paperclip me-1"></i> Current Attached Images ({{ count($chargeback->attachments) }}):
                                </span>
                                <div class="row g-2">
                                    @foreach($chargeback->attachments as $path)
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <div class="card h-100 border shadow-xs p-1 position-relative text-center">
                                                <a href="{{ asset('storage/' . $path) }}" target="_blank" title="View Full Image">
                                                    <img src="{{ asset('storage/' . $path) }}" alt="Attachment" class="card-img-top rounded" style="height: 90px; object-fit: cover;">
                                                </a>
                                                <div class="small text-truncate px-1 mt-1 text-muted" style="font-size: 0.70rem;">
                                                    {{ basename($path) }}
                                                </div>
                                                <a href="{{ asset('storage/' . $path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1 mt-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-box-arrow-up-right"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <input type="file" name="attachments[]" id="form_attachments" multiple accept="image/png,image/jpeg,image/jpg,image/webp" onchange="handleImageFileSelection(this)" class="form-control">
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i>Usually the chargeback desk adds a single file, but you can attach multiple images if needed. Only image files (PNG, JPG, JPEG, WEBP) are allowed. PDF files are not accepted.
                        </div>
                        @error('attachments')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                        <!-- Live Preview Thumbnail Gallery for new selections -->
                        <div id="image_preview_gallery" class="row g-2 mt-2 d-none"></div>
                    </div>

                    <!-- New Remark Note -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">
                            Append New Progress Note / Remark to Linked Booking
                        </label>
                        <textarea name="new_remark" rows="2" placeholder="Add an optional progress note that will be logged into booking remarks..." class="form-control"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-body p-4 d-flex justify-content-end gap-2">
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary fw-semibold px-4 py-2">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Chargeback Case
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function handleImageFileSelection(input) {
        const gallery = document.getElementById('image_preview_gallery');
        gallery.innerHTML = '';

        if (!input.files || input.files.length === 0) {
            gallery.classList.add('d-none');
            return;
        }

        const allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        let hasInvalidFile = false;
        let invalidFileName = '';

        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const ext = file.name.split('.').pop().toLowerCase();

            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(ext)) {
                hasInvalidFile = true;
                invalidFileName = file.name;
                break;
            }
        }

        if (hasInvalidFile) {
            alert(`File "${invalidFileName}" is not a supported image format.\n\nOnly images (PNG, JPG, JPEG, WEBP) are permitted. PDF files are strictly not allowed.`);
            input.value = '';
            gallery.classList.add('d-none');
            return;
        }

        gallery.classList.remove('d-none');
        Array.from(input.files).forEach((file) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-lg-2';

            const card = document.createElement('div');
            card.className = 'card h-100 border shadow-xs p-1 position-relative text-center';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'card-img-top rounded object-fit-cover';
            img.style.height = '90px';
            img.style.objectFit = 'cover';

            const label = document.createElement('div');
            label.className = 'small text-truncate px-1 mt-1 text-muted';
            label.style.fontSize = '0.70rem';
            label.textContent = file.name;
            label.title = file.name;

            const sizeBadge = document.createElement('span');
            sizeBadge.className = 'badge bg-light text-secondary border mt-1';
            sizeBadge.style.fontSize = '0.65rem';
            sizeBadge.textContent = (file.size / 1024).toFixed(1) + ' KB';

            card.appendChild(img);
            card.appendChild(label);
            card.appendChild(sizeBadge);
            col.appendChild(card);
            gallery.appendChild(col);
        });
    }
</script>
@endsection
