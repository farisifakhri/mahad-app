<x-filament-panels::page>
@include('filament.pages.operational-style')
<div class="sipma-notice">Pekan Minggu–Sabtu · Absensi biasa dikunci setiap Sabtu pukul <strong>23.59 WIB</strong>.</div>
@if(auth()->user()->can('sessions.open'))
<form wire:submit="openSession" class="sipma-card">
<h2>Buka sesi kegiatan</h2>
<div class="sipma-grid">
<label>Kelompok<select wire:model="groupId" required><option value="">Pilih kelompok</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
<label>Kegiatan<select wire:model="activityId" required><option value="">Pilih kegiatan</option>@foreach($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->name }}</option>@endforeach</select></label>
<label>Tanggal<input type="date" wire:model="date" required></label>
<label>Mulai (WIB)<input type="time" wire:model="startsAt" required></label>
<label>Selesai (WIB)<input type="time" wire:model="endsAt" required></label>
<label>Urutan kegiatan hari ini<input type="number" min="1" max="100" wire:model="occurrence" required></label>
</div><button type="submit" wire:loading.attr="disabled">Buka sesi</button>
</form>
@endif
<div class="sipma-card sipma-session-list">
<h2>Sesi terbaru</h2>
<div class="sipma-grid"><label>Kelompok<select wire:model.live="filterGroup"><option value="">Semua kelompok tugas</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label><label>Kegiatan<select wire:model.live="filterActivity"><option value="">Semua kegiatan</option>@foreach($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->name }}</option>@endforeach</select></label><label>Tanggal<input aria-label="Filter tanggal sesi" type="date" wire:model.live="filterDate"></label></div>
@forelse($sessions as $item)
<button type="button" wire:click="selectSession({{ $item->id }})"><span>{{ $item->activity->name }} #{{ $item->occurrence }}<small style="display:block;font-weight:400;margin-top:5px">{{ $item->date->format('d/m/Y') }} · {{ $item->group->name }} · {{ $item->starts_at }}–{{ $item->ends_at }} WIB</small></span><x-status-badge :status="$item->status"/></button>
@empty<div class="sipma-empty"><strong>Belum ada sesi</strong>Ubah filter atau tunggu murabbi/ketua membuka kegiatan.</div>@endforelse
</div>
@if($session)
<form wire:submit="saveAttendance" class="sipma-card" x-data="{ dirty: false }" @change="dirty = true" @attendance-saved.window="dirty = false">
<h2>{{ $session->activity->name }} · {{ $session->group->name }}</h2>
<p>{{ $session->date->format('d/m/Y') }} {{ $session->starts_at }}–{{ $session->ends_at }} WIB · Tenggat {{ $state['cutoff'] }}</p>
@if($state['lock_reason'])<p class="sipma-notice" role="status">▣ {{ $state['lock_reason'] }}</p>@endif
<p class="sipma-muted" style="margin:12px 0"><span x-show="!dirty">✓ Data terakhir dimuat dari server. Pilih status, lalu simpan seluruh absensi.</span><span x-show="dirty" x-cloak>◷ Ada perubahan pada formulir. Simpan untuk mencatat ke server.</span></p>
<div class="sipma-scroll"><table class="sipma-table sipma-attendance-table"><thead><tr><th>Mahasantri</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
@foreach($session->roster ?? [] as $index => $member)
<tr wire:key="attendance-{{ $session->id }}-{{ $member['student_id'] }}">
<td>{{ $member['name'] }}<br>{{ $member['nim'] }}</td>
<td data-label="Status"><select aria-label="Status {{ $member['name'] }}" wire:model="attendanceRows.{{ $index }}.status" @disabled(!$state['can_edit']) required><option value="">Belum diisi</option>@foreach(\App\Enums\AttendanceStatusEnum::cases() as $status)<option value="{{ $status->value }}">{{ $status->getLabel() }}</option>@endforeach</select></td>
<td data-label="Catatan"><input aria-label="Catatan {{ $member['name'] }}" wire:model="attendanceRows.{{ $index }}.notes" maxlength="2000" @disabled(!$state['can_edit'])></td></tr>
@endforeach
</tbody></table></div>
@if($state['can_edit'])<p>IZIN/SAKIT memerlukan pengajuan yang sudah disetujui.</p><button type="submit" wire:loading.attr="disabled">Simpan seluruh absensi</button>@endif
</form>
@endif
</x-filament-panels::page>
