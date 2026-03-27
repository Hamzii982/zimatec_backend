<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['fehler', 'vorschlag', 'anleitung']);

            $table->string('machine')->index();

            $table->longText('description');

            $table->enum('priority', ['niedrig', 'mittel', 'hoch'])->default('niedrig');

            $table->enum('ai_solution', ['ja', 'nein', 'naja']);

            $table->string('name')->nullable();

            $table->string('attachment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
