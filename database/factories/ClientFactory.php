<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'voornaam' => fake()->firstName(),
            'achternaam' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefoon' => fake()->numerify('06########'),
            'bsn' => fake()->unique()->numerify('#########'),
            'geboortedatum' => fake()->dateTimeBetween('-85 years', '-18 years')->format('Y-m-d'),
            'status' => Client::STATUS_ACTIEF,
            'care_type' => fake()->randomElement([Client::CARE_WMO, Client::CARE_WLZ, Client::CARE_JW]),
            'created_by_user_id' => null,
        ];
    }

    public function actief(): static
    {
        return $this->state(fn () => ['status' => Client::STATUS_ACTIEF]);
    }

    public function wachtlijst(): static
    {
        return $this->state(fn () => ['status' => Client::STATUS_WACHT]);
    }

    public function inactief(): static
    {
        return $this->state(fn () => ['status' => Client::STATUS_INACTIEF]);
    }

    public function wmo(): static
    {
        return $this->state(fn () => ['care_type' => Client::CARE_WMO]);
    }

    public function wlz(): static
    {
        return $this->state(fn () => ['care_type' => Client::CARE_WLZ]);
    }

    public function jw(): static
    {
        return $this->state(fn () => ['care_type' => Client::CARE_JW]);
    }
}
