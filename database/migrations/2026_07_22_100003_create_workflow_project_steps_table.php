<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_project_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_project_id')
                ->constrained('workflow_projects')
                ->cascadeOnDelete();
            $table->foreignId('step_id')
                ->constrained('workflow_steps')
                ->cascadeOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed'])
                ->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('order_index');
            $table->timestamps();

            $table->unique(['workflow_project_id', 'step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_project_steps');
    }
};
