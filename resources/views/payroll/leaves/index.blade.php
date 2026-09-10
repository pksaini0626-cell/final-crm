@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i> Leave Balance Management
            </h1>
            <p class="text-secondary small mb-0">Confidential HR &amp; Accounts: Track Earned Leaves (EL), Casual Leaves (CL), and Unpaid Leaves (UL).</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Run Monthly Accrual Button -->
            <form action="{{ route('payroll.leaves.accrue') }}" method="POST" onsubmit="return confirm('Trigger monthly leave accrual for all permanent employees (+1.0 EL, +0.5 CL)?');">
                @csrf
                <button type="submit" class="btn btn-success fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill"></i> Run Monthly Leave Accrual
                </button>
            </form>
            <a href="{{ route('payroll.employees.index') }}" class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i> Employees
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Rules Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-white border-light-subtle shadow-sm p-3 border-start border-4 border-info">
                <div class="small fw-bold text-secondary text-uppercase">Earned Leave (EL)</div>
                <div class="h5 fw-bold text-dark mt-1 mb-1">+1.0 Day / Month</div>
                <div class="small text-muted">Automatically accrued every month for Permanent employees (>90 days tenure).</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-light-subtle shadow-sm p-3 border-start border-4 border-primary">
                <div class="small fw-bold text-secondary text-uppercase">Casual Leave (CL)</div>
                <div class="h5 fw-bold text-dark mt-1 mb-1">+0.5 Day / Month</div>
                <div class="small text-muted">Automatically accrued every month for Permanent employees (>90 days tenure).</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-light-subtle shadow-sm p-3 border-start border-4 border-danger">
                <div class="small fw-bold text-secondary text-uppercase">Unpaid Leave (UL)</div>
                <div class="h5 fw-bold text-dark mt-1 mb-1">Salary Proration</div>
                <div class="small text-muted">Deducted proportionally from gross salary components during monthly payslip generation.</div>
            </div>
        </div>
    </div>

    <!-- Search & List Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i> Leave Balance Directory
            </h2>
            <form action="{{ route('payroll.leaves.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search employee..." class="form-control form-control-sm bg-light">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-white table-hover table-striped align-middle mb-0 text-nowrap">
                <thead>
                    <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                        <th class="px-3 py-3">Employee</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-center">EL Balance</th>
                        <th class="px-3 py-3 text-center">CL Balance</th>
                        <th class="px-3 py-3 text-center">UL Accumulated</th>
                        <th class="px-3 py-3">Last Accrual</th>
                        <th class="px-3 py-3 text-end">Update Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $emp)
                        @php
                            $lb = $emp->leaveBalance;
                            $el = $lb ? (float)$lb->el_balance : 0.0;
                            $cl = $lb ? (float)$lb->cl_balance : 0.0;
                            $ul = $lb ? (float)$lb->ul_balance : 0.0;
                        @endphp
                        <tr>
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                <div class="small text-muted font-monospace">{{ $emp->alias_name }} &bull; {{ $emp->employee_code ?: 'EMP-' . $emp->id }}</div>
                            </td>
                            <td class="px-3 py-3">
                                @if(strtolower($emp->computed_employment_type) === 'permanent')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Permanent</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Probation</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono fw-bold fs-6 text-info">
                                {{ number_format($el, 1) }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono fw-bold fs-6 text-primary">
                                {{ number_format($cl, 1) }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono fw-bold fs-6 text-danger">
                                {{ number_format($ul, 1) }}
                            </td>
                            <td class="px-3 py-3 text-muted small">
                                {{ $lb && $lb->last_accrual_month ? $lb->last_accrual_month : 'None' }}
                            </td>
                            <td class="px-3 py-3 text-end">
                                <form action="{{ route('payroll.leaves.update', $emp) }}" method="POST" class="d-flex justify-content-end gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" step="0.5" min="0" name="el_balance" value="{{ $el }}" class="form-control form-control-sm font-mono text-center" style="width: 75px;" title="EL Balance">
                                    <input type="number" step="0.5" min="0" name="cl_balance" value="{{ $cl }}" class="form-control form-control-sm font-mono text-center" style="width: 75px;" title="CL Balance">
                                    <input type="number" step="0.5" min="0" name="ul_balance" value="{{ $ul }}" class="form-control form-control-sm font-mono text-center text-danger" style="width: 75px;" title="UL Balance">
                                    <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold" title="Save Balance">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                No employee records found.
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
