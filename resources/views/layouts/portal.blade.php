<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — SIPMA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto min-h-screen max-w-lg" x-data="{ menuOpen: false }">
        <header class="border-b bg-white px-5 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold text-emerald-800">SIPMA</a>
                <button type="button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="portal-menu" class="rounded-lg border px-3 py-2">Menu</button>
            </div>
            <p class="mt-1 text-sm text-slate-600">Mabna Syekh Nawawi</p>
            <nav id="portal-menu" x-show="menuOpen" x-cloak class="mt-4 space-y-3" aria-label="Navigasi portal">
                @role('mahasantri')
                    <a class="block" href="{{ route('portal.absensi') }}">Riwayat absensi</a>
                    <a class="block" href="{{ route('portal.pengajuan') }}">Pengajuan izin / sakit</a>
                @endrole
                @role('orang_tua')
                    <a class="block" href="{{ route('portal.anak') }}">Perkembangan anak</a>
                @endrole
                <a class="block" href="{{ route('profile.edit') }}">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg bg-slate-800 px-4 py-2 text-white" type="submit">Keluar</button>
                </form>
            </nav>
        </header>
        <main class="p-5">
            <p class="mb-2 text-sm text-slate-500">{{ auth()->user()->name }}</p>
            <h1 class="mb-5 text-2xl font-bold">@yield('title')</h1>
            @yield('content')
        </main>
    </div>
</body>
</html>
