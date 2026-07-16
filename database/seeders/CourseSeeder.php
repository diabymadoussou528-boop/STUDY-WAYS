<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up existing seeded courses/lessons
        Course::query()->delete();
        CourseModule::query()->delete();
        Lesson::query()->delete();

        // 1. Create categories
        $catWeb = Category::query()->firstOrCreate(
            ['name' => 'Informatique'],
            ['slug' => 'informatique']
        );

        $catVue = Category::query()->firstOrCreate(
            ['name' => 'Vue.js'],
            ['slug' => 'vue-js']
        );

        $catReact = Category::query()->firstOrCreate(
            ['name' => 'React'],
            ['slug' => 'react']
        );

        // 2. Create teachers
        $profPierre = User::query()->firstOrCreate(
            ['email' => 'pierre@studways.test'],
            [
                'name' => 'Pierre Nikolaus',
                'role' => 'professor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $profMagnolia = User::query()->firstOrCreate(
            ['email' => 'magnolia@studways.test'],
            [
                'name' => 'Magnolia Kub',
                'role' => 'professor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $profRita = User::query()->firstOrCreate(
            ['email' => 'rita@studways.test'],
            [
                'name' => 'Rita Beatty PhD',
                'role' => 'professor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Define courses
        $coursesData = [
            [
                'title' => 'Vue.js Essentials',
                'description' => 'Maîtrisez les bases de Vue.js pour créer des applications web interactives.',
                'short_description' => 'Apprenez à développer des composants avec Vue.js.',
                'category_id' => $catVue->id,
                'user_id' => $profPierre->id,
                'thumbnail' => 'thumbnails/vuejs_essentials.png',
                'video_path' => 'videos/Q7qd0FGfeb4rMq8ppJzepljG3ayu594uLyVO84L5.mp4',
            ],
            [
                'title' => 'Maîtriser Laravel 13',
                'description' => 'Découvrez les fonctionnalités de Laravel 13 et construisez des architectures web d\'élite.',
                'short_description' => 'Développement backend moderne avec Laravel 13.',
                'category_id' => $catWeb->id,
                'user_id' => $profMagnolia->id,
                'thumbnail' => 'thumbnails/maitriser_laravel.png',
                'video_path' => 'videos/Q7qd0FGfeb4rMq8ppJzepljG3ayu594uLyVO84L5.mp4',
            ],
            [
                'title' => 'ReactJS — Développement Frontend',
                'description' => 'Devenez expert en développement React et apprenez à gérer les états complexes.',
                'short_description' => 'Applications SPA modernes avec React.',
                'category_id' => $catReact->id,
                'user_id' => $profRita->id,
                'thumbnail' => 'thumbnails/react_laravel.png',
                'video_path' => 'videos/Q7qd0FGfeb4rMq8ppJzepljG3ayu594uLyVO84L5.mp4',
            ],
            [
                'title' => 'Développement Frontend',
                'description' => 'Un parcours complet pour maîtriser l\'intégration web et les technologies frontend.',
                'short_description' => 'Intégration moderne HTML, CSS et JavaScript.',
                'category_id' => $catWeb->id,
                'user_id' => $profRita->id,
                'thumbnail' => 'thumbnails/dev_frontend.png',
                'video_path' => 'videos/Q7qd0FGfeb4rMq8ppJzepljG3ayu594uLyVO84L5.mp4',
            ],
            [
                'title' => 'Martin Bernard',
                'description' => 'Découvrez les techniques avancées de gestion de projet informatique.',
                'short_description' => 'Gestion agile et architecture technique.',
                'category_id' => $catWeb->id,
                'user_id' => $profRita->id,
                'thumbnail' => 'thumbnails/laptop_coffee.png',
                'video_path' => 'videos/Q7qd0FGfeb4rMq8ppJzepljG3ayu594uLyVO84L5.mp4',
            ],
        ];

        foreach ($coursesData as $idx => $data) {
            $course = Course::query()->updateOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'short_description' => $data['short_description'],
                    'category_id' => $data['category_id'],
                    'user_id' => $data['user_id'],
                    'status' => CourseStatus::Published,
                    'approval_status' => 'approved',
                    'published_at' => now()->subDays($idx),
                    'price' => 0,
                    'difficulty' => 'beginner',
                    'duration_minutes' => 120,
                    'thumbnail' => $data['thumbnail'],
                    'video_path' => $data['video_path'],
                    'video_url' => null,
                    'video_drive_id' => null,
                    'thumbnail_drive_id' => null,
                    'thumbnail_url' => null,
                    'google_drive_video_id' => null,
                    'google_drive_thumbnail_id' => null,
                    'google_drive_video_url' => null,
                    'google_drive_thumbnail_url' => null,
                    'upload_status' => 'completed',
                ]
            );

            // Seed a module and lesson
            $module = CourseModule::query()->firstOrCreate(
                ['course_id' => $course->id, 'title' => 'Introduction'],
                ['description' => 'Bases et configuration.', 'sort_order' => 1]
            );

            Lesson::query()->updateOrCreate(
                ['course_id' => $course->id, 'title' => 'Introduction générale'],
                [
                    'module_id' => $module->id,
                    'resource_path' => $data['video_path'],
                    'lesson_type' => LessonType::Video,
                    'duration_seconds' => 300,
                    'sort_order' => 1,
                    'is_preview' => true,
                ]
            );
        }
    }
}
