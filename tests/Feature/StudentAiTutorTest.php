<?php

use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AiChatMessage;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * @return array{student: User, course: Course}
 */
function createAiFixture(): array
{
    $category = Category::query()->create(['name' => 'Dev', 'slug' => 'dev-'.uniqid()]);
    $professor = User::factory()->create(['role' => 'professor']);
    $student = User::factory()->create(['role' => 'student']);

    $course = Course::query()->create([
        'title' => 'Python Programming',
        'description' => 'Cours Python complet',
        'category_id' => $category->id,
        'user_id' => $professor->id,
    ]);

    Enrollment::query()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'progress' => 10,
        'enrolled_at' => now(),
    ]);

    return ['student' => $student, 'course' => $course];
}

function makeStudentPremium(User $student): void
{
    $student->update(['is_premium' => true]);
    Subscription::query()->create([
        'user_id' => $student->id,
        'plan' => 'monthly',
        'status' => SubscriptionStatus::Active,
        'provider' => 'manual',
        'amount' => 9900,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

test('student ai tutor page renders premium chat interface', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.ai-tutor'))
        ->assertSuccessful()
        ->assertSee('Tuteur', false)
        ->assertSee('aiTutorApp', false)
        ->assertSee('student-ai-tutor.js', false);
});

test('student can chat with ai tutor and receive response', function () {
    ['student' => $student, 'course' => $course] = createAiFixture();
    makeStudentPremium($student);

    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'En Python, une boucle for itère sur une séquence.']],
            ],
        ]),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.ai-tutor.chat'), [
        'message' => 'Explique les boucles',
        'course_id' => $course->id,
        'topic' => 'Boucles',
        'mode' => 'chat',
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('reply', 'En Python, une boucle for itère sur une séquence.');

    expect(AiChatMessage::query()->count())->toBe(2);
});

test('student cannot use ai tutor on unenrolled course', function () {
    $student = User::factory()->create(['role' => 'student']);
    makeStudentPremium($student);
    $category = Category::query()->create(['name' => 'Test', 'slug' => 't-'.uniqid()]);
    $course = Course::query()->create([
        'title' => 'Cours non inscrit',
        'category_id' => $category->id,
        'user_id' => User::factory()->create(['role' => 'professor'])->id,
    ]);

    Http::fake();

    $this->actingAs($student)->postJson(route('student.ai-tutor.chat'), [
        'message' => 'Question',
        'course_id' => $course->id,
    ])->assertStatus(422);
});

test('student can clear ai chat history', function () {
    $student = User::factory()->create(['role' => 'student']);

    AiChatMessage::query()->create([
        'user_id' => $student->id,
        'role' => 'user',
        'content' => 'Test',
        'mode' => 'chat',
    ]);

    $this->actingAs($student)
        ->delete(route('student.ai-tutor.clear'))
        ->assertSuccessful();

    expect(AiChatMessage::query()->count())->toBe(0);
});
