@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-building-add text-dark"></i> Create Merchant Profile
            </h1>
            <p class="text-secondary small mb-0">Add a new merchant payment profile and SMTP configurations.</p>
        </div>
        <a href="{{ route('admin.merchants.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <form action="{{ route('admin.merchants.store') }}" method="POST" class="vstack gap-4">
        @csrf

        <!-- Merchant Gateway Settings -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-info"></i> Merchant Gateway Settings
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Merchant Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" required class="form-control">
                    </div>

                    <!-- Code -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Merchant Code <span class="text-danger">*</span></label>
                        <input type="text" name="merchant_code" required class="form-control font-monospace" placeholder="e.g., stripe_us">
                    </div>

                    <!-- Contact phone -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" placeholder="+123456789">
                    </div>

                    <!-- Support mail -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Support Email</label>
                        <input type="email" name="support_mail" class="form-control" placeholder="support@merchant.com">
                    </div>

                    <!-- API Url -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">API URL</label>
                        <input type="url" name="api_url" class="form-control font-monospace" placeholder="https://api.merchant.com">
                    </div>

                    <!-- Security key -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Security Key / Secret</label>
                        <input type="text" name="security_key" class="form-control font-monospace">
                    </div>

                    <!-- Tokenization key -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Tokenization Key</label>
                        <input type="text" name="tokenization_key" class="form-control font-monospace">
                    </div>

                    <!-- Account Number -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Account Number / ID</label>
                        <input type="text" name="account_number" class="form-control font-monospace">
                    </div>

                    <!-- Code -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Extra Code (SWIFT/Sort)</label>
                        <input type="text" name="code" class="form-control font-monospace">
                    </div>

                    <!-- Currency -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency" value="USD" required class="form-control font-monospace fw-bold">
                    </div>

                    <!-- Wallet balance -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Wallet Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="wallet_balance" value="0.00" required class="form-control font-monospace">
                    </div>

                    <div class="col-md-4 d-flex flex-column justify-content-end">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-2">Profile Status</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-check-inline mb-0">
                                <input type="radio" name="is_active" id="merchant_active_1" value="1" {{ (string) old('is_active', '1') === '1' ? 'checked' : '' }} class="form-check-input">
                                <label for="merchant_active_1" class="form-check-label text-white fw-semibold" style="cursor: pointer;">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input type="radio" name="is_active" id="merchant_active_0" value="0" {{ (string) old('is_active', '1') === '0' ? 'checked' : '' }} class="form-check-input">
                                <label for="merchant_active_0" class="form-check-label text-white fw-semibold" style="cursor: pointer;">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i> Inactive</span>
                                </label>
                            </div>
                        </div>
                        @error('is_active')<div class="form-text text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="form-control" placeholder="Internal remarks..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP Email Settings -->
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-paper text-warning"></i> SMTP Configurations
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Host -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control font-monospace" placeholder="smtp.mailtrap.io">
                    </div>

                    <!-- Port -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control font-monospace" placeholder="587">
                    </div>

                    <!-- Encryption -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Encryption</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="none">None / Plain</option>
                            <option value="tls" selected>TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                    </div>

                    <!-- Username -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control font-monospace">
                    </div>

                    <!-- Password -->
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control font-monospace">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="is_smtp_active" id="is_smtp_active" value="1" class="form-check-input">
                            <label for="is_smtp_active" class="form-check-label text-white fw-semibold">Enable SMTP</label>
                        </div>
                    </div>

                    <!-- From Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Name</label>
                        <input type="text" name="from_name" class="form-control" placeholder="Flight CRM Support">
                    </div>

                    <!-- From Email -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Email</label>
                        <input type="email" name="from_email" class="form-control" placeholder="no-reply@crm.com">
                    </div>

                    <!-- Reply To Name -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reply To Name</label>
                        <input type="text" name="reply_to_name" class="form-control" placeholder="Billing Dept">
                    </div>

                    <!-- Reply To Email -->
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reply To Email</label>
                        <input type="email" name="reply_to_email" class="form-control" placeholder="billing@crm.com">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.merchants.index') }}" class="btn btn-outline-secondary fw-semibold px-4">Cancel</a>
            <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                <i class="bi bi-save me-1"></i> Save Merchant
            </button>
        </div>
    </form>
</div>
@endsection
