<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ChargebackActivity extends Model
{
    use HasFactory;

    protected $table = 'chargeback_activities';

    protected $fillable = [
        'user_id',
        'chargeback_id',
        'case_number',
        'pnr',
        'action',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * User who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Linked chargeback record if still exists.
     */
    public function chargeback(): BelongsTo
    {
        return $this->belongsTo(ChargebackControl::class, 'chargeback_id');
    }

    /**
     * Helper to conveniently record an activity from any controller or listener.
     */
    public static function record(array $data): self
    {
        if (empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        if (empty($data['ip_address']) && Request::hasSession()) {
            $data['ip_address'] = Request::ip();
        }

        if (empty($data['user_agent']) && Request::hasSession()) {
            $data['user_agent'] = Request::userAgent();
        }

        return self::create($data);
    }
}
