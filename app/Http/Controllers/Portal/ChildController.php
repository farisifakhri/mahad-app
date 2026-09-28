<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\DateRange;
use App\Http\Controllers\Controller;
use App\Services\DevelopmentService;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        $range = DateRange::fromArray($request->query());
        $students = $monitoring->students($request->user())->with(['user', 'group'])->get();
        $summaries = $students->mapWithKeys(fn ($student) => [$student->id => app(DevelopmentService::class)->summary($request->user(), $student, $range)]);

        return view('portal.anak', compact('students', 'summaries', 'range'));
    }
}
