<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Display a listing of the agent's bookings.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Booking::query();

        // Apply Search Filter (booking_id, airline_pnr, gk_pnr, email_address, passenger name)
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search, $user) {
                if ($user && ($user->hasAnyRole(['admin', 'manager']) || in_array($user->role, ['admin', 'manager']))) {
                    $q->where('booking_id', 'like', "%{$search}%")
                      ->orWhere('airline_pnr', 'like', "%{$search}%")
                      ->orWhere('gk_pnr', 'like', "%{$search}%")
                      ->orWhere('email_address', 'like', "%{$search}%")
                      ->orWhereHas('passengers', function ($qp) use ($search) {
                          $qp->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                      });
                } else {
                    // Own bookings matching search
                    $q->where(function ($own) use ($search, $user) {
                        $own->where('agent_id', $user->id)
                            ->where(function ($sub) use ($search) {
                                $sub->where('booking_id', 'like', "%{$search}%")
                                    ->orWhere('airline_pnr', 'like', "%{$search}%")
                                    ->orWhere('gk_pnr', 'like', "%{$search}%")
                                    ->orWhere('email_address', 'like', "%{$search}%")
                                    ->orWhereHas('passengers', function ($qp) use ($search) {
                                        $qp->where('first_name', 'like', "%{$search}%")
                                           ->orWhere('last_name', 'like', "%{$search}%");
                                    });
                            });
                    })
                    // OR any booking in system matching airline_pnr or gk_pnr
                    ->orWhere(function ($pnrQ) use ($search) {
                        $pnrQ->where(function ($pnrSub) use ($search) {
                            $pnrSub->where('airline_pnr', 'like', "%{$search}%")
                                   ->orWhere('gk_pnr', 'like', "%{$search}%");
                        })
                        ->where(function ($notNull) {
                            $notNull->where(function($a) {
                                $a->whereNotNull('airline_pnr')->where('airline_pnr', '!=', '');
                            })->orWhere(function($g) {
                                $g->whereNotNull('gk_pnr')->where('gk_pnr', '!=', '');
                            });
                        });
                    });
                }
            });
        } else {
            if (!$user || (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager']))) {
                $query->where('agent_id', Auth::id());
            }
        }

        // Apply Status Filter
        if ($status = $request->input('status')) {
            $query->where('booking_status', $status);
        }

        $bookings = $query->with(['passengers', 'bookingFlights', 'flightSegments', 'bookingRemarks.user', 'agent', 'ticketingUser'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $pendingAuthBookings = collect();
        if ($user && ($user->hasAnyRole(['admin', 'manager']) || in_array($user->role, ['admin', 'manager']))) {
            $pendingAuthBookings = Booking::where('booking_status', 'email_auth_sent')
                ->with(['agent', 'passengers', 'bookingFlights'])
                ->latest()
                ->get();
        }

        $ticketingAgents = \App\Models\User::where('role', 'ticketing')->where('is_active', true)->orderBy('alias_name')->get();

        return view('bookings.index', compact('bookings', 'pendingAuthBookings', 'ticketingAgents'));
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create(Request $request)
    {
        $merchants = \App\Models\Merchant::where('is_active', true)->get();
        if ($merchants->isEmpty()) {
            $merchants = \App\Models\Merchant::all();
        }

        $agents = collect();
        $user = Auth::user();
        if ($user && ($user->hasAnyRole(['admin', 'manager']) || in_array($user->role, ['admin', 'manager']))) {
            $agents = \App\Models\User::where('is_active', true)->orderBy('alias_name')->get();
            if ($agents->isEmpty()) {
                $agents = \App\Models\User::orderBy('alias_name')->get();
            }
        }

        $duplicateBooking = null;
        if ($request->filled('duplicate')) {
            $dupRef = $request->input('duplicate');
            $duplicateBooking = Booking::with(['passengers', 'bookingFlights', 'flightSegments', 'bookingCards', 'merchantProfile', 'agent'])
                ->where(function ($q) use ($dupRef) {
                    $q->where('id', $dupRef)->orWhere('booking_id', $dupRef);
                })->first();
        }

        return view('bookings.create', compact('merchants', 'agents', 'duplicateBooking'));
    }

    /**
     * Store a newly created booking in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            
            // Assign agent_id: If admin/manager provided an agent_id, use it; otherwise default to Auth::id()
            $user = Auth::user();
            if ($user && ($user->hasAnyRole(['admin', 'manager']) || in_array($user->role, ['admin', 'manager'])) && $request->filled('agent_id')) {
                $data['agent_id'] = $request->input('agent_id');
            } else {
                $data['agent_id'] = Auth::id();
            }

            // Assign merchant_id automatically from selected merchant name if empty
            if (empty($data['merchant_id']) && !empty($data['merchant'])) {
                $merchantObj = \App\Models\Merchant::where('name', $data['merchant'])->first();
                if ($merchantObj) {
                    $data['merchant_id'] = $merchantObj->id;
                }
            }

            $data['booking_status'] = 'booking_generated';

            // Create the Booking record (total_mco is saved as passed from input)
            $booking = Booking::create($data);

            // Create Passengers
            if ($request->has('passengers')) {
                foreach ($request->input('passengers') as $paxData) {
                    $booking->passengers()->create($paxData);
                }
            }

            // Create Flights & Flight Segments
            if ($request->has('flights')) {
                foreach ($request->input('flights') as $idx => $flightData) {
                    $segmentData = array_merge($flightData, [
                        'segment_number' => $idx + 1,
                    ]);
                    $booking->bookingFlights()->create($flightData);
                    $booking->flightSegments()->create($segmentData);
                }
            }

            // Create Multiple Cards
            if ($request->has('booking_cards') && is_array($request->input('booking_cards'))) {
                foreach ($request->input('booking_cards') as $cardData) {
                    if (!empty($cardData['card_last_4'])) {
                        $booking->bookingCards()->create($cardData);
                    }
                }
            }

            // Record Initial BookingRemark
            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => $request->input('initial_remark') ?: 'Booking created successfully.',
                'type' => 'agent_remark',
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully.',
                    'booking_id' => $booking->booking_id,
                    'redirect' => route('bookings.index'),
                ]);
            }

            return redirect()->route('bookings.index')
                ->with('success', 'Booking created successfully.');
        });
    }

    /**
     * Append a new remark to the booking.
     */
    public function addRemark(Request $request, Booking $booking)
    {
        abort_if($booking->agent_id !== Auth::id(), 403);

        $request->validate([
            'remark' => 'required|string',
        ]);

        $booking->bookingRemarks()->create([
            'user_id' => Auth::id(),
            'remark' => $request->input('remark'),
            'type' => 'agent_remark',
        ]);

        return redirect()->back()->with('success', 'Remark added successfully.');
    }

    /**
     * Update post-booking details (Airline PNR, Card Last 4, Payment Info, Tickets, Seats, Remarks).
     */
    public function updateTicketsAndSeats(Request $request, Booking $booking)
    {
        abort_if($booking->agent_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'airline_pnr' => 'nullable|string|max:255',
            'trip_type' => 'nullable|in:one_way,round_trip,multi_city',
            'card_last_4' => 'nullable|string|max:4',
            'billing_address' => 'nullable|string|max:1000',
            'payment_info' => 'nullable|string',
            'new_remark' => 'nullable|string',
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'required_with:passengers|exists:passengers,id',
            'passengers.*.ticket_number' => 'nullable|string|max:255',
            'passengers.*.seat_number' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $booking) {
            $updateFields = [];
            if ($request->has('airline_pnr')) {
                $updateFields['airline_pnr'] = $request->input('airline_pnr');
            }
            if ($request->has('trip_type')) {
                $updateFields['trip_type'] = $request->input('trip_type');
            }
            if ($request->has('card_last_4')) {
                $updateFields['card_last_4'] = $request->input('card_last_4');
            }
            if ($request->has('billing_address')) {
                $updateFields['billing_address'] = $request->input('billing_address');
            }
            if ($request->has('payment_info')) {
                $updateFields['payment_info'] = $request->input('payment_info');
            }

            if (!empty($updateFields)) {
                $booking->update($updateFields);
            }

            if ($request->has('passengers')) {
                foreach ($request->input('passengers') as $paxData) {
                    $passenger = $booking->passengers()->find($paxData['id']);
                    if ($passenger) {
                        $passenger->update([
                            'ticket_number' => $paxData['ticket_number'] ?? null,
                            'seat_number' => $paxData['seat_number'] ?? null,
                        ]);
                    }
                }
            }

            if ($request->filled('new_remark')) {
                $booking->bookingRemarks()->create([
                    'user_id' => Auth::id(),
                    'remark' => $request->input('new_remark'),
                    'type' => Auth::user()->hasAnyRole(['admin', 'manager']) ? 'admin_remark' : 'agent_remark',
                ]);
            }
        });

        return redirect()->back()->with('success', 'Booking details updated successfully.');
    }

    /**
     * Show the preview and customization page for Customer Authorization Email.
     */
    public function previewAuthEmail(Request $request, Booking $booking)
    {
        abort_if($booking->agent_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $booking->load(['passengers', 'bookingFlights', 'agent']);

        $agent = $booking->agent ?: Auth::user();
        $agentLanguageOption = $agent->agent_language ?? 'english';

        // Selected email language: query param 'lang' > agent preference > 'english'
        if ($request->has('lang')) {
            $selectedLanguage = $request->input('lang') === 'spanish' ? 'spanish' : 'english';
        } elseif ($agentLanguageOption === 'spanish') {
            $selectedLanguage = 'spanish';
        } else {
            $selectedLanguage = 'english';
        }

        $airlineName = $booking->airline_name ?: ($booking->airline_code ?: 'Airline');
        $pnr = $booking->airline_pnr ?: ($booking->gk_pnr ?: $booking->booking_id);

        if ($selectedLanguage === 'spanish') {
            $serviceNameEs = match($booking->service_provided) {
                'exchange' => 'Cambio de Vuelo',
                'cancellation' => 'Cancelación',
                'refund' => 'Reembolso',
                'seat_selection' => 'Selección de Asientos',
                'baggage_addition' => 'Edición de Equipaje',
                'others' => 'Servicio',
                'cancel_and_refund' => 'Cancelación y Reembolso',
                'name_correction' => 'Corrección de Nombre',
                'flight_upgrade' => 'Upgrade de Vuelo',
                'dob_correction' => 'Corrección de Fecha de Nacimiento',
                'pet_in_cabin' => 'Mascota en Cabina',
                'ancillary_refund' => 'Reembolso de Servicios Adicionales',
                default => 'Reserva de Vuelo'
            };
            $defaultSubject = "Autorización para {$airlineName} {$serviceNameEs} Confirmación #{$pnr}";
        } else {
            $serviceNameEn = match($booking->service_provided) {
                'exchange' => 'Exchange',
                'cancellation' => 'Cancellation',
                'refund' => 'Refund',
                'seat_selection' => 'Seat Selection',
                'baggage_addition' => 'Baggage Edition',
                'others' => 'Service',
                'cancel_and_refund' => 'Cancel and Refund',
                'name_correction' => 'Name Correction',
                'flight_upgrade' => 'Flight Upgrade',
                'dob_correction' => 'D.O.B Correction',
                'pet_in_cabin' => 'Pet In Cabin',
                'ancillary_refund' => 'Ancillary Refund',
                default => ucwords(str_replace('_', ' ', $booking->service_provided ?: 'Booking'))
            };
            $defaultSubject = "Authorization for {$airlineName} {$serviceNameEn} Booking Confirmation #{$pnr}";
        }

        $merchantObj = $booking->merchantProfile ?: \App\Models\Merchant::where('name', $booking->merchant)->first();
        $defaultFromEmail = $merchantObj && !empty($merchantObj->from_email) ? $merchantObj->from_email : (config('mail.from.address') ?: 'reservation@travelomile.com');
        $defaultFromName = 'Reservation Desk';
        $agentName = Auth::user()->name ?: 'Agent Desk';
        $agentExt = Auth::user()->extension ?: '187';

        return view('bookings.auth_email_preview', compact(
            'booking',
            'defaultSubject',
            'defaultFromEmail',
            'defaultFromName',
            'agentName',
            'agentExt',
            'agentLanguageOption',
            'selectedLanguage'
        ));
    }

    /**
     * Send or resend the Customer Authorization Email after agent review.
     */
    public function sendAuthEmail(Request $request, Booking $booking)
    {
        abort_if($booking->agent_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'email_address' => 'required|email',
            'subject' => 'required|string',
            'email_language' => 'required|in:english,spanish',
            'from_email' => 'nullable|email',
            'from_name' => 'nullable|string',
            'custom_note' => 'nullable|string',
            'agent_name' => 'nullable|string',
            'agent_ext' => 'nullable|string',
            'custom_html' => 'nullable|string',
        ]);

        $booking->load(['passengers', 'bookingFlights', 'agent']);

        $mailService = new \App\Services\MerchantMailService();
        $mailService->sendAuthorizationEmail($booking, [
            'email_address' => $request->input('email_address'),
            'subject' => $request->input('subject'),
            'from_email' => $request->input('from_email'),
            'from_name' => $request->input('from_name'),
            'custom_note' => $request->input('custom_note'),
            'agent_name' => $request->input('agent_name'),
            'agent_ext' => $request->input('agent_ext'),
            'email_language' => $request->input('email_language', 'english'),
            'custom_html' => $request->input('custom_html'),
        ]);

        $booking->update([
            'booking_status' => 'email_auth_sent',
        ]);

        $booking->bookingRemarks()->create([
            'user_id' => Auth::id(),
            'remark' => 'Authorization email (' . strtoupper($request->input('email_language')) . ') sent to ' . $request->input('email_address'),
            'type' => 'agent_remark',
        ]);

        return redirect()->route('bookings.index')
            ->with('success', 'Authorization email dispatched successfully to ' . $request->input('email_address'));
    }

    /**
     * Approve customer authorization response and transition status to email_auth_done.
     */
    public function approveAuth(Booking $booking)
    {
        abort_if(!Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        DB::transaction(function () use ($booking) {
            $booking->update([
                'booking_status' => 'email_auth_done',
            ]);

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => 'Customer Email Authorization approved by ' . strtoupper(Auth::user()->role) . ' (' . Auth::user()->name . '). Status updated to: EMAIL AUTH DONE.',
                'type' => 'admin_remark',
            ]);
        });

        return redirect()->back()->with('success', 'Customer authorization approved successfully for Booking #' . $booking->booking_id . '. Status updated to EMAIL AUTH DONE.');
    }
}
