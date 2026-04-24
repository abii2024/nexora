<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('zorgbegeleider')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->foreignId('team_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
            $table->string('dienstverband')->nullable()->after('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn(['role', 'is_active', 'team_id', 'dienstverband']);
        });
    }
};
