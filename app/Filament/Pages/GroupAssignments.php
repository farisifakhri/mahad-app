<?php

namespace App\Filament\Pages;

use App\Actions\AssignStudentsToGroup;
use App\Actions\CreateManagedGroup;
use App\Services\GroupOrganizationScope;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

class GroupAssignments extends Page
{
    protected string $view = 'filament.pages.group-assignments';

    protected static ?string $title = 'Pembagian Kelompok';

    public string $filterGroup = '';

    public string $search = '';

    public string $targetGroupId = '';

    public string $sourceGroupId = '';

    public string $newGroupName = '';

    public string $academicYear = '2026/2027';

    public array $selectedStudents = [];

    #[Locked]
    public array $sourceGroups = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'mudabbir']) && auth()->user()->can('groups.organize');
    }

    public function updatedFilterGroup(): void
    {
        $this->selectedStudents = [];
        $this->sourceGroups = [];
    }

    public function move(): void
    {
        $rows = array_map(fn ($id) => ['student_id' => $id, 'from_group_id' => $this->sourceGroups[$id] ?? ''], $this->selectedStudents);
        $count = app(AssignStudentsToGroup::class)->execute(auth()->user(), $this->targetGroupId, $rows);
        $this->selectedStudents = [];
        $this->sourceGroups = [];
        Notification::make()->title($count.' mahasantri dipindahkan')->success()->send();
    }

    public function createGroup(): void
    {
        $group = app(CreateManagedGroup::class)->execute(auth()->user(), $this->sourceGroupId, ['name' => $this->newGroupName, 'academic_year' => $this->academicYear]);
        $this->targetGroupId = $group->id;
        $this->newGroupName = '';
        Notification::make()->title('Kelompok dibuat')->success()->send();
    }

    protected function getViewData(): array
    {
        $scope = app(GroupOrganizationScope::class);
        $students = $scope->students(auth()->user())->when($this->filterGroup, fn ($q) => $q->where('group_id', $this->filterGroup))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('nim', 'like', '%'.$this->search.'%')->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))))->orderBy('nim')->limit(500)->get();
        foreach ($students as $student) {
            if (! in_array($student->id, $this->selectedStudents, true)) {
                $this->sourceGroups[$student->id] = $student->group_id;
            }
        }

        return ['groups' => $scope->groups(auth()->user())->with('mabna')->orderBy('name')->get(), 'students' => $students];
    }
}
