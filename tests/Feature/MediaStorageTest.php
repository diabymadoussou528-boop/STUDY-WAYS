<?php

use App\Enums\LessonType;
use App\Enums\MediaCategory;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['media.disk' => 'public']);
    Storage::fake('public');
});

test('avatar upload stores file locally and saves path in database', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), ['avatar' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->avatar)->not->toBeNull()
        ->and($user->avatar)->toStartWith('avatars/');

    Storage::disk('public')->assertExists($user->avatar);

    $url = app(MediaStorageService::class)->url($user->avatar, MediaCategory::Avatar);

    expect($url)->toContain($user->avatar)
        ->and($user->avatarUrl())->toBe($url);
});

test('professor can upload course thumbnail and video to local storage', function () {
    $professor = User::factory()->professor()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($professor)
        ->post(route('courses.store'), [
            'title' => 'Cloud Storage Course',
            'description' => 'Learn media uploads',
            'category' => 'Web',
            'thumbnail' => $thumbnail,
            'video' => $video,
            'price' => 0,
        ])
        ->assertRedirect(route('professor.courses.index'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Cloud Storage Course')->first();

    expect($course)->not->toBeNull()
        ->and($course->thumbnail)->toStartWith('thumbnails/')
        ->and($course->video_path)->toStartWith('videos/')
        ->and($course->video_url)->toBeNull();

    Storage::disk('public')->assertExists($course->thumbnail);
    Storage::disk('public')->assertExists($course->video_path);

    expect($course->thumbnailUrl())->toContain($course->thumbnail)
        ->and($course->videoUrl())->toBe(route('courses.video.stream', $course));
});

test('lesson upload stores resource_path and supports video playback url', function () {
    $professor = User::factory()->professor()->create();
    $category = Category::query()->create(['name' => 'Dev', 'slug' => 'dev']);
    $course = Course::factory()->draft()->create([
        'category_id' => $category->id,
        'user_id' => $professor->id,
    ]);

    $video = UploadedFile::fake()->create('lesson.mp4', 512, 'video/mp4');

    $this->actingAs($professor)
        ->post(route('lessons.store'), [
            'course_id' => $course->id,
            'title' => 'Intro video',
            'file' => $video,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $lesson = Lesson::query()->where('title', 'Intro video')->first();

    expect($lesson)->not->toBeNull()
        ->and($lesson->resource_path)->toStartWith('lesson-videos/')
        ->and($lesson->lesson_type)->toBe(LessonType::Video);

    Storage::disk('public')->assertExists($lesson->resource_path);

    expect($lesson->storedVideoUrl())->toBe(route('lessons.video.stream', $lesson))
        ->and($lesson->resourceUrl())->toContain($lesson->resource_path);
});

test('professor cannot upload lesson media for another professors course', function () {
    $owner = User::factory()->professor()->create();
    $intruder = User::factory()->professor()->create();
    $category = Category::query()->create(['name' => 'Sec', 'slug' => 'sec']);
    $course = Course::factory()->draft()->create([
        'category_id' => $category->id,
        'user_id' => $owner->id,
    ]);

    $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

    $this->actingAs($intruder)
        ->post(route('lessons.store'), [
            'course_id' => $course->id,
            'title' => 'Stolen upload',
            'file' => $file,
        ])
        ->assertForbidden();

    expect(Lesson::query()->where('title', 'Stolen upload')->exists())->toBeFalse();
});

test('media storage service resolves external urls unchanged', function () {
    $service = app(MediaStorageService::class);
    $external = 'https://example.com/assets/image.jpg';

    expect($service->url($external, MediaCategory::Avatar))->toBe($external)
        ->and($service->isExternalUrl($external))->toBeTrue()
        ->and($service->isCloudStored('studways/avatars/test-id'))->toBeTrue()
        ->and($service->isCloudStored('avatars/local.jpg'))->toBeFalse();
});

test('migrate media command succeeds without external storage configuration', function () {
    config(['media.disk' => 'public']);

    $this->artisan('media:migrate-to-cloud')
        ->assertSuccessful();
});

test('avatar upload rejects invalid files', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), ['avatar' => $file])
        ->assertSessionHasErrors('avatar');
});
