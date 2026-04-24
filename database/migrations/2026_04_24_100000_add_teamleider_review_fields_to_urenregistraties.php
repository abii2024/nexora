<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('urenregistraties', function (Blueprint $table) {
            $table->foreignId('goedgekeurd_door_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('beoordeeld_op')->nullable()->after('goedgekeurd_door_user_id');
            $table->index(['status', 'beoordeeld_op']);
        });
    }

    public function down(): void
    {
        Schema::table('urenregistraties', function (Blueprint $table) {
            $table->dropIndex(['status', 'beoordeeld_op']);
            $table->dropConstrainedForeignId('goedgekeurd_door_user_id');
            $table->dropColumn('beoordeeld_op');
        });
    }
};
