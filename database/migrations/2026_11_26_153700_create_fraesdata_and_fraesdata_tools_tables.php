<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraesdata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->cascadeOnDelete();
            $table->foreignId('bauteil_id')->nullable()->constrained('bauteile')->cascadeOnDelete();
            $table->foreignId('related_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('name');
            $table->string('document_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['position_id', 'created_at']);
            $table->index(['bauteil_id', 'created_at']);
        });

        Schema::create('fraesdata_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraesdata_id')->constrained('fraesdata')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->string('tool_number', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraesdata_tools');
        Schema::dropIfExists('fraesdata');
    }
};
