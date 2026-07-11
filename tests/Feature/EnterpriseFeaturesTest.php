<?php

use App\Enums\EnrollmentStatus;
use App\Enums\QuizQuestionType;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Payments\PaymentService;

test('global search returns published courses', function () {
    $student = User::factory()->create(['role' => 'student']);
    $cat = Category::query()->create(['name' => 'Search', 'slug' => 'search-'.uniqid()]);
    $prof = User::factory()->create(['role' => 'professor']);
    $course = Course::factory()->published()->create([
        'title' => 'Unique Blockchain Course XYZ',
        'category_id' => $cat->id,
        'user_id' => $prof->id,
    ]);

    $response = $this->actingAs($student)
        ->getJson(route('search', ['q' => 'Unique Blockchain']));

    $response->assertSuccessful();
    expect($response->json('total'))->toBeGreaterThanOrEqual(1);
});

test('sitemap xml is accessible', function () {
    $this->get(route('seo.sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml');
});

test('payment completion generates receipt and invoice', function () {
    $student = User::factory()->create(['role' => 'student']);
    $payment = Payment::factory()->create([
        'user_id' => $student->id,
        'status' => 'pending',
        'amount' => 9900,
        'meta' => ['type' => 'subscription', 'plan' => 'monthly'],
    ]);

    app(PaymentService::class)->complete($payment);

    $payment->refresh();
    expect($payment->status)->toBe('completed')
        ->and($payment->receipt_number)->not->toBeNull()
        ->and(Invoice::query()->where('payment_id', $payment->id)->exists())->toBeTrue();
});

test('student can submit a quiz and see result', function () {
    $student = User::factory()->create(['role' => 'student']);
    $prof = User::factory()->create(['role' => 'professor']);
    $cat = Category::query()->create(['name' => 'Quiz', 'slug' => 'quiz-'.uniqid()]);
    $course = Course::factory()->published()->create(['category_id' => $cat->id, 'user_id' => $prof->id]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $quiz = Quiz::query()->create([
        'course_id' => $course->id,
        'user_id' => $prof->id,
        'title' => 'Quiz Test',
        'passing_score' => 50,
        'max_attempts' => 3,
        'is_published' => true,
    ]);

    $question = QuizQuestion::query()->create([
        'quiz_id' => $quiz->id,
        'type' => QuizQuestionType::TrueFalse,
        'question' => 'Laravel est un framework PHP ?',
        'correct_answer' => 'true',
        'points' => 1,
    ]);

    $this->actingAs($student)
        ->post(route('student.quizzes.submit', $quiz), [
            'answers' => [$question->id => 'true'],
        ])
        ->assertRedirect();

    expect(QuizAttempt::query()->where('user_id', $student->id)->where('passed', true)->exists())->toBeTrue();
});

test('certificate verification page validates token', function () {
    $student = User::factory()->create(['role' => 'student']);
    $prof = User::factory()->create(['role' => 'professor']);
    $cat = Category::query()->create(['name' => 'Cert', 'slug' => 'cert-'.uniqid()]);
    $course = Course::factory()->published()->create(['category_id' => $cat->id, 'user_id' => $prof->id]);

    $enrollment = Enrollment::factory()->completed()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'verification_token' => 'valid-token-123',
    ]);

    $this->get(route('certificates.verify', 'valid-token-123'))
        ->assertSuccessful()
        ->assertSee('Certificat authentique')
        ->assertSee($student->name);
});
