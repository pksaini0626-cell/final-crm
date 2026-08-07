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
        'follow_up',
        'call_date',
        'remark',
    ];

    protected $casts = [
        'follow_up' => 'boolean',
        'call_date' => 'datetime',
    ];

    /**
     * Relationship: Call log belongs to an Agent (User).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
