<?php

use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Review;
use App\Models\User;

function courseDetailCategory(): Category
{
    return Category::query()->create(['name' => 'React', 'slug' => 'react-'.uniqid()]);
}

function courseDetailPublishedCourse(?User $professor = null): Course
{
    $professor ??= User::factory()->create([
        'role' => 'professor',
        'bio' => 'Instructeur expérimenté en développement web.',
    ]);

    return Course::factory()->published()->create([
        'title' => 'ReactJS — Développement Frontend',
        'description' => 'Apprenez ReactJS de A à Z avec des projets concrets.',
        'short_description' => 'Maîtrisez ReactJS et construisez des applications modernes.',
        'category_id' => courseDetailCategory()->id,
        'user_id' => $professor->id,
        'difficulty' => 'beginner',
        'duration_minutes' => 1200,
        'objectives' => ['Créer des composants React', 'Gérer l\'état avec hooks'],
        'requirements' => ['JavaScript de base', 'HTML/CSS'],
        'meta_keywords' => 'ReactJS, JavaScript, Frontend',
    ]);
}

function courseDetailWithCurriculum(Course $course): void
{
    $module = CourseModule::query()->create([
        'course_id' => $course->id,
        'title' => 'Introduction & démarrage',
        'description' => 'Premiers pas avec React.',
        'sort_order' => 1,
    ]);

    Lesson::query()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Installation et configuration',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'lesson_type' => LessonType::Video,
        'duration_seconds' => 320,
        'sort_order' => 1,
        'is_preview' => true,
    ]);

    Lesson::query()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Votre premier composant',
        'content' => 'Un composant React est une fonction qui retourne du JSX.',
        'lesson_type' => LessonType::Text,
        'duration_seconds' => 480,
        'sort_order' => 2,
    ]);
}

test('guest can view course detail page with dynamic data', function () {
    $course = courseDetailPublishedCourse();
    courseDetailWithCurriculum($course);

    Review::factory()->create([
        'course_id' => $course->id,
        'rating' => 5,
        'comment' => 'Super cours React !',
    ]);

    $this->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee('ReactJS — Développement Frontend')
        ->assertSee('Maîtrisez ReactJS')
        ->assertSee('Programme du cours')
        ->assertSee('Introduction & démarrage')
        ->assertSee('Installation et configuration')
        ->assertSee('Super cours React !')
        ->assertSee('ReactJS');
});

test('guest can access preview lesson without enrollment', function () {
    $course = courseDetailPublishedCourse();
    courseDetailWithCurriculum($course);

    $previewLesson = Lesson::query()->where('course_id', $course->id)->where('is_preview', true)->first();

    $this->get(route('courses.learn', [$course, $previewLesson]))
        ->assertSuccessful()
        ->assertSee('Installation et configuration');
});

test('guest cannot access full learn page without enrollment', function () {
    $course = courseDetailPublishedCourse();
    courseDetailWithCurriculum($course);

    $lesson = Lesson::query()->where('course_id', $course->id)->where('is_preview', false)->first();

    $this->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee('fa-lock', false);

    $this->get(route('courses.learn', [$course, $lesson]))
        ->assertRedirect(route('courses.show', $course));
});

test('enrolled student can access learn page and complete lesson', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = courseDetailPublishedCourse();
    courseDetailWithCurriculum($course);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $lesson = Lesson::query()->where('course_id', $course->id)->where('is_preview', false)->first();

    $this->actingAs($student)
        ->get(route('courses.learn', [$course, $lesson]))
        ->assertSuccessful()
        ->assertSee('Votre premier composant')
        ->assertSee('Marquer comme terminée');

    $this->actingAs($student)
        ->post(route('courses.lessons.complete', [$course, $lesson]))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('enrolled student sees continue learning button on detail page', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = courseDetailPublishedCourse();
    courseDetailWithCurriculum($course);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee('Continuer l\'apprentissage', false);
});

test('draft course is not visible to guests', function () {
    $course = Course::factory()->draft()->create([
        'category_id' => courseDetailCategory()->id,
    ]);

    $this->get(route('courses.show', $course))
        ->assertNotFound();
});
