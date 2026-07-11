<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'student']),
            'course_id' => Course::factory()->paid(),
            'amount' => fake()->randomFloat(2, 5000, 50000),
            'currency' => 'XOF',
            'provider' => 'manual',
            'status' => 'completed',
        ];
    }

    public function forSubscription(): static
    {
        return $this->state(fn () => [
            'course_id' => null,
            'amount' => 9900,
        ]);
    }
}
