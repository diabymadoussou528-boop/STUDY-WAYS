<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractHttpPaymentProvider implements PaymentProviderInterface
{
    abstract protected function checkoutUrl(): string;

    abstract protected function buildPayload(Payment $payment, array $meta): array;

    abstract protected function extractRedirectUrl(array $response): ?string;

    public function verifySession(Payment $payment, string $sessionId): bool
    {
        return false;
    }

    public function charge(Payment $payment, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException($this->name().' n\'est pas configuré.');
        }

        $response = Http::withHeaders($this->headers())
            ->post($this->checkoutUrl(), $this->buildPayload($payment, $meta));

        if (! $response->successful()) {
            throw new RuntimeException($this->name().' : '.$response->body());
        }

        $data = $response->json();

        return [
            'completed' => false,
            'redirect_url' => $this->extractRedirectUrl($data),
            'provider_payment_id' => $this->extractProviderId($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function extractProviderId(array $response): ?string
    {
        return $response['id'] ?? $response['data']['id'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return ['Accept' => 'application/json'];
    }
}
