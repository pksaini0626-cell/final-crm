<?php

namespace App\Http\Controllers\Chargeback;

use App\Http\Controllers\Controller;
use App\Models\ChargebackControl;
use App\Models\ChargebackPortal;
use App\Models\Booking;
use App\Mail\ChargebackAlertAgentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChargebackController extends Controller
{
    /**
     * Enforce strict role access: ONLY the Chargeback Team can access.
     */
    protected function authorizeChargebackRole(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'chargeback') {
            abort(403, 'Access Denied: The Chargeback Control Panel is exclusively managed by the Chargeback Team.');
        }
    }

    /**
     * Display Chargeback Control dashboard with filters, search, sorting, and pagination.
     */
    public function index(Request $request)
    {
        $this->authorizeChargebackRole();

        $query = ChargebackControl::with(['booking.passengers', 'booking.agent', 'creator']);

        // 1. Text Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('booking_reference', 'like', "%{$search}%")
                  ->orWhere('pnr', 'like', "%{$search}%")
                  ->orWhere('card_no', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('portal', 'like', "%{$search}%")
                  ->orWhere('reason_code', 'like', "%{$search}%");
            });
        }

        // 2. Date Range Filter
        $dateField = $request->input('date_field', 'received_date');
        if (!in_array($dateField, ['received_date', 'booking_date', 'deadline_date', 'action_taken_date', 'created_at'])) {
            $dateField = 'received_date';
        }

        if ($startDate = $request->input('date_from')) {
            $query->whereDate($dateField, '>=', $startDate);
        }
        if ($endDate = $request->input('date_to')) {
            $query->whereDate($dateField, '<=', $endDate);
        }

        // 3. Dropdown Filters
        if ($portal = $request->input('portal')) {
            $query->where('portal', $portal);
        }
        if ($cbkStatus = $request->input('cbk_status')) {
            $query->where('cbk_status', $cbkStatus);
        }
        if ($caseType = $request->input('case_type')) {
            $query->where('case_type', $caseType);
        }
        if ($disputeType = $request->input('dispute_type')) {
            $query->where('dispute_type', $disputeType);
        }
        if ($currentStatus = $request->input('current_status')) {
            $query->where('current_status', $currentStatus);
        }

        // Clone for KPI summary calculation before pagination
        $statsQuery = clone $query;
        $totalCases = $statsQuery->count();
        $totalDisputed = $statsQuery->sum('disputed_amount');
        $wonCases = (clone $query)->where('current_status', 'Won')->count();
        $lostCases = (clone $query)->where('current_status', 'Lost')->count();
        $pendingCases = (clone $query)->whereIn('current_status', ['Proceed with chargeback', 'Chargeback received'])->count();

        // 4. Sorting
        $sortBy = $request->input('sort_by', 'received_date');
        $allowedSorts = [
            'received_date', 'booking_date', 'deadline_date', 'action_taken_date',
            'disputed_amount', 'case_number', 'dispute_type', 'current_status', 'created_at'
        ];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'received_date';
        }
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // 5. Pagination
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }
        $chargebacks = $query->paginate($perPage)->withQueryString();

        // Load available portals for filter dropdown
        $portals = ChargebackPortal::orderBy('name')->get();

        return view('chargeback.index', compact(
            'chargebacks',
            'portals',
            'totalCases',
            'totalDisputed',
            'wonCases',
            'lostCases',
            'pendingCases',
            'sortBy',
            'sortDir'
        ));
    }

    /**
     * Show form to create a new chargeback record.
     */
    public function create()
    {
        $this->authorizeChargebackRole();

        $portals = ChargebackPortal::orderBy('name')->get();
        return view('chargeback.create', compact('portals'));
    }

    /**
     * Store newly created chargeback record.
     */
    public function store(Request $request)
    {
        $this->authorizeChargebackRole();

        $validated = $request->validate([
            'portal' => 'required|string|max:255',
            'case_number' => 'required|string|max:255',
            'case_type' => 'required|in:new,old',
            'dispute_type' => 'required|string|max:255',
            'received_date' => 'required|date',
            'received_month' => 'required|string|max:20',
            'booking_date' => 'nullable|date',
            'booking_month' => 'nullable|string|max:20',
            'deadline_date' => 'nullable|date',
            'action_taken_date' => 'nullable|date',
            'cbk_status' => 'nullable|string|max:255',
            'current_status' => 'required|string|max:255',
            'pnr' => 'nullable|string|max:50',
            'agent_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'total_booking_amount' => 'nullable|numeric|min:0',
            'disputed_amount' => 'required|numeric|min:0',
            'cc_brand' => 'nullable|string|max:50',
            'card_no' => 'nullable|string|max:10',
            'reason_code' => 'nullable|string|max:100',
            'reason_description' => 'nullable|string',
            'vertical' => 'nullable|string|max:100',
            'service_provided' => 'nullable|string|max:255',
            'shift_time' => 'nullable|string|max:20',
            'shift_month' => 'nullable|date',
            'statement_month' => 'nullable|date',
            'sds' => 'nullable|integer',
            'booking_id' => 'nullable|exists:bookings,id',
            'booking_reference' => 'nullable|string|max:20',
            'initial_remark' => 'nullable|string',
            'update_booking_status' => 'nullable|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['currency'] = !empty($validated['currency']) ? $validated['currency'] : 'USD';
        $validated['vertical'] = !empty($validated['vertical']) ? $validated['vertical'] : 'Flight';
        $validated['sds'] = isset($validated['sds']) ? (int) $validated['sds'] : 1;
        $validated['dispute_type'] = strtoupper($validated['dispute_type']);
        if (empty($validated['cbk_status'])) {
            $validated['cbk_status'] = $validated['current_status'];
        }

        // Process Image Attachments (Images Only)
        $uploadedImages = $this->handleImageUploads($request);
        if (!empty($uploadedImages)) {
            $validated['attachments'] = $uploadedImages;
        }

        // If booking_id is provided but booking_reference is empty, retrieve booking_id string
        if (!empty($validated['booking_id']) && empty($validated['booking_reference'])) {
            $bookingObj = Booking::find($validated['booking_id']);
            if ($bookingObj) {
                $validated['booking_reference'] = $bookingObj->booking_id;
            }
        }

        $oldDisputeType = null;
        $oldStatus = null;
        $booking = null;

        if (!empty($validated['booking_id'])) {
            $booking = Booking::with('agent')->find($validated['booking_id']);
            if ($booking) {
                $oldDisputeType = $booking->dispute_type;
                $oldStatus = $booking->booking_status;
            }
        }

        DB::transaction(function () use ($validated, $request, &$chargeback, $booking) {
            $chargeback = ChargebackControl::create($validated);

            // If user checked "Update Booking Status to Chargeback" or if linked booking exists
            if ($booking) {
                $booking->dispute_type = $validated['dispute_type'];
                if ($request->boolean('update_booking_status')) {
                    $booking->booking_status = 'chargeback';
                    $booking->save();

                    $booking->bookingRemarks()->create([
                        'user_id' => Auth::id(),
                        'remark' => "Status changed to CHARGEBACK & Dispute Type set to {$validated['dispute_type']} by Chargeback Team. Case #" . $chargeback->case_number . " | Disputed: {$chargeback->currency} {$chargeback->disputed_amount}",
                        'type' => 'admin_remark',
                    ]);
                } else {
                    $booking->save();
                }

                if (!empty($validated['initial_remark'])) {
                    $booking->bookingRemarks()->create([
                        'user_id' => Auth::id(),
                        'remark' => "Chargeback Note [Case #{$chargeback->case_number}]: " . trim($validated['initial_remark']),
                        'type' => 'admin_remark',
                    ]);
                }
            }
        });

        // Automated Email Alert to Agent who created the booking
        if ($booking) {
            $this->sendAgentAlertEmail(
                booking: $booking,
                oldDisputeType: $oldDisputeType,
                newDisputeType: $validated['dispute_type'],
                oldStatus: $oldStatus,
                newStatus: $booking->booking_status,
                note: $validated['initial_remark'] ?? null,
                chargeback: $chargeback
            );
        }

        return redirect()->route('chargeback.index')->with('success', "Chargeback Case #{$chargeback->case_number} created successfully.");
    }

    /**
     * Show chargeback detail view with linked booking, passenger info, and remarks timeline.
     */
    public function show(ChargebackControl $chargeback)
    {
        $this->authorizeChargebackRole();

        $chargeback->load([
            'booking.passengers',
            'booking.bookingRemarks.user',
            'booking.agent',
            'booking.ticketingUser',
            'booking.bookingFlights',
            'creator'
        ]);

        return view('chargeback.show', compact('chargeback'));
    }

    /**
     * Show form to edit existing chargeback record.
     */
    public function edit(ChargebackControl $chargeback)
    {
        $this->authorizeChargebackRole();

        $portals = ChargebackPortal::orderBy('name')->get();
        $chargeback->load('booking');

        return view('chargeback.edit', compact('chargeback', 'portals'));
    }

    /**
     * Update existing chargeback record.
     */
    public function update(Request $request, ChargebackControl $chargeback)
    {
        $this->authorizeChargebackRole();

        $validated = $request->validate([
            'portal' => 'required|string|max:255',
            'case_number' => 'required|string|max:255',
            'case_type' => 'required|in:new,old',
            'dispute_type' => 'required|string|max:255',
            'received_date' => 'required|date',
            'received_month' => 'required|string|max:20',
            'booking_date' => 'nullable|date',
            'booking_month' => 'nullable|string|max:20',
            'deadline_date' => 'nullable|date',
            'action_taken_date' => 'nullable|date',
            'cbk_status' => 'nullable|string|max:255',
            'current_status' => 'required|string|max:255',
            'pnr' => 'nullable|string|max:50',
            'agent_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'total_booking_amount' => 'nullable|numeric|min:0',
            'disputed_amount' => 'required|numeric|min:0',
            'cc_brand' => 'nullable|string|max:50',
            'card_no' => 'nullable|string|max:10',
            'reason_code' => 'nullable|string|max:100',
            'reason_description' => 'nullable|string',
            'vertical' => 'nullable|string|max:100',
            'service_provided' => 'nullable|string|max:255',
            'shift_time' => 'nullable|string|max:20',
            'shift_month' => 'nullable|date',
            'statement_month' => 'nullable|date',
            'sds' => 'nullable|integer',
            'new_remark' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $validated['sds'] = isset($validated['sds']) ? (int) $validated['sds'] : 1;
        $validated['dispute_type'] = strtoupper($validated['dispute_type']);
        if (empty($validated['cbk_status'])) {
            $validated['cbk_status'] = $validated['current_status'];
        }

        $oldDisputeType = $chargeback->dispute_type;
        $oldCurrentStatus = $chargeback->current_status;

        // Process Additional Image Attachments
        $newImages = $this->handleImageUploads($request);
        if (!empty($newImages)) {
            $existingImages = is_array($chargeback->attachments) ? $chargeback->attachments : [];
            $validated['attachments'] = array_merge($existingImages, $newImages);
        }

        DB::transaction(function () use ($chargeback, $validated) {
            $chargeback->update($validated);

            if ($chargeback->booking) {
                $chargeback->booking->dispute_type = $validated['dispute_type'];
                $chargeback->booking->save();
            }

            if (!empty($validated['new_remark']) && $chargeback->booking) {
                $chargeback->booking->bookingRemarks()->create([
                    'user_id' => Auth::id(),
                    'remark' => "Chargeback Update Note [Case #{$chargeback->case_number}]: " . trim($validated['new_remark']),
                    'type' => 'admin_remark',
                ]);
            }
        });

        // Automated Alert Email on Dispute Type or Current Status Change
        $disputeTypeChanged = ($oldDisputeType !== $validated['dispute_type']);
        $statusChanged = ($oldCurrentStatus !== $validated['current_status']);

        if (($disputeTypeChanged || $statusChanged) && $chargeback->booking) {
            $this->sendAgentAlertEmail(
                booking: $chargeback->booking,
                oldDisputeType: $oldDisputeType,
                newDisputeType: $validated['dispute_type'],
                oldStatus: $oldCurrentStatus,
                newStatus: $validated['current_status'],
                note: $validated['new_remark'] ?? null,
                chargeback: $chargeback
            );
        }

        return redirect()->route('chargeback.index')->with('success', "Chargeback Case #{$chargeback->case_number} updated successfully.");
    }

    /**
     * Delete chargeback record.
     */
    public function destroy(ChargebackControl $chargeback)
    {
        $this->authorizeChargebackRole();

        $caseNo = $chargeback->case_number;
        $chargeback->delete();

        return redirect()->route('chargeback.index')->with('success', "Chargeback Case #{$caseNo} deleted successfully.");
    }

    /**
     * AJAX endpoint to lookup booking by Booking ID OR Airline PNR OR GK PNR.
     * Returns pre-filled booking details, passengers, card info, and remarks.
     */
    public function lookupBooking(Request $request)
    {
        $this->authorizeChargebackRole();

        $query = trim($request->input('query', ''));
        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Please provide Booking ID or Airline PNR.'], 422);
        }

        $booking = Booking::with(['passengers', 'bookingRemarks.user', 'agent', 'ticketingUser'])
            ->where(function ($q) use ($query) {
                $q->where('booking_id', $query)
                  ->orWhere('airline_pnr', $query)
                  ->orWhere('gk_pnr', $query);
            })
            ->latest()
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => "No booking found matching '{$query}'."
            ], 404);
        }

        $agentName = $booking->agent ? ($booking->agent->alias_name ?: $booking->agent->name) : 'N/A';
        $bookingMonth = $booking->booking_date ? $booking->booking_date->format('Y-m') : date('Y-m');

        $passengers = $booking->passengers->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => trim("{$p->first_name} {$p->middle_name} {$p->last_name}"),
                'gender' => $p->gender ?: 'N/A',
                'dob' => $p->dob ? $p->dob->format('Y-m-d') : 'N/A',
                'ticket_number' => $p->ticket_number ?: 'N/A',
                'seat_number' => $p->seat_number ?: 'N/A',
            ];
        });

        $remarks = $booking->bookingRemarks->map(function ($r) {
            return [
                'id' => $r->id,
                'remark' => $r->remark,
                'author' => $r->user ? ($r->user->alias_name ?: $r->user->name) : 'System',
                'created_at' => $r->created_at ? $r->created_at->format('M d, Y h:i A') : '',
            ];
        });

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'booking_date' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                'booking_month' => $bookingMonth,
                'pnr' => $booking->airline_pnr ?: ($booking->gk_pnr ?: $booking->booking_id),
                'agent_name' => $agentName,
                'currency' => $booking->currency ?: 'USD',
                'total_amount' => (float) $booking->total_amount,
                'cc_brand' => $booking->card_type ?: 'Visa',
                'card_no' => $booking->card_last_4 ?: '',
                'vertical' => $booking->vertical ?: 'Flight',
                'service_provided' => $booking->service_provided ?: 'Flight Booking',
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'passengers' => $passengers,
                'remarks' => $remarks,
            ]
        ]);
    }

    /**
     * AJAX endpoint to add a new portal dynamically.
     */
    public function addPortal(Request $request)
    {
        $this->authorizeChargebackRole();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:chargeback_portals,name',
        ]);

        $portal = ChargebackPortal::create([
            'name' => trim($validated['name']),
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'portal' => $portal,
            'message' => "Portal '{$portal->name}' added successfully."
        ]);
    }

    /**
     * Add a remark to a booking directly from the Chargeback Control Panel.
     */
    public function addRemark(Request $request, ChargebackControl $chargeback)
    {
        $this->authorizeChargebackRole();

        $validated = $request->validate([
            'remark' => 'required|string|max:2000',
        ]);

        if ($chargeback->booking) {
            $chargeback->booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => "Chargeback Remark [Case #{$chargeback->case_number}]: " . trim($validated['remark']),
                'type' => 'admin_remark',
            ]);

            return redirect()->back()->with('success', 'Remark added to booking successfully.');
        }

        return redirect()->back()->with('error', 'No booking is linked to this chargeback record.');
    }

    /**
     * Export all filtered chargeback records to CSV.
     */
    public function exportCsv(Request $request)
    {
        $this->authorizeChargebackRole();

        $query = ChargebackControl::with(['booking', 'creator']);

        // Apply same filters as index
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('booking_reference', 'like', "%{$search}%")
                  ->orWhere('pnr', 'like', "%{$search}%")
                  ->orWhere('card_no', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('portal', 'like', "%{$search}%")
                  ->orWhere('reason_code', 'like', "%{$search}%");
            });
        }

        $dateField = $request->input('date_field', 'received_date');
        if (!in_array($dateField, ['received_date', 'booking_date', 'deadline_date', 'action_taken_date', 'created_at'])) {
            $dateField = 'received_date';
        }

        if ($startDate = $request->input('date_from')) {
            $query->whereDate($dateField, '>=', $startDate);
        }
        if ($endDate = $request->input('date_to')) {
            $query->whereDate($dateField, '<=', $endDate);
        }

        if ($portal = $request->input('portal')) {
            $query->where('portal', $portal);
        }
        if ($cbkStatus = $request->input('cbk_status')) {
            $query->where('cbk_status', $cbkStatus);
        }
        if ($caseType = $request->input('case_type')) {
            $query->where('case_type', $caseType);
        }
        if ($disputeType = $request->input('dispute_type')) {
            $query->where('dispute_type', $disputeType);
        }
        if ($currentStatus = $request->input('current_status')) {
            $query->where('current_status', $currentStatus);
        }

        $records = $query->orderBy('received_date', 'desc')->get();

        $filename = 'chargeback_records_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // CSV Header Row
            fputcsv($handle, [
                'Case Number',
                'Booking ID',
                'PNR',
                'Portal',
                'Received Date',
                'Received Month',
                'Booking Date',
                'Booking Month',
                'Deadline Date',
                'Action Taken Date',
                'CBK Status',
                'Case Type',
                'Dispute Type',
                'Current Status',
                'Agent Name',
                'Currency',
                'Total Booking Amount',
                'Disputed Amount',
                'CC Brand',
                'Card No (Last 4)',
                'Reason Code',
                'Reason Description',
                'Vertical',
                'Service Provided',
                'Shift Time',
                'Shift Month',
                'Statement Month',
                'SDS',
                'Created By',
                'Created At'
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->case_number,
                    $row->booking_reference ?: ($row->booking ? $row->booking->booking_id : ''),
                    $row->pnr,
                    $row->portal,
                    $row->received_date ? $row->received_date->format('Y-m-d') : '',
                    $row->received_month,
                    $row->booking_date ? $row->booking_date->format('Y-m-d') : '',
                    $row->booking_month,
                    $row->deadline_date ? $row->deadline_date->format('Y-m-d') : '',
                    $row->action_taken_date ? $row->action_taken_date->format('Y-m-d') : '',
                    $row->cbk_status,
                    $row->case_type,
                    $row->dispute_type,
                    $row->current_status,
                    $row->agent_name,
                    $row->currency,
                    $row->total_booking_amount,
                    $row->disputed_amount,
                    $row->cc_brand,
                    $row->card_no,
                    $row->reason_code,
                    $row->reason_description,
                    $row->vertical,
                    $row->service_provided,
                    $row->shift_time,
                    $row->shift_month ? $row->shift_month->format('Y-m-d') : '',
                    $row->statement_month ? $row->statement_month->format('Y-m-d') : '',
                    $row->sds,
                    $row->creator ? ($row->creator->alias_name ?: $row->creator->name) : 'System',
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Handle single or multiple image uploads strictly for images (PNG, JPG, WEBP).
     */
    protected function handleImageUploads(Request $request): array
    {
        $uploaded = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                        continue;
                    }
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $path = $file->storeAs('chargeback_attachments', $filename, 'public');
                    $uploaded[] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getClientMimeType(),
                        'url' => asset('storage/' . $path),
                    ];
                }
            }
        }
        return $uploaded;
    }

    /**
     * Send automated dispute/status change alert email to the agent who created the booking.
     */
    protected function sendAgentAlertEmail(
        Booking $booking,
        ?string $oldDisputeType = null,
        ?string $newDisputeType = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $note = null,
        ?ChargebackControl $chargeback = null
    ): void {
        $booking->loadMissing(['agent', 'passengers', 'flightSegments', 'bookingFlights']);
        $agent = $booking->agent;
        $recipientEmail = $agent?->email;

        if (!$recipientEmail) {
            return;
        }

        try {
            Mail::to($recipientEmail)->send(new ChargebackAlertAgentMail(
                booking: $booking,
                oldDisputeType: $oldDisputeType,
                newDisputeType: $newDisputeType,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                changedBy: Auth::user(),
                note: $note,
                chargeback: $chargeback
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to send ChargebackAlertAgentMail for Booking #{$booking->booking_id} to {$recipientEmail}: " . $e->getMessage());
        }
    }
}
