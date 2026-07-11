<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

class InvoiceService
{
    public function createFromPayment(Payment $payment, ?string $description = null): Invoice
    {
        $existing = Invoice::query()->where('payment_id', $payment->id)->first();

        if ($existing) {
            return $existing;
        }

        $meta = $payment->meta ?? [];

        return Invoice::query()->create([
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'number' => $this->generateNumber(),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status === 'completed' ? 'paid' : 'pending',
            'description' => $description ?? $this->defaultDescription($payment),
            'meta' => $meta,
            'issued_at' => now(),
        ]);
    }

    public function generateNumber(): string
    {
        return 'INV-'.now()->format('Y').'-'.str_pad((string) (Invoice::query()->count() + 1), 6, '0', STR_PAD_LEFT);
    }

    private function defaultDescription(Payment $payment): string
    {
        $meta = $payment->meta ?? [];

        if ($payment->course_id) {
            return 'Achat de cours — '.($meta['course_title'] ?? 'Cours StudyWays');
        }

        if (($meta['type'] ?? null) === 'subscription') {
            return 'Abonnement Premium '.($meta['plan'] ?? '');
        }

        return 'Paiement StudyWays';
    }
}
