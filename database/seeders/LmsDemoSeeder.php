<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AiChatMessage;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'demo-lms'],
            ['name' => 'Développement Web'],
        );

        $professor = User::query()->firstOrCreate(
            ['email' => 'prof.demo@studways.test'],
            [
                'name' => 'Prof. Awa Diallo',
                'role' => 'professor',
                'specialization' => 'Développement Web',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );

        $students = collect([
            ['email' => 'student1@studways.test', 'name' => 'Moussa Koné'],
            ['email' => 'student2@studways.test', 'name' => 'Fatou Ba'],
            ['email' => 'student3@studways.test', 'name' => 'Ibrahim Sow'],
        ])->map(fn (array $data) => User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'role' => 'student',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        ));

        $freeCourse = Course::query()->firstOrCreate(
            ['title' => 'Introduction au HTML & CSS'],
            [
                'description' => 'Apprenez les bases du développement web front-end.',
                'category_id' => $category->id,
                'user_id' => $professor->id,
                'status' => CourseStatus::Published,
                'published_at' => now()->subMonths(2),
                'price' => 0,
            ],
        );

        $paidCourse = Course::query()->firstOrCreate(
            ['title' => 'Laravel Avancé'],
            [
                'description' => 'Architecture, services, tests et déploiement Laravel.',
                'category_id' => $category->id,
                'user_id' => $professor->id,
                'status' => CourseStatus::Published,
                'published_at' => now()->subMonth(),
                'price' => 25000,
            ],
        );

        $premiumStudent = $students->first();
        $premiumStudent->update(['is_premium' => true, 'premium_plan' => 'monthly']);
        Subscription::query()->firstOrCreate(
            ['user_id' => $premiumStudent->id, 'plan' => 'monthly'],
            [
                'status' => SubscriptionStatus::Active,
                'provider' => 'manual',
                'amount' => 9900,
                'currency' => 'XOF',
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
            ],
        );

        foreach ($students as $index => $student) {
            Enrollment::query()->firstOrCreate(
                ['user_id' => $student->id, 'course_id' => $freeCourse->id],
                [
                    'status' => $index === 0 ? EnrollmentStatus::Completed : EnrollmentStatus::Active,
                    'progress' => $index === 0 ? 100 : fake()->numberBetween(20, 80),
                    'enrolled_at' => now()->subWeeks(3 - $index),
                    'completed_at' => $index === 0 ? now()->subWeek() : null,
                    'certificate_eligible' => $index === 0,
                ],
            );

            if ($index > 0) {
                Enrollment::query()->firstOrCreate(
                    ['user_id' => $student->id, 'course_id' => $paidCourse->id],
                    [
                        'status' => EnrollmentStatus::Active,
                        'progress' => fake()->numberBetween(10, 60),
                        'enrolled_at' => now()->subDays(10),
                    ],
                );
            }
        }

        Payment::query()->firstOrCreate(
            ['user_id' => $students[1]->id, 'course_id' => $paidCourse->id],
            [
                'amount' => 25000,
                'currency' => 'XOF',
                'provider' => 'manual',
                'status' => 'completed',
            ],
        );

        Payment::query()->firstOrCreate(
            ['user_id' => $premiumStudent->id, 'subscription_id' => Subscription::query()->where('user_id', $premiumStudent->id)->value('id')],
            [
                'course_id' => null,
                'amount' => 9900,
                'currency' => 'XOF',
                'provider' => 'manual',
                'status' => 'completed',
            ],
        );

        Appointment::query()->firstOrCreate(
            [
                'student_id' => $students[1]->id,
                'professor_id' => $professor->id,
                'course_id' => $freeCourse->id,
                'scheduled_at' => now()->addDays(3)->startOfHour(),
            ],
            [
                'status' => AppointmentStatus::Pending,
                'message' => 'J\'aimerais clarifier les sélecteurs CSS.',
            ],
        );

        Appointment::query()->firstOrCreate(
            [
                'student_id' => $premiumStudent->id,
                'professor_id' => $professor->id,
                'course_id' => $freeCourse->id,
                'scheduled_at' => now()->addDays(5)->startOfHour(),
            ],
            [
                'status' => AppointmentStatus::Accepted,
                'message' => 'Révision du projet final.',
                'meeting_link' => 'https://meet.studways.test/demo',
            ],
        );

        Review::query()->firstOrCreate(
            ['user_id' => $premiumStudent->id, 'course_id' => $freeCourse->id],
            ['rating' => 5, 'comment' => 'Excellent cours, très clair et bien structuré.'],
        );

        Review::query()->firstOrCreate(
            ['user_id' => $students[1]->id, 'course_id' => $freeCourse->id],
            ['rating' => 4, 'comment' => 'Bon contenu, j\'aurais aimé plus d\'exercices pratiques.'],
        );

        if (Schema::hasTable('ai_chat_messages')) {
            AiChatMessage::query()->firstOrCreate(
                [
                    'user_id' => $premiumStudent->id,
                    'course_id' => $freeCourse->id,
                    'role' => 'user',
                    'content' => 'Explique-moi la différence entre flexbox et grid.',
                ],
                ['topic' => 'CSS Layout', 'mode' => 'tutor'],
            );

            AiChatMessage::query()->firstOrCreate(
                [
                    'user_id' => $premiumStudent->id,
                    'course_id' => $freeCourse->id,
                    'role' => 'assistant',
                    'content' => 'Flexbox est idéal pour les alignements unidimensionnels, Grid pour les mises en page bidimensionnelles.',
                ],
                ['topic' => 'CSS Layout', 'mode' => 'tutor'],
            );
        }
    }
}
