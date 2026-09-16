<!DOCTYPE html>
<html lang="en" data-bs-theme="light" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Merchant Charge Terminal') - Direct Gateway</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        :root {
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --primary-blue: #0d6efd;
            --primary-hover: #0b5ed7;
        }

        body {
            font-family: var(--font-main);
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4, h5, h6 {
            color: #0f172a !important;
            font-weight: 700;
        }

        .font-mono {
            font-family: var(--font-mono) !important;
        }

        .form-label {
            color: #334155;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.35rem;
        }

        .form-control, .form-select {
            color: #0f172a !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.925rem;
            font-weight: 500;
            padding: 0.6rem 0.85rem;
        }

        .form-control:focus, .form-select:focus {
            color: #0f172a !important;
            background-color: #ffffff !important;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            outline: none;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
            font-weight: 400;
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #64748b;
        }

        .software-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .merchant-select-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .merchant-select-card:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }

        .merchant-select-card.selected {
            border-color: #0d6efd !important;
            background-color: #f0f7ff !important;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
        }

        .btn-charge-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            border: none;
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
            transition: all 0.15s ease-in-out;
        }

        .btn-charge-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
            color: #ffffff !important;
        }

        .btn-charge-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Virtual Credit Card Mockup */
        .credit-card-preview {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            padding: 20px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.25);
        }

        .chip {
            width: 38px;
            height: 26px;
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            border-radius: 4px;
            position: relative;
        }

        /* Live status dot */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
            position: relative;
            background-color: #198754;
            box-shadow: 0 0 6px #198754;
        }

        /* Tables */
        .table {
            --bs-table-color: #0f172a;
            --bs-table-bg: #ffffff;
            --bs-table-border-color: #e2e8f0;
        }

        .table thead th {
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            font-weight: 600;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">

    @yield('content')

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Global CSRF Header Setup -->
    <script>
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    </script>

    @stack('scripts')
</body>
</html>
