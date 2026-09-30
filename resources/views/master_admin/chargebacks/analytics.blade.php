@extends('layouts.app')

@section('content')
<div class="vstack gap-4 pb-5" x-data="{ activeTab: '{{ request('tab', 'footprints') }}' }">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-white p-4 rounded-3 border border-light-subtle shadow-sm">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-dark px-2.5 py-1 text-uppercase fw-bold letter-spacing-1 shadow-sm">
                    <i class="bi bi-shield-lock-fill me-1"></i> Master Admin Only
                </span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
                    Confidential Audit &amp; Analytics
                </span>
            </div>
            <h1 class="h3 fw-bolder text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-shield-shaded text-danger"></i> Chargeback Analytics &amp; User Footprints
            </h1>
            <p class="text-secondary small mb-0">
                Complete operational visibility: audit change logs, login sessions, remarks timeline, and daily/monthly productivity across all chargeback team users.
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-light text-dark border border-light-subtle px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-calendar3 me-1 text-primary"></i> {{ now()->format('M j, Y') }}
            </span>
            <span class="badge bg-light text-secondary border border-light-subtle px-3 py-2 fw-semibold shadow-sm font-monospace">
                <i class="bi bi-clock me-1 text-danger"></i> {{ now()->format('H:i:s') }}
            </span>
            <a href="{{ route('chargeback.index') }}" class="btn btn-outline-danger btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Chargeback Panel
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards (Today vs This Month) -->
    <div class="row g-3">
        <!-- Today's Work Summary -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-gradient" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #000;">
                <div class="card-body p-4 vstack justify-content-between gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-white/20 text-dark border border-white/30 text-uppercase fw-bold px-2 py-1 small">Today's Work</span>
                        <i class="bi bi-calendar-check fs-4 opacity-75"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold mb-1">{{ number_format($todayCreated + $todayUpdated + $todayRemarks) }}</div>
                        <div class="small opacity-90">Total Operational Actions Today</div>
                    </div>
                    <div class="pt-2 border-top border-white/20 d-flex justify-content-between small opacity-90">
                        <span><i class="bi bi-plus-circle me-1"></i> Created: <strong>{{ $todayCreated }}</strong></span>
                        <span><i class="bi bi-pencil-square me-1"></i> Updated: <strong>{{ $todayUpdated }}</strong></span>
                        <span><i class="bi bi-chat-text me-1"></i> Remarks: <strong>{{ $todayRemarks }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month's Work -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-gradient" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #000;">
                <div class="card-body p-4 vstack justify-content-between gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-white/20 text-dark border border-white/30 text-uppercase fw-bold px-2 py-1 small">This Month ({{ now()->format('M Y') }})</span>
                        <i class="bi bi-graph-up-arrow fs-4 opacity-75"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold mb-1">{{ number_format($monthCreated + $monthUpdated + $monthRemarks) }}</div>
                        <div class="small opacity-90">Total Month Actions</div>
                    </div>
                    <div class="pt-2 border-top border-white/20 d-flex justify-content-between small opacity-90">
                        <span><i class="bi bi-plus-circle me-1"></i> Cases: <strong>{{ $monthCreated }}</strong></span>
                        <span><i class="bi bi-pencil me-1"></i> Edits: <strong>{{ $monthUpdated }}</strong></span>
                        <span><i class="bi bi-currency-dollar me-1"></i> Disputed: <strong>${{ number_format($monthDisputedAmount, 0) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users & Logins Today -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-gradient" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #000;">
                <div class="card-body p-4 vstack justify-content-between gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-white/20 text-dark border border-white/30 text-uppercase fw-bold px-2 py-1 small">User Footprints</span>
                        <i class="bi bi-person-badge fs-4 opacity-75"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold mb-1">{{ $todayActiveUsersCount }} <span class="fs-6 fw-normal opacity-75">/ {{ $chargebackUsers->count() }}</span></div>
                        <div class="small opacity-90">Active Chargeback Users Today</div>
                    </div>
                    <div class="pt-2 border-top border-white/20 d-flex justify-content-between small opacity-90">
                        <span><i class="bi bi-box-arrow-in-right me-1"></i> Today Logins: <strong>{{ $todayLogins }}</strong></span>
                        <span><i class="bi bi-people me-1"></i> Total Staff: <strong>{{ $chargebackUsers->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total System Chargebacks -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-gradient" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #000;">
                <div class="card-body p-4 vstack justify-content-between gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-white/20 text-dark border border-white/30 text-uppercase fw-bold px-2 py-1 small">Total Portfolio</span>
                        <i class="bi bi-shield-exclamation fs-4 opacity-75"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold mb-1">{{ number_format($totalChargebacksCount) }}</div>
                        <div class="small opacity-90">All-Time Registered Chargebacks</div>
                    </div>
                    <div class="pt-2 border-top border-white/20 d-flex justify-content-between small opacity-90">
                        <span><i class="bi bi-cash-stack me-1"></i> Total Disputed:</span>
                        <strong class="font-monospace">${{ number_format($totalDisputedAmount, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="card border-light-subtle shadow-sm">
        <div class="card-header bg-white border-bottom p-2">
            <ul class="nav nav-pills card-header-pills gap-1">
                <li class="nav-item">
                    <button class="nav-link fw-semibold px-3 py-2" :class="{ 'active bg-danger text-dark': activeTab === 'footprints' }" @click="activeTab = 'footprints'">
                        <i class="bi bi-people-fill me-1.5"></i> Chargeback Users Footprints
                        <span class="badge bg-white text-danger ms-1.5">{{ count($userFootprints) }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold px-3 py-2" :class="{ 'active bg-danger text-dark': activeTab === 'changes' }" @click="activeTab = 'changes'">
                        <i class="bi bi-clock-history me-1.5"></i> What They Have Changed (Audit Trail)
                        <span class="badge bg-light text-dark ms-1.5 border">{{ $activities->total() }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold px-3 py-2" :class="{ 'active bg-danger text-dark': activeTab === 'remarks' }" @click="activeTab = 'remarks'">
                        <i class="bi bi-chat-left-text me-1.5"></i> Remarks Stream
                        <span class="badge bg-light text-dark ms-1.5 border">{{ count($remarks) }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold px-3 py-2" :class="{ 'active bg-danger text-dark': activeTab === 'chargebacks' }" @click="activeTab = 'chargebacks'">
                        <i class="bi bi-table me-1.5"></i> All Chargebacks List
                        <span class="badge bg-light text-dark ms-1.5 border">{{ $allChargebacks->total() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <!-- TAB 1: USER FOOTPRINTS & PRODUCTIVITY MATRIX -->
            <div x-show="activeTab === 'footprints'" class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-fingerprint text-danger"></i> Chargeback Team Footprints &amp; Productivity
                        </h2>
                        <p class="text-secondary small mb-0">Live login activity, today's workload, and month-to-date output per user.</p>
                    </div>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-secondary">
                            <tr>
                                <th class="ps-3 py-3">Chargeback User</th>
                                <th>Status</th>
                                <th>Last Login Footprint</th>
                                <th>Latest Action</th>
                                <th class="text-center bg-light-subtle">
                                    <span class="text-primary fw-bold"><i class="bi bi-calendar-check me-1"></i> Today's Work</span>
                                    <div class="text-muted" style="font-size: 0.7rem; font-weight: normal;">Created / Updated / Remarks</div>
                                </th>
                                <th class="text-center">
                                    <span class="text-success fw-bold"><i class="bi bi-calendar-month me-1"></i> This Month's Work</span>
                                    <div class="text-muted" style="font-size: 0.7rem; font-weight: normal;">Created / Updated / Remarks</div>
                                </th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userFootprints as $footprint)
                                @php
                                    $u = $footprint['user'];
                                    $hasWorkedToday = $footprint['today']['total'] > 0;
                                    $hasWorkedMonth = $footprint['month']['total'] > 0;
                                @endphp
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-circle bg-danger-subtle text-danger fw-bold d-flex align-items-center justify-content-center border border-danger-subtle" style="width: 40px; height: 40px; font-size: 1rem;">
                                                {{ strtoupper(substr($u->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $u->name }}</div>
                                                <div class="text-muted small font-monospace">{{ $u->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($u->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle me-1"></i> Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Deactivated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($footprint['last_login_at'])
                                            <div class="fw-semibold text-dark small">
                                                <i class="bi bi-box-arrow-in-right text-success me-1"></i>
                                                {{ \Carbon\Carbon::parse($footprint['last_login_at'])->format('M j, Y - h:i A') }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="text-primary">{{ \Carbon\Carbon::parse($footprint['last_login_at'])->diffForHumans() }}</span>
                                                @if($footprint['last_login_ip'])
                                                    • <span class="font-monospace">{{ $footprint['last_login_ip'] }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">Never logged in</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($footprint['last_action_at'])
                                            <div class="text-dark small fw-medium text-truncate" style="max-width: 250px;" title="{{ $footprint['last_action_desc'] }}">
                                                {{ $footprint['last_action_desc'] }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i> {{ \Carbon\Carbon::parse($footprint['last_action_at'])->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-muted small">No logged activity</span>
                                        @endif
                                    </td>
                                    <td class="text-center bg-light-subtle">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Cases Created Today">
                                                +{{ $footprint['today']['created'] }}
                                            </span>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Cases Updated Today">
                                                ✎ {{ $footprint['today']['updated'] }}
                                            </span>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" title="Remarks Added Today">
                                                💬 {{ $footprint['today']['remarks'] }}
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1 fw-bold" style="font-size: 0.75rem;">
                                            Total: {{ $footprint['today']['total'] }} actions
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" title="Cases Created This Month">
                                                +{{ $footprint['month']['created'] }}
                                            </span>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Cases Updated This Month">
                                                ✎ {{ $footprint['month']['updated'] }}
                                            </span>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" title="Remarks Added This Month">
                                                💬 {{ $footprint['month']['remarks'] }}
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1 fw-bold" style="font-size: 0.75rem;">
                                            Total: {{ $footprint['month']['total'] }} actions
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="?tab=changes&activity_user_id={{ $u->id }}" class="btn btn-outline-danger btn-sm px-2.5 py-1 fw-semibold" title="View exact changes made by this user">
                                            <i class="bi bi-search me-1"></i> Footprints
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                                        No users with the 'chargeback' role found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: WHAT THEY HAVE CHANGED (AUDIT TRAIL) -->
            <div x-show="activeTab === 'changes'" class="p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-danger"></i> Complete Audit Trail: What They Have Changed
                        </h2>
                        <p class="text-secondary small mb-0">Granular field-level changes, logins, case creations, remarks, and deletions.</p>
                    </div>
                </div>

                <!-- Filter Form for Audit Trail -->
                <form method="GET" action="{{ route('master-admin.chargebacks.analytics') }}" class="card bg-light border-light-subtle shadow-sm mb-4 p-3">
                    <input type="hidden" name="tab" value="changes">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Filter by User</label>
                            <select name="activity_user_id" class="form-select form-select-sm">
                                <option value="">All Chargeback Users</option>
                                @foreach($chargebackUsers as $cbUser)
                                    <option value="{{ $cbUser->id }}" {{ request('activity_user_id') == $cbUser->id ? 'selected' : '' }}>
                                        {{ $cbUser->name }} ({{ $cbUser->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Action Type</label>
                            <select name="activity_action" class="form-select form-select-sm">
                                <option value="">All Actions</option>
                                <option value="login" {{ request('activity_action') === 'login' ? 'selected' : '' }}>Login</option>
                                <option value="created" {{ request('activity_action') === 'created' ? 'selected' : '' }}>Case Created</option>
                                <option value="updated" {{ request('activity_action') === 'updated' ? 'selected' : '' }}>Case Updated / Fields Changed</option>
                                <option value="remark_added" {{ request('activity_action') === 'remark_added' ? 'selected' : '' }}>Remark Added</option>
                                <option value="csv_imported" {{ request('activity_action') === 'csv_imported' ? 'selected' : '' }}>CSV Bulk Import</option>
                                <option value="deleted" {{ request('activity_action') === 'deleted' ? 'selected' : '' }}>Case Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">From Date</label>
                            <input type="date" name="activity_date_from" value="{{ request('activity_date_from') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">To Date</label>
                            <input type="date" name="activity_date_to" value="{{ request('activity_date_to') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Search Case / PNR</label>
                            <input type="text" name="activity_search" value="{{ request('activity_search') }}" placeholder="Case # or PNR..." class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm" title="Apply Filters">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            @if(request()->hasAny(['activity_user_id', 'activity_action', 'activity_date_from', 'activity_date_to', 'activity_search']))
                                <a href="?tab=changes" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Activities Timeline / Table -->
                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-secondary">
                            <tr>
                                <th class="ps-3 py-3" style="width: 170px;">Date &amp; Time</th>
                                <th style="width: 180px;">User</th>
                                <th style="width: 140px;">Action</th>
                                <th style="width: 160px;">Case / PNR</th>
                                <th>Description &amp; Field Diffs</th>
                                <th class="text-end pe-3" style="width: 150px;">IP / Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $act)
                                @php
                                    $actionBadge = match($act->action) {
                                        'login' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                        'created' => 'bg-success-subtle text-success border-success-subtle',
                                        'updated' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                        'remark_added' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'deleted' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'csv_imported' => 'bg-secondary-subtle text-dark border-secondary-subtle',
                                        default => 'bg-light text-dark'
                                    };
                                    $actionIcon = match($act->action) {
                                        'login' => 'bi-box-arrow-in-right',
                                        'created' => 'bi-plus-circle-fill',
                                        'updated' => 'bi-pencil-fill',
                                        'remark_added' => 'bi-chat-left-dots-fill',
                                        'deleted' => 'bi-trash3-fill',
                                        'csv_imported' => 'bi-file-earmark-spreadsheet-fill',
                                        default => 'bi-circle'
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-semibold text-dark small font-monospace">
                                            {{ $act->created_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-muted small font-monospace">
                                            {{ $act->created_at->format('h:i:s A') }}
                                            <span class="badge bg-light text-secondary ms-1">{{ $act->created_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small">{{ $act->user ? $act->user->name : 'Unknown User' }}</div>
                                        <div class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            {{ $act->user ? $act->user->email : 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $actionBadge }} border px-2.5 py-1 small fw-bold text-uppercase d-inline-flex align-items-center gap-1">
                                            <i class="bi {{ $actionIcon }}"></i> {{ str_replace('_', ' ', $act->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($act->case_number)
                                            <div>
                                                <span class="badge bg-light text-dark border font-monospace fw-bold">
                                                    #{{ $act->case_number }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($act->pnr)
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-warning-emphasis font-monospace border border-warning-subtle">
                                                    PNR: {{ $act->pnr }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small mb-1">{{ $act->description }}</div>

                                        <!-- Field Differences (Old vs New) -->
                                        @if(!empty($act->changes) && is_array($act->changes) && $act->action === 'updated')
                                            <div class="bg-light p-2 rounded-2 border small mt-1 font-monospace" style="max-width: 600px;">
                                                <strong class="text-secondary d-block mb-1" style="font-size: 0.75rem;">FIELD CHANGES:</strong>
                                                <div class="vstack gap-1">
                                                    @foreach($act->changes as $field => $change)
                                                        @if(is_array($change) && isset($change['old']) && isset($change['new']))
                                                            <div class="d-flex flex-wrap align-items-center gap-1" style="font-size: 0.8rem;">
                                                                <span class="badge bg-white text-dark border fw-bold">{{ ucwords(str_replace('_', ' ', $field)) }}:</span>
                                                                <span class="text-danger text-decoration-line-through">{{ $change['old'] ?: '(empty)' }}</span>
                                                                <i class="bi bi-arrow-right text-muted"></i>
                                                                <span class="text-success fw-bold">{{ $change['new'] ?: '(empty)' }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif(!empty($act->changes['initial_state']) && $act->action === 'created')
                                            <div class="small text-muted font-monospace">
                                                Portal: <strong>{{ $act->changes['initial_state']['portal'] ?? 'N/A' }}</strong> | 
                                                Status: <strong>{{ $act->changes['initial_state']['current_status'] ?? 'N/A' }}</strong> |
                                                Amount: <strong>{{ $act->changes['initial_state']['currency'] ?? 'USD' }} {{ $act->changes['initial_state']['disputed_amount'] ?? '0' }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="small font-monospace text-secondary">{{ $act->ip_address ?: '—' }}</div>
                                        @if($act->user_agent)
                                            <div class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 140px;" title="{{ $act->user_agent }}">
                                                {{ $act->user_agent }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-50"></i>
                                        No audit footprints found matching the criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($activities->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>

            <!-- TAB 3: REMARKS STREAM -->
            <div x-show="activeTab === 'remarks'" class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-chat-left-dots text-danger"></i> Chargeback Remarks Stream
                        </h2>
                        <p class="text-secondary small mb-0">Chronological feed of all remarks, internal case notes, and updates posted by chargeback staff.</p>
                    </div>
                </div>

                <div class="vstack gap-3">
                    @forelse($remarks as $rmk)
                        <div class="card bg-white border border-light-subtle shadow-sm rounded-3">
                            <div class="card-body p-3 d-flex gap-3">
                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center border border-primary-subtle flex-shrink-0" style="width: 44px; height: 44px;">
                                    <i class="bi bi-chat-quote fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark">{{ $rmk->user ? $rmk->user->name : 'Chargeback User' }}</span>
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;">
                                                {{ $rmk->user ? $rmk->user->email : 'N/A' }}
                                            </span>
                                            @if($rmk->case_number)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace fw-bold">
                                                    Case #{{ $rmk->case_number }}
                                                </span>
                                            @endif
                                            @if($rmk->pnr)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                                                    PNR: {{ $rmk->pnr }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-muted small font-monospace">
                                            <i class="bi bi-clock me-1"></i> {{ $rmk->created_at->format('M j, Y - h:i A') }} ({{ $rmk->created_at->diffForHumans() }})
                                        </div>
                                    </div>
                                    <p class="text-dark mb-0 bg-light p-3 rounded-2 border border-light-subtle small">
                                        {{ $rmk->changes['remark'] ?? $rmk->description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted border rounded-3">
                            <i class="bi bi-chat-square-text fs-2 d-block mb-2 opacity-50"></i>
                            No remarks recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 4: ALL CHARGEBACKS MASTER LIST -->
            <div x-show="activeTab === 'chargebacks'" class="p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-table text-danger"></i> All Chargebacks Master Records
                        </h2>
                        <p class="text-secondary small mb-0">Complete master list of all chargeback cases across all portals with live status and booking links.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('chargeback.csv.export') }}" class="btn btn-outline-success btn-sm fw-bold shadow-sm">
                            <i class="bi bi-download me-1"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- Filter Controls -->
                <form method="GET" action="{{ route('master-admin.chargebacks.analytics') }}" class="card bg-light border-light-subtle shadow-sm mb-4 p-3">
                    <input type="hidden" name="tab" value="chargebacks">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Search Anything</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Case #, PNR, Card, Agent, Portal..." class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Portal</label>
                            <select name="portal" class="form-select form-select-sm">
                                <option value="">All Portals</option>
                                @foreach($portals as $portal)
                                    <option value="{{ $portal->name }}" {{ request('portal') === $portal->name ? 'selected' : '' }}>{{ $portal->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Dispute Type</label>
                            <select name="dispute_type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @foreach(['CHARGEBACK', 'ALERT', 'RDR', 'RETRIEVAL', 'FRAUD'] as $dt)
                                    <option value="{{ $dt }}" {{ request('dispute_type') === $dt ? 'selected' : '' }}>{{ $dt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Current Status</label>
                            <select name="current_status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach(['Proceed with chargeback', 'Won', 'Lost', 'Refunded', 'Voided', 'Chargeback received', 'Represent', 'Accepted', 'Declined'] as $st)
                                    <option value="{{ $st }}" {{ request('current_status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Received Date Range</label>
                            <div class="d-flex gap-1">
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" title="From">
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" title="To">
                            </div>
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm">
                                <i class="bi bi-funnel"></i>
                            </button>
                            @if(request()->hasAny(['search', 'portal', 'dispute_type', 'current_status', 'date_from', 'date_to']))
                                <a href="?tab=chargebacks" class="btn btn-outline-secondary btn-sm" title="Reset">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Chargebacks Master Table -->
                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-secondary">
                            <tr>
                                <th class="ps-3 py-3">Case Number</th>
                                <th>Received Date</th>
                                <th>Portal</th>
                                <th>Dispute Type</th>
                                <th>PNR / Booking</th>
                                <th>Disputed Amount</th>
                                <th>Current Status</th>
                                <th>Created By</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allChargebacks as $cb)
                                <tr>
                                    <td class="ps-3 py-3">
                                        <a href="{{ route('chargeback.show', $cb->id) }}" class="fw-bold text-danger text-decoration-none font-monospace">
                                            #{{ $cb->case_number }}
                                        </a>
                                        @if($cb->case_type === 'new')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">NEW</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size: 0.65rem;">OLD</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $cb->received_date ? $cb->received_date->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold">
                                            {{ $cb->portal }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            {{ $cb->dispute_type }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($cb->pnr)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace fw-bold">
                                                {{ $cb->pnr }}
                                            </span>
                                        @else
                                            <span class="text-muted small">No PNR</span>
                                        @endif
                                        @if($cb->booking_reference)
                                            <div class="text-muted small font-monospace mt-0.5">Ref: {{ $cb->booking_reference }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold font-monospace text-dark">
                                            {{ $cb->currency }} {{ number_format($cb->disputed_amount, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                            {{ $cb->current_status }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        <div class="fw-medium text-dark">{{ $cb->creator ? $cb->creator->name : ($cb->agent_name ?: 'System') }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ $cb->created_at ? $cb->created_at->format('M d, Y') : '' }}</div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('chargeback.show', $cb->id) }}" class="btn btn-outline-primary btn-sm px-2 py-1" title="View Case">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('chargeback.edit', $cb->id) }}" class="btn btn-outline-secondary btn-sm px-2 py-1" title="Edit Case">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        No chargeback records found matching current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($allChargebacks->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $allChargebacks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
