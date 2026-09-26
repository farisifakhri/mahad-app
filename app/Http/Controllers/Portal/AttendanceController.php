<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        return view('portal.absensi', ['attendances' => $monitoring->attendances($request->user())->latest()->paginate(15)]);
    }
}
