<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_consumption', function (Blueprint $table) {
            $table->enum('consumption_type', ['use', 'return', 'reserve', 'restock', 'adjust', 'delivery', 'damaged'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_consumption', function (Blueprint $table) {
            $table->enum('consumption_type', ['use', 'return', 'reserve', 'restock', 'adjust', 'delivery'])->change();
        });
    }
};
