<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NmiTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'booking_id',
        'payment_link_id',
        'order_id',
        'transaction_id',
        'type',
        'customer_first_name',
        'customer_last_name',
        'email',
        'card_last4',
        'card_brand',
        'address1',
        'city',
        'state',
        'zip',
        'country',
        'amount',
        'currency',
        'status',
        'processed_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'processed_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    public function getCustomerFullNameAttribute(): string
    {
        return trim("{$this->customer_first_name} {$this->customer_last_name}");
    }
}
