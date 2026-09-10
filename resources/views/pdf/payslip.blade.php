<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $payslip->payslip_number }}</title>
    <style>
        @page {
            margin: 25px 30px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .payslip-title-badge {
            font-size: 13px;
            font-weight: 700;
            background-color: #0f172a;
            color: #ffffff;
            padding: 6px 14px;
            text-align: center;
            border-radius: 4px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 14px;
            background-color: #f8fafc;
        }
        .meta-box td {
            padding: 5px 8px;
            font-size: 10px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-label {
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
            width: 16%;
        }
        .meta-val {
            font-weight: 600;
            color: #0f172a;
            width: 34%;
        }

        /* Earnings Table matching user's exact uploaded design */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .salary-table th.main-header {
            background-color: #000000;
            color: #ffffff;
            text-align: center;
            font-weight: 800;
            font-size: 11px;
            padding: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .salary-table th.sub-header {
            background-color: #d1d5db;
            color: #111827;
            text-align: left;
            font-weight: 800;
            font-size: 10px;
            padding: 5px 8px;
            border: 1px solid #9ca3af;
            text-transform: uppercase;
        }
        .salary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: 500;
        }
        .salary-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row td {
            background-color: #e5e7eb !important;
            font-weight: 800 !important;
            color: #000000;
            border-top: 2px solid #374151;
            border-bottom: 2px solid #374151;
        }

        /* Two Column Layout for Earnings & Deductions */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .net-pay-box {
            width: 100%;
            border: 2px solid #0f172a;
            background-color: #f1f5f9;
            padding: 10px 14px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }
        .net-pay-amount {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .net-pay-words {
            font-size: 10.5px;
            font-weight: 600;
            color: #334155;
            margin-top: 3px;
        }

        .leaves-summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1px solid #cbd5e1;
        }
        .leaves-summary th {
            background-color: #e2e8f0;
            font-size: 9.5px;
            padding: 4px 6px;
            text-align: center;
            border: 1px solid #cbd5e1;
        }
        .leaves-summary td {
            font-size: 10px;
            padding: 4px 6px;
            text-align: center;
            border: 1px solid #cbd5e1;
            font-weight: 700;
        }

        .footer-note {
            margin-top: 25px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">Calling Genie LLP</div>
                <div class="company-sub">Global Operations &amp; Travel Reservations Network</div>
                <div class="company-sub">Payslip Reference: <strong>{{ $payslip->payslip_number }}</strong></div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div class="payslip-title-badge">
                    Payslip - {{ strtoupper($payslip->month_name) }} {{ $payslip->year }}
                </div>
                <div style="font-size: 9px; color: #64748b; margin-top: 4px;">
                    Generated: {{ $payslip->created_at ? $payslip->created_at->format('d-M-Y') : now()->format('d-M-Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Employee Information Grid -->
    <table class="meta-box">
        <tr>
            <td class="meta-label">Employee Code</td>
            <td class="meta-val font-mono">{{ $payslip->user->employee_code ?: 'EMP-' . str_pad($payslip->user->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td class="meta-label">Employee Name</td>
            <td class="meta-val">{{ $payslip->user->official_name }}</td>
        </tr>
        <tr>
            <td class="meta-label">Designation</td>
            <td class="meta-val">{{ $payslip->user->designation ?: strtoupper($payslip->user->role) }}</td>
            <td class="meta-label">Location</td>
            <td class="meta-val">{{ $payslip->user->location ?: 'Corporate Office' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date of Joining</td>
            <td class="meta-val">{{ $payslip->user->joining_date ? $payslip->user->joining_date->format('d-M-Y') : 'N/A' }}</td>
            <td class="meta-label">Employment Type</td>
            <td class="meta-val">{{ $payslip->user->computed_employment_type }}</td>
        </tr>
        <tr>
            <td class="meta-label">Bank Account</td>
            <td class="meta-val">{{ $payslip->user->account_number ?: 'N/A' }}</td>
            <td class="meta-label">PAN</td>
            <td class="meta-val">{{ $payslip->user->pan ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="meta-label">PF Account / UAN</td>
            <td class="meta-val">{{ $payslip->user->pf_uan ?: ($payslip->user->pf_account_number ?: 'N/A') }}</td>
            <td class="meta-label">ESI Number</td>
            <td class="meta-val">{{ $payslip->user->esi_number ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Total Month Days</td>
            <td class="meta-val">{{ $payslip->total_days_in_month }} Days</td>
            <td class="meta-label">Total Paid Days</td>
            <td class="meta-val" style="color: #16a34a; font-weight: 800;">{{ number_format($payslip->paid_days, 1) }} Days</td>
        </tr>
        <tr>
            <td class="meta-label">Working Days</td>
            <td class="meta-val">{{ number_format($payslip->working_days, 1) }} Days</td>
            <td class="meta-label">Leaves Taken</td>
            <td class="meta-val">
                EL: {{ number_format($payslip->el_used, 1) }} | 
                CL: {{ number_format($payslip->cl_used, 1) }} | 
                Unpaid (UL): {{ number_format($payslip->unpaid_days, 1) }}
            </td>
        </tr>
    </table>

    <!-- Main Earnings Table (Matching user's attached design) -->
    <table class="salary-table">
        <thead>
            <tr>
                <th colspan="5" class="main-header">EARNINGS (INR)</th>
            </tr>
            <tr>
                <th class="sub-header" style="width: 36%;">COMPONENTS</th>
                <th class="sub-header text-right" style="width: 16%;">RATE</th>
                <th class="sub-header text-right" style="width: 16%;">MONTHLY</th>
                <th class="sub-header text-right" style="width: 16%;">ARREAR</th>
                <th class="sub-header text-right" style="width: 16%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($payslip->earnings_data) && is_array($payslip->earnings_data))
                @foreach($payslip->earnings_data as $item)
                    <tr>
                        <td><strong>{{ $item['component'] }}</strong></td>
                        <td class="text-right">{{ number_format($item['rate'], 2) }}</td>
                        <td class="text-right">{{ number_format($item['monthly'], 2) }}</td>
                        <td class="text-right">{{ number_format($item['arrear'], 2) }}</td>
                        <td class="text-right" style="font-weight: 600;">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            @endif
            <tr class="total-row">
                <td><strong>TOTAL EARNINGS</strong></td>
                <td class="text-right"><strong>{{ number_format($payslip->gross_rate, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($payslip->gross_monthly, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($payslip->gross_arrear, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($payslip->total_earnings, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Deductions Table -->
    @if(!empty($payslip->deductions_data) && count($payslip->deductions_data) > 0)
    <table class="salary-table" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th colspan="2" class="main-header" style="background-color: #334155;">DEDUCTIONS (INR)</th>
            </tr>
            <tr>
                <th class="sub-header" style="width: 70%;">COMPONENT</th>
                <th class="sub-header text-right" style="width: 30%;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payslip->deductions_data as $ded)
                <tr>
                    <td>{{ $ded['component'] }}</td>
                    <td class="text-right">{{ number_format($ded['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>TOTAL DEDUCTIONS</strong></td>
                <td class="text-right"><strong>{{ number_format($payslip->total_deductions, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Net Payable Box -->
    <div class="net-pay-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%;">
                    <div style="font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase;">Net Payable Amount (INR)</div>
                    <div class="net-pay-amount">&#8377; {{ number_format($payslip->net_pay, 2) }}</div>
                    <div class="net-pay-words">Amount in words: <em>{{ $payslip->net_pay_words }}</em></div>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div style="font-size: 9px; color: #64748b;">
                        Gross Earnings: &#8377; {{ number_format($payslip->total_earnings, 2) }}<br>
                        Total Deductions: &#8377; {{ number_format($payslip->total_deductions, 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Leave Balances Summary -->
    @if($payslip->user->leaveBalance)
    <table class="leaves-summary">
        <thead>
            <tr>
                <th colspan="3">LEAVE BALANCE SUMMARY (AS ON DATE)</th>
            </tr>
            <tr>
                <th>Earned Leave (EL) Balance</th>
                <th>Casual Leave (CL) Balance</th>
                <th>Unpaid Leave (UL) Accumulated</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($payslip->user->leaveBalance->el_balance, 1) }}</td>
                <td>{{ number_format($payslip->user->leaveBalance->cl_balance, 1) }}</td>
                <td>{{ number_format($payslip->user->leaveBalance->ul_balance, 1) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(!empty($payslip->remarks))
    <div style="margin-top: 8px; font-size: 9.5px; color: #475569; background: #f8fafc; padding: 5px 8px; border: 1px solid #e2e8f0;">
        <strong>Remarks:</strong> {{ $payslip->remarks }}
    </div>
    @endif

    <!-- Footer Note -->
    <div class="footer-note">
        This is a system generated document from <strong>Calling Genie LLP</strong> and does not require a signature.<br>
        Confidential — For intended recipient only.
    </div>

</body>
</html>
