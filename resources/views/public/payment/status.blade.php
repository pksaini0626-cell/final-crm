<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Payment Link</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: #f8fafc;
        }
        .status-card {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 1.25rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <div class="container px-3 px-md-4" style="max-width: 540px;">
        <div class="status-card p-4 p-md-5 text-center">
            <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-20 text-warning mb-3">
                <i class="bi bi-exclamation-octagon-fill display-3"></i>
            </div>

            <h3 class="fw-bold text-white mb-2">{{ $title }}</h3>
            <p class="text-secondary mb-4 fs-6">{{ $message }}</p>

            <div class="p-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-3 mb-4 text-start small">
                <div class="text-secondary mb-1"><i class="bi bi-info-circle me-1"></i> Need assistance?</div>
                <div class="text-white">If you believe this is an error or need a new payment link, please contact customer support or your assigned agent.</div>
            </div>
        </div>

        <div class="text-center mt-4 text-secondary small">
            &copy; {{ date('Y') }} Flight CRM System. All rights reserved.
        </div>
    </div>
</body>
</html>
