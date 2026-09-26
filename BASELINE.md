# SIPMA baseline

Sistem Informasi Pembinaan Mahasantri untuk Mabna Syekh Nawawi,
Ma'had Al-Jami'ah UIN Syarif Hidayatullah Jakarta.

## Stack

Laravel 12, PHP 8.3+, MySQL 8+, Filament 4, Blade, Alpine.js,
Spatie Permission, Activity Log, dan Media Library.

## Menjalankan lokal

Project berada langsung di `C:\laragon\www\mahad-app`.

```powershell
composer install
Copy-Item .env.example .env # hanya jika .env belum ada
php artisan key:generate # hanya untuk instalasi baru
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sipma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
npm install
npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Port default SIPMA adalah `8001` melalui `SERVER_PORT` di `.env`; URL lokal
`http://127.0.0.1:8001`. Vite development memakai port `5174` dan cookie
`sipma_session` untuk mencegah bentrok dengan aplikasi Laravel lain.

`migrate:fresh` menghapus seluruh tabel database yang dikonfigurasi. Gunakan
database development `sipma`, bukan database produksi.

Panel internal: `/admin`. Portal mahasantri: `/portal/absensi` dan
`/portal/pengajuan`. Portal orang tua: `/portal/anak`. Login portal: `/login`.
Keduanya memakai guard `web`.

## Batas baseline

Modul absensi, pengajuan izin/sakit, dan pelanggaran berikut workflow upload/GPS
masih akan dikembangkan pada sprint modul. Fondasi menyediakan skema,
relasi, enum, role, policy, audit, query bersama dan halaman portal skeleton.
Kode master kegiatan TS, SS, TM, SM, SI memakai nama sementara sama dengan kode
karena kepanjangan resmi belum diberikan.

## Akun demo

Semua akun dummy memakai password `Sipma123!` (khusus development).

| Role | Email contoh |
|---|---|
| Super Admin | admin@sipma.test |
| Murabbi | murabbi@sipma.test |
| Mudabbir | mudabbir1@sipma.test |
| Mahasantri | mahasantri1@sipma.test |
| Orang Tua | orangtua1@sipma.test |

Seeder membuat 122 user: 1 admin, 1 murabbi, 10 mudabbir, 100 mahasantri,
dan 10 orang tua. Ada 5 kelompok (20 mahasantri dan 2 mudabbir per kelompok).
Pendaftaran Breeze menghasilkan role mahasantri; admin perlu menghubungkan
akun baru dengan profil Mahasantri dan kelompok melalui panel.

## Struktur aplikasi

```text
app/
  Actions/.gitkeep
  DTOs/.gitkeep
  Helpers/.gitkeep
  Enums/{UserRoleEnum,AttendanceStatusEnum,SubmissionStatusEnum}.php
  Models/{Mabna,User,Group,Student,ParentModel,Activity,ActivitySession}.php
  Models/{Attendance,AbsenceSubmission,ViolationCategory,Violation}.php
  Observers/UserObserver.php
  Traits/AuditsChanges.php
  Repositories/{GroupRepository,StudentRepository}.php
  Services/MonitoringService.php
  Policies/{AttendancePolicy,ViolationPolicy,ParentPolicy,AbsenceSubmissionPolicy}.php
  Filament/Resources/{InternalResource,UserResource,GroupResource,StudentResource}.php
  Filament/Resources/{ActivityResource,ViolationCategoryResource}.php
  Filament/Resources/Pages/{ManageUsers,ManageGroups,ManageStudents}.php
  Filament/Resources/Pages/{ManageActivities,ManageViolationCategories}.php
  Filament/Widgets/GroupStatistics.php
  Http/Controllers/HomeController.php
  Http/Controllers/Portal/{AttendanceController,SubmissionController,ChildController}.php
  Http/Controllers/Auth/ (scaffolding Breeze)
  Http/Controllers/ProfileController.php
  Http/Requests/{Auth/LoginRequest,ProfileUpdateRequest}.php
  Http/Middleware/.gitkeep (alias role/permission berada di bootstrap/app.php)
  Providers/{AppServiceProvider,Filament/AdminPanelProvider}.php
database/
  migrations/ (mabnas, UUID users, groups, pivot mudabbir, students/parents,
               activities, sessions, attendances/submissions, categories, violations,
               tabel Permission, Activity Log, Media Library, cache dan jobs)
  seeders/{DatabaseSeeder,RolePermissionSeeder,SipmaSeeder}.php
  factories/UserFactory.php
resources/
  views/layouts/portal.blade.php
  views/portal/{absensi,pengajuan,anak}.blade.php
  views/{auth,profile,components,layouts}/ (scaffolding Breeze)
  css/app.css
  js/{app,bootstrap}.js
routes/{web,auth,console}.php
config/{permission,activitylog,media-library}.php
tests/Feature/SipmaBaselineTest.php
tests/Feature/Auth/ (tes Breeze)
tests/Feature/{ProfileTest,ExampleTest}.php
.env.example, composer.json, composer.lock, package.json, package-lock.json
vite.config.js, tailwind.config.js, postcss.config.js, phpunit.xml
```

`ParentModel` memakai nama tersebut karena `parent` adalah keyword PHP.
Relasi orang tua-anak memakai pivot `parent_student` untuk mendukung beberapa
anak per orang tua dan beberapa wali per anak.

## Aturan akses dan arsitektur

Panel dan portal memanggil `MonitoringService`, yang memanggil repository
kelompok/mahasantri. Query internal selalu mengikuti penugasan `murabbi_id`
atau pivot mudabbir; query eksternal mengikuti user mahasantri atau pivot wali.
Super admin mengelola kelima resource. Murabbi/mudabbir membaca kelompok
masing-masing dan master kegiatan/kategori; pencatatan modul diatur policy.

Kolom `users.role` adalah satu role enum per akun dan disinkronkan ke pivot
Spatie oleh `UserObserver`; ubah role melalui kolom ini/panel, bukan dengan
menambahkan role Spatie kedua secara langsung. Permission memakai guard `web`.

Model Attendance, AbsenceSubmission dan Violation mencatat perubahan atribut
melalui Spatie Activity Log. Media `evidence` dan `photos` memakai disk private
`local`. Endpoint upload/download terotorisasi dan alur GPS belum dibuat.

## Verifikasi

```powershell
php artisan test --compact
npm run build
php artisan migrate:fresh --seed
```

Tes berjalan dengan SQLite memory agar database MySQL lokal tidak dihapus.
Hasil verifikasi baseline: 32 tes lulus (166 assertion), build Vite berhasil,
migration+seeder MySQL berhasil, dan `/login` pada port 8001 mengembalikan HTTP 200.
Migration dan seeder aplikasi juga diverifikasi langsung pada MySQL.
Tes baseline mencakup jumlah seed, login kelima role, akses portal/panel,
query kelompok, hubungan orang tua-anak, policy, audit UUID, detail Filament,
dan pencegahan eskalasi role melalui registrasi.

Jika VS Code masih menampilkan error Tailwind lama setelah `npm install`,
jalankan perintah editor `Tailwind CSS: Restart Language Server`.

Referensi paket: [Filament 4](https://filamentphp.com/docs/4.x/introduction/installation),
[Breeze](https://github.com/laravel/breeze),
[UUID Spatie Permission](https://spatie.be/docs/laravel-permission/v6/advanced-usage/uuid).
