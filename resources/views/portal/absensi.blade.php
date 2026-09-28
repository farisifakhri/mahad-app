@extends('layouts.portal')
@section('title', 'Riwayat Absensi')
@section('content')
    @include('portal.date-filter')
    <section class="sipma-card"><div class="sipma-section-head"><h2>Kehadiran pekan pilihan</h2><a href="{{ route('portal.pengajuan') }}" class="sipma-button sipma-secondary">Ajukan izin / sakit →</a></div><p class="sipma-muted">{{ $summary['expected'] }} sesi selesai · {{ $summary['unrecorded'] }} belum diisi. Belum diisi tidak dihitung sebagai alfa.</p><div class="flex flex-wrap gap-3 mt-4">@foreach($summary['counts'] as $status => $count)<div><x-status-badge :status="$status"/> <strong>{{ $count }}</strong></div>@endforeach</div></section>
    <section class="sipma-card"><h2>Sesi kegiatan dalam periode</h2>@forelse($sessions as $session)<div class="sipma-row"><div><strong>{{ $session->activity->name }}</strong><small>{{ $session->date->format('d/m/Y') }} · {{ $session->starts_at }}–{{ $session->ends_at }} WIB</small></div><x-status-badge :status="$session->status"/></div>@empty<div class="sipma-empty"><strong>Belum ada sesi kegiatan</strong>Sesi yang sudah dibuka untuk Anda akan muncul di sini.</div>@endforelse</section>
    <div class="space-y-3">
        @forelse ($attendances as $attendance)
            <article class="rounded-xl border bg-white p-4">
                <p class="font-semibold">{{ $attendance->activitySession->activity->name }}</p>
                <div class="sipma-section-head"><p class="sipma-muted">{{ $attendance->activitySession->date->format('d/m/Y') }}</p><x-status-badge :status="$attendance->status"/></div>
                @if($attendance->notes)<p class="text-sm">{{ $attendance->notes }}</p>@endif
                <p class="text-xs text-slate-500">Versi absensi {{ $attendance->version }}</p>
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada catatan absensi.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $attendances->links() }}</div>
@endsection
