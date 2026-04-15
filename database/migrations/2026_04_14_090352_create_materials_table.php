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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->string('name');                     // Material name
            $table->unsignedInteger('quantity')->default(0); // Current stock

            $table->string('tablar')->nullable();       // Shelf/Tray (e.g., A1, B2)
            $table->unsignedInteger('threshold')->nullable(); // Low stock threshold
            $table->string('type')->nullable();         // Category/type

            $table->timestamps();

            // Optional: index for faster search/filter later
            $table->index('name');
            $table->index('tablar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
