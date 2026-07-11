<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'student']),
            'course_id' => Course::factory(),
            'status' => EnrollmentStatus::Active,
            'progress' => fake()->numberBetween(0, 99),
            'enrolled_at' => now(),
            'last_accessed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Completed,
            'progress' => 100,
            'completed_at' => now(),
            'certificate_eligible' => true,
        ]);
    }

    public function certificateEligible(): static
    {
        return $this->completed();
    }
}
