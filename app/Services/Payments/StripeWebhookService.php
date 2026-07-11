<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\ProcessedWebhookEvent;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    private const TOLERANCE_SECONDS = 300;

    public function __construct(
        private PaymentService $paymentService,
        private ReceiptService $receipts,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, ?string $rawPayload = null, ?string $signature = null): void
    {
        $secret = config('payments.providers.stripe.webhook_secret');

        if (app()->environment('production') && blank($secret)) {
            abort(403, 'Webhook secret required.');
        }

        if ($secret) {
            if (! $this->verifySignature($rawPayload ?? '', $signature, $secret)) {
                abort(400, 'Invalid signature');
            }
        }

        $eventId = $payload['id'] ?? null;
        $type = $payload['type'] ?? null;

        if (! $eventId || ! $type) {
            return;
        }

        if (ProcessedWebhookEvent::alreadyProcessed('stripe', $eventId)) {
            return;
        }

        match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->handleCheckoutCompleted($payload),
            'checkout.session.async_payment_failed' => $this->handleCheckoutFailed($payload),
            default => null,
        };

        ProcessedWebhookEvent::record('stripe', $eventId, $type);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutCompleted(array $payload): void
    {
        $session = $payload['data']['object'] ?? [];
        $payment = $this->resolvePayment($session);

        if (! $payment || $payment->status === 'completed') {
            return;
        }

        if (($session['payment_status'] ?? 'paid') !== 'paid') {
            return;
        }

        $this->paymentService->complete($payment);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutFailed(array $payload): void
    {
        $session = $payload['data']['object'] ?? [];
        $payment = $this->resolvePayment($session);

        if (! $payment || $payment->status === 'completed') {
            return;
        }

        $this->receipts->markFailed($payment, 'Paiement Stripe échoué ou annulé.');

        Log::warning('Stripe async payment failed', [
            'payment_id' => $payment->id,
            'session_id' => $session['id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function resolvePayment(array $session): ?Payment
    {
        $paymentId = (int) ($session['client_reference_id'] ?? $session['metadata']['payment_id'] ?? 0);

        if ($paymentId <= 0) {
            return null;
        }

        return Payment::query()->find($paymentId);
    }

    private function verifySignature(string $payload, ?string $signature, string $secret): bool
    {
        if (! $signature) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signature) as $element) {
            [$key, $value] = explode('=', $element, 2);
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || $signatures === []) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        $signed = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signed, $secret);

        foreach ($signatures as $v1) {
            if (hash_equals($expected, $v1)) {
                return true;
            }
        }

        return false;
    }
}
