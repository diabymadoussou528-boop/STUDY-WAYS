<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;

class ManualPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function charge(Payment $payment, array $meta = []): array
    {
        return [
            'completed' => true,
            'provider_payment_id' => 'manual_'.uniqid(),
        ];
    }

    public function verifySession(Payment $payment, string $sessionId): bool
    {
        return false;
    }
}
