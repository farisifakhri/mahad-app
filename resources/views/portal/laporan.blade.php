@extends('layouts.portal')
@section('title', 'Versi Laporan Pembinaan')
@section('content')
@include('portal.date-filter')
@forelse($reports as $report)
<article class="mb-4 rounded-xl border bg-white p-4">
<h2 class="font-semibold">{{ $report['group'] }} · Versi {{ $report['version'] }}</h2>
<x-status-badge :status="$report['version'] > 1 ? 'revisi' : 'final'"/>
<p class="text-sm">{{ $report['from'] }}–{{ $report['to'] }} · Terbit {{ $report['published_at']->format('d/m/Y H:i') }} WIB</p>
@foreach($report['sessions'] as $session)
<div class="mt-3 border-t pt-3"><p>{{ $session['date'] }} · {{ $session['activity'] }}</p>
@foreach($session['students'] as $student)<p class="text-sm">{{ $student['name'] }} · <x-status-badge :status="$student['status']"/> · {{ $student['notes'] }}</p>@endforeach
</div>
@endforeach
</article>
@empty<p class="rounded-xl border bg-white p-5">Belum ada laporan final untuk Anda/anak Anda pada periode ini.</p>@endforelse
@endsection
