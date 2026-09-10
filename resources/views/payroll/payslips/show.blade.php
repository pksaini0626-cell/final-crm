@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header & Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-check-fill text-success"></i> Payslip:
                <span class="font-mono text-primary">{{ $payslip->payslip_number }}</span>
            </h1>
            <p class="text-secondary small mb-0">{{ $payslip->user->name }} ({{ $payslip->user->alias_name }}) &bull; Pay Period: <strong>{{ $payslip->period }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.payslips.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> All Payslips
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="{{ route('payroll.payslips.download-pdf', $payslip) }}" class="btn btn-primary btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-lock-fill"></i> Download Password-Protected PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- PDF Password Notification Alert -->
    <div class="alert alert-primary border border-primary-subtle d-flex align-items-center justify-content-between p-3 rounded-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-3 text-primary"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
                <div class="fw-bold text-dark">Password-Protected PDF Security</div>
                <div class="small text-secondary">
                    This payslip PDF is encrypted. The password to open the file is the employee's <strong>PAN in UPPERCASE</strong> (<code>{{ $payslip->pdf_password }}</code>).
                </div>
            </div>
        </div>
        <a href="{{ route('payroll.payslips.download-pdf', $payslip) }}" class="btn btn-primary fw-bold shadow-sm text-nowrap">
            <i class="bi bi-download me-1"></i> Download PDF
        </a>
    </div>

    <!-- Main Payslip Document Container -->
    <div class="card bg-white border shadow-sm p-4 p-md-5 mb-5 mx-auto" style="max-width: 960px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <div class="h3 fw-black text-dark text-uppercase mb-1">Calling Genie LLP</div>
                <div class="text-secondary small">Corporate Headquarters &amp; Operations Network</div>
                <div class="text-muted small mt-1 font-mono">Reference: {{ $payslip->payslip_number }}</div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white px-3 py-2 fs-6 text-uppercase fw-bold">
                    Payslip - {{ strtoupper($payslip->month_name) }} {{ $payslip->year }}
                </div>
                <div class="text-muted small mt-2">
                    Generated: {{ $payslip->created_at ? $payslip->created_at->format('d-M-Y') : now()->format('d-M-Y') }}
                </div>
            </div>
        </div>

        <!-- Employee Info Grid -->
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered mb-0 small">
                <tbody>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary" style="width: 18%;">Employee Code</td>
                        <td class="fw-bold font-mono text-primary" style="width: 32%;">{{ $payslip->user->employee_code ?: 'EMP-' . str_pad($payslip->user->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary" style="width: 18%;">Employee Name</td>
                        <td class="fw-bold text-dark" style="width: 32%;">{{ $payslip->user->official_name }}</td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Designation</td>
                        <td class="fw-semibold">{{ $payslip->user->designation ?: strtoupper($payslip->user->role) }}</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Location</td>
                        <td>{{ $payslip->user->location ?: 'New Delhi' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Date of Joining</td>
                        <td>{{ $payslip->user->joining_date ? $payslip->user->joining_date->format('d-M-Y') : 'N/A' }}</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Employment Type</td>
                        <td><span class="badge bg-success-subtle text-success border">{{ $payslip->user->computed_employment_type }}</span></td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Bank Account</td>
                        <td class="font-mono">{{ $payslip->user->account_number ?: 'N/A' }}</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary">PAN</td>
                        <td class="font-mono fw-bold">{{ $payslip->user->pan ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">PF UAN</td>
                        <td class="font-mono">{{ $payslip->user->pf_uan ?: 'N/A' }}</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary">ESI Number</td>
                        <td class="font-mono">{{ $payslip->user->esi_number ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Month Days</td>
                        <td class="fw-bold">{{ $payslip->total_days_in_month }} Days</td>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Total Paid Days</td>
                        <td class="fw-bold text-success fs-6 font-mono">{{ number_format($payslip->paid_days, 1) }} Days</td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold text-uppercase text-secondary">Working Days</td>
                        <td>{{ number_format($payslip->working_days, 1) }} Days</td>
                       
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- EARNINGS (INR) Table (Matching user's exact uploaded image) -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr style="background-color: #000000; color: #ffffff;" class="text-center text-uppercase small">
                        <th colspan="5" class="py-2 fw-black tracking-wide">EARNINGS (INR)</th>
                    </tr>
                    <tr style="background-color: #d1d5db; color: #111827;" class="text-uppercase small font-bold">
                        <th style="width: 36%;">COMPONENTS</th>
                        <th style="width: 16%;" class="text-end">RATE</th>
                        <th style="width: 16%;" class="text-end">MONTHLY</th>
                        <th style="width: 16%;" class="text-end">ARREAR</th>
                        <th style="width: 16%;" class="text-end">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($payslip->earnings_data) && is_array($payslip->earnings_data))
                        @foreach($payslip->earnings_data as $item)
                            <tr>
                                <td class="fw-bold text-dark">{{ $item['component'] }}</td>
                                <td class="text-end font-mono">{{ number_format($item['rate'], 2) }}</td>
                                <td class="text-end font-mono">{{ number_format($item['monthly'], 2) }}</td>
                                <td class="text-end font-mono">{{ number_format($item['arrear'], 2) }}</td>
                                <td class="text-end font-mono fw-bold text-dark">{{ number_format($item['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr style="background-color: #e5e7eb; font-weight: 800; border-top: 2px solid #374151;">
                        <td class="fw-black text-dark text-uppercase">TOTAL EARNINGS</td>
                        <td class="text-end font-mono fw-black text-dark">{{ number_format($payslip->gross_rate, 2) }}</td>
                        <td class="text-end font-mono fw-black text-dark">{{ number_format($payslip->gross_monthly, 2) }}</td>
                        <td class="text-end font-mono fw-black text-dark">{{ number_format($payslip->gross_arrear, 2) }}</td>
                        <td class="text-end font-mono fw-black text-dark fs-6">{{ number_format($payslip->total_earnings, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deductions Table -->
        @if(!empty($payslip->deductions_data) && count($payslip->deductions_data) > 0)
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr style="background-color: #334155; color: #ffffff;" class="text-center text-uppercase small">
                        <th colspan="2" class="py-2 fw-black tracking-wide">DEDUCTIONS (INR)</th>
                    </tr>
                    <tr style="background-color: #e2e8f0; color: #1e293b;" class="text-uppercase small font-bold">
                        <th style="width: 70%;">COMPONENT</th>
                        <th style="width: 30%;" class="text-end">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payslip->deductions_data as $ded)
                        <tr>
                            <td>{{ $ded['component'] }}</td>
                            <td class="text-end font-mono text-danger">- {{ number_format($ded['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #fee2e2; font-weight: 800;">
                        <td class="fw-bold text-danger text-uppercase">TOTAL DEDUCTIONS</td>
                        <td class="text-end font-mono fw-bold text-danger">- {{ number_format($payslip->total_deductions, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Net Payable Amount Box -->
        <div class="p-4 rounded-3 border border-2 border-dark bg-light mb-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="small text-secondary text-uppercase fw-bold">Net Salary Payable (INR)</div>
                    <div class="display-6 fw-black font-mono text-dark">&#8377; {{ number_format($payslip->net_pay, 2) }}</div>
                    <div class="small text-muted fst-italic mt-1">Amount in Words: <strong>{{ $payslip->net_pay_words }}</strong></div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 small text-secondary">
                    <div>Gross Earnings: <strong>&#8377; {{ number_format($payslip->total_earnings, 2) }}</strong></div>
                    <div>Total Deductions: <strong class="text-danger">&#8377; {{ number_format($payslip->total_deductions, 2) }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        @if(!empty($payslip->remarks))
        <div class="p-3 bg-light rounded border border-light-subtle small mb-4">
            <strong>Remarks:</strong> {{ $payslip->remarks }}
        </div>
        @endif

        <!-- Footer -->
        <div class="text-center pt-4 border-top text-muted small">
            This is a system generated payslip from <b>Calling Genie LLP</b> and does not require a signature.
        </div>

    </div>

</div>
@endsection
