<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Mail\AuthApprovedAgentNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

        $approvedAuthBookings = collect();
        if ($user && ($user->role === 'agent' || (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager'])))) {
            $approvedAuthBookings = Booking::where('agent_id', $user->id)
                ->where('booking_status', 'email_auth_done')
                ->with(['agent', 'passengers', 'bookingFlights'])
                ->latest('updated_at')
                ->take(5)
                ->get();
        }

        $ticketingAgents = \App\Models\User::where('role', 'ticketing')->where('is_active', true)->orderBy('alias_name')->get();

        return view('bookings.index', compact('bookings', 'pendingAuthBookings', 'approvedAuthBookings', 'ticketingAgents'));
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
            $data['email_auth_taken'] = $request->boolean('email_auth_taken');

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
            $initialAttachments = [];
            if ($request->hasFile('initial_attachments')) {
                foreach ($request->file('initial_attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('booking_remarks', 'public');
                        $ext = strtolower($file->getClientOriginalExtension());
                        $initialAttachments[] = [
                            'file_path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'file_type' => in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file'),
                            'file_size' => $file->getSize(),
                            'file_url' => Storage::disk('public')->url($path),
                        ];
                    }
                }
            }

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => $request->input('initial_remark') ?: 'Booking created successfully.',
                'type' => 'agent_remark',
                'attachments' => $initialAttachments,
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
        abort_if($booking->agent_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'remark' => 'nullable|string|required_without:attachments',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $attachmentData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('booking_remarks', 'public');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $attachmentData[] = [
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type' => in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file'),
                        'file_size' => $file->getSize(),
                        'file_url' => Storage::disk('public')->url($path),
                    ];
                }
            }
        }

        $booking->bookingRemarks()->create([
            'user_id' => Auth::id(),
            'remark' => $request->input('remark') ?: 'Attachment(s) added.',
            'type' => Auth::user()->hasAnyRole(['admin', 'manager']) ? 'admin_remark' : 'agent_remark',
            'attachments' => $attachmentData,
        ]);

        return redirect()->back()->with('success', 'Remark with attachment(s) saved successfully.');
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
            'new_remark_attachments' => 'nullable|array',
            'new_remark_attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'required_with:passengers|exists:passengers,id',
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

            if ($request->filled('new_remark') || $request->hasFile('new_remark_attachments')) {
                $newAttachments = [];
                if ($request->hasFile('new_remark_attachments')) {
                    foreach ($request->file('new_remark_attachments') as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store('booking_remarks', 'public');
                            $ext = strtolower($file->getClientOriginalExtension());
                            $newAttachments[] = [
                                'file_path' => $path,
                                'original_name' => $file->getClientOriginalName(),
                                'file_type' => in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file'),
                                'file_size' => $file->getSize(),
                                'file_url' => Storage::disk('public')->url($path),
                            ];
                        }
                    }
                }

                $booking->bookingRemarks()->create([
                    'user_id' => Auth::id(),
                    'remark' => $request->input('new_remark') ?: 'Updated booking details with attachment(s).',
                    'type' => Auth::user()->hasAnyRole(['admin', 'manager']) ? 'admin_remark' : 'agent_remark',
                    'attachments' => $newAttachments,
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
            'email_language' => 'nullable|string',
            'from_email' => 'nullable|email',
            'from_name' => 'nullable|string',
            'custom_note' => 'nullable|string',
            'agent_name' => 'nullable|string',
            'agent_ext' => 'nullable|string',
            'custom_html' => 'nullable|string',
        ]);

        $booking->load(['passengers', 'bookingFlights', 'agent']);

        try {
            $mailService = new \App\Services\MerchantMailService();
            $mailService->sendAuthorizationEmail($booking, [
                'email_address' => $request->input('email_address'),
                'subject' => $request->input('subject'),
                'from_email' => filter_var($request->input('from_email'), FILTER_VALIDATE_EMAIL) ? $request->input('from_email') : null,
                'from_name' => $request->input('from_name'),
                'custom_note' => $request->input('custom_note'),
                'agent_name' => $request->input('agent_name'),
                'agent_ext' => $request->input('agent_ext'),
                'email_language' => strtolower($request->input('email_language', 'english')) === 'spanish' ? 'spanish' : 'english',
                'custom_html' => $request->input('custom_html'),
            ]);

            $booking->update([
                'booking_status' => 'email_auth_sent',
                'email_auth_taken' => true,
            ]);

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => 'Authorization email (' . strtoupper($request->input('email_language', 'ENGLISH')) . ') sent to ' . $request->input('email_address'),
                'type' => 'agent_remark',
            ]);

            return redirect()->route('bookings.index')
                ->with('success', 'Authorization email dispatched successfully to ' . $request->input('email_address'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'Failed to dispatch authorization email: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve customer authorization response and transition status to email_auth_done.
     */
    public function approveAuth(Booking $booking)
    {
        abort_if(!Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $approver = Auth::user();

        DB::transaction(function () use ($booking, $approver) {
            $booking->update([
                'booking_status' => 'email_auth_done',
            ]);

            $booking->bookingRemarks()->create([
                'user_id' => $approver->id,
                'remark' => 'Customer Email Authorization approved by ' . strtoupper($approver->role) . ' (' . $approver->name . '). Status updated to: EMAIL AUTH DONE.',
                'type' => 'admin_remark',
            ]);
        });

        // Dispatch Email Notification to the agent who created the booking
        $booking->load('agent');
        if ($booking->agent && filter_var($booking->agent->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($booking->agent->email)->send(new AuthApprovedAgentNotificationMail($booking, $approver));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send auth approval email to agent: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Customer authorization approved successfully for Booking #' . $booking->booking_id . '. Status updated to EMAIL AUTH DONE & notification email sent to agent.');
    }
}
