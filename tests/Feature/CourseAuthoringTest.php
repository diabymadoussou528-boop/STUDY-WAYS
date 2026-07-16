<?php

use App\Enums\ApprovalStatus;
use App\Enums\CourseStatus;
use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use App\Models\AdminActionRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['media.disk' => 'public']);
    Storage::fake('public');
});

test('professor can create a course with intelligent category and media uploads', function () {
    $professor = User::factory()->professor()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($professor)
        ->post(route('courses.store'), [
            'title' => 'Laravel Mastery',
            'description' => 'Apprenez Laravel de A à Z',
            'category' => '  Programming  ',
            'difficulty' => 'débutant',
            'duration_minutes' => 180,
            'language' => 'Français',
            'tags' => 'laravel, php, api',
            'objectives' => "Créer une API\nUtiliser Eloquent",
            'requirements' => 'Bases PHP',
            'price' => 0,
            'thumbnail' => $thumbnail,
            'video' => $video,
        ])
        ->assertRedirect(route('professor.courses.index'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Laravel Mastery')->first();

    expect($course)->not->toBeNull()
        ->and($course->category->normalized_name)->toBe('programming')
        ->and($course->language)->toBe('Français')
        ->and($course->duration_minutes)->toBe(180)
        ->and($course->objectives)->toBe(['Créer une API', 'Utiliser Eloquent'])
        ->and($course->meta_keywords)->toBe('laravel, php, api')
        ->and($course->thumbnail)->toStartWith('thumbnails/')
        ->and($course->video_path)->toStartWith('videos/')
        ->and($course->creator_id)->toBe($professor->id)
        ->and($course->teacher_id)->toBe($professor->id);

    Storage::disk('public')->assertExists($course->thumbnail);
    Storage::disk('public')->assertExists($course->video_path);
});

test('super admin course creation publishes immediately', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($superAdmin)
        ->post(route('admin.courses.store'), [
            'title' => 'Super Admin Course',
            'description' => 'Published right away',
            'category' => 'Business',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ])
        ->assertRedirect(route('admin.courses'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Super Admin Course')->latest()->first();

    expect($course)->not->toBeNull()
        ->and($course->status)->toBe(CourseStatus::Published)
        ->and($course->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($course->approved_by)->toBe($superAdmin->id)
        ->and($course->published_at)->not->toBeNull();
});

test('teacher course creation publishes immediately and notifies the super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $professor = User::factory()->professor()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($professor)
        ->post(route('courses.store'), [
            'title' => 'Professor Published Course',
            'description' => 'Published by the teacher',
            'category' => 'Design',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ])
        ->assertRedirect(route('professor.courses.index'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Professor Published Course')->latest()->first();

    expect($course)->not->toBeNull()
        ->and($course->status)->toBe(CourseStatus::Published)
        ->and($course->approval_status)->toBe(ApprovalStatus::Approved);

    $notified = PlatformNotification::query()
        ->where('user_id', $superAdmin->id)
        ->where('type', 'teacher_course_published')
        ->get()
        ->contains(fn (PlatformNotification $n) => ($n->data['course_id'] ?? null) === $course->id);

    expect($notified)->toBeTrue();
});

test('simple admin course creation requests approval and stays pending', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $simpleAdmin = User::factory()->simpleAdmin()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($simpleAdmin)
        ->post(route('admin.courses.store'), [
            'title' => 'Simple Admin Course',
            'description' => 'Needs approval',
            'category' => 'Marketing',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ])
        ->assertRedirect(route('admin.courses'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Simple Admin Course')->latest()->first();

    expect($course)->not->toBeNull()
        ->and($course->status)->toBe(CourseStatus::Draft)
        ->and($course->approval_status)->toBe(ApprovalStatus::Pending)
        ->and($course->creator_id)->toBe($simpleAdmin->id)
        ->and(AdminActionRequest::query()->where('action', 'create_course')->where('target_id', $course->id)->exists())->toBeTrue();

    expect(PlatformNotification::query()->where('user_id', $superAdmin->id)
        ->where('type', 'approval_request')
        ->exists())->toBeTrue();
});

test('super admin approval publishes the simple admins course and notifies them', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $simpleAdmin = User::factory()->simpleAdmin()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($simpleAdmin)
        ->post(route('admin.courses.store'), [
            'title' => 'Pending Approval Course',
            'description' => 'Needs approval',
            'category' => 'Marketing',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ]);

    $course = Course::query()->where('title', 'Pending Approval Course')->latest()->first();
    $request = AdminActionRequest::query()->where('action', 'create_course')->where('target_id', $course->id)->first();

    $this->actingAs($superAdmin)
        ->post(route('admin.approvals.approve', $request))
        ->assertRedirect(route('admin.approvals'))
        ->assertSessionHas('success');

    $course->refresh();

    expect($course->status)->toBe(CourseStatus::Published)
        ->and($course->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($course->approved_by)->toBe($superAdmin->id)
        ->and($course->approved_at)->not->toBeNull();

    $approvedNotified = PlatformNotification::query()
        ->where('user_id', $simpleAdmin->id)
        ->where('type', 'course_approved')
        ->get()
        ->contains(fn (PlatformNotification $n) => ($n->data['course_id'] ?? null) === $course->id);

    expect($approvedNotified)->toBeTrue();
});

test('course creation rolls back uploads when a media upload fails', function () {
    $professor = User::factory()->professor()->create();

    $thumbnail = UploadedFile::fake()->image('thumb.jpg', 1280, 720);
    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->mock(MediaStorageService::class, function ($mock) {
        $mock->shouldReceive('upload')->once()->andReturn('thumbnails/temp.jpg');
        $mock->shouldReceive('upload')->once()->andThrow(new MediaUploadException('Upload failed'));
        $mock->shouldReceive('driveFileId')->andReturn(null);
        $mock->shouldReceive('delete')->once()->with('thumbnails/temp.jpg', MediaCategory::CourseThumbnail);
    });

    $this->actingAs($professor)
        ->post(route('courses.store'), [
            'title' => 'Rollback Course',
            'description' => 'Should not be created',
            'category' => 'Science',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ])
        ->assertRedirect(route('professor.courses.index'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Rollback Course')->first();

    expect($course)->not->toBeNull()
        ->and($course->upload_status)->toBe('failed')
        ->and($course->thumbnail)->toBeNull()
        ->and($course->video_path)->toBeNull();
});

test('professor cannot update another professors course', function () {
    $owner = User::factory()->professor()->create();
    $intruder = User::factory()->professor()->create();
    $category = Category::query()->create(['name' => 'Web', 'slug' => 'web']);
    $course = Course::factory()->draft()->create([
        'user_id' => $owner->id,
        'creator_id' => $owner->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($intruder)
        ->put(route('courses.update', $course), [
            'title' => 'Hacked',
            'description' => 'Nope',
            'category' => 'Web',
        ])
        ->assertForbidden();
});

test('professor can create a course with video and without thumbnail', function () {
    $professor = User::factory()->professor()->create();

    $video = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $this->actingAs($professor)
        ->post(route('courses.store'), [
            'title' => 'Laravel Mastery No Thumbnail',
            'description' => 'Apprenez Laravel sans miniature',
            'category' => 'Programming',
            'difficulty' => 'débutant',
            'duration_minutes' => 180,
            'language' => 'Français',
            'tags' => 'laravel, php, api',
            'objectives' => "Créer une API\nUtiliser Eloquent",
            'requirements' => 'Bases PHP',
            'price' => 0,
            'video' => $video,
        ])
        ->assertRedirect(route('professor.courses.index'))
        ->assertSessionHas('success');

    $course = Course::query()->where('title', 'Laravel Mastery No Thumbnail')->first();

    expect($course)->not->toBeNull()
        ->and($course->video_path)->toStartWith('videos/')
        ->and($course->thumbnail)->toBeNull();
});
