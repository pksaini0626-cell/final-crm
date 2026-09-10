<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'employee_name',
        'alias_name',
        'email',
        'password',
        'contact',
        'extension',
        'role',
        'agent_language',
        'joining_date',
        'is_active',
        'last_login_at',
        // Payroll & HR Profile Fields
        'employee_code',
        'designation',
        'location',
        'leaving_date',
        'tax_regime',
        'pan',
        'gender',
        'account_number',
        'pf_account_number',
        'pf_uan',
        'esi_number',
        'ctc',
        'employment_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joining_date' => 'date',
            'leaving_date' => 'date',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'ctc' => 'decimal:2',
        ];
    }

    /**
     * Get official employee name for payslips and legal documents.
     */
    public function getOfficialNameAttribute(): string
    {
        return $this->employee_name ?: $this->name;
    }

    /**
     * Determine employment type: If joining date crossed 90 days, it is Permanent.
     */
    public function getComputedEmploymentTypeAttribute(): string
    {
        if ($this->joining_date) {
            $daysSinceJoining = \Carbon\Carbon::parse($this->joining_date)->diffInDays(now());
            if ($daysSinceJoining >= 90) {
                return 'Permanent';
            }
        }
        return $this->employment_type ?: 'Probation';
    }

    /**
     * Get or create leave balance for this user.
     */
    public function leaveBalance(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeaveBalance::class);
    }

    /**
     * Get or create earning structure for this user.
     */
    public function earning(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Earning::class);
    }

    /**
     * Get all monthly payslips for this user.
     */
    public function payslips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payslip::class)->orderBy('year', 'desc')->orderBy('month', 'desc');
    }

    /**
     * Get all bookings for the agent.
     */
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'agent_id');
    }

    /**
     * Get all remarks made by the user.
     */
    public function bookingRemarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingRemark::class, 'user_id');
    }
}
