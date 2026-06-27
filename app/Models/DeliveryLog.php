<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLog extends Model
{
    protected $fillable = [
        'delivery_id', 'user_id', 'from_status', 'to_status',
        'event', 'notes', 'metadata', 'lat', 'lng',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'lat'      => 'decimal:7',
            'lng'      => 'decimal:7',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
