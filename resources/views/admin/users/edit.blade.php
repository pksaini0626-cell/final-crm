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
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control">
                        @error('name')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Mandatory Alias Name -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Mandatory Alias Name <span class="text-danger">*</span></label>
                        <input type="text" name="alias_name" value="{{ old('alias_name', $user->alias_name) }}" required class="form-control font-monospace fw-bold text-info">
                        @error('alias_name')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-4">
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

                    <!-- Active Status -->
                    <div class="col-md-12 pt-2">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="form-check-input">
                            <label for="is_active" class="form-check-label text-white fw-semibold">Active Account</label>
                        </div>
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
