<?php

namespace App\Services\Payments;

use App\Models\Payment;

class FlutterwavePaymentProvider extends AbstractHttpPaymentProvider
{
    public function name(): string
    {
        return 'flutterwave';
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.providers.flutterwave.secret_key'));
    }

    protected function checkoutUrl(): string
    {
        return 'https://api.flutterwave.com/v3/payments';
    }

    protected function headers(): array
    {
        return array_merge(parent::headers(), [
            'Authorization' => 'Bearer '.config('payments.providers.flutterwave.secret_key'),
        ]);
    }

    protected function buildPayload(Payment $payment, array $meta): array
    {
        return [
            'tx_ref' => 'sw_'.$payment->id.'_'.time(),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'redirect_url' => $meta['success_url'] ?? route('student.checkout.success', $payment),
            'customer' => [
                'email' => $payment->user?->email,
                'name' => $payment->user?->name,
            ],
            'customizations' => [
                'title' => $meta['title'] ?? 'StudyWays',
            ],
            'meta' => ['payment_id' => $payment->id],
        ];
    }

    protected function extractRedirectUrl(array $response): ?string
    {
        return $response['data']['link'] ?? null;
    }
}
