# CRM Finalization Progress — Stage 1

## Artikel

Migration additive `2026_11_03_000001_add_metadata_to_articles_table.php` menambah `category` (nullable string, 80 karakter), `seo_title` (nullable string, 180), dan `seo_description` (nullable string, 320). Migration lama tidak diubah. `down()` hanya menghapus kolom yang diperkenalkan migration ini. Model, controller admin, dan form artikel kini menerima metadata tersebut. Saat edit, metadata yang tidak dikirim dipertahankan. Kategori dibatasi pada Tips Roti, Edukasi Bakery, Tips Dapur, dan Inspirasi.

Scope `published` dan pemeriksaan detail publik yang sudah ada tetap dipakai. Test mencakup draft tersembunyi, artikel published, tanggal publikasi masa depan, serta persistensi dan pelestarian metadata.

## Payment ledger

Migration additive `2026_11_03_000002_add_idempotency_metadata_to_payment_ledger_entries_table.php` menambah `reference`, `idempotency_key`, dan `description`. Ketiganya nullable agar baris ledger lama tetap kompatibel; idempotency key mempunyai unique index. Tidak ada backfill, perubahan baris lama, atau perubahan RevenueService.

Kolom `order_id` tidak ditambahkan: `payments.order_id` wajib dan unique, dan ledger sudah mempunyai relasi payment. Order dapat ditelusuri tanpa menyimpan salinan relasi yang bisa menyimpang.

Source controller hanya menerima refund ketika status payment `paid`, lalu mengubahnya ke `refunded`; tidak ada partial refund. Karena itu unique `(payment_id, entry_type)` dipertahankan, sehingga setiap payment maksimal memiliki satu event paid dan satu refund. Approval/refund berjalan di transaksi dan menggunakan `firstOrCreate`; key deterministik `payment:{payment_id}:paid` atau `payment:{payment_id}:refund` melengkapi idempotensi. Refund berulang ditolak oleh pemeriksaan status sebelum ledger dicatat.

Reference memakai `payments.reference_number` yang sudah tersedia, tanpa mengarang referensi eksternal. Description menjelaskan event dan nomor order lokal. RevenueService tidak diubah; data legacy tanpa ledger tetap dihitung lewat fallback-nya.

## Verifikasi

- PHPUnit config memaksa `APP_ENV=testing`, SQLite `:memory:`, `DB_URL` kosong, cache/session array, dan queue sync. Tidak ada koneksi MySQL yang digunakan.
- Driver SQLite DLL tersedia, tetapi tidak aktif di PHP ini secara default. Test dijalankan dengan mengaktifkan DLL hanya pada proses PHP (`-d extension=php_sqlite3.dll -d extension=php_pdo_sqlite.dll`); `php.ini` tidak diubah.
- Full suite tahap sebelumnya: 64 test dan 746 assertions lulus. Full suite final: 64 test dan 814 assertions lulus.
- Pint dijalankan dengan PHP 8.3.36 pada file yang berubah. `--dirty` tidak dapat dipakai karena workspace tidak memiliki Git metadata.
- PHP lint lulus untuk file PHP yang berubah.
- Migration metadata baru diuji fresh melalui RefreshDatabase dan eksplisit down/up pada SQLite.

## File yang berubah

- `database/migrations/2026_11_03_000001_add_metadata_to_articles_table.php`
- `database/migrations/2026_11_03_000002_add_idempotency_metadata_to_payment_ledger_entries_table.php`
- `app/Models/Article.php`
- `app/Models/PaymentLedgerEntry.php`
- `app/Http/Controllers/Admin/ArticleController.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `resources/views/admin/article-form.blade.php`
- `tests/Feature/ArticlePublishingTest.php`
- `tests/Feature/PaymentLedgerRevenueTest.php`
- `docs/CRM_FINALIZATION_PROGRESS.md`

## Pekerjaan lanjutan

Ticketing awal ditambahkan dengan migration `2026_11_03_120000_create_support_ticket_tables.php`, model `SupportTicket`, `TicketMessage`, dan `TicketAttachment`, route admin/customer yang memakai middleware role, daftar/search/filter, reply, status/prioritas, notifikasi database, ownership checks, rate limiting, serta file pada private disk `local` yang hanya diunduh melalui route berotorisasi. Test mencakup create/ownership, reply/status admin, akses lampiran, kategori invalid, dan SVG ditolak.

Empat artikel edukatif disiapkan lewat `ArticleSeeder` memakai `firstOrCreate` per slug sehingga tidak menimpa edit atau status artikel existing. Cover tidak disediakan karena tidak ada aset yang diverifikasi. SEO title/description dipakai pada halaman detail.

Dashboard admin menambahkan buyer unik, repeat purchase rate bulanan, average paid order value, dan tiket terbuka; KPI rating sudah berbasis field rating feedback yang tersedia. Definisi ada di `docs/CRM_KPI_DEFINITIONS.md`. Page transition memakai CSS View Transitions dengan fallback normal dan reduced-motion. Footer mendapat link Pusat Bantuan dan social URL dibatasi ke HTTPS serta host resmi.

## Batas dan blocker

Migration belum dan tidak boleh diterapkan pada MySQL aktif. Tidak ada seeder atau data artikel yang dijalankan/dimasukkan ke database aktif. Tidak ada perubahan pada order, payment, refund, produk, atau QRIS. Setelah review pemilik, migration artikel, ledger, dan ticket masih perlu diterapkan di lingkungan tujuan. Ticket belum memiliki tabel audit status-history atau token idempotency request untuk mencegah duplikasi akibat retry jaringan. Waktu respons/penyelesaian SLA belum dihitung.

## Finalisasi artikel dan global page loading (4 November 2026)

### Artikel

| Judul | Slug | Kategori | Jumlah isi | SEO |
| --- | --- | --- | --- | --- |
| Cara Menyimpan Roti agar Tetap Lembut | `cara-menyimpan-roti-agar-tetap-lembut` | Tips Bakery | 12 paragraf, 6 heading | SEO title dan description terisi |
| Mengenal Perbedaan Roti Manis, Roti Tawar, dan Pastry | `mengenal-perbedaan-roti-manis-roti-tawar-dan-pastry` | Edukasi Bakery | 16 paragraf, 5 heading, daftar 3 butir | SEO title dan description terisi |
| Tips Menghangatkan Roti di Rumah | `tips-menghangatkan-roti-di-rumah` | Tips Bakery | 19 paragraf, 6 heading | SEO title dan description terisi |
| Inspirasi Memilih Camilan Bakery untuk Berbagai Momen | `inspirasi-memilih-camilan-bakery-untuk-berbagai-momen` | Inspirasi | 13 paragraf, 7 heading | SEO title dan description terisi |

`ArticleSeeder` berisi konten lengkap, menggunakan slug unik dan `firstOrCreate`; status seed awal published dengan `published_at` kemarin. Seeder tidak menghapus atau memperbarui artikel existing, sehingga edit admin tetap utuh. Category, excerpt, SEO title/description, dan body terisi. Detail publik menampilkan breadcrumb, kategori, judul/tanggal/excerpt, body berheading dengan escaping, cover valid atau fallback brand, artikel terkait, dan link kembali. Route detail membuka keempat artikel hasil seed; halaman daftar memfilter published dan homepage membatasi tiga artikel terbaru. Hasil SQLite: `ArticlePublishingTest` lulus, 5 test/116 assertion, termasuk dua kali seed tanpa duplikasi dan edit admin tetap dipertahankan. Draft dan jadwal masa depan tidak terlihat publik.

### Global loading

- File: `public/css/page-transitions.css`, `public/js/rotikita-page-loader.js`, `resources/views/partials/page-loader.blade.php`.
- Layout: `layouts.storefront`, `layouts.app` (dashboard admin/customer), dan `layouts.guest` (login/register).
- Same-origin internal links dan submit form normal yang lolos validasi memasang flag session sementara dan menampilkan overlay RotiKita. Overlay disembunyikan pada `load`; fail-safe 15 detik, flag kedaluwarsa, dan halaman tujuan yang gagal siap tidak meninggalkan overlay permanen.
- Back/forward tidak menghidupkan flag stale; `pageshow` membersihkan overlay ketika dokumen dipulihkan dari BFCache.
- External, target baru, mailto/tel, download, anchor halaman yang sama, modifier/middle click, dan navigasi yang dibatalkan tidak memicu atau mempertahankan loader. Form AJAX ditandai `data-ajax`, `data-remote`, atau `wire:submit` dilewati. Submit event normal baru terjadi setelah validasi browser; method/action/enctype/input tidak diubah.
- CSS View Transitions yang lama tetap berfungsi sebagai page transition terpisah. `prefers-reduced-motion` meniadakan gerak tetapi status `aria-live="polite"` tetap tersedia. Overlay tersembunyi tanpa class pending; inline fail-safe juga menghapus pending jika JS utama gagal.
- Node test tanpa dependency baru: 4 test lulus untuk navigasi link, pengecualian link, form normal/AJAX/external/cancelled, hide setelah siap, aria status, dan reduced motion. Node syntax check lulus. PHPUnit full suite SQLite in-memory: 64 test/814 assertion lulus. Build Vite lulus.
- Browser automation tidak tersedia pada dependency proyek; visual browser test tidak diklaim. Uji manual: homepage → artikel → homepage; homepage → login → dashboard; dashboard → daftar tiket → detail tiket; katalog customer → cart → checkout; lakukan Back/Forward; uji form invalid, link external, anchor, Ctrl/Cmd-click, dan reduced-motion OS. Pastikan overlay tampak pada navigasi halaman normal dan menghilang setelah halaman siap.
