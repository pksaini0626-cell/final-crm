<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'el_balance',
        'cl_balance',
        'ul_balance',
        'last_accrual_month',
    ];

    protected function casts(): array
    {
        return [
            'el_balance' => 'decimal:2',
            'cl_balance' => 'decimal:2',
            'ul_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accrue monthly leaves if user is permanent.
     * Add 1 EL and 0.5 CL every month.
     */
    public function accrueForMonth(string $monthYear): bool
    {
        if ($this->last_accrual_month === $monthYear) {
            return false; // Already accrued for this month
        }

        $employmentType = $this->user->computed_employment_type;

        if (strtolower($employmentType) === 'permanent') {
            $this->el_balance += 1.0;
            $this->cl_balance += 0.5;
            $this->last_accrual_month = $monthYear;
            $this->save();
            return true;
        }

        return false;
    }
}
