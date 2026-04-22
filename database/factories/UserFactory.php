<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_ZORGBEGELEIDER,
            'is_active' => true,
            'team_id' => Team::factory(),
            'dienstverband' => 'intern',
        ];
    }

    public function teamleider(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_TEAMLEIDER]);
    }

    public function zorgbegeleider(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ZORGBEGELEIDER]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
