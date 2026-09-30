<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRemark;
use App\Models\ChargebackActivity;
use App\Models\ChargebackControl;
use App\Models\ChargebackPortal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterAdminChargebackAnalyticsController extends Controller
{
    /**
     * Enforce strict role access: ONLY the Master Admin can access this controller.
     */
    protected function authorizeMasterAdmin(): void
    {
        $user = Auth::user();
        if (!$user || ($user->role !== 'master_admin' && (!method_exists($user, 'hasRole') || !$user->hasRole('master_admin')))) {
            abort(403, 'Access Denied: This analytics panel is exclusively reserved for the Master Administrator.');
        }
    }

    /**
     * Master Admin Chargeback Analytics & User Footprints Dashboard.
     */
    public function index(Request $request)
    {
        $this->authorizeMasterAdmin();

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. All Chargeback Team Users
        $chargebackUsers = User::where(function ($q) {
            $q->where('role', 'chargeback')
              ->orWhereHas('roles', fn($r) => $r->where('name', 'chargeback'));
        })->orderBy('name')->get();

        $chargebackUserIds = $chargebackUsers->pluck('id')->toArray();

        // 2. High-Level KPI Summary (Today vs This Month vs Total)
        $todayCreated = ChargebackControl::whereDate('created_at', $today)->count();
        $todayUpdated = ChargebackActivity::where('action', 'updated')
            ->whereDate('created_at', $today)
            ->count();
        $todayRemarks = ChargebackActivity::where('action', 'remark_added')
            ->whereDate('created_at', $today)
            ->count();
        $todayLogins = ChargebackActivity::where('action', 'login')
            ->whereDate('created_at', $today)
            ->count();
        $todayActiveUsersCount = ChargebackActivity::whereDate('created_at', $today)
            ->distinct('user_id')
            ->count('user_id');

        $monthCreated = ChargebackControl::where('created_at', '>=', $startOfMonth)->count();
        $monthUpdated = ChargebackActivity::where('action', 'updated')
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        $monthRemarks = ChargebackActivity::where('action', 'remark_added')
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        $monthDisputedAmount = ChargebackControl::where('created_at', '>=', $startOfMonth)
            ->sum('disputed_amount');

        $totalChargebacksCount = ChargebackControl::count();
        $totalDisputedAmount = ChargebackControl::sum('disputed_amount');

        // 3. User Footprints & Productivity Matrix
        $userFootprints = [];
        foreach ($chargebackUsers as $cbUser) {
            $userActivitiesToday = ChargebackActivity::where('user_id', $cbUser->id)
                ->whereDate('created_at', $today)
                ->get();

            $userActivitiesMonth = ChargebackActivity::where('user_id', $cbUser->id)
                ->where('created_at', '>=', $startOfMonth)
                ->get();

            $lastLogin = ChargebackActivity::where('user_id', $cbUser->id)
                ->where('action', 'login')
                ->latest('created_at')
                ->first();

            $lastAction = ChargebackActivity::where('user_id', $cbUser->id)
                ->latest('created_at')
                ->first();

            $userFootprints[] = [
                'user' => $cbUser,
                'last_login_at' => $lastLogin ? $lastLogin->created_at : $cbUser->last_login_at,
                'last_login_ip' => $lastLogin ? $lastLogin->ip_address : null,
                'last_action_at' => $lastAction ? $lastAction->created_at : null,
                'last_action_desc' => $lastAction ? $lastAction->description : null,
                'today' => [
                    'created' => $userActivitiesToday->where('action', 'created')->count(),
                    'updated' => $userActivitiesToday->where('action', 'updated')->count(),
                    'remarks' => $userActivitiesToday->where('action', 'remark_added')->count(),
                    'logins' => $userActivitiesToday->where('action', 'login')->count(),
                    'total' => $userActivitiesToday->count(),
                ],
                'month' => [
                    'created' => $userActivitiesMonth->where('action', 'created')->count(),
                    'updated' => $userActivitiesMonth->where('action', 'updated')->count(),
                    'remarks' => $userActivitiesMonth->where('action', 'remark_added')->count(),
                    'logins' => $userActivitiesMonth->where('action', 'login')->count(),
                    'total' => $userActivitiesMonth->count(),
                ],
            ];
        }

        // 4. Activity Audit Footprints Feed (Filterable & Paginated)
        $activitiesQuery = ChargebackActivity::with(['user', 'chargeback'])->latest('created_at');

        if ($filterUser = $request->input('activity_user_id')) {
            $activitiesQuery->where('user_id', $filterUser);
        }
        if ($filterAction = $request->input('activity_action')) {
            $activitiesQuery->where('action', $filterAction);
        }
        if ($filterDateFrom = $request->input('activity_date_from')) {
            $activitiesQuery->whereDate('created_at', '>=', $filterDateFrom);
        }
        if ($filterDateTo = $request->input('activity_date_to')) {
            $activitiesQuery->whereDate('created_at', '<=', $filterDateTo);
        }
        if ($filterSearch = trim($request->input('activity_search', ''))) {
            $activitiesQuery->where(function ($q) use ($filterSearch) {
                $q->where('case_number', 'like', "%{$filterSearch}%")
                  ->orWhere('pnr', 'like', "%{$filterSearch}%")
                  ->orWhere('description', 'like', "%{$filterSearch}%");
            });
        }

        $activities = $activitiesQuery->paginate(20, ['*'], 'activities_page')->withQueryString();

        // 5. Remarks Stream
        $remarks = ChargebackActivity::with('user')
            ->where('action', 'remark_added')
            ->latest('created_at')
            ->take(30)
            ->get();

        // 6. All Chargebacks Master List (Filterable & Paginated)
        $chargebacksQuery = ChargebackControl::with(['booking.passengers', 'booking.agent', 'creator'])
            ->latest('received_date');

        if ($search = trim($request->input('search', ''))) {
            $chargebacksQuery->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('booking_reference', 'like', "%{$search}%")
                  ->orWhere('pnr', 'like', "%{$search}%")
                  ->orWhere('card_no', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('portal', 'like', "%{$search}%")
                  ->orWhere('reason_code', 'like', "%{$search}%");
            });
        }

        if ($portal = $request->input('portal')) {
            $chargebacksQuery->where('portal', $portal);
        }
        if ($cbkStatus = $request->input('cbk_status')) {
            $chargebacksQuery->where('cbk_status', $cbkStatus);
        }
        if ($caseType = $request->input('case_type')) {
            $chargebacksQuery->where('case_type', $caseType);
        }
        if ($disputeType = $request->input('dispute_type')) {
            $chargebacksQuery->where('dispute_type', $disputeType);
        }
        if ($currentStatus = $request->input('current_status')) {
            $chargebacksQuery->where('current_status', $currentStatus);
        }
        if ($dateFrom = $request->input('date_from')) {
            $chargebacksQuery->whereDate('received_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $chargebacksQuery->whereDate('received_date', '<=', $dateTo);
        }

        $allChargebacks = $chargebacksQuery->paginate(25, ['*'], 'cb_page')->withQueryString();
        $portals = ChargebackPortal::orderBy('name')->get();

        return view('master_admin.chargebacks.analytics', compact(
            'chargebackUsers',
            'todayCreated',
            'todayUpdated',
            'todayRemarks',
            'todayLogins',
            'todayActiveUsersCount',
            'monthCreated',
            'monthUpdated',
            'monthRemarks',
            'monthDisputedAmount',
            'totalChargebacksCount',
            'totalDisputedAmount',
            'userFootprints',
            'activities',
            'remarks',
            'allChargebacks',
            'portals'
        ));
    }
}
