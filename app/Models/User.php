<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Stock;
use App\Models\Address;
use App\Models\Setting;
use App\Models\WishlistItem;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // ← THIS LINE

    protected $fillable = [
        'role',
        'first_name',
        'last_name',
        'username',
        'email',
        'phone_number',
        'profile_image',
        'social_provider',
        'gender',
        'date_of_birth',
        'password',
        'email_verified_at',
        'fcm_tokens',
        'push_notifications_enabled',
        'order_updates_enabled',
        'promotional_notifications_enabled',
        'security_alerts_enabled',
        'email_notifications_enabled',
        'last_device_platform',
        'last_app_version',
        'quiet_hours_start',
        'quiet_hours_end',
        'last_notification_at',
        'notification_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_tokens',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'     => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'last_notification_at' => 'datetime',
        'fcm_tokens'        => 'array',
        'push_notifications_enabled' => 'boolean',
        'order_updates_enabled'      => 'boolean',
        'promotional_notifications_enabled' => 'boolean',
        'security_alerts_enabled'    => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end'   => 'datetime:H:i',
    ];

    // Relationships
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    public function setNameAttribute($value): void
    {
        $parts = explode(' ', trim($value), 2);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name']  = $parts[1] ?? '';
    }

    // Scopes — Critical for your CustomerController
    public function scopeCustomers($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    public function scopeWithOrderStats($query)
    {
        return $query->withCount('orders')
                     ->withSum('orders', 'total_amount');
    }

    // FCM & Notification Methods
    public function addFcmToken(string $token, string $deviceId, string $platform = 'flutter', string $appVersion = '1.0.0'): void
    {
        $tokens = $this->fcm_tokens ?? [];
        $tokens = array_filter($tokens, fn($item) => $item['device_id'] !== $deviceId);

        $tokens[] = [
            'token'       => $token,
            'device_id'   => $deviceId,
            'platform'    => $platform,
            'app_version' => $appVersion,
            'added_at'    => now()->toISOString(),
            'last_used_at'=> now()->toISOString(),
        ];

        $this->update([
            'fcm_tokens'           => $tokens,
            'last_device_platform' => $platform,
            'last_app_version'     => $appVersion,
        ]);
    }

    public function removeFcmToken(string $deviceId): void
    {
        $tokens = $this->fcm_tokens ?? [];
        $tokens = array_filter($tokens, fn($item) => $item['device_id'] !== $deviceId);
        $this->update(['fcm_tokens' => array_values($tokens)]);
    }

    public function clearAllFcmTokens(): void
    {
        $this->update(['fcm_tokens' => []]);
    }

    public function getActiveFcmTokens(): array
    {
        return array_column($this->fcm_tokens ?? [], 'token');
    }

    public function hasActiveFcmTokens(): bool
    {
        return !empty($this->getActiveFcmTokens());
    }

    public function canReceivePushNotifications(string $type = 'general'): bool
    {
        if (!($this->push_notifications_enabled ?? true)) return false;

        return match ($type) {
            'order_update'  => $this->order_updates_enabled ?? true,
            'promotional'   => $this->promotional_notifications_enabled ?? false,
            'security'      => $this->security_alerts_enabled ?? true,
            default         => true,
        } && !$this->isInQuietHours() && $this->hasActiveFcmTokens();
    }

    public function canReceiveEmailNotifications(string $type = 'general'): bool
    {
        return ($this->email_notifications_enabled ?? true) && $this->hasVerifiedEmail();
    }

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) return false;

        $now   = now()->format('H:i');
        $start = $this->quiet_hours_start->format('H:i');
        $end   = $this->quiet_hours_end->format('H:i');

        if ($start < $end) {
            return $now >= $start && $now <= $end;
        } else {
            return $now >= $start || $now <= $end;
        }
    }

    public function recordNotificationSent(): void
    {
        $this->update([
            'last_notification_at' => now(),
            'notification_count'   => ($this->notification_count ?? 0) + 1,
        ]);
    }

    public function getNotificationPreferences(): array
    {
        return [
            'push_notifications_enabled' => $this->push_notifications_enabled ?? true,
            'order_updates_enabled'      => $this->order_updates_enabled ?? true,
            'promotional_notifications_enabled' => $this->promotional_notifications_enabled ?? false,
            'security_alerts_enabled'    => $this->security_alerts_enabled ?? true,
            'email_notifications_enabled' => $this->email_notifications_enabled ?? true,
            'quiet_hours' => [
                'start' => $this->quiet_hours_start?->format('H:i'),
                'end'   => $this->quiet_hours_end?->format('H:i'),
            ],
            'has_active_tokens' => $this->hasActiveFcmTokens(),
            'device_count'      => count($this->fcm_tokens ?? []),
        ];
    }

  
}
