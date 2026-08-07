<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'agent_id',
        'assigned_changes_user_id',
        'change_request_text',
        'agent_remark',
        'changes_remark',
        'status',
        'assigned_at',
        'working_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'working_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship: Change request belongs to a Booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Relationship: Change request created by Agent (User).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relationship: Change request assigned to Changes Team member (User).
     */
    public function changesAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_changes_user_id');
    }
}
