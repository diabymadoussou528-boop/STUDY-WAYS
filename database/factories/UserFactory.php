<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'is_active' => true,
            'is_super_admin' => false,
            'first_login' => false,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_super_admin' => true,
            'first_login' => false,
        ]);
    }

    public function simpleAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'first_login' => false,
        ]);
    }

    public function pendingFirstLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_super_admin' => false,
            'first_login' => true,
        ]);
    }

    public function professor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'professor',
            'specialization' => fake()->randomElement([
                'Développement Web',
                'UI/UX Design',
                'Data Science',
                'Réseaux & Sécurité',
                'Laravel / PHP',
            ]),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
