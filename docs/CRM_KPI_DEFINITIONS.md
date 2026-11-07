# Definisi KPI CRM RotiKita

Semua KPI pada dashboard menggunakan periode bulan kalender berjalan berdasarkan `paid_at` untuk transaksi dan `created_at` untuk feedback. Tidak ada angka contoh.

| KPI | Definisi dan rumus | Sumber | Batasan |
|---|---|---|---|
| Total customer | Jumlah seluruh profil customer | `customers` | Profil tanpa transaksi tetap dihitung |
| Customer pembeli aktif | Customer unik dengan order yang memiliki payment `paid` dan `paid_at` dalam bulan berjalan | `orders`, `payments` | Order tanpa `customer_id` tidak dihitung |
| Repeat purchase rate | Buyer unik dengan minimal dua order paid pada bulan berjalan ÷ seluruh buyer unik bulan itu × 100% | `orders`, `payments` | Hanya repeat dalam periode yang sama, bukan lifetime |
| Average paid order value | Jumlah amount payment paid pada periode ÷ jumlah order paid pada periode | `payments`, `orders` | Payment refund yang statusnya bukan `paid` tidak masuk AOV; metrik bukan nilai keranjang sebelum pembayaran |
| Net revenue | Perhitungan `RevenueService` | ledger dan payment legacy | Paid dikurangi refund sesuai event ledger/fallback; tidak dihitung ulang dashboard |
| Rata-rata rating feedback | AVG rating non-null yang dibuat pada bulan berjalan | `feedback.rating` | Null rating dikecualikan; empty tampil sebagai 0 pada UI |
| Tiket terbuka | Jumlah status open, in_progress, waiting_customer | `support_tickets` | Jika migration belum diterapkan, dashboard menampilkan 0 sampai skema tersedia |

Buyer dihitung menggunakan order customer yang memiliki payment paid dalam rentang `startOfMonth()` sampai `endOfMonth()`. Dashboard menampilkan 0% saat tidak ada buyer. Pendapatan tetap menggunakan RevenueService existing.

Belum dihitung: waktu respons pertama dan rata-rata penyelesaian tiket karena belum ada status history atau aturan penanda respons admin yang dapat dipercaya.
