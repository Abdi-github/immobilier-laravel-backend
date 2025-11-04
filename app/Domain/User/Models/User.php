<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function newFactory(): \Database\Factories\UserFactory
    {
        return \Database\Factories\UserFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['email', 'user_type', 'status', 'agency_id', 'email_verified_at'])
            ->logOnlyDirty()
            ->useLogName('user');
    }

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'user_type',
        'agency_id',
        'preferred_language',
        'notification_preferences',
        'status',
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
        'password_reset_token',
        'password_reset_expires_at',
        'last_login_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
            'status' => AccountStatus::class,
            'notification_preferences' => 'array',
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'password_reset_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ──

    public function agency(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Agency\Models\Agency::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(\App\Domain\Property\Models\Property::class, 'owner_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\App\Domain\Lead\Models\Lead::class);
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(\App\Domain\Lead\Models\Lead::class, 'assigned_to');
    }

    // ── Accessors ──

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', AccountStatus::ACTIVE);
    }

    public function scopeByType($query, UserType|string $type)
    {
        $value = $type instanceof UserType ? $type->value : $type;

        return $query->where('user_type', $value);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    // ── Helpers ──

    public function isAdmin(): bool
    {
        return in_array($this->user_type, [UserType::SUPER_ADMIN, UserType::PLATFORM_ADMIN]);
    }

    public function isAgencyMember(): bool
    {
        return $this->agency_id !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}
