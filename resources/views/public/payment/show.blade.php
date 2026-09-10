<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - {{ $merchant->name ?? 'Flight CRM' }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: #f8fafc;
        }
        .payment-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 1.25rem;
        }
        .merchant-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-top-left-radius: 1.25rem;
            border-top-right-radius: 1.25rem;
        }
        .form-control, .form-select {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a;
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        .form-control::placeholder {
            color: #64748b;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <div class="container px-3 px-md-4" style="max-width: 680px;">
        <!-- Payment Container -->
        <div class="payment-card overflow-hidden">
            <!-- Merchant Brand Header -->
            <div class="merchant-header p-4 text-center text-white">
                <div class="d-inline-flex p-3 rounded-circle bg-white text-primary mb-2 shadow-sm">
                    <i class="bi bi-shield-check fs-2"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $merchant->name }}</h3>
                <p class="mb-0 text-white-50 small">Secure Credit Card Payment Gateway</p>
            </div>

            <div class="p-4 p-md-5">
                <!-- Alerts -->
                @if (session('error'))
                    <div class="alert alert-danger border-0 bg-danger text-white rounded-3 mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0 bg-danger text-white rounded-3 mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Payment Error:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Payment Summary Box -->
                <div class="p-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small fw-semibold text-uppercase">Payment Description</span>
                        <span class="badge bg-primary-subtle text-primary-emphasis fw-bold">Ref #{{ $paymentLink->token_short ?? Str::limit($paymentLink->token, 8, '') }}</span>
                    </div>
                    <div class="fw-semibold text-white mb-2 fs-6">
                        {{ $paymentLink->description ?: 'Service Payment' }}
                    </div>
                    @if($paymentLink->booking)
                        <div class="small text-secondary mb-2">
                            <i class="bi bi-ticket-perforated me-1"></i> Booking Reference: <strong>{{ $paymentLink->booking->pnr }}</strong>
                        </div>
                    @endif
                    <hr class="my-2 border-secondary opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary">Total Amount Due</span>
                        <span class="fs-3 fw-extrabold text-success">${{ number_format($paymentLink->amount, 2) }} <small class="fs-6 text-white-50">{{ $paymentLink->currency ?: 'USD' }}</small></span>
                    </div>
                </div>

                <!-- Card Payment Form -->
                <form action="{{ route('payment.process', ['token' => $paymentLink->token]) }}" method="POST" id="paymentForm">
                    @csrf
                    
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-person-circle me-1 text-primary"></i> Billing Information</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $paymentLink->customer_first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $paymentLink->customer_last_name) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $paymentLink->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $paymentLink->phone) }}">
                        </div>
                    </div>

                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-credit-card-fill me-1 text-primary"></i> Payment Details</h5>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Card Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="ccnumber" id="ccnumber" class="form-control font-monospace" placeholder="4111 2222 3333 4444" maxlength="19" required>
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">
                                <i class="bi bi-credit-card fs-5"></i>
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Expiration Date (MMYY) <span class="text-danger">*</span></label>
                            <input type="text" name="ccexp" class="form-control font-monospace" placeholder="1228" maxlength="4" required>
                            <small class="text-secondary" style="font-size: 0.75rem;">e.g. 1228 for Dec 2028</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">CVV Code <span class="text-danger">*</span></label>
                            <input type="password" name="cvv" class="form-control font-monospace" placeholder="123" maxlength="4" required>
                        </div>
                    </div>

                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-geo-alt-fill me-1 text-primary"></i> Billing Address (Optional)</h5>
                    <div class="mb-3">
                        <input type="text" name="address1" class="form-control mb-2" placeholder="Street Address">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="city" class="form-control" placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="state" class="form-control" placeholder="State/Province">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="zip" class="form-control" placeholder="Zip Code">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="submitBtn" class="btn btn-success btn-lg fw-bold py-3 shadow-lg rounded-3">
                            <i class="bi bi-lock-fill me-1"></i> Pay ${{ number_format($paymentLink->amount, 2) }} Securely
                        </button>
                    </div>

                    <div class="text-center mt-3 text-secondary small">
                        <i class="bi bi-shield-lock-fill me-1 text-success"></i> 256-Bit TLS Encrypted Secure Transaction
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4 text-secondary small">
            &copy; {{ date('Y') }} {{ $merchant->name }}. All rights reserved.
        </div>
    </div>
</body>
</html>
