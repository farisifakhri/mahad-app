<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\DateRange;
use App\Http\Controllers\Controller;
use App\Services\DevelopmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, DevelopmentService $development): View
    {
        $range = DateRange::fromArray($request->query());

        return view('portal.laporan', ['range' => $range, 'reports' => $development->reportVersions($request->user(), $range)]);
    }
}
