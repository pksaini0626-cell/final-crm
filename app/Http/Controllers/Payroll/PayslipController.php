<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PayslipController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of payslips.
     */
    public function index(Request $request)
    {
        $query = Payslip::with(['user', 'creator']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('month')) {
            $query->where('month', $request->input('month'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->input('year'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('alias_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            })->orWhere('payslip_number', 'like', "%{$search}%");
        }

        $payslips = $query->latest('id')->paginate(15);
        $employees = User::where('is_active', true)->orderBy('name')->get();

        return view('payroll.payslips.index', compact('payslips', 'employees'));
    }

    /**
     * Show form to generate a monthly payslip.
     */
    public function create(Request $request)
    {
        $employees = User::with(['earning', 'leaveBalance'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedUserId = $request->input('user_id');
        $selectedYear = (int) ($request->input('year') ?: now()->year);
        $selectedMonth = (int) ($request->input('month') ?: now()->subMonth()->month);
        if ($selectedMonth === 0) {
            $selectedMonth = 12;
            $selectedYear--;
        }

        $totalDaysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

        return view('payroll.payslips.create', compact('employees', 'selectedUserId', 'selectedYear', 'selectedMonth', 'totalDaysInMonth'));
    }

    /**
     * Live calculation preview via AJAX.
     */
    public function calculatePreview(Request $request)
    {
        $data = $request->all();
        $calculated = $this->payrollService->calculate($data);

        return response()->json($calculated);
    }

    /**
     * Store newly generated payslip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2099',
            'total_days_in_month' => 'required|integer|min:28|max:31',
            'working_days' => 'required|numeric|min:0',
            'el_used' => 'nullable|numeric|min:0',
            'cl_used' => 'nullable|numeric|min:0',
            'deduct_leaves_from_balance' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',

            // Rates & Arrears
            'basic_rate' => 'nullable|numeric|min:0',
            'basic_arrear' => 'nullable|numeric|min:0',
            'hra_rate' => 'nullable|numeric|min:0',
            'hra_arrear' => 'nullable|numeric|min:0',
            'conveyance_rate' => 'nullable|numeric|min:0',
            'conveyance_arrear' => 'nullable|numeric|min:0',
            'medical_rate' => 'nullable|numeric|min:0',
            'medical_arrear' => 'nullable|numeric|min:0',
            'special_rate' => 'nullable|numeric|min:0',
            'special_arrear' => 'nullable|numeric|min:0',
            'other_rate' => 'nullable|numeric|min:0',
            'other_monthly' => 'nullable|numeric|min:0',
            'other_arrear' => 'nullable|numeric|min:0',

            // Deductions
            'deduction_pf' => 'nullable|numeric|min:0',
            'deduction_esi' => 'nullable|numeric|min:0',
            'deduction_pt' => 'nullable|numeric|min:0',
            'deduction_tds' => 'nullable|numeric|min:0',
            'deduction_other' => 'nullable|numeric|min:0',
        ]);

        $calculated = $this->payrollService->calculate($validated);

        // Generate payslip number e.g. PS-2026-09-001
        $count = Payslip::where('year', $calculated['year'])->where('month', $calculated['month'])->count() + 1;
        $payslipNumber = sprintf('PS-%04d-%02d-%03d', $calculated['year'], $calculated['month'], $count);

        $payslip = Payslip::create([
            'payslip_number' => $payslipNumber,
            'user_id' => $validated['user_id'],
            'month' => $calculated['month'],
            'year' => $calculated['year'],
            'total_days_in_month' => $calculated['total_days_in_month'],
            'working_days' => $calculated['working_days'],
            'el_used' => $calculated['el_used'],
            'cl_used' => $calculated['cl_used'],
            'paid_days' => $calculated['paid_days'],
            'unpaid_days' => $calculated['unpaid_days'],
            'earnings_data' => $calculated['earnings_data'],
            'gross_rate' => $calculated['gross_rate'],
            'gross_monthly' => $calculated['gross_monthly'],
            'gross_arrear' => $calculated['gross_arrear'],
            'total_earnings' => $calculated['total_earnings'],
            'deductions_data' => $calculated['deductions_data'],
            'total_deductions' => $calculated['total_deductions'],
            'net_pay' => $calculated['net_pay'],
            'net_pay_words' => $calculated['net_pay_words'],
            'status' => 'published',
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Optionally deduct EL and CL from user's leave balance
        if ($request->has('deduct_leaves_from_balance')) {
            $user = User::find($validated['user_id']);
            if ($user && $user->leaveBalance) {
                $lb = $user->leaveBalance;
                $lb->el_balance = max(0, $lb->el_balance - (float)$calculated['el_used']);
                $lb->cl_balance = max(0, $lb->cl_balance - (float)$calculated['cl_used']);
                if ($calculated['unpaid_days'] > 0) {
                    $lb->ul_balance += (float)$calculated['unpaid_days'];
                }
                $lb->save();
            }
        }

        return redirect()->route('payroll.payslips.show', $payslip)
            ->with('success', "Payslip {$payslip->payslip_number} generated successfully.");
    }

    /**
     * Display a specific payslip.
     */
    public function show(Payslip $payslip)
    {
        $payslip->load(['user', 'creator']);
        return view('payroll.payslips.show', compact('payslip'));
    }

    /**
     * Download password-protected PDF.
     */
    public function downloadPdf(Payslip $payslip)
    {
        $payslip->load(['user', 'creator']);
        $pdfContent = $this->payrollService->generatePdf($payslip);

        $fileName = "Payslip_{$payslip->user->alias_name}_{$payslip->month_name}_{$payslip->year}.pdf";
        $fileName = str_replace([' ', '/', '\\'], '_', $fileName);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Delete a payslip.
     */
    public function destroy(Payslip $payslip)
    {
        $number = $payslip->payslip_number;
        $payslip->delete();

        return redirect()->route('payroll.payslips.index')
            ->with('success', "Payslip '{$number}' has been deleted.");
    }
}
