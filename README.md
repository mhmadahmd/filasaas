# Filasaas - Multi-Gateway Billing and Subscription System

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mhmadahmd/filasaas.svg?style=flat-square)](https://packagist.org/packages/mhmadahmd/filasaas)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mhmadahmd/filasaas/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mhmadahmd/filasaas/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mhmadahmd/filasaas/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mhmadahmd/filasaas/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mhmadahmd/filasaas.svg?style=flat-square)](https://packagist.org/packages/mhmadahmd/filasaas)

A comprehensive Filament plugin for billing and subscription management with multi-gateway support. Supports Cash payments (with configurable approval workflow), Stripe (via Laravel Cashier), PayPal (via Laravel package), and extensible custom local gateways.

## Features

- **Multi-Gateway Support**: Cash, Stripe, PayPal, and extensible custom gateways
- **Configurable Cash Approval**: Plans can auto-approve or require manual admin approval
- **Unified Gateway Interface**: All payment methods use the same interface
- **Plan-Based Gateway Restrictions**: Plans can limit which payment methods are available
- **Admin Approval Workflow**: Manual approval system for cash payments with audit trail
- **Webhook Support**: Handle payment status updates from all gateways
- **Subscription Management**: Full lifecycle management (create, cancel, switch, renew)
- **Feature-Based Plans**: Plans with features and usage tracking
- **Trial & Grace Periods**: Support for trial periods and grace periods
- **Filament Integration**: Complete Filament 4 resources, pages, and actions

## Installation

```bash
composer require mhmadahmd/filasaas
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filasaas-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="filasaas-config"
```

## Configuration

After publishing the config file, configure your payment gateways in `config/filasaas.php`:

```php
'gateways' => [
    'cash' => [
        'enabled' => true,
        'default_approval_mode' => 'manual', // 'auto' or 'manual'
    ],
    'stripe' => [
        'enabled' => true,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'paypal' => [
        'enabled' => true,
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],
],
```

## Quick Start

### 1. Add Trait to Billable Model

Add the `HasPlanSubscriptions` trait to your User model (or any billable model):

```php
use Mhmadahmd\Filasaas\Traits\HasPlanSubscriptions;

class User extends Authenticatable
{
    use HasPlanSubscriptions;
    // ...
}
```

### 2. Register the Plugin

Register the plugin in your Filament panel provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Mhmadahmd\Filasaas\FilasaasPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other panel configuration
        ->plugin(FilasaasPlugin::make());
}
```

**Important:** Use `->plugin()` (singular) and pass the plugin instance directly, not an array.

### 3. Create a Plan

Use the Plan resource in Filament to create subscription plans with features.

### 4. Subscribe Users

Users can subscribe through the Billing page or programmatically:

```php
use Mhmadahmd\Filasaas\Services\TenantBillingProvider;

$billingProvider = app(TenantBillingProvider::class);
$subscription = $billingProvider->subscribeToPlan($planId, 'cash');
```

## Usage Examples

### Check if User is Subscribed

```php
$user = User::find(1);
$billingProvider = app(TenantBillingProvider::class);

if ($billingProvider->isSubscribedTo($planId, $user)) {
    // User is subscribed
}
```

### Cancel Subscription

```php
$billingProvider = app(TenantBillingProvider::class);
$billingProvider->cancelSubscription($subscriptionId, $immediately = false);
```

### Record Feature Usage

```php
$subscription = Subscription::find(1);
$subscription->recordFeatureUsage($featureId, $uses = 1);
```

### Check Feature Access

```php
if ($subscription->canUseFeature($featureId)) {
    // User can use this feature
}
```

## Gateway Setup

### Cash Gateway

The cash gateway is enabled by default. Configure auto-approval per plan:

```php
$plan->cash_auto_approve = true; // Auto-approve
$plan->cash_auto_approve = false; // Require manual approval
```

### Stripe Gateway

1. Install Laravel Cashier (optional but recommended):
```bash
composer require laravel/cashier-stripe
```

2. Configure Stripe credentials in `.env`:
```
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

3. Enable Stripe in config:
```php
'stripe' => [
    'enabled' => true,
    // ...
],
```

### PayPal Gateway

1. Configure PayPal credentials in `.env`:
```
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
```

2. Enable PayPal in config:
```php
'paypal' => [
    'enabled' => true,
    // ...
],
```

3. Set up PayPal webhook URL: `https://yourdomain.com/webhooks/billing/paypal`

### Custom Gateways

See [CUSTOM_GATEWAYS.md](docs/CUSTOM_GATEWAYS.md) for detailed instructions on implementing custom local gateways.

## API Documentation

### TenantBillingProvider

Main service for subscription management:

- `subscribeToPlan(int $planId, string $gateway, array $options = []): Subscription`
- `cancelSubscription(int $subscriptionId, bool $immediately = false): Subscription`
- `switchPlan(int $subscriptionId, int $newPlanId, array $options = []): Subscription`
- `getCurrentSubscriptions(): Collection`
- `getActiveSubscriptions(): Collection`
- `getAvailablePlans(): Collection`
- `getPaymentHistory(?int $limit = 10): Collection`
- `hasActiveSubscription(): bool`
- `isSubscribedTo(int $planId): bool`

### PaymentGatewayManager

Service for managing payment gateways:

- `register(string $identifier, PaymentGatewayInterface $gateway): void`
- `get(string $identifier): ?PaymentGatewayInterface`
- `getAll(): array`
- `getAvailableForPlan(Plan $plan): array`
- `processPayment(SubscriptionPayment $payment): mixed`
- `isGatewayAvailable(string $gateway, Plan $plan): bool`

## Middleware

Use the `VerifyBillableIsSubscribed` middleware to protect routes:

```php
Route::middleware([\Mhmadahmd\Filasaas\Middleware\VerifyBillableIsSubscribed::class])
    ->group(function () {
        // Protected routes
    });
```

## Webhooks

Webhook routes are automatically registered:

- PayPal: `POST /webhooks/billing/paypal`
- Custom Gateways: `POST /webhooks/billing/{gateway}`

## Testing

```bash
composer test
```

## Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [Custom Gateways Guide](docs/CUSTOM_GATEWAYS.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohammad Ahmad](https://github.com/mhmadahmd)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
