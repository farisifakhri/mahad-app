<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('mabna_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('murabbi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('academic_year', 9);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['mabna_id', 'name', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
