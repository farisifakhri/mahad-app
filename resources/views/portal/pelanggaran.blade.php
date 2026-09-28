@extends('layouts.portal')
@section('title', 'Riwayat Pelanggaran')
@section('content')
@include('portal.date-filter')
@forelse($violations as $violation)
<article class="mb-3 rounded-xl border bg-white p-4">
<h2 class="font-semibold">{{ $violation->category->name }}</h2>
<p class="text-sm">{{ $violation->occurred_on->format('d/m/Y') }} · {{ $violation->description }}</p>
@foreach($violation->media as $media)<a class="text-emerald-800 underline" href="{{ route('media.download', $media) }}">Unduh foto</a>@endforeach
</article>
@empty<p class="rounded-xl border bg-white p-5">Tidak ada pelanggaran dalam periode ini.</p>@endforelse
{{ $violations->links() }}
@endsection
