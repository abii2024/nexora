<?php

namespace Database\Factories;

use App\Enums\UrenStatus;
use App\Models\Client;
use App\Models\Urenregistratie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Urenregistratie>
 */
class UrenregistratieFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->randomElement(['08:00:00', '09:00:00', '10:00:00', '13:00:00']);
        $eind = fake()->randomElement(['12:00:00', '14:00:00', '15:30:00', '17:00:00']);
        $uren = round(
            (strtotime($eind) - strtotime($start)) / 3600,
            2
        );

        return [
            'user_id' => User::factory()->zorgbegeleider(),
            'client_id' => Client::factory(),
            'datum' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'starttijd' => $start,
            'eindtijd' => $eind,
            'uren' => max($uren, 0.25),
            'notities' => fake()->optional()->sentence(),
            'status' => UrenStatus::Concept,
        ];
    }

    public function concept(): static
    {
        return $this->state(fn () => ['status' => UrenStatus::Concept]);
    }

    public function ingediend(): static
    {
        return $this->state(fn () => ['status' => UrenStatus::Ingediend]);
    }

    public function goedgekeurd(): static
    {
        return $this->state(fn () => ['status' => UrenStatus::Goedgekeurd]);
    }

    public function afgekeurd(): static
    {
        return $this->state(fn () => ['status' => UrenStatus::Afgekeurd]);
    }
}
