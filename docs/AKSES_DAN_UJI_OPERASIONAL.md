# Akses dan verifikasi operasional

Keputusan ini menggantikan pembagian tugas lama yang mengizinkan murabbi membuka
sesi atau memfinalkan laporan.

| Peran | Tugas |
|---|---|
| Super Admin — Fakhri | Kelola akun/master dan penugasan; lihat semua laporan; finalisasi |
| Pengasuh — Ustad Fasjud | Pantau semua kelompok, performa mudabbir, mahasantri, absensi dan laporan |
| Murabbi | Pantau kelompok binaan dan laporan; review koreksi mudabbir |
| Mudabbir | Buka sesi, isi absensi, finalisasi, ajukan koreksi, review izin/sakit, input pelanggaran, pembagian kelompok |

Role Ketua Mudabbir dihapus. Migration menggabungkan akun dan penugasannya ke
Mudabbir tanpa mengganti UUID, password, atau data kegiatan.

Pembagian kelompok oleh mudabbir mencakup seluruh mahasantri dalam mabna tugas.
Kegiatan operasional lainnya tetap mengikuti kelompok tugas. Pemindahan tidak
menulis ulang roster sesi, absensi, atau laporan yang sudah diterbitkan.
Data kelompok asal diperiksa ulang dalam transaksi; perubahan bersamaan ditolak
agar pilihan lama tidak menimpa pembagian terbaru. Setiap pemindahan diaudit.

Form buka sesi cukup memilih kegiatan, kelompok dan tanggal. Kelompok tunggal
serta tanggal hari ini terisi otomatis. Nomor sesi berulang diatur server dalam
transaksi; jam 18.00–19.00 WIB tetap terlihat dan bisa diubah melalui pengaturan.

Pengujian otomatis mencakup login dan pengalihan setiap role; akses lintas role,
kelompok dan mabna; pembagian melalui Livewire; penolakan perubahan bersamaan;
riwayat roster; batas Sabtu 23.59 WIB dan finalisasi mulai Minggu; koreksi mudabbir
dengan review murabbi; izin/sakit, bukti privat, audit dan snapshot laporan.
Hasil terkini serta perintah menjalankan tes terdapat dalam BASELINE.md.

Uji penerimaan berikutnya menggunakan data nyata: kepanjangan kode kegiatan
TS/SS/TM/SM/SI, jadwal kegiatan, penugasan murabbi kedua, nama mudabbir 7–10,
serta aturan kelayakan libur. Keputusan tersebut belum diberikan, sehingga
aplikasi belum menghitung kelayakan libur otomatis.
