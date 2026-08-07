<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'card_holder_name',
        'card_type',
        'card_last_4',
        'card_expiration',
    ];

    /**
     * Relationship: Card belongs to a Booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
