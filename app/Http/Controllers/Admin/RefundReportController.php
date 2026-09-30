<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Merchant;
use App\Models\RefundRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundReportController extends Controller
{
    /**
     * Display the Refund / Void Request Report Sheet.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        $baseQuery = $this->buildReportQuery($request);

        // Cloned for KPI calculations
        $totalCases = (clone $baseQuery)->count();
        $approvedCases = (clone $baseQuery)->where('status', 'approved')->count();
        $pendingCases = (clone $baseQuery)->where('status', 'pending')->count();
        $voidCases = (clone $baseQuery)->whereIn('request_type', ['void', 'partial_void'])->count();
        $refundOnlyCases = (clone $baseQuery)->where('request_type', 'refund')->count();

        // Currency sums for approved refunds (group by currency only, clean of other columns)
        $currencyTotals = (clone $baseQuery)
            ->where('status', 'approved')
            ->select('currency', DB::raw('SUM(refund_amount) as total_refunded'))
            ->groupBy('currency')
            ->get();

        $refundRequests = (clone $baseQuery)
            ->with([
                'booking.agent',
                'booking.merchantProfile',
                'booking.passengers',
                'agent',
                'approver'
            ])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $merchants = Merchant::where('is_active', true)->orderBy('name')->get();
        $agents = User::whereIn('role', ['agent', 'ticketing', 'admin'])->orderBy('name')->get();

        return view('admin.reports.refunds', compact(
            'refundRequests',
            'totalCases',
            'approvedCases',
            'pendingCases',
            'voidCases',
            'refundOnlyCases',
            'currencyTotals',
            'merchants',
            'agents'
        ));
    }

    /**
     * Export the Refund / Void Request Report Sheet to CSV with 35 columns.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = auth()->user();
        abort_if(!$user || (!$user->hasAnyRole(['admin', 'master_admin', 'mis', 'manager']) && !in_array($user->role, ['admin', 'master_admin', 'mis', 'manager'])), 403, 'Unauthorized access.');

        $query = $this->buildReportQuery($request)
            ->with([
                'booking.agent',
                'booking.merchantProfile',
                'booking.passengers',
                'agent',
                'approver'
            ])
            ->latest('created_at');

        $filename = 'refund-void-report-' . date('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // 35 Header Columns matching exact specification
            fputcsv($handle, [
                'Timestamp',
                'Date of Booking',
                'Agent Name',
                'Travel Date',
                'PNR',
                'Merchant Name',
                'Currency',
                'Reason for Refund',
                'Request Raised By',
                'Approved By',
                'Booking Type',
                'Company Card / VAN Card Used',
                'Amount / na',
                'Card Last 4 Digit',
                'Card Holder\'s Name',
                'Email address',
                'Billing Phone',
                'Refund Amount',
                'Request Type',
                'Remarks',
                'Verticals',
                'MIS Remarks',
                'Refund age',
                'Refund date',
                'Status',
                'TL/Admin Remarks',
                'Month',
                'Actioned by',
                'Deduction from Agent (By MIS for Incentive Calculation)',
                'Email Sent To Agent By',
                'Actioned in (Month)',
                'Receipt Sent to CS',
                'Updated on MCO Sheets',
                'Merchant as per MIS',
                'Dub/Uniqe',
            ]);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $item) {
                    $booking = $item->booking;
                    $merchantObj = $booking ? $booking->merchantProfile : null;

                    // 1. Timestamp
                    $timestamp = $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : 'N/A';

                    // 2. Date of Booking
                    $bookingDate = ($booking && $booking->booking_date) ? Carbon::parse($booking->booking_date)->format('Y-m-d') : 'N/A';

                    // 3. Agent Name
                    $agentName = ($booking && $booking->agent) ? ($booking->agent->alias_name ?: $booking->agent->name) : 'N/A';

                    // 4. Travel Date
                    $travelDate = ($booking && $booking->travel_date) ? Carbon::parse($booking->travel_date)->format('Y-m-d') : 'N/A';

                    // 5. PNR
                    $pnr = $booking ? ($booking->airline_pnr ?: ($booking->gk_pnr ?: $booking->booking_id)) : 'N/A';

                    // 6. Merchant Name (merchant name + merchant code)
                    $merchantName = 'N/A';
                    if ($merchantObj) {
                        $mCode = $merchantObj->merchant_code ?: ($merchantObj->code ?: '');
                        $merchantName = $merchantObj->name . ($mCode ? " ({$mCode})" : '');
                    } elseif ($booking && $booking->merchant) {
                        $merchantName = $booking->merchant;
                    }

                    // 7. Currency
                    $currency = $booking ? ($booking->currency ?: 'USD') : ($item->currency ?: 'USD');

                    // 8. Reason for Refund
                    $reasonForRefund = $item->reason_for_refund ?: 'N/A';

                    // 9. Request Raised By
                    $raisedBy = $item->agent ? ($item->agent->alias_name ?: $item->agent->name) : 'N/A';

                    // 10. Approved By
                    $approvedBy = $item->approver ? ($item->approver->alias_name ?: $item->approver->name) : ($item->status === 'approved' ? 'Approved' : 'Pending');

                    // 11. Booking Type
                    $bookingType = $booking ? ($booking->booking_portal ?: ($booking->service_provided ?: 'Flight')) : 'N/A';

                    // 12. Company Card / VAN Card Used
                    $companyCardUsed = ($booking && $booking->company_card_used) ? 'Yes' : 'No';

                    // 13. Amount / na
                    $companyCardAmount = ($booking && $booking->company_card_used && $booking->company_card_amount > 0)
                        ? number_format((float)$booking->company_card_amount, 2)
                        : 'na';

                    // 14. Card Last 4 Digit
                    $cardLast4 = $booking ? ($booking->card_last_4 ?: 'N/A') : 'N/A';

                    // 15. Card Holder's Name
                    $cardHolderName = $booking ? ($booking->card_holder_name ?: 'N/A') : 'N/A';

                    // 16. Email address
                    $emailAddress = $booking ? ($booking->email_address ?: 'N/A') : 'N/A';

                    // 17. Billing Phone
                    $billingPhone = $booking ? ($booking->billing_phone ?: ($booking->calling_number ?: 'N/A')) : 'N/A';

                    // 18. Refund Amount
                    $refundAmount = number_format((float)$item->refund_amount, 2);

                    // 19. Request Type (Void/Partial void/ refund)
                    $requestType = match ($item->request_type) {
                        'void' => 'Void',
                        'partial_void' => 'Partial void',
                        'refund' => 'refund',
                        default => ucfirst($item->request_type)
                    };

                    // 20. Remarks
                    $remarks = $item->remarks ?: 'N/A';

                    // 21. Verticals
                    $verticals = $booking ? ucfirst($booking->vertical ?: 'Flight') : 'Flight';

                    // 22. MIS Remarks
                    $misRemarks = $item->mis_remarks ?: '';

                    // 23. Refund age: booking date - refund date = refund days
                    $refundAge = $item->refund_age_days . ' days';

                    // 24. Refund date
                    $refundDate = $item->refund_date ? Carbon::parse($item->refund_date)->format('Y-m-d') : 'N/A';

                    // 25. Status (payment status from booking)
                    $status = $booking ? $booking->payment_status : $item->status;

                    // 26. TL/Admin Remarks
                    $tlAdminRemarks = $item->admin_remarks ?: '';

                    // 27. Month (refund month fetched from refund date)
                    $month = $item->refund_month;

                    // 28. Actioned by
                    $actionedBy = $item->approver ? ($item->approver->alias_name ?: $item->approver->name) : 'N/A';

                    // 29. Deduction from Agent
                    $deduction = $item->deduction_from_agent ?: 'nil';

                    // 30. Email Sent To Agent By
                    $emailSentToAgentBy = $item->email_sent_to_agent_by ?: 'nil';

                    // 31. Actioned in (Month)
                    $actionedInMonth = $item->actioned_month;

                    // 32. Receipt Sent to CS
                    $receiptSentToCs = $item->receipt_sent_to_cs ?: 'nil';

                    // 33. Updated on MCO Sheets
                    $updatedOnMco = 'yes (' . ($booking ? $booking->payment_status : $item->request_type) . ')';

                    // 34. Merchant as per MIS
                    $merchantAsPerMis = 'N/A';
                    if ($merchantObj) {
                        $merchantAsPerMis = $merchantObj->merchant_code ?: ($merchantObj->code ?: $merchantObj->name);
                    } elseif ($booking && $booking->merchant) {
                        $merchantAsPerMis = $booking->merchant;
                    }

                    // 35. Dub/Uniqe
                    $dubUnique = $item->is_duplicate ?: 'nil';

                    fputcsv($handle, [
                        $timestamp,
                        $bookingDate,
                        $agentName,
                        $travelDate,
                        $pnr,
                        $merchantName,
                        $currency,
                        $reasonForRefund,
                        $raisedBy,
                        $approvedBy,
                        $bookingType,
                        $companyCardUsed,
                        $companyCardAmount,
                        $cardLast4,
                        $cardHolderName,
                        $emailAddress,
                        $billingPhone,
                        $refundAmount,
                        $requestType,
                        $remarks,
                        $verticals,
                        $misRemarks,
                        $refundAge,
                        $refundDate,
                        $status,
                        $tlAdminRemarks,
                        $month,
                        $actionedBy,
                        $deduction,
                        $emailSentToAgentBy,
                        $actionedInMonth,
                        $receiptSentToCs,
                        $updatedOnMco,
                        $merchantAsPerMis,
                        $dubUnique,
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Common query builder for refund requests report.
     */
    protected function buildReportQuery(Request $request)
    {
        $query = RefundRequest::query();

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('refund_requests.status', $request->input('status'));
        }

        // Request Type Filter
        if ($request->filled('request_type')) {
            $query->where('refund_requests.request_type', $request->input('request_type'));
        }

        // Payment status filter on booking
        if ($request->filled('payment_status')) {
            $ps = $request->input('payment_status');
            $query->whereHas('booking', function ($qb) use ($ps) {
                $qb->where('payment_status', $ps);
            });
        }

        // Date range filter (refund_date by default)
        if ($request->filled('date_from')) {
            $query->whereDate('refund_requests.refund_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('refund_requests.refund_date', '<=', $request->input('date_to'));
        }

        // Agent Filter
        if ($request->filled('agent_id')) {
            $query->where('refund_requests.agent_id', $request->input('agent_id'));
        }

        // Merchant Filter
        if ($request->filled('merchant_id')) {
            $mId = $request->input('merchant_id');
            $query->whereHas('booking', function ($qb) use ($mId) {
                $qb->where('merchant_id', $mId);
            });
        }

        // General search
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

        return $query;
    }
}
