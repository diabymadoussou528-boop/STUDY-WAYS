<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class PayPalPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.providers.paypal.client_id'))
            && filled(config('payments.providers.paypal.client_secret'));
    }

    public function charge(Payment $payment, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PayPal n\'est pas configuré.');
        }

        $token = $this->accessToken();
        $response = Http::withToken($token)
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $payment->id,
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => number_format((float) $payment->amount, 2, '.', ''),
                    ],
                    'description' => $meta['title'] ?? 'StudyWays',
                ]],
                'application_context' => [
                    'return_url' => str_replace('{CHECKOUT_SESSION_ID}', (string) $payment->id, $meta['success_url'] ?? ''),
                    'cancel_url' => $meta['cancel_url'] ?? route('home'),
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('PayPal : '.$response->body());
        }

        $approve = collect($response->json('links', []))->firstWhere('rel', 'approve');

        return [
            'completed' => false,
            'redirect_url' => $approve['href'] ?? null,
            'provider_payment_id' => $response->json('id'),
        ];
    }

    public function verifySession(Payment $payment, string $sessionId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = $this->accessToken();
        $response = Http::withToken($token)->get($this->baseUrl().'/v2/checkout/orders/'.$sessionId);

        return $response->successful() && $response->json('status') === 'APPROVED';
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(
                config('payments.providers.paypal.client_id'),
                config('payments.providers.paypal.client_secret'),
            )
            ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        return $response->json('access_token') ?? throw new \RuntimeException('PayPal auth failed');
    }

    private function baseUrl(): string
    {
        return config('app.env') === 'production'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
