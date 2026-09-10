<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportController extends Controller
{
    /**
     * Display daily booking report summary grouped by date.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Booking::query();

        if ($dateFrom) {
            $query->whereDate('booking_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('booking_date', '<=', $dateTo);
        }

        // Group by booking date
        $dailyDates = (clone $query)
            ->selectRaw('DATE(booking_date) as report_date, COUNT(*) as total_bookings')
            ->groupBy(DB::raw('DATE(booking_date)'))
            ->orderByDesc('report_date')
            ->paginate(15)
            ->withQueryString();

        // Get currency breakdown for the paginated dates
        $datesOnPage = $dailyDates->pluck('report_date')->filter()->toArray();

        $currencyBreakdowns = [];
        if (!empty($datesOnPage)) {
            $currencyRows = Booking::whereIn(DB::raw('DATE(booking_date)'), $datesOnPage)
                ->selectRaw("DATE(booking_date) as report_date, COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(total_amount) as total_amount, SUM(total_mco) as total_mco")
                ->groupBy(DB::raw('DATE(booking_date)'), 'currency_code')
                ->orderBy('currency_code')
                ->get();

            foreach ($currencyRows as $row) {
                $currencyBreakdowns[$row->report_date][] = [
                    'currency' => $row->currency_code,
                    'total_amount' => (float) $row->total_amount,
                    'total_mco' => (float) $row->total_mco,
                ];
            }
        }

        return view('admin.reports.daily', compact('dailyDates', 'currencyBreakdowns', 'dateFrom', 'dateTo'));
    }

    /**
     * Display detailed booking report for a specific date.
     */
    public function detail(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $query = Booking::with(['agent', 'passengers', 'merchantProfile', 'bookingRemarks.user'])
            ->whereDate('booking_date', $date);

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                  ->orWhere('airline_pnr', 'like', "%{$search}%")
                  ->orWhere('gk_pnr', 'like', "%{$search}%")
                  ->orWhere('card_holder_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%")
                  ->orWhere('calling_number', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Calculate summary for this specific date
        $summaryTotalBookings = Booking::whereDate('booking_date', $date)->count();
        $currencyBreakdown = Booking::whereDate('booking_date', $date)
            ->selectRaw("COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(total_amount) as total_amount, SUM(total_mco) as total_mco")
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get()
            ->keyBy('currency_code');

        return view('admin.reports.daily-detail', compact('bookings', 'date', 'summaryTotalBookings', 'currencyBreakdown'));
    }

    /**
     * Export daily report / date-specific bookings to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Booking::with(['agent', 'passengers', 'merchantProfile']);

        $date = $request->input('date');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($date) {
            $query->whereDate('booking_date', $date);
            $filename = "daily-booking-report-{$date}.csv";
        } else {
            if ($dateFrom) {
                $query->whereDate('booking_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('booking_date', '<=', $dateTo);
            }
            $filename = 'daily-booking-report-' . date('Y-m-d') . '.csv';
        }

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                  ->orWhere('airline_pnr', 'like', "%{$search}%")
                  ->orWhere('gk_pnr', 'like', "%{$search}%")
                  ->orWhere('card_holder_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%");
            });
        }

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header row - exact 7 key fields specified in requirement
            fputcsv($handle, [
                'Date',
                'Agent',
                'Customer Detail',
                'Service Provided',
                'Total Amount',
                'Total MCO',
                'Booking Status',
            ]);

            // Stream rows in chunks
            $query->orderByDesc('booking_date')->orderByDesc('created_at')->chunk(200, function ($bookings) use ($handle) {
                foreach ($bookings as $b) {
                    $agentName = $b->agent ? ($b->agent->alias_name ?: $b->agent->name) : 'N/A';
                    
                    $customerDetail = trim(
                        ($b->card_holder_name ?: 'N/A') . 
                        ($b->email_address ? " ({$b->email_address})" : '') . 
                        ($b->phone ? " | Phone: {$b->phone}" : '')
                    );

                    $serviceProvided = trim(
                        ucwords(str_replace('_', ' ', $b->service_provided ?: 'Flight Booking')) . 
                        ($b->airline_pnr ? " [PNR: {$b->airline_pnr}]" : ($b->gk_pnr ? " [GK PNR: {$b->gk_pnr}]" : '')) . 
                        ($b->booking_portal ? " - Portal: {$b->booking_portal}" : '')
                    );

                    $curr = $b->currency ?: 'USD';
                    $totalAmountStr = "{$curr} " . number_format((float)$b->total_amount, 2, '.', '');
                    $totalMcoStr = "{$curr} " . number_format((float)$b->total_mco, 2, '.', '');
                    $bookingStatusStr = ucwords(str_replace('_', ' ', $b->booking_status ?: 'N/A'));

                    fputcsv($handle, [
                        $b->booking_date ? $b->booking_date->format('Y-m-d') : ($b->created_at ? $b->created_at->format('Y-m-d') : ''),
                        $agentName,
                        $customerDetail,
                        $serviceProvided,
                        $totalAmountStr,
                        $totalMcoStr,
                        $bookingStatusStr,
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
