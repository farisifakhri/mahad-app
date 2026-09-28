<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\DateRange;
use App\Http\Controllers\Controller;
use App\Models\ActivitySession;
use App\Services\AttendanceWorkflow;
use App\Services\DevelopmentService;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        $range = DateRange::fromArray($request->query());
        $student = $request->user()->student;
        $summary = app(DevelopmentService::class)->summary($request->user(), $student, $range);
        $sessions = ActivitySession::with('activity')->where('status', 'open')->whereBetween('date', [$range->from, $range->to])->where('roster', 'like', '%'.$student->id.'%')->orderBy('date')->orderBy('starts_at')->get()->filter(fn ($session) => app(AttendanceWorkflow::class)->rosterContains($session, $student->id));

        return view('portal.absensi', ['range' => $range, 'summary' => $summary, 'sessions' => $sessions, 'attendances' => $monitoring->attendances($request->user())
            ->whereHas('activitySession', fn ($query) => $query->whereBetween('date', [$range->from, $range->to]))->latest()->paginate(15)->withQueryString()]);
    }
}
