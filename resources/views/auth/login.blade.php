<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Portal Login | Flight CRM</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon/android-chrome-192x192.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Vite Styles / Scripts (keep your backend pipeline) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a; /* slate-950 */
            color: #f1f5f9; /* slate-100 */
        }
        /* custom overrides to keep the dark aesthetic */
        .bg-slate-950 { background-color: #0f172a; }
        .bg-slate-900\/80 { background-color: rgba(15, 23, 42, 0.8); }
        .bg-slate-900\/50 { background-color: rgba(15, 23, 42, 0.5); }
        .bg-slate-950\/80 { background-color: rgba(15, 23, 42, 0.8); }
        .border-slate-800 { border-color: #1e293b; }
        .border-slate-800\/60 { border-color: rgba(30, 41, 59, 0.6); }
        .border-slate-800\/80 { border-color: rgba(30, 41, 59, 0.8); }
        .text-slate-100 { color: #f1f5f9; }
        .text-slate-200 { color: #e2e8f0; }
        .text-slate-300 { color: #cbd5e1; }
        .text-slate-400 { color: #94a3b8; }
        .text-slate-500 { color: #64748b; }
        .text-slate-600 { color: #475569; }
        .placeholder-slate-600::placeholder { color: #475569; }
        .bg-gradient-to-br { background-image: linear-gradient(to bottom right, var(--tw-gradient-stops)); }
        .from-indigo-950\/60 { --tw-gradient-from: rgba(30, 27, 75, 0.6); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(30, 27, 75, 0)); }
        .via-slate-900\/90 { --tw-gradient-stops: var(--tw-gradient-from), rgba(15, 23, 42, 0.9), var(--tw-gradient-to, rgba(15, 23, 42, 0)); }
        .to-slate-950 { --tw-gradient-to: #0f172a; }
        .bg-gradient-to-tr { background-image: linear-gradient(to top right, var(--tw-gradient-stops)); }
        .from-indigo-600 { --tw-gradient-from: #4f46e5; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(79, 70, 229, 0)); }
        .to-indigo-400 { --tw-gradient-to: #818cf8; }
        .bg-indigo-500\/10 { background-color: rgba(99, 102, 241, 0.1); }
        .border-indigo-500\/20 { border-color: rgba(99, 102, 241, 0.2); }
        .text-indigo-400 { color: #818cf8; }
        .bg-indigo-600 { background-color: #4f46e5; }
        .hover\:bg-indigo-500:hover { background-color: #6366f1; }
        .shadow-indigo-600\/30 { box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3); }
        .shadow-indigo-600\/50 { box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5); }
        .ring-indigo-500\/40 { --tw-ring-color: rgba(99, 102, 241, 0.4); }
        .backdrop-blur-2xl { backdrop-filter: blur(30px); }
        .selection\:bg-indigo-500 ::selection { background-color: #6366f1; color: white; }
        .bg-emerald-500\/10 { background-color: rgba(16, 185, 129, 0.1); }
        .border-emerald-500\/20 { border-color: rgba(16, 185, 129, 0.2); }
        .text-emerald-300 { color: #6ee7b7; }
        .bg-rose-500\/10 { background-color: rgba(244, 63, 94, 0.1); }
        .border-rose-500\/20 { border-color: rgba(244, 63, 94, 0.2); }
        .text-rose-300 { color: #fda4af; }
        /* utility */
        .rounded-3xl { border-radius: 1.5rem; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .bg-\[radial-gradient\(\#334155_1px\,_transparent_1px\)\] { background-image: radial-gradient(#334155 1px, transparent 1px); }
        .bg-\[length\:24px_24px\] { background-size: 24px 24px; }
        .backdrop-blur-sm { backdrop-filter: blur(4px); }
        /* animate pulse */
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        /* spin */
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /* x-cloak */
        [x-cloak] { display: none !important; }
        /* fix for bg-clip-text */
        .bg-clip-text { -webkit-background-clip: text; background-clip: text; }
        .text-transparent { color: transparent; }
        .tracking-tight { letter-spacing: -0.025em; }
        .tracking-wider { letter-spacing: 0.05em; }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3 p-sm-4 p-lg-5 bg-slate-950 text-slate-100 position-relative overflow-x-hidden">

    <!-- Ambient Background Glow Effects -->
    <div class="position-fixed top-0 start-0 w-100 h-100 pointer-events-none overflow-hidden" style="z-index:0;">
        <div class="position-absolute top-0 start-0 w-25 h-25 bg-indigo-600/15 rounded-circle blur-3xl" style="transform: translate(-30%, -30%);"></div>
        <div class="position-absolute top-50 end-0 w-25 h-25 bg-purple-600/15 rounded-circle blur-3xl" style="transform: translate(30%, -50%);"></div>
        <div class="position-absolute bottom-0 start-50 w-25 h-25 bg-blue-600/10 rounded-circle blur-3xl" style="transform: translate(-50%, 30%);"></div>
        <div class="position-absolute inset-0 bg-[radial-gradient(#334155_1px,_transparent_1px)] bg-[length:24px_24px] opacity-20"></div>
    </div>

    <!-- Main Container -->
    <div class="position-relative w-100" style="max-width: 72rem; z-index:1;">
        <div class="row g-0 bg-slate-900/80 border border-slate-800/60 rounded-3xl shadow-2xl backdrop-blur-2xl overflow-hidden">

            <!-- Left Panel: Branding & Info (hidden on small screens) -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 bg-gradient-to-br from-indigo-950/60 via-slate-900/90 to-slate-950 border-end border-slate-800/60 position-relative" style="min-height: 28rem;">
                <!-- Inner Decorative Element -->
                <div class="position-absolute top-0 start-0 w-50 h-50 bg-indigo-500/10 rounded-circle blur-2xl" style="transform: translate(-30%, -30%);"></div>

                <!-- Brand Header -->
                <div class="position-relative">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-gradient-to-tr from-indigo-600 to-indigo-400 p-3 rounded-3 d-inline-flex text-white shadow-lg shadow-indigo-600/30">
                            <i class="bi bi-airplane-fill fs-4"></i>
                        </div>
                        <span class="fs-2 fw-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">Flight CRM</span>
                    </div>
                    <div class="mt-4">
                        <h1 class="display-6 fw-extrabold text-white tracking-tight lh-sm">Enterprise Flight Booking & Customer Portal</h1>
                        <p class="mt-2 text-slate-400 small lh-base">Streamline flight reservations, agent workflows, ticketing queues, and secure customer authorizations in one unified platform.</p>
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div class="position-relative my-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="p-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 d-inline-flex mt-1"><i class="bi bi-check-circle-fill"></i></span>
                        <div><h6 class="text-slate-200 small fw-semibold">Real-Time Booking Workflows</h6><p class="text-slate-400 small">Manage agent bookings and dynamic itinerary adjustments effortlessly.</p></div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="p-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 d-inline-flex mt-1"><i class="bi bi-shield-lock-fill"></i></span>
                        <div><h6 class="text-slate-200 small fw-semibold">Secure Authorizations</h6><p class="text-slate-400 small">Encrypted e-signatures and customer verification links.</p></div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="p-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 d-inline-flex mt-1"><i class="bi bi-clock-fill"></i></span>
                        <div><h6 class="text-slate-200 small fw-semibold">Manager Ticketing Queue</h6><p class="text-slate-400 small">Automated payment approval and e-ticket generation.</p></div>
                    </div>
                </div>

                <!-- Footer Status -->
                <div class="position-relative pt-3 border-top border-slate-800/80 d-flex justify-content-between align-items-center small text-slate-500">
                    <span class="d-inline-flex align-items-center gap-2"><span class="bg-emerald-500 w-2 h-2 rounded-circle d-inline-block animate-pulse"></span><span class="text-slate-400 fw-medium">System Active</span></span>
                    <span>v1.0.0</span>
                </div>
            </div>

            <!-- Right Panel: Login Form -->
            <div class="col-lg-7 p-4 p-sm-5 p-xl-5 d-flex flex-column justify-content-between bg-slate-900/50" x-data="{ showPassword: false, submitting: false }">

                <div>
                    <!-- Mobile Brand Header -->
                    <div class="d-flex d-lg-none align-items-center justify-content-center gap-2 mb-4">
                        <div class="bg-gradient-to-tr from-indigo-600 to-indigo-500 p-2 rounded-3 text-white shadow-lg shadow-indigo-600/25">
                            <i class="bi bi-airplane-fill fs-5"></i>
                        </div>
                        <span class="fs-5 fw-bold text-white tracking-tight">Flight CRM</span>
                    </div>

                    <!-- Form Header -->
                    <div class="mb-4">
                        <h2 class="display-6 fw-bold tracking-tight text-white">Sign in to workspace</h2>
                        <p class="mt-2 text-slate-400 small">Enter your credentials to access your agent, manager, or administrator account.</p>
                    </div>

                    <!-- Flash / Error Alerts -->
                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 p-3 text-rose-300 d-flex align-items-start gap-2 small animate-fade-in">
                            <i class="bi bi-exclamation-circle text-rose-400 fs-5 mt-1"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="fw-medium mb-0">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="mb-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-3 text-emerald-300 d-flex align-items-start gap-2 small">
                            <i class="bi bi-check-circle text-emerald-400 fs-5 mt-1"></i>
                            <p class="fw-medium mb-0">{{ session('status') }}</p>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form action="{{ route('login') }}" method="POST" @submit="submitting = true" class="needs-validation" novalidate>
                        @csrf

                        <!-- Email Address Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label text-xs fw-semibold text-uppercase tracking-wider text-slate-300">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-950/80 border-slate-800 text-slate-500 rounded-start-xl"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="name@company.com" class="form-control bg-slate-950/80 border-slate-800 text-slate-100 placeholder-slate-600 rounded-end-xl focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 py-2">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label text-xs fw-semibold text-uppercase tracking-wider text-slate-300">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-950/80 border-slate-800 text-slate-500 rounded-start-xl"><i class="bi bi-lock"></i></span>
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="••••••••••••" class="form-control bg-slate-950/80 border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 py-2">
                                <button type="button" @click="showPassword = !showPassword" class="btn btn-outline-secondary border-slate-800 bg-slate-950/80 text-slate-500 hover:text-slate-300 rounded-end-xl" tabindex="-1">
                                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="form-check">
                                <input id="remember" name="remember" type="checkbox" class="form-check-input border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500/40 focus:ring-offset-slate-900" style="border-radius: 0.25rem;">
                                <label for="remember" class="form-check-label text-slate-300 small fw-medium">Keep me signed in</label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" :disabled="submitting" class="btn btn-primary w-100 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 border-0 shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/50 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold text-sm transition-all" style="background-color: #4f46e5;">
                            <span x-show="submitting" x-cloak class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <span x-text="submitting ? 'Signing in...' : 'Sign In'">Sign In</span>
                        </button>
                    </form>
                </div>

                <!-- Footer Copyright -->
                <div class="mt-4 text-center text-slate-600 small">
                    &copy; {{ date('Y') }} Flight CRM System. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (optional for toggles etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>