<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Merchant;
use App\Models\NmiTransaction;
use App\Models\PaymentLink;
use App\Services\NmiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentChargeController extends Controller
{
    protected NmiService $nmiService;

    public function __construct(NmiService $nmiService)
    {
        $this->nmiService = $nmiService;
    }

    /**
     * Display listing of payment links and NMI transaction logs.
     */
    public function index(Request $request): View
    {
        $merchants = Merchant::where('is_active', true)->get();
        if ($merchants->isEmpty()) {
            $merchants = Merchant::all();
        }

        $bookings = Booking::with('passengers')->latest()->take(100)->get();

        $paymentLinks = PaymentLink::with(['merchant', 'booking', 'creator'])
            ->latest()
            ->paginate(15, ['*'], 'links_page');

        $transactions = NmiTransaction::with(['merchant', 'booking', 'paymentLink'])
            ->latest()
            ->paginate(15, ['*'], 'tx_page');

        // Stats summary
        $totalCharged = NmiTransaction::where('status', 'approved')->sum('amount');
        $activeLinksCount = PaymentLink::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $paidLinksCount = PaymentLink::where('status', 'paid')->count();
        $totalTransactionsCount = NmiTransaction::count();

        return view('admin.charges.index', compact(
            'merchants',
            'bookings',
            'paymentLinks',
            'transactions',
            'totalCharged',
            'activeLinksCount',
            'paidLinksCount',
            'totalTransactionsCount'
        ));
    }

    /**
     * Store a newly created payment link.
     */
    public function storeLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_id'         => 'required|exists:merchants,id',
            'booking_id'          => 'nullable|exists:bookings,id',
            'customer_first_name' => 'required|string|max:255',
            'customer_last_name'  => 'required|string|max:255',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'nullable|string|max:500',
            'expiry_hours'        => 'nullable|integer|min:1',
        ]);

        $merchant = Merchant::findOrFail($validated['merchant_id']);
        $expiryHours = (int) ($validated['expiry_hours'] ?? 48);

        $paymentLink = PaymentLink::create([
            'token'               => Str::random(40),
            'merchant_id'         => $merchant->id,
            'booking_id'          => $validated['booking_id'] ?? null,
            'created_by'          => auth()->id(),
            'customer_first_name' => $validated['customer_first_name'],
            'customer_last_name'  => $validated['customer_last_name'],
            'email'               => $validated['email'] ?? null,
            'phone'               => $validated['phone'] ?? null,
            'amount'              => $validated['amount'],
            'currency'            => $merchant->currency ?: 'USD',
            'description'         => $validated['description'] ?? null,
            'status'              => 'pending',
            'expires_at'          => now()->addHours($expiryHours),
        ]);

        return redirect()->route('admin.charges.index')
            ->with('success', 'Payment Link created successfully! Link: ' . $paymentLink->public_url);
    }

    /**
     * Cancel an active payment link.
     */
    public function cancelLink(PaymentLink $paymentLink): RedirectResponse
    {
        if ($paymentLink->isPaid()) {
            return redirect()->back()->with('error', 'Cannot cancel a payment link that has already been paid.');
        }

        $paymentLink->update(['status' => 'cancelled']);

        return redirect()->route('admin.charges.index')
            ->with('success', 'Payment Link has been cancelled.');
    }

    /**
     * Direct charge card via NMI Gateway (No customer email required, no email sent).
     */
    public function directCharge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_id'         => 'required|exists:merchants,id',
            'booking_id'          => 'nullable|exists:bookings,id',
            'customer_first_name' => 'required|string|max:255',
            'customer_last_name'  => 'required|string|max:255',
            'phone'               => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'ccnumber'            => 'required|string',
            'ccexp'               => 'required|string',
            'cvv'                 => 'required|string',
            'address1'            => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:255',
            'state'               => 'nullable|string|max:255',
            'zip'                 => 'nullable|string|max:255',
            'country'             => 'nullable|string|max:255',
            'order_description'   => 'nullable|string|max:500',
        ]);

        $merchant = Merchant::findOrFail($validated['merchant_id']);

        try {
            $this->nmiService->useMerchant($merchant);

            $orderId = !empty($validated['order_description']) 
                ? Str::limit(preg_replace('/[^A-Za-z0-9\-\_]/', '', $validated['order_description']), 35, '') 
                : ($validated['booking_id'] ? 'BOOKING-' . $validated['booking_id'] : 'ADM-' . time());

            $chargeData = [
                'amount'            => $validated['amount'],
                'ccnumber'          => $validated['ccnumber'],
                'ccexp'             => $validated['ccexp'],
                'cvv'               => $validated['cvv'],
                'first_name'        => $validated['customer_first_name'],
                'last_name'         => $validated['customer_last_name'],
                'email'             => null, // Omitted to ensure no email sending
                'phone'             => $validated['phone'] ?? null,
                'address1'          => $validated['address1'] ?? null,
                'city'              => $validated['city'] ?? null,
                'state'             => $validated['state'] ?? null,
                'zip'               => $validated['zip'] ?? null,
                'country'           => $validated['country'] ?? null,
                'order_id'          => $orderId,
                'order_description' => $validated['order_description'] ?? null,
            ];

            $response = $this->nmiService->sale($chargeData);

            $transaction = $this->nmiService->logTransaction(
                $chargeData,
                $response,
                $merchant->id,
                $validated['booking_id'] ?? null
            );

            if ($transaction->status === 'approved') {
                return redirect()->route('admin.charges.index')
                    ->with('success', 'Payment of $' . number_format($transaction->amount, 2) . ' approved successfully! Transaction ID: ' . $transaction->transaction_id);
            } else {
                $reason = $response['responsetext'] ?? 'Transaction was declined.';
                return redirect()->route('admin.charges.index')
                    ->with('error', 'Charge Failed: ' . $reason);
            }
        } catch (Exception $e) {
            return redirect()->route('admin.charges.index')
                ->with('error', 'Error processing charge: ' . $e->getMessage());
        }
    }

    /**
     * Find booking by Airline PNR or Booking ID string to prefill details.
     */
    public function findBookingByPnr(Request $request): JsonResponse
    {
        $pnr = trim($request->input('pnr', ''));

        if (empty($pnr)) {
            return response()->json(['success' => false, 'message' => 'Please enter a PNR or Booking ID.'], 422);
        }

        $booking = Booking::where('airline_pnr', $pnr)
            ->orWhere('gk_pnr', $pnr)
            ->orWhere('booking_id', $pnr)
            ->orWhere('id', $pnr)
            ->latest()
            ->first();

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'No booking found matching PNR: ' . $pnr], 404);
        }

        $firstPassenger = $booking->passengers()->first();
        $firstName = '';
        $lastName = '';

        if ($firstPassenger) {
            $firstName = $firstPassenger->first_name ?? '';
            $lastName = $firstPassenger->last_name ?? '';
        } elseif (! empty($booking->card_holder_name)) {
            $parts = explode(' ', trim($booking->card_holder_name), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        return response()->json([
            'success'             => true,
            'id'                  => $booking->id,
            'booking_id'          => $booking->booking_id,
            'pnr'                 => $booking->pnr,
            'customer_first_name' => $firstName,
            'customer_last_name'  => $lastName,
            'phone'               => $booking->phone,
            'amount'              => $booking->total_amount,
            'merchant_id'         => $booking->merchant_id,
            'billing_address'     => $booking->billing_address ?? null,
            'billing_city'        => $booking->billing_city ?? null,
            'billing_state'       => $booking->billing_state ?? null,
            'billing_zip'         => $booking->billing_zip ?? null,
            'billing_country'     => $booking->billing_country ?? null,
        ]);
    }

    /**
     * Get booking details prefill API for Admin forms.
     */
    public function getBookingDetails(Booking $booking): JsonResponse
    {
        $firstPassenger = $booking->passengers()->first();
        $firstName = '';
        $lastName = '';

        if ($firstPassenger) {
            $firstName = $firstPassenger->first_name ?? '';
            $lastName = $firstPassenger->last_name ?? '';
        } elseif (! empty($booking->card_holder_name)) {
            $parts = explode(' ', trim($booking->card_holder_name), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        return response()->json([
            'success'             => true,
            'id'                  => $booking->id,
            'pnr'                 => $booking->pnr,
            'customer_first_name' => $firstName,
            'customer_last_name'  => $lastName,
            'email'               => $booking->email,
            'phone'               => $booking->phone,
            'amount'              => $booking->total_amount,
            'merchant_id'         => $booking->merchant_id,
            'billing_address'     => $booking->billing_address ?? null,
            'billing_city'        => $booking->billing_city ?? null,
            'billing_state'       => $booking->billing_state ?? null,
            'billing_zip'         => $booking->billing_zip ?? null,
            'billing_country'     => $booking->billing_country ?? null,
        ]);
    }
}
