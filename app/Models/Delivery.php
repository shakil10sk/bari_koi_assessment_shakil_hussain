<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'driver_id', 'tenant_id', 'tracking_number', 'status',
        'recipient_name', 'recipient_phone', 'pickup_address', 'pickup_lat',
        'pickup_lng', 'delivery_address', 'delivery_lat', 'delivery_lng',
        'weight_kg', 'notes', 'scheduled_at', 'picked_up_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat'   => 'decimal:7',
            'pickup_lng'   => 'decimal:7',
            'delivery_lat' => 'decimal:7',
            'delivery_lng' => 'decimal:7',
            'weight_kg'    => 'decimal:2',
            'scheduled_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(DeliveryLog::class)->latestOfMany();
    }

    public static function forUserWithLogSummary(int $userId): Builder
    {
        return static::query()
            ->where('user_id', $userId)
            ->withCount('logs')
            ->with(['latestLog'])
            ->latest();
    }
}
