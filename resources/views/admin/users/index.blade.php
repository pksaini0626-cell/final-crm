@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-people text-primary"></i> User Management
            </h1>
            <p class="text-secondary small mb-0">Manage system users, roles, mandatory display aliases, and user account status.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-person-plus-fill"></i> Add New User
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show bg-danger-subtle text-danger border border-danger-subtle mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search / Filter Bar -->
    <div class="card bg-white border-light-subtle shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by full name, mandatory alias, or email address..." class="form-control bg-white border-light-subtle text-dark">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm bg-white border-light-subtle text-dark">
                        <option value="">All User Roles</option>
                        <option value="master_admin" {{ request('role') === 'master_admin' ? 'selected' : '' }}>Master Admin</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="ticketing" {{ request('role') === 'ticketing' ? 'selected' : '' }}>Ticketing Desk</option>
                        <option value="changes" {{ request('role') === 'changes' ? 'selected' : '' }}>Booking Changes Desk</option>
                        <option value="mis" {{ request('role') === 'mis' ? 'selected' : '' }}>MIS Agent</option>
                        <option value="hr" {{ request('role') === 'hr' ? 'selected' : '' }}>HR Operations</option>
                        <option value="accounts" {{ request('role') === 'accounts' ? 'selected' : '' }}>Accounts & Payroll</option>
                        <option value="chargeback" {{ request('role') === 'chargeback' ? 'selected' : '' }}>Chargeback Desk</option>
                        <option value="agent" {{ request('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold flex-fill"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
                    @if(request()->hasAny(['q', 'role']))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card bg-white border-light-subtle shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-light-subtle py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i> System Users Directory
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $users->total() }} total</span>
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-white table-hover table-striped align-middle mb-0 text-nowrap">
                <thead>
                    <tr class="small text-uppercase text-secondary border-light-subtle bg-light">
                        <th class="px-3 py-3">Full Name</th>
                        <th class="px-3 py-3">Mandatory Alias</th>
                        <th class="px-3 py-3">Email Address</th>
                        <th class="px-3 py-3">Assigned Role</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-end" style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <!-- Full Name -->
                            <td class="px-3 py-3 fw-bold text-dark">
                                {{ $user->name }}
                            </td>
                            <!-- Mandatory Alias -->
                            <td class="px-3 py-3 fw-bold text-primary font-monospace">
                                {{ $user->alias_name }}
                            </td>
                            <!-- Email -->
                            <td class="px-3 py-3 text-secondary font-monospace small">
                                {{ $user->email }}
                            </td>
                            <!-- Role Badge -->
                            <td class="px-3 py-3">
                                @php
                                    $roleBadge = match($user->role) {
                                        'master_admin' => 'bg-danger text-white border border-danger shadow-sm',
                                        'admin' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'manager' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'ticketing' => 'bg-info-subtle text-info border border-info-subtle',
                                        'changes' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'mis' => 'bg-success-subtle text-success border border-success-subtle',
                                        'chargeback' => 'bg-dark text-white border border-secondary',
                                        'hr' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'accounts' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }} text-uppercase px-2.5 py-1.5 font-monospace">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <!-- Status -->
                            <td class="px-3 py-3">
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5">
                                        <i class="bi bi-pause-circle-fill me-1"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-1.5">
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-sm" title="Edit User Account">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <!-- Toggle Active Button -->
                                    <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} px-2 py-1 shadow-sm" title="{{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                            <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i> {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    @if(Auth::id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete user \'{{ addslashes($user->alias_name ?: $user->name) }}\' from CRM? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-sm" title="Delete User Permanently">
                                                <i class="bi bi-trash3-fill"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-secondary fst-italic">
                                No system users found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="card-footer bg-white border-light-subtle py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                    </div>
                    <div>
                        {{ $users->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
