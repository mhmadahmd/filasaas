<?php

namespace Mhmadahmd\Filasaas\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mhmadahmd\Filasaas\Models\Subscription;
use Mhmadahmd\Filasaas\Models\SubscriptionPayment;

class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'gateway' => SubscriptionPayment::GATEWAY_CASH,
            'payment_method' => SubscriptionPayment::METHOD_CASH,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'status' => SubscriptionPayment::STATUS_PENDING,
            'gateway_transaction_id' => null,
            'gateway_response' => null,
            'transaction_id' => null,
            'reference_number' => null,
            'notes' => null,
            'requires_approval' => false,
            'approved_by' => null,
            'approved_at' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionPayment::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionPayment::STATUS_FAILED,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionPayment::STATUS_REFUNDED,
        ]);
    }

    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
            'status' => SubscriptionPayment::STATUS_PENDING,
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => SubscriptionPayment::GATEWAY_STRIPE,
            'payment_method' => SubscriptionPayment::METHOD_ONLINE,
            'gateway_transaction_id' => 'pi_' . $this->faker->unique()->uuid(),
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => SubscriptionPayment::GATEWAY_PAYPAL,
            'payment_method' => SubscriptionPayment::METHOD_ONLINE,
            'gateway_transaction_id' => $this->faker->unique()->uuid(),
        ]);
    }
}

