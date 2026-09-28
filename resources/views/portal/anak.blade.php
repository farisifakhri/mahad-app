@extends('layouts.portal')
@section('title', 'Perkembangan Anak')
@section('content')
    @include('portal.date-filter')
    <div class="space-y-4">
        @forelse ($students as $student)
            <article class="rounded-xl border bg-white p-5">
                <h2 class="text-lg font-semibold">{{ $student->user->name }}</h2>
                <p class="text-sm text-slate-600">{{ $student->nim }} · {{ $student->group->name }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-emerald-50 p-3"><dt class="text-sm">Kehadiran periode ini</dt><dd class="text-xl font-bold">{{ $summaries[$student->id]['attendance_percentage'] !== null ? $summaries[$student->id]['attendance_percentage'].'%' : 'Belum ada sesi' }}</dd></div>
                    <div class="rounded-lg bg-amber-50 p-3"><dt class="text-sm">Pelanggaran periode ini</dt><dd class="text-xl font-bold">{{ $summaries[$student->id]['violation_count'] }}</dd></div>
                </dl>
                <p class="mt-4 text-sm">@foreach($summaries[$student->id]['counts'] as $status => $count){{ $status }}: {{ $count }} · @endforeach Belum diisi: {{ $summaries[$student->id]['unrecorded'] }}</p>
                <h3 class="mt-4 font-semibold">Detail absensi</h3>
                @forelse($summaries[$student->id]['attendances'] as $attendance)<p class="mt-2 text-sm">{{ $attendance->activitySession->date->format('d/m/Y') }} · {{ $attendance->activitySession->activity->name }} · {{ $attendance->status->getLabel() }}</p>@empty<p class="text-sm text-slate-500">Belum ada catatan.</p>@endforelse
                <h3 class="mt-4 font-semibold">Detail pelanggaran</h3>
                @forelse($summaries[$student->id]['violations'] as $violation)<p class="mt-2 text-sm">{{ $violation->occurred_on->format('d/m/Y') }} · {{ $violation->category->name }} · {{ $violation->description }}</p>@foreach($violation->media as $media)<a class="text-sm text-emerald-800 underline" href="{{ route('media.download', $media) }}">Unduh foto</a>@endforeach @empty<p class="text-sm text-slate-500">Belum ada pelanggaran.</p>@endforelse
                <p class="mt-3 text-xs text-slate-500">Detail menampilkan maksimal 100 catatan terbaru dalam periode pilihan.</p>
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada anak yang terhubung. Hubungi pengelola mabna.</p>
        @endforelse
    </div>
@endsection
