@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 py-3" style="max-width: 1200px;">
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary btn-sm px-2.5 py-1.5 rounded-3">
                    <i class="bi bi-arrow-left"></i> Back to Control Panel
                </a>
                <h1 class="h4 mb-0 fw-bold text-dark">
                    <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i> Upload Chargeback Data (CSV)
                </h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">
                Bulk upload historical or portal dispute records with automatic <strong>Case Number deduplication</strong> and column mapping.
            </p>
        </div>
        <div>
            <a href="{{ route('chargeback.csv.template') }}" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 rounded-3 shadow-xs">
                <i class="bi bi-download me-1"></i> Download CSV Template
            </a>
        </div>
    </div>

    <!-- Alert / Information Bar -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 py-3 px-4 mb-4 d-flex align-items-start gap-3">
        <i class="bi bi-shield-lock-fill text-info fs-4 mt-1"></i>
        <div>
            <h6 class="fw-bold mb-1 text-dark">Email Automation Protection Active</h6>
            <p class="mb-0 text-secondary small">
                Agent email alerts are <strong>strictly disabled</strong> during CSV bulk data uploads. None of your agents will receive notifications when uploading old or historical records.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Upload Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                    <h6 class="mb-0 fw-bold text-dark text-uppercase small d-flex align-items-center gap-2">
                        <i class="bi bi-cloud-arrow-up-fill text-primary"></i> Upload CSV File &amp; Settings
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('chargeback.upload.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <!-- Dropzone Area -->
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-2">
                                Select or Drop CSV File <span class="text-danger">*</span>
                            </label>
                            <div id="dropzone_box" class="border border-2 border-dashed rounded-3 p-4 text-center bg-light" style="cursor: pointer; transition: all 0.2s;" onclick="document.getElementById('csv_file').click()">
                                <i class="bi bi-file-earmark-arrow-up text-primary display-5 d-block mb-2"></i>
                                <h6 class="fw-bold text-dark mb-1" id="dropzone_text">Click to choose a CSV file or drag and drop here</h6>
                                <p class="text-muted small mb-0" id="file_details">Supported format: .csv (Max: 50MB)</p>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required class="d-none" onchange="handleFileSelected(this)">
                            </div>
                            @error('csv_file')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>

                        <!-- Duplicate Handling Strategy -->
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-2">
                                <i class="bi bi-layers-half text-warning me-1"></i> Case Number Deduplication Rule
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border p-3 rounded-3 h-100 position-relative cursor-pointer duplicate-card active-card" onclick="selectDuplicateOption('skip')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="duplicate_action" id="dup_skip" value="skip" checked>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="dup_skip">
                                                Skip Existing Duplicates <span class="badge bg-success-subtle text-success border border-success-subtle small ms-1">Recommended</span>
                                            </label>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2 ps-4">
                                            If a <code>Case Number</code> already exists in the system or file, only the first record is kept and duplicate rows are safely skipped.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border p-3 rounded-3 h-100 position-relative cursor-pointer duplicate-card" onclick="selectDuplicateOption('update')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="duplicate_action" id="dup_update" value="update">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="dup_update">
                                                Update Existing Cases
                                            </label>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2 ps-4">
                                            If a <code>Case Number</code> already exists, update its status, amounts, and dates with the new values from this uploaded sheet.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" name="auto_link_bookings" id="auto_link_bookings" value="1" checked>
                                <label class="form-check-label fw-bold text-dark" for="auto_link_bookings">
                                    Automatically Link to CRM Bookings by PNR
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4">
                                Cross-references Airline PNR / Booking ID with your CRM database and associates passengers and agent information automatically.
                            </small>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary fw-semibold px-4 py-2">
                                Cancel
                            </a>
                            <button type="submit" id="btnSubmitUpload" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                                <i class="bi bi-upload me-1"></i> Start CSV Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instructions & Column Mapping Sidebar -->
        <div class="col-lg-4">
            <!-- Stats overview -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3 text-uppercase small">
                        <i class="bi bi-database text-primary me-1"></i> System Status
                    </h6>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Total Cases in Database</span>
                        <strong class="font-monospace text-dark">{{ number_format($recordsCount) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Configured Portals</span>
                        <strong class="font-monospace text-primary">{{ $portalsCount }}</strong>
                    </div>
                </div>
            </div>

            <!-- Header Guidelines -->
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                    <h6 class="mb-0 fw-bold text-dark text-uppercase small">
                        <i class="bi bi-check2-circle text-success me-1"></i> Exact Column Order (29 Columns)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-2">The system maps columns matching your reference consolidated sheet:</p>
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <tr><td class="text-muted font-monospace" style="width: 28px;">1</td><td><strong>Portal</strong></td></tr>
                                <tr><td class="text-muted font-monospace">2</td><td><strong>Received Date</strong></td></tr>
                                <tr><td class="text-muted font-monospace">3</td><td>Received Month</td></tr>
                                <tr><td class="text-muted font-monospace">4</td><td>Booking Date</td></tr>
                                <tr><td class="text-muted font-monospace">5</td><td>Booking Month</td></tr>
                                <tr><td class="text-muted font-monospace">6</td><td>Deadline Date</td></tr>
                                <tr><td class="text-muted font-monospace">7</td><td>Action Taken Date</td></tr>
                                <tr><td class="text-muted font-monospace">8</td><td>CBK Status (Reference)</td></tr>
                                <tr><td class="text-muted font-monospace">9</td><td>Case (New / Old)</td></tr>
                                <tr><td class="text-muted font-monospace">10</td><td><strong>Dispute Type</strong></td></tr>
                                <tr><td class="text-muted font-monospace">11</td><td>Current status</td></tr>
                                <tr><td class="text-muted font-monospace">12</td><td>PNR</td></tr>
                                <tr><td class="text-muted font-monospace">13</td><td>AgentName</td></tr>
                                <tr><td class="text-muted font-monospace">14</td><td>Currency</td></tr>
                                <tr><td class="text-muted font-monospace">15</td><td>Total booking amount</td></tr>
                                <tr><td class="text-muted font-monospace">16</td><td>Disputed Amount ($)</td></tr>
                                <tr><td class="text-muted font-monospace">17</td><td><strong class="text-danger">Case Number (Unique Key)</strong></td></tr>
                                <tr><td class="text-muted font-monospace">18</td><td>CC Brand</td></tr>
                                <tr><td class="text-muted font-monospace">19</td><td>Card No.</td></tr>
                                <tr><td class="text-muted font-monospace">20</td><td>Reason Code</td></tr>
                                <tr><td class="text-muted font-monospace">21</td><td>Reason description</td></tr>
                                <tr><td class="text-muted font-monospace">22</td><td>Verticle</td></tr>
                                <tr><td class="text-muted font-monospace">23</td><td>Remarks</td></tr>
                                <tr><td class="text-muted font-monospace">24</td><td>Passenger</td></tr>
                                <tr><td class="text-muted font-monospace">25</td><td>Service provided</td></tr>
                                <tr><td class="text-muted font-monospace">26</td><td>Shift time</td></tr>
                                <tr><td class="text-muted font-monospace">27</td><td>Shift Month</td></tr>
                                <tr><td class="text-muted font-monospace">28</td><td>Statemen Month</td></tr>
                                <tr><td class="text-muted font-monospace">29</td><td>sds</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.duplicate-card {
    transition: all 0.2s ease;
    border-color: #e2e8f0 !important;
}
.duplicate-card.active-card {
    border-color: #0d6efd !important;
    background-color: #f8faff;
}
</style>

<script>
    function selectDuplicateOption(val) {
        document.getElementById('dup_' + val).checked = true;
        document.querySelectorAll('.duplicate-card').forEach(c => c.classList.remove('active-card'));
        event.currentTarget.classList.add('active-card');
    }

    function handleFileSelected(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        const dropzone = document.getElementById('dropzone_box');
        const title = document.getElementById('dropzone_text');
        const details = document.getElementById('file_details');

        title.textContent = file.name;
        title.className = 'fw-bold text-success mb-1';
        details.textContent = 'Size: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB • Ready to upload';
        dropzone.classList.remove('bg-light');
        dropzone.classList.add('bg-success-subtle', 'border-success');
    }

    document.getElementById('uploadForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitUpload');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading &amp; Processing...';
    });
</script>
@endsection
