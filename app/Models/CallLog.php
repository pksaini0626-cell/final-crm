<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'customer_name',
        'phone_number',
        'email',
        'city',
        'service_provided',
        'follow_up',
        'call_date',
        'remark',
    ];

    protected $casts = [
        'follow_up' => 'boolean',
        'call_date' => 'datetime',
    ];

    /**
     * Helper to get human-readable label for service_provided (Call Type).
     */
    public function getServiceProvidedLabelAttribute(): string
    {
        $labels = [
            'new_booking' => 'New Booking',
            'exchange' => 'Exchange',
            'cancellation' => 'Cancellation',
            'refund' => 'Refund',
            'seat_selection' => 'Seat Selection',
            'baggage_addition' => 'Baggage Edition',
            'others' => 'Others',
            'cancel_and_refund' => 'Cancel and Refund',
            'name_correction' => 'Name Correction',
            'flight_upgrade' => 'Flight Upgrade',
            'dob_correction' => 'D.O.B Correction',
            'pet_in_cabin' => 'Pet In Cabin',
            'ancillary_refund' => 'Ancillary Refund',
            'general_inquiry' => 'General Inquiry',
            'infant_ticket' => 'Infant Ticket',
        ];

        return $labels[$this->service_provided] ?? ($this->service_provided ? ucfirst(str_replace('_', ' ', $this->service_provided)) : 'N/A');
    }

    /**
     * Relationship: Call log belongs to an Agent (User).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
