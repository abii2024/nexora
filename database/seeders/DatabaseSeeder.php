<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $team = Team::factory()->create([
            'name' => 'Team Rotterdam-Noord',
            'organization' => 'Stichting Beschermd Wonen Rijnmond',
        ]);

        User::factory()->teamleider()->create([
            'name' => 'Fatima El Amrani',
            'email' => 'teamleider@nexora.test',
            'team_id' => $team->id,
            'dienstverband' => 'intern',
        ]);

        User::factory()->zorgbegeleider()->create([
            'name' => 'Jeroen Bakker',
            'email' => 'zorgbegeleider@nexora.test',
            'team_id' => $team->id,
            'dienstverband' => 'intern',
        ]);

        User::factory()->zorgbegeleider()->inactive()->create([
            'name' => 'Ilse Voskuil',
            'email' => 'inactief@nexora.test',
            'team_id' => $team->id,
            'dienstverband' => 'extern',
        ]);
    }
}
