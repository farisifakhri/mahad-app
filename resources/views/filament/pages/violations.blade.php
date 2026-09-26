<x-filament-panels::page>
@include('filament.pages.operational-style')
@if(auth()->user()->can('violations.record'))
<form wire:submit="save" class="sipma-card"><h2>{{ $recordId ? 'Ubah pelanggaran' : 'Catat pelanggaran' }}</h2>
<div class="sipma-grid">
<label>Mahasantri<select wire:model="studentId" required @disabled($recordId)><option value="">Pilih mahasantri</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->nim }} · {{ $student->user->name }}</option>@endforeach</select></label>
<label>Kategori<select wire:model="categoryId" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }} ({{ $category->points }} poin)</option>@endforeach</select></label>
<label>Tanggal<input type="date" wire:model="occurredOn" required></label>
</div>
<label>Deskripsi<textarea wire:model="description" minlength="5" maxlength="2000" required></textarea></label>
<label>Foto opsional<input type="file" wire:model="photo" accept="image/jpeg,image/png"></label>
<button type="submit" wire:loading.attr="disabled">Simpan</button></form>
@endif
@forelse($violations as $violation)
<div class="sipma-card">
<h2>{{ $violation->student?->user?->name ?? 'Mahasantri historis' }} · {{ $violation->category->name }}</h2>
<p>{{ $violation->occurred_on->format('d/m/Y') }} · {{ $violation->description }}</p>
@foreach($violation->media as $media)<a href="{{ route('media.download', $media) }}">Unduh foto</a>@endforeach
@can('update', $violation)<button wire:click="edit('{{ $violation->id }}')">Ubah</button>@endcan
@can('delete', $violation)<button wire:click="remove('{{ $violation->id }}')" wire:confirm="Arsipkan pelanggaran ini?">Arsipkan</button>@endcan
</div>
@empty<p>Belum ada pelanggaran.</p>@endforelse
</x-filament-panels::page>
