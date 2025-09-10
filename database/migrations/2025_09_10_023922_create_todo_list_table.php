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
        Schema::create('todo_list', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('assigne')->nullable();
            $table->date('due_date');
            $table->integer('time_tracked')->default(0);
            $table->enum('status', ['pending', 'open', 'in_progress', 'completed', 'stuck'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->enum('type', ['feature_enhancements', 'other', 'bug'])->nullable();
            $table->integer('estimated_sp')->nullable();
            $table->integer('actual_sp')->nullable();
            $table->timestamps();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_list');
    }
};
