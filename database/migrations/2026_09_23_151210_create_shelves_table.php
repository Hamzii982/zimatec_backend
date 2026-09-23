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
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lager_id')
                ->constrained('lager')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // don't allow deleting a lager that still has shelves
            $table->string('name');           // e.g. old "tablar" value, "A1", "Regal 3", ...
            $table->string('code')->nullable(); // optional short code, barcode, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['lager_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelves');
    }
};
