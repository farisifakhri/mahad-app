@extends('layouts.portal')
@section('title', 'Riwayat Absensi')
@section('content')
    <div class="space-y-3">
        @forelse ($attendances as $attendance)
            <article class="rounded-xl border bg-white p-4">
                <p class="font-semibold">{{ $attendance->activitySession->activity->name }}</p>
                <p class="text-sm">{{ $attendance->activitySession->date->format('d/m/Y') }} · {{ $attendance->status->getLabel() }}</p>
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada catatan absensi.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $attendances->links() }}</div>
@endsection
