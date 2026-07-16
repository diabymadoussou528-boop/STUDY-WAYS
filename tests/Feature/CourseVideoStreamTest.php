<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('course video stream returns partial content for range requests', function () {
    Storage::fake('public');

    $payload = random_bytes(4096);
    Storage::disk('public')->put('videos/demo.mp4', $payload);

    $course = Course::factory()->published()->create([
        'category_id' => Category::query()->create(['name' => 'Stream', 'slug' => 'stream-'.uniqid()])->id,
        'user_id' => User::factory()->professor()->create()->id,
        'video_path' => 'videos/demo.mp4',
        'video_url' => null,
    ]);

    expect($course->videoUrl())->toBe(route('courses.video.stream', $course));

    $this->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee(route('courses.video.stream', $course), false);

    $this->withHeaders(['Range' => 'bytes=0-1023'])
        ->get(route('courses.video.stream', $course))
        ->assertStatus(206)
        ->assertHeader('Accept-Ranges', 'bytes')
        ->assertHeader('Content-Range', 'bytes 0-1023/4096')
        ->assertHeader('Content-Length', '1024');
});

test('course video stream returns full file without content-range when no range header', function () {
    Storage::fake('public');

    $payload = random_bytes(2048);
    Storage::disk('public')->put('videos/full.mp4', $payload);

    $course = Course::factory()->published()->create([
        'category_id' => Category::query()->create(['name' => 'Full', 'slug' => 'full-'.uniqid()])->id,
        'user_id' => User::factory()->professor()->create()->id,
        'video_path' => 'videos/full.mp4',
    ]);

    $this->get(route('courses.video.stream', $course))
        ->assertSuccessful()
        ->assertHeader('Accept-Ranges', 'bytes')
        ->assertHeader('Content-Length', '2048')
        ->assertHeaderMissing('Content-Range');
});

test('course video stream returns not found when course has no video', function () {
    $course = Course::factory()->published()->create([
        'category_id' => Category::query()->create(['name' => 'Empty', 'slug' => 'empty-'.uniqid()])->id,
        'user_id' => User::factory()->professor()->create()->id,
        'video_path' => null,
        'video_url' => null,
        'video_drive_id' => null,
    ]);

    $this->get(route('courses.video.stream', $course))
        ->assertNotFound();
});
