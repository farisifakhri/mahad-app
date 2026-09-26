@extends('layouts.portal')
@section('title', 'Perkembangan Anak')
@section('content')
    <div class="space-y-4">
        @forelse ($students as $student)
            <article class="rounded-xl border bg-white p-5">
                <h2 class="text-lg font-semibold">{{ $student->user->name }}</h2>
                <p class="text-sm text-slate-600">{{ $student->nim }} · {{ $student->group->name }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-emerald-50 p-3"><dt class="text-sm">Catatan absensi</dt><dd class="text-xl font-bold">{{ $student->attendances_count }}</dd></div>
                    <div class="rounded-lg bg-amber-50 p-3"><dt class="text-sm">Pelanggaran</dt><dd class="text-xl font-bold">{{ $student->violations_count }}</dd></div>
                </dl>
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada anak yang terhubung. Hubungi pengelola mabna.</p>
        @endforelse
    </div>
@endsection
