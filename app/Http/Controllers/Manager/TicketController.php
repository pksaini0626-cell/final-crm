<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MerchantMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class TicketController extends Controller
{
    protected MerchantMailService $mailService;

    public function __construct(MerchantMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Display a list of bookings awaiting approval or ready for ticketing.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Booking::whereNotIn('booking_status', ['void'])
            ->with(['passengers', 'bookingFlights', 'merchantProfile', 'agent', 'ticketingUser']);

        if ($user && $user->role === 'ticketing') {
            $query->where(function ($q) use ($user) {
                $q->where('ticketing_user_id', $user->id)
                  ->orWhereNull('ticketing_user_id');
            });
        }

        $bookings = $query->latest()->paginate(10);
        $ticketingAgents = \App\Models\User::where('role', 'ticketing')->where('is_active', true)->orderBy('alias_name')->get();

        return view('manager.tickets.index', compact('bookings', 'ticketingAgents'));
    }

    /**
     * Assign booking to a ticketing agent and dispatch notification email.
     */
    public function assignTicketingUser(Request $request, Booking $booking)
    {
        $request->validate([
            'ticketing_user_id' => 'required|exists:users,id',
        ]);

        $ticketingUser = \App\Models\User::findOrFail($request->input('ticketing_user_id'));

        DB::transaction(function () use ($booking, $ticketingUser) {
            $booking->update([
                'ticketing_user_id' => $ticketingUser->id,
            ]);

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Booking assigned to Ticketing Agent: " . ($ticketingUser->alias_name ?: $ticketingUser->name) . " (" . $ticketingUser->email . ").",
                'type' => 'admin_remark',
            ]);
        });

        // Send email alert to ticketing user
        try {
            \Illuminate\Support\Facades\Mail::to($ticketingUser->email)
                ->send(new \App\Mail\TicketingAssignmentMail($booking, Auth::user()));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send ticketing assignment email', [
                'booking_id' => $booking->id,
                'ticketing_user_id' => $ticketingUser->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->back()->with('success', 'Booking successfully assigned to ' . ($ticketingUser->alias_name ?: $ticketingUser->name) . '. Notification email sent.');
    }

    /**
     * Approve payment status.
     */
    public function approvePayment(Request $request, Booking $booking)
    {
        $request->validate([
            'payment_status' => 'required|in:received,refund',
        ]);

        DB::transaction(function () use ($request, $booking) {
            $booking->update([
                'payment_status' => $request->input('payment_status'),
            ]);

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Payment approved by Manager. Status updated to: " . strtoupper($request->input('payment_status')) . ".",
                'type' => 'admin_remark',
            ]);
        });

        return redirect()->back()->with('success', 'Payment status updated successfully.');
    }

    /**
     * Preview the PDF e-ticket.
     */
    public function previewETicket(Booking $booking)
    {
        $booking->load(['passengers', 'bookingFlights', 'flightSegments', 'merchantProfile']);
        $pdf = Pdf::loadView('pdf.e-ticket', compact('booking'))
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true);
        return $pdf->stream('e-ticket-' . $booking->booking_id . '.pdf');
    }

    /**
     * Preview the E-Ticket email with interactive editing options.
     */
    public function previewETicketEmail(Booking $booking)
    {
        $booking->load(['passengers', 'bookingFlights', 'flightSegments', 'merchantProfile']);
        return view('manager.tickets.eticket_preview', compact('booking'));
    }

    /**
     * Update passenger ticket numbers, seat numbers, and trip type from ticketing screen.
     */
    public function updateTicketDetails(Request $request, Booking $booking)
    {
        $request->validate([
            'trip_type' => 'nullable|in:one_way,round_trip,multi_city',
            'passengers' => 'nullable|array',
            'passengers.*.title' => 'nullable|string|max:50',
            'passengers.*.first_name' => 'nullable|string|max:255',
            'passengers.*.middle_name' => 'nullable|string|max:255',
            'passengers.*.last_name' => 'nullable|string|max:255',
            'passengers.*.ticket_number' => 'nullable|string|max:255',
            'passengers.*.seat_number' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $booking) {
            if ($request->has('trip_type')) {
                $booking->update(['trip_type' => $request->input('trip_type')]);
            }

            if ($request->has('passengers')) {
                foreach ($request->input('passengers') as $paxId => $paxData) {
                    $passenger = $booking->passengers()->find($paxId);
                    if ($passenger) {
                        $passenger->update([
                            'title' => $paxData['title'] ?? $passenger->title,
                            'first_name' => $paxData['first_name'] ?? $passenger->first_name,
                            'middle_name' => array_key_exists('middle_name', $paxData) ? $paxData['middle_name'] : $passenger->middle_name,
                            'last_name' => $paxData['last_name'] ?? $passenger->last_name,
                            'ticket_number' => array_key_exists('ticket_number', $paxData) ? $paxData['ticket_number'] : $passenger->ticket_number,
                            'seat_number' => array_key_exists('seat_number', $paxData) ? $paxData['seat_number'] : $passenger->seat_number,
                        ]);
                    }
                }
            }

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Updated passenger names, ticket/seat numbers, and trip type.",
                'type' => 'admin_remark',
            ]);
        });

        return redirect()->back()->with('success', 'Passenger names, ticket numbers, seat details, and trip type updated successfully.');
    }

    /**
     * Send e-ticket PDF to customer.
     */
    public function sendETicket(Request $request, Booking $booking)
    {
        $request->validate([
            'email_address' => 'nullable|email',
            'subject' => 'nullable|string|max:255',
            'support_phone' => 'nullable|string|max:255',
            'custom_note' => 'nullable|string',
            'booking_status' => 'required|in:ticketed,booking_complete',
            'notes' => 'nullable|string',
        ]);

        $merchant = $booking->merchantProfile ?: \App\Models\Merchant::where('name', $booking->merchant)->first();
        if (!$merchant) {
            return redirect()->back()->withErrors(['error' => 'Cannot send E-Ticket: No merchant profile found for name "' . ($booking->merchant ?: 'N/A') . '".']);
        }
        if (empty($booking->merchant_id)) {
            $booking->update(['merchant_id' => $merchant->id]);
        }

        try {
            DB::transaction(function () use ($request, $booking) {
                $booking->load(['passengers', 'bookingFlights', 'flightSegments', 'merchantProfile']);

                // Render PDF
                $pdf = Pdf::loadView('pdf.e-ticket', compact('booking'))
                    ->setOption('isRemoteEnabled', true)
                    ->setOption('isHtml5ParserEnabled', true);
                $pdfBinary = $pdf->output();

                // Send email dynamically using merchant SMTP settings
                $overrides = [
                    'email_address' => $request->input('email_address', $booking->email_address),
                    'subject' => $request->input('subject', "Your E-Ticket Travel Itinerary - Ref: #{$booking->booking_id}"),
                    'support_phone' => $request->input('support_phone', '+1-888-476-0932'),
                    'custom_note' => $request->input('custom_note'),
                    'from_email' => $request->input('from_email'),
                    'from_name' => $request->input('from_name', 'Reservation Desk'),
                ];

                $this->mailService->sendETicketEmail($booking, $pdfBinary, $overrides);

                // Update booking status
                $booking->update([
                    'booking_status' => $request->input('booking_status'),
                ]);

                // Append admin remark
                $notes = $request->input('notes') ? ' Notes: ' . $request->input('notes') : '';
                $booking->bookingRemarks()->create([
                    'user_id' => Auth::id(),
                    'remark' => "E-Ticket emailed to " . ($overrides['email_address']) . ". Status updated to: " . strtoupper(str_replace('_', ' ', $request->input('booking_status'))) . "." . $notes,
                    'type' => 'admin_remark',
                ]);
            });

            return redirect()->route('manager.tickets.index')->with('success', 'E-Ticket generated and emailed to customer successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to send E-Ticket: ' . $e->getMessage()]);
        }
    }
}
