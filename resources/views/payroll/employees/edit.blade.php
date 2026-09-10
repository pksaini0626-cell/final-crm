@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-person-lines-fill text-primary"></i> Complete Employee Profile:
                <span class="text-primary font-monospace">{{ $user->alias_name }}</span>
            </h1>
            <p class="text-secondary small mb-0">Confidential HR &amp; Accounts Management: Personal, Statutory, Salary Package, and Leave Balances.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.employees.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Back to Employees
            </a>
            <a href="{{ route('payroll.payslips.create', ['user_id' => $user->id]) }}" class="btn btn-success btn-sm fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-receipt"></i> Generate Payslip
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show bg-danger-subtle text-danger border border-danger-subtle mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Please correct the errors below:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('payroll.employees.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            
            <!-- Left Column: Core & HR Profile -->
            <div class="col-lg-6">

                <!-- 1. Account & Contact Information -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge text-primary"></i> 1. Account &amp; Contact Info
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Account Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Official Employee Name <small class="text-primary fw-bold">(Real Name on Payslip)</small></label>
                                <input type="text" name="employee_name" value="{{ old('employee_name', $user->employee_name) }}" placeholder="Full legal name for payslips" class="form-control fw-semibold">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Display Alias <span class="text-danger">*</span></label>
                                <input type="text" name="alias_name" value="{{ old('alias_name', $user->alias_name) }}" required class="form-control font-monospace fw-bold text-primary">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Role <span class="text-danger">*</span></label>
                                <select name="role" required class="form-select">
                                    <option value="agent" {{ old('role', $user->role) === 'agent' ? 'selected' : '' }}>Agent</option>
                                    <option value="ticketing" {{ old('role', $user->role) === 'ticketing' ? 'selected' : '' }}>Ticketing Agent</option>
                                    <option value="changes" {{ old('role', $user->role) === 'changes' ? 'selected' : '' }}>Booking Changes Desk</option>
                                    <option value="mis" {{ old('role', $user->role) === 'mis' ? 'selected' : '' }}>MIS Agent</option>
                                    <option value="hr" {{ old('role', $user->role) === 'hr' ? 'selected' : '' }}>HR Operations</option>
                                    <option value="accounts" {{ old('role', $user->role) === 'accounts' ? 'selected' : '' }}>Accounts &amp; Payroll</option>
                                    <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Contact Number</label>
                                <input type="text" name="contact" value="{{ old('contact', $user->contact) }}" class="form-control" placeholder="+91 9876543210">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Extension Number</label>
                                <input type="text" name="extension" value="{{ old('extension', $user->extension) }}" class="form-control font-monospace" placeholder="104">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Change Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                <input type="password" name="password" class="form-control font-monospace" placeholder="Enter new password if resetting">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase d-block mb-1">Account Status</label>
                                @php
                                    $empActive = (string) old('is_active', $user->is_active ? '1' : '0');
                                @endphp
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="is_active" id="emp_active_1" value="1" {{ $empActive === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark fw-semibold small" for="emp_active_1" style="cursor: pointer;">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="is_active" id="emp_active_0" value="0" {{ $empActive === '0' ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark fw-semibold small" for="emp_active_0" style="cursor: pointer;">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i> Inactive</span>
                                        </label>
                                    </div>
                                </div>
                                @error('is_active')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Employment & HR Information -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-briefcase text-primary"></i> 2. Employment &amp; Tenure
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Employee Code</label>
                                <input type="text" name="employee_code" value="{{ old('employee_code', $user->employee_code) }}" placeholder="e.g. EMP1024" class="form-control font-monospace fw-bold">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Designation</label>
                                <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" placeholder="e.g. Senior Travel Specialist" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Location / Branch</label>
                                <input type="text" name="location" value="{{ old('location', $user->location) }}" placeholder="e.g. Head Office / Delhi" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Joining Date</label>
                                <input type="date" name="joining_date" id="joiningDateInput" value="{{ old('joining_date', $user->joining_date ? $user->joining_date->format('Y-m-d') : '') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Leaving Date <small class="text-muted">(If separated)</small></label>
                                <input type="date" name="leaving_date" value="{{ old('leaving_date', $user->leaving_date ? $user->leaving_date->format('Y-m-d') : '') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Employment Type</label>
                                <select name="employment_type" id="employmentTypeSelect" class="form-select">
                                    <option value="Probation" {{ old('employment_type', $user->employment_type) === 'Probation' ? 'selected' : '' }}>Probation (&le; 90 Days)</option>
                                    <option value="Permanent" {{ old('employment_type', $user->employment_type) === 'Permanent' ? 'selected' : '' }}>Permanent (&gt; 90 Days)</option>
                                </select>
                            </div>

                            <!-- 90 Days Logic Banner -->
                            <div class="col-12">
                                @php
                                    $daysJoined = $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->diffInDays(now()) : 0;
                                    $isEligiblePermanent = $daysJoined >= 90;
                                @endphp
                                <div class="p-3 rounded-3 border {{ $isEligiblePermanent ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning-emphasis' }} small">
                                    <div class="fw-bold d-flex align-items-center gap-2">
                                        <i class="bi {{ $isEligiblePermanent ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }}"></i>
                                        90-Day Employment Type Rule:
                                    </div>
                                    <div class="mt-1">
                                        @if($user->joining_date)
                                            Employee has completed <strong>{{ $daysJoined }} days</strong> since joining date ({{ $user->joining_date->format('d-M-Y') }}).
                                            @if($isEligiblePermanent)
                                                Status evaluated as <strong>Permanent</strong> (leaves accrue monthly).
                                            @else
                                                Currently under <strong>Probation</strong> ({{ 90 - $daysJoined }} days remaining for Permanent status).
                                            @endif
                                        @else
                                            Joining date is not set yet. Set joining date to automatically calculate Permanent status after 90 days.
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- 3. Statutory & Banking Details -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-light-subtle py-3">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-bank text-primary"></i> 3. Statutory &amp; Bank Information
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Tax Regime</label>
                                <select name="tax_regime" class="form-select">
                                    <option value="New Regime" {{ old('tax_regime', $user->tax_regime) === 'New Regime' ? 'selected' : '' }}>New Tax Regime</option>
                                    <option value="Old Regime" {{ old('tax_regime', $user->tax_regime) === 'Old Regime' ? 'selected' : '' }}>Old Tax Regime</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">PAN Number <small class="text-danger">(Used for PDF Password)</small></label>
                                <input type="text" name="pan" value="{{ old('pan', $user->pan) }}" placeholder="e.g. ABCDE1234F" class="form-control font-monospace fw-bold text-uppercase">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender', $user->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $user->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender', $user->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Bank Account Number</label>
                                <input type="text" name="account_number" value="{{ old('account_number', $user->account_number) }}" placeholder="e.g. 501004389281" class="form-control font-monospace">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">PF Account Number</label>
                                <input type="text" name="pf_account_number" value="{{ old('pf_account_number', $user->pf_account_number) }}" placeholder="e.g. DL/CPM/0012345/000/1234" class="form-control font-monospace">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">PF UAN</label>
                                <input type="text" name="pf_uan" value="{{ old('pf_uan', $user->pf_uan) }}" placeholder="e.g. 100904589211" class="form-control font-monospace">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">ESI Number</label>
                                <input type="text" name="esi_number" value="{{ old('esi_number', $user->esi_number) }}" placeholder="e.g. 31000987650000999" class="form-control font-monospace">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: CTC, Salary Structure & Leave Balances -->
            <div class="col-lg-6">

                <!-- 4. CTC & Salary Structure (Earnings) -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-success"></i> 4. Salary Package &amp; Earning Structure
                        </h2>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Monthly Rates</span>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- CTC Input -->
                        <div class="mb-4 p-3 bg-light rounded-3 border border-light-subtle">
                            <label class="form-label text-dark fw-bold text-uppercase small mb-1">Annual CTC (INR)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold">&#8377;</span>
                                <input type="number" step="0.01" name="ctc" value="{{ old('ctc', $user->ctc) }}" placeholder="e.g. 698400.00" class="form-control form-control-lg fw-bold text-dark font-mono">
                            </div>
                            <div class="form-text text-muted small">Total Cost to Company per annum.</div>
                        </div>

                        <div class="row g-3">
                            <!-- Basic Salary -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Basic Salary Rate <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="basic_salary" id="rateBasic" value="{{ old('basic_salary', $earning->basic_salary) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- HRA -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">HRA Rate</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="hra" id="rateHra" value="{{ old('hra', $earning->hra) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- Conveyance Allowance -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Conveyance Allowance</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="conveyance_allowance" id="rateConveyance" value="{{ old('conveyance_allowance', $earning->conveyance_allowance) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- Medical Allowance -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Medical Allowance</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="medical_allowance" id="rateMedical" value="{{ old('medical_allowance', $earning->medical_allowance) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- Special Allowance -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Special Allowance</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="special_allowance" id="rateSpecial" value="{{ old('special_allowance', $earning->special_allowance) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- Other Payments -->
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Other Payments</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8377;</span>
                                    <input type="number" step="0.01" name="other_payments" id="rateOther" value="{{ old('other_payments', $earning->other_payments) }}" class="form-control font-mono rate-input">
                                </div>
                            </div>

                            <!-- Live Total Rate Summary Box -->
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-dark text-white shadow-sm">
                                    <div>
                                        <div class="small text-secondary text-uppercase fw-bold">Total Monthly Earning Rate</div>
                                        <div class="h4 fw-bold mb-0 text-white font-mono" id="totalRateDisplay">&#8377; 0.00</div>
                                    </div>
                                    <div class="text-end text-secondary small">
                                        Component sum used for<br>monthly payslip proration
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- 5. Leave Balances -->
                <div class="card bg-white border-light-subtle shadow-sm mb-4">
                    <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                            <i class="bi bi-calendar3 text-primary"></i> 5. Leave Balance Ledger
                        </h2>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Accruals &amp; Deductions</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Leave Accrual Rules:</strong> Permanent employees earn <strong>1.0 EL</strong> and <strong>0.5 CL</strong> each month. Unpaid leaves (UL) deduct pay from monthly slips.
                        </div>

                        <div class="row g-3">
                            <!-- EL Balance -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Earn Leave (EL)</label>
                                <div class="input-group">
                                    <input type="number" step="0.5" name="el_balance" value="{{ old('el_balance', $leaveBalance->el_balance) }}" class="form-control font-mono fw-bold text-center">
                                    <span class="input-group-text small">Days</span>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.72rem;">+1.0 / month for Permanent</div>
                            </div>

                            <!-- CL Balance -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Casual Leave (CL)</label>
                                <div class="input-group">
                                    <input type="number" step="0.5" name="cl_balance" value="{{ old('cl_balance', $leaveBalance->cl_balance) }}" class="form-control font-mono fw-bold text-center">
                                    <span class="input-group-text small">Days</span>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.72rem;">+0.5 / month for Permanent</div>
                            </div>

                            <!-- UL Balance -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Unpaid Leave (UL)</label>
                                <div class="input-group">
                                    <input type="number" step="0.5" name="ul_balance" value="{{ old('ul_balance', $leaveBalance->ul_balance) }}" class="form-control font-mono fw-bold text-danger text-center">
                                    <span class="input-group-text small text-danger">Days</span>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.72rem;">Deducted from payslip</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('payroll.employees.index') }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow">
                        <i class="bi bi-check2-circle me-1"></i> Save Complete Profile &amp; Payroll
                    </button>
                </div>

            </div>

        </div>
    </form>

</div>

<script>
    function updateTotalRate() {
        const inputs = document.querySelectorAll('.rate-input');
        let total = 0;
        inputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
        });
        document.getElementById('totalRateDisplay').innerHTML = '&#8377; ' + total.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    document.querySelectorAll('.rate-input').forEach(input => {
        input.addEventListener('input', updateTotalRate);
    });

    document.addEventListener('DOMContentLoaded', updateTotalRate);
</script>
@endsection
