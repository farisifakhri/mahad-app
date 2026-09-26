<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_sessions', function (Blueprint $table) {
            $table->foreignUuid('opened_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('roster')->nullable();
            $table->unsignedInteger('version')->default(1);
            // An explicit occurrence preserves duplicate protection and supports repeats.
            $table->unsignedInteger('occurrence')->default(1);
            $table->unique(['activity_id', 'group_id', 'date', 'occurrence'], 'activity_session_occurrence_unique');
        });
        Schema::table('activity_sessions', fn (Blueprint $table) => $table->dropUnique(['activity_id', 'group_id', 'date']));
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('absence_submissions', fn (Blueprint $table) => $table->unsignedInteger('version')->default(1));
        Schema::table('violations', fn (Blueprint $table) => $table->unsignedInteger('version')->default(1));

        Schema::create('weekly_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('group_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedInteger('current_version')->default(0);
            $table->timestamps();
            $table->unique(['group_id', 'starts_on']);
        });
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_period_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['weekly_period_id', 'version']);
        });
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attendance_id')->constrained()->restrictOnDelete();
            $table->foreignId('weekly_period_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('attendance_version');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('new_notes')->nullable();
            $table->text('reason');
            $table->string('status')->default('PENDING')->index();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('weekly_report_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Refuse to discard operational history or collapse repeated sessions.
        if (DB::table('weekly_reports')->exists() || DB::table('attendance_corrections')->exists()
            || DB::table('activity_sessions')->where('occurrence', '>', 1)->exists()
            || DB::table('activity_sessions')->whereNotNull('opened_at')->exists()
            || DB::table('attendances')->where('version', '>', 1)->exists()
            || DB::table('absence_submissions')->where('version', '>', 1)->exists()
            || DB::table('violations')->where('version', '>', 1)->exists()) {
            throw new RuntimeException('Rollback refused: operational history exists. Restore a backup or create a forward migration.');
        }
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('weekly_reports');
        Schema::dropIfExists('weekly_periods');
        Schema::table('violations', fn (Blueprint $table) => $table->dropColumn('version'));
        Schema::table('absence_submissions', fn (Blueprint $table) => $table->dropColumn('version'));
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('version');
        });
        Schema::table('activity_sessions', function (Blueprint $table) {
            $table->unique(['activity_id', 'group_id', 'date']);
            $table->dropUnique('activity_session_occurrence_unique');
            $table->dropConstrainedForeignId('opened_by');
            $table->dropColumn(['opened_at', 'status', 'roster', 'version', 'occurrence']);
        });
    }
};
