<x-filament-panels::page>
@include('filament.pages.operational-style')
<div class="sipma-notice">Laporan pekan Minggu–Sabtu dapat difinalkan mulai <strong>Minggu</strong>. Koreksi mudabbir menunggu review murabbi.</div>
@if(auth()->user()->hasAnyRole(['super_admin', 'mudabbir']) && auth()->user()->can('reports.finalize'))
<form wire:submit="finalize" class="sipma-card"><h2>Finalisasi laporan</h2><div class="sipma-grid">
<label>Kelompok<select wire:model="groupId" required><option value="">Pilih kelompok</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
<label>Tanggal dalam pekan laporan<input type="date" wire:model="periodDate" required></label>
</div><button type="submit" wire:loading.attr="disabled">Finalisasi pekan</button></form>
@endif
@if($correctionAttendanceId)
<form wire:submit="requestCorrection" class="sipma-card"><h2>Ajukan koreksi</h2>
<label>Status baru<select wire:model="newStatus">@foreach(\App\Enums\AttendanceStatusEnum::cases() as $status)<option value="{{ $status->value }}">{{ $status->getLabel() }}</option>@endforeach</select></label>
<label>Catatan baru<textarea wire:model="newNotes" maxlength="2000"></textarea></label>
<label>Alasan wajib<textarea wire:model="correctionReason" required minlength="5" maxlength="2000"></textarea></label>
<button type="submit" wire:loading.attr="disabled">Kirim ke murabbi</button></form>
@endif
<div class="sipma-card"><h2>Riwayat koreksi</h2>
@forelse($corrections as $correction)
<div class="sipma-card">
<p>{{ $correction->period->group->name }} · {{ $correction->attendance->student?->user?->name ?? 'Mahasantri historis' }} · {{ $correction->old_status->getLabel() }} → {{ $correction->new_status->getLabel() }}</p>
<p>{{ $correction->reason }} · <x-status-badge :status="$correction->status"/> · Pengaju {{ $correction->requestedBy->name }}</p>
@if($correction->review_notes)<p>Catatan review: {{ $correction->review_notes }}</p>@endif
@can('review', $correction)
<label>Catatan review<textarea wire:model="reviewNotes.{{ $correction->id }}" maxlength="2000"></textarea></label>
<button wire:click="reviewCorrection({{ $correction->id }}, 'APPROVED')" wire:loading.attr="disabled">Setujui & terbitkan revisi</button>
<button wire:click="reviewCorrection({{ $correction->id }}, 'REJECTED')" wire:loading.attr="disabled">Tolak</button>
@endcan
</div>
@empty<p>Belum ada koreksi.</p>@endforelse
</div>
@forelse($periods as $period)
<div class="sipma-card">
<h2>{{ $period->group->name }} · {{ $period->starts_on->format('d/m/Y') }}–{{ $period->ends_on->format('d/m/Y') }}</h2>
<x-status-badge :status="$period->finalized_at ? ($period->current_version > 1 ? 'revisi' : 'final') : 'draft'"/>
@foreach($period->reports->sortByDesc('version') as $report)
<details class="sipma-card"><summary>Versi {{ $report->version }} · {{ $report->created_at->format('d/m/Y H:i') }} WIB</summary>
<p>{{ $report->reason }}</p>
<p>@foreach($report->snapshot['totals'] as $status => $count) {{ $status }}: {{ $count }} · @endforeach</p>
@foreach($report->snapshot['sessions'] as $session)
<h3>{{ $session['date'] }} · {{ $session['activity'] }}</h3>
<div class="sipma-scroll"><table class="sipma-table"><thead><tr><th>Mahasantri</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
@foreach($session['students'] as $member)<tr>
<td>{{ $member['name'] }} · {{ $member['nim'] }}</td><td><x-status-badge :status="$member['status']"/></td><td>{{ $member['notes'] }}</td>
<td>@if(auth()->user()->can('attendances.correct') && $report->version === $period->current_version && $member['attendance_id'])
<button wire:click="beginCorrection('{{ $member['attendance_id'] }}', {{ $member['version'] }})">Ajukan koreksi</button>@endif</td>
</tr>@endforeach</tbody></table></div>
@endforeach</details>
@endforeach
</div>
@empty<p>Belum ada periode laporan.</p>@endforelse
</x-filament-panels::page>
