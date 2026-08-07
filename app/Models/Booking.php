<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'agent_id',
        'ticketing_user_id',
        'merchant_id',
        'booking_date',
        'call_type',
        'vertical',
        'trip_type',
        'service_provided',
        'booking_portal',
        'gk_pnr',
        'airline_pnr',
        'airline_name',
        'airline_code',
        'from_airport',
        'to_airport',
        'from_city',
        'to_city',
        'travel_date',
        'language',
        'card_holder_name',
        'card_type',
        'calling_number',
        'billing_phone',
        'billing_address',
        'card_last_4',
        'card_expiration',
        'email_address',
        'booking_status',
        'case_status',
        'email_auth_taken',
        'currency',
        'merchant',
        'total_amount',
        'paid_to_airline',
        'total_mco',
        'payment_status',
        'payment_info',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'travel_date' => 'date',
            'email_auth_taken' => 'boolean',
            'total_amount' => 'decimal:2',
            'paid_to_airline' => 'decimal:2',
            'total_mco' => 'decimal:2',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_id)) {
                $booking->booking_id = static::generateUniqueBookingId();
            }
        });
    }

    /**
     * Generate a unique 7-character uppercase alphanumeric string for booking_id.
     */
    public static function generateUniqueBookingId(): string
    {
        do {
            // Generate random 7-character alphanumeric string, then uppercase it
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $id = '';
            for ($i = 0; $i < 7; $i++) {
                $id .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (static::where('booking_id', $id)->exists());

        return $id;
    }

    /**
     * Accessor / Mutator for total_mco.
     */
    protected function totalMco(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (float)$value,
            set: fn ($value) => [
                'total_mco' => (float)$value,
            ]
        );
    }

    /**
     * Get the agent who owns the booking.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the ticketing agent assigned to the booking.
     */
    public function ticketingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ticketing_user_id');
    }

    /**
     * Get the merchant associated with the booking.
     */
    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Get the passengers for the booking.
     */
    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class, 'booking_id');
    }

    /**
     * Get the flights for the booking.
     */
    public function bookingFlights(): HasMany
    {
        return $this->hasMany(BookingFlight::class, 'booking_id');
    }

    /**
     * Get the flight segments for the booking.
     */
    public function flightSegments(): HasMany
    {
        return $this->hasMany(FlightSegment::class, 'booking_id')->orderBy('segment_number');
    }

    /**
     * Get the remarks for the booking.
     */
    public function bookingRemarks(): HasMany
    {
        return $this->hasMany(BookingRemark::class, 'booking_id');
    }

    /**
     * Get the multiple cards for the booking.
     */
    public function bookingCards(): HasMany
    {
        return $this->hasMany(BookingCard::class, 'booking_id');
    }

    /**
     * Get the change requests for the booking.
     */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class, 'booking_id')->latest();
    }

    /**
     * Get the latest change request for the booking.
     */
    public function latestChangeRequest(): HasOne
    {
        return $this->hasOne(ChangeRequest::class, 'booking_id')->latestOfMany();
    }
}
