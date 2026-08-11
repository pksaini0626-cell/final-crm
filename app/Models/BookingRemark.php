<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingRemark extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'remark',
        'type',
        'attachments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * Append calculated attachment data for frontend rendering.
     */
    protected $appends = ['attachments_data'];

    public function getAttachmentsDataAttribute(): array
    {
        $raw = $this->attachments;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (empty($raw) || !is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($att) {
            if (is_string($att)) {
                $filename = basename($att);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg']);
                return [
                    'file_path' => $att,
                    'original_name' => $filename,
                    'file_type' => $isImg ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file'),
                    'file_url' => asset('storage/' . ltrim($att, '/')),
                ];
            }

            if (is_array($att)) {
                $filePath = $att['file_path'] ?? ($att['path'] ?? '');
                $filename = $att['original_name'] ?? ($att['name'] ?? basename($filePath));
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (empty($ext) && $filePath) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                }

                $isImg = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg']) || (isset($att['file_type']) && $att['file_type'] === 'image');
                $fileType = $isImg ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file');

                $url = !empty($filePath) ? asset('storage/' . ltrim($filePath, '/')) : ($att['file_url'] ?? '#');

                return array_merge($att, [
                    'file_path' => $filePath,
                    'original_name' => $filename ?: 'Attachment',
                    'file_type' => $fileType,
                    'file_url' => $url,
                ]);
            }

            return null;
        }, $raw)));
    }

    /**
     * Get the booking that owns the remark.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the user who wrote the remark.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
