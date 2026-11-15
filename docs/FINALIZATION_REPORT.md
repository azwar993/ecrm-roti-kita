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

CRUD existing dipertahankan dengan category/SEO metadata. Draft dan artikel terjadwal tetap dikecualikan oleh query publik. `ArticleSeeder` menambah empat artikel edukatif via `firstOrCreate` dan tidak menimpa edit/unpublish admin. Konten tidak mencantumkan klaim medis, nutrisi, sertifikasi, atau fakta khusus bisnis. Empat cover JPEG terverifikasi dipublikasikan ke public disk dan dipetakan berdasarkan slug. Seeder tidak dijalankan terhadap MySQL aktif.

Audit lanjutan 2026-11-07: `/artikel` terbukti dapat dibuka tetapi masih empty
state karena data artikel belum dideploy. Admin index tidak lagi memunculkan 503
ketika tabel belum tersedia; halaman menampilkan peringatan migration dan
menonaktifkan penyimpanan. Empat artikel tetap menunggu backup restore test dan
persetujuan owner. Lihat `docs/ARTICLE_MODULE_REPAIR_PLAN.md`.

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

Audit artikel 2026-11-07: full PHPUnit menjadi **82 tests, 989 assertions**
lulus; `ArticlePublishingTest` menjadi **8 tests, 169 assertions** lulus.
Browser membuka `/artikel` pada desktop dan mobile dengan empty state. Card
artikel dan detail belum dapat dinyatakan lulus tanpa data live.

## Keamanan database aktif

MySQL aktif tidak diakses; tidak ada migration atau seeder production dijalankan. `.env`, koneksi database aktif, transaksi, produk, customer, order, payment, refund, dan QRIS tidak diubah. Migration tetap menunggu review/persetujuan pemilik proyek sebelum aktivasi.

## Finalisasi artikel dan global page loading (4 November 2026)

**Konten artikel benar-benar dibuat lengkap** di `ArticleSeeder`, dengan konten, category, slug, excerpt, SEO title dan description. Seeder idempotent memakai `firstOrCreate`, mempublikasikan seed awal dengan `published_at` yang valid, dan tidak menimpa edit admin. **Empat artikel tersedia pada SQLite testing** dan route daftar/detail publik lulus; test memastikan judul, body non-placeholder, metadata, slug, status/tanggal, draft tidak tampak, halaman homepage maksimal tiga, dan seed ulang tidak membuat duplikat atau mengembalikan edit admin. Detail menampilkan body sebagai heading/paragraf/daftar yang di-escape, breadcrumb, tanggal, excerpt, fallback brand untuk cover yang tidak ada, dan artikel terkait. Tidak ada foto produk yang dibuat atau diklaim.

**Loader benar-benar terlihat saat perpindahan halaman** melalui overlay full-screen RotiKita dengan monogram R, status, titik animasi, dan progress indeterminate. Ia terpasang pada storefront, dashboard admin/customer, serta guest login/register. Klik internal same-origin dan submit form halaman memicu loader; external/anchor/download/target baru/modifier/AJAX/cancelled tidak memicu. Halaman tujuan menyembunyikan overlay saat `load`, dengan flag session kedaluwarsa, fail-safe, dan penanganan Back/Forward/BFCache.

**Loader bekerja pada form** melalui event submit setelah validasi browser dan tidak mengubah atribut form atau input; browser tidak mengirim event submit ketika constraint validation gagal. **Reduced motion dan fallback telah diuji** lewat test JavaScript/CSS: animasi dinonaktifkan tetapi aria-live status tetap ada, konten normal tetap tersedia tanpa JS, dan fail-safe menghapus overlay yang tersisa. Node syntax check dan 4 test JavaScript lulus; Vite production build lulus. Pengujian visual browser tidak dapat dijalankan karena browser automation tidak terpasang, sehingga keberhasilan tampilan visual tidak diklaim. Instruksi uji manual tersedia di `CRM_FINALIZATION_PROGRESS.md`.

Tidak ada database aktif yang diakses. Feature test menggunakan SQLite `:memory:`; tidak ada migration/seeder production, perubahan `.env`, QRIS, order, payment, refund, atau produk.
# Laporan release readiness — 2026-11-07

## Perubahan dan hasil

- Sales report admin menyediakan CSV dan print view khusus. Periode dan filter status order konsisten pada halaman, CSV, dan print view. CSV berisi paid/refund per order dan ringkasan order, paid, refund, serta net revenue dari `RevenueService`. Print view memuat semua hasil filter tanpa pagination; browser print dapat menyimpan PDF, tetapi PDF otomatis tidak dibuat.
- CSV menggunakan streaming per chunk 500, UTF-8 BOM, netralisasi formula, dan tidak menyertakan PII customer yang tidak diperlukan. Akses route dibatasi admin.
- `products:prepare-launch` tetap dry-run secara default. Preview menampilkan produk siap/tertahan beserta alasan dan perubahan stok lama → 30. Mode apply menolak DB non-SQLite in-memory. Tidak dijalankan pada database aplikasi; stock 30 adalah target demo, bukan verifikasi fisik.
- ROT-074 dan ROT-084 tertahan karena harga belum final. Tidak ada harga yang dikarang.
- Tiga konsep promo tersedia sebagai draft di admin tanpa nilai finansial, kode, kuota, atau periode rekaan; belum ada promo yang diaktifkan.
- Footer menggunakan `mailto:Rotikita@gmail.com`; Instagram/TikTok hanya tampil saat URL canonical resmi dikonfigurasi.
- Kategori mencari file public valid dari produk kategori yang sama, termasuk produk nonaktif. Tidak memakai foto kategori lain; fallback CSS tanpa emoji digunakan jika aset tidak ada.
- Loader menggunakan SVG maskot roti dengan hop/squash/shadow dan dukungan reduced motion. Identitas Haikal, Rizal, Selva serta copywriting ada di dashboard.
- Empat artikel mempunyai konten substantif dan seeder `firstOrCreate`. Empat cover import tersedia, tervalidasi sebagai JPEG 1200×675, dan disalin hanya bila file tujuan belum ada.

## Pengujian dan batas

- PHPUnit config/test case mengisolasi `APP_ENV=testing`, SQLite `:memory:`, `DB_URL` kosong, cache/session array, queue sync. SQLite DLL dimuat sementara pada PHP 8.3.36 melalui `-d`; tidak ada perubahan `php.ini`.
- PHPUnit full suite final: **70 tests, 887 assertions, lulus**. Mencakup report CSV/status/refund/print, launch preparation, category matching, promotion drafts, dan regression lainnya.
- Pint, PHP lint, Node syntax/test, dan Vite production build diperiksa. Build dapat menampilkan warning opsional package `fontaine`.
- Browser visual acceptance publik sudah dilakukan terbatas setelah `mod_rewrite` aktif: homepage, katalog, daftar artikel, login, dan register diuji pada desktop/mobile. Dashboard dan fitur internal masih memerlukan sesi autentikasi.
- Pada audit sebelumnya folder cover belum tersedia; integrasi berikutnya dicatat pada addendum Visual acceptance & article covers di bawah.
- Audit GitHub mengidentifikasi file konfigurasi lokal dan backup pada initial commit `7869689`; status Private tidak diverifikasi. Lihat `GITHUB_PREPUBLISH_AUDIT.md`.

## Status akhir

1. **Selesai:** report CSV/print, detail refund, filter status, product launch preview, kategori, loader, kontak, konsep campaign, dan pengujian SQLite.
2. **Belum selesai:** cover artikel yang diminta dan visual browser test.
3. **Blocker:** harga final ROT-074/084 belum tersedia. DLL SQLite perlu dimuat sementara pada mesin ini.
4. **Perlu keputusan pemilik:** harga ROT-074/084, URL resmi social, parameter promo, aset cover yang sah, dan pemeriksaan visual.
5. **Database aktif:** stok/aktivasi belum diterapkan. Jangan jalankan apply ke MySQL sebelum persetujuan. MySQL, `.env`, QRIS, dan transaksi tidak disentuh.

Langkah visual manual: buka homepage dan cek kategori/filter; jalankan navigasi internal serta Back/Forward dengan reduced motion aktif; buka admin report lalu print dan pastikan semua baris filter tercetak; cek dashboard admin/customer dan storefront pada desktop serta viewport mobile.

## Verifikasi lanjutan — 7 November 2026

- Keempat source cover ada, berformat `image/jpeg`, dan berukuran 1200×675. Source tetap berada di `storage/app/imports/rotikita-article-covers/`.
- Tujuan ada di `storage/app/public/articles/` dengan URL relatif `/storage/articles/<slug>.jpg`; ukuran byte salinan cocok. `public/storage` adalah junction ke `storage/app/public`. URL JPEG diuji melalui browser pada host Laragon dan mengembalikan halaman gambar 1200×675.
- Mapping slug, path, dan alt text berada di `config/rotikita-article-covers.php`. `ArticleCoverSeeder` hanya mengisi `cover_path` yang kosong; command publikasi memvalidasi JPEG dan tidak menimpa file tujuan.
- PHPUnit penuh aktual: **74 tests, 940 assertions, lulus** pada SQLite in-memory dari `phpunit.xml`. Vite build, lint PHP cover, Node syntax check, dan `git diff --check` lulus. Build memberi warning informasional untuk package opsional `fontaine`.
- Browser dapat membuka route publik setelah `mod_rewrite` aktif. Homepage, katalog, daftar artikel, login, dan register telah diperiksa pada desktop/mobile; dashboard, fitur internal, dan artikel detail masih belum diverifikasi.
- Tidak ada seeder/migration ke database aplikasi, koneksi MySQL, `.env`, atau data transaksi yang disentuh. Tidak ada operasi Git yang mengubah riwayat atau mengirim perubahan.
- Ditemukan dan diperbaiki overflow horizontal homepage mobile yang disebabkan carousel produk; `.rks-page` kini memotong overflow halaman tanpa menonaktifkan scroll internal carousel.
- Daftar artikel live menampilkan empty state karena ArticleSeeder tidak dijalankan; empat cover belum dapat diklaim tampil visual pada card/detail live.
- QA closure: CSS diff menambahkan `overflow-x: hidden` pada `.rks-page` only. Carousel tetap menggunakan `overflow-x: auto`, `scroll-snap`, dan card flex-basis; body overflow terukur normal pada 390px dan 1440px. Tombol carousel tetap tersedia, tetapi artikel/card cover live belum tersedia untuk pemeriksaan visual penuh.
- Regression aktual closure: PHPUnit **74 tests / 940 assertions lulus**, PHP lint **119 files lulus**, Pint lulus, JavaScript tests **4 lulus**, Node syntax lulus, Vite build lulus dengan warning opsional `fontaine`, dan `git diff --check` lulus.

# Visual acceptance & article covers — 2026-11-07

- Empat file sumber ditemukan, JPEG valid, masing-masing berukuran 1200×675. File dicocokkan dengan judul dan isi pada `ArticleSeeder` dan diperiksa visual secara lokal.
- File cover dipublikasikan melalui Laravel Storage ke `storage/app/public/articles/` dengan command `articles:publish-covers`. Command memvalidasi seluruh sumber sebelum menulis, tidak menyentuh database, dan melewati file tujuan yang sudah ada. Sumber import tidak dihapus.
- Mapping slug/path/alt tersedia pada `config/rotikita-article-covers.php`. `ArticleCoverSeeder` hanya mengisi `cover_path` kosong untuk artikel yang sudah ada dan aset public tersedia. Model juga menyediakan `display_cover_path` dari mapping agar artikel lama tanpa nilai `cover_path` dapat memakai aset yang sudah dipublikasikan. Seeder database tidak dijalankan pada database aplikasi.
- Homepage, daftar artikel, dan detail artikel menggunakan URL `/storage/articles/...`, alt text deskriptif, dan fallback brand untuk path/file yang tidak tersedia atau gagal dimuat.
- PHPUnit penuh setelah perubahan: **74 tests, 940 assertions lulus**. Test mencakup validasi/copy aset, tidak menimpa aset tujuan, mapping slug, render halaman artikel, serta cover admin yang dipertahankan.
- Pint, PHP lint, Node test/syntax, Vite build, dan `git diff --check` telah dijalankan. Lihat laporan akhir Codex untuk angka final jika ada pembaruan setelah bagian ini.
- Browser visual acceptance publik terbatas telah dilakukan setelah `mod_rewrite` aktif; dashboard admin/customer, pelanggan, pemesanan, promosi, feedback, tiket, laporan, dan artikel detail masih menunggu sesi/data yang sesuai. Checklist: `VISUAL_ACCEPTANCE_CHECKLIST.md`.
- `/storage` adalah junction yang menunjuk `storage/app/public`; keempat URL cover merespons HTTP 200 dengan content type `image/jpeg` melalui host Laragon.
- Diagnosis route sebelumnya terselesaikan di luar workspace: vhost tetap menunjuk ke `C:/laragon/www/ecrm-roti-kita/public` dan `rewrite_module` kini aktif.
## Critical error verification — 2026-11-07

- Ticket migration was audited without execution. The missing `support_tickets` table remains
  an environment/deployment blocker requiring owner-approved migration execution.
- Article list returned HTTP 200 through both `127.0.0.1:8000` and `ecrm-roti-kita.test`.
  The reported HTTP 503 was not reproducible; no maintenance marker or matching PHP exception
  was found. Article detail returns 404 when its row is absent, as expected without seeding.
- No active database, `.env`, migration, or seeder was touched.
- Current status: **NEEDS FIX** until the ticket schema is installed and authenticated routes
  are smoke-tested by the database owner.
