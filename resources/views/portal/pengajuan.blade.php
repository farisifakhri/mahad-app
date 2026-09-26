@extends('layouts.portal')
@section('title', 'Pengajuan Izin / Sakit')
@section('content')
    <p class="mb-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">Form pengajuan izin/sakit akan tersedia pada pengembangan modul berikutnya.</p>
    <div class="space-y-3">
        @forelse ($submissions as $submission)
            <article class="rounded-xl border bg-white p-4">
                <p class="font-semibold">{{ $submission->type->getLabel() }} · {{ $submission->status->getLabel() }}</p>
                <p class="text-sm">{{ $submission->reason }}</p>
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada pengajuan.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $submissions->links() }}</div>
@endsection
