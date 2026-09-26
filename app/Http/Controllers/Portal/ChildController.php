<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        return view('portal.anak', ['students' => $monitoring->students($request->user())
            ->with(['user', 'group'])->withCount(['attendances', 'violations'])->get()]);
    }
}
