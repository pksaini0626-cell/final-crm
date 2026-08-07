<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CustomerAuthController extends Controller
{
    /**
     * Generate secure cryptographic signature hash for PNR authorization.
     *
     * @param Booking $booking
     * @return string
     */
    public static function generateHash(Booking $booking): string
    {
        return hash_hmac('sha256', $booking->id . '|' . $booking->email_address, config('app.key'));
    }

    /**
     * Show the public authorization page.
     */
    public function show(Booking $booking, string $hash)
    {
        if ($hash !== static::generateHash($booking)) {
            abort(403, 'Invalid or expired signature hash.');
        }

        return view('customer.authorize.show', compact('booking', 'hash'));
    }

    /**
     * Process the customer authorization signature.
     */
    public function approve(Request $request, Booking $booking, string $hash)
    {
        if ($hash !== static::generateHash($booking)) {
            abort(403, 'Invalid or expired signature hash.');
        }

        $request->validate([
            'terms_accepted' => 'required|accepted',
            'signature' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $booking) {
            // Update authorization status
            $booking->update([
                'email_auth_taken' => true,
                'booking_status' => 'email_auth_done',
            ]);

            $ip = $request->ip();
            $timestamp = now()->toDateTimeString();

            // Log customer authorization in remarks under the agent's user ID (since user_id is foreign key constraint to users)
            $booking->bookingRemarks()->create([
                'user_id' => $booking->agent_id,
                'remark' => "Customer authorized charges. IP: {$ip}. Signed: {$request->input('signature')}. Timestamp: {$timestamp}.",
                'type' => 'system_log',
            ]);
        });

        return redirect()->back()->with('success', 'Your booking charges have been authorized successfully. The agent has been notified.');
    }
}
