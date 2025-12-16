<?php

namespace Mhmadahmd\Filasaas\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mhmadahmd\Filasaas\Models\Feature;
use Mhmadahmd\Filasaas\Models\Plan;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'slug' => $this->faker->unique()->slug(),
            'name' => ['en' => $this->faker->words(2, true)],
            'description' => ['en' => $this->faker->sentence()],
            'value' => 'false',
            'resettable_period' => null,
            'resettable_interval' => null,
            'sort_order' => 0,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => 'unlimited',
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => 'true',
        ]);
    }

    public function withLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => (string) $limit,
        ]);
    }

    public function resettable(int $period = 1, string $interval = 'month'): static
    {
        return $this->state(fn (array $attributes) => [
            'resettable_period' => $period,
            'resettable_interval' => $interval,
        ]);
    }
}

