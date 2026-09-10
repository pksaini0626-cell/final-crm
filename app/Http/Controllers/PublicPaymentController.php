<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Services\NmiService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPaymentController extends Controller
{
    protected NmiService $nmiService;

    public function __construct(NmiService $nmiService)
    {
        $this->nmiService = $nmiService;
    }

    /**
     * Display public payment page for customer.
     */
    public function show(string $token)
    {
        $paymentLink = PaymentLink::with(['merchant', 'booking'])->where('token', $token)->firstOrFail();

        if ($paymentLink->isPaid()) {
            $transaction = $paymentLink->transactions()->where('status', 'approved')->latest()->first();
            return view('public.payment.success', compact('paymentLink', 'transaction'));
        }

        if ($paymentLink->isExpired()) {
            return view('public.payment.status', [
                'type'    => 'expired',
                'title'   => 'Payment Link Expired',
                'message' => 'This payment link has expired. Please contact support or your agent to receive a new payment link.',
            ]);
        }

        if ($paymentLink->status === 'cancelled') {
            return view('public.payment.status', [
                'type'    => 'cancelled',
                'title'   => 'Payment Link Cancelled',
                'message' => 'This payment link is no longer valid.',
            ]);
        }

        $merchant = $paymentLink->merchant;
        $tokenizationKey = $merchant->tokenization_key ?: config('nmi.tokenization_key');

        return view('public.payment.show', compact('paymentLink', 'merchant', 'tokenizationKey'));
    }

    /**
     * Process customer payment.
     */
    public function process(Request $request, string $token)
    {
        $paymentLink = PaymentLink::with(['merchant', 'booking'])->where('token', $token)->firstOrFail();

        if (! $paymentLink->isAvailable()) {
            return redirect()->route('payment.show', ['token' => $token])
                ->with('error', 'This payment link is no longer active.');
        }

        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'payment_token' => 'nullable|string',
            'ccnumber'      => 'required_without:payment_token|nullable|string',
            'ccexp'         => 'required_without:payment_token|nullable|string',
            'cvv'           => 'required_without:payment_token|nullable|string',
            'address1'      => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:255',
            'state'         => 'nullable|string|max:255',
            'zip'           => 'nullable|string|max:255',
            'country'       => 'nullable|string|max:255',
        ]);

        $merchant = $paymentLink->merchant;

        try {
            $this->nmiService->useMerchant($merchant);

            $chargeData = [
                'amount'          => $paymentLink->amount,
                'currency'        => $paymentLink->currency ?: 'USD',
                'first_name'      => $request->input('first_name'),
                'last_name'       => $request->input('last_name'),
                'email'           => $request->input('email'),
                'phone'           => $request->input('phone', $paymentLink->phone),
                'address1'        => $request->input('address1'),
                'city'            => $request->input('city'),
                'state'           => $request->input('state'),
                'zip'             => $request->input('zip'),
                'country'         => $request->input('country'),
                'order_id'        => 'PL-' . $paymentLink->id . '-' . time(),
                'payment_link_id' => $paymentLink->id,
                'booking_id'      => $paymentLink->booking_id,
            ];

            if ($request->filled('payment_token')) {
                $chargeData['payment_token'] = $request->input('payment_token');
                $response = $this->nmiService->saleWithToken($chargeData);
            } else {
                $chargeData['ccnumber'] = $request->input('ccnumber');
                $chargeData['ccexp'] = $request->input('ccexp');
                $chargeData['cvv'] = $request->input('cvv');
                $response = $this->nmiService->sale($chargeData);
            }

            $transaction = $this->nmiService->logTransaction(
                $chargeData,
                $response,
                $merchant->id,
                $paymentLink->booking_id,
                $paymentLink->id
            );

            if ($transaction->status === 'approved') {
                $paymentLink->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                ]);

                return view('public.payment.success', compact('paymentLink', 'transaction'));
            } else {
                $reason = $response['responsetext'] ?? 'Transaction was declined.';
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Payment Failed: ' . $reason);
            }
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }
}
