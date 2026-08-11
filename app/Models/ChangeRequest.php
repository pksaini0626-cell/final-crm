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
        'request_type',
        'change_request_text',
        'agent_remark',
        'changes_remark',
        'paid_to_airline',
        'fop',
        'final_remark',
        'closed_by_user_id',
        'attachments',
        'status',
        'assigned_at',
        'working_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'working_at' => 'datetime',
        'completed_at' => 'datetime',
        'attachments' => 'array',
    ];

    protected $appends = ['attachments_data'];

    /**
     * Accessor for attachments data with full URLs and type resolution.
     */
    public function getAttachmentsDataAttribute(): array
    {
        $attachments = $this->attachments;
        if (empty($attachments) || !is_array($attachments)) {
            return [];
        }

        $formatted = [];
        foreach ($attachments as $item) {
            $path = is_array($item) ? ($item['file_path'] ?? '') : (is_string($item) ? $item : '');
            $originalName = is_array($item) ? ($item['original_name'] ?? basename($path)) : basename($path);
            $fileType = is_array($item) ? ($item['file_type'] ?? null) : null;

            if (!$fileType && $path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $fileType = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg']) ? 'image' : 'pdf';
            }

            if ($path) {
                $formatted[] = [
                    'file_path' => $path,
                    'file_url' => asset('storage/' . ltrim($path, '/')),
                    'original_name' => $originalName ?: 'attachment',
                    'file_type' => $fileType ?: 'document',
                ];
            }
        }

        return $formatted;
    }

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

    /**
     * Relationship: Change request closed by Changes Team member (User).
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
