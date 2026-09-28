<x-filament-panels::page>
@include('filament.pages.operational-style')
@forelse($submissions as $submission)
<div class="sipma-card">
<h2>{{ $submission->student?->user?->name ?? 'Mahasantri historis' }} · {{ $submission->type->getLabel() }}</h2>
<p>{{ $submission->activitySession->date->format('d/m/Y') }} · {{ $submission->activitySession->activity->name }} · {{ $submission->status->getLabel() }}</p>
<p>{{ $submission->reason }}</p>
@foreach($submission->media as $media)<a href="{{ route('media.download', $media) }}">Unduh bukti</a>@endforeach
@if($submission->latitude !== null)<p>Lokasi: {{ $submission->latitude }}, {{ $submission->longitude }}</p>@endif
@if($submission->review_notes)<p>Catatan review: {{ $submission->review_notes }}</p>@endif
@if(\Illuminate\Support\Facades\Gate::allows('review', $submission) && app(\App\Services\AttendanceWorkflow::class)->ordinaryWindow($submission->activitySession))
<label>Catatan review<textarea wire:model="reviewNotes.{{ $submission->id }}" maxlength="2000"></textarea></label>
<button wire:click="review({{ $submission->id }}, 'APPROVED', {{ $submission->version }})" wire:loading.attr="disabled">Setujui</button>
<button wire:click="review({{ $submission->id }}, 'REJECTED', {{ $submission->version }})" wire:loading.attr="disabled">Tolak</button>
@else<p>Review tidak tersedia untuk role ini atau periode sudah terkunci.</p>@endif
</div>
@empty<p>Belum ada pengajuan.</p>@endforelse
</x-filament-panels::page>
