<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flag which lager uses sheet-cutting workflow instead of plain qty.
        Schema::table('lager', function (Blueprint $table) {
            $table->string('type')->nullable()->after('description');
            // e.g. 'holz' for this lager, null for all others (unchanged behavior).
        });

        Schema::create('material_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('code', 32)->unique();       // e.g. SHT-0001
            $table->decimal('length_mm', 10, 2);
            $table->decimal('width_mm', 10, 2);
            $table->decimal('thickness_mm', 10, 2);
            $table->enum('status', ['in_stock', 'used'])->default('in_stock');
            $table->foreignId('parent_sheet_id')->nullable()
                ->constrained('material_sheets')->nullOnDelete();
            $table->timestamps();

            $table->index(['material_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_sheets');
        Schema::table('lager', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};