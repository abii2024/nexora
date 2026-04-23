<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('urenregistraties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('datum');
            $table->time('starttijd');
            $table->time('eindtijd');
            $table->decimal('uren', 5, 2);
            $table->text('notities')->nullable();
            $table->string('status')->default('concept');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['client_id', 'datum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urenregistraties');
    }
};
