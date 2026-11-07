# Pemetaan CRM RotiKita terhadap materi kuliah

File `PPT SIM 9_CRM.pptx` tidak ditemukan pada inventaris workspace yang diperiksa. Pemetaan ini memakai daftar konsep yang diberikan pemilik proyek; uraian detail slide yang tidak tersedia tidak diasumsikan.

| Konsep CRM | Makna dalam acuan | Implementasi RotiKita dan bukti | Status | Gap / rekomendasi |
|---|---|---|---|---|
| Vision | Arah hubungan pelanggan | Tagline dan storefront tersedia; belum ada visi CRM tertulis | Sebagian | Tetapkan visi layanan dan target terukur |
| Strategy | Cara mencapai tujuan hubungan pelanggan | Orders, promotions, feedback tersedia | Sebagian | Hubungkan strategi segmentasi dan retensi dengan KPI |
| Customer Experience | Pengalaman pelanggan | Storefront, checkout, tracking order, feedback, ticketing | Sebagian | Uji pengalaman lintas perangkat dan ukur kepuasan setelah ticketing aktif |
| Collaboration | Kolaborasi tim | Admin inbox database notifications dan dashboard operasional | Sebagian | Belum ada assignment ticket atau SLA lintas tim |
| Process | Proses layanan | Order/payment workflow, feedback follow-up, status tiket | Sebagian | Ticket status history dan panduan operasional perlu diperkuat |
| Information | Data untuk memahami pelanggan | Customer profiles, orders, feedback, consent marketing | Sebagian | Segmentasi dan kualitas data belum lengkap |
| Technology | Sistem pendukung CRM | Laravel, MySQL app, Blade, database notifications, private storage | Terpenuhi sebagian | Migration tiket baru belum diterapkan ke database aktif |
| Metrics | Pengukuran hasil | Dashboard total customer, revenue, paid orders, repeat purchase, rating, tiket | Sebagian | Promo conversion dan cohort retention belum tersedia |
| Kepuasan dan loyalitas | Tujuan CRM | Feedback dan repeat purchase KPI tersedia | Sebagian | Belum ada CSAT terstruktur setelah order/ticket |
| Efisiensi operasional | Tujuan CRM | Dashboard, inbox, order workflow, ticket queue | Sebagian | Belum ada waktu respons/penyelesaian tervalidasi |
| Pendapatan/profitabilitas | Tujuan CRM | RevenueService menghitung paid dikurangi refund | Sebagian | Profit membutuhkan biaya pokok yang tervalidasi |
| Pemahaman dan personalisasi | Tujuan CRM | Customer/order history dan consent ada | Belum | Segmentasi, preferensi, dan campaign targeting belum tersedia |
| Planning → evaluation | Implementasi CRM | Fitur berjalan bertahap dan tes otomatis | Sebagian | Training pengguna dan siklus evaluasi operasional belum dibuktikan |
| Risiko adopsi/data/biaya/integrasi | Risiko implementasi | Role checks, validasi, migration additive, tests | Sebagian | Pelatihan, change management, dan prosedur insiden perlu disiapkan |

## Evaluasi F&B

- Masalah: pencatatan pesanan/pembayaran dan keluhan membutuhkan keterlacakan serta tindak lanjut.
- Strategi: satu riwayat customer, status order/payment, feedback, dan pusat tiket.
- Teknologi: Laravel Blade, Eloquent, database notifications, file private, MySQL untuk aplikasi; SQLite memory untuk test.
- Hasil yang dapat dibuktikan pada kode: alur transaksi dan ledger diuji; ticketing diuji pada SQLite setelah implementasi. Hasil operasional/penjualan aktual tidak diklaim.
- Temuan: segmentasi, retention cohort, conversion attribution promo, dan SLA support masih gap.

Status implementasi harus ditinjau kembali setelah migration tiket disetujui dan diterapkan oleh pemilik proyek.
