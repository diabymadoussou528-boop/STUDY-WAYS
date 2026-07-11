<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;

/**
 * @return array{student: User, professor: User, course: Course}
 */
function createMessagingFixture(): array
{
    $category = Category::query()->create(['name' => 'Informatique', 'slug' => 'info-'.uniqid()]);

    $professor = User::factory()->create(['role' => 'professor']);
    $student = User::factory()->create(['role' => 'student']);

    $course = Course::query()->create([
        'title' => 'Python Programming',
        'description' => 'Apprendre Python',
        'category_id' => $category->id,
        'user_id' => $professor->id,
    ]);

    Enrollment::query()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'progress' => 25,
        'enrolled_at' => now(),
    ]);

    return compact('student', 'professor', 'course');
}

test('student can send message to enrolled course teacher', function () {
    ['student' => $student, 'professor' => $professor, 'course' => $course] = createMessagingFixture();

    $response = $this->actingAs($student)->postJson(route('student.messages.send'), [
        'recipient_id' => $professor->id,
        'course_id' => $course->id,
        'body' => 'Bonjour professeur, j\'ai une question sur les boucles.',
    ]);

    $response->assertSuccessful();
    expect(Message::query()->count())->toBe(1);
});

test('student cannot message teacher of unenrolled course', function () {
    ['student' => $student, 'professor' => $professor] = createMessagingFixture();

    $otherProfessor = User::factory()->create(['role' => 'professor']);
    $category = Category::query()->first();
    $otherCourse = Course::query()->create([
        'title' => 'Autre cours',
        'category_id' => $category->id,
        'user_id' => $otherProfessor->id,
    ]);

    $this->actingAs($student)->postJson(route('student.messages.send'), [
        'recipient_id' => $otherProfessor->id,
        'course_id' => $otherCourse->id,
        'body' => 'Message non autorisé',
    ])->assertForbidden();
});

test('professor can reply to enrolled student', function () {
    ['student' => $student, 'professor' => $professor, 'course' => $course] = createMessagingFixture();

    $this->actingAs($professor)->postJson(route('professor.messages.send'), [
        'recipient_id' => $student->id,
        'course_id' => $course->id,
        'body' => 'Bonjour, je suis disponible pour vous aider.',
    ])->assertSuccessful();
});

test('student messages page renders premium chat layout', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.messages'))
        ->assertSuccessful()
        ->assertSee('sw-chat-layout', false)
        ->assertSee('Conversations', false);
});

test('messaging service marks messages as read', function () {
    ['student' => $student, 'professor' => $professor, 'course' => $course] = createMessagingFixture();

    Message::query()->create([
        'from_user_id' => $professor->id,
        'to_user_id' => $student->id,
        'course_id' => $course->id,
        'body' => 'Réponse du professeur',
    ]);

    expect(app(MessagingService::class)->unreadCount($student))->toBe(1);

    $this->actingAs($student)->getJson(route('student.messages.thread', [
        'other_user_id' => $professor->id,
        'course_id' => $course->id,
    ]))->assertSuccessful();

    expect(app(MessagingService::class)->unreadCount($student))->toBe(0);
});

test('student profile uses premium student layout', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('StudyWays Étudiant', false)
        ->assertSee('Photo de profil', false)
        ->assertSee('modern-input', false);
});
