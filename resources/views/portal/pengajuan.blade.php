@extends('layouts.portal')
@section('title', 'Pengajuan Izin / Sakit')
@section('content')
    @if(session('status'))<p class="mb-4 rounded-xl bg-emerald-50 p-4">{{ session('status') }}</p>@endif
    @if($errors->any())<ul class="mb-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($sessions->isEmpty())<div class="sipma-notice">Belum ada sesi yang dapat diajukan. Sesi harus dibuka dan periode belum terkunci.</div>@endif
    <form method="POST" action="{{ route('portal.pengajuan.store') }}" enctype="multipart/form-data" class="sipma-card" x-data="{ locationMessage: '', submitting: false }" @submit="submitting = true">
        <h2>Pengajuan per sesi kegiatan</h2><p class="sipma-muted mb-4">Pilih satu sesi. Bukti dan lokasi bersifat opsional; pengajuan menunggu verifikasi pengelola kelompok.</p>
        @csrf
        <label class="block">Sesi kegiatan<select name="activity_session_id" class="mt-1 w-full rounded-lg border-slate-300" required><option value="">Pilih sesi</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected(old('activity_session_id') == $session->id)>{{ $session->date->format('d/m/Y') }} · {{ $session->activity->name }} #{{ $session->occurrence }}</option>@endforeach</select></label>
        <label class="block">Jenis<select name="type" class="mt-1 w-full rounded-lg border-slate-300"><option value="IZIN">Izin</option><option value="SAKIT" @selected(old('type') === 'SAKIT')>Sakit</option></select></label>
        <label class="block">Alasan<textarea name="reason" required minlength="5" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea></label>
        <label class="block">Bukti opsional (JPG/PNG/PDF, maks. 5 MB)<input type="file" name="evidence" accept="image/jpeg,image/png,application/pdf" class="mt-1 w-full"></label>
        <input type="hidden" name="latitude" x-ref="latitude" value="{{ old('latitude') }}"><input type="hidden" name="longitude" x-ref="longitude" value="{{ old('longitude') }}">
        <button type="button" class="rounded-lg border px-3 py-2" @click="if (!navigator.geolocation) { locationMessage = 'Lokasi tidak didukung.'; } else { navigator.geolocation.getCurrentPosition(position => { $refs.latitude.value = position.coords.latitude; $refs.longitude.value = position.coords.longitude; locationMessage = 'Lokasi ditambahkan.'; }, () => locationMessage = 'Lokasi tidak diberikan. Anda tetap bisa mengirim pengajuan.'); }">Tambahkan lokasi (opsional)</button>
        <p class="text-sm text-slate-500" x-text="locationMessage"></p>
        <button type="submit" :disabled="submitting" @disabled($sessions->isEmpty())><span x-show="!submitting">Kirim pengajuan →</span><span x-show="submitting" x-cloak> Mengirim…</span></button>
    </form>
    <div class="space-y-3">
        @forelse ($submissions as $submission)
            <article class="rounded-xl border bg-white p-4">
                <div class="sipma-section-head"><strong>{{ $submission->type->getLabel() }}</strong><x-status-badge :status="$submission->status"/></div>
                <p class="text-sm">{{ $submission->reason }}</p>
                @if($submission->review_notes)<p class="text-sm">Catatan review: {{ $submission->review_notes }}</p>@endif
                @foreach($submission->media as $media)<a class="text-sm text-emerald-800 underline" href="{{ route('media.download', $media) }}">Unduh bukti</a>@endforeach
            </article>
        @empty
            <p class="rounded-xl border bg-white p-5 text-slate-600">Belum ada pengajuan.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $submissions->links() }}</div>
@endsection
