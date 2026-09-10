@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i> Monthly Payslips Ledger
            </h1>
            <p class="text-secondary small mb-0">Confidential HR &amp; Accounts Portal: Manage, generate, and download password-protected employee payslips.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.payslips.create') }}" class="btn btn-primary fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-plus-fill"></i> Generate New Payslip
            </a>
            <a href="{{ route('payroll.employees.index') }}" class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-person-lines-fill"></i> Employee Profiles
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('payroll.payslips.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by payslip #, name, or code..." class="form-control form-control-sm bg-white border-light-subtle text-dark">
                </div>
                <div class="col-md-3">
                    <select name="user_id" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->alias_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="month" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0,0,0,$m,10)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-1">
                    <select name="year" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-fill"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
                    @if(request()->hasAny(['q', 'user_id', 'month', 'year']))
                        <a href="{{ route('payroll.payslips.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Payslip Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary"></i> Generated Payslips
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $payslips->total() }} total</span>
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-white table-hover table-striped align-middle mb-0 text-nowrap">
                <thead>
                    <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                        <th class="px-3 py-3">Payslip #</th>
                        <th class="px-3 py-3">Employee</th>
                        <th class="px-3 py-3">Period</th>
                        <th class="px-3 py-3 text-center">Days (Paid / Total)</th>
                        <th class="px-3 py-3 text-end">Gross Earnings</th>
                        <th class="px-3 py-3 text-end">Deductions</th>
                        <th class="px-3 py-3 text-end">Net Pay</th>
                        <th class="px-3 py-3 text-end" style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $ps)
                        <tr>
                            <td class="px-3 py-3 font-monospace fw-bold text-primary">
                                <a href="{{ route('payroll.payslips.show', $ps) }}" class="text-decoration-none">
                                    {{ $ps->payslip_number }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $ps->user->name }}</div>
                                <div class="small text-muted font-monospace">{{ $ps->user->alias_name }} &bull; {{ $ps->user->employee_code ?: 'N/A' }}</div>
                            </td>
                            <td class="px-3 py-3 fw-semibold text-dark">
                                <i class="bi bi-calendar2-event text-secondary me-1"></i> {{ $ps->period }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">
                                    {{ number_format($ps->paid_days, 1) }} / {{ $ps->total_days_in_month }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-end font-mono fw-semibold text-dark">
                                &#8377; {{ number_format($ps->total_earnings, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end font-mono text-danger">
                                - &#8377; {{ number_format($ps->total_deductions, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end font-mono fw-bold text-success fs-6">
                                &#8377; {{ number_format($ps->net_pay, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('payroll.payslips.show', $ps) }}" class="btn btn-outline-secondary btn-sm" title="View Payslip">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('payroll.payslips.download-pdf', $ps) }}" class="btn btn-outline-primary btn-sm fw-semibold" title="Download Password-Protected PDF">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
                                    </a>
                                    <form action="{{ route('payroll.payslips.destroy', $ps) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payslip record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Payslip">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                No payslips found for selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payslips->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                {{ $payslips->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
