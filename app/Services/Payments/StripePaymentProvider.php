<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripePaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.providers.stripe.secret'));
    }

    public function charge(Payment $payment, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe n\'est pas configuré.');
        }

        $currency = strtolower($payment->currency ?: config('payments.currency', 'xof'));
        $amount = (int) round((float) $payment->amount);
        $title = $meta['title'] ?? 'StudyWays Payment';

        $response = Http::withToken(config('payments.providers.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => $meta['mode'] ?? 'payment',
                'success_url' => $meta['success_url'],
                'cancel_url' => $meta['cancel_url'],
                'client_reference_id' => (string) $payment->id,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][product_data][name]' => $title,
                'line_items[0][price_data][unit_amount]' => $this->toStripeAmount($amount, $currency),
                'line_items[0][quantity]' => 1,
                'metadata[payment_id]' => (string) $payment->id,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Erreur Stripe : '.$response->json('error.message', 'inconnue'));
        }

        return [
            'completed' => false,
            'redirect_url' => $response->json('url'),
            'provider_payment_id' => $response->json('id'),
        ];
    }

    public function verifySession(Payment $payment, string $sessionId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = Http::withToken(config('payments.providers.stripe.secret'))
            ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);

        if (! $response->successful()) {
            return false;
        }

        $status = $response->json('payment_status');
        $reference = $response->json('client_reference_id');

        return $status === 'paid' && (int) $reference === (int) $payment->id;
    }

    private function toStripeAmount(int $amount, string $currency): int
    {
        $zeroDecimal = in_array($currency, ['xof', 'jpy', 'krw'], true);

        return $zeroDecimal ? $amount : $amount * 100;
    }
}
