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
        $query = ChangeRequest::with(['booking', 'agent', 'changesAgent', 'closedBy']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search Filter (Booking Ref, Customer Name, Request Type, Remarks)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($bq) use ($search) {
                    $bq->where('booking_id', 'like', "%{$search}%")
                       ->orWhere('airline_pnr', 'like', "%{$search}%")
                       ->orWhere('card_holder_name', 'like', "%{$search}%")
                       ->orWhere('email_address', 'like', "%{$search}%");
                })
                ->orWhere('request_type', 'like', "%{$search}%")
                ->orWhere('change_request_text', 'like', "%{$search}%")
                ->orWhere('agent_remark', 'like', "%{$search}%")
                ->orWhere('changes_remark', 'like', "%{$search}%")
                ->orWhere('final_remark', 'like', "%{$search}%")
                ->orWhere('fop', 'like', "%{$search}%");
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
            'request_type' => 'nullable|string|max:100',
            'change_request_text' => 'required|string|max:5000',
            'agent_remark' => 'nullable|string|max:2000',
            'assigned_changes_user_id' => 'nullable|exists:users,id',
            'attachments.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg,webp,gif,svg|max:10240',
        ]);

        $uploadedAttachments = [];
        $files = $request->file('attachments') ?: $request->file('initial_attachments');
        if (!empty($files)) {
            $filesList = is_array($files) ? $files : [$files];
            foreach ($filesList as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('change_requests', 'public');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $fileType = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg']) ? 'image' : 'pdf';
                    $uploadedAttachments[] = [
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type' => $fileType,
                    ];
                }
            }
        }

        $changeRequest = ChangeRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => Auth::id(),
            'assigned_changes_user_id' => $validated['assigned_changes_user_id'] ?? null,
            'request_type' => $validated['request_type'] ?? null,
            'change_request_text' => $validated['change_request_text'],
            'agent_remark' => $validated['agent_remark'] ?? null,
            'attachments' => $uploadedAttachments,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        // Record in Booking Remarks History for agent & admin visibility
        $remarkText = "[CHANGE REQUEST SUBMITTED]";
        if (!empty($validated['request_type'])) {
            $remarkText .= " Type: " . $validated['request_type'];
        }
        $remarkText .= "\n" . $validated['change_request_text'];
        if (!empty($validated['agent_remark'])) {
            $remarkText .= "\nAgent Note: " . $validated['agent_remark'];
        }

        $booking->bookingRemarks()->create([
            'user_id' => Auth::id(),
            'remark' => $remarkText,
            'type' => 'agent_remark',
            'attachments' => $uploadedAttachments,
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
     * Update status, remarks & booking status for a change request.
     */
    public function updateStatus(Request $request, ChangeRequest $changeRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:closed,denied,follow_up,assigned_to_agent,awaiting_revert_from_airline,sale_cancelled,chargeback,refunded,voided,pending,working,completed,cancelled',
            'paid_to_airline' => 'nullable|string|in:yes,no,Yes,No',
            'fop' => 'nullable|string|max:150',
            'final_remark' => 'nullable|string|max:2000',
            'changes_remark' => 'nullable|string|max:2000',
            'booking_status' => 'nullable|string|in:booking_generated,email_auth_sent,email_auth_done,ticketed,booking_complete,void',
            'case_status' => 'nullable|string|in:rdr,retrieval,chargeback,refund,void',
            'attachments.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg,webp,gif,svg|max:10240',
        ]);

        $oldStatus = $changeRequest->status;
        $newStatus = strtolower($validated['status']);

        $newUploadedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('change_requests', 'public');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $fileType = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg']) ? 'image' : 'pdf';
                    $newUploadedAttachments[] = [
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type' => $fileType,
                    ];
                }
            }
        }

        $allAttachments = array_merge($changeRequest->attachments ?: [], $newUploadedAttachments);

        $updateData = [
            'status' => $newStatus,
            'paid_to_airline' => $validated['paid_to_airline'] ?? $changeRequest->paid_to_airline,
            'fop' => $validated['fop'] ?? $changeRequest->fop,
            'final_remark' => $validated['final_remark'] ?? $changeRequest->final_remark,
            'changes_remark' => $validated['changes_remark'] ?? $changeRequest->changes_remark,
            'attachments' => $allAttachments,
            'assigned_changes_user_id' => Auth::id(),
            'closed_by_user_id' => Auth::id(),
        ];

        if ($newStatus === 'working' && $oldStatus !== 'working') {
            $updateData['working_at'] = now();
        }

        if (in_array($newStatus, ['completed', 'closed']) && !in_array($oldStatus, ['completed', 'closed'])) {
            $updateData['completed_at'] = now();
        }

        $changeRequest->update($updateData);

        // Update Booking Table Status directly if provided
        $booking = $changeRequest->booking;
        if ($booking) {
            $bookingUpdate = [];
            if (!empty($validated['booking_status'])) {
                $bookingUpdate['booking_status'] = $validated['booking_status'];
            }
            if (!empty($validated['case_status'])) {
                $bookingUpdate['case_status'] = $validated['case_status'];
            }
            if (!empty($bookingUpdate)) {
                $booking->update($bookingUpdate);
            }

            // Append remark to Booking Remarks History for agent visibility
            $changesRemarkText = "[CHANGES STATUS: " . strtoupper(str_replace('_', ' ', $newStatus)) . "]";
            if (!empty($validated['final_remark'])) {
                $changesRemarkText .= "\nFinal Remark: " . $validated['final_remark'];
            }
            if (!empty($validated['changes_remark'])) {
                $changesRemarkText .= "\nChanges Remark: " . $validated['changes_remark'];
            }
            if (!empty($validated['paid_to_airline'])) {
                $changesRemarkText .= "\nPaid to Airline: " . strtoupper($validated['paid_to_airline']);
            }
            if (!empty($validated['fop'])) {
                $changesRemarkText .= "\nFOP: " . $validated['fop'];
            }

            $booking->bookingRemarks()->create([
                'user_id' => Auth::id(),
                'remark' => $changesRemarkText,
                'type' => 'admin_remark',
                'attachments' => $newUploadedAttachments,
            ]);
        }

        // Send Email Notification to requesting Agent if status changed
        if (in_array($newStatus, ['working', 'completed', 'closed', 'denied', 'assigned_to_agent']) && $changeRequest->agent && $changeRequest->agent->email) {
            try {
                Mail::to($changeRequest->agent->email)->send(new ChangeRequestStatusUpdatedMail($changeRequest));
            } catch (\Throwable $e) {
                Log::error("Failed sending change request status email to agent for booking #{$changeRequest->booking_id}: " . $e->getMessage());
            }
        }

        return redirect()->back()
            ->with('success', "Change request status updated to " . strtoupper(str_replace('_', ' ', $newStatus)) . " successfully.");
    }
}
