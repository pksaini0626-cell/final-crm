<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
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
        'approved_by_id',
        'request_type',
        'refund_amount',
        'currency',
        'refund_date',
        'reason_for_refund',
        'remarks',
        'mis_remarks',
        'admin_remarks',
        'status',
        'deduction_from_agent',
        'email_sent_to_agent_by',
        'receipt_sent_to_cs',
        'is_duplicate',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'refund_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Refund request belongs to a Booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Relationship: Refund request created by Agent.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relationship: Refund request approved/actioned by Admin/Master Admin/MIS.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Alias for approver relationship.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Compute refund age in days: booking_date - refund_date.
     */
    public function getRefundAgeDaysAttribute(): int
    {
        if ($this->booking && $this->booking->booking_date && $this->refund_date) {
            $bookingDate = Carbon::parse($this->booking->booking_date)->startOfDay();
            $refundDate = Carbon::parse($this->refund_date)->startOfDay();
            return abs($bookingDate->diffInDays($refundDate));
        }
        return 0;
    }

    /**
     * Get refund month name and year (e.g. September 2026).
     */
    public function getRefundMonthAttribute(): string
    {
        return $this->refund_date ? Carbon::parse($this->refund_date)->format('F Y') : 'N/A';
    }

    /**
     * Get actioned month name and year (e.g. September 2026).
     */
    public function getActionedMonthAttribute(): string
    {
        if ($this->approved_at) {
            return Carbon::parse($this->approved_at)->format('F Y');
        }
        return $this->updated_at ? Carbon::parse($this->updated_at)->format('F Y') : 'N/A';
    }

    /**
     * Format display request type.
     */
    public function getFormattedRequestTypeAttribute(): string
    {
        return match ($this->request_type) {
            'void' => 'Void',
            'partial_void' => 'Partial Void',
            'refund' => 'Refund',
            default => ucfirst(str_replace('_', ' ', $this->request_type)),
        };
    }
}
