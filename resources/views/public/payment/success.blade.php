<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - {{ $paymentLink->merchant->name ?? 'Flight CRM' }}</title>
    
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
        .receipt-card {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 1.25rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <div class="container px-3 px-md-4" style="max-width: 580px;">
        <div class="receipt-card p-4 p-md-5 text-center">
            <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-20 text-success mb-3">
                <i class="bi bi-check-circle-fill display-3"></i>
            </div>

            <h2 class="fw-bold text-white mb-2">Payment Successful!</h2>
            <p class="text-secondary mb-4">Thank you! Your payment has been processed successfully.</p>

            <!-- Receipt Box -->
            <div class="p-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-3 text-start mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small">Merchant:</span>
                    <span class="fw-semibold text-white">{{ $paymentLink->merchant->name ?? 'Merchant' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small">Customer Name:</span>
                    <span class="fw-semibold text-white">{{ $paymentLink->customer_full_name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small">Transaction ID:</span>
                    <span class="fw-bold font-monospace text-primary">{{ $transaction->transaction_id ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small">Date & Time:</span>
                    <span class="text-white small">{{ $paymentLink->paid_at ? $paymentLink->paid_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A') }}</span>
                </div>
                @if($transaction && $transaction->card_last4)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Payment Method:</span>
                        <span class="text-white font-monospace small"><i class="bi bi-credit-card me-1"></i> {{ strtoupper($transaction->card_brand ?: 'CARD') }} ****{{ $transaction->card_last4 }}</span>
                    </div>
                @endif
                <hr class="my-3 border-secondary opacity-25">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-white">Total Paid:</span>
                    <span class="fs-4 fw-extrabold text-success">${{ number_format($paymentLink->amount, 2) }} {{ $paymentLink->currency ?: 'USD' }}</span>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-light rounded-3 py-2 fw-semibold">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>

        <div class="text-center mt-4 text-secondary small">
            &copy; {{ date('Y') }} {{ $paymentLink->merchant->name ?? 'Flight CRM' }}. All rights reserved.
        </div>
    </div>
</body>
</html>
