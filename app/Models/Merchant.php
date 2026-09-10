<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'merchant_code',
        'security_key',
        'api_url',
        'tokenization_key',
        'contact_number',
        'support_mail',
        'wallet_balance',
        'is_active',
        'notes',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'from_email',
        'from_name',
        'reply_to_email',
        'reply_to_name',
        'is_smtp_active',
        'code',
        'account_number',
        'currency',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wallet_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'is_smtp_active' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
        ];
    }

    /**
     * Get the bookings associated with the merchant.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'merchant_id');
    }

    /**
     * Get the payment links generated for the merchant.
     */
    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    /**
     * Get the NMI transactions processed by the merchant.
     */
    public function nmiTransactions(): HasMany
    {
        return $this->hasMany(NmiTransaction::class);
    }
}
