@props(['status'])
@php
$value = $status instanceof \BackedEnum ? $status->value : $status;
$labels = ['HADIR'=>'✓ Hadir','ALFA'=>'× Alfa','IZIN'=>'↗ Izin','SAKIT'=>'+ Sakit','PENDING'=>'◷ Menunggu','APPROVED'=>'✓ Disetujui','REJECTED'=>'× Ditolak','open'=>'○ Dibuka','draft'=>'◷ Draft','final'=>'▣ Final','revisi'=>'↻ Revisi','locked'=>'▣ Terkunci'];
@endphp
<span class="sipma-badge" data-status="{{ $value }}">{{ $labels[$value] ?? $value }}</span>
