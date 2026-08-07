@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-people text-primary"></i> User Management
            </h1>
            <p class="text-secondary small mb-0">Manage system users, roles, and mandatory display aliases.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-person-plus-fill"></i> Add New User
        </a>
    </div>

    <!-- Search / Filter Bar -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, mandatory alias, or email..." class="form-control">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="agent" {{ request('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-search me-1"></i> Filter</button>
                    @if(request()->hasAny(['q', 'role']))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary fw-semibold">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card bg-dark border-secondary shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-striped align-middle mb-0 text-nowrap">
                <thead class="table-dark text-secondary small text-uppercase">
                    <tr>
                        <th class="px-3 py-3">Full Name</th>
                        <th class="px-3 py-3">Mandatory Alias</th>
                        <th class="px-3 py-3">Email</th>
                        <th class="px-3 py-3">Role</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <!-- Full Name -->
                            <td class="px-3 py-3 fw-bold text-white">
                                {{ $user->name }}
                            </td>
                            <!-- Mandatory Alias -->
                            <td class="px-3 py-3 fw-bold text-primary font-monospace">
                                {{ $user->alias_name }}
                            </td>
                            <!-- Email -->
                            <td class="px-3 py-3 text-secondary">
                                {{ $user->email }}
                            </td>
                            <!-- Role Badge -->
                            <td class="px-3 py-3">
                                @php
                                    $roleBadge = match($user->role) {
                                        'admin' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'manager' => 'bg-purple-subtle text-purple border border-purple-subtle',
                                        default => 'bg-primary-subtle text-primary border border-primary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }} text-uppercase px-2.5 py-1.5">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <!-- Status -->
                            <td class="px-3 py-3">
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5">Inactive</span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Edit User">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} px-2 py-1">
                                            <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i> {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-secondary fst-italic">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="card-footer bg-dark border-secondary py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
