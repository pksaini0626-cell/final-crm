<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'merchant_id',
        'booking_id',
        'created_by',
        'customer_first_name',
        'customer_last_name',
        'email',
        'phone',
        'amount',
        'currency',
        'description',
        'status',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at'    => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = Str::random(40);
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(NmiTransaction::class);
    }

    public function getCustomerFullNameAttribute(): string
    {
        return trim("{$this->customer_first_name} {$this->customer_last_name}");
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return true;
        }

        return false;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function getPublicUrlAttribute(): string
    {
        return route('payment.show', ['token' => $this->token]);
    }
}
