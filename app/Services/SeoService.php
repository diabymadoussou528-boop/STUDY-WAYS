<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * @return array<string, mixed>
     */
    public function forCourse(Course $course): array
    {
        $title = $course->meta_title ?: $course->title.' — StudyWays';
        $description = $course->meta_description ?: Str::limit(strip_tags($course->description ?? ''), 160);
        $image = $course->og_image ?: asset('images/og-default.jpg');
        $url = $course->canonical_url ?: route('courses.show', $course);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $course->meta_keywords,
            'og' => [
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'url' => $url,
                'type' => 'website',
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $description,
                'image' => $image,
            ],
            'canonical' => $url,
            'jsonLd' => $this->courseSchema($course, $url, $description),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function sitemapEntries(): array
    {
        $entries = [
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('catalog.index'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => route('testimonials.index'), 'changefreq' => 'weekly', 'priority' => '0.6'],
        ];

        Course::query()->published()->each(function (Course $course) use (&$entries) {
            $entries[] = [
                'loc' => route('courses.show', $course),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

        return $entries;
    }

    /** @return array<string, mixed> */
    private function courseSchema(Course $course, string $url, string $description): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => $description,
            'url' => $url,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'StudyWays',
                'sameAs' => URL::to('/'),
            ],
        ];
    }
}
