@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i> Edit User: <span class="text-primary font-monospace">{{ $user->alias_name }}</span>
            </h1>
            <p class="text-secondary small mb-0">Update system user details, roles, or reset password.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>

    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card bg-dark border-secondary shadow-sm mb-4">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-person-vcard text-info"></i> User Account Details
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control">
                        @error('name')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Employee Name (Official/Real Name) -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Employee Name <small class="text-muted">(Payslip)</small></label>
                        <input type="text" name="employee_name" value="{{ old('employee_name', $user->employee_name) }}" placeholder="Real/legal name" class="form-control">
                        @error('employee_name')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Mandatory Alias Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Mandatory Alias Name <span class="text-danger">*</span></label>
                        <input type="text" name="alias_name" value="{{ old('alias_name', $user->alias_name) }}" required class="form-control font-monospace fw-bold text-info">
                        @error('alias_name')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control">
                        @error('email')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Password -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Password <small class="text-secondary">(Leave blank to keep same)</small></label>
                        <input type="password" name="password" class="form-control font-monospace">
                        @error('password')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Role -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Role <span class="text-danger">*</span></label>
                        <select name="role" required class="form-select">
                            <option value="agent" {{ old('role', $user->role) === 'agent' ? 'selected' : '' }}>Agent</option>
                            <option value="ticketing" {{ old('role', $user->role) === 'ticketing' ? 'selected' : '' }}>Ticketing Agent</option>
                            <option value="changes" {{ old('role', $user->role) === 'changes' ? 'selected' : '' }}>Booking Changes Desk</option>
                            <option value="mis" {{ old('role', $user->role) === 'mis' ? 'selected' : '' }}>MIS Agent</option>
                            <option value="hr" {{ old('role', $user->role) === 'hr' ? 'selected' : '' }}>HR Operations</option>
                            <option value="accounts" {{ old('role', $user->role) === 'accounts' ? 'selected' : '' }}>Accounts & Payroll</option>
                            <option value="chargeback" {{ old('role', $user->role) === 'chargeback' ? 'selected' : '' }}>Chargeback Desk</option>
                            <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Agent Language Preference -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent Language Option</label>
                        <select name="agent_language" class="form-select border-info border-opacity-50">
                            <option value="english" {{ old('agent_language', $user->agent_language ?? 'english') === 'english' ? 'selected' : '' }}>English (Default)</option>
                            <option value="spanish" {{ old('agent_language', $user->agent_language) === 'spanish' ? 'selected' : '' }}>Spanish</option>
                            <option value="both" {{ old('agent_language', $user->agent_language) === 'both' ? 'selected' : '' }}>Both (English & Spanish)</option>
                        </select>
                        @error('agent_language')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Contact -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Contact Number</label>
                        <input type="text" name="contact" value="{{ old('contact', $user->contact) }}" class="form-control">
                    </div>

                    <!-- Extension -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Extension</label>
                        <input type="text" name="extension" value="{{ old('extension', $user->extension) }}" class="form-control font-monospace">
                    </div>

                    <!-- Joining Date -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Joining Date</label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', $user->joining_date ? $user->joining_date->format('Y-m-d') : '') }}" class="form-control">
                    </div>

                    <!-- Active Status Radio -->
                    <div class="col-md-12 pt-2">
                        <label class="form-label text-secondary small fw-bold text-uppercase d-block mb-2">Account Status <span class="text-danger">*</span></label>
                        @php
                            $currentActive = (string) old('is_active', $user->is_active ? '1' : '0');
                        @endphp
                        <div class="d-flex align-items-center gap-4">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_1" value="1" {{ $currentActive === '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-white fw-semibold" for="is_active_1" style="cursor: pointer;">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_0" value="0" {{ $currentActive === '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-white fw-semibold" for="is_active_0" style="cursor: pointer;">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i> Inactive</span>
                                </label>
                            </div>
                        </div>
                        @error('is_active')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary fw-semibold px-4">Cancel</a>
            <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                <i class="bi bi-save me-1"></i> Update User
            </button>
        </div>
    </form>
</div>
@endsection
