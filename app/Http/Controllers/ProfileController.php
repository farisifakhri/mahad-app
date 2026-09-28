<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->hasAnyRole(['super_admin', 'pengasuh', 'murabbi', 'mudabbir'])
            || $user->student()->withTrashed()->exists() || $user->parentProfile()->exists()
            || $user->recordedAttendances()->exists() || $user->recordedViolations()->withTrashed()->exists()
            || $user->reviewedSubmissions()->exists()
            || Activity::where('causer_type', $user->getMorphClass())->where('causer_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'password' => 'Akun yang terkait pembinaan tidak dapat dihapus mandiri. Hubungi admin.',
            ])->errorBag('userDeletion');
        }

        try {
            DB::transaction(fn () => $user->delete());
        } catch (QueryException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                throw ValidationException::withMessages([
                    'password' => 'Akun masih terhubung dengan data pembinaan. Hubungi admin.',
                ])->errorBag('userDeletion');
            }
            throw $exception;
        }

        // Do not cycle the remember token after deletion: that can re-insert a deleted model.
        Auth::guard('web')->logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
