<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $professor = User::query()->where('role', 'professor')->first()
            ?? User::factory()->professor()->create([
                'name' => 'Prof. Demo',
                'bio' => 'Développeur passionné par l\'enseignement et la transmission de connaissances techniques.',
            ]);

        $cat = Category::query()->firstOrCreate(
            ['name' => 'Informatique'],
            ['slug' => 'informatique'],
        );

        $course1 = Course::query()->updateOrCreate(
            ['title' => 'Algorithmique'],
            [
                'description' => 'Maîtrisez les fondamentaux de l\'algorithmique et des structures de données. Ce cours couvre les concepts essentiels pour résoudre des problèmes complexes de manière efficace.',
                'short_description' => 'Apprenez les bases de l\'algorithmique et des structures de données.',
                'category_id' => $cat->id,
                'user_id' => $professor->id,
                'status' => CourseStatus::Published,
                'published_at' => now(),
                'price' => 0,
                'difficulty' => 'beginner',
                'duration_minutes' => 480,
                'objectives' => [
                    'Comprendre la complexité algorithmique',
                    'Maîtriser les structures de données fondamentales',
                    'Résoudre des problèmes avec des algorithmes efficaces',
                    'Préparer des entretiens techniques',
                ],
                'requirements' => [
                    'Notions de base en programmation',
                    'Motivation et régularité',
                ],
                'meta_keywords' => 'Algorithmique, Structures de données, PHP',
            ],
        );

        $module1 = CourseModule::query()->firstOrCreate(
            ['course_id' => $course1->id, 'title' => 'Introduction & bases'],
            ['description' => 'Découvrez les concepts fondamentaux.', 'sort_order' => 1],
        );

        Lesson::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Qu\'est-ce que l\'algorithmique ?'],
            [
                'module_id' => $module1->id,
                'video_url' => 'https://www.youtube.com/embed/videoseries?list=PL0AhTDV4Osc9pJ1drLrWc9KVpWAZe6tpZ',
                'lesson_type' => LessonType::Video,
                'duration_seconds' => 320,
                'sort_order' => 1,
                'is_preview' => true,
            ],
        );

        Lesson::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Complexité algorithmique'],
            [
                'module_id' => $module1->id,
                'content' => 'La complexité algorithmique mesure l\'efficacité d\'un algorithme en fonction de la taille des données.',
                'lesson_type' => LessonType::Text,
                'duration_seconds' => 600,
                'sort_order' => 2,
            ],
        );

        $course2 = Course::query()->updateOrCreate(
            ['title' => 'Réseaux'],
            [
                'description' => 'Comprenez les réseaux informatiques, TCP/IP et la sécurité réseau. Un cours complet pour maîtriser les fondamentaux du networking.',
                'short_description' => 'TCP/IP, protocoles et sécurité réseau.',
                'category_id' => $cat->id,
                'user_id' => $professor->id,
                'status' => CourseStatus::Published,
                'published_at' => now(),
                'price' => 0,
                'difficulty' => 'intermediate',
                'duration_minutes' => 360,
                'objectives' => [
                    'Comprendre le modèle OSI et TCP/IP',
                    'Configurer des réseaux locaux',
                    'Sécuriser une infrastructure réseau',
                ],
                'requirements' => ['Notions en informatique'],
                'meta_keywords' => 'Réseaux, TCP/IP, Sécurité',
            ],
        );

        $module2 = CourseModule::query()->firstOrCreate(
            ['course_id' => $course2->id, 'title' => 'Fondamentaux réseau'],
            ['sort_order' => 1],
        );

        Lesson::query()->updateOrCreate(
            ['course_id' => $course2->id, 'title' => 'Introduction aux réseaux'],
            [
                'module_id' => $module2->id,
                'video_url' => 'https://www.youtube.com/embed/videoseries?list=PLSuzYIVSEUT6SE-5dSZq-zbtItfHqWdqv',
                'lesson_type' => LessonType::Video,
                'duration_seconds' => 450,
                'sort_order' => 1,
                'is_preview' => true,
            ],
        );

        $student = User::query()->where('role', 'student')->first();
        if ($student) {
            Review::query()->firstOrCreate(
                ['user_id' => $student->id, 'course_id' => $course1->id],
                ['rating' => 5, 'comment' => 'Excellent cours, très bien structuré et facile à suivre.'],
            );
        }
    }
}
