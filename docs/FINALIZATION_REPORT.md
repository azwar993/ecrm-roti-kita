# Laporan Finalisasi CRM RotiKita

## Ringkasan

Implementasi Tahap 1 artikel/payment ledger dipertahankan dan direview. Ticketing awal customer/admin, empat artikel bakery seed idempotent dengan isi lengkap, KPI admin awal, footer help link, social URL validation, CSS View Transitions, dan global page loader storefront/dashboard/auth ditambahkan. Semua database tests memakai SQLite memory. MySQL aktif tidak diakses.

## File baru/diubah

**Migration:**
- `2026_11_03_000001_add_metadata_to_articles_table.php`
- `2026_11_03_000002_add_idempotency_metadata_to_payment_ledger_entries_table.php`
- `2026_11_03_120000_create_support_ticket_tables.php`

**Implementasi:** article/payment models, admin article/order/inbox/dashboard controllers, customer/admin ticket controllers, ticket models, notification, ArticleSeeder, ticket/customer/admin Blade views, storefront metadata/social partials, layout navigation/footer and `public/css/page-transitions.css`.

**Test:** `ArticlePublishingTest`, `PaymentLedgerRevenueTest`, `SupportTicketTest`.

**Dokumentasi:** `CRM_FINALIZATION_PROGRESS.md`, `CRM_KPI_DEFINITIONS.md`, `CRM_PPT_ALIGNMENT.md`, this report.

## Migration dan status database

- Artikel metadata: category (nullable string 80), seo_title (nullable 180), seo_description (nullable 320); SQLite test mencakup up/down/up.
- Ledger metadata: reference, unique nullable idempotency_key, description; tidak menambah order_id karena payment wajib terhubung ke order dan payment_id ledger menyediakan traversal; SQLite test mencakup up/down/up.
- Ticket: support_tickets, ticket_messages, ticket_attachments; FK ke users/orders/ticket parent, unique ticket number, index status/customer/message lookup. SQLite test menjalankan seluruh migration.
- `UNIQUE(payment_id, entry_type)` dipertahankan karena source hanya mendukung satu refund penuh setelah status paid, bukan partial refund. Key deterministik melindungi retry event yang sama.
- Tidak ada migration yang diterapkan ke MySQL aktif. Status migration MySQL aktif tidak diperiksa.

## Ticketing

Route customer dibatasi `auth, role:customer`; admin dibatasi `auth, role:admin`. Customer query di-scope ke pemilik ticket. File disimpan di disk private `local`, tipe JPG/PNG/WEBP/PDF, maksimum 5 MB, akses download memeriksa role/ownership. Ada rate limit create/reply, status dan priority allowlist, admin notification untuk tiket/balasan customer, customer notification untuk balasan admin/status.

Belum ada tabel audit status-history atau request idempotency token untuk deduplikasi submit tiket saat retry jaringan. Customer menerima status terbaru dan notifikasi, namun histori status tidak ditampilkan sebagai event terpisah. SLA response/resolve tidak dihitung.

## Artikel dan storefront

CRUD existing dipertahankan dengan category/SEO metadata. Draft dan artikel terjadwal tetap dikecualikan oleh query publik. `ArticleSeeder` menambah empat artikel edukatif via `firstOrCreate` dan tidak menimpa edit/unpublish admin. Konten tidak mencantumkan klaim medis, nutrisi, sertifikasi, atau fakta khusus bisnis. Cover tidak disediakan karena aset cover terverifikasi tidak ditemukan. Seeder tidak dijalankan terhadap MySQL aktif.

Footer storefront memakai konfigurasi `rotikita-contact`; email dirender bila valid, WhatsApp memakai digits-only wa.me, social hanya HTTPS pada host resmi yang diizinkan. Link help mengarah ke customer tickets; guest akan melalui middleware login. SMTP atau integrasi DM tidak diklaim.

Transisi memakai CSS View Transitions API progressive enhancement, fallback normal browser, 180–220 ms, dan reduced-motion. Belum ada browser visual test. Plus Jakarta Sans tidak ditemukan; Instrument Sans lokal dipertahankan.

## KPI aktual

Dashboard menampilkan buyer unik paid bulan berjalan, repeat purchase rate (buyer dengan ≥2 order paid dalam bulan ÷ buyer unik paid bulan × 100), average paid order value (jumlah amount payment paid ÷ jumlah order paid dalam bulan), tiket aktif, rating feedback existing, dan KPI RevenueService existing. AOV mengecualikan payment berstatus refunded; net revenue tetap dihitung oleh RevenueService. Periode/limit data dan gap tercatat di `CRM_KPI_DEFINITIONS.md`. Formula KPI baru belum mempunyai test kasus numerik khusus.

## Kesesuaian materi CRM

Matriks delapan fondasi, tujuan, implementasi, risiko, dan evaluasi F&B berada di `CRM_PPT_ALIGNMENT.md`. File PPT langsung tidak tersedia saat pemeriksaan. Segmentasi/personalisasi, promo conversion attribution, customer retention cohort, customer satisfaction survey, ticket status audit, dan SLA masih gap. Status ditandai sebagian/belum, bukan seluruhnya terpenuhi.

## Test, lint, build

- PHPUnit via PHP 8.3.36 dengan `php_sqlite3.dll` dan `php_pdo_sqlite.dll` diaktifkan hanya untuk proses: **64 tests, 814 assertions, lulus**.
- SQLite isolation berasal dari `phpunit.xml`: in-memory, `DB_URL` kosong, cache/session array, queue sync.
- Pint dijalankan pada daftar file PHP eksplisit. `--dirty` tidak tersedia karena workspace tanpa metadata Git.
- PHP lint lulus pada file PHP yang berubah pada Tahap 1; untuk semua file tahap ticketing, Pint berjalan dan PHPUnit berhasil memuat source files.
- Vite build berhasil dengan `npm.cmd run build`; Vite memberi informational warning opsional `fontaine` tidak terpasang. Tidak ada dependency diubah.
- Global loader JavaScript diuji dengan `node --check` dan 4 test Node bawaan; seluruhnya lulus.
- Automated route/view assertions lulus pada halaman ticket customer/admin dan artikel. Browser visual testing tidak dilakukan.

## Keamanan database aktif

MySQL aktif tidak diakses; tidak ada migration atau seeder production dijalankan. `.env`, koneksi database aktif, transaksi, produk, customer, order, payment, refund, dan QRIS tidak diubah. Migration tetap menunggu review/persetujuan pemilik proyek sebelum aktivasi.

## Finalisasi artikel dan global page loading (4 November 2026)

**Konten artikel benar-benar dibuat lengkap** di `ArticleSeeder`, dengan konten, category, slug, excerpt, SEO title dan description. Seeder idempotent memakai `firstOrCreate`, mempublikasikan seed awal dengan `published_at` yang valid, dan tidak menimpa edit admin. **Empat artikel tersedia pada SQLite testing** dan route daftar/detail publik lulus; test memastikan judul, body non-placeholder, metadata, slug, status/tanggal, draft tidak tampak, halaman homepage maksimal tiga, dan seed ulang tidak membuat duplikat atau mengembalikan edit admin. Detail menampilkan body sebagai heading/paragraf/daftar yang di-escape, breadcrumb, tanggal, excerpt, fallback brand untuk cover yang tidak ada, dan artikel terkait. Tidak ada foto produk yang dibuat atau diklaim.

**Loader benar-benar terlihat saat perpindahan halaman** melalui overlay full-screen RotiKita dengan monogram R, status, titik animasi, dan progress indeterminate. Ia terpasang pada storefront, dashboard admin/customer, serta guest login/register. Klik internal same-origin dan submit form halaman memicu loader; external/anchor/download/target baru/modifier/AJAX/cancelled tidak memicu. Halaman tujuan menyembunyikan overlay saat `load`, dengan flag session kedaluwarsa, fail-safe, dan penanganan Back/Forward/BFCache.

**Loader bekerja pada form** melalui event submit setelah validasi browser dan tidak mengubah atribut form atau input; browser tidak mengirim event submit ketika constraint validation gagal. **Reduced motion dan fallback telah diuji** lewat test JavaScript/CSS: animasi dinonaktifkan tetapi aria-live status tetap ada, konten normal tetap tersedia tanpa JS, dan fail-safe menghapus overlay yang tersisa. Node syntax check dan 4 test JavaScript lulus; Vite production build lulus. Pengujian visual browser tidak dapat dijalankan karena browser automation tidak terpasang, sehingga keberhasilan tampilan visual tidak diklaim. Instruksi uji manual tersedia di `CRM_FINALIZATION_PROGRESS.md`.

Tidak ada database aktif yang diakses. Feature test menggunakan SQLite `:memory:`; tidak ada migration/seeder production, perubahan `.env`, QRIS, order, payment, refund, atau produk.
