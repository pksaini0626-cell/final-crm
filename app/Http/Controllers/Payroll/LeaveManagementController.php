<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LeaveManagementController extends Controller
{
    /**
     * Display leave balances overview.
     */
    public function index(Request $request)
    {
        $query = User::with('leaveBalance')->where('is_active', true);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('alias_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('payroll.leaves.index', compact('users'));
    }

    /**
     * Update leave balance for a user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'el_balance' => 'required|numeric|min:0',
            'cl_balance' => 'required|numeric|min:0',
            'ul_balance' => 'required|numeric|min:0',
        ]);

        $user->leaveBalance()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()->back()
            ->with('success', "Leave balance updated for {$user->alias_name}.");
    }

    /**
     * Trigger monthly accrual for permanent employees.
     */
    public function triggerAccrual(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        Artisan::call('leaves:accrue', ['--month' => $month]);
        $output = Artisan::output();

        return redirect()->back()
            ->with('success', "Monthly accrual executed for {$month}: " . trim($output));
    }
}
