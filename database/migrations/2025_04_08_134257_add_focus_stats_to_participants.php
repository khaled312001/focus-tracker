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
        Schema::table('participants', function (Blueprint $table) {
            $table->decimal('average_focus', 5, 1)->default(0);
            $table->decimal('highest_focus', 5, 1)->default(0);
            $table->decimal('lowest_focus', 5, 1)->default(0);
            $table->integer('total_focus_logs')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('average_focus');
            $table->dropColumn('highest_focus');
            $table->dropColumn('lowest_focus');
            $table->dropColumn('total_focus_logs');
        });
    }
};
