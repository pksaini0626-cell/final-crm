@extends('layouts.app')

@section('content')
<div>
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i> Edit Merchant Profile
            </h1>
            <p class="text-secondary small mb-0">Update merchant details and verify SMTP connectivity.</p>
        </div>
        <a href="{{ route('admin.merchants.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- SMTP test connection errors -->
    @if($errors->has('smtp_error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first('smtp_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Edit Form -->
        <div class="col-lg-8">
            <form action="{{ route('admin.merchants.update', $merchant) }}" method="POST" class="vstack gap-4">
                @csrf
                @method('PUT')

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
                                <input type="text" name="name" value="{{ old('name', $merchant->name) }}" required class="form-control">
                            </div>

                            <!-- Code -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Merchant Code <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_code" value="{{ old('merchant_code', $merchant->merchant_code) }}" required class="form-control font-monospace">
                            </div>

                            <!-- Contact phone -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number', $merchant->contact_number) }}" class="form-control">
                            </div>

                            <!-- Support mail -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Support Email</label>
                                <input type="email" name="support_mail" value="{{ old('support_mail', $merchant->support_mail) }}" class="form-control">
                            </div>

                            <!-- API Url -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">API URL</label>
                                <input type="url" name="api_url" value="{{ old('api_url', $merchant->api_url) }}" class="form-control font-monospace">
                            </div>

                            <!-- Security key -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Security Key / Secret</label>
                                <input type="text" name="security_key" value="{{ old('security_key', $merchant->security_key) }}" class="form-control font-monospace">
                            </div>

                            <!-- Tokenization key -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Tokenization Key</label>
                                <input type="text" name="tokenization_key" value="{{ old('tokenization_key', $merchant->tokenization_key) }}" class="form-control font-monospace">
                            </div>

                            <!-- Account Number -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Account Number / ID</label>
                                <input type="text" name="account_number" value="{{ old('account_number', $merchant->account_number) }}" class="form-control font-monospace">
                            </div>

                            <!-- Code -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Extra Code (SWIFT/Sort)</label>
                                <input type="text" name="code" value="{{ old('code', $merchant->code) }}" class="form-control font-monospace">
                            </div>

                            <!-- Currency -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Currency <span class="text-danger">*</span></label>
                                <input type="text" name="currency" value="{{ old('currency', $merchant->currency) }}" required class="form-control font-monospace fw-bold">
                            </div>

                            <!-- Wallet balance -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Wallet Balance <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="wallet_balance" value="{{ old('wallet_balance', $merchant->wallet_balance) }}" required class="form-control font-monospace">
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $merchant->is_active) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_active" class="form-check-label text-white fw-semibold">Active Profile</label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="col-md-12">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Notes</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $merchant->notes) }}</textarea>
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
                                <input type="text" name="smtp_host" value="{{ old('smtp_host', $merchant->smtp_host) }}" class="form-control font-monospace">
                            </div>

                            <!-- Port -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Port</label>
                                <input type="number" name="smtp_port" value="{{ old('smtp_port', $merchant->smtp_port) }}" class="form-control font-monospace">
                            </div>

                            <!-- Encryption -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="none" {{ old('smtp_encryption', $merchant->smtp_encryption) === 'none' ? 'selected' : '' }}>None / Plain</option>
                                    <option value="tls" {{ old('smtp_encryption', $merchant->smtp_encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('smtp_encryption', $merchant->smtp_encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                            </div>

                            <!-- Username -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Username</label>
                                <input type="text" name="smtp_username" value="{{ old('smtp_username', $merchant->smtp_username) }}" class="form-control font-monospace">
                            </div>

                            <!-- Password -->
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">SMTP Password <small class="text-secondary">(Leave blank to keep same)</small></label>
                                <input type="password" name="smtp_password" class="form-control font-monospace">
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_smtp_active" id="is_smtp_active" value="1" {{ old('is_smtp_active', $merchant->is_smtp_active) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_smtp_active" class="form-check-label text-white fw-semibold">Enable SMTP</label>
                                </div>
                            </div>

                            <!-- From Name -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Name</label>
                                <input type="text" name="from_name" value="{{ old('from_name', $merchant->from_name) }}" class="form-control">
                            </div>

                            <!-- From Email -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Email</label>
                                <input type="email" name="from_email" value="{{ old('from_email', $merchant->from_email) }}" class="form-control">
                            </div>

                            <!-- Reply To Name -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reply To Name</label>
                                <input type="text" name="reply_to_name" value="{{ old('reply_to_name', $merchant->reply_to_name) }}" class="form-control">
                            </div>

                            <!-- Reply To Email -->
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Reply To Email</label>
                                <input type="email" name="reply_to_email" value="{{ old('reply_to_email', $merchant->reply_to_email) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="{{ route('admin.merchants.index') }}" class="btn btn-outline-secondary fw-semibold px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                        <i class="bi bi-save me-1"></i> Update Merchant
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar Actions (SMTP Connection Test) -->
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary py-3">
                    <h3 class="h6 font-bold text-white mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-plug text-info"></i> Connection Utilities
                    </h3>
                </div>
                <div class="card-body p-4 vstack gap-3">
                    <p class="text-secondary small mb-0">Validate the configured SMTP server configuration by performing a real connection test.</p>
                    
                    <form action="{{ route('admin.merchants.test-smtp', $merchant) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-info w-100 fw-bold py-2">
                            <i class="bi bi-wifi me-1"></i> Test SMTP Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
