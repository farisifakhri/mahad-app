# Review dan prioritas SIPMA

> Catatan historis: review di bawah mendokumentasikan baseline sebelum perubahan.
> Pengguna kemudian menyetujui implementasi dengan tenggat Sabtu 23.59 WIB,
> pekan Minggu–Sabtu, finalisasi mulai Minggu, revisi ketua direview murabbi,
> akun dibuat admin, dan pengajuan per sesi dengan bukti/GPS opsional.
> Penyebutan Kamis/Jumat dan registrasi di bawah telah digantikan keputusan ini.
> Tahap 0–6 kini diimplementasikan; GPS opsional dan unduhan privat juga tersedia.
> Seeder kini idempoten. Dokumentasi perilaku terkini ada di BASELINE.md.

Tanggal: 26 September 2026. Repository terverifikasi: `farisifakhri/mahad-app`.
Review memakai kode lokal baseline dan dua dokumen pengguna. Dokumen sumber
adalah spesifikasi/usulan; keputusan terbuka tidak dianggap sebagai instruksi
implementasi yang sudah disetujui. Pada pekerjaan ini kode aplikasi tidak diubah.
Remote lokal diperbarui mengikuti rename repository.

## Bukti dan batas review

- Pemeriksaan statis: policy, repository, service, route, model, migration,
  seeder, portal, dan cakupan tes baseline.
- Dua probe sementara pada SQLite memory lulus dengan 6 assertion:
  1. `DELETE /profile` untuk akun dengan profil Student menghasilkan HTTP 500;
     user masih tersimpan, tetapi guard sudah logout.
  2. Pencatat yang hanya ditugaskan di B mendapat izin create/update untuk
     siswa B, sementara record memakai sesi A; insert diterima database.
- Probe memeriksa perilaku bermasalah yang ada, bukan membuktikan perilaku benar.
  File probe dihapus setelah review. Database MySQL tidak diubah dan suite
  baseline lengkap tidak dijalankan ulang pada review ini.
- Endpoint tulis sesi/absensi belum tersedia. Temuan silang kelompok merupakan
  kekurangan fondasi/domain yang harus ditutup sebelum endpoint tersebut aktif,
  bukan klaim bahwa API publik absensi sekarang dapat dieksploitasi.

## Temuan menurut urgensi

### 1. P0 — penghapusan akun terhubung gagal pada fitur yang sudah tersedia

`app/Http/Controllers/ProfileController.php:43` memanggil logout sebelum
delete. Relasi Student membatasi penghapusan user, sehingga request valid dapat
menghasilkan HTTP 500. Tes ProfileTest sebelumnya memakai user tanpa profil.

Rekomendasi tahap awal: blokir hapus mandiri untuk akun domain/penugasan,
beri pesan yang jelas, dan jangan logout bila penghapusan ditolak. Kebijakan
nonaktif/suspend adalah keputusan lanjutan; bila dipilih, harus berlaku pada
login baru maupun sesi yang sudah aktif, termasuk panel dan portal.

Koreksi atas dokumen sumber: `groups.murabbi_id` memakai `nullOnDelete()`,
bukan FK restriktif. Akun murabbi tanpa relasi restriktif lain bisa terhapus
dan kelompok kehilangan penanggung jawab. Pemeriksaan harus meliputi profil,
penugasan dan referensi histori, bukan hanya menangkap exception FK.

### 2. P0 sebelum absensi aktif — policy belum mengikat siswa ke sesi

`AttendancePolicy::create` menerima siswa, tanpa target sesi. `update` hanya
memeriksa akses siswa/role. FK dan unique constraint tidak memeriksa kecocokan
kelompok siswa dengan sesi. Probe mengonfirmasi bahwa kombinasi silang diterima.

Setiap aksi tulis harus mengotorisasi sesi dan siswa, memastikan kelompok sama,
memeriksa penugasan, sesi dibuka/selesai sesuai aturan, serta periode edit.
`recorded_by` ditetapkan server dari user login. Untuk batch, seluruh item harus
divalidasi; tetapkan apakah kegagalan satu item membatalkan seluruh batch.

### 3. P0 sebelum absensi aktif — otorisasi baseline berbeda dari target baru

Role ketua belum ada. Policy absensi masih memberi super admin izin create/update
dan belum memeriksa tenggat/finalisasi. Ini sesuai baseline lama tetapi tidak
memenuhi target dokumen baru. Bypass admin pada absensi tidak boleh terbawa
otomatis ke fitur operasional. Hak admin pada pelanggaran/pengajuan harus
ditinjau terpisah; jangan menghapus semua haknya tanpa keputusan produk.

Role ketua harus diterapkan bersama pivot penugasan, repository, middleware/
panel, redirect, pilihan anggota kelompok, permission dan seluruh policy terkait.
Permission koreksi final boleh disiapkan tetapi fitur tersebut belum diaktifkan
pada tahap role saja.

### 4. P1 — registrasi menghasilkan akun tanpa profil/kelompok

RegisteredUserController membuat user dan login, tanpa Student. Query portal
menghasilkan daftar kosong sehingga kondisi belum ditautkan terlihat sama
dengan belum ada absensi. Pisahkan keadaan onboarding dari keadaan kosong.
Pilihan pendaftaran terbuka versus akun dibuat admin tetap keputusan pengguna.

### 5. P1 — kontrak sesi, waktu dan histori harus mendahului form tulis

Sesi belum memiliki pembuka/status/waktu pembukaan; jam boleh kosong dan kegiatan
dibatasi sekali per tanggal/kelompok. Gunakan migration baru dan tetapkan kunci
pengganti sebelum mendukung sesi berulang. Jangan menghapus unique constraint
tanpa pengaman pengiriman ulang.

MonitoringService mengambil histori lewat kelompok Student saat ini. Jika siswa
dipindahkan A ke B, histori sesi A akan masuk cakupan siswa B. Tentukan apakah
histori mengikuti kelompok saat kejadian, profil siswa saat ini, atau keduanya
dengan aturan baca berbeda. Rekap kelompok mingguan sebaiknya memiliki identitas
kelompok/anggota pada periode kejadian yang dapat ditelusuri; ini rekomendasi
desain, belum keputusan organisasi.

### 6. P1 sebelum laporan final — audit model belum merupakan versi laporan

Log perubahan sudah tersedia tetapi tidak menyimpan snapshot laporan terbit.
Finalisasi dan pencatatan/koreksi harus menggunakan transaksi dan penguncian
periode yang konsisten agar tidak ada perubahan terselip setelah snapshot.
Unique attendance mencegah record ganda, tetapi tidak sendiri mencegah dua edit
saling menimpa: pilih deteksi versi/conflict untuk perubahan bersamaan.

### 7. P1/P2 — pengajuan, pelanggaran, dan rekap periode masih modul lanjutan

Pengajuan belum memiliki aksi review/sinkronisasi absensi. Policy PENDING saja
belum mencegah dua review bersamaan. Approval harus atomik, berpengaruh ke sesi
yang tepat, dan mengikuti aturan penguncian periode. Kebijakan pengajuan yang
disetujui setelah laporan final perlu disepakati agar tidak menjadi bypass.

File evidence/photos sudah diarahkan ke disk privat; endpoint upload/download
terotorisasi belum tersedia. Penggunaan GPS, bukti wajib, periode pengajuan,
akses catatan sensitif dan rumus poin harus mengikuti keputusan organisasi.
Statistik saat ini adalah jumlah catatan sepanjang masa, bukan persentase
kehadiran atau rekap mingguan; FE perlu label dan filter periode yang tepat.

## Urutan pekerjaan yang direkomendasikan

| Tahap | Lingkup | Kriteria selesai | Ketergantungan |
|---|---|---|---|
| 0 | Hapus akun aman dan keadaan onboarding | Akun terhubung ditolak dengan pesan, tanpa 500/logout; akun belum ditautkan mendapat penjelasan | Kebijakan minimum akun |
| 1 | Role ketua mudabbir | Panel/policy/query bekerja untuk kelompok tugas saja; cabut pivot mencabut akses; promosi demo mempertahankan jumlah user | Baseline |
| 2 | Pembukaan sesi aktual | Murabbi/ketua bisa membuka sesi kelompoknya; mudabbir/admin ditolak sesuai matriks; waktu dan duplikasi tervalidasi | Tahap 1; aturan sesi/admin |
| 3 | Absensi operasional dan kunci biasa | Batch aman, sesi-siswa satu kelompok, pelaku dari server, audit, konflik/duplikasi, tenggat terpusat, portal membaca hasil | Tahap 2; jam Kamis dan periode laporan |
| 4 | Laporan final dan koreksi ketua | Snapshot awal tetap dapat dibuka; alasan wajib; koreksi kelompok sendiri menghasilkan versi; finalisasi dan koreksi atomik | Tahap 3; aturan penerbitan revisi |
| 5 | Pengajuan izin/sakit | Review sekali, atomik, bukti privat, absensi dan periode final tetap konsisten | Tahap 3/4; aturan pengajuan |
| 6 | Pelanggaran dan rekap lanjutan | Input/ubah/soft delete terotorisasi, bukti privat, audit, filter periode dan angka jelas | Aturan pembinaan |
| 7 | Ekspor/notifikasi/dashboard lanjutan/GPS | Memakai data, versi laporan dan otorisasi yang sudah stabil | Kebutuhan operasional terkonfirmasi |

Tahap 0 dan 1 dapat menjadi dua perubahan kecil. Tahap 3 harus memiliki aturan
tenggat sebelum diaktifkan; jangan menunda semua logika penguncian hingga tahap 4.
Dokumen sumber dipakai sebagai urutan domain, bukan kewajiban membuat PR/push
tanpa instruksi publikasi berikutnya.

## Keputusan yang perlu dicatat sebelum tahap terkait

1. Sebelum tahap 0: pendaftaran terbuka atau admin; kebijakan hapus/nonaktif.
2. Sebelum tahap 2: sesi berulang; kapan boleh dibuka/susulan; apakah admin boleh
   membuka sesi; jumlah ketua tetap tidak dipaksakan sebelum dikonfirmasi.
3. Sebelum tahap 3: jam kunci Kamis; rentang pekan; kapan absensi boleh dicatat;
   aturan siswa berpindah kelompok; arti IZIN/SAKIT sebelum workflow approval aktif.
4. Sebelum tahap 4: siapa boleh memperbaiki setelah kunci Kamis tetapi sebelum
   laporan Jumat final; revisi ketua langsung terbit atau direview murabbi.
5. Sebelum tahap 5: izin/sakit per sesi atau rentang; bukti/GPS wajib atau opsional;
   dampak approval yang terjadi setelah periode terkunci/final.

## Kesiapan FE dan gerbang pengujian

Desain dan kerangka FE bisa dimulai sekarang: onboarding, dashboard, daftar sesi,
detail absensi, portal riwayat, dan laporan dengan data contoh. Form tulis baru
disambungkan setelah aksi server dan izin `can_edit`/`lock_reason` tersedia.
Kode FE tidak menghitung tenggat atau menentukan izin aksi sebagai sumber utama.

Uji wajib saat fitur terkait dibuat: request lintas kelompok, pencabutan pivot,
akun terhubung, sesi belum dibuka/belum selesai, siswa-sesi berbeda kelompok,
pengiriman ulang, edit bersamaan, batas Kamis tepat sebelum/sesudah, lintas tahun,
snapshot finalisasi bersamaan dengan edit, koreksi tanpa alasan, penolakan admin,
approval setelah final, serta unduhan bukti oleh pihak lain.

Upgrade database berisi data harus memakai migration baru. RolePermissionSeeder
memakai findOrCreate/sync, tetapi SipmaSeeder memakai create: jangan menjalankan
DatabaseSeeder penuh ulang pada data baseline. Pakai pembaruan role/permission
terarah dan promosi akun yang sudah terhubung, lalu verifikasi idempotensi.
Uji migrasi upgrade dan transaksi/constraint pada database MySQL khusus pengujian;
SQLite memory dipakai untuk tes cepat, bukan satu-satunya bukti perilaku MySQL.
