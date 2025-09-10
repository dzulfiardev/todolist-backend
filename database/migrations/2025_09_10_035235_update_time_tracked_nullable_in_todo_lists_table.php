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
        Schema::table('todo_lists', function (Blueprint $table) {
            $table->integer('time_tracked')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todo_lists', function (Blueprint $table) {
            $table->integer('time_tracked')->default(0)->change();
        });
    }
};
