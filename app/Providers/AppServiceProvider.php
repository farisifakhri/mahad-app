<?php

namespace App\Providers;

use App\Models\AbsenceSubmission;
use App\Models\Attendance;
use App\Models\ParentModel;
use App\Models\User;
use App\Models\Violation;
use App\Observers\UserObserver;
use App\Policies\AbsenceSubmissionPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ParentPolicy;
use App\Policies\ViolationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Violation::class, ViolationPolicy::class);
        Gate::policy(ParentModel::class, ParentPolicy::class);
        Gate::policy(AbsenceSubmission::class, AbsenceSubmissionPolicy::class);
    }
}
