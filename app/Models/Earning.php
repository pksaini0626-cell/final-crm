<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'basic_salary',
        'hra',
        'conveyance_allowance',
        'medical_allowance',
        'special_allowance',
        'other_payments',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'hra' => 'decimal:2',
            'conveyance_allowance' => 'decimal:2',
            'medical_allowance' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'other_payments' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Compute total rate of regular salary package.
     */
    public function getTotalRateAttribute(): float
    {
        return (float) (
            $this->basic_salary +
            $this->hra +
            $this->conveyance_allowance +
            $this->medical_allowance +
            $this->special_allowance +
            $this->other_payments
        );
    }
}
