<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyPayslipController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Show logged-in employee's payslips and leave balance.
     */
    public function index()
    {
        $user = Auth::user()->load(['leaveBalance', 'earning']);
        $payslips = Payslip::where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(12);

        return view('employee.my_payslips', compact('user', 'payslips'));
    }

    /**
     * Download own password protected payslip.
     */
    public function downloadPdf(Payslip $payslip)
    {
        // Enforce user can only download their own payslip
        if ($payslip->user_id !== Auth::id() && !in_array(Auth::user()->role, ['hr', 'accounts'])) {
            abort(403, 'Unauthorized access to payslip.');
        }

        $payslip->load(['user', 'creator']);
        $pdfContent = $this->payrollService->generatePdf($payslip);

        $fileName = "Payslip_{$payslip->month_name}_{$payslip->year}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
