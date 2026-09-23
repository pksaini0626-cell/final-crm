<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminBookingController extends Controller
{
    /**
     * Display a global listing of bookings with multi-filtering.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['agent', 'merchantProfile', 'passengers', 'bookingFlights', 'flightSegments', 'ticketingUser']);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->input('date_to'));
        }

        // Agent filter
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        // Booking status filter
        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->input('booking_status'));
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Merchant filter
        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->input('merchant_id'));
        }

        // Service provided filter
        if ($request->filled('service_provided')) {
            $query->where('service_provided', $request->input('service_provided'));
        }

        // Dispute type filter
        if ($request->filled('dispute_type')) {
            $query->where('dispute_type', $request->input('dispute_type'));
        }

        // General search query
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                  ->orWhere('airline_pnr', 'like', "%{$search}%")
                  ->orWhere('gk_pnr', 'like', "%{$search}%")
                  ->orWhere('card_holder_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%")
                  ->orWhere('calling_number', 'like', "%{$search}%");
            });
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        // Data for dropdown filters
        $agents = User::orderBy('alias_name')->get();
        $merchants = Merchant::orderBy('name')->get();
        $ticketingAgents = User::where('role', 'ticketing')->where('is_active', true)->orderBy('alias_name')->get();

        // Pending Customer Authorization Emails (email_auth_sent)
        $pendingAuthBookings = Booking::where('booking_status', 'email_auth_sent')
            ->with(['agent', 'passengers', 'bookingFlights'])
            ->latest()
            ->get();

        return view('admin.bookings.index', compact('bookings', 'agents', 'merchants', 'pendingAuthBookings', 'ticketingAgents'));
    }

    /**
     * Show form to edit any booking parameter as Admin.
     */
    public function edit(Booking $booking)
    {
        $booking->load(['passengers', 'bookingFlights', 'bookingRemarks.user']);
        $agents = User::orderBy('alias_name')->get();
        $merchants = Merchant::orderBy('name')->get();

        return view('admin.bookings.edit', compact('booking', 'agents', 'merchants'));
    }

    /**
     * Update any booking attribute.
     */
    public function update(Request $request, Booking $booking)
    {
        if ($request->has('company_card_used')) {
            $request->merge([
                'company_card_used' => filter_var($request->company_card_used, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'merchant_id' => 'nullable|exists:merchants,id',
            'booking_date' => 'required|date',
            'call_type' => 'required|in:meta,ppc,other',
            'vertical' => 'required|string',
            'trip_type' => 'nullable|in:one_way,round_trip,multi_city',
            'service_provided' => 'required|string',
            'booking_portal' => 'required|string',
            'currency' => 'required|string|size:3',
            'total_amount' => 'required|numeric|min:0',
            'paid_to_airline' => 'required|numeric|min:0',
            'total_mco' => 'required|numeric|min:0',
            'company_card_used' => 'nullable|boolean',
            'company_card_amount' => 'nullable|numeric|min:0',
            'booking_status' => 'required|string',
            'payment_status' => 'required|string',
            'case_status' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'card_type' => 'nullable|string',
            'card_last_4' => 'nullable|string',
            'card_expiration' => 'nullable|string',
            'email_address' => 'required|email',
            'calling_number' => 'nullable|string',
            'billing_phone' => 'nullable|string',
            'billing_address' => 'nullable|string|max:1000',
            'airline_name' => 'nullable|string',
            'airline_pnr' => 'nullable|string',
            'gk_pnr' => 'nullable|string',
            'payment_info' => 'nullable|string',
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'nullable|integer',
            'passengers.*.title' => 'nullable|string|max:50',
            'passengers.*.first_name' => 'nullable|string|max:255',
            'passengers.*.middle_name' => 'nullable|string|max:255',
            'passengers.*.last_name' => 'nullable|string|max:255',
            'passengers.*.gender' => 'nullable|in:M,F,O',
            'passengers.*.dob' => 'nullable|string',
            'passengers.*.ticket_number' => 'nullable|string|max:255',
            'passengers.*.seat_number' => 'nullable|string|max:255',
            'flights' => 'nullable|array',
            'flights.*.operating_carrier' => 'nullable|string|max:255',
            'flights.*.flight_number' => 'nullable|string|max:255',
            'flights.*.origin_airport' => 'nullable|string|max:255',
            'flights.*.destination_airport' => 'nullable|string|max:255',
            'flights.*.departure_time' => 'nullable|string',
            'flights.*.arrival_time' => 'nullable|string',
            'flights.*.booking_class' => 'nullable|string|max:10',
            'flights.*.status' => 'nullable|string|max:255',
        ]);

        $validated['company_card_used'] = $request->boolean('company_card_used');
        $validated['company_card_amount'] = $validated['company_card_used'] ? (float) ($validated['company_card_amount'] ?? 0) : 0.00;

        DB::transaction(function () use ($request, $validated, $booking) {
            $booking->update($validated);

            if ($request->has('passengers')) {
                foreach ($request->input('passengers') as $paxData) {
                    if (!empty($paxData['id'])) {
                        $passenger = $booking->passengers()->find($paxData['id']);
                        if ($passenger) {
                            $passenger->update([
                                'title' => $paxData['title'] ?? $passenger->title,
                                'first_name' => $paxData['first_name'] ?? $passenger->first_name,
                                'middle_name' => array_key_exists('middle_name', $paxData) ? $paxData['middle_name'] : $passenger->middle_name,
                                'last_name' => $paxData['last_name'] ?? $passenger->last_name,
                                'gender' => $paxData['gender'] ?? $passenger->gender,
                                'dob' => !empty($paxData['dob']) ? $paxData['dob'] : $passenger->dob,
                                'ticket_number' => array_key_exists('ticket_number', $paxData) ? $paxData['ticket_number'] : $passenger->ticket_number,
                                'seat_number' => array_key_exists('seat_number', $paxData) ? $paxData['seat_number'] : $passenger->seat_number,
                            ]);
                        }
                    }
                }
            }

            if ($request->has('flights')) {
                $booking->bookingFlights()->delete();
                $booking->flightSegments()->delete();
                foreach ($request->input('flights') as $idx => $flightData) {
                    if (!empty($flightData['flight_number']) || !empty($flightData['origin_airport']) || !empty($flightData['destination_airport'])) {
                        $segmentData = array_merge($flightData, [
                            'segment_number' => $idx + 1,
                        ]);
                        $booking->bookingFlights()->create($flightData);
                        $booking->flightSegments()->create($segmentData);
                    }
                }
            }

            $roleLabel = match(Auth::user()->role) {
                'ticketing' => 'Ticketing Agent',
                'manager' => 'Manager',
                default => 'Administrator'
            };

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Booking updated by {$roleLabel}.",
                'type' => 'admin_remark',
            ]);
        });

        if (in_array(Auth::user()->role, ['ticketing', 'agent'])) {
            return redirect()->route('manager.tickets.index')->with('success', 'Booking updated successfully.');
        }

        return redirect()->route('admin.bookings.index')->with('success', 'Booking updated successfully.');
    }

    /**
     * Delete booking permanently.
     */
    public function destroy(Booking $booking)
    {
        $ref = $booking->booking_id;
        $booking->delete(); // Cascade deletes passengers, flights, remarks

        return redirect()->route('admin.bookings.index')->with('success', "Booking #{$ref} deleted permanently.");
    }

    /**
     * Add admin remark.
     */
    public function addAdminRemark(Request $request, Booking $booking)
    {
        $request->validate([
            'remark' => 'required|string|max:1000',
        ]);

        $booking->bookingRemarks()->create([
            'user_id' => Auth::id(),
            'remark' => $request->input('remark'),
            'type' => 'admin_remark',
        ]);

        return redirect()->back()->with('success', 'Admin remark logged.');
    }

    /**
     * Update case status (rdr, retrieval, chargeback, refund, void).
     */
    public function updateCaseStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'case_status' => 'required|in:rdr,retrieval,chargeback,refund,void',
            'update_booking_status_to_void' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $booking) {
            $data = [
                'case_status' => $request->input('case_status'),
            ];

            if ($request->has('update_booking_status_to_void')) {
                $data['booking_status'] = 'void';
            }

            $booking->update($data);

            $statusText = strtoupper($request->input('case_status'));
            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Case status updated to {$statusText} by Administrator.",
                'type' => 'admin_remark',
            ]);
        });

        return redirect()->back()->with('success', 'Case status updated successfully.');
    }
}
