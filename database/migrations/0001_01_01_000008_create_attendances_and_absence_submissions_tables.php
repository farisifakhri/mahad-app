<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('activity_session_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('student_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['HADIR', 'ALFA', 'IZIN', 'SAKIT']);
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['activity_session_id', 'student_id']);
        });
        Schema::create('absence_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_session_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['IZIN', 'SAKIT']);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('reason');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'activity_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_submissions');
        Schema::dropIfExists('attendances');
    }
};
