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
        Schema::create('focus_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('user_name');
            $table->float('focus_score');
            $table->boolean('face_detected');
            $table->integer('total_focus_time');
            $table->timestamp('last_update')->useCurrent();
            $table->timestamps();

            // Index for faster queries
            $table->index(['meeting_id', 'user_id']);
            $table->index('last_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('focus_data');
    }
};
