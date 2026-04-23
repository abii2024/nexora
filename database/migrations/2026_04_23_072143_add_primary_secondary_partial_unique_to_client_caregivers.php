<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * US-08 AC-5: max 1 primair + 1 secundair per client.
 *
 * Partial unique indexes garanderen dit op DB-niveau, onafhankelijk van
 * application logic (defense in depth tegen race conditions bij gelijk-
 * tijdige edits).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "CREATE UNIQUE INDEX client_caregivers_primary_unique
             ON client_caregivers(client_id) WHERE role = 'primair'"
        );

        DB::statement(
            "CREATE UNIQUE INDEX client_caregivers_secondary_unique
             ON client_caregivers(client_id) WHERE role = 'secundair'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS client_caregivers_primary_unique');
        DB::statement('DROP INDEX IF EXISTS client_caregivers_secondary_unique');
    }
};
