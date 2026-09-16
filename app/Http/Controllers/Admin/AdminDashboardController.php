<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display the Admin Dashboard metrics and performance views.
     */
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Top Section - KPI Cards Summary (Grouped by Currency)
        $todayBookingsCount = Booking::whereDate('created_at', $today)->count();
        $todayTotalAmount = (float) Booking::whereDate('created_at', $today)->sum('total_amount');
        $todayTotalMco = (float) Booking::whereDate('created_at', $today)->whereReportableMco()->sum('total_mco');

        $todayCurrencyBreakdown = Booking::whereDate('created_at', $today)
            ->selectRaw("COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(total_amount) as total_amount, SUM(CASE WHEN booking_status IN ('ticketed', 'booking_complete') AND payment_status IN ('received', 'booking_complete') THEN total_mco ELSE 0 END) as total_mco, COUNT(*) as booking_count")
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get()
            ->keyBy('currency_code');

        $monthBookingsCount = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $monthTotalAmount = (float) Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $monthTotalMco = (float) Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->whereReportableMco()->sum('total_mco');

        $monthCurrencyBreakdown = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(total_amount) as total_amount, SUM(CASE WHEN booking_status IN ('ticketed', 'booking_complete') AND payment_status IN ('received', 'booking_complete') THEN total_mco ELSE 0 END) as total_mco, COUNT(*) as booking_count")
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get()
            ->keyBy('currency_code');

        // 2. Second Section - Currently Logged in Agents (role = 'agent', logged in today)
        $loggedInAgents = User::where('role', 'agent')
            ->whereNotNull('last_login_at')
            ->whereDate('last_login_at', $today)
            ->withCount(['bookings as today_bookings_count' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }])
            ->withSum(['bookings as today_total_mco' => function ($query) use ($today) {
                $query->whereDate('created_at', $today)->whereReportableMco();
            }], 'total_mco')
            ->orderByDesc('last_login_at')
            ->get();

        foreach ($loggedInAgents as $agent) {
            $agent->today_currency_mco = Booking::where('agent_id', $agent->id)
                ->whereDate('created_at', $today)
                ->selectRaw("COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(CASE WHEN booking_status IN ('ticketed', 'booking_complete') AND payment_status IN ('received', 'booking_complete') THEN total_mco ELSE 0 END) as total_mco")
                ->groupBy('currency_code')
                ->get()
                ->keyBy('currency_code');
        }

        // All active agents count for context
        $totalActiveAgents = User::where('role', 'agent')->where('is_active', true)->count();

        // 3. Third Section - Last 10 Bookings
        $latestBookings = Booking::with(['agent', 'passengers', 'merchantProfile'])
            ->latest('created_at')
            ->take(10)
            ->get();

        // 4. Fourth Section - Top 5 Agents Performance by MCO (Current Month)
        $topAgents = User::where('role', 'agent')
            ->where('is_active', true)
            ->withCount(['bookings as month_bookings_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            }])
            ->withSum(['bookings as month_total_amount' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            }], 'total_amount')
            ->withSum(['bookings as month_total_mco' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth])->whereReportableMco();
            }], 'total_mco')
            ->orderByDesc('month_total_mco')
            ->take(5)
            ->get()
            ->map(function ($agent) {
                $agent->month_total_amount = (float) ($agent->month_total_amount ?? 0);
                $agent->month_total_mco = (float) ($agent->month_total_mco ?? 0);
                return $agent;
            });

        foreach ($topAgents as $agent) {
            $agent->month_currency_mco = Booking::where('agent_id', $agent->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->selectRaw("COALESCE(NULLIF(currency, ''), 'USD') as currency_code, SUM(CASE WHEN booking_status IN ('ticketed', 'booking_complete') AND payment_status IN ('received', 'booking_complete') THEN total_mco ELSE 0 END) as total_mco, SUM(total_amount) as total_amount")
                ->groupBy('currency_code')
                ->get()
                ->keyBy('currency_code');
        }

        // Chart Data formatting for Top 5 Agents MCO Pie Chart
        $chartLabels = $topAgents->pluck('alias_name')->map(fn($name, $i) => $name ?: 'Agent #' . ($i + 1))->toArray();
        $chartData = $topAgents->pluck('month_total_mco')->toArray();

        return view('admin.dashboard', compact(
            'todayBookingsCount',
            'todayTotalAmount',
            'todayTotalMco',
            'todayCurrencyBreakdown',
            'monthBookingsCount',
            'monthTotalAmount',
            'monthTotalMco',
            'monthCurrencyBreakdown',
            'loggedInAgents',
            'totalActiveAgents',
            'latestBookings',
            'topAgents',
            'chartLabels',
            'chartData'
        ));
    }
}
