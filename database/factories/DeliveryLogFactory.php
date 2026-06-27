<?php

namespace Database\Factories;

use App\Models\DeliveryLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryLog>
 */
class DeliveryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses   = ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed'];
        $fromStatus = $this->faker->randomElement($statuses);
        $toStatus   = $this->faker->randomElement($statuses);

        return [
            'delivery_id' => \App\Models\Delivery::factory(),
            'user_id'     => \App\Models\User::factory(),
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'event'       => 'status_changed',
            'notes'       => "Status changed from {$fromStatus} to {$toStatus}",
            'metadata'    => ['source' => 'factory'],
            'lat'         => $this->faker->randomFloat(7, 23.65, 23.90),
            'lng'         => $this->faker->randomFloat(7, 90.33, 90.50),
        ];
    }
}
