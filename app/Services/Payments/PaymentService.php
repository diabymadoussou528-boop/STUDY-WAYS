<?php

namespace App\Services\Payments;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\EnrollmentService;
use App\Services\InvoiceService;
use App\Services\NotificationDispatchService;
use App\Services\ReceiptService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private EnrollmentService $enrollmentService,
        private SubscriptionService $subscriptionService,
        private NotificationDispatchService $notifications,
        private ReceiptService $receipts,
        private InvoiceService $invoices,
        private AuditLogService $auditLog,
    ) {}

    /**
     * @return array{payment: Payment, redirect_url?: string, completed: bool}
     */
    public function initiateCoursePurchase(User $student, Course $course, string $provider): array
    {
        if ($course->isFree()) {
            throw new RuntimeException('Ce cours est gratuit.');
        }

        if ($this->enrollmentService->isEnrolled($student, $course)) {
            throw new RuntimeException('Vous êtes déjà inscrit à ce cours.');
        }

        return DB::transaction(function () use ($student, $course, $provider) {
            $payment = Payment::query()->create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'currency' => config('payments.currency', 'XOF'),
                'provider' => $provider,
                'status' => 'pending',
                'meta' => ['type' => 'course', 'course_title' => $course->title],
            ]);

            $gateway = $this->gateways->driver($provider);

            $result = $gateway->charge($payment, [
                'title' => $course->title,
                'success_url' => route('student.checkout.success', $payment).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('student.checkout.course', $course),
            ]);

            if ($result['provider_payment_id'] ?? null) {
                $payment->update(['provider_payment_id' => $result['provider_payment_id']]);
            }

            if ($result['completed']) {
                $this->complete($payment);

                return ['payment' => $payment->fresh(), 'completed' => true];
            }

            return [
                'payment' => $payment->fresh(),
                'redirect_url' => $result['redirect_url'] ?? null,
                'completed' => false,
            ];
        });
    }

    /**
     * @return array{payment: Payment, redirect_url?: string, completed: bool}
     */
    public function initiateSubscription(User $student, string $plan, string $provider): array
    {
        $plans = $this->subscriptionService->plans();
        $amount = $plans[$plan]['amount'] ?? null;

        if ($amount === null) {
            throw new RuntimeException('Plan invalide.');
        }

        return DB::transaction(function () use ($student, $plan, $provider, $amount, $plans) {
            $payment = Payment::query()->create([
                'user_id' => $student->id,
                'amount' => $amount,
                'currency' => $plans[$plan]['currency'] ?? config('payments.currency', 'XOF'),
                'provider' => $provider,
                'status' => 'pending',
                'meta' => ['type' => 'subscription', 'plan' => $plan],
            ]);

            $gateway = $this->gateways->driver($provider);

            $result = $gateway->charge($payment, [
                'title' => 'StudyWays Premium — '.$plans[$plan]['label'],
                'success_url' => route('student.premium.success', $payment).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('student.premium'),
            ]);

            if ($result['provider_payment_id'] ?? null) {
                $payment->update(['provider_payment_id' => $result['provider_payment_id']]);
            }

            if ($result['completed']) {
                $this->complete($payment);

                return ['payment' => $payment->fresh(), 'completed' => true];
            }

            return [
                'payment' => $payment->fresh(),
                'redirect_url' => $result['redirect_url'] ?? null,
                'completed' => false,
            ];
        });
    }

    public function complete(Payment $payment, ?string $sessionId = null): Payment
    {
        return DB::transaction(function () use ($payment, $sessionId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === 'completed') {
                return $payment;
            }

            if ($sessionId && $payment->provider === 'stripe') {
                $verified = $this->gateways->driver('stripe')->verifySession($payment, $sessionId);
                if (! $verified) {
                    throw new RuntimeException('Paiement non confirmé.');
                }
            }

            $payment->update(['status' => 'completed']);

            $meta = $payment->meta ?? [];

            if ($payment->course_id) {
                $course = Course::query()->findOrFail($payment->course_id);
                $this->enrollmentService->enrollAfterPayment($payment->user, $course);
            } elseif (($meta['type'] ?? null) === 'subscription' && isset($meta['plan'])) {
                $this->subscriptionService->activateFromPayment($payment->user, $meta['plan'], $payment);
            }

            $this->notifications->notify(
                $payment->user,
                'payment_received',
                'Paiement reçu',
                number_format((float) $payment->amount, 0, ',', ' ').' '.$payment->currency.' confirmé.',
                ['payment_id' => $payment->id, 'receipt_url' => route('payments.receipt', $payment)],
            );

            $this->receipts->assignReceiptNumber($payment);
            $this->invoices->createFromPayment($payment);

            $this->auditLog->log(
                action: 'payment.completed',
                module: 'payments',
                description: 'Paiement confirmé #'.$payment->id,
                metadata: ['payment_id' => $payment->id, 'amount' => $payment->amount],
                actor: $payment->user,
            );

            return $payment->fresh();
        });
    }
}
