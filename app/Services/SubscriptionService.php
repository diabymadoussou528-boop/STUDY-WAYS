<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private NotificationDispatchService $notifications,
    ) {}

    public function activateFromPayment(User $user, string $plan, Payment $payment): Subscription
    {
        if ($payment->subscription_id) {
            return Subscription::query()->findOrFail($payment->subscription_id);
        }

        return DB::transaction(function () use ($user, $plan, $payment) {
            $endsAt = $plan === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $subscription = Subscription::query()->create([
                'user_id' => $user->id,
                'plan' => $plan,
                'status' => SubscriptionStatus::Active,
                'provider' => $payment->provider,
                'amount' => $payment->amount,
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);

            $payment->update(['subscription_id' => $subscription->id]);

            $user->update([
                'is_premium' => true,
                'premium_plan' => $plan,
            ]);

            $this->notifications->notify(
                $user,
                'premium_subscription',
                'Abonnement Premium activé',
                'Votre abonnement '.$plan.' est actif jusqu\'au '.$endsAt->format('d/m/Y').'.',
            );

            return $subscription;
        });
    }

    /**
     * @return array{subscription: Subscription, payment: Payment}
     */
    public function subscribe(User $user, string $plan, float $amount, string $provider = 'manual'): array
    {
        return DB::transaction(function () use ($user, $plan, $amount, $provider) {
            $endsAt = $plan === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $subscription = Subscription::query()->create([
                'user_id' => $user->id,
                'plan' => $plan,
                'status' => SubscriptionStatus::Active,
                'provider' => $provider,
                'amount' => $amount,
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);

            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'provider' => $provider,
                'status' => 'completed',
                'meta' => ['plan' => $plan],
            ]);

            $user->update([
                'is_premium' => true,
                'premium_plan' => $plan,
            ]);

            $this->notifications->notify(
                $user,
                'premium_subscription',
                'Abonnement Premium activé',
                'Votre abonnement '.$plan.' est actif jusqu\'au '.$endsAt->format('d/m/Y').'.',
            );

            return compact('subscription', 'payment');
        });
    }

    public function renew(User $user, string $plan, float $amount, string $provider = 'manual'): array
    {
        $this->cancel($user, immediate: false);

        return $this->subscribe($user, $plan, $amount, $provider);
    }

    public function cancel(User $user, bool $immediate = true): void
    {
        Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', SubscriptionStatus::Active)
            ->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

        if ($immediate) {
            $user->update(['is_premium' => false, 'premium_plan' => null]);
        }
    }

    public function expireDueSubscriptions(): int
    {
        $count = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->each(function (Subscription $subscription) use (&$count) {
                $subscription->update(['status' => SubscriptionStatus::Expired]);
                $subscription->user?->update(['is_premium' => false, 'premium_plan' => null]);
                $count++;
            });

        return $count;
    }

    public function plans(): array
    {
        return [
            'monthly' => ['label' => 'Mensuel', 'amount' => 9900, 'currency' => 'XOF'],
            'yearly' => ['label' => 'Annuel', 'amount' => 99000, 'currency' => 'XOF'],
        ];
    }
}
