<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LemonSqueezyPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'lemonsqueezy';
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.providers.lemonsqueezy.api_key'))
            && filled(config('payments.providers.lemonsqueezy.store_id'));
    }

    public function charge(Payment $payment, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Lemon Squeezy n\'est pas configuré.');
        }

        $response = Http::withToken(config('payments.providers.lemonsqueezy.api_key'))
            ->post('https://api.lemonsqueezy.com/v1/checkouts', [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'checkout_data' => [
                            'email' => $payment->user?->email,
                            'custom' => ['payment_id' => $payment->id],
                        ],
                        'product_options' => [
                            'name' => $meta['title'] ?? 'StudyWays',
                            'redirect_url' => str_replace('{CHECKOUT_SESSION_ID}', (string) $payment->id, $meta['success_url'] ?? ''),
                        ],
                    ],
                    'relationships' => [
                        'store' => [
                            'data' => [
                                'type' => 'stores',
                                'id' => (string) config('payments.providers.lemonsqueezy.store_id'),
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Lemon Squeezy : '.$response->body());
        }

        return [
            'completed' => false,
            'redirect_url' => $response->json('data.attributes.url'),
            'provider_payment_id' => $response->json('data.id'),
        ];
    }

    public function verifySession(Payment $payment, string $sessionId): bool
    {
        return false;
    }
}
