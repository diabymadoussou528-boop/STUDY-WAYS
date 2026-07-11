<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $meta
     * @return array{completed: bool, redirect_url?: string, provider_payment_id?: string}
     */
    public function charge(Payment $payment, array $meta = []): array;

    public function verifySession(Payment $payment, string $sessionId): bool;
}
