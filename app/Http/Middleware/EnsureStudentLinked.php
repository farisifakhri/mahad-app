<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentLinked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->student()->whereHas('group', fn ($group) => $group->whereNull('groups.deleted_at'))->exists()) {
            return redirect()->route('portal.onboarding');
        }

        return $next($request);
    }
}
