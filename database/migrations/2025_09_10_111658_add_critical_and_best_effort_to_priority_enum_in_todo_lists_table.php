<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new enum values to priority column
        DB::statement("ALTER TABLE todo_lists MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'critical', 'best_effort') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE todo_lists MODIFY COLUMN priority ENUM('low', 'medium', 'high') NULL");
    }
};
