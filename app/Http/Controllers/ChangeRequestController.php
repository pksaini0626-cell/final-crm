<?php

namespace App\Http\Controllers;

use App\Mail\ChangeRequestCreatedMail;
use App\Mail\ChangeRequestStatusUpdatedMail;
use App\Models\Booking;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChangeRequestController extends Controller
{
    /**
     * Display the Changes Queue Panel (/changes/requests).
     */
    public function index(Request $request)
    {
        $query = ChangeRequest::with(['booking', 'agent', 'changesAgent']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search Filter (Booking Ref, Customer Name, Request Text)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($bq) use ($search) {
                    $bq->where('booking_id', 'like', "%{$search}%")
                       ->orWhere('airline_pnr', 'like', "%{$search}%")
                       ->orWhere('card_holder_name', 'like', "%{$search}%")
                       ->orWhere('email_address', 'like', "%{$search}%");
                })
                ->orWhere('change_request_text', 'like', "%{$search}%")
                ->orWhere('agent_remark', 'like', "%{$search}%")
                ->orWhere('changes_remark', 'like', "%{$search}%");
            });
        }

        // Date Filter
        if ($request->filled('date')) {
            $query->whereDate('assigned_at', $request->input('date'));
        }

        $changeRequests = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $changesAgents = User::where('role', 'changes')
            ->orWhereHas('roles', fn($r) => $r->where('name', 'changes'))
            ->get();

        return view('changes.index', compact('changeRequests', 'changesAgents'));
    }

    /**
     * Show dedicated change request form webpage for a booking.
     */
    public function create(Booking $booking)
    {
        $booking->load(['passengers', 'bookingFlights', 'agent']);
        return view('bookings.request_change', compact('booking'));
    }

    /**
     * Store a new change request for a booking.
     */
    public function store(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'change_request_text' => 'required|string|max:5000',
            'agent_remark' => 'nullable|string|max:2000',
            'assigned_changes_user_id' => 'nullable|exists:users,id',
        ]);

        $changeRequest = ChangeRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => Auth::id(),
            'assigned_changes_user_id' => $validated['assigned_changes_user_id'] ?? null,
            'change_request_text' => $validated['change_request_text'],
            'agent_remark' => $validated['agent_remark'] ?? null,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        // Send Email Notification to Changes Team
        try {
            $changesMail = env('CHANGES_TEAM_EMAIL', 'changes@callinggenie.com');
            Mail::to($changesMail)->send(new ChangeRequestCreatedMail($changeRequest));
        } catch (\Throwable $e) {
            Log::error("Failed sending change request creation email for booking #{$booking->booking_id}: " . $e->getMessage());
        }

        return redirect()->route('bookings.index')
            ->with('success', "Change request submitted successfully for Booking #{$booking->booking_id}.");
    }

    /**
     * Update status & remarks for a change request (pending -> working -> completed).
     */
    public function updateStatus(Request $request, ChangeRequest $changeRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,working,completed,cancelled',
            'changes_remark' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $changeRequest->status;
        $newStatus = $validated['status'];

        $updateData = [
            'status' => $newStatus,
            'changes_remark' => $validated['changes_remark'] ?? $changeRequest->changes_remark,
            'assigned_changes_user_id' => Auth::id(),
        ];

        if ($newStatus === 'working' && $oldStatus !== 'working') {
            $updateData['working_at'] = now();
        }

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $updateData['completed_at'] = now();
        }

        $changeRequest->update($updateData);

        // Send Email Notification to requesting Agent if status changed to working or completed
        if (in_array($newStatus, ['working', 'completed']) && $changeRequest->agent && $changeRequest->agent->email) {
            try {
                Mail::to($changeRequest->agent->email)->send(new ChangeRequestStatusUpdatedMail($changeRequest));
            } catch (\Throwable $e) {
                Log::error("Failed sending change request status email to agent for booking #{$changeRequest->booking_id}: " . $e->getMessage());
            }
        }

        return redirect()->back()
            ->with('success', "Change request status updated to " . strtoupper($newStatus) . " successfully.");
    }
}
