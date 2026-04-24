<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('urenregistraties', function (Blueprint $table) {
            $table->text('afkeur_reden')->nullable()->after('notities');
        });
    }

    public function down(): void
    {
        Schema::table('urenregistraties', function (Blueprint $table) {
            $table->dropColumn('afkeur_reden');
        });
    }
};
