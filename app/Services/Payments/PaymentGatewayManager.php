<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentProviderInterface>
     */
    private array $drivers = [];

    public function driver(?string $name = null): PaymentProviderInterface
    {
        $name ??= config('payments.default', 'manual');

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $config = config("payments.providers.{$name}");

        if (! $config) {
            throw new InvalidArgumentException("Payment provider [{$name}] is not configured.");
        }

        $driverClass = match ($config['driver']) {
            'manual' => ManualPaymentProvider::class,
            'stripe' => StripePaymentProvider::class,
            'paypal' => PayPalPaymentProvider::class,
            'flutterwave' => FlutterwavePaymentProvider::class,
            'lemonsqueezy' => LemonSqueezyPaymentProvider::class,
            default => throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported yet."),
        };

        return $this->drivers[$name] = app($driverClass);
    }

    /**
     * @return array<string, string>
     */
    public function availableProviders(): array
    {
        $providers = [];

        foreach (array_keys(config('payments.providers', [])) as $name) {
            try {
                if ($this->driver($name)->isConfigured()) {
                    $providers[$name] = ucfirst($name);
                }
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $providers;
    }
}
