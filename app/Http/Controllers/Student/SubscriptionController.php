<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function checkout(SubscriptionService $service, PaymentGatewayManager $gateways): View
    {
        return view('student.premium', [
            'isPremium' => auth()->user()->hasActivePremium(),
            'plans' => $service->plans(),
            'providers' => $gateways->availableProviders(),
        ]);
    }

    public function subscribe(Request $request, PaymentService $paymentService, PaymentGatewayManager $gateways): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:monthly,yearly'],
            'provider' => ['required', Rule::in(array_keys($gateways->availableProviders()))],
        ]);

        try {
            $result = $paymentService->initiateSubscription(
                auth()->user(),
                $validated['plan'],
                $validated['provider'],
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result['completed']) {
            return redirect()
                ->route('student.premium')
                ->with('success', 'Abonnement Premium activé avec succès !');
        }

        if ($result['redirect_url'] ?? null) {
            return redirect()->away($result['redirect_url']);
        }

        return back()->with('error', 'Impossible de démarrer le paiement.');
    }

    public function success(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        abort_unless((int) $payment->user_id === (int) auth()->id(), 403);

        try {
            if ($payment->status !== 'completed') {
                $paymentService->complete($payment, $request->query('session_id'));
            }
        } catch (RuntimeException $exception) {
            return redirect()->route('student.premium')->with('error', $exception->getMessage());
        }

        return redirect()->route('student.premium')->with('success', 'Abonnement Premium activé !');
    }

    public function cancel(SubscriptionService $service): RedirectResponse
    {
        $service->cancel(auth()->user());

        return back()->with('success', 'Abonnement annulé.');
    }

    public function renew(Request $request, PaymentService $paymentService, PaymentGatewayManager $gateways, SubscriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:monthly,yearly'],
            'provider' => ['required', Rule::in(array_keys($gateways->availableProviders()))],
        ]);

        $plans = $service->plans();
        $amount = $plans[$validated['plan']]['amount'];

        try {
            $result = $paymentService->initiateSubscription(auth()->user(), $validated['plan'], $validated['provider']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result['completed']) {
            return back()->with('success', 'Abonnement renouvelé avec succès !');
        }

        if ($result['redirect_url'] ?? null) {
            return redirect()->away($result['redirect_url']);
        }

        return back()->with('error', 'Impossible de renouveler l\'abonnement.');
    }

    public function history(): View
    {
        $payments = Payment::query()
            ->where('user_id', auth()->id())
            ->with('invoice')
            ->latest()
            ->paginate(10);

        $subscription = Subscription::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();

        return view('student.premium-history', compact('payments', 'subscription'));
    }
}
