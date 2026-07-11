<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\EnrollmentService;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CourseCheckoutController extends Controller
{
    public function show(Course $course, EnrollmentService $enrollmentService, PaymentGatewayManager $gateways): View|RedirectResponse
    {
        abort_unless(auth()->user()->isStudent(), 403);

        if ($course->isFree()) {
            return redirect()->route('student.enrollment.confirm', $course);
        }

        if ($enrollmentService->isEnrolled(auth()->user(), $course)) {
            return redirect()->route('student.courses')->with('success', 'Vous êtes déjà inscrit.');
        }

        $course->load(['user:id,name', 'category:id,name']);

        return view('student.checkout.course', [
            'course' => $course,
            'providers' => $gateways->availableProviders(),
        ]);
    }

    public function pay(Request $request, Course $course, PaymentService $paymentService, PaymentGatewayManager $gateways): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_keys($gateways->availableProviders()))],
        ]);

        try {
            $result = $paymentService->initiateCoursePurchase(
                auth()->user(),
                $course,
                $validated['provider'],
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result['completed']) {
            $enrollment = Enrollment::query()
                ->where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->latest()
                ->first();

            return redirect()
                ->route('student.enrollment.success', $enrollment)
                ->with('success', 'Paiement confirmé — inscription réussie !');
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
            return redirect()->route('student.courses')->with('error', $exception->getMessage());
        }

        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $payment->course_id)
            ->latest()
            ->first();

        if ($enrollment) {
            return redirect()
                ->route('student.enrollment.success', $enrollment)
                ->with('success', 'Paiement confirmé !');
        }

        return redirect()->route('student.premium')->with('success', 'Paiement confirmé !');
    }
}
