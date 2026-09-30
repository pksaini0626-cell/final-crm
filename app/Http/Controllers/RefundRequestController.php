<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\User;
use App\Mail\RefundRequestedNotificationMail;
use App\Mail\RefundApprovedAgentNotificationMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RefundRequestController extends Controller
{
    /**
     * Display a listing of refund/void requests (Queue for Admin, Master Admin, MIS, Manager).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        $query = RefundRequest::with([
            'booking.agent',
            'booking.merchantProfile',
            'booking.passengers',
            'agent',
            'approver'
        ]);

        // Filter by Status (pending, approved, rejected, all)
        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by Request Type (void, partial_void, refund)
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->input('request_type'));
        }

        // Filter by Date Range (refund_date)
        if ($request->filled('date_from')) {
            $query->whereDate('refund_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('refund_date', '<=', $request->input('date_to'));
        }

        // Search by booking_id, airline_pnr, gk_pnr, card_holder_name, email
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($qb) use ($search) {
                    $qb->where('booking_id', 'like', "%{$search}%")
                       ->orWhere('airline_pnr', 'like', "%{$search}%")
                       ->orWhere('gk_pnr', 'like', "%{$search}%")
                       ->orWhere('card_holder_name', 'like', "%{$search}%")
                       ->orWhere('email_address', 'like', "%{$search}%")
                       ->orWhere('billing_phone', 'like', "%{$search}%");
                })
                ->orWhereHas('agent', function ($qa) use ($search) {
                    $qa->where('name', 'like', "%{$search}%")
                       ->orWhere('alias_name', 'like', "%{$search}%");
                });
            });
        }

        $refundRequests = $query->latest('created_at')->paginate(15)->withQueryString();

        // Status counts for queue badges
        $pendingCount = RefundRequest::where('status', 'pending')->count();
        $approvedCount = RefundRequest::where('status', 'approved')->count();
        $rejectedCount = RefundRequest::where('status', 'rejected')->count();
        $totalCount = RefundRequest::count();

        return view('admin.refunds.index', compact(
            'refundRequests',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'totalCount',
            'status'
        ));
    }

    /**
     * Show the form / details for creating a new refund request for a booking.
     */
    public function create(Booking $booking)
    {
        $user = Auth::user();
        abort_if(!$user, 403, 'Unauthorized.');

        // Verify that booking status is ticketed or booking_complete
        if (!in_array($booking->booking_status, ['ticketed', 'booking_complete'])) {
            return redirect()->route('bookings.index')
                ->with('error', "Refund or Void request can only be submitted for bookings with status Ticketed or Completed.");
        }

        $booking->loadMissing(['agent', 'merchantProfile', 'passengers', 'refundRequests.agent', 'refundRequests.approver']);

        return view('bookings.request_refund', compact('booking'));
    }

    /**
     * Store a newly created refund/void request in storage.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();
        abort_if(!$user, 403, 'Unauthorized.');

        // Eligibility check
        if (!in_array($booking->booking_status, ['ticketed', 'booking_complete'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund/Void requests can only be raised for Ticketed or Completed bookings.'
                ], 422);
            }
            return back()->with('error', 'Refund/Void requests can only be raised for Ticketed or Completed bookings.');
        }

        $maxRefundAmount = max(0.01, (float)$booking->total_mco);

        $validated = $request->validate([
            'request_type' => 'required|in:void,partial_void,refund',
            'refund_amount' => [
                'required',
                'numeric',
                'min:0.01',
                "max:{$maxRefundAmount}"
            ],
            'refund_date' => 'required|date',
            'reason_for_refund' => 'required|string|max:500',
            'remarks' => 'required|string|max:2000',
        ], [
            'refund_amount.max' => "Refund amount cannot exceed the charged MCO amount of {$booking->currency} " . number_format($maxRefundAmount, 2) . ".",
            'remarks.required' => 'Remarks is mandatory for submitting a refund or void request.',
        ]);

        $refundRequest = DB::transaction(function () use ($booking, $user, $validated) {
            $record = RefundRequest::create([
                'booking_id' => $booking->id,
                'agent_id' => $user->id,
                'request_type' => $validated['request_type'],
                'refund_amount' => $validated['refund_amount'],
                'currency' => $booking->currency ?: 'USD',
                'refund_date' => $validated['refund_date'],
                'reason_for_refund' => trim($validated['reason_for_refund']),
                'remarks' => trim($validated['remarks']),
                'status' => 'pending',
                'deduction_from_agent' => 'nil',
                'email_sent_to_agent_by' => 'nil',
                'receipt_sent_to_cs' => 'nil',
                'is_duplicate' => 'nil',
            ]);

            // Update booking payment_status to refund_pending
            $booking->payment_status = 'refund_pending';
            $booking->save();

            // Format type label for log
            $typeLabel = match ($validated['request_type']) {
                'void' => 'Void',
                'partial_void' => 'Partial Void',
                'refund' => 'Refund',
                default => ucfirst($validated['request_type']),
            };

            $auditText = "Refund Request ({$typeLabel}) of {$booking->currency} " . number_format($validated['refund_amount'], 2) .
                " submitted by Agent {$user->name}. Reason: {$validated['reason_for_refund']}. Remarks: {$validated['remarks']}";

            $booking->bookingRemarks()->create([
                'user_id' => $user->id,
                'remark' => $auditText,
                'type' => 'agent_remark',
            ]);

            return $record;
        });

        // Dispatch Email Notification to MIS Team, MIS Manager, Admin, and Super Admin
        try {
            $recipients = User::where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('role', ['admin', 'master_admin', 'mis', 'manager'])
                      ->orWhereHas('roles', function ($qr) {
                          $qr->whereIn('name', ['admin', 'master_admin', 'mis', 'manager']);
                      });
                })
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $envNotificationEmail = env('REFUND_NOTIFICATION_EMAIL');
            if ($envNotificationEmail) {
                $extraEmails = array_map('trim', explode(',', $envNotificationEmail));
                $recipients = array_values(array_unique(array_merge($recipients, $extraEmails)));
            }

            if (!empty($recipients)) {
                Mail::to($recipients)->send(new RefundRequestedNotificationMail($refundRequest));
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send RefundRequestedNotificationMail for Booking #{$booking->booking_id}: " . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Refund/Void request for Booking #{$booking->booking_id} submitted successfully and assigned to Admin/MIS team.",
                'refund_request' => $refundRequest
            ]);
        }

        return redirect()->route('bookings.index')
            ->with('success', "Refund/Void request for Booking #{$booking->booking_id} submitted successfully and assigned to Admin/MIS team.");
    }

    /**
     * Approve the refund/void request (Admin, Master Admin, MIS, Manager).
     */
    public function approve(Request $request, RefundRequest $refundRequest)
    {
        $user = Auth::user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        if ($refundRequest->status === 'approved') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Request has already been approved.'], 422);
            }
            return back()->with('info', 'This request has already been approved.');
        }

        $booking = $refundRequest->booking;
        $maxRefundAmount = max(0.01, (float)$booking->total_mco);

        $validated = $request->validate([
            'refund_amount' => "nullable|numeric|min:0.01|max:{$maxRefundAmount}",
            'refund_date' => 'nullable|date',
            'mis_remarks' => 'nullable|string|max:1000',
            'admin_remarks' => 'nullable|string|max:1000',
            'deduction_from_agent' => 'nullable|string|max:100',
            'reason_for_refund' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($refundRequest, $booking, $user, $validated) {
            if (!empty($validated['refund_amount'])) {
                $refundRequest->refund_amount = $validated['refund_amount'];
            }
            if (!empty($validated['refund_date'])) {
                $refundRequest->refund_date = $validated['refund_date'];
            }
            if (!empty($validated['reason_for_refund'])) {
                $refundRequest->reason_for_refund = $validated['reason_for_refund'];
            }
            if (isset($validated['mis_remarks'])) {
                $refundRequest->mis_remarks = $validated['mis_remarks'];
            }
            if (isset($validated['admin_remarks'])) {
                $refundRequest->admin_remarks = $validated['admin_remarks'];
            }
            if (isset($validated['deduction_from_agent'])) {
                $refundRequest->deduction_from_agent = $validated['deduction_from_agent'];
            }

            $refundRequest->status = 'approved';
            $refundRequest->approved_by_id = $user->id;
            $refundRequest->approved_at = now();
            $refundRequest->save();

            // Update Booking payment_status according to request_type
            $newPaymentStatus = match ($refundRequest->request_type) {
                'void' => 'void',
                'partial_void' => 'partial_void',
                'refund' => 'refund',
                default => 'refund',
            };

            $booking->payment_status = $newPaymentStatus;

            // Also update case_status if void or refund
            if (in_array($newPaymentStatus, ['void', 'refund'])) {
                $booking->case_status = $newPaymentStatus;
            }

            $booking->save();

            $roleLabel = match ($user->role) {
                'admin' => 'Administrator',
                'master_admin' => 'Master Admin',
                'mis' => 'MIS Team',
                'manager' => 'Manager',
                default => 'Staff'
            };

            $typeLabel = match ($refundRequest->request_type) {
                'void' => 'Void',
                'partial_void' => 'Partial Void',
                'refund' => 'Refund',
                default => ucfirst($refundRequest->request_type),
            };

            $misNote = !empty($refundRequest->mis_remarks) ? " | MIS Remarks: {$refundRequest->mis_remarks}" : "";
            $adminNote = !empty($refundRequest->admin_remarks) ? " | Admin Remarks: {$refundRequest->admin_remarks}" : "";

            $auditText = "{$typeLabel} Request APPROVED by {$roleLabel} ({$user->name}). Refund Amount: {$booking->currency} " .
                number_format($refundRequest->refund_amount, 2) . ". Payment status updated to: " . strtoupper(str_replace('_', ' ', $newPaymentStatus)) .
                $misNote . $adminNote;

            $booking->bookingRemarks()->create([
                'user_id' => $user->id,
                'remark' => $auditText,
                'type' => 'admin_remark',
            ]);
        });

        // Send Approval Email Directly to Agent
        try {
            $agentEmail = $refundRequest->agent?->email ?? $booking->agent?->email;
            if ($agentEmail) {
                Mail::to($agentEmail)->send(new RefundApprovedAgentNotificationMail($refundRequest, $user));
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send RefundApprovedAgentNotificationMail for Booking #{$booking->booking_id}: " . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Refund/Void request for Booking #{$booking->booking_id} has been approved successfully."
            ]);
        }

        return redirect()->back()
            ->with('success', "Refund/Void request for Booking #{$booking->booking_id} has been approved successfully.");
    }

    /**
     * Reject the refund/void request.
     */
    public function reject(Request $request, RefundRequest $refundRequest)
    {
        $user = Auth::user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $booking = $refundRequest->booking;

        DB::transaction(function () use ($refundRequest, $booking, $user, $validated) {
            $refundRequest->status = 'rejected';
            $refundRequest->admin_remarks = $validated['rejection_reason'];
            $refundRequest->approved_by_id = $user->id;
            $refundRequest->approved_at = now();
            $refundRequest->save();

            // Revert payment status back to received if it was refund_pending
            if ($booking->payment_status === 'refund_pending') {
                $booking->payment_status = 'received';
                $booking->save();
            }

            $roleLabel = match ($user->role) {
                'admin' => 'Administrator',
                'master_admin' => 'Master Admin',
                'mis' => 'MIS Team',
                'manager' => 'Manager',
                default => 'Staff'
            };

            $auditText = "Refund/Void Request REJECTED by {$roleLabel} ({$user->name}). Reason: " . trim($validated['rejection_reason']);

            $booking->bookingRemarks()->create([
                'user_id' => $user->id,
                'remark' => $auditText,
                'type' => 'admin_remark',
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Refund/Void request for Booking #{$booking->booking_id} was rejected."
            ]);
        }

        return redirect()->back()
            ->with('success', "Refund/Void request for Booking #{$booking->booking_id} was rejected.");
    }

    /**
     * Update MIS remarks or audit fields.
     */
    public function updateMisRemarks(Request $request, RefundRequest $refundRequest)
    {
        $user = Auth::user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'mis_remarks' => 'nullable|string|max:1000',
            'deduction_from_agent' => 'nullable|string|max:100',
            'email_sent_to_agent_by' => 'nullable|string|max:100',
            'receipt_sent_to_cs' => 'nullable|string|max:100',
            'is_duplicate' => 'nullable|string|max:100',
        ]);

        $refundRequest->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'MIS data updated successfully.',
                'refund_request' => $refundRequest
            ]);
        }

        return redirect()->back()->with('success', 'MIS data updated successfully.');
    }
}
