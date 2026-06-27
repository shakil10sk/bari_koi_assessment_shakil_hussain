<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    private const DHAKA_AREAS = [
        'Gulshan, Dhaka', 'Banani, Dhaka', 'Dhanmondi, Dhaka',
        'Motijheel, Dhaka', 'Uttara, Dhaka', 'Mirpur, Dhaka',
        'Mohammadpur, Dhaka', 'Tejgaon, Dhaka', 'Bashundhara, Dhaka',
        'Wari, Dhaka', 'Lalbagh, Dhaka', 'Khilgaon, Dhaka',
    ];

    public function definition(): array
    {
        $status    = $this->faker->randomElement(['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed']);
        $createdAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $pickedUp  = in_array($status, ['picked_up', 'in_transit', 'delivered'])
            ? $this->faker->dateTimeBetween($createdAt, '+2 hours')
            : null;
        $delivered = $status === 'delivered'
            ? $this->faker->dateTimeBetween($pickedUp ?? $createdAt, '+8 hours')
            : null;

        return [
            'user_id'          => User::factory(),
            'tracking_number'  => strtoupper($this->faker->bothify('BD######??')),
            'status'           => $status,
            'recipient_name'   => $this->faker->name(),
            'recipient_phone'  => '01' . $this->faker->numberBetween(3, 9) . $this->faker->numerify('########'),
            'pickup_address'   => $this->faker->randomElement(self::DHAKA_AREAS) . ', ' . $this->faker->streetAddress(),
            'pickup_lat'       => $this->faker->randomFloat(7, 23.65, 23.90),
            'pickup_lng'       => $this->faker->randomFloat(7, 90.33, 90.50),
            'delivery_address' => $this->faker->randomElement(self::DHAKA_AREAS) . ', ' . $this->faker->streetAddress(),
            'delivery_lat'     => $this->faker->randomFloat(7, 23.65, 23.90),
            'delivery_lng'     => $this->faker->randomFloat(7, 90.33, 90.50),
            'weight_kg'        => $this->faker->randomFloat(2, 0.1, 25.0),
            'picked_up_at'     => $pickedUp,
            'delivered_at'     => $delivered,
            'created_at'       => $createdAt,
            'updated_at'       => $delivered ?? $pickedUp ?? $createdAt,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'picked_up_at' => null, 'delivered_at' => null]);
    }
}
