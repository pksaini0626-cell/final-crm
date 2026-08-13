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

        // 1. Top Section - KPI Cards Summary
        $todayBookingsCount = Booking::whereDate('created_at', $today)->count();
        $todayTotalAmount = (float) Booking::whereDate('created_at', $today)->sum('total_amount');
        $todayTotalMco = (float) Booking::whereDate('created_at', $today)->sum('total_mco');

        $monthBookingsCount = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $monthTotalAmount = (float) Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $monthTotalMco = (float) Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_mco');

        // 2. Second Section - Currently Logged in Agents (role = 'agent', logged in today)
        $loggedInAgents = User::where('role', 'agent')
            ->whereNotNull('last_login_at')
            ->whereDate('last_login_at', $today)
            ->withCount(['bookings as today_bookings_count' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }])
            ->withSum(['bookings as today_total_mco' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }], 'total_mco')
            ->orderByDesc('last_login_at')
            ->get();

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
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            }], 'total_mco')
            ->orderByDesc('month_total_mco')
            ->take(5)
            ->get()
            ->map(function ($agent) {
                $agent->month_total_amount = (float) ($agent->month_total_amount ?? 0);
                $agent->month_total_mco = (float) ($agent->month_total_mco ?? 0);
                return $agent;
            });

        // Chart Data formatting for Top 5 Agents MCO Pie Chart
        $chartLabels = $topAgents->pluck('alias_name')->map(fn($name, $i) => $name ?: 'Agent #' . ($i + 1))->toArray();
        $chartData = $topAgents->pluck('month_total_mco')->toArray();

        return view('admin.dashboard', compact(
            'todayBookingsCount',
            'todayTotalAmount',
            'todayTotalMco',
            'monthBookingsCount',
            'monthTotalAmount',
            'monthTotalMco',
            'loggedInAgents',
            'totalActiveAgents',
            'latestBookings',
            'topAgents',
            'chartLabels',
            'chartData'
        ));
    }
}
