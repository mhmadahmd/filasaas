<?php

namespace Mhmadahmd\Filasaas\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mhmadahmd\Filasaas\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'name' => ['en' => $this->faker->words(3, true)],
            'description' => ['en' => $this->faker->sentence()],
            'is_active' => true,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'signup_fee' => $this->faker->randomFloat(2, 0, 100),
            'currency' => 'USD',
            'trial_period' => 0,
            'trial_interval' => 'day',
            'invoice_period' => 1,
            'invoice_interval' => 'month',
            'grace_period' => 0,
            'grace_interval' => 'day',
            'cash_auto_approve' => false,
            'allowed_payment_gateways' => ['cash', 'stripe', 'paypal'],
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }

    public function withTrial(int $period = 7, string $interval = 'day'): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_period' => $period,
            'trial_interval' => $interval,
        ]);
    }
}

