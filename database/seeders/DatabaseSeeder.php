<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $teamRotterdam = Team::factory()->create([
            'name' => 'Team Rotterdam-Noord',
            'organization' => 'Stichting Beschermd Wonen Rijnmond',
        ]);

        $teamAmsterdam = Team::factory()->create([
            'name' => 'Team Amsterdam-Zuid',
            'organization' => 'Stichting Beschermd Wonen Rijnmond',
        ]);

        $teamleider = User::factory()->teamleider()->create([
            'name' => 'Fatima El Amrani',
            'email' => 'teamleider@nexora.test',
            'team_id' => $teamRotterdam->id,
            'dienstverband' => 'intern',
        ]);

        $jeroen = User::factory()->zorgbegeleider()->create([
            'name' => 'Jeroen Bakker',
            'email' => 'zorgbegeleider@nexora.test',
            'team_id' => $teamRotterdam->id,
            'dienstverband' => 'intern',
        ]);

        User::factory()->zorgbegeleider()->inactive()->create([
            'name' => 'Ilse Voskuil',
            'email' => 'inactief@nexora.test',
            'team_id' => $teamRotterdam->id,
            'dienstverband' => 'extern',
        ]);

        // Tweede zorgbegeleider in zelfde team (voor cross-caregiver US-02 tests).
        $mo = User::factory()->zorgbegeleider()->create([
            'name' => 'Mo Yilmaz',
            'email' => 'mo@nexora.test',
            'team_id' => $teamRotterdam->id,
            'dienstverband' => 'intern',
        ]);

        // Zorgbegeleider in een ander team (voor cross-team US-02 tests).
        User::factory()->zorgbegeleider()->create([
            'name' => 'Noa De Vries',
            'email' => 'noa@nexora.test',
            'team_id' => $teamAmsterdam->id,
            'dienstverband' => 'intern',
        ]);

        // Test-cliënten voor US-02 autorisatie-tests:
        //   C1: Jeroen = primair (mag zien)
        //   C2: Jeroen = secundair (mag zien)
        //   C3: Jeroen NIET gekoppeld, alleen Mo (mag NIET zien -> 403)
        $c1 = Client::factory()->actief()->wmo()->create([
            'team_id' => $teamRotterdam->id,
            'voornaam' => 'Sanne',
            'achternaam' => 'de Wit',
            'created_by_user_id' => $teamleider->id,
        ]);
        $c1->caregivers()->attach($jeroen->id, [
            'role' => Client::ROLE_PRIMAIR,
            'created_by_user_id' => $teamleider->id,
        ]);

        $c2 = Client::factory()->actief()->wlz()->create([
            'team_id' => $teamRotterdam->id,
            'voornaam' => 'Thomas',
            'achternaam' => 'Groen',
            'created_by_user_id' => $teamleider->id,
        ]);
        $c2->caregivers()->attach($jeroen->id, [
            'role' => Client::ROLE_SECUNDAIR,
            'created_by_user_id' => $teamleider->id,
        ]);

        $c3 = Client::factory()->actief()->jw()->create([
            'team_id' => $teamRotterdam->id,
            'voornaam' => 'Amira',
            'achternaam' => 'Hassan',
            'created_by_user_id' => $teamleider->id,
        ]);
        $c3->caregivers()->attach($mo->id, [
            'role' => Client::ROLE_PRIMAIR,
            'created_by_user_id' => $teamleider->id,
        ]);
    }
}
