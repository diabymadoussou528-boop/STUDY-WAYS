<?php

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('course detail uses local stream url instead of google drive share links', function () {
    Storage::fake('public');
    Storage::disk('public')->put('videos/local-course.mp4', random_bytes(2048));

    $course = Course::factory()->published()->create([
        'category_id' => Category::query()->create(['name' => 'Disk', 'slug' => 'disk-'.uniqid()])->id,
        'user_id' => User::factory()->professor()->create()->id,
        'video_path' => 'videos/local-course.mp4',
        'video_drive_id' => 'drive-file-id',
        'google_drive_video_id' => 'drive-file-id',
        'google_drive_video_url' => 'https://drive.google.com/file/d/drive-file-id/view',
        'video_url' => 'https://drive.google.com/uc?id=drive-file-id',
        'upload_status' => 'completed',
    ]);

    expect($course->videoUrl())->toBe(route('courses.video.stream', $course))
        ->and($course->videoUrl())->not->toContain('drive.google.com');

    $this->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee(route('courses.video.stream', $course), false)
        ->assertDontSee('drive.google.com', false);

    $this->withHeaders(['Range' => 'bytes=0-127'])
        ->get(route('courses.video.stream', $course))
        ->assertStatus(206)
        ->assertHeader('Content-Range', 'bytes 0-127/2048');
});

test('media migrate to disk clears drive metadata when file already exists locally', function () {
    Storage::fake('public');
    Storage::disk('public')->put('videos/already-local.mp4', random_bytes(512));

    $course = Course::factory()->published()->create([
        'category_id' => Category::query()->create(['name' => 'Clear', 'slug' => 'clear-'.uniqid()])->id,
        'user_id' => User::factory()->professor()->create()->id,
        'status' => CourseStatus::Published,
        'video_path' => 'videos/already-local.mp4',
        'video_drive_id' => 'stale-drive-id',
        'google_drive_video_url' => 'https://drive.google.com/file/d/stale-drive-id/view',
        'google_drive_video_id' => 'stale-drive-id',
    ]);

    $this->artisan('media:migrate-to-disk', ['--force' => true])
        ->assertSuccessful();

    $course->refresh();

    expect($course->video_path)->toBe('videos/already-local.mp4')
        ->and($course->video_drive_id)->toBeNull()
        ->and($course->google_drive_video_id)->toBeNull()
        ->and($course->google_drive_video_url)->toBeNull()
        ->and($course->videoUrl())->toBe(route('courses.video.stream', $course));
});

test('demo accounts can authenticate for each role', function () {
    $this->seed(DemoAccountsSeeder::class);

    foreach ([
        ['student@studways.test', 'Douss123', 'student.dashboard'],
        ['professor@studways.test', 'Douss123', 'professor.dashboard'],
        ['admin@studways.test', 'Douss123', 'admin.dashboard'],
        ['diabymadoussou528@gmail.com', env('SUPER_ADMIN_PASSWORD', 'Douss123'), 'admin.dashboard'],
    ] as [$email, $password, $dashboard]) {
        $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ])->assertRedirect(route($dashboard, absolute: false));

        $this->assertAuthenticated();
        $this->post('/logout');
        $this->assertGuest();
    }
});

test('catalogue lists all published courses with playable video metadata', function () {
    Storage::fake('public');
    Storage::disk('public')->put('videos/catalog.mp4', random_bytes(1024));

    $professor = User::factory()->professor()->create([
        'password' => Hash::make('password'),
    ]);

    $courses = collect([
        'Alpha Course',
        'Beta Course',
        'Gamma Course',
    ])->map(fn (string $title) => Course::factory()->published()->create([
        'title' => $title,
        'category_id' => Category::query()->create(['name' => $title, 'slug' => Str::slug($title).'-'.uniqid()])->id,
        'user_id' => $professor->id,
        'video_path' => 'videos/catalog.mp4',
        'video_drive_id' => null,
        'google_drive_video_url' => null,
        'upload_status' => 'completed',
    ]));

    $response = $this->get(route('catalog.index'))
        ->assertSuccessful();

    foreach ($courses as $course) {
        $response->assertSee($course->title);
        expect($course->fresh()->videoUrl())->toContain('/video/stream');
    }
});
