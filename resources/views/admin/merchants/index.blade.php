@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary"></i> Merchant Profiles
            </h1>
            <p class="text-secondary small mb-0">Configure third-party payment gateways and dynamic SMTP servers.</p>
        </div>
        <a href="{{ route('admin.merchants.create') }}" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-lg"></i> Add Merchant
        </a>
    </div>

    <!-- Merchants Table -->
    <div class="card bg-dark border-secondary shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-striped align-middle mb-0 text-nowrap">
                <thead class="table-dark text-secondary small text-uppercase">
                    <tr>
                        <th class="px-3 py-3">Name</th>
                        <th class="px-3 py-3">Merchant Code</th>
                        <th class="px-3 py-3">Balance</th>
                        <th class="px-3 py-3">SMTP Status</th>
                        <th class="px-3 py-3">Active Status</th>
                        <th class="px-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($merchants as $merchant)
                        <tr>
                            <!-- Name -->
                            <td class="px-3 py-3 font-bold text-white">
                                {{ $merchant->name }}
                            </td>
                            <!-- Code -->
                            <td class="px-3 py-3 font-monospace small">
                                <span class="badge bg-body-tertiary text-info border border-secondary">{{ $merchant->merchant_code }}</span>
                            </td>
                            <!-- Wallet balance -->
                            <td class="px-3 py-3 fw-semibold font-monospace">
                                {{ $merchant->currency }} {{ number_format($merchant->wallet_balance, 2) }}
                            </td>
                            <!-- SMTP Status -->
                            <td class="px-3 py-3">
                                @if($merchant->is_smtp_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                        <i class="bi bi-check-circle me-1"></i> Active ({{ $merchant->smtp_host }})
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5">
                                        Disabled
                                    </span>
                                @endif
                            </td>
                            <!-- Active Status -->
                            <td class="px-3 py-3">
                                <form action="{{ route('admin.merchants.toggle-active', $merchant) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="border-0 bg-transparent p-0">
                                        @if($merchant->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5">
                                                Inactive
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-end">
                                <a href="{{ route('admin.merchants.edit', $merchant) }}" class="btn btn-outline-info btn-sm px-3 py-1">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-secondary fst-italic">
                                No merchants registered.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($merchants->hasPages())
            <div class="card-footer bg-dark border-secondary py-3">
                {{ $merchants->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
