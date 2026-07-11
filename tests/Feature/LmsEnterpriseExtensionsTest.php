<?php

use App\Enums\EnrollmentStatus;
use App\Enums\QuizQuestionType;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Models\ProcessedWebhookEvent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Notifications\LmsEventNotification;
use App\Services\NotificationDispatchService;
use App\Services\Payments\PaymentService;
use App\Services\QuizService;
use Illuminate\Support\Facades\Notification;

test('stripe webhook completes pending payment with valid signature', function () {
    $student = User::factory()->create(['role' => 'student']);
    $payment = Payment::factory()->create([
        'user_id' => $student->id,
        'status' => 'pending',
        'provider' => 'stripe',
        'amount' => 12000,
        'meta' => ['type' => 'course', 'course_title' => 'Test Course'],
        'course_id' => Course::factory()->published()->create()->id,
    ]);

    config(['payments.providers.stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = [
        'id' => 'evt_test_'.uniqid(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'client_reference_id' => (string) $payment->id,
                'payment_status' => 'paid',
            ],
        ],
    ];

    $raw = json_encode($payload);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$raw, 'whsec_test_secret');

    $this->postJson(route('webhooks.stripe'), $payload, [
        'Stripe-Signature' => $signature,
    ])->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('completed')
        ->and(ProcessedWebhookEvent::query()->where('event_id', $payload['id'])->exists())->toBeTrue();
});

test('stripe webhook rejects duplicate events', function () {
    $payment = Payment::factory()->create([
        'status' => 'pending',
        'provider' => 'stripe',
    ]);

    config(['payments.providers.stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = [
        'id' => 'evt_dup_'.uniqid(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_dup',
                'client_reference_id' => (string) $payment->id,
                'payment_status' => 'paid',
            ],
        ],
    ];

    $raw = json_encode($payload);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$raw, 'whsec_test_secret');

    $this->postJson(route('webhooks.stripe'), $payload, ['Stripe-Signature' => $signature])->assertOk();
    $this->postJson(route('webhooks.stripe'), $payload, ['Stripe-Signature' => $signature])->assertOk();

    expect(Payment::query()->whereKey($payment->id)->value('status'))->toBe('completed');
});

test('stripe webhook rejects invalid signature', function () {
    config(['payments.providers.stripe.webhook_secret' => 'whsec_test_secret']);

    $this->postJson(route('webhooks.stripe'), [
        'id' => 'evt_bad',
        'type' => 'checkout.session.completed',
        'data' => ['object' => []],
    ], ['Stripe-Signature' => 't=1,v1=invalid'])
        ->assertStatus(400);
});

test('professor can grade essay answer and finalize attempt', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $student = User::factory()->create(['role' => 'student']);
    $cat = Category::query()->create(['name' => 'Essay', 'slug' => 'essay-'.uniqid()]);
    $course = Course::factory()->published()->create(['category_id' => $cat->id, 'user_id' => $professor->id]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $quiz = Quiz::query()->create([
        'course_id' => $course->id,
        'user_id' => $professor->id,
        'title' => 'Quiz Dissertation',
        'passing_score' => 50,
        'max_attempts' => 2,
        'is_published' => true,
    ]);

    $essay = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'type' => QuizQuestionType::Essay,
        'question' => 'Expliquez MVC.',
        'points' => 10,
    ]);

    $attempt = QuizAttempt::query()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'status' => 'pending_grading',
        'submitted_at' => now(),
        'score' => 0,
        'percentage' => 0,
        'passed' => false,
    ]);

    $answer = QuizAttemptAnswer::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'quiz_question_id' => $essay->id,
        'answer' => 'MVC sépare modèle, vue et contrôleur.',
        'points_awarded' => 0,
        'feedback' => 'En attente de correction manuelle.',
    ]);

    $this->actingAs($professor)
        ->post(route('professor.quizzes.answers.grade', $answer), [
            'points' => 8,
            'feedback' => 'Bonne explication.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $attempt->refresh();
    expect($attempt->status)->toBe('submitted')
        ->and($attempt->percentage)->toBe(80)
        ->and($attempt->passed)->toBeTrue();
});

test('notification dispatch sends lms email when enabled', function () {
    Notification::fake();

    config([
        'notifications.email_enabled' => true,
        'notifications.email_types' => ['payment_received'],
        'mail.from.address' => 'noreply@studyways.test',
    ]);

    $student = User::factory()->create(['role' => 'student']);
    $service = app(NotificationDispatchService::class);

    $service->notify($student, 'payment_received', 'Paiement reçu', 'Votre paiement a été confirmé.', ['payment_id' => 1]);

    Notification::assertSentTo($student, LmsEventNotification::class);
    expect(PlatformNotification::query()->where('user_id', $student->id)->count())->toBe(1);
});

test('payment completion remains idempotent', function () {
    $student = User::factory()->create(['role' => 'student']);
    $payment = Payment::factory()->create([
        'user_id' => $student->id,
        'status' => 'pending',
        'amount' => 9900,
        'meta' => ['type' => 'subscription', 'plan' => 'monthly'],
    ]);

    $service = app(PaymentService::class);
    $service->complete($payment);
    $service->complete($payment->fresh());

    expect(Payment::query()->whereKey($payment->id)->value('status'))->toBe('completed');
});

test('essay quiz submission sets pending grading status', function () {
    $student = User::factory()->create(['role' => 'student']);
    $professor = User::factory()->create(['role' => 'professor']);
    $cat = Category::query()->create(['name' => 'Quiz', 'slug' => 'quiz-'.uniqid()]);
    $course = Course::factory()->published()->create(['category_id' => $cat->id, 'user_id' => $professor->id]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $quiz = Quiz::query()->create([
        'course_id' => $course->id,
        'user_id' => $professor->id,
        'title' => 'Quiz Essay',
        'passing_score' => 50,
        'max_attempts' => 2,
        'is_published' => true,
    ]);

    $question = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'type' => QuizQuestionType::Essay,
        'question' => 'Décrivez Laravel.',
        'points' => 5,
    ]);

    app(QuizService::class)->startAttempt($student, $quiz);

    $this->actingAs($student)
        ->post(route('student.quizzes.submit', $quiz), [
            'answers' => [$question->id => 'Laravel est un framework PHP.'],
        ])
        ->assertRedirect();

    $attempt = QuizAttempt::query()->where('user_id', $student->id)->first();
    expect($attempt->status)->toBe('pending_grading');
});
