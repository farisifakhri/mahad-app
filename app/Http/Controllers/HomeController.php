<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasAnyRole(['super_admin', 'murabbi', 'mudabbir'])) {
            return redirect()->route('filament.admin.pages.dashboard');
        }
        if ($user->hasRole('mahasantri')) {
            return redirect()->route('portal.absensi');
        }
        if ($user->hasRole('orang_tua')) {
            return redirect()->route('portal.anak');
        }
        abort(403);
    }
}
