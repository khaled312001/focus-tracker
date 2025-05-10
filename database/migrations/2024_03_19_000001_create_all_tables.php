<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create users table if it doesn't exist
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('student');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Create meetings table if it doesn't exist
        if (!Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('teacher_id');
                $table->string('status')->default('scheduled');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->timestamps();
                $table->foreign('teacher_id')->references('id')->on('users');
            });
        }

        // Create participants table if it doesn't exist
        if (!Schema::hasTable('participants')) {
            Schema::create('participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('meeting_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->boolean('is_present')->default(false);
                $table->timestamps();
                $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('users');
    }
}; 