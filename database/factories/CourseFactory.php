<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = Category::query()->first() ?? Category::query()->create([
            'name' => fake()->word(),
            'slug' => fake()->slug(),
        ]);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category_id' => $category->id,
            'user_id' => User::factory()->create(['role' => 'professor'])->id,
            'status' => CourseStatus::Published,
            'published_at' => now(),
            'price' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function paid(float $price = 15000): static
    {
        return $this->state(fn () => [
            'price' => $price,
        ]);
    }

    public function premiumOnly(): static
    {
        return $this->state(fn () => [
            'is_premium_only' => true,
        ]);
    }
}
