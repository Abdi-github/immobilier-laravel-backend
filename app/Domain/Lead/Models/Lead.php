<?php

declare(strict_types=1);

namespace App\Domain\Lead\Models;

use App\Domain\Lead\Enums\ContactMethod;
use App\Domain\Lead\Enums\InquiryType;
use App\Domain\Lead\Enums\LeadPriority;
use App\Domain\Lead\Enums\LeadSource;
use App\Domain\Lead\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'assigned_to', 'closed_at', 'close_reason'])
            ->logOnlyDirty()
            ->useLogName('lead');
    }
    protected $fillable = [
        'property_id',
        'agency_id',
        'user_id',
        'contact_first_name',
        'contact_last_name',
        'contact_email',
        'contact_phone',
        'preferred_contact_method',
        'preferred_language',
        'inquiry_type',
        'message',
        'status',
        'priority',
        'source',
        'assigned_to',
        'viewing_scheduled_at',
        'follow_up_date',
        'first_response_at',
        'closed_at',
        'close_reason',
    ];

    protected function casts(): array
    {
        return [
            'inquiry_type' => InquiryType::class,
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'source' => LeadSource::class,
            'preferred_contact_method' => ContactMethod::class,
            'viewing_scheduled_at' => 'datetime',
            'follow_up_date' => 'date',
            'first_response_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function property(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Property\Models\Property::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Agency\Models\Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->orderByDesc('created_at');
    }

    // ── Scopes ──

    public function scopeByStatus($query, LeadStatus|string $status)
    {
        $value = $status instanceof LeadStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('follow_up_date', '<=', now()->toDateString())
            ->whereNotIn('status', [LeadStatus::WON, LeadStatus::LOST, LeadStatus::ARCHIVED]);
    }

    // ── Accessors ──

    public function getContactFullNameAttribute(): string
    {
        return "{$this->contact_first_name} {$this->contact_last_name}";
    }
}
