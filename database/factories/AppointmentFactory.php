<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $course = Course::factory()->create(['user_id' => $professor->id]);

        return [
            'student_id' => User::factory()->create(['role' => 'student']),
            'professor_id' => $professor->id,
            'course_id' => $course->id,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'status' => AppointmentStatus::Pending,
            'message' => fake()->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Accepted,
        ]);
    }
}
