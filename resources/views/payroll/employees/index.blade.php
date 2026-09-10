@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-person-badge-fill text-primary"></i> Employee Directory &amp; Payroll Management
            </h1>
            <p class="text-secondary small mb-0">Confidential HR &amp; Accounts Portal: Manage employee records, statutory details, CTC, and leave balances.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.payslips.create') }}" class="btn btn-primary fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-plus-fill"></i> Generate Payslip
            </a>
            <a href="{{ route('payroll.leaves.index') }}" class="btn btn-outline-secondary fw-bold d-inline-flex align-items-center gap-2">
                <i class="bi bi-calendar-check-fill text-success"></i> Leave Balances
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('payroll.employees.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, code, alias, email, or PAN..." class="form-control bg-white border-light-subtle text-dark">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">All Roles</option>
                        <option value="agent" {{ request('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                        <option value="ticketing" {{ request('role') === 'ticketing' ? 'selected' : '' }}>Ticketing Desk</option>
                        <option value="changes" {{ request('role') === 'changes' ? 'selected' : '' }}>Booking Changes Desk</option>
                        <option value="mis" {{ request('role') === 'mis' ? 'selected' : '' }}>MIS Agent</option>
                        <option value="hr" {{ request('role') === 'hr' ? 'selected' : '' }}>HR Operations</option>
                        <option value="accounts" {{ request('role') === 'accounts' ? 'selected' : '' }}>Accounts &amp; Payroll</option>
                        <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="employment_type" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">All Employment</option>
                        <option value="Permanent" {{ request('employment_type') === 'Permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="Probation" {{ request('employment_type') === 'Probation' ? 'selected' : '' }}>Probation</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-fill"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
                    @if(request()->hasAny(['q', 'role', 'employment_type']))
                        <a href="{{ route('payroll.employees.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i> Employee Profiles &amp; Payroll
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $users->total() }} Employees</span>
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-white table-hover table-striped align-middle mb-0 text-nowrap">
                <thead>
                    <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                        <th class="px-3 py-3">Emp Code</th>
                        <th class="px-3 py-3">Employee Details</th>
                        <th class="px-3 py-3">Designation &amp; Role</th>
                        <th class="px-3 py-3">Status / Tenure</th>
                        <th class="px-3 py-3 text-end">Monthly Rate</th>
                        <th class="px-3 py-3 text-center">Leave Balances</th>
                        <th class="px-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $emp)
                        <tr>
                            <!-- Employee Code -->
                            <td class="px-3 py-3 font-monospace fw-bold text-primary">
                                {{ $emp->employee_code ?: 'EMP-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <!-- Employee Details -->
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                <div class="small text-muted font-monospace">{{ $emp->alias_name }} &bull; {{ $emp->email }}</div>
                            </td>
                            <!-- Designation & Role -->
                            <td class="px-3 py-3">
                                <div class="fw-semibold text-dark">{{ $emp->designation ?: 'N/A' }}</div>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-uppercase" style="font-size: 0.72rem;">
                                    {{ $emp->role }}
                                </span>
                            </td>
                            <!-- Employment Type & 90 Days Logic -->
                            <td class="px-3 py-3">
                                @php
                                    $computed = $emp->computed_employment_type;
                                    $isPermanent = strtolower($computed) === 'permanent';
                                @endphp
                                <div>
                                    @if($isPermanent)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                            <i class="bi bi-shield-check me-1"></i> Permanent
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold">
                                            <i class="bi bi-hourglass-split me-1"></i> Probation
                                        </span>
                                    @endif
                                </div>
                                @if($emp->joining_date)
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        Joined: {{ $emp->joining_date->format('d-M-Y') }}
                                        ({{ \Carbon\Carbon::parse($emp->joining_date)->diffInDays(now()) }}d)
                                    </div>
                                @endif
                            </td>
                            <!-- Monthly Salary Rate -->
                            <td class="px-3 py-3 text-end font-mono">
                                @if($emp->earning && $emp->earning->total_rate > 0)
                                    <div class="fw-bold text-dark">&#8377; {{ number_format($emp->earning->total_rate, 2) }}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Basic: &#8377;{{ number_format($emp->earning->basic_salary, 0) }}</div>
                                @else
                                    <span class="text-secondary small fst-italic">Not Configured</span>
                                @endif
                            </td>
                            <!-- Leave Balances -->
                            <td class="px-3 py-3 text-center">
                                @if($emp->leaveBalance)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle me-1" title="Earned Leaves">
                                        EL: {{ number_format($emp->leaveBalance->el_balance, 1) }}
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" title="Casual Leaves">
                                        CL: {{ number_format($emp->leaveBalance->cl_balance, 1) }}
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Unpaid Leaves">
                                        UL: {{ number_format($emp->leaveBalance->ul_balance, 1) }}
                                    </span>
                                @else
                                    <span class="text-muted small">0.0 / 0.0</span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('payroll.employees.edit', $emp) }}" class="btn btn-outline-primary btn-sm fw-semibold" title="Edit Full Profile & Payroll">
                                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                                    </a>
                                    <a href="{{ route('payroll.payslips.create', ['user_id' => $emp->id]) }}" class="btn btn-outline-success btn-sm fw-semibold" title="Generate Payslip">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No employee records found matching filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
