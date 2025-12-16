<?php

namespace Mhmadahmd\Filasaas\Services;

use Illuminate\Contracts\Foundation\Application;
use Mhmadahmd\Filasaas\Contracts\PaymentGatewayInterface;
use Mhmadahmd\Filasaas\Models\Plan;
use Mhmadahmd\Filasaas\Models\SubscriptionPayment;
use Mhmadahmd\Filasaas\Services\Gateways\CashGateway;
use Mhmadahmd\Filasaas\Services\Gateways\PayPalGateway;
use Mhmadahmd\Filasaas\Services\Gateways\StripeGateway;

class PaymentGatewayManager
{
    protected array $gateways = [];

    public function __construct(Application $app)
    {
        $this->registerDefaultGateways($app);
    }

    protected function registerDefaultGateways(Application $app): void
    {
        // Register Cash Gateway (always register if enabled, default is true)
        if (config('filasaas.gateways.cash.enabled', true)) {
            if (! $this->get('cash')) {
                $this->register('cash', new CashGateway);
            }
        }

        // Register Stripe Gateway
        if (config('filasaas.gateways.stripe.enabled', false)) {
            if (! $this->get('stripe')) {
                try {
                    $this->register('stripe', new StripeGateway);
                } catch (\Exception $e) {
                    // Stripe not configured, skip
                }
            }
        }

        // Register PayPal Gateway
        if (config('filasaas.gateways.paypal.enabled', false)) {
            if (! $this->get('paypal')) {
                try {
                    $this->register('paypal', new PayPalGateway);
                } catch (\Exception $e) {
                    // PayPal not configured, skip
                }
            }
        }
    }

    /**
     * Ensure gateways are registered (useful if called before full initialization)
     */
    protected function ensureGatewaysRegistered(): void
    {
        if (empty($this->gateways)) {
            $this->registerDefaultGateways(app());
        }
    }

    public function register(string $identifier, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$identifier] = $gateway;
    }

    public function get(string $identifier): ?PaymentGatewayInterface
    {
        return $this->gateways[$identifier] ?? null;
    }

    public function getAll(): array
    {
        return $this->gateways;
    }

    public function getAvailableForPlan(Plan $plan): array
    {
        // Ensure gateways are registered
        $this->ensureGatewaysRegistered();

        $allowedGateways = $plan->getAllowedGateways();
        $available = [];

        // If no gateways are allowed, return empty array
        if (empty($allowedGateways) || ! is_array($allowedGateways)) {
            // If plan has no gateways set, show all enabled gateways as fallback
            foreach ($this->gateways as $identifier => $gateway) {
                $configKey = "filasaas.gateways.{$identifier}.enabled";
                // Cash gateway is always available if enabled (defaults to true)
                if ($identifier === 'cash' && config($configKey, true)) {
                    $available[$identifier] = $gateway;
                } elseif ($identifier !== 'cash' && config($configKey, false)) {
                    $available[$identifier] = $gateway;
                }
            }
            
            return $available;
        }

        // Plan has specific gateways set, use those
        foreach ($allowedGateways as $gatewayIdentifier) {
            if (empty($gatewayIdentifier)) {
                continue;
            }

            $gateway = $this->get($gatewayIdentifier);
            
            // Skip if gateway is not registered
            // If gateway is registered, it means it passed the enabled check during registration
            if (! $gateway) {
                continue;
            }

            // If gateway is registered, it's available (registration already checked if it's enabled)
            $available[$gatewayIdentifier] = $gateway;
        }

        return $available;
    }

    public function processPayment(SubscriptionPayment $payment): mixed
    {
        $gateway = $this->get($payment->gateway);

        if (! $gateway) {
            throw new \Exception("Gateway '{$payment->gateway}' not found.");
        }

        return $gateway->processPayment($payment);
    }

    public function isGatewayAvailable(string $gateway, Plan $plan): bool
    {
        // Check if gateway is registered
        if (! $this->get($gateway)) {
            return false;
        }

        // Check if gateway is enabled in config
        $configKey = "filasaas.gateways.{$gateway}.enabled";
        if (! config($configKey, false) && $gateway !== 'cash') {
            return false;
        }

        // Check if plan allows this gateway
        $allowedGateways = $plan->getAllowedGateways();
        if (! in_array($gateway, $allowedGateways)) {
            return false;
        }

        return true;
    }
}
