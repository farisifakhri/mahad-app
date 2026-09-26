<?php

namespace Tests\Feature;

use App\Actions\AssignStudentsToGroup;
use App\Actions\CreateManagedGroup;
use App\Actions\FinalizeWeeklyReport;
use App\Actions\OpenActivitySession;
use App\Actions\RecordAttendanceBatch;
use App\Actions\RequestAttendanceCorrection;
use App\Actions\ReviewAbsenceSubmission;
use App\Actions\ReviewAttendanceCorrection;
use App\Actions\SaveViolation;
use App\Actions\SubmitAbsence;
use App\DTOs\DateRange;
use App\Enums\UserRoleEnum;
use App\Filament\Pages\GroupAssignments;
use App\Filament\Pages\OperationalSessions;
use App\Filament\Pages\WeeklyReports;
use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Group;
use App\Models\Mabna;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use App\Models\WeeklyReport;
use App\Services\DashboardService;
use App\Services\DevelopmentService;
use App\Services\GroupOrganizationScope;
use App\Services\MonitoringService;
use App\Services\OperationalQuery;
use App\Services\WeeklyCalendar;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SipmaSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as Audit;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SipmaOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $murabbi;

    private User $otherMurabbi;

    private User $primaryMudabbir;

    private User $mudabbir;

    private User $admin;

    private Group $group;

    private Group $otherGroup;

    private Activity $activity;

    private array $students;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freeze('2026-09-26 20:00:00');
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->murabbi = User::factory()->create(['role' => UserRoleEnum::MURABBI]);
        $this->otherMurabbi = User::factory()->create(['role' => UserRoleEnum::MURABBI]);
        $this->primaryMudabbir = User::factory()->create(['role' => UserRoleEnum::MUDABBIR]);
        $this->mudabbir = User::factory()->create(['role' => UserRoleEnum::MUDABBIR]);
        $this->admin = User::factory()->create(['role' => UserRoleEnum::SUPER_ADMIN]);
        $mabna = Mabna::create(['name' => 'Test', 'gender' => 'male']);
        $this->group = Group::create(['name' => 'A', 'mabna_id' => $mabna->id, 'murabbi_id' => $this->murabbi->id, 'academic_year' => '2026/2027']);
        $this->otherGroup = Group::create(['name' => 'B', 'mabna_id' => $mabna->id, 'murabbi_id' => $this->otherMurabbi->id, 'academic_year' => '2026/2027']);
        $this->group->mudabbirs()->attach([$this->primaryMudabbir->id, $this->mudabbir->id]);
        $this->students = [];
        foreach ([$this->group, $this->group, $this->otherGroup] as $index => $group) {
            $this->students[] = Student::create(['user_id' => User::factory()->create()->id, 'group_id' => $group->id, 'nim' => 'TEST'.$index]);
        }
        $this->activity = Activity::create(['code' => 'TS', 'name' => 'Kegiatan TS']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function freeze(string $time): void
    {
        $date = CarbonImmutable::parse($time, 'Asia/Jakarta');
        Carbon::setTestNow($date);
        CarbonImmutable::setTestNow($date);
    }

    private function sessionData(int $occurrence = 1): array
    {
        return ['group_id' => $this->group->id, 'activity_id' => $this->activity->id, 'date' => '2026-09-26', 'starts_at' => '18:00', 'ends_at' => '19:00', 'occurrence' => $occurrence];
    }

    private function openTestSession(): ActivitySession
    {
        $this->actingAs($this->primaryMudabbir);

        return app(OpenActivitySession::class)->execute($this->primaryMudabbir, $this->sessionData());
    }

    private function rows(string $status = 'HADIR', int $version = 0): array
    {
        return array_map(fn ($student) => ['student_id' => $student->id, 'status' => $status, 'notes' => null, 'version' => $version], array_slice($this->students, 0, 2));
    }

    private function record(ActivitySession $session): void
    {
        $this->actingAs($this->mudabbir);
        app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows());
    }

    private function denied(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected authorization failure.');
        } catch (AuthorizationException|HttpException $e) {
            if ($e instanceof HttpException) {
                $this->assertSame(403, $e->getStatusCode());
            } else {
                $this->assertTrue(true);
            }
        }
    }

    private function invalid(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected validation failure.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    private function finalReport(ActivitySession $session): WeeklyReport
    {
        $this->record($session);
        $this->freeze('2026-09-27 00:00:00');
        $this->actingAs($this->primaryMudabbir);

        return app(FinalizeWeeklyReport::class)->execute($this->primaryMudabbir, $this->group->id, '2026-09-26');
    }

    public function test_group_organization_covers_assigned_mabna_without_expanding_attendance_scope(): void
    {
        $this->assertSame(3, app(GroupOrganizationScope::class)->students($this->mudabbir)->count());
        $this->assertSame(2, app(MonitoringService::class)->students($this->mudabbir)->count());
        $session = $this->openTestSession();
        $roster = $session->fresh()->roster;
        $this->actingAs($this->mudabbir);
        Livewire::test(GroupAssignments::class)
            ->set('selectedStudents', [$this->students[0]->id])
            ->set('targetGroupId', $this->otherGroup->id)->call('move')->assertHasNoErrors();
        $this->assertSame($this->otherGroup->id, $this->students[0]->fresh()->group_id);
        $this->assertSame($roster, $session->fresh()->roster);
        $this->record($session);
        $this->assertSame(2, $session->attendances()->count());
        $this->assertSame(1, Audit::where('log_name', 'group-assignment')->count());
        $this->group->mudabbirs()->detach($this->mudabbir->id);
        $this->assertSame(0, app(GroupOrganizationScope::class)->groups($this->mudabbir)->count());
    }

    public function test_legacy_leader_role_merges_without_changing_accounts_or_assignments(): void
    {
        $password = $this->mudabbir->password;
        Role::findOrCreate('ketua_mudabbir', 'web')->givePermissionTo('sessions.open');
        DB::table('users')->where('id', $this->mudabbir->id)->update(['role' => 'ketua_mudabbir']);
        $this->mudabbir->syncRoles(['ketua_mudabbir']);
        $migration = require database_path('migrations/2026_09_27_000001_merge_leader_role_into_mudabbir.php');
        $migration->up();
        $user = $this->mudabbir->fresh();
        $this->assertSame(UserRoleEnum::MUDABBIR, $user->role);
        $this->assertSame($password, $user->password);
        $this->assertTrue($user->hasRole('mudabbir'));
        $this->assertTrue($user->can('sessions.open'));
        $this->assertTrue($user->managedGroups->contains('id', $this->group->id));
        $this->assertDatabaseMissing('roles', ['name' => 'ketua_mudabbir']);
        $this->assertDatabaseMissing('users', ['role' => 'ketua_mudabbir']);
    }

    public function test_group_transfer_rejects_stale_and_foreign_mabna_atomically(): void
    {
        $action = app(AssignStudentsToGroup::class);
        $rows = [
            ['student_id' => $this->students[0]->id, 'from_group_id' => $this->group->id],
            ['student_id' => $this->students[1]->id, 'from_group_id' => $this->otherGroup->id],
        ];
        $this->invalid(fn () => $action->execute($this->mudabbir, $this->otherGroup->id, $rows));
        $this->assertSame($this->group->id, $this->students[0]->fresh()->group_id);
        $this->assertSame(0, Audit::where('log_name', 'group-assignment')->count());
        $foreign = Group::create(['name' => 'Luar', 'mabna_id' => Mabna::create(['name' => 'Luar', 'gender' => 'male'])->id, 'academic_year' => '2026/2027']);
        $this->denied(fn () => $action->execute($this->mudabbir, $foreign->id, [$rows[0]]));
        $this->denied(fn () => $action->execute($this->murabbi, $this->otherGroup->id, [$rows[0]]));
        $this->denied(fn () => $action->execute($this->admin, $foreign->id, [$rows[0]]));
    }

    public function test_mudabbir_creates_group_in_assigned_mabna_and_murabbi_only_monitors(): void
    {
        $action = app(CreateManagedGroup::class);
        $data = ['name' => 'Baru', 'academic_year' => '2026/2027'];
        $new = $action->execute($this->mudabbir, $this->group->id, $data);
        $this->assertSame($this->group->mabna_id, $new->mabna_id);
        $this->assertSame($this->murabbi->id, $new->murabbi_id);
        $this->assertTrue($new->mudabbirs->contains('id', $this->mudabbir->id));
        $this->invalid(fn () => $action->execute($this->mudabbir, $this->group->id, $data));
        $this->murabbi->givePermissionTo(['groups.organize', 'sessions.open', 'reports.finalize']);
        $this->denied(fn () => $action->execute($this->murabbi, $this->group->id, ['name' => 'Forbidden', 'academic_year' => '2026/2027']));
        $this->denied(fn () => app(OpenActivitySession::class)->execute($this->murabbi, $this->sessionData()));
        $session = $this->openTestSession();
        $this->record($session);
        $this->actingAs($this->murabbi)->get('/admin')->assertOk()->assertSee('Performa mudabbir')->assertSee('Pantau laporan')->assertDontSee('Kelola akun');
        $this->get('/admin/group-assignments')->assertForbidden();
        $this->get('/admin/operational-sessions')->assertOk()->assertDontSee('Buka sesi kegiatan');
        $this->freeze('2026-09-27 00:00:00');
        $this->denied(fn () => app(FinalizeWeeklyReport::class)->execute($this->murabbi, $this->group->id, '2026-09-26'));
    }

    public function test_simple_session_form_selects_only_group_and_numbers_repeated_sessions_automatically(): void
    {
        $this->actingAs($this->primaryMudabbir);
        Livewire::test(OperationalSessions::class)->assertSet('groupId', $this->group->id)
            ->set('activityId', (string) $this->activity->id)->call('openSession')->assertHasNoErrors();
        Livewire::test(OperationalSessions::class)->set('activityId', (string) $this->activity->id)
            ->call('openSession')->assertHasNoErrors();
        $this->assertSame([1, 2], ActivitySession::orderBy('occurrence')->pluck('occurrence')->all());
    }

    public function test_pengasuh_reads_all_groups_and_reports_without_operational_write_access(): void
    {
        $pengasuh = User::factory()->create(['role' => UserRoleEnum::PENGASUH]);
        $session = $this->openTestSession();
        $report = $this->finalReport($session);
        $record = $session->attendances()->firstOrFail();
        $this->assertSame(2, app(MonitoringService::class)->groups($pengasuh)->count());
        $this->assertSame(3, app(MonitoringService::class)->students($pengasuh)->count());
        $this->assertTrue(Gate::forUser($pengasuh)->allows('view', $record));
        $this->actingAs($pengasuh)->get('/admin')->assertOk()->assertSee('Performa mudabbir');
        $this->get('/admin/weekly-reports')->assertOk()->assertSee($report->period->group->name)->assertDontSee('Finalisasi laporan');
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/group-assignments')->assertForbidden();
        $this->denied(fn () => app(RecordAttendanceBatch::class)->execute($pengasuh, $session, $this->rows()));
        $this->denied(fn () => app(FinalizeWeeklyReport::class)->execute($pengasuh, $this->group->id, '2026-09-26'));
        $correction = app(RequestAttendanceCorrection::class)->execute($this->primaryMudabbir, $record, ['new_status' => 'ALFA', 'new_notes' => null, 'reason' => 'Perlu ditinjau', 'version' => $record->version]);
        $this->denied(fn () => app(ReviewAttendanceCorrection::class)->execute($pengasuh, $correction, 'APPROVED', null));
        $this->denied(fn () => app(OpenActivitySession::class)->execute($pengasuh, $this->sessionData(2)));
    }

    public function test_sessions_open_only_by_assigned_mudabbir_and_support_occurrences(): void
    {
        $session = $this->openTestSession();
        $this->assertSame($this->primaryMudabbir->id, $session->opened_by);
        $this->assertCount(2, $session->roster);
        $this->actingAs($this->primaryMudabbir);
        $second = app(OpenActivitySession::class)->execute($this->primaryMudabbir, $this->sessionData(2));
        $this->assertSame(2, $second->occurrence);
        $this->invalid(fn () => app(OpenActivitySession::class)->execute($this->primaryMudabbir, $this->sessionData(2)));
        foreach ([$this->admin, $this->murabbi, $this->otherMurabbi] as $user) {
            $this->denied(fn () => app(OpenActivitySession::class)->execute($user, $this->sessionData(3)));
        }
        $this->invalid(fn () => app(OpenActivitySession::class)->execute($this->primaryMudabbir, array_replace($this->sessionData(3), ['ends_at' => '17:00'])));
        $third = app(OpenActivitySession::class)->execute($this->mudabbir, $this->sessionData(3));
        $this->assertSame($this->mudabbir->id, $third->opened_by);
        $this->assertDatabaseCount('activity_sessions', 3);
    }

    public function test_batch_is_atomic_and_rejects_cross_group_student_and_unauthorized_actor(): void
    {
        $session = $this->openTestSession();
        $rows = $this->rows();
        $rows[1]['student_id'] = $this->students[2]->id;
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $rows));
        $this->assertDatabaseCount('attendances', 0);
        foreach ([$this->murabbi, $this->admin, $this->otherMurabbi] as $user) {
            $this->denied(fn () => app(RecordAttendanceBatch::class)->execute($user, $session, $this->rows()));
        }
        $this->record($session);
        $this->assertDatabaseCount('attendances', 2);
        $this->assertSame($this->mudabbir->id, Attendance::first()->recorded_by);
    }

    public function test_version_conflicts_and_duplicate_submissions_do_not_overwrite(): void
    {
        $session = $this->openTestSession();
        $this->record($session);
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows('ALFA', 0)));
        app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows('HADIR', 1));
        $this->assertSame(1, Attendance::first()->version);
        app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows('ALFA', 1));
        $this->assertSame(2, Attendance::first()->version);
        $this->assertDatabaseCount('attendances', 2);
        $audit = Audit::where('subject_type', Attendance::class)->where('event', 'updated')->firstOrFail();
        $this->assertSame('HADIR', $audit->properties['old']['status']);
        $this->assertSame('ALFA', $audit->properties['attributes']['status']);
    }

    public function test_saturday_lock_is_enforced_at_exact_2359_wib(): void
    {
        $session = $this->openTestSession();
        $this->freeze('2026-09-26 23:58:59');
        $this->record($session);
        $this->freeze('2026-09-26 23:59:00');
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows('ALFA', 1)));
        $this->assertFalse(Gate::forUser($this->mudabbir)->allows('update', Attendance::first()));
        $this->assertSame('HADIR', Attendance::first()->status->value);
    }

    public function test_recording_requires_open_session_and_end_time(): void
    {
        $session = $this->openTestSession();
        $this->freeze('2026-09-26 18:30:00');
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows()));
        $this->freeze('2026-09-26 20:00:00');
        $session->update(['status' => 'draft']);
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session->fresh(), $this->rows()));
    }

    public function test_calendar_crosses_year_and_new_week_is_unlocked(): void
    {
        $calendar = app(WeeklyCalendar::class);
        $this->assertSame('2026-12-27', $calendar->start('2027-01-01')->toDateString());
        $this->assertSame('2027-01-02 23:59:00', $calendar->cutoff('2027-01-01')->format('Y-m-d H:i:s'));
        $this->freeze('2026-09-27 00:00:00');
        $this->assertTrue($calendar->isLocked('2026-09-26'));
        $this->assertFalse($calendar->isLocked('2026-09-27'));
        $this->assertSame('2026-09-27', $calendar->start('2026-09-27')->toDateString());
    }

    public function test_finalization_requires_sunday_and_complete_roster_and_preserves_snapshot(): void
    {
        $session = $this->openTestSession();
        $this->denied(fn () => app(FinalizeWeeklyReport::class)->execute($this->primaryMudabbir, $this->group->id, '2026-09-26'));
        $this->freeze('2026-09-27 00:00:00');
        $this->invalid(fn () => app(FinalizeWeeklyReport::class)->execute($this->primaryMudabbir, $this->group->id, '2026-09-26'));
        $this->freeze('2026-09-26 20:00:00');
        $report = $this->finalReport($session);
        $this->assertSame(2, $report->snapshot['totals']['HADIR']);
        $this->assertSame(0, $report->snapshot['totals']['BELUM_DIISI']);
        $this->denied(fn () => app(FinalizeWeeklyReport::class)->execute($this->primaryMudabbir, $this->group->id, '2026-09-26'));
        try {
            $report->update(['reason' => 'tamper']);
            $this->fail('Report must be immutable');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
    }

    public function test_correction_requires_mudabbir_reason_and_murabbi_review_before_new_version(): void
    {
        $session = $this->openTestSession();
        $original = $this->finalReport($session);
        $attendance = Attendance::first();
        $data = ['new_status' => 'ALFA', 'new_notes' => 'Catatan koreksi', 'reason' => 'Salah mencatat status', 'version' => 1];
        foreach ([$this->admin, $this->murabbi] as $user) {
            $this->denied(fn () => app(RequestAttendanceCorrection::class)->execute($user, $attendance, $data));
        }
        $this->invalid(fn () => app(RequestAttendanceCorrection::class)->execute($this->primaryMudabbir, $attendance, array_replace($data, ['reason' => ''])));
        $this->actingAs($this->primaryMudabbir);
        $correction = app(RequestAttendanceCorrection::class)->execute($this->mudabbir, $attendance, $data);
        $this->assertSame('HADIR', $attendance->fresh()->status->value);
        $this->assertDatabaseCount('weekly_reports', 1);
        $this->invalid(fn () => app(RequestAttendanceCorrection::class)->execute($this->primaryMudabbir, $attendance, $data));
        foreach ([$this->primaryMudabbir, $this->admin, $this->otherMurabbi] as $user) {
            $this->denied(fn () => app(ReviewAttendanceCorrection::class)->execute($user, $correction, 'APPROVED', null));
        }
        $this->actingAs($this->murabbi);
        app(ReviewAttendanceCorrection::class)->execute($this->murabbi, $correction, 'APPROVED', 'Diverifikasi');
        $this->assertSame('ALFA', $attendance->fresh()->status->value);
        $this->assertDatabaseCount('weekly_reports', 2);
        $this->assertSame(2, $correction->fresh()->report->version);
        $this->assertSame(2, $original->fresh()->snapshot['totals']['HADIR']);
        $this->assertSame(1, WeeklyReport::latest('id')->first()->snapshot['totals']['ALFA']);
        $this->denied(fn () => app(ReviewAttendanceCorrection::class)->execute($this->murabbi, $correction->fresh(), 'APPROVED', null));
    }

    public function test_rejecting_correction_keeps_attendance_and_report_unchanged(): void
    {
        $session = $this->openTestSession();
        $this->finalReport($session);
        $attendance = Attendance::first();
        $correction = app(RequestAttendanceCorrection::class)->execute($this->primaryMudabbir, $attendance, ['new_status' => 'ALFA', 'reason' => 'Mohon verifikasi catatan', 'version' => 1]);
        app(ReviewAttendanceCorrection::class)->execute($this->murabbi, $correction, 'REJECTED', 'Bukti tidak sesuai');
        $this->assertSame('HADIR', $attendance->fresh()->status->value);
        $this->assertDatabaseCount('weekly_reports', 1);
        $this->assertSame('REJECTED', $correction->fresh()->status->value);
    }

    public function test_submission_approval_is_atomic_and_cannot_be_reviewed_twice(): void
    {
        $session = $this->openTestSession();
        $student = $this->students[0];
        $this->actingAs($student->user);
        $submission = app(SubmitAbsence::class)->execute($student->user, $session, ['type' => 'SAKIT', 'reason' => 'Sedang sakit dan istirahat']);
        $this->actingAs($this->mudabbir);
        app(ReviewAbsenceSubmission::class)->execute($this->mudabbir, $submission, 'APPROVED', 'Disetujui', 1);
        $this->assertSame('SAKIT', Attendance::first()->status->value);
        $this->assertSame($this->mudabbir->id, $submission->fresh()->reviewed_by);
        $this->denied(fn () => app(ReviewAbsenceSubmission::class)->execute($this->mudabbir, $submission->fresh(), 'REJECTED', null, 2));
        $this->invalid(fn () => app(SubmitAbsence::class)->execute($student->user, $session, ['type' => 'IZIN', 'reason' => 'Pengajuan kedua']));
        $this->assertDatabaseCount('absence_submissions', 1);
    }

    public function test_submissions_are_per_roster_and_cannot_bypass_lock(): void
    {
        $session = $this->openTestSession();
        $this->invalid(fn () => app(SubmitAbsence::class)->execute($this->students[2]->user, $session, ['type' => 'IZIN', 'reason' => 'Sesi kelompok lain']));
        $submission = app(SubmitAbsence::class)->execute($this->students[0]->user, $session, ['type' => 'IZIN', 'reason' => 'Keperluan keluarga']);
        $this->freeze('2026-09-26 23:59:00');
        $this->invalid(fn () => app(ReviewAbsenceSubmission::class)->execute($this->mudabbir, $submission, 'APPROVED', null, 1));
        $this->assertSame('PENDING', $submission->fresh()->status->value);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_private_upload_download_and_gps_validation(): void
    {
        Storage::fake('local');
        $session = $this->openTestSession();
        $user = $this->students[0]->user;
        $this->actingAs($user)->post('/portal/pengajuan', ['activity_session_id' => $session->id, 'type' => 'IZIN', 'reason' => 'Keperluan keluarga', 'latitude' => -6.3, 'longitude' => 106.7, 'evidence' => UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF")])->assertRedirect(route('portal.pengajuan'));
        $media = Media::firstOrFail();
        $this->assertSame('local', $media->disk);
        $this->get(route('media.download', $media))->assertOk();
        $this->actingAs($this->students[1]->user)->get(route('media.download', $media))->assertForbidden();
        $this->actingAs($this->mudabbir)->get(route('media.download', $media))->assertOk();
        $this->invalid(fn () => app(SubmitAbsence::class)->execute($this->students[1]->user, $session, ['type' => 'SAKIT', 'reason' => 'Pengajuan invalid', 'latitude' => 99, 'longitude' => 106]));
    }

    public function test_izin_sakit_cannot_be_recorded_without_approved_submission(): void
    {
        $session = $this->openTestSession();
        $this->invalid(fn () => app(RecordAttendanceBatch::class)->execute($this->mudabbir, $session, $this->rows('IZIN')));
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_self_delete_is_blocked_without_logout_for_linked_or_internal_accounts(): void
    {
        foreach ([$this->students[0]->user, $this->murabbi, $this->primaryMudabbir, $this->admin] as $user) {
            $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])->assertRedirect('/profile')->assertSessionHasErrorsIn('userDeletion', 'password');
            $this->assertAuthenticatedAs($user);
            $this->assertNotNull($user->fresh());
        }
    }

    public function test_removing_assignment_immediately_revokes_group_operations(): void
    {
        $session = $this->openTestSession();
        $this->group->mudabbirs()->detach($this->primaryMudabbir->id);
        $this->denied(fn () => app(OpenActivitySession::class)->execute($this->primaryMudabbir, $this->sessionData(2)));
        $this->denied(fn () => app(RecordAttendanceBatch::class)->execute($this->primaryMudabbir, $session, $this->rows()));
        $this->assertSame(0, app(OperationalQuery::class)->sessions($this->primaryMudabbir)->count());
    }

    public function test_historical_attendance_stays_with_session_group_after_student_transfer(): void
    {
        $session = $this->openTestSession();
        $this->record($session);
        $student = $this->students[0];
        $student->update(['group_id' => $this->otherGroup->id]);
        $this->assertSame(2, app(MonitoringService::class)->attendances($this->murabbi)->count());
        $this->assertSame(0, app(MonitoringService::class)->attendances($this->otherMurabbi)->count());
        $this->assertTrue(Gate::forUser($this->murabbi)->allows('view', Attendance::where('student_id', $student->id)->first()));
        $this->assertFalse(Gate::forUser($this->otherMurabbi)->allows('view', Attendance::where('student_id', $student->id)->first()));
    }

    public function test_violation_write_scope_versions_and_soft_delete(): void
    {
        $category = ViolationCategory::create(['name' => 'Disiplin', 'points' => 1]);
        $data = ['student_id' => $this->students[0]->id, 'violation_category_id' => $category->id, 'occurred_on' => '2026-09-26', 'description' => 'Pelanggaran kedisiplinan', 'version' => 0];
        $this->denied(fn () => app(SaveViolation::class)->execute($this->murabbi, $data));
        $this->denied(fn () => app(SaveViolation::class)->execute($this->primaryMudabbir, array_replace($data, ['student_id' => $this->students[2]->id])));
        $this->actingAs($this->primaryMudabbir);
        $record = app(SaveViolation::class)->execute($this->primaryMudabbir, $data);
        $this->invalid(fn () => app(SaveViolation::class)->execute($this->primaryMudabbir, $data, $record));
        app(SaveViolation::class)->execute($this->primaryMudabbir, array_replace($data, ['version' => 1, 'description' => 'Keterangan diperbaiki']), $record);
        $this->assertSame(2, $record->fresh()->version);
        $this->assertFalse(Gate::forUser($this->primaryMudabbir)->allows('delete', $record));
        $this->actingAs($this->admin);
        Gate::authorize('delete', $record);
        $record->delete();
        $this->assertSoftDeleted($record);
    }

    public function test_internal_pages_render_and_livewire_actions_use_backend_authorization(): void
    {
        foreach ([$this->admin, $this->murabbi, $this->mudabbir, $this->primaryMudabbir] as $user) {
            $this->actingAs($user);
            foreach (['', 'operational-sessions', 'weekly-reports', 'violations'] as $path) {
                $this->get('/admin/'.$path)->assertOk();
            }
        }
        $this->actingAs($this->primaryMudabbir);
        Livewire::test(OperationalSessions::class)->set('groupId', $this->group->id)->set('activityId', (string) $this->activity->id)->set('startsAt', '18:00')->set('endsAt', '19:00')->call('openSession')->assertHasNoErrors()
            ->set('attendanceRows', $this->rows())->call('saveAttendance')->assertHasNoErrors();
        $this->assertDatabaseCount('attendances', 2);
        $this->actingAs($this->murabbi);
        Livewire::test(OperationalSessions::class)->set('groupId', $this->group->id)->set('activityId', (string) $this->activity->id)->call('openSession')->assertForbidden();
    }

    public function test_demo_seeder_is_repeatable_without_resetting_passwords(): void
    {
        $this->seed(SipmaSeeder::class);
        $count = User::count();
        $password = User::where('email', 'admin@sipma.test')->value('password');
        $student = User::where('email', 'mahasantri1@sipma.test')->firstOrFail();
        $student->update(['name' => 'Drajat Cayadi Gunawan S.I.Kom']);
        $this->seed(SipmaSeeder::class);
        $this->assertSame('Drajat Cayadi Gunawan', $student->fresh()->name);
        $this->assertSame($student->id, User::where('email', 'mahasantri1@sipma.test')->value('id'));
        $this->assertSame($count, User::count());
        $this->assertSame($password, User::where('email', 'admin@sipma.test')->value('password'));
        $this->assertSame(2, User::where('email', 'mudabbir1@sipma.test')->first()->managedGroups->first()->mudabbirs()->count());
    }

    public function test_portal_reports_and_parent_summary_include_only_linked_student(): void
    {
        $session = $this->openTestSession();
        $this->finalReport($session);
        $range = new DateRange('2026-09-20', '2026-09-26');
        $parentUser = User::factory()->create(['role' => UserRoleEnum::ORANG_TUA]);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($this->students[0]->id);
        $service = app(DevelopmentService::class);
        $reports = $service->reportVersions($parentUser, $range);
        $this->assertCount(1, $reports);
        $this->assertCount(1, $reports[0]['sessions'][0]['students']);
        $this->assertSame($this->students[0]->id, $reports[0]['sessions'][0]['students'][0]['student_id']);
        $this->assertArrayNotHasKey('totals', $reports[0]);
        $summary = $service->summary($parentUser, $this->students[0], $range);
        $this->assertSame(1, $summary['expected']);
        $this->assertSame(100.0, $summary['attendance_percentage']);
        $this->denied(fn () => $service->summary($parentUser, $this->students[1], $range));
        $query = '?from=2026-09-20&to=2026-09-26';
        $this->actingAs($parentUser)->get('/portal/anak'.$query)->assertOk()->assertSee($this->students[0]->nim)->assertDontSee($this->students[1]->nim);
        $this->get('/portal/laporan'.$query)->assertOk()->assertSee($this->students[0]->user->name)->assertDontSee($this->students[1]->user->name);
        $this->actingAs($this->students[0]->user)->get('/portal/absensi'.$query)->assertOk();
        $this->get('/portal/pelanggaran'.$query)->assertOk();
        $this->get('/portal/pengajuan')->assertOk();
        $this->get('/portal/laporan'.$query)->assertOk()->assertDontSee($this->students[1]->user->name);
        $this->get('/portal/absensi?from=2026-09-26&to=2026-09-20')->assertRedirect()->assertSessionHasErrors('to');
    }

    public function test_finalization_and_review_work_through_livewire_pages(): void
    {
        $session = $this->openTestSession();
        $this->record($session);
        $this->freeze('2026-09-27 00:00:00');
        $this->actingAs($this->primaryMudabbir);
        Livewire::test(WeeklyReports::class)->set('groupId', $this->group->id)->set('periodDate', '2026-09-26')->call('finalize')->assertHasNoErrors();
        $attendance = Attendance::first();
        Livewire::test(WeeklyReports::class)->call('beginCorrection', $attendance->id, 1)->set('newStatus', 'ALFA')->set('correctionReason', 'Koreksi melalui panel mudabbir')->call('requestCorrection')->assertHasNoErrors();
        $correction = AttendanceCorrection::firstOrFail();
        $this->actingAs($this->murabbi);
        Livewire::test(WeeklyReports::class)->call('reviewCorrection', $correction->id, 'APPROVED')->assertHasNoErrors();
        $this->assertDatabaseCount('weekly_reports', 2);
        $this->assertSame('ALFA', $attendance->fresh()->status->value);
    }

    public function test_dashboard_counts_real_roster_and_filters_other_groups(): void
    {
        $session = $this->openTestSession();
        $summary = app(DashboardService::class)->overview($this->primaryMudabbir);
        $this->assertCount(1, $summary['groups']);
        $this->assertSame(2, $summary['completion'][0]['expected']);
        $this->assertSame(0, $summary['completion'][0]['recorded']);
        $this->assertCount(1, $summary['incomplete']);
        $this->record($session);
        $summary = app(DashboardService::class)->overview($this->primaryMudabbir);
        $this->assertSame(100.0, $summary['completion'][0]['percentage']);
        $this->assertCount(0, $summary['incomplete']);
        $this->assertCount(0, app(DashboardService::class)->overview($this->otherMurabbi)['todaySessions']);
        $this->actingAs($this->primaryMudabbir)->get('/admin/operational-sessions?session='.$session->id)->assertOk()->assertSee('Status');
        $this->actingAs($this->otherMurabbi)->get('/admin/operational-sessions?session='.$session->id)->assertNotFound();
    }

    public function test_guests_use_the_shared_sipma_login(): void
    {
        $this->actingAs($this->primaryMudabbir);
        $this->post('/logout')->assertRedirect('/');
        $this->get('/admin/login')->assertRedirect(route('login'));
        $this->get('/login')->assertOk()->assertSee('Selamat datang di SIPMA');
    }

    public function test_failed_media_attachment_rolls_back_submission(): void
    {
        Storage::fake('local');
        $session = $this->openTestSession();
        try {
            app(SubmitAbsence::class)->execute($this->students[0]->user, $session, ['type' => 'IZIN', 'reason' => 'Bukti gagal diproses'], UploadedFile::fake()->create('bukti.pdf', 1, 'application/pdf'));
            $this->fail('Empty fake file should not be accepted by the media collection.');
        } catch (FileUnacceptableForCollection $e) {
            $this->assertDatabaseCount('absence_submissions', 0);
            $this->assertDatabaseCount('media', 0);
        }
    }
}
