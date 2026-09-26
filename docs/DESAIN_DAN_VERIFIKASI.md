# Desain dan verifikasi SIPMA
## Audit awal dan arah visual
Login awal memakai kartu Breeze satu kolom; dashboard memakai widget total umum;
portal dibatasi max-width kecil dan belum memiliki navigasi/komponen status
bersama. Halaman operasional sudah menggunakan action/policy bersama, sehingga
perubahan tampilan tidak memerlukan perubahan aturan domain.

Fondasi baru: navy #0B2E59, biru #2465A8, latar #F7F9FC, teks #172B45,
aksen kuning #D8A941 secukupnya, Plus Jakarta Sans, radius 9–24 px.
Ini palet SIPMA usulan pengguna, bukan klaim sebagai palet resmi universitas.
Login memakai kartu dua panel lebar hingga 1400 px. Ponsel memakai header
ilustrasi ringkas, form satu kolom dan tombol minimal 44–46 px.
Dashboard menampilkan kelompok/sesi/roster asli, kelengkapan dan tenggat.
Sesi, laporan, pengajuan, pelanggaran, portal dan resource Filament memakai
token visual yang sama dalam public/css/sipma.css. Profil dan halaman
pemulihan password memakai logo, font, tombol dan input yang sama.

Badge selalu mempunyai teks/simbol selain warna. Form absensi berubah menjadi
daftar dengan label status/catatan pada ponsel. Tabel laporan tetap memiliki
wadah scroll lokal. Fokus keyboard terlihat, input mempunyai label,
loading/error memakai role status/alert, navigasi mobile dapat dibuka/tutup.
Indikator perubahan form direset setelah penyimpanan berhasil oleh server.
Validasi CSRF, login Laravel, redirect role, permission dan policy dipertahankan.

## Sumber dan provenance aset
- [Identitas resmi UIN](https://uinjkt.ac.id/id/identitas):
  berkas lambang di public/brand/uin-jakarta-official.png diunduh langsung dari
  pusat identitas, URL
  https://asset.uinjkt.ac.id/uploads/fmXyXwZY/2026/07/lambang-resmi-universitasstack-up-lock-up-1.png.
  Tidak digambar ulang, diubah warnanya, atau disatukan ke logo aplikasi.
- [Panduan brand UIN](https://uinjkt.ac.id/id/brand-guideline) dan
  [profil Mabna Syekh Nawawi](https://mahadaljamiah.uinjkt.ac.id/index.php/id/mabna-syekh-nawawi)
  dibaca sebagai acuan konteks kelembagaan.
- public/brand/mabna-syekh-nawawi.jpg dan mahad-al-jamiah.jpg adalah dua aset
  yang diberikan pengguna dalam percakapan. Dipakai apa adanya, terpisah.
- sipma.svg, sipma-mono.svg, favicon.svg: SVG orisinal dengan konsep buku,
  pintu bangunan, dan tiga titik kebersamaan. Tidak meniru lambang resmi UIN.
- mabna-dawn.svg: ilustrasi orisinal suasana mabna; bukan foto atau penggambaran
  arsitektur faktual yang terverifikasi.
- Komposisi login repository farisifakhri/lpmqtashihhub,
  frontend/src/features/auth/LoginPage.jsx, dibaca melalui GitHub API.
  Yang diadaptasi adalah susunan dua panel/form responsif. Tidak memakai kode
  React, aset LPMQ/Kemenag, gambar Al-Qur’an, atau identitas SIPNA.
- Font Plus Jakarta Sans diunduh dari Bunny Fonts dan disajikan lokal di
  public/fonts/sipma; lisensi SIL OFL disertakan dalam OFL.txt. Login dan panel
  tidak perlu menunggu koneksi layanan font eksternal.

## Hasil pemeriksaan
- 56 tes / 360 assertion lulus pada SQLite memory dan MySQL sipma_testing.
- Migration upgrade database sipma berhasil; tetap 122 akun / 100 mahasantri.
- Build Vite berhasil; php artisan serve lokal 8001 dan GET /login HTTP 200.
- Playwright memakai Edge headless pada 1440×1000 dan 390×844.
  Login, error kredensial, toggle password, dashboard ketua, form absensi,
  laporan kosong, portal mahasantri, pengajuan dan portal orang tua diperiksa.
- Resize login pada jendela yang sama diperiksa di 1920×1080, 1440×900,
  1366×768, 1280×720, 390×844 dan 375×667; tampilan normal harus muat tanpa
  scroll horizontal/vertikal. Layar yang sangat pendek tetap dapat menggulir
  bila diperlukan untuk membaca pesan error; tidak ada konten form yang dipotong.
- Pengujian tidak menemukan error JavaScript/HTTP 500 atau overflow halaman
  pada layar yang dicakup. Navigasi portal mobile diverifikasi tertutup,
  dapat dibuka, lalu ditutup kembali.
- Tangkapan layar ada di docs/screenshots; hasil otomatis checks.json.
  Data contoh berasal dari database sipma_testing yang terpisah dari sipma.
  Ini data fixture demo, bukan statistik operasional lembaga.
- Inspeksi visual dilakukan pada login desktop/mobile, dashboard desktop,
  form absensi mobile dan portal mobile. Pemeriksaan menemukan navigasi mobile
  yang tertimpa CSS dan tabel absensi terlalu lebar; keduanya diperbaiki.
- Dialog/notifikasi resource menggunakan komponen Filament dengan token warna
  panel. Tidak ada klaim audit aksesibilitas menyeluruh atau uji semua browser.

## Mengulang pemeriksaan browser
Jalankan suite MySQL terlebih dahulu. Sesudahnya, siapkan fixture visual hanya
pada sipma_testing:

```powershell
$env:DB_DATABASE = 'sipma_testing'
php tests/Browser/seed-visual.php
$env:SESSION_COOKIE = 'sipma_visual_session'
$env:APP_URL = 'http://127.0.0.1:8002'
php artisan serve --host=127.0.0.1 --port=8002
```

Di terminal terpisah, jalankan node tests/Browser/sipma-smoke.mjs.
Script memakai Edge yang terpasang; executable dapat diganti lewat
SIPMA_BROWSER_EXECUTABLE dan alamat lewat SIPMA_BROWSER_URL.
Seed visual menolak database selain sipma_testing. Setelah selesai, tutup
server visual dan gunakan terminal biasa untuk serve database sipma di 8001.
Jangan menjalankan RefreshDatabase pada sipma_testing selama pemeriksaan
browser masih memakai database itu.

## File utama
Login: resources/views/auth/login.blade.php.
Dashboard: app/Filament/Pages/Dashboard.php, app/Services/DashboardService.php,
resources/views/filament/pages/dashboard.blade.php.
Token/aset: public/css/sipma.css, public/brand/.
Layout dan badge: resources/views/layouts/portal.blade.php,
resources/views/components/status-badge.blade.php, primary-button.blade.php,
text-input.blade.php.
Halaman lain: resources/views/filament/pages/, resources/views/portal/.
Panel: app/Providers/Filament/AdminPanelProvider.php.
Tes: tests/Browser/, tests/Feature/SipmaOperationsTest.php.
Daftar struktur domain dan aturan operasional lengkap: BASELINE.md.
