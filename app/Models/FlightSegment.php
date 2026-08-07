<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightSegment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'flight_segments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'segment_number',
        'flight_number',
        'operating_carrier',
        'airline_name',
        'airline_logo',
        'operated_by',
        'operated_by_logo',
        'origin_airport',
        'origin_city',
        'origin_airport_name',
        'destination_airport',
        'destination_city',
        'destination_airport_name',
        'departure_time',
        'arrival_time',
        'booking_class',
        'cabin',
        'aircraft_type',
        'status',
        'flight_duration',
        'day_offset',
        'transit_text',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
            'segment_number' => 'integer',
            'day_offset' => 'integer',
        ];
    }

    /**
     * Get the booking that owns the flight segment.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
