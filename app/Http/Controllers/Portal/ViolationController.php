<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\DateRange;
use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViolationController extends Controller
{
    public function index(Request $request, MonitoringService $monitoring): View
    {
        $range = DateRange::fromArray($request->query());

        return view('portal.pelanggaran', ['range' => $range, 'violations' => $monitoring->violations($request->user())->whereBetween('occurred_on', [$range->from, $range->to])->with('media')->latest()->paginate(15)->withQueryString()]);
    }
}
