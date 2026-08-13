<!DOCTYPE html>
<html lang="en" data-bs-theme="light" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Flight CRM - Agent Dashboard</title>

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
    
    <!-- Bootstrap 5.3.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        if (window.axios) {
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta && csrfMeta.content) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.content;
            }
        }
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .navbar-brand-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card {
            background-color: #ffffff;
            border-color: #e2e8f0;
        }
        .card-header {
            background-color: #f8fafc;
            border-bottom-color: #e2e8f0;
        }
        .form-control, .form-select {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: #6366f1;
            color: #0f172a;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        /* Light Theme Pagination Styling */
        .pagination {
            margin-bottom: 0;
            display: flex;
            justify-content: center;
            gap: 2px;
        }
        .page-link {
            background-color: #ffffff;
            border-color: #e2e8f0;
            color: #475569;
            border-radius: 6px !important;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .page-link:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .page-item.active .page-link {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }
        .page-item.disabled .page-link {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 0.6;
        }

        /* Dark Theme Pagination Styling */
        .bg-dark .page-link,
        .card.bg-dark .page-link,
        .card-footer.bg-dark .page-link {
            background-color: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
        .bg-dark .page-link:hover,
        .card.bg-dark .page-link:hover,
        .card-footer.bg-dark .page-link:hover {
            background-color: #334155;
            border-color: #475569;
            color: #f8fafc;
        }
        .bg-dark .page-item.active .page-link,
        .card.bg-dark .page-item.active .page-link,
        .card-footer.bg-dark .page-item.active .page-link {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.4);
        }
        .bg-dark .page-item.disabled .page-link,
        .card.bg-dark .page-item.disabled .page-link,
        .card-footer.bg-dark .page-item.disabled .page-link {
            background-color: #0f172a;
            border-color: #1e293b;
            color: #475569;
            opacity: 0.5;
        }
        .bg-dark .text-muted,
        .card-footer.bg-dark .text-muted {
            color: #94a3b8 !important;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg border-bottom border-light-subtle bg-white sticky-top shadow-sm">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex items-center gap-2 font-black fs-4" href="{{ route('bookings.index') }}">
                <span class="p-1 px-2 rounded-3 bg-primary text-white me-1 fs-5"><i class="bi bi-send-fill"></i></span>
                <span class="navbar-brand-gradient fw-bold">Flight CRM</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCRM" aria-controls="navbarCRM" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCRM">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 rounded-2" href="{{ route('bookings.index') }}">
                            <i class="bi bi-journal-bookmark me-1"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('call-logs.*') ? 'active' : '' }}" href="{{ route('call-logs.index') }}">
                            <i class="bi bi-telephone-inbound me-1"></i> Call Logs
                        </a>
                    </li>
                    
                    @if(Auth::check() && in_array(Auth::user()->role, ['manager', 'admin', 'ticketing']))
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2" href="{{ route('manager.tickets.index') }}">
                                <i class="bi bi-ticket-detailed me-1"></i> Ticketing Queue
                            </a>
                        </li>
                    @endif

                    @if(Auth::check() && in_array(Auth::user()->role, ['manager', 'admin', 'changes']))
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('changes.*') ? 'active' : '' }}" href="{{ route('changes.index') }}">
                                <i class="bi bi-arrow-repeat me-1"></i> Changes Queue
                            </a>
                        </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}">
                                <i class="bi bi-shield-lock me-1"></i> Admin Control
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                <i class="bi bi-people me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-2 {{ request()->routeIs('admin.merchants.*') ? 'active' : '' }}" href="{{ route('admin.merchants.index') }}">
                                <i class="bi bi-building me-1"></i> Merchants
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm rounded-3">
                        <i class="bi bi-plus-lg me-1"></i> New Booking
                    </a>

                    @auth
                    <div class="vr h-50 mx-1 text-secondary"></div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border border-secondary-subtle px-3 py-2 rounded-pill fw-semibold">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->alias_name }}
                            <small class="text-primary font-mono">({{ strtoupper(Auth::user()->role) }})</small>
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm px-2.5 py-1.5 rounded-3" title="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="flex-shrink-0 py-4 container-fluid px-lg-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-white border-top border-light-subtle text-center text-secondary small">
        <div class="container">
            &copy; {{ date('Y') }} Flight CRM System. All rights reserved.
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
