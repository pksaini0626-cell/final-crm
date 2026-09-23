@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 py-2">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="p-2 rounded-3 bg-danger bg-opacity-10 text-danger fs-5">
                    <i class="bi bi-shield-exclamation"></i>
                </span>
                <div>
                    <h1 class="h4 mb-0 fw-bold text-dark">Chargeback Control Panel</h1>
                    <p class="text-secondary small mb-0">Exclusively managed by Chargeback Desk • Dispute &amp; Case Tracking</p>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Upload CSV Button -->
            <a href="{{ route('chargeback.csv.upload') }}" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 shadow-sm rounded-3">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload CSV Data
            </a>

            <!-- Export CSV Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-success btn-sm fw-semibold px-3 py-2 shadow-sm rounded-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i> Export CSV
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
                    <li>
                        <a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="{{ route('chargeback.csv.export', request()->query()) }}">
                            <i class="bi bi-filter text-success"></i> Export Filtered Records ({{ number_format($chargebacks->total()) }})
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="exportSelectedRows()">
                            <i class="bi bi-check2-square text-primary"></i> Export Selected Cases (<span id="menu_selected_count">0</span>)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="{{ route('chargeback.csv.template') }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i> Download Blank CSV Template
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Add Portal Button -->
            <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalAddPortal">
                <i class="bi bi-plus-circle me-1"></i> Add Portal
            </button>

            <!-- New Chargeback Case Button -->
            <a href="{{ route('chargeback.create') }}" class="btn btn-danger btn-sm fw-bold px-3 py-2 shadow-sm rounded-3">
                <i class="bi bi-plus-lg me-1"></i> New Chargeback Case
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small fw-bold text-uppercase">Total Cases</span>
                        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-folder2-open"></i></span>
                    </div>
                    <div class="h4 mb-0 fw-bold text-dark font-monospace">{{ number_format($totalCases) }}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Filtered Records</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small fw-bold text-uppercase">Total Disputed</span>
                        <span class="badge bg-danger-subtle text-danger"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="h4 mb-0 fw-bold text-danger font-monospace">${{ number_format($totalDisputed, 2) }}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Total Exposure</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small fw-bold text-uppercase">Cases Won</span>
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <div class="h4 mb-0 fw-bold text-success font-monospace">{{ number_format($wonCases) }}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Won &amp; Recovered</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small fw-bold text-uppercase">In Progress / Pending</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="h4 mb-0 fw-bold text-warning-emphasis font-monospace">{{ number_format($pendingCases) }}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Under Review / Represent</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i> Filter &amp; Search Cases
            </h6>
            @if(request()->hasAny(['search', 'date_from', 'date_to', 'portal', 'cbk_status', 'case_type', 'dispute_type', 'current_status']))
                <a href="{{ route('chargeback.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2.5">
                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                </a>
            @endif
        </div>
        <div class="card-body p-3">
            <form action="{{ route('chargeback.index') }}" method="GET">
                <div class="row g-2">
                    <!-- Keyword Search -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Case #, Booking ID, PNR, Card No, Agent, Portal..." class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- Date Field Selector -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date Criteria</label>
                        <select name="date_field" class="form-select form-select-sm">
                            <option value="received_date" {{ request('date_field', 'received_date') === 'received_date' ? 'selected' : '' }}>Received Date</option>
                            <option value="booking_date" {{ request('date_field') === 'booking_date' ? 'selected' : '' }}>Booking Date</option>
                            <option value="deadline_date" {{ request('date_field') === 'deadline_date' ? 'selected' : '' }}>Deadline Date</option>
                            <option value="action_taken_date" {{ request('date_field') === 'action_taken_date' ? 'selected' : '' }}>Action Taken Date</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-lg-3 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>

                    <!-- Date To -->
                    <div class="col-lg-3 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>

                    <!-- Portal -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Portal</label>
                        <select name="portal" class="form-select form-select-sm">
                            <option value="">All Portals</option>
                            @foreach($portals as $portalItem)
                                <option value="{{ $portalItem->name }}" {{ request('portal') === $portalItem->name ? 'selected' : '' }}>{{ $portalItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dispute Type -->
                    <div class="col-lg-3 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Dispute Type</label>
                        <select name="dispute_type" class="form-select form-select-sm font-monospace fw-bold">
                            <option value="">All Dispute Types</option>
                            @foreach(['CHARGEBACK', 'RDR', 'ALERT', 'RETRIEVAL'] as $dt)
                                <option value="{{ $dt }}" {{ strtoupper(request('dispute_type', '')) === $dt ? 'selected' : '' }}>{{ $dt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Current Status -->
                    <div class="col-lg-3 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Current Status</label>
                        <select name="current_status" class="form-select form-select-sm">
                            <option value="">All Current Statuses</option>
                            @foreach(['Proceed with chargeback', 'Chargeback received', 'Represent', 'Accepted', 'Declined', 'Won', 'Lost', 'RDR-Lost', 'Recharge', 'Reversed', 'Refunded', 'Voided'] as $cs)
                                <option value="{{ $cs }}" {{ request('current_status') === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Case Type -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Case Type</label>
                        <select name="case_type" class="form-select form-select-sm">
                            <option value="">All (New/Old)</option>
                            <option value="new" {{ request('case_type') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="old" {{ request('case_type') === 'old' ? 'selected' : '' }}>Old</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-3 pt-2 border-top border-light-subtle">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Selection Floating Bar -->
    <div id="bulk_selection_bar" class="alert alert-primary py-2 px-3 mb-3 d-none align-items-center justify-content-between rounded-3 border-0 shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check2-circle fs-5 text-primary"></i>
            <span class="fw-bold small text-dark" id="selected_count_text">0 cases selected</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-1.5 rounded-3 shadow-xs" onclick="exportSelectedRows()">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Export Selected CSV
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-2.5 py-1.5 rounded-3 bg-white" onclick="clearSelection()">
                Clear Selection
            </button>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger font-monospace px-2.5 py-1.5">{{ $chargebacks->total() }} Record(s)</span>
                <span class="text-secondary small">Sorted by: <strong>{{ strtoupper(str_replace('_', ' ', $sortBy)) }} ({{ strtoupper($sortDir) }})</strong></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-secondary">Per page:</span>
                <form action="{{ route('chargeback.index') }}" method="GET" class="d-inline">
                    @foreach(request()->except(['per_page', 'page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <select name="per_page" onchange="this.form.submit()" class="form-select form-select-sm" style="width: 80px;">
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light text-secondary small text-uppercase font-semibold">
                    <tr>
                        <th class="ps-3 py-3" style="width: 40px;">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" onchange="toggleSelectAllRows(this)" title="Select all on this page">
                        </th>
                        <th class="py-3">
                            <a href="{{ route('chargeback.index', array_merge(request()->query(), ['sort_by' => 'case_number', 'sort_dir' => $sortBy === 'case_number' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-secondary text-decoration-none d-flex align-items-center gap-1">
                                Case # &amp; Type
                                <i class="bi {{ $sortBy === 'case_number' ? ($sortDir === 'asc' ? 'bi-sort-alpha-down text-primary' : 'bi-sort-alpha-up text-primary') : 'bi-arrow-down-up text-muted' }}"></i>
                            </a>
                        </th>
                        <th class="py-3">Booking / PNR</th>
                        <th class="py-3">Portal</th>
                        <th class="py-3">
                            <a href="{{ route('chargeback.index', array_merge(request()->query(), ['sort_by' => 'received_date', 'sort_dir' => $sortBy === 'received_date' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-secondary text-decoration-none d-flex align-items-center gap-1">
                                Received Date
                                <i class="bi {{ $sortBy === 'received_date' ? ($sortDir === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up text-primary') : 'bi-arrow-down-up text-muted' }}"></i>
                            </a>
                        </th>
                        <th class="py-3">
                            <a href="{{ route('chargeback.index', array_merge(request()->query(), ['sort_by' => 'deadline_date', 'sort_dir' => $sortBy === 'deadline_date' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-secondary text-decoration-none d-flex align-items-center gap-1">
                                Deadline
                                <i class="bi {{ $sortBy === 'deadline_date' ? ($sortDir === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up text-primary') : 'bi-arrow-down-up text-muted' }}"></i>
                            </a>
                        </th>
                        <th class="py-3">
                            <a href="{{ route('chargeback.index', array_merge(request()->query(), ['sort_by' => 'disputed_amount', 'sort_dir' => $sortBy === 'disputed_amount' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-secondary text-decoration-none d-flex align-items-center gap-1">
                                Disputed Amount
                                <i class="bi {{ $sortBy === 'disputed_amount' ? ($sortDir === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up text-primary') : 'bi-arrow-down-up text-muted' }}"></i>
                            </a>
                        </th>
                        <th class="py-3">
                            <a href="{{ route('chargeback.index', array_merge(request()->query(), ['sort_by' => 'dispute_type', 'sort_dir' => $sortBy === 'dispute_type' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-secondary text-decoration-none d-flex align-items-center gap-1">
                                Dispute Type
                                <i class="bi {{ $sortBy === 'dispute_type' ? ($sortDir === 'asc' ? 'bi-sort-alpha-down text-primary' : 'bi-sort-alpha-up text-primary') : 'bi-arrow-down-up text-muted' }}"></i>
                            </a>
                        </th>
                        <th class="py-3">Current Status</th>
                        <th class="py-3">Card Brand / Last 4</th>
                        <th class="pe-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chargebacks as $cb)
                        <tr>
                            <!-- Row Selection Checkbox -->
                            <td class="ps-3 py-3" style="width: 40px;">
                                <input type="checkbox" class="case-checkbox form-check-input" value="{{ $cb->id }}" onchange="onCaseCheckboxChange()">
                            </td>

                            <!-- Case Number & Case Type -->
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-dark font-monospace fs-6">{{ $cb->case_number }}</strong>
                                    <span class="badge {{ $cb->case_type === 'new' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} text-uppercase">
                                        {{ $cb->case_type }}
                                    </span>
                                </div>
                                <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                    Type: <span class="fw-bold text-uppercase text-primary">{{ $cb->dispute_type }}</span>
                                </div>
                            </td>

                            <!-- Booking Reference & PNR -->
                            <td class="py-3">
                                @if($cb->booking)
                                    <div>
                                        <button type="button" class="btn btn-link p-0 text-decoration-none fw-bold font-monospace text-primary" onclick="window.viewBookingDetails({{ $cb->booking_id }})">
                                            #{{ $cb->booking->booking_id }}
                                        </button>
                                    </div>
                                    <span class="badge bg-light text-primary border border-primary-subtle font-monospace">
                                        PNR: {{ $cb->pnr ?: ($cb->booking->airline_pnr ?: 'N/A') }}
                                    </span>
                                @elseif($cb->booking_reference)
                                    <div class="fw-bold font-monospace text-dark">#{{ $cb->booking_reference }}</div>
                                    <span class="badge bg-light text-secondary border font-monospace">PNR: {{ $cb->pnr ?: 'N/A' }}</span>
                                @else
                                    <span class="text-muted font-monospace">Unlinked (PNR: {{ $cb->pnr ?: 'N/A' }})</span>
                                @endif
                            </td>

                            <!-- Portal -->
                            <td class="py-3">
                                <span class="badge bg-dark-subtle text-dark border border-secondary-subtle fw-semibold font-monospace">
                                    {{ $cb->portal }}
                                </span>
                            </td>

                            <!-- Received Date -->
                            <td class="py-3">
                                <div class="fw-semibold text-dark">{{ $cb->received_date ? $cb->received_date->format('M d, Y') : 'N/A' }}</div>
                                <small class="text-muted font-monospace" style="font-size: 0.72rem;">Month: {{ $cb->received_month }}</small>
                            </td>

                            <!-- Deadline Date -->
                            <td class="py-3">
                                <div class="fw-bold {{ $cb->deadline_date && $cb->deadline_date->isPast() && !in_array($cb->current_status, ['Won', 'Lost', 'Refunded']) ? 'text-danger' : 'text-dark' }}">
                                    {{ $cb->deadline_date ? $cb->deadline_date->format('M d, Y') : 'N/A' }}
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Action: {{ $cb->action_taken_date ? $cb->action_taken_date->format('M d, Y') : 'N/A' }}</small>
                            </td>

                            <!-- Disputed Amount / Booking Total -->
                            <td class="py-3">
                                <div class="fw-bold text-danger font-monospace fs-6">
                                    {{ $cb->currency }} {{ number_format($cb->disputed_amount, 2) }}
                                </div>
                                <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                    Total: {{ $cb->currency }} {{ number_format($cb->total_booking_amount, 2) }}
                                </small>
                            </td>

                            <!-- Dispute Type -->
                            <td class="py-3">
                                @php
                                    $dtUpper = strtoupper($cb->dispute_type);
                                    $dtBadge = match($dtUpper) {
                                        'CHARGEBACK' => 'bg-danger text-white',
                                        'RDR' => 'bg-warning text-dark',
                                        'ALERT' => 'bg-info text-dark',
                                        'RETRIEVAL' => 'bg-primary text-white',
                                        default => 'bg-secondary text-white'
                                    };
                                @endphp
                                <span class="badge {{ $dtBadge }} font-monospace text-uppercase px-2.5 py-1.5">
                                    {{ $dtUpper }}
                                </span>
                            </td>

                            <!-- Current Status -->
                            <td class="py-3">
                                @php
                                    $curBadge = match(strtolower($cb->current_status)) {
                                        'won', 'accepted' => 'bg-success text-white',
                                        'lost', 'declined', 'rdr-lost' => 'bg-danger text-white',
                                        'represent', 'recharge' => 'bg-info-subtle text-info border border-info-subtle',
                                        'reversed', 'refunded', 'voided' => 'bg-secondary text-white',
                                        default => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $curBadge }} text-uppercase px-2.5 py-1.5">
                                    {{ $cb->current_status }}
                                </span>
                            </td>

                            <!-- Card Brand / Last 4 -->
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-1.5">
                                    <i class="bi bi-credit-card text-secondary"></i>
                                    <span class="fw-semibold text-dark">{{ $cb->cc_brand ?: 'CARD' }}</span>
                                    <span class="font-monospace text-muted">•••• {{ $cb->card_no ?: '----' }}</span>
                                </div>
                                @if($cb->reason_code)
                                    <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">Code: {{ $cb->reason_code }}</small>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="pe-3 py-3 text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('chargeback.show', $cb) }}" class="btn btn-outline-info btn-sm px-2 py-1 shadow-xs" title="View Full Case Record">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('chargeback.edit', $cb) }}" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" title="Edit Case">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @if($cb->booking_id)
                                        <button type="button" 
                                                class="btn btn-outline-success btn-sm px-2 py-1 shadow-xs" 
                                                title="Add Remark to Booking"
                                                onclick="openChargebackRemarkModal('{{ route('chargeback.remarks.store', $cb) }}', '{{ $cb->case_number }}')">
                                            <i class="bi bi-chat-left-text"></i>
                                        </button>
                                    @endif
                                    <form action="{{ route('chargeback.destroy', $cb) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Case #{{ $cb->case_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" title="Delete Case">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="text-secondary">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                    <div class="fw-bold fs-6">No chargeback records found.</div>
                                    <p class="small text-muted mb-3">Try adjusting your search criteria or register a new chargeback dispute.</p>
                                    <a href="{{ route('chargeback.create') }}" class="btn btn-danger btn-sm fw-bold">
                                        <i class="bi bi-plus-lg me-1"></i> Register New Chargeback
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($chargebacks->hasPages())
            <div class="card-footer bg-white py-3 border-top border-light-subtle d-flex justify-content-between align-items-center">
                <div class="small text-secondary">
                    Showing {{ $chargebacks->firstItem() }} to {{ $chargebacks->lastItem() }} of {{ $chargebacks->total() }} entries
                </div>
                <div>
                    {{ $chargebacks->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Add New Portal -->
<div class="modal fade" id="modalAddPortal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom border-light-subtle py-3">
                <h5 class="modal-title h6 fw-bold text-dark mb-0">
                    <i class="bi bi-plus-circle-fill text-primary me-1"></i> Add New Chargeback Portal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddPortal" onsubmit="submitNewPortal(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Portal Name <span class="text-danger">*</span></label>
                        <input type="text" id="new_portal_name" required placeholder="e.g. AMEX - DISPUTE, TOM - ALERT" class="form-control">
                        <div class="form-text small">Enter a unique portal identifier. It will immediately appear in portal filter and selection lists.</div>
                    </div>
                    <div id="portalAddAlert" class="alert d-none py-2 small mb-0"></div>
                </div>
                <div class="modal-footer bg-light border-top border-light-subtle py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitPortal" class="btn btn-primary btn-sm fw-bold px-3">
                        <i class="bi bi-save me-1"></i> Save Portal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Chargeback Remark -->
<div class="modal fade" id="modalChargebackRemark" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom border-light-subtle py-3">
                <h5 class="modal-title h6 fw-bold text-dark mb-0">
                    <i class="bi bi-chat-left-text-fill text-success me-1"></i> Post Chargeback Remark to Booking
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formChargebackRemark" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Case Reference</label>
                        <input type="text" id="remark_case_ref" readonly class="form-control font-monospace bg-light">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase">Chargeback Update / Note <span class="text-danger">*</span></label>
                        <textarea name="remark" required rows="3" placeholder="Enter progress note or dispute resolution detail..." class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top border-light-subtle py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                        <i class="bi bi-send me-1"></i> Post Remark
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleSelectAllRows(master) {
        document.querySelectorAll('.case-checkbox').forEach(cb => {
            cb.checked = master.checked;
        });
        updateSelectionUI();
    }

    function onCaseCheckboxChange() {
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const selected = document.querySelectorAll('.case-checkbox:checked');
        const count = selected.length;
        const bar = document.getElementById('bulk_selection_bar');
        const text = document.getElementById('selected_count_text');
        const menuCount = document.getElementById('menu_selected_count');
        if (menuCount) menuCount.textContent = count;

        if (count > 0) {
            bar.classList.remove('d-none');
            bar.classList.add('d-flex');
            text.textContent = count + (count === 1 ? ' case selected' : ' cases selected');
        } else {
            bar.classList.add('d-none');
            bar.classList.remove('d-flex');
            const master = document.getElementById('selectAllCheckbox');
            if (master) master.checked = false;
        }
    }

    function clearSelection() {
        document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = false);
        const master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
        updateSelectionUI();
    }

    function exportSelectedRows() {
        const checked = Array.from(document.querySelectorAll('.case-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('Please select at least one record using the checkboxes to export.');
            return;
        }
        const url = new URL("{{ route('chargeback.csv.export') }}", window.location.origin);
        url.searchParams.set('selected_ids', checked.join(','));
        window.location.href = url.toString();
    }

    function openChargebackRemarkModal(actionUrl, caseNumber) {
        const form = document.getElementById('formChargebackRemark');
        form.action = actionUrl;
        document.getElementById('remark_case_ref').value = 'Case #' + caseNumber;
        const modal = new bootstrap.Modal(document.getElementById('modalChargebackRemark'));
        modal.show();
    }

    async function submitNewPortal(event) {
        event.preventDefault();
        const input = document.getElementById('new_portal_name');
        const alertBox = document.getElementById('portalAddAlert');
        const btn = document.getElementById('btnSubmitPortal');
        const val = input.value.trim();

        if (!val) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
        alertBox.className = 'alert d-none';

        try {
            const response = await fetch("{{ route('chargeback.portals.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ name: val })
            });

            const res = await response.json();

            if (response.ok && res.success) {
                alertBox.className = 'alert alert-success py-2 small mb-0 d-block';
                alertBox.textContent = res.message || 'Portal added successfully!';
                input.value = '';
                setTimeout(() => {
                    location.reload();
                }, 800);
            } else {
                alertBox.className = 'alert alert-danger py-2 small mb-0 d-block';
                alertBox.textContent = res.message || (res.errors ? Object.values(res.errors)[0][0] : 'Failed to add portal.');
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 small mb-0 d-block';
            alertBox.textContent = 'Server error: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Portal';
        }
    }
</script>
@endsection
