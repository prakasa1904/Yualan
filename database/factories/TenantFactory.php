<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
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
        return [
            'id' => fake()->uuid(),
            'name' => fake()->company(),
            'slug' => fake()->slug(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'owner_name' => fake()->name(),
            'owner_email' => fake()->email(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonth(),
            'ipaymu_api_key' => null,
            'ipaymu_secret_key' => null,
            'ipaymu_mode' => 'sandbox',
        ];
    }
}
