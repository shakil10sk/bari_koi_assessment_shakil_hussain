<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'                  => $name,
            'slug'                  => \Illuminate\Support\Str::slug($name) . '-' . $this->faker->unique()->numberBetween(100, 999),
            'api_key'               => \Illuminate\Support\Str::random(64),
            'plan'                  => $this->faker->randomElement(['free', 'basic', 'pro', 'enterprise']),
            'rate_limit_per_minute' => $this->faker->randomElement([30, 60, 120, 300]),
            'is_active'             => true,
        ];
    }
}
