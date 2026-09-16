<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRemark;
use App\Models\Merchant;
use App\Models\NmiTransaction;
use App\Services\NmiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MerchantChargeController extends Controller
{
    protected NmiService $nmiService;

    public function __construct(NmiService $nmiService)
    {
        $this->nmiService = $nmiService;
    }

    /**
     * Display the Merchant Charge portal or Login form if unauthenticated.
     */
    public function index(Request $request): View
    {
        if (! Auth::check()) {
            return view('merchant_charge.login');
        }

        $merchants = Merchant::where('is_active', true)->orderBy('name')->get();
        if ($merchants->isEmpty()) {
            $merchants = Merchant::orderBy('name')->get();
        }

        $merchants->each(function ($m) {
            $walletBalance = (float) ($m->wallet_balance ?? 0);
            $totalCharged = (float) NmiTransaction::where('merchant_id', $m->id)
                ->where('status', 'approved')
                ->sum('amount');

            $m->wallet_balance_num = $walletBalance;
            $m->wallet_balance_formatted = number_format($walletBalance, 2);
            $m->total_charged = $totalCharged;
            $m->total_charged_formatted = number_format($totalCharged, 2);
        });

        $recentBookings = Booking::with(['passengers', 'bookingCards'])
            ->latest()
            ->take(15)
            ->get();

        $recentTransactions = NmiTransaction::with(['merchant', 'booking'])
            ->latest()
            ->take(20)
            ->get();

        $transactionsData = $recentTransactions->map(function ($tx) {
            return [
                'id'             => $tx->id,
                'transaction_id' => $tx->transaction_id,
                'order_id'       => $tx->order_id,
                'merchant_name'  => $tx->merchant ? $tx->merchant->name : 'N/A',
                'customer_name'  => trim(($tx->customer_first_name ?? '') . ' ' . ($tx->customer_last_name ?? '')) ?: 'N/A',
                'booking_id'     => $tx->booking ? $tx->booking->booking_id : null,
                'card_last4'     => $tx->card_last4,
                'card_brand'     => $tx->card_brand ?: 'Card',
                'amount'         => number_format((float) $tx->amount, 2),
                'status'         => $tx->status,
                'processed_at'   => $tx->processed_at ? $tx->processed_at->format('M d, Y H:i') : $tx->created_at->format('M d, Y H:i'),
            ];
        })->values()->toArray();

        return view('merchant_charge.index', compact('merchants', 'recentBookings', 'recentTransactions', 'transactionsData'));
    }

    /**
     * Handle login submission for the Merchant Charge portal.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember', true);

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is deactivated. Please contact an administrator.',
                    ], 403);
                }
                return back()->withErrors(['email' => 'Your account is deactivated. Please contact an administrator.'])->onlyInput('email');
            }

            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route('merchant.charge.index'),
                    'message'  => 'Authentication successful.',
                ]);
            }

            return redirect()->route('merchant.charge.index');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 422);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout from the charge portal.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('merchant.charge.index');
    }

    /**
     * Search bookings by PNR, Booking ID, Passenger Name, Phone, Email, or Cardholder.
     */
    public function searchBookings(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if (empty($query)) {
            $bookings = Booking::with(['passengers', 'bookingCards'])
                ->latest()
                ->take(15)
                ->get();
        } else {
            $bookings = Booking::with(['passengers', 'bookingCards'])
                ->where(function ($q) use ($query) {
                    $q->where('airline_pnr', 'like', "%{$query}%")
                        ->orWhere('gk_pnr', 'like', "%{$query}%")
                        ->orWhere('booking_id', 'like', "%{$query}%")
                        ->orWhere('id', $query)
                        ->orWhere('card_holder_name', 'like', "%{$query}%")
                        ->orWhere('email_address', 'like', "%{$query}%")
                        ->orWhere('billing_phone', 'like', "%{$query}%")
                        ->orWhere('calling_number', 'like', "%{$query}%")
                        ->orWhereHas('passengers', function ($pq) use ($query) {
                            $pq->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%");
                        });
                })
                ->latest()
                ->take(20)
                ->get();
        }

        $formatted = $bookings->map(function ($booking) {
            $firstPass = $booking->passengers->first();
            $passengerName = $firstPass ? trim(($firstPass->first_name ?? '') . ' ' . ($firstPass->last_name ?? '')) : ($booking->card_holder_name ?: 'N/A');

            return [
                'id'                 => $booking->id,
                'booking_id'         => $booking->booking_id,
                'airline_pnr'        => $booking->airline_pnr ?: $booking->gk_pnr ?: 'N/A',
                'gk_pnr'             => $booking->gk_pnr,
                'airline_name'       => $booking->airline_name ?: $booking->airline_code ?: 'Flight Booking',
                'customer_name'      => $passengerName,
                'card_holder_name'   => $booking->card_holder_name,
                'card_type'          => $booking->card_type,
                'card_last_4'        => $booking->card_last_4,
                'card_expiration'    => $booking->card_expiration,
                'email'              => $booking->email_address,
                'phone'              => $booking->billing_phone ?: $booking->calling_number,
                'billing_address'    => $booking->billing_address,
                'total_amount'       => (float) $booking->total_amount,
                'currency'           => $booking->currency ?: 'USD',
                'merchant_id'        => $booking->merchant_id,
                'merchant_name'      => $booking->merchant,
                'booking_status'     => $booking->booking_status,
                'payment_status'     => $booking->payment_status,
                'payment_info'       => $booking->payment_info,
                'booking_date'       => $booking->booking_date ? $booking->booking_date->format('M d, Y') : null,
                'cards_count'        => $booking->bookingCards->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $formatted->count(),
            'data'    => $formatted,
        ]);
    }

    /**
     * Retrieve single booking details with full customer & payment info.
     */
    public function getBooking(Booking $booking): JsonResponse
    {
        $booking->load(['passengers', 'bookingCards']);

        $firstPassenger = $booking->passengers->first();
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

        $extraCards = $booking->bookingCards->map(function ($card) {
            return [
                'id'               => $card->id,
                'card_holder_name' => $card->card_holder_name,
                'card_type'        => $card->card_type,
                'card_last_4'      => $card->card_last_4,
                'card_expiration'  => $card->card_expiration,
            ];
        });

        return response()->json([
            'success'             => true,
            'id'                  => $booking->id,
            'booking_id'          => $booking->booking_id,
            'airline_pnr'         => $booking->airline_pnr,
            'gk_pnr'              => $booking->gk_pnr,
            'airline_name'        => $booking->airline_name,
            'customer_first_name' => $firstName,
            'customer_last_name'  => $lastName,
            'email'               => $booking->email_address,
            'phone'               => $booking->billing_phone ?: $booking->calling_number,
            'billing_address'     => $booking->billing_address,
            'amount'              => (float) $booking->total_amount,
            'currency'            => $booking->currency ?: 'USD',
            'merchant_id'         => $booking->merchant_id,
            'booking_status'      => $booking->booking_status,
            'payment_status'      => $booking->payment_status,
            'payment_info'        => $booking->payment_info,
            'card_holder_name'    => $booking->card_holder_name,
            'card_type'           => $booking->card_type,
            'card_last_4'         => $booking->card_last_4,
            'card_expiration'     => $booking->card_expiration,
            'extra_cards'         => $extraCards,
        ]);
    }

    /**
     * Process real-time direct charge via NMI Gateway.
     */
    public function charge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id'         => 'required|exists:merchants,id',
            'booking_id'          => 'nullable|exists:bookings,id',
            'customer_first_name' => 'nullable|string|max:255',
            'customer_last_name'  => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:255',
            'email'               => 'nullable|email|max:255',
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
            'payment_info_note'   => 'nullable|string|max:1000',
        ]);

        $merchant = Merchant::findOrFail($validated['merchant_id']);

        if (! $merchant->is_active) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'The selected merchant account is currently marked inactive.',
            ], 422);
        }

        try {
            $this->nmiService->useMerchant($merchant);

            $orderId = ! empty($validated['order_description'])
                ? Str::limit(preg_replace('/[^A-Za-z0-9\-\_]/', '', $validated['order_description']), 35, '')
                : (! empty($validated['booking_id']) ? 'BK-' . $validated['booking_id'] . '-' . time() : 'CHG-' . time());

            $chargeData = [
                'amount'            => $validated['amount'],
                'ccnumber'          => $validated['ccnumber'],
                'ccexp'             => $validated['ccexp'],
                'cvv'               => $validated['cvv'],
                'first_name'        => $validated['customer_first_name'] ?? null,
                'last_name'         => $validated['customer_last_name'] ?? null,
                'email'             => null, // Omit to prevent auto customer emailing
                'phone'             => $validated['phone'] ?? null,
                'address1'          => $validated['address1'] ?? null,
                'city'              => $validated['city'] ?? null,
                'state'             => $validated['state'] ?? null,
                'zip'               => $validated['zip'] ?? null,
                'country'           => $validated['country'] ?? 'US',
                'order_id'          => $orderId,
                'booking_id'        => $validated['booking_id'] ?? null,
            ];

            // Execute NMI Sale
            $rawResponse = $this->nmiService->sale($chargeData);

            // Log Transaction in DB
            $transaction = $this->nmiService->logTransaction(
                $chargeData,
                $rawResponse,
                $merchant->id,
                $validated['booking_id'] ?? null
            );

            // Interpret full response diagnostics
            $diagnostic = $this->nmiService->interpretResponse($rawResponse);

            $booking = null;
            if (! empty($validated['booking_id'])) {
                $booking = Booking::find($validated['booking_id']);
            }

            // If Approved, update booking details and add audit remark
            if ($diagnostic['is_approved']) {
                if ($booking) {
                    $booking->payment_status = 'received';
                    if (empty($booking->merchant_id)) {
                        $booking->merchant_id = $merchant->id;
                        $booking->merchant = $merchant->name;
                    }

                    // Append payment info notes
                    $newPaymentInfo = $booking->payment_info ? $booking->payment_info . "\n" : '';
                    $newPaymentInfo .= '[' . now()->format('Y-m-d H:i:s') . '] Charged $' . number_format($transaction->amount, 2) . ' via NMI (' . $merchant->name . ') TxID: ' . ($transaction->transaction_id ?: 'N/A') . ' Auth: ' . ($diagnostic['auth_code'] ?: 'N/A');

                    if (! empty($validated['payment_info_note'])) {
                        $newPaymentInfo .= ' | Note: ' . $validated['payment_info_note'];
                    }

                    $booking->payment_info = $newPaymentInfo;
                    $booking->save();

                    // Create System Booking Remark
                    BookingRemark::create([
                        'booking_id' => $booking->id,
                        'user_id'    => Auth::id(),
                        'type'       => 'system_log',
                        'remark'     => 'Direct Merchant Charge: $' . number_format($transaction->amount, 2) . ' APPROVED via ' . $merchant->name . '. Transaction ID: ' . ($transaction->transaction_id ?: 'N/A') . ', Auth Code: ' . ($diagnostic['auth_code'] ?: 'N/A'),
                    ]);
                }

                // Deduct charged amount directly from merchant wallet balance in real time
                $currentBalance = (float) ($merchant->wallet_balance ?? 0);
                $newBalance = max(0, $currentBalance - (float) $transaction->amount);
                $merchant->wallet_balance = $newBalance;
                $merchant->save();

                // Recalculate total charged volume for this merchant
                $totalCharged = (float) NmiTransaction::where('merchant_id', $merchant->id)
                    ->where('status', 'approved')
                    ->sum('amount');

                return response()->json([
                    'success' => true,
                    'status'  => 'approved',
                    'message' => $diagnostic['friendly_message'],
                    'data'    => [
                        'transaction_id'             => $transaction->transaction_id,
                        'auth_code'                  => $diagnostic['auth_code'],
                        'amount'                     => number_format((float) $transaction->amount, 2),
                        'currency'                   => $transaction->currency ?: 'USD',
                        'card_last4'                 => $transaction->card_last4,
                        'card_brand'                 => $transaction->card_brand ?: 'Card',
                        'merchant_id'                => $merchant->id,
                        'merchant_name'              => $merchant->name,
                        'merchant_wallet_balance'    => number_format($newBalance, 2),
                        'merchant_wallet_balance_num'=> $newBalance,
                        'merchant_total_charged'     => number_format($totalCharged, 2),
                        'order_id'                   => $transaction->order_id,
                        'avs_code'                   => $diagnostic['avs_code'],
                        'avs_description'            => $diagnostic['avs_description'],
                        'cvv_code'                   => $diagnostic['cvv_code'],
                        'cvv_description'            => $diagnostic['cvv_description'],
                        'response_code'              => $diagnostic['response_code'],
                        'response_text'              => $diagnostic['response_text'],
                        'processed_at'               => $transaction->processed_at ? $transaction->processed_at->format('M d, Y H:i:s') : now()->format('M d, Y H:i:s'),
                        'booking_id'                 => $booking ? $booking->booking_id : null,
                        'payment_info'               => $booking ? $booking->payment_info : null,
                    ],
                ]);
            } else {
                // Declined or error
                return response()->json([
                    'success' => false,
                    'status'  => $diagnostic['status'],
                    'message' => $diagnostic['friendly_message'],
                    'data'    => [
                        'transaction_id'  => $transaction->transaction_id,
                        'response_code'   => $diagnostic['response_code'],
                        'response_text'   => $diagnostic['response_text'],
                        'avs_code'        => $diagnostic['avs_code'],
                        'avs_description' => $diagnostic['avs_description'],
                        'cvv_code'        => $diagnostic['cvv_code'],
                        'cvv_description' => $diagnostic['cvv_description'],
                        'merchant_name'   => $merchant->name,
                        'amount'          => number_format((float) $transaction->amount, 2),
                    ],
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Gateway Communication Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent transactions list for live ledger updating.
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = NmiTransaction::with(['merchant', 'booking'])
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($tx) {
                return [
                    'id'             => $tx->id,
                    'transaction_id' => $tx->transaction_id,
                    'order_id'       => $tx->order_id,
                    'merchant_name'  => $tx->merchant ? $tx->merchant->name : 'N/A',
                    'customer_name'  => trim(($tx->customer_first_name ?? '') . ' ' . ($tx->customer_last_name ?? '')) ?: 'N/A',
                    'booking_id'     => $tx->booking ? $tx->booking->booking_id : null,
                    'card_last4'     => $tx->card_last4,
                    'card_brand'     => $tx->card_brand ?: 'Card',
                    'amount'         => number_format((float) $tx->amount, 2),
                    'currency'       => $tx->currency ?: 'USD',
                    'status'         => $tx->status,
                    'processed_at'   => $tx->processed_at ? $tx->processed_at->format('M d, Y H:i') : $tx->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }
}
