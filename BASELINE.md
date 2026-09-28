# SIPMA — baseline operasional
Laravel 12, PHP 8.3+, MySQL 8+, Filament 4, Blade/Tailwind/Alpine,
Spatie Permission, Activity Log, dan Media Library. Repository: farisifakhri/mahad-app.
Project lokal: C:\laragon\www\mahad-app.

## Menjalankan aplikasi
Untuk instalasi baru, siapkan .env dari .env.example, install Composer/npm,
buat database sipma, lalu jalankan:

```powershell
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Untuk upgrade database baseline yang sudah berisi akun:
```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=DemoIdentitySeeder # akun demo dan identitas terbaru
npm run build
```

Migration baru mempertahankan tabel dan data baseline. Jangan memakai
migrate:fresh pada database berisi data yang perlu disimpan.
SIPMA menggunakan port 8001, Vite 5174, dan cookie sipma_session agar terpisah
dari aplikasi lain. Login bersama /login; /admin/login mengarah ke login ini.
Guard seluruh UI tetap web. Akun eksternal tidak memperoleh panel Filament.

## Keputusan operasional pengguna
- Pengasuh adalah role tersendiri: memantau seluruh kelompok, perkembangan,
  absensi dan laporan tanpa mengubah data operasional.
- Murabbi berada di atas mudabbir: memantau kelompok binaan dan performa
  mudabbir/mahasantri, melihat laporan, serta mereview koreksi mudabbir.
  Murabbi tidak membuka sesi, mengisi absensi atau memfinalkan laporan.
- Mudabbir membagi seluruh mahasantri dalam mabna tugas melalui
  Pembagian Kelompok. Akses absensi tetap terbatas kelompok tugas masing-masing.
  Pemindahan dicatat dalam audit; roster, absensi dan snapshot lama tetap utuh.
- Pekan Minggu–Sabtu, Asia/Jakarta.
- Perubahan biasa dikunci **Sabtu pukul 23:59:00 WIB**; 23:58:59 masih terbuka
  bila kegiatan sudah selesai. Perhitungan terpusat dalam WeeklyCalendar.
- Finalisasi mulai Minggu 00:00 WIB; semua anggota roster harus diisi.
  Belum diisi tidak dianggap ALFA.
- Koreksi setelah final diajukan mudabbir kelompok dengan alasan dan versi absensi.
  Hanya murabbi yang ditugaskan pada kelompok tersebut boleh mereview.
  Persetujuan menerbitkan snapshot versi baru; laporan sebelumnya tetap utuh.
  Penolakan tidak mengubah absensi atau laporan.
- Akun dibuat admin. Registrasi publik ditutup (404). Admin menautkan profil
  Mahasantri/kelompok dan profil Orang Tua/anak melalui resource panel.
  Mahasantri tanpa profil/kelompok aktif diarahkan ke /portal/onboarding.
- Pengajuan izin/sakit per sesi, bukti JPG/PNG/PDF maksimal 5 MB dan GPS opsional.
  Latitude/longitude harus berpasangan dan valid. Lokasi hanya diminta saat
  pengguna memilih tombolnya. Approval sebelum kunci menyinkronkan absensi.
  Review biasa tidak dapat melewati tenggat/finalisasi.
- Batas izin libur mingguan atau rumus poin belum ditentukan; aplikasi tidak
  mengarang keputusan kelayakan libur dari jumlah absensi.

## Akun demo
Password demo: Sipma123! (development saja).

| Role | Email |
|---|---|
| Super Admin — Fakhri | admin@sipma.test |
| Pengasuh — Ustad Fasjud | pengasuh@sipma.test |
| Murabbi — Muhammad Ara'af, S.Ag | murabbi@sipma.test |
| Murabbi — Syahrul Ramdhani, S.Ag | murabbi2@sipma.test |
| Mudabbir — Riyan Hidayat | mudabbir1@sipma.test |
| Mudabbir — Syarif Hidayatullah | mudabbir2@sipma.test |
| Mudabbir — Dandi Muchammad Mudzakir | mudabbir3@sipma.test |
| Mudabbir — Muhammad Haqi An-Nazili | mudabbir4@sipma.test |
| Mudabbir — Muhammad Najwan Cahyadi | mudabbir5@sipma.test |
| Mudabbir — Ahmad Naufal Farhan | mudabbir6@sipma.test |
| Mahasantri | mahasantri1@sipma.test |
| Orang Tua | orangtua1@sipma.test |

Seeder membuat 124 akun, 5 kelompok, 100 mahasantri, dan 10 wali.
Ada dua murabbi. Akun murabbi pertama mempertahankan penugasan baseline;
murabbi kedua belum mendapat penugasan kelompok sampai pembagiannya dikonfirmasi.
Mudabbir 7–10 memakai label belum dikonfirmasi. Nama dummy mahasantri tidak
memakai gelar akademik. Untuk memperbarui identitas database baseline tanpa
mereset password, UUID atau penugasan, jalankan:

```powershell
php artisan db:seed --class=DemoIdentitySeeder
```

Password akun baru demo tetap Sipma123!; pembaruan nama tidak mereset password
akun yang sudah ada. Nama ini merupakan data yang diberikan pengguna; email
@sipma.test tetap alamat demo lokal, bukan email personal yang telah dikonfirmasi.
Role ketua lama digabung ke Mudabbir tanpa mengubah penugasan. Seeder dapat diulang tanpa
mereset password/profil lama. Perubahan peran berikutnya memakai panel admin.
Satu role enum users.role disinkronkan ke
Spatie oleh UserObserver; hak kelompok tetap memerlukan penugasan/pivot.

## Alur dan layar
- /admin: dashboard kelompok tugas, sesi hari ini, kelengkapan pekan, laporan terbit.
- /admin/operational-sessions: buka sesi oleh mudabbir; filter tanggal,
  kelompok/kegiatan; absensi massal oleh mudabbir setelah kegiatan selesai.
  Tanggal otomatis hari ini; kelompok otomatis dipilih bila hanya satu tugas.
  Nomor sesi otomatis bertambah, jam dapat diubah melalui pengaturan lipat.
- /admin/group-assignments: buat kelompok dan pindahkan mahasantri dalam mabna
  tugas oleh mudabbir/admin. Kelompok baru mewarisi mabna dan murabbi
  dari kelompok acuan. Mudabbir pembuat mendapat penugasan kelompok baru.
- /admin/weekly-reports: finalisasi, versi snapshot, pengajuan/review koreksi.
- /admin/submission-reviews: verifikasi izin/sakit oleh mudabbir/admin.
- /admin/violations: input/ubah pelanggaran dan foto privat; arsip hanya admin.
- Master User, Kelompok, Mahasantri, Orang Tua, Kegiatan, Kategori Pelanggaran
  dikelola admin; resource baca mengikuti role dan kelompok tugas.
- /portal/absensi: sesi dan histori pribadi dengan filter periode.
- /portal/pengajuan: form dan status pengajuan.
- /portal/pelanggaran: riwayat pribadi.
- /portal/anak: ringkasan, detail dan filter anak yang terhubung saja.
- /portal/laporan: versi laporan dengan hanya baris diri/anak; tanpa total
  kelompok atau alasan koreksi mahasantri lain.
- /media/{media}: unduhan privat melalui policy, bukan public storage link.

## Struktur tambahan
```text
app/Actions/
  OpenActivitySession, RecordAttendanceBatch, FinalizeWeeklyReport
  RequestAttendanceCorrection, ReviewAttendanceCorrection
  SubmitAbsence, ReviewAbsenceSubmission, SaveViolation
app/Services/
  WeeklyCalendar, AttendanceWorkflow, WeeklyReportService
  MonitoringService, OperationalQuery, DashboardService, DevelopmentService
app/DTOs/DateRange.php
app/Models/{WeeklyPeriod,WeeklyReport,AttendanceCorrection}.php
app/Policies/{ActivitySession,Attendance,WeeklyPeriod,AttendanceCorrection}Policy.php
app/Http/Middleware/EnsureStudentLinked.php
app/Http/Controllers/Portal/{Attendance,Submission,Child,Violation,Report,Media}Controller.php
app/Filament/Pages/{Dashboard,OperationalSessions,WeeklyReports,SubmissionReviews,Violations}.php
app/Filament/Pages/Auth/Login.php
app/Filament/Resources/ParentResource.php
database/migrations/2026_09_26_160000_add_operational_sessions_and_reports.php
config/sipma.php
resources/views/filament/pages/
resources/views/portal/
resources/views/components/status-badge.blade.php
public/css/sipma.css
public/brand/
tests/Feature/SipmaOperationsTest.php
tests/Browser/{seed-visual.php,sipma-smoke.mjs}
phpunit.mysql.xml
docs/{REVIEW_PRIORITAS_SIPMA,DESAIN_DAN_VERIFIKASI}.md
docs/screenshots/
```

UI internal dan eksternal menggunakan service/action/repository bersama.
Policy dan transaksi tetap menjadi sumber izin, bukan tombol UI. Penyimpanan
batch atomik; konflik memakai versi optimistis. Penulisan mengunci periode
sebelum sesi/absensi; finalisasi dan koreksi memakai kunci periode yang sama.
Roster sesi disimpan saat pembukaan. Histori absensi kelompok mengikuti
kelompok sesi saat kejadian, walaupun siswa kemudian dipindahkan.
Pelanggaran mengikuti profil/kelompok siswa saat ini; histori pelanggaran per
kelompok saat kejadian belum memakai snapshot kelompok tersendiri.

Activity Log mencatat perubahan absensi, keputusan pengajuan, koreksi, dan
pelanggaran. Akun domain/internal tidak dapat dihapus mandiri; penolakan memberi
validasi tanpa logout/500. Akun kosong yang boleh dihapus tetap mengikuti Breeze.
WeeklyReport tidak dapat diubah/dihapus melalui instance model.
Rollback migration ditolak bila ada histori operasional yang akan hilang.

## Pengujian
```powershell
php artisan test --compact
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sipma_testing;"
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml --no-progress
npm run build
```

phpunit.xml memakai SQLite memory. phpunit.mysql.xml memaksa database
sipma_testing, memakai kredensial koneksi dari environment; RefreshDatabase
hanya menghapus tabel database khusus pengujian tersebut. Jangan menjalankan
suite MySQL saat server visual sedang memakai database yang sama.
Hasil terbaru: 62 tes / 523 assertion pada SQLite dan MySQL.
Build Vite dan Pint berhasil. Database lokal memiliki 124 akun dan 100 Student;
penambahan pengasuh serta pembaruan identitas mempertahankan data dan password lama.
Pemeriksaan browser dan sumber aset dirinci dalam docs/DESAIN_DAN_VERIFIKASI.md.

Ekspor PDF/Excel, notifikasi, keputusan libur otomatis, suspend akun,
dan jadwal otomatis belum termasuk implementasi ini. Kode master TS, SS, TM,
SM, SI belum diberi kepanjangan resmi.
