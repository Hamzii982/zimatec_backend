<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_project_step_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_project_step_id')
                ->constrained('workflow_project_steps')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['workflow_project_step_id', 'user_id'], 'wpsa_step_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_project_step_assignees');
    }
};