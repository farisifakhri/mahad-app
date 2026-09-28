<?php

namespace App\Http\Controllers\Portal;

use App\Actions\SubmitAbsence;
use App\Http\Controllers\Controller;
use App\Models\ActivitySession;
use App\Services\AttendanceWorkflow;
use App\Services\MonitoringService;
use App\Services\WeeklyCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        $student = $request->user()->student;

        return view('portal.pengajuan', [
            'submissions' => $monitoring->submissions($request->user())->with('media')->latest()->paginate(15),
            'sessions' => ActivitySession::where('status', 'open')->whereDate('date', '>=', app(WeeklyCalendar::class)->start(now())->toDateString())->orderBy('date')->get()
                ->filter(fn ($session) => app(AttendanceWorkflow::class)->rosterContains($session, $student->id) && ! app(WeeklyCalendar::class)->isLocked($session->date)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['activity_session_id' => ['required', 'integer', 'exists:activity_sessions,id']]);
        app(SubmitAbsence::class)->execute($request->user(), ActivitySession::findOrFail($request->integer('activity_session_id')),
            $request->only(['type', 'reason', 'latitude', 'longitude']), $request->file('evidence'));

        return redirect()->route('portal.pengajuan')->with('status', 'Pengajuan terkirim dan menunggu review.');
    }
}
