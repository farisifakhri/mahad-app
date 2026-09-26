<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('group_id')->constrained()->restrictOnDelete();
            $table->string('nim')->unique();
            $table->string('faculty')->nullable();
            $table->string('study_program')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_student', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('wali');
            $table->timestamps();
            $table->primary(['parent_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('parents');
        Schema::dropIfExists('students');
    }
};
