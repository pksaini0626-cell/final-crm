<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use App\Models\LeaveBalance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PayrollService
{
    /**
     * Calculate payslip breakdown given parameters and rates.
     */
    public function calculate(array $data): array
    {
        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);
        $totalDays = (int) ($data['total_days_in_month'] ?? Carbon::create($year, $month, 1)->daysInMonth);

        $workingDays = (float) ($data['working_days'] ?? 0);
        $elUsed = (float) ($data['el_used'] ?? 0);
        $clUsed = (float) ($data['cl_used'] ?? 0);

        $paidDays = $workingDays + $elUsed + $clUsed;
        $unpaidDays = max(0, $totalDays - $paidDays);

        $ratio = $totalDays > 0 ? ($paidDays / $totalDays) : 0;

        // Earning Components Setup
        $components = [
            'Basic' => [
                'rate' => (float) ($data['basic_rate'] ?? 0),
                'arrear' => (float) ($data['basic_arrear'] ?? 0),
                'prorate' => true,
            ],
            'HRA' => [
                'rate' => (float) ($data['hra_rate'] ?? 0),
                'arrear' => (float) ($data['hra_arrear'] ?? 0),
                'prorate' => true,
            ],
            'Conveyance Allowance' => [
                'rate' => (float) ($data['conveyance_rate'] ?? 0),
                'arrear' => (float) ($data['conveyance_arrear'] ?? 0),
                'prorate' => true,
            ],
            'Medical Allowance' => [
                'rate' => (float) ($data['medical_rate'] ?? 0),
                'arrear' => (float) ($data['medical_arrear'] ?? 0),
                'prorate' => true,
            ],
            'Other Payments' => [
                'rate' => (float) ($data['other_rate'] ?? 0),
                'arrear' => (float) ($data['other_arrear'] ?? 0),
                'prorate' => false,
                'override_monthly' => isset($data['other_monthly']) ? (float) $data['other_monthly'] : null,
            ],
            'Special Allowance' => [
                'rate' => (float) ($data['special_rate'] ?? 0),
                'arrear' => (float) ($data['special_arrear'] ?? 0),
                'prorate' => true,
            ],
        ];

        $earningsTable = [];
        $grossRate = 0.0;
        $grossMonthly = 0.0;
        $grossArrear = 0.0;
        $totalEarnings = 0.0;

        foreach ($components as $name => $cfg) {
            $rate = $cfg['rate'];
            $arrear = $cfg['arrear'];

            if (isset($cfg['override_monthly']) && $cfg['override_monthly'] !== null) {
                $monthly = $cfg['override_monthly'];
            } elseif ($cfg['prorate']) {
                $monthly = round($rate * $ratio, 2);
            } else {
                $monthly = $rate;
            }

            $compTotal = round($monthly + $arrear, 2);

            $earningsTable[] = [
                'component' => $name,
                'rate' => $rate,
                'monthly' => $monthly,
                'arrear' => $arrear,
                'total' => $compTotal,
            ];

            $grossRate += $rate;
            $grossMonthly += $monthly;
            $grossArrear += $arrear;
            $totalEarnings += $compTotal;
        }

        // Deductions Setup
        $deductions = [
            'Provident Fund (PF)' => (float) ($data['deduction_pf'] ?? 0),
            'Employee State Insurance (ESI)' => (float) ($data['deduction_esi'] ?? 0),
            'Professional Tax (PT)' => (float) ($data['deduction_pt'] ?? 0),
            'Income Tax / TDS' => (float) ($data['deduction_tds'] ?? 0),
            'Other Deductions' => (float) ($data['deduction_other'] ?? 0),
        ];

        $deductionsTable = [];
        $totalDeductions = 0.0;
        foreach ($deductions as $name => $amt) {
            if ($amt > 0 || in_array($name, ['Provident Fund (PF)', 'Employee State Insurance (ESI)', 'Professional Tax (PT)'])) {
                $deductionsTable[] = [
                    'component' => $name,
                    'amount' => $amt,
                ];
                $totalDeductions += $amt;
            }
        }

        $netPay = max(0, round($totalEarnings - $totalDeductions, 2));
        $netPayWords = $this->numberToWords((int) round($netPay)) . ' Rupees Only';

        return [
            'year' => $year,
            'month' => $month,
            'total_days_in_month' => $totalDays,
            'working_days' => $workingDays,
            'el_used' => $elUsed,
            'cl_used' => $clUsed,
            'paid_days' => $paidDays,
            'unpaid_days' => $unpaidDays,
            'earnings_data' => $earningsTable,
            'gross_rate' => round($grossRate, 2),
            'gross_monthly' => round($grossMonthly, 2),
            'gross_arrear' => round($grossArrear, 2),
            'total_earnings' => round($totalEarnings, 2),
            'deductions_data' => $deductionsTable,
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => $netPay,
            'net_pay_words' => $netPayWords,
        ];
    }

    /**
     * Generate password-protected PDF output for a payslip.
     */
    public function generatePdf(Payslip $payslip): string
    {
        $pdf = Pdf::loadView('pdf.payslip', compact('payslip'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $cpdf = $canvas->get_cpdf();

        $password = $payslip->pdf_password;
        $ownerPassword = config('app.key', 'CGAdminSecretKey');

        if (is_object($cpdf) && method_exists($cpdf, 'setEncryption')) {
            $cpdf->setEncryption($password, $ownerPassword, ['print']);
        }

        return $dompdf->output();
    }

    /**
     * Convert integer number to English currency words (Indian numbering style).
     */
    public function numberToWords(int $num): string
    {
        if ($num === 0) {
            return 'Zero';
        }

        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];

        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        $str = '';
        $crore = floor($num / 10000000);
        $num %= 10000000;

        $lakh = floor($num / 100000);
        $num %= 100000;

        $thousand = floor($num / 1000);
        $num %= 1000;

        $hundred = floor($num / 100);
        $remainder = $num % 100;

        if ($crore > 0) {
            $str .= $this->numberToWords((int)$crore) . ' Crore ';
        }
        if ($lakh > 0) {
            $str .= $this->twoDigitToWords((int)$lakh, $words) . ' Lakh ';
        }
        if ($thousand > 0) {
            $str .= $this->twoDigitToWords((int)$thousand, $words) . ' Thousand ';
        }
        if ($hundred > 0) {
            $str .= $words[$hundred] . ' Hundred ';
        }
        if ($remainder > 0) {
            $str .= $this->twoDigitToWords((int)$remainder, $words) . ' ';
        }

        return trim(preg_replace('/\s+/', ' ', $str));
    }

    private function twoDigitToWords(int $n, array $words): string
    {
        if ($n < 20) {
            return $words[$n] ?? '';
        }
        $tens = floor($n / 10) * 10;
        $units = $n % 10;
        return trim(($words[$tens] ?? '') . ' ' . ($words[$units] ?? ''));
    }
}
