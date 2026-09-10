@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-plus text-primary"></i> Generate Monthly Payslip
            </h1>
            <p class="text-secondary small mb-0">Select employee and enter working days &amp; leave usage. System dynamically prorates earnings and generates password-protected PDF.</p>
        </div>
        <a href="{{ route('payroll.payslips.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Payslips
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Errors occurred:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('payroll.payslips.store') }}" method="POST" id="payslipForm">
        @csrf

        <div class="row g-4">
            
            <!-- Parameters Selection Card -->
            <div class="col-12">
                <div class="card bg-white border-light-subtle shadow-sm">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-sliders text-primary"></i> 1. Select Employee &amp; Pay Period
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-end">
                            <!-- Employee Select -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Employee <span class="text-danger">*</span></label>
                                <select name="user_id" id="employeeSelect" required class="form-select select2">
                                    <option value="">-- Choose Employee --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" 
                                            {{ (old('user_id', $selectedUserId) == $emp->id) ? 'selected' : '' }}
                                            data-code="{{ $emp->employee_code }}"
                                            data-pan="{{ $emp->pan }}"
                                            data-type="{{ $emp->computed_employment_type }}"
                                            data-salary-url="{{ route('payroll.employees.salary-data', $emp) }}">
                                            {{ $emp->name }} ({{ $emp->alias_name }}) - {{ $emp->employee_code ?: 'EMP-' . $emp->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year -->
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Year <span class="text-danger">*</span></label>
                                <select name="year" id="yearSelect" required class="form-select">
                                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                                        <option value="{{ $y }}" {{ old('year', $selectedYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Month -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Month <span class="text-danger">*</span></label>
                                <select name="month" id="monthSelect" required class="form-select">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ old('month', $selectedMonth) == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Total Days in Month -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total Days of Month</label>
                                <div class="input-group">
                                    <input type="number" name="total_days_in_month" id="totalDaysInput" value="{{ old('total_days_in_month', $totalDaysInMonth) }}" required class="form-control font-mono fw-bold text-center">
                                    <span class="input-group-text small">Days</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Employee Badge & Leave Balances Banner -->
                        <div id="employeeInfoBanner" class="mt-3 p-3 rounded-3 bg-light border border-light-subtle d-none">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="fw-bold text-dark" id="bannerEmpName">-</div>
                                    <div class="small text-muted" id="bannerEmpMeta">-</div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <span class="badge bg-secondary-subtle text-secondary border me-1" id="bannerEmpType">-</span>
                                    <span class="badge bg-info-subtle text-info border me-1" id="bannerEL">EL: 0.0</span>
                                    <span class="badge bg-primary-subtle text-primary border me-1" id="bannerCL">CL: 0.0</span>
                                    <span class="badge bg-danger-subtle text-danger border" id="bannerUL">UL: 0.0</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Attendance & Leaves Input Card -->
            <div class="col-12">
                <div class="card bg-white border-light-subtle shadow-sm">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-calendar2-check text-primary"></i> 2. Attendance &amp; Leave Days Calculation
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Working Days Worked <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" name="working_days" id="workingDays" value="{{ old('working_days', '0.0') }}" required class="form-control font-mono fw-bold text-center fs-5 calc-trigger">
                                <div class="form-text text-muted small">e.g. 12.5 days</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">EL Used (Earned Leave)</label>
                                <input type="number" step="0.5" name="el_used" id="elUsed" value="{{ old('el_used', '0.0') }}" class="form-control font-mono fw-bold text-center fs-5 calc-trigger">
                                <div class="form-text text-muted small">Paid Earned Leave</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">CL Used (Casual Leave)</label>
                                <input type="number" step="0.5" name="cl_used" id="clUsed" value="{{ old('cl_used', '0.0') }}" class="form-control font-mono fw-bold text-center fs-5 calc-trigger">
                                <div class="form-text text-muted small">Paid Casual Leave</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Total Paid Days</label>
                                <div class="p-2 px-3 rounded-3 bg-success-subtle text-success border border-success-subtle d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold">Paid / Payable:</span>
                                    <span class="fs-4 font-mono fw-bold" id="totalPaidDaysDisplay">0.0 Days</span>
                                </div>
                                <div class="small text-danger mt-1 text-end" id="unpaidDaysDisplay">Unpaid: 0.0 Days</div>
                            </div>

                            <div class="col-12 pt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="deduct_leaves_from_balance" id="deductLeaves" value="1" checked>
                                    <label class="form-check-label text-dark fw-semibold small" for="deductLeaves">
                                        Deduct EL &amp; CL used from employee's leave balance upon payslip generation
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings Table (Matching user's exact uploaded design) -->
            <div class="col-12">
                <div class="card bg-white border-light-subtle shadow-sm overflow-hidden">
                    <div class="card-header bg-dark py-3 d-flex justify-content-between align-items-center">
                        <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-currency-rupee text-warning"></i> EARNINGS (INR)
                        </h2>
                        <span class="text-white-50 small font-mono">Formula: Rate &times; (Paid Days / Month Days) + Arrear</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0" id="earningsTable">
                            <thead>
                                <tr style="background-color: #d1d5db; color: #111827;" class="text-uppercase small font-bold">
                                    <th style="width: 28%;">COMPONENTS</th>
                                    <th style="width: 18%;" class="text-end">RATE</th>
                                    <th style="width: 18%;" class="text-end">MONTHLY</th>
                                    <th style="width: 18%;" class="text-end">ARREAR</th>
                                    <th style="width: 18%;" class="text-end">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Basic -->
                                <tr>
                                    <td class="fw-bold text-dark">Basic</td>
                                    <td>
                                        <input type="number" step="0.01" name="basic_rate" id="basicRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" readonly id="basicMonthly" class="form-control form-control-sm text-end font-mono bg-light text-dark fw-semibold">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="basic_arrear" id="basicArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="basicTotal">0.00</td>
                                </tr>

                                <!-- HRA -->
                                <tr>
                                    <td class="fw-bold text-dark">HRA</td>
                                    <td>
                                        <input type="number" step="0.01" name="hra_rate" id="hraRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" readonly id="hraMonthly" class="form-control form-control-sm text-end font-mono bg-light text-dark fw-semibold">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="hra_arrear" id="hraArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="hraTotal">0.00</td>
                                </tr>

                                <!-- Conveyance Allowance -->
                                <tr>
                                    <td class="fw-bold text-dark">Conveyance Allowance</td>
                                    <td>
                                        <input type="number" step="0.01" name="conveyance_rate" id="conveyanceRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" readonly id="conveyanceMonthly" class="form-control form-control-sm text-end font-mono bg-light text-dark fw-semibold">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="conveyance_arrear" id="conveyanceArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="conveyanceTotal">0.00</td>
                                </tr>

                                <!-- Medical Allowance -->
                                <tr>
                                    <td class="fw-bold text-dark">Medical Allowance</td>
                                    <td>
                                        <input type="number" step="0.01" name="medical_rate" id="medicalRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" readonly id="medicalMonthly" class="form-control form-control-sm text-end font-mono bg-light text-dark fw-semibold">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="medical_arrear" id="medicalArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="medicalTotal">0.00</td>
                                </tr>

                                <!-- Other Payments -->
                                <tr>
                                    <td class="fw-bold text-dark">Other Payments</td>
                                    <td>
                                        <input type="number" step="0.01" name="other_rate" id="otherRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="other_monthly" id="otherMonthly" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="other_arrear" id="otherArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="otherTotal">0.00</td>
                                </tr>

                                <!-- Special Allowance -->
                                <tr>
                                    <td class="fw-bold text-dark">Special Allowance</td>
                                    <td>
                                        <input type="number" step="0.01" name="special_rate" id="specialRate" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" readonly id="specialMonthly" class="form-control form-control-sm text-end font-mono bg-light text-dark fw-semibold">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="special_arrear" id="specialArrear" value="0.00" class="form-control form-control-sm text-end font-mono calc-trigger">
                                    </td>
                                    <td class="text-end font-mono fw-bold text-dark pe-3" id="specialTotal">0.00</td>
                                </tr>

                                <!-- TOTAL EARNINGS ROW -->
                                <tr style="background-color: #e5e7eb; font-weight: 800; border-top: 2px solid #374151;">
                                    <td class="fw-black text-dark text-uppercase">TOTAL EARNINGS</td>
                                    <td class="text-end font-mono fw-black text-dark" id="grossRateDisplay">0.00</td>
                                    <td class="text-end font-mono fw-black text-dark" id="grossMonthlyDisplay">0.00</td>
                                    <td class="text-end font-mono fw-black text-dark" id="grossArrearDisplay">0.00</td>
                                    <td class="text-end font-mono fw-black text-dark pe-3 fs-6" id="totalEarningsDisplay">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Deductions & Net Pay Summary Card -->
            <div class="col-lg-6">
                <div class="card bg-white border-light-subtle shadow-sm h-100">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-dash-circle-dotted text-danger"></i> Deductions (INR)
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Provident Fund (PF)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="deduction_pf" id="deductionPf" value="0.00" class="form-control font-mono calc-trigger">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">ESI Deduction</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="deduction_esi" id="deductionEsi" value="0.00" class="form-control font-mono calc-trigger">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Professional Tax (PT)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="deduction_pt" id="deductionPt" value="0.00" class="form-control font-mono calc-trigger">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">TDS / Income Tax</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="deduction_tds" id="deductionTds" value="0.00" class="form-control font-mono calc-trigger">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Other Deductions</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="deduction_other" id="deductionOther" value="0.00" class="form-control font-mono calc-trigger">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded-3 bg-danger-subtle text-danger border border-danger-subtle">
                                    <span class="fw-bold small text-uppercase">Total Deductions</span>
                                    <span class="fs-5 font-mono fw-bold" id="totalDeductionsDisplay">&#8377; 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Payable Summary -->
            <div class="col-lg-6">
                <div class="card bg-white border-light-subtle shadow-sm h-100">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-success"></i> Net Payable Calculation
                        </h2>
                    </div>
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="p-3 rounded-3 bg-dark text-white mb-3">
                                <div class="text-white-50 small text-uppercase fw-bold">Net Salary Payable</div>
                                <div class="display-6 fw-black font-mono text-white" id="netPayDisplay">&#8377; 0.00</div>
                                <div class="small text-light mt-1 fst-italic" id="netPayWordsDisplay">Zero Rupees Only</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Payslip Remarks <small class="text-muted">(Optional)</small></label>
                                <textarea name="remarks" rows="2" class="form-control small" placeholder="e.g. Incentive included, Arrear adjustments"></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid pt-2">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow">
                                <i class="bi bi-check-circle-fill me-2"></i> Save &amp; Generate Payslip PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
    // Days in month helper
    function getDaysInMonth(year, month) {
        return new Date(year, month, 0).getDate();
    }

    function updateDaysInMonth() {
        const y = parseInt(document.getElementById('yearSelect').value);
        const m = parseInt(document.getElementById('monthSelect').value);
        if (y && m) {
            const days = getDaysInMonth(y, m);
            document.getElementById('totalDaysInput').value = days;
            recalculate();
        }
    }

    document.getElementById('yearSelect').addEventListener('change', updateDaysInMonth);
    document.getElementById('monthSelect').addEventListener('change', updateDaysInMonth);

    // Fetch employee salary info on selection
    document.getElementById('employeeSelect').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const url = option.getAttribute('data-salary-url');
        if (!url) {
            document.getElementById('employeeInfoBanner').classList.add('d-none');
            return;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                document.getElementById('employeeInfoBanner').classList.remove('d-none');
                document.getElementById('bannerEmpName').innerText = data.name + ' (' + data.alias_name + ')';
                document.getElementById('bannerEmpMeta').innerText = (data.designation || 'Specialist') + ' • ' + (data.employee_code || 'EMP-' + data.id) + ' • PAN: ' + (data.pan || 'N/A');
                document.getElementById('bannerEmpType').innerText = data.employment_type;
                document.getElementById('bannerEL').innerText = 'EL: ' + data.leaves.el_balance;
                document.getElementById('bannerCL').innerText = 'CL: ' + data.leaves.cl_balance;
                document.getElementById('bannerUL').innerText = 'UL: ' + data.leaves.ul_balance;

                // Populate Rates
                document.getElementById('basicRate').value = data.earnings.basic_salary;
                document.getElementById('hraRate').value = data.earnings.hra;
                document.getElementById('conveyanceRate').value = data.earnings.conveyance_allowance;
                document.getElementById('medicalRate').value = data.earnings.medical_allowance;
                document.getElementById('specialRate').value = data.earnings.special_allowance;
                document.getElementById('otherRate').value = data.earnings.other_payments;
                document.getElementById('otherMonthly').value = data.earnings.other_payments;

                recalculate();
            })
            .catch(err => console.error('Failed to load employee salary structure', err));
    });

    // Recalculate everything live
    function recalculate() {
        const totalDays = parseFloat(document.getElementById('totalDaysInput').value) || 30;
        const workingDays = parseFloat(document.getElementById('workingDays').value) || 0;
        const elUsed = parseFloat(document.getElementById('elUsed').value) || 0;
        const clUsed = parseFloat(document.getElementById('clUsed').value) || 0;

        const paidDays = workingDays + elUsed + clUsed;
        const unpaidDays = Math.max(0, totalDays - paidDays);

        document.getElementById('totalPaidDaysDisplay').innerText = paidDays.toFixed(1) + ' Days';
        document.getElementById('unpaidDaysDisplay').innerText = 'Unpaid: ' + unpaidDays.toFixed(1) + ' Days';

        const factor = totalDays > 0 ? (paidDays / totalDays) : 0;

        // Prorated components
        const components = [
            { rateId: 'basicRate', monthlyId: 'basicMonthly', arrearId: 'basicArrear', totalId: 'basicTotal', prorate: true },
            { rateId: 'hraRate', monthlyId: 'hraMonthly', arrearId: 'hraArrear', totalId: 'hraTotal', prorate: true },
            { rateId: 'conveyanceRate', monthlyId: 'conveyanceMonthly', arrearId: 'conveyanceArrear', totalId: 'conveyanceTotal', prorate: true },
            { rateId: 'medicalRate', monthlyId: 'medicalMonthly', arrearId: 'medicalArrear', totalId: 'medicalTotal', prorate: true },
            { rateId: 'otherRate', monthlyId: 'otherMonthly', arrearId: 'otherArrear', totalId: 'otherTotal', prorate: false },
            { rateId: 'specialRate', monthlyId: 'specialMonthly', arrearId: 'specialArrear', totalId: 'specialTotal', prorate: true },
        ];

        let grossRate = 0;
        let grossMonthly = 0;
        let grossArrear = 0;
        let totalEarnings = 0;

        components.forEach(comp => {
            const rate = parseFloat(document.getElementById(comp.rateId).value) || 0;
            const arrear = parseFloat(document.getElementById(comp.arrearId).value) || 0;
            let monthly = 0;

            if (comp.prorate) {
                monthly = Math.round(rate * factor * 100) / 100;
                document.getElementById(comp.monthlyId).value = monthly.toFixed(2);
            } else {
                monthly = parseFloat(document.getElementById(comp.monthlyId).value) || 0;
            }

            const total = Math.round((monthly + arrear) * 100) / 100;
            document.getElementById(comp.totalId).innerText = total.toFixed(2);

            grossRate += rate;
            grossMonthly += monthly;
            grossArrear += arrear;
            totalEarnings += total;
        });

        document.getElementById('grossRateDisplay').innerText = grossRate.toFixed(2);
        document.getElementById('grossMonthlyDisplay').innerText = grossMonthly.toFixed(2);
        document.getElementById('grossArrearDisplay').innerText = grossArrear.toFixed(2);
        document.getElementById('totalEarningsDisplay').innerText = totalEarnings.toFixed(2);

        // Deductions
        const pf = parseFloat(document.getElementById('deductionPf').value) || 0;
        const esi = parseFloat(document.getElementById('deductionEsi').value) || 0;
        const pt = parseFloat(document.getElementById('deductionPt').value) || 0;
        const tds = parseFloat(document.getElementById('deductionTds').value) || 0;
        const otherDed = parseFloat(document.getElementById('deductionOther').value) || 0;

        const totalDeductions = pf + esi + pt + tds + otherDed;
        document.getElementById('totalDeductionsDisplay').innerText = '₹ ' + totalDeductions.toFixed(2);

        const netPay = Math.max(0, totalEarnings - totalDeductions);
        document.getElementById('netPayDisplay').innerText = '₹ ' + netPay.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Attach listener to all trigger inputs
    document.querySelectorAll('.calc-trigger').forEach(input => {
        input.addEventListener('input', recalculate);
    });

    document.addEventListener('DOMContentLoaded', () => {
        const empSelect = document.getElementById('employeeSelect');
        if (empSelect.value) {
            empSelect.dispatchEvent(new Event('change'));
        } else {
            recalculate();
        }
    });
</script>
@endsection
