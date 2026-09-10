<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_number',
        'user_id',
        'month',
        'year',
        'total_days_in_month',
        'working_days',
        'el_used',
        'cl_used',
        'paid_days',
        'unpaid_days',
        'earnings_data',
        'gross_rate',
        'gross_monthly',
        'gross_arrear',
        'total_earnings',
        'deductions_data',
        'total_deductions',
        'net_pay',
        'net_pay_words',
        'status',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'total_days_in_month' => 'integer',
            'working_days' => 'decimal:1',
            'el_used' => 'decimal:1',
            'cl_used' => 'decimal:1',
            'paid_days' => 'decimal:1',
            'unpaid_days' => 'decimal:1',
            'earnings_data' => 'array',
            'gross_rate' => 'decimal:2',
            'gross_monthly' => 'decimal:2',
            'gross_arrear' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'deductions_data' => 'array',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get month full name.
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 10));
    }

    /**
     * Formatted period: e.g. "September 2026"
     */
    public function getPeriodAttribute(): string
    {
        return "{$this->month_name} {$this->year}";
    }

    /**
     * Get password protect code for this payslip's PDF.
     * Corporate standard: PAN in uppercase (fallback to employee code or contact).
     */
    public function getPdfPasswordAttribute(): string
    {
        $user = $this->user;
        if (!empty($user->pan)) {
            return strtoupper(trim($user->pan));
        }
        if (!empty($user->employee_code)) {
            return strtoupper(trim($user->employee_code));
        }
        if (!empty($user->contact)) {
            return preg_replace('/[^0-9]/', '', $user->contact);
        }
        return '123456';
    }
}
