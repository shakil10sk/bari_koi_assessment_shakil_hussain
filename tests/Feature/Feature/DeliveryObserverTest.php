<?php

use App\Events\DeliveryStatusChanged;
use App\Models\Delivery;
use App\Models\DeliveryLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant   = Tenant::factory()->create();
    $this->customer = User::factory()->create(['role' => 'customer', 'tenant_id' => $this->tenant->id]);
    $this->driver   = User::factory()->create(['role' => 'driver',   'tenant_id' => $this->tenant->id]);
});

it('automatically creates a log entry when delivery status changes', function () {
    $delivery = Delivery::factory()->create([
        'user_id'   => $this->customer->id,
        'driver_id' => $this->driver->id,
        'status'    => 'pending',
        'tenant_id' => $this->tenant->id,
    ]);

    expect(DeliveryLog::where('delivery_id', $delivery->id)->count())->toBe(0);

    $delivery->update(['status' => 'assigned']);

    $log = DeliveryLog::where('delivery_id', $delivery->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->from_status)->toBe('pending')
        ->and($log->to_status)->toBe('assigned')
        ->and($log->event)->toBe('status_changed');
});

it('does not create a log entry for non-status updates', function () {
    $delivery = Delivery::factory()->create([
        'user_id'   => $this->customer->id,
        'status'    => 'pending',
        'tenant_id' => $this->tenant->id,
    ]);

    $delivery->update(['notes' => 'Handle with care']);

    expect(DeliveryLog::where('delivery_id', $delivery->id)->count())->toBe(0);
});

it('fires DeliveryStatusChanged event on status change', function () {
    Event::fake([DeliveryStatusChanged::class]);

    $delivery = Delivery::factory()->create([
        'user_id'   => $this->customer->id,
        'driver_id' => $this->driver->id,
        'status'    => 'assigned',
        'tenant_id' => $this->tenant->id,
    ]);

    $delivery->update(['status' => 'picked_up']);

    Event::assertDispatched(DeliveryStatusChanged::class, function ($event) use ($delivery) {
        return $event->delivery->id === $delivery->id
            && $event->previousStatus === 'assigned';
    });
});

it('creates separate log entries for each status transition', function () {
    $delivery = Delivery::factory()->create([
        'user_id'   => $this->customer->id,
        'status'    => 'pending',
        'tenant_id' => $this->tenant->id,
    ]);

    $delivery->update(['status' => 'assigned']);
    $delivery->update(['status' => 'picked_up']);
    $delivery->update(['status' => 'in_transit']);

    expect(DeliveryLog::where('delivery_id', $delivery->id)->count())->toBe(3);
});
