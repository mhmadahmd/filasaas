<?php

namespace Mhmadahmd\Filasaas\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mhmadahmd\Filasaas\Models\Plan;
use Mhmadahmd\Filasaas\Models\Subscription;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $endsAt = (clone $startsAt)->modify('+1 month');

        return [
            'subscriber_type' => \App\Models\User::class,
            'subscriber_id' => 1,
            'plan_id' => Plan::factory(),
            'slug' => $this->faker->slug(),
            'name' => ['en' => $this->faker->words(2, true)],
            'description' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'trial_ends_at' => null,
            'cancels_at' => null,
            'canceled_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startsAt = now()->subDays(5);
            $endsAt = now()->addDays(25);

            return [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'canceled_at' => null,
            ];
        });
    }

    public function canceled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'canceled_at' => now(),
                'cancels_at' => $attributes['ends_at'] ?? now()->addDays(30),
            ];
        });
    }

    public function ended(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subDays(5),
            ];
        });
    }

    public function onTrial(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'trial_ends_at' => now()->addDays(5),
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
            ];
        });
    }
}

