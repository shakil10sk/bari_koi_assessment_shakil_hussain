<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\DeliveryLog;
use App\Models\DeliveryRoute;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create two tenants with different subscription plans
        $proTenant = Tenant::factory()->create([
            'name'                  => 'Swift Logistics BD',
            'slug'                  => 'swift-logistics-bd',
            'plan'                  => 'pro',
            'rate_limit_per_minute' => 120,
        ]);

        $basicTenant = Tenant::factory()->create([
            'name'                  => 'Dhaka Express Delivery',
            'slug'                  => 'dhaka-express-delivery',
            'plan'                  => 'basic',
            'rate_limit_per_minute' => 60,
        ]);

        // Create an admin for each tenant
        $admin = User::factory()->create([
            'name'      => 'Admin User',
            'email'     => 'admin@example.com',
            'role'      => 'admin',
            'tenant_id' => $proTenant->id,
        ]);

        // Create drivers
        $drivers = User::factory(5)->create([
            'role'      => 'driver',
            'tenant_id' => $proTenant->id,
        ]);

        // Create customers
        $customers = User::factory(10)->create([
            'role'      => 'customer',
            'tenant_id' => $proTenant->id,
        ]);

        // Create 1000 deliveries — 500 owned by admin, 500 spread across customers
        $deliveries = Delivery::factory(500)
            ->recycle($drivers)
            ->create(['tenant_id' => $proTenant->id, 'user_id' => $admin->id]);

        $deliveries = $deliveries->merge(
            Delivery::factory(500)
                ->recycle($customers)
                ->recycle($drivers)
                ->create(['tenant_id' => $proTenant->id])
        );

        // Seed delivery logs for each delivery (status transitions)
        foreach ($deliveries as $delivery) {
            $statuses = collect(['pending', 'assigned', 'picked_up', 'in_transit'])
                ->take(rand(1, 4));

            $prevStatus = null;
            foreach ($statuses as $status) {
                DeliveryLog::create([
                    'delivery_id' => $delivery->id,
                    'user_id'     => $drivers->random()->id,
                    'from_status' => $prevStatus,
                    'to_status'   => $status,
                    'event'       => 'status_changed',
                    'notes'       => "Status changed to {$status}",
                    'lat'         => fake()->randomFloat(7, 23.65, 23.90),
                    'lng'         => fake()->randomFloat(7, 90.33, 90.50),
                ]);
                $prevStatus = $status;
            }
        }

        // Seed delivery routes for the pro tenant
        DeliveryRoute::create([
            'tenant_id'   => $proTenant->id,
            'name'        => 'Gulshan → Dhanmondi Circuit',
            'description' => 'High-traffic commercial route',
            'waypoints'   => [
                ['lat' => 23.7925, 'lng' => 90.4078, 'address' => 'Gulshan 2 Circle, Dhaka'],
                ['lat' => 23.7759, 'lng' => 90.3988, 'address' => 'Banani 11, Dhaka'],
                ['lat' => 23.7461, 'lng' => 90.3742, 'address' => 'Dhanmondi 27, Dhaka'],
            ],
            'is_active' => true,
        ]);

        $this->command->info('Database seeded successfully.');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Tenants', 2],
                ['Users (admin/driver/customer)', User::count()],
                ['Deliveries', $deliveries->count()],
                ['Delivery Logs', DeliveryLog::count()],
                ['Delivery Routes', DeliveryRoute::count()],
            ]
        );
    }
}
