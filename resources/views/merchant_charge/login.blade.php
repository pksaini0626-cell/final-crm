@extends('merchant_charge.layout')

@section('title', 'Merchant Charge Terminal - Authentication')

@section('content')
<div class="d-flex align-items-center justify-content-center min-vh-100 p-3" x-data="chargeLogin()">
    <div class="w-100" style="max-width: 440px;">
        
        <!-- Top Gateway Branding -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-circle p-3 mb-3 shadow-sm">
                <i class="bi bi-shield-lock-fill text-primary fs-1"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Merchant Charge Terminal</h3>
            <p class="text-secondary small mb-0">Secure Point-of-Sale Payment Gateway Access</p>
        </div>

        <!-- Login Card -->
        <div class="software-card p-4 p-sm-5 bg-white shadow-sm border">
            @if ($errors->any())
                <div class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger rounded-3 p-3 mb-4 d-flex align-items-center gap-2 small">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('merchant.charge.login') }}" @submit="loading = true">
                @csrf

                <!-- Email Input -->
                <div class="mb-3">
                    <label class="form-label text-dark d-flex justify-content-between">
                        <span>Terminal Operator Email</span>
                        <span class="text-muted font-mono" style="font-size: 0.75rem;">charge@callinggenie.com</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <input type="email" 
                               name="email" 
                               x-model="email"
                               class="form-control" 
                               placeholder="operator@callinggenie.com" 
                               required 
                               autocomplete="username">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <label class="form-label text-dark d-flex justify-content-between">
                        <span>Security Password</span>
                        <span class="text-muted font-mono" style="font-size: 0.75rem;">Charge@123#</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" 
                               x-model="password"
                               class="form-control font-mono" 
                               placeholder="••••••••••••" 
                               required 
                               autocomplete="current-password">
                        <button class="btn btn-outline-secondary" 
                                type="button" 
                                @click="showPassword = !showPassword">
                            <i class="bi" :class="showPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember & Quick Fill -->
                <div class="d-flex align-items-center justify-content-between mb-4 small">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                        <label class="form-check-label text-secondary" for="remember">Keep session active</label>
                    </div>
                    <button type="button" 
                            @click="fillDefaults()" 
                            class="btn btn-link btn-sm text-primary text-decoration-none p-0 fw-semibold">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Quick Fill
                    </button>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="btn btn-charge-primary w-100 d-flex align-items-center justify-content-center gap-2"
                        :disabled="loading">
                    <template x-if="!loading">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                            <span>Access Charge Terminal</span>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span>Authenticating Security Gateway...</span>
                        </span>
                    </template>
                </button>
            </form>
        </div>

        <!-- Security Notice Footer -->
        <div class="text-center mt-4">
            <div class="d-inline-flex align-items-center gap-2 text-secondary small px-3 py-1.5 rounded-pill bg-white border border-light-subtle shadow-sm">
                <i class="bi bi-shield-check text-success"></i>
                <span class="font-mono text-dark" style="font-size: 0.75rem;">256-Bit Encrypted PCI-DSS NMI Direct Gateway</span>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function chargeLogin() {
    return {
        email: '{{ old('email', 'charge@callinggenie.com') }}',
        password: '',
        showPassword: false,
        loading: false,
        fillDefaults() {
            this.email = 'charge@callinggenie.com';
            this.password = 'Charge@123#';
        }
    }
}
</script>
@endpush
@endsection
