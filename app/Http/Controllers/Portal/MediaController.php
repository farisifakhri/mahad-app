<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AbsenceSubmission;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __invoke(Request $request, Media $media): BinaryFileResponse
    {
        $owner = $media->model;
        abort_unless($owner instanceof AbsenceSubmission || $owner instanceof Violation, 404);
        Gate::authorize('view', $owner);
        abort_unless($media->disk === 'local' && Storage::disk('local')->exists($media->getPathRelativeToRoot()), 404);

        return response()->download($media->getPath(), $media->file_name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
