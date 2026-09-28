<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SubmissionStatusEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\GroupResource;
use App\Filament\Resources\Pages\ManageGroups;
use App\Filament\Resources\Pages\ManageStudents;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserResource;
use App\Models\AbsenceSubmission;
use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Mabna;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Services\MonitoringService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SipmaSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as AuditActivity;
use Tests\TestCase;

class SipmaBaselineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(UserRoleEnum $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function group(User $murabbi, string $name): Group
    {
        $mabna = Mabna::firstOrCreate(['name' => 'Test Mabna'], ['gender' => 'male', 'status' => true]);

        return Group::create(['mabna_id' => $mabna->id, 'murabbi_id' => $murabbi->id, 'name' => $name, 'academic_year' => '2026/2027']);
    }

    private function student(Group $group): Student
    {
        return Student::create(['user_id' => $this->user(UserRoleEnum::MAHASANTRI)->id, 'group_id' => $group->id, 'nim' => fake()->unique()->numerify('1126########')]);
    }

    public function test_complete_seed_counts_relations_and_role_assignments(): void
    {
        $this->seed(SipmaSeeder::class);
        $this->assertDatabaseCount('mabnas', 1);
        $this->assertDatabaseCount('groups', 5);
        $this->assertDatabaseCount('group_mudabbir', 10);
        $this->assertDatabaseCount('students', 100);
        $this->assertDatabaseCount('parents', 10);
        $this->assertDatabaseCount('activities', 5);
        $this->assertDatabaseCount('model_has_roles', 124);
        $this->assertSame("Muhammad Ara'af, S.Ag", User::where('email', 'murabbi@sipma.test')->value('name'));
        $this->assertSame('Syahrul Ramdhani, S.Ag', User::where('email', 'murabbi2@sipma.test')->value('name'));
        $this->assertSame('Riyan Hidayat', User::where('email', 'mudabbir1@sipma.test')->value('name'));
        $this->assertSame('Ahmad Naufal Farhan', User::where('email', 'mudabbir6@sipma.test')->value('name'));
        $this->assertStringContainsString('belum dikonfirmasi', User::where('email', 'mudabbir7@sipma.test')->value('name'));
        foreach (User::where('role', UserRoleEnum::MAHASANTRI)->get() as $user) {
            $this->assertDoesNotMatchRegularExpression('/(?:S|M)\.[A-Za-z.]+$/', $user->name);
        }
        $this->assertTrue(User::where('email', 'admin@sipma.test')->first()->hasRole('super_admin'));
        $this->assertSame('Fakhri', User::where('email', 'admin@sipma.test')->value('name'));
        $this->assertSame('Ustad Fasjud', User::where('email', 'pengasuh@sipma.test')->value('name'));
        $this->assertTrue(User::where('email', 'pengasuh@sipma.test')->first()->hasRole('pengasuh'));
        $this->assertCount(2, Group::first()->mudabbirs);
        $this->assertCount(20, Group::first()->students);
    }

    public function test_portal_login_redirects_roles_and_denies_cross_role_routes(): void
    {
        foreach (UserRoleEnum::cases() as $role) {
            $user = $this->user($role);
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
            $target = match ($role) {
                UserRoleEnum::MAHASANTRI => '/portal/absensi',
                UserRoleEnum::ORANG_TUA => '/portal/anak',
                default => '/admin',
            };
            $this->get('/dashboard')->assertRedirect($target);
            $this->post('/logout');
        }
        $student = $this->user(UserRoleEnum::MAHASANTRI);
        $this->actingAs($student)->get('/portal/absensi')->assertRedirect(route('portal.onboarding'));
        $this->get('/portal/pengajuan')->assertRedirect(route('portal.onboarding'));
        $this->get('/portal/onboarding')->assertOk();
        $this->get('/portal/anak')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $parent = $this->user(UserRoleEnum::ORANG_TUA);
        $this->actingAs($parent)->get('/portal/anak')->assertOk();
        $this->get('/portal/absensi')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_internal_queries_and_policies_scope_groups_and_students(): void
    {
        $murabbi = $this->user(UserRoleEnum::MURABBI);
        $group = $this->group($murabbi, 'Binaan');
        $otherGroup = $this->group($this->user(UserRoleEnum::MURABBI), 'Lain');
        $student = $this->student($group);
        $otherStudent = $this->student($otherGroup);
        $mudabbir = $this->user(UserRoleEnum::MUDABBIR);
        $group->mudabbirs()->attach($mudabbir);
        $monitoring = app(MonitoringService::class);
        foreach ([$murabbi, $mudabbir] as $user) {
            $this->actingAs($user);
            $this->assertSame([$group->id], GroupResource::getEloquentQuery()->pluck('id')->all());
            $this->assertSame([$student->id], StudentResource::getEloquentQuery()->pluck('id')->all());
            $this->assertFalse(GroupResource::canView($otherGroup));
            $this->assertFalse(GroupResource::canCreate());
            $this->assertFalse(StudentResource::canEdit($student));
            $this->assertFalse(UserResource::canViewAny());
            $this->assertTrue(ActivityResource::canViewAny());
            $this->assertFalse(Gate::forUser($user)->allows('create', [Attendance::class, $otherStudent]));
        }
        // A student without a target session is never sufficient authorization.
        $this->assertFalse(Gate::forUser($mudabbir)->allows('create', [Attendance::class, $student]));
        $this->assertFalse(Gate::forUser($murabbi)->allows('create', [Attendance::class, $student]));
        $this->assertSame(1, $monitoring->students($mudabbir)->count());
        $admin = $this->user(UserRoleEnum::SUPER_ADMIN);
        $this->actingAs($admin);
        $this->assertSame(2, StudentResource::getEloquentQuery()->count());
        $this->assertTrue(UserResource::canCreate());
    }

    public function test_parent_can_only_query_and_view_linked_children(): void
    {
        $group = $this->group($this->user(UserRoleEnum::MURABBI), 'A');
        $child = $this->student($group);
        $other = $this->student($group);
        $parent = ParentModel::create(['user_id' => $this->user(UserRoleEnum::ORANG_TUA)->id]);
        $parent->students()->attach($child);
        $this->actingAs($parent->user)->get('/portal/anak')->assertOk()->assertSee($child->nim)->assertDontSee($other->nim);
        $this->assertTrue(Gate::allows('viewChild', [$parent, $child]));
        $this->assertFalse(Gate::allows('viewChild', [$parent, $other]));
        $this->assertSame([$child->id], app(MonitoringService::class)->students($parent->user)->pluck('id')->all());
    }

    public function test_changes_are_audited_with_uuid_subjects_and_actor(): void
    {
        $actor = $this->user(UserRoleEnum::MUDABBIR);
        $group = $this->group($this->user(UserRoleEnum::MURABBI), 'A');
        $group->mudabbirs()->attach($actor);
        $student = $this->student($group);
        $activity = Activity::create(['code' => 'TS', 'name' => 'TS']);
        $session = ActivitySession::create(['activity_id' => $activity->id, 'group_id' => $group->id, 'date' => now()]);
        $this->actingAs($actor);
        $attendance = Attendance::create(['activity_session_id' => $session->id, 'student_id' => $student->id, 'recorded_by' => $actor->id, 'status' => AttendanceStatusEnum::HADIR]);
        $attendance->update(['status' => AttendanceStatusEnum::ALFA]);
        $submission = AbsenceSubmission::create(['student_id' => $student->id, 'activity_session_id' => $session->id, 'type' => AttendanceStatusEnum::SAKIT, 'status' => SubmissionStatusEnum::PENDING, 'reason' => 'Sakit']);
        $this->assertTrue(Gate::allows('review', $submission));
        $submission->update(['status' => SubmissionStatusEnum::APPROVED, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
        $this->assertFalse(Gate::allows('review', $submission));
        $category = ViolationCategory::create(['name' => 'Disiplin', 'points' => 1]);
        $violation = Violation::create(['student_id' => $student->id, 'violation_category_id' => $category->id, 'recorded_by' => $actor->id, 'occurred_on' => now(), 'description' => 'Test']);
        $this->assertSame(5, AuditActivity::count());
        $audit = AuditActivity::where('subject_type', Attendance::class)->where('event', 'updated')->firstOrFail();
        $this->assertSame($actor->id, $audit->causer_id);
        $this->assertSame($attendance->id, $audit->subject_id);
        $this->assertSame('HADIR', $audit->properties['old']['status']);
        $this->assertSame('ALFA', $audit->properties['attributes']['status']);
        $this->assertSame($submission->id, $submission->fresh()->id);
        $this->assertTrue($violation->hasMedia('photos') === false);
    }

    public function test_filament_lists_render_for_internal_roles_and_deny_user_management(): void
    {
        $this->seed(SipmaSeeder::class);
        foreach (['admin@sipma.test', 'murabbi@sipma.test', 'mudabbir1@sipma.test'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail());
            $this->get('/admin')->assertOk();
            foreach (['groups', 'students', 'activities', 'violation-categories'] as $resource) {
                $this->get('/admin/'.$resource)->assertOk();
            }
            if ($email !== 'admin@sipma.test') {
                $this->get('/admin/users')->assertForbidden();
            } else {
                $this->get('/admin/users')->assertOk();
            }
        }
        $mudabbir = User::where('email', 'mudabbir1@sipma.test')->firstOrFail();
        $this->actingAs($mudabbir);
        Livewire::test(ManageGroups::class)
            ->assertCanSeeTableRecords($mudabbir->managedGroups)
            ->assertCanNotSeeTableRecords(Group::whereNotIn('id', $mudabbir->managedGroups->modelKeys())->get())
            ->mountTableAction('view', $mudabbir->managedGroups->first())
            ->assertSuccessful();
        Livewire::test(ManageStudents::class)
            ->mountTableAction('view', $mudabbir->managedGroups->first()->students->first())
            ->assertSuccessful();
    }

    public function test_registration_cannot_escalate_role_and_role_changes_sync(): void
    {
        $this->post('/register', ['name' => 'External', 'email' => 'external@sipma.test', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'super_admin'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'external@sipma.test']);
        $user = $this->user(UserRoleEnum::MAHASANTRI);
        $this->assertTrue($user->hasRole('mahasantri'));
        $this->assertFalse($user->hasRole('super_admin'));
        $user->update(['role' => UserRoleEnum::MUDABBIR]);
        $this->assertTrue($user->fresh()->hasRole('mudabbir'));
        $this->assertFalse($user->fresh()->hasRole('mahasantri'));
    }
}
