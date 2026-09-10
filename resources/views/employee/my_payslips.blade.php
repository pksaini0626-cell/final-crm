@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-primary"></i> My Payslips &amp; Leave Balances
            </h1>
            <p class="text-secondary small mb-0">View your monthly salary statements, leave records, and download encrypted payslips.</p>
        </div>
    </div>

    <!-- Top Cards: Profile & Leaves -->
    <div class="row g-4 mb-4">
        
        <!-- Employee Profile Summary -->
        <div class="col-lg-6">
            <div class="card bg-white border-light-subtle shadow-sm h-100">
                <div class="card-header bg-white border-light-subtle py-3">
                    <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i> Employee Information
                    </h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">Employee Name</div>
                            <div class="fw-bold text-dark fs-6">{{ $user->name }}</div>
                            <div class="small text-secondary font-mono">{{ $user->alias_name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">Employee Code</div>
                            <div class="fw-bold text-primary font-mono fs-6">{{ $user->employee_code ?: 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">Designation</div>
                            <div class="fw-semibold text-dark">{{ $user->designation ?: strtoupper($user->role) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">Employment Status</div>
                            @if(strtolower($user->computed_employment_type) === 'permanent')
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                    <i class="bi bi-shield-check me-1"></i> Permanent
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-semibold">
                                    <i class="bi bi-hourglass-split me-1"></i> Probation
                                </span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">Date of Joining</div>
                            <div class="text-dark">{{ $user->joining_date ? $user->joining_date->format('d-M-Y') : 'Not Recorded' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small text-uppercase fw-bold">PAN / Tax Identifier</div>
                            <div class="font-mono fw-bold text-dark">{{ $user->pan ?: 'Not Provided' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balance Cards -->
        <div class="col-lg-6">
            <div class="card bg-white border-light-subtle shadow-sm h-100">
                <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-primary"></i> Current Leave Balances
                    </h2>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Updated</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-info-subtle border border-info-subtle">
                                <div class="text-uppercase small fw-bold text-info">Earned Leave (EL)</div>
                                <div class="display-6 fw-bold text-info font-mono my-1">
                                    {{ $user->leaveBalance ? number_format($user->leaveBalance->el_balance, 1) : '0.0' }}
                                </div>
                                <div class="small text-muted" style="font-size: 0.72rem;">Paid Annual</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                                <div class="text-uppercase small fw-bold text-primary">Casual Leave (CL)</div>
                                <div class="display-6 fw-bold text-primary font-mono my-1">
                                    {{ $user->leaveBalance ? number_format($user->leaveBalance->cl_balance, 1) : '0.0' }}
                                </div>
                                <div class="small text-muted" style="font-size: 0.72rem;">Paid Casual</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle">
                                <div class="text-uppercase small fw-bold text-danger">Unpaid Leave (UL)</div>
                                <div class="display-6 fw-bold text-danger font-mono my-1">
                                    {{ $user->leaveBalance ? number_format($user->leaveBalance->ul_balance, 1) : '0.0' }}
                                </div>
                                <div class="small text-muted" style="font-size: 0.72rem;">Deducted</div>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Password Security Banner -->
                    <div class="alert alert-light border border-secondary-subtle d-flex align-items-center gap-3 p-3 mt-4 mb-0 rounded-3">
                        <div class="fs-2 text-primary"><i class="bi bi-file-earmark-lock2-fill"></i></div>
                        <div class="small">
                            <strong class="text-dark">How to open your downloaded payslip:</strong><br>
                            Your payslip is encrypted for confidential privacy. When prompted for password in Adobe Acrobat or PDF viewer, enter your <strong>PAN in UPPERCASE</strong>
                            @if(!empty($user->pan))
                                (<code>{{ strtoupper($user->pan) }}</code>)
                            @elseif(!empty($user->employee_code))
                                (<code>{{ strtoupper($user->employee_code) }}</code>)
                            @endif.
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Monthly Payslip History Table -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Payslip Statements
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-white table-hover align-middle mb-0 text-nowrap">
                <thead>
                    <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                        <th class="px-3 py-3">Payslip Reference</th>
                        <th class="px-3 py-3">Pay Period</th>
                        <th class="px-3 py-3 text-center">Paid Days</th>
                        <th class="px-3 py-3 text-end">Gross Earnings</th>
                        <th class="px-3 py-3 text-end">Total Deductions</th>
                        <th class="px-3 py-3 text-end">Net Take-Home Pay</th>
                        <th class="px-3 py-3 text-end">Download</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $ps)
                        <tr>
                            <td class="px-3 py-3 font-mono fw-bold text-primary">
                                {{ $ps->payslip_number }}
                            </td>
                            <td class="px-3 py-3 fw-bold text-dark">
                                <i class="bi bi-calendar2-event text-secondary me-1"></i> {{ $ps->period }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono">
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    {{ number_format($ps->paid_days, 1) }} / {{ $ps->total_days_in_month }} Days
                                </span>
                            </td>
                            <td class="px-3 py-3 text-end font-mono text-secondary">
                                &#8377; {{ number_format($ps->total_earnings, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end font-mono text-danger">
                                - &#8377; {{ number_format($ps->total_deductions, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end font-mono fw-bold text-success fs-6">
                                &#8377; {{ number_format($ps->net_pay, 2) }}
                            </td>
                            <td class="px-3 py-3 text-end">
                                <a href="{{ route('employee.payslips.download-pdf', $ps) }}" class="btn btn-primary btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-lock-fill"></i> Download PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No payslips have been published for your account yet.
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
