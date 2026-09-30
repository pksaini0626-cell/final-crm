<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargebackControl extends Model
{
    use HasFactory;

    protected $table = 'chargeback_control';

    protected $fillable = [
        'booking_id',
        'booking_reference',
        'portal',
        'case_number',
        'case_type',
        'dispute_type',
        'received_date',
        'received_month',
        'booking_date',
        'booking_month',
        'deadline_date',
        'action_taken_date',
        'cbk_status',
        'current_status',
        'pnr',
        'agent_name',
        'currency',
        'total_booking_amount',
        'disputed_amount',
        'cc_brand',
        'card_no',
        'reason_code',
        'reason_description',
        'vertical',
        'remarks',
        'passenger',
        'service_provided',
        'shift_time',
        'shift_month',
        'statement_month',
        'sds',
        'attachments',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'booking_date' => 'date',
            'deadline_date' => 'date',
            'action_taken_date' => 'date',
            'total_booking_amount' => 'decimal:2',
            'disputed_amount' => 'decimal:2',
            'sds' => 'integer',
            'attachments' => 'array',
        ];
    }

    /**
     * Get the associated booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the user who created this chargeback record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the audit footprints and activities for this chargeback.
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChargebackActivity::class, 'chargeback_id');
    }
}
