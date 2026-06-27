<?php

use App\Models\Delivery;
use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['plan' => 'basic', 'rate_limit_per_minute' => 60]);
    $this->user   = User::factory()->create(['role' => 'customer', 'tenant_id' => $this->tenant->id]);
    $this->driver = User::factory()->create(['role' => 'driver', 'tenant_id' => $this->tenant->id]);
});

it('returns cursor-paginated deliveries for authenticated user', function () {
    Delivery::factory(5)->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->getJson('/api/v1/deliveries?limit=3')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [['id', 'tracking_number', 'status']],
            'meta' => ['next_cursor', 'has_more', 'limit'],
        ])
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.has_more', true);
});

it('creates a delivery and returns 201', function () {
    $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->postJson('/api/v1/deliveries', [
            'recipient_name'   => 'Rahim Uddin',
            'recipient_phone'  => '01712345678',
            'pickup_address'   => 'Gulshan 1, Dhaka',
            'delivery_address' => 'Dhanmondi 15, Dhaka',
            'pickup_lat'       => 23.7925,
            'pickup_lng'       => 90.4078,
            'delivery_lat'     => 23.7461,
            'delivery_lng'     => 90.3742,
        ])
        ->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'tracking_number', 'status']])
        ->assertJsonPath('data.status', 'pending');
});

it('prevents a customer from viewing another user\'s delivery', function () {
    $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $delivery  = Delivery::factory()->create(['user_id' => $otherUser->id, 'tenant_id' => $this->tenant->id]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->getJson("/api/v1/deliveries/{$delivery->id}")
        ->assertStatus(403);
});

it('allows driver to update status of their assigned delivery', function () {
    $delivery = Delivery::factory()->create([
        'user_id'   => $this->user->id,
        'driver_id' => $this->driver->id,
        'status'    => 'assigned',
        'tenant_id' => $this->tenant->id,
    ]);

    $this->actingAs($this->driver)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->patchJson("/api/v1/deliveries/{$delivery->id}", ['status' => 'picked_up'])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'picked_up');
});

it('rejects unauthenticated delivery requests', function () {
    $this->getJson('/api/v1/deliveries')->assertStatus(401);
});

it('v2 response includes nested assigned_agent object', function () {
    $delivery = Delivery::factory()->create([
        'user_id'   => $this->user->id,
        'driver_id' => $this->driver->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->getJson("/api/v2/deliveries/{$delivery->id}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'assigned_agent' => ['id', 'name', 'email', 'phone', 'role'],
                'pickup'         => ['address', 'lat', 'lng'],
                'destination'    => ['address', 'recipient', 'phone'],
            ],
        ]);
});

it('v1 responses include Deprecation and Sunset headers', function () {
    $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-Key' => $this->tenant->api_key])
        ->getJson('/api/v1/deliveries')
        ->assertHeader('Deprecation', 'true')
        ->assertHeader('Sunset');
});
