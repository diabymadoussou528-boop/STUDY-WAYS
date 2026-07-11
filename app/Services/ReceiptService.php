<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Str;

class ReceiptService
{
    public function __construct(private InvoiceService $invoices) {}

    public function assignReceiptNumber(Payment $payment): Payment
    {
        if ($payment->receipt_number) {
            return $payment;
        }

        $payment->update([
            'receipt_number' => $this->generateReceiptNumber(),
            'transaction_id' => $payment->transaction_id ?? $this->generateTransactionId($payment),
        ]);

        return $payment->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function receiptPayload(Payment $payment): array
    {
        $payment = $this->assignReceiptNumber($payment);
        $payment->load(['user', 'course', 'subscription']);

        $invoice = $payment->invoice ?? $this->invoices->createFromPayment($payment);

        return [
            'payment' => $payment,
            'invoice' => $invoice,
            'user' => $payment->user,
        ];
    }

    public function markFailed(Payment $payment, string $reason): Payment
    {
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        return $payment->fresh();
    }

    /**
     * Refund-ready structure — actual provider refund wired per gateway.
     */
    public function markRefundPending(Payment $payment, float $amount): Payment
    {
        $payment->update([
            'refund_status' => 'pending',
            'refund_amount' => $amount,
        ]);

        return $payment->fresh();
    }

    public function markRefunded(Payment $payment): Payment
    {
        $payment->update([
            'refund_status' => 'completed',
            'refunded_at' => now(),
        ]);

        return $payment->fresh();
    }

    private function generateReceiptNumber(): string
    {
        return 'RCP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    private function generateTransactionId(Payment $payment): string
    {
        return 'TXN-'.$payment->id.'-'.Str::upper(Str::random(8));
    }
}
