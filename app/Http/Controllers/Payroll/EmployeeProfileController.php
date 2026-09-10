<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class EmployeeProfileController extends Controller
{
    /**
     * Display listing of all employees with their payroll summary.
     */
    public function index(Request $request)
    {
        $query = User::with(['earning', 'leaveBalance']);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('alias_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('pan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('payroll.employees.index', compact('users'));
    }

    /**
     * Show full profile editor for HR & Accounts.
     */
    public function edit(User $user)
    {
        $user->load(['earning', 'leaveBalance']);
        
        // Auto-initialize records if not existing
        $earning = $user->earning ?? new Earning();
        $leaveBalance = $user->leaveBalance ?? new LeaveBalance();

        return view('payroll.employees.edit', compact('user', 'earning', 'leaveBalance'));
    }

    public function update(Request $request, User $user)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $request->boolean('is_active'),
            ]);
        }

        $validated = $request->validate([
            // Core User Fields
            'name' => 'required|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'alias_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'contact' => 'nullable|string|max:50',
            'extension' => 'nullable|string|max:50',
            'role' => 'required|string',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|string|min:6',

            // HR & Employment
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'designation' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'leaving_date' => 'nullable|date',
            'employment_type' => 'nullable|in:Probation,Permanent',

            // Statutory & Bank
            'tax_regime' => 'nullable|string|max:50',
            'pan' => 'nullable|string|max:20',
            'gender' => 'nullable|in:Male,Female,Other',
            'account_number' => 'nullable|string|max:60',
            'pf_account_number' => 'nullable|string|max:60',
            'pf_uan' => 'nullable|string|max:60',
            'esi_number' => 'nullable|string|max:60',
            'ctc' => 'nullable|numeric|min:0',

            // Earning Structure
            'basic_salary' => 'nullable|numeric|min:0',
            'hra' => 'nullable|numeric|min:0',
            'conveyance_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'special_allowance' => 'nullable|numeric|min:0',
            'other_payments' => 'nullable|numeric|min:0',

            // Leave Balances
            'el_balance' => 'nullable|numeric|min:0',
            'cl_balance' => 'nullable|numeric|min:0',
            'ul_balance' => 'nullable|numeric|min:0',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;

        // Logic: if joining date crossed 90 days, employment_type = Permanent (unless manually set or override)
        if (!empty($validated['joining_date'])) {
            $daysSinceJoining = \Carbon\Carbon::parse($validated['joining_date'])->diffInDays(now());
            if ($daysSinceJoining >= 90 && empty($request->input('manual_employment_type_override'))) {
                $validated['employment_type'] = 'Permanent';
            }
        }

        // 1. Update User
        $user->update($validated);

        // 2. Update or Create Earning
        $user->earning()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'basic_salary' => $validated['basic_salary'] ?? 0,
                'hra' => $validated['hra'] ?? 0,
                'conveyance_allowance' => $validated['conveyance_allowance'] ?? 0,
                'medical_allowance' => $validated['medical_allowance'] ?? 0,
                'special_allowance' => $validated['special_allowance'] ?? 0,
                'other_payments' => $validated['other_payments'] ?? 0,
            ]
        );

        // 3. Update or Create Leave Balance
        $user->leaveBalance()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'el_balance' => $validated['el_balance'] ?? 0,
                'cl_balance' => $validated['cl_balance'] ?? 0,
                'ul_balance' => $validated['ul_balance'] ?? 0,
            ]
        );

        return redirect()->route('payroll.employees.edit', $user)
            ->with('success', "Employee profile for '{$user->alias_name}' updated successfully.");
    }

    /**
     * AJAX endpoint to fetch employee salary structure & leave balances.
     */
    public function getSalaryData(User $user)
    {
        $user->load(['earning', 'leaveBalance']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'employee_name' => $user->employee_name,
            'official_name' => $user->official_name,
            'alias_name' => $user->alias_name,
            'employee_code' => $user->employee_code,
            'designation' => $user->designation,
            'location' => $user->location,
            'joining_date' => $user->joining_date ? $user->joining_date->format('Y-m-d') : null,
            'employment_type' => $user->computed_employment_type,
            'pan' => $user->pan,
            'account_number' => $user->account_number,
            'pf_uan' => $user->pf_uan,
            'earnings' => [
                'basic_salary' => $user->earning ? (float) $user->earning->basic_salary : 0,
                'hra' => $user->earning ? (float) $user->earning->hra : 0,
                'conveyance_allowance' => $user->earning ? (float) $user->earning->conveyance_allowance : 0,
                'medical_allowance' => $user->earning ? (float) $user->earning->medical_allowance : 0,
                'special_allowance' => $user->earning ? (float) $user->earning->special_allowance : 0,
                'other_payments' => $user->earning ? (float) $user->earning->other_payments : 0,
            ],
            'leaves' => [
                'el_balance' => $user->leaveBalance ? (float) $user->leaveBalance->el_balance : 0,
                'cl_balance' => $user->leaveBalance ? (float) $user->leaveBalance->cl_balance : 0,
                'ul_balance' => $user->leaveBalance ? (float) $user->leaveBalance->ul_balance : 0,
            ],
        ]);
    }
}
