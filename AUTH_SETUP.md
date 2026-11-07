# RotiKita — Paket Fondasi Autentikasi Laravel

Paket ini melanjutkan project Laravel 13 yang sudah memiliki migration `users`, `role`, `is_active`, dan `customers`. Jangan jalankan `migrate:fresh`.

## File dalam paket

- `app/Models/User.php` — model akun lengkap, relasi `customer()`, dan helper role.
- `app/Models/Customer.php` — model profil pelanggan dan relasi `user()`.
- `app/Http/Controllers/Auth/AuthController.php` — login, registrasi, logout, dan redirect berdasarkan role.
- `app/Http/Middleware/RoleMiddleware.php` — membatasi route berdasarkan role dan status akun.
- `routes/web.php` — route login, register, logout, dashboard admin/customer.
- `resources/views/...` — halaman login, register, dan dashboard awal bergaya RotiKita.
- `database/seeders/AdminSeeder.php` — akun admin development lokal.

## 1. Salin file

Ekstrak isi ZIP ke `C:\laragon\www\ecrm-roti-kita`, lalu izinkan file yang memang bernama sama untuk diganti. Sebelum mengganti `routes/web.php` atau `app/Models/User.php`, simpan backup bila sudah ada perubahan sendiri.

## 2. Daftarkan alias middleware (WAJIB)

Buka `bootstrap/app.php`. Tambahkan import berikut jika belum ada:

```php
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Configuration\Middleware;
```

Pada callback `->withMiddleware(function (Middleware $middleware): void { ... })`, tambahkan alias ini tanpa menghapus konfigurasi lain:

```php
$middleware->alias([
    'role' => RoleMiddleware::class,
]);
```

Kalau callback `withMiddleware` sudah ada, cukup masukkan `$middleware->alias(...)` ke dalam callback yang sama. Jangan membuat dua callback `withMiddleware` terpisah.

## 3. Periksa `.env`

Pastikan konfigurasi lokal database benar. Contoh untuk MySQL Laragon tanpa password:

```dotenv
APP_NAME="RotiKita"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecrm_roti_kita
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Jangan menyalin atau mengunggah `.env` ke GitHub. Pertahankan `APP_KEY` yang sudah dibuat; bila belum ada, jalankan `php artisan key:generate`.

## 4. Jalankan perintah dengan PHP 8.3 Laragon

Di PowerShell, dari folder project:

```powershell
$php = "C:\laragon\bin\php\php-8.3.36-1-Win32-vs16-x64\php.exe"
& $php artisan optimize:clear
& $php artisan route:list
& $php artisan db:seed --class=AdminSeeder
```

Seeder membuat akun development berikut:

- Email: `admin@rotikita.test`
- Password: `RotiKitaAdmin123!`

Ini hanya kredensial lokal untuk demo. Ganti password dan jangan gunakan kredensial ini pada server publik.

Jalankan server:

```powershell
& $php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000/login`.

## 5. Uji alur

1. Login admin dengan kredensial development untuk masuk `/admin/dashboard`.
2. Logout, kemudian registrasi akun customer melalui `/register`.
3. Setelah registrasi, user otomatis mendapat role `customer` dan profil CRM dibuat pada tabel `customers`.
4. Customer masuk ke `/customer/dashboard`.
5. Coba akses `/admin/dashboard` ketika login sebagai customer; seharusnya menerima HTTP 403.
6. Coba akses `/customer/dashboard` ketika login sebagai admin; seharusnya menerima HTTP 403.

## Catatan

- Paket ini merupakan fondasi autentikasi dan dashboard awal; katalog, pemesanan, promosi, feedback, serta laporan belum diimplementasikan.
- Role ditentukan di backend. Form registrasi publik tidak menerima pilihan role admin.
- `User::$fillable` sengaja tidak mencakup `role` dan `is_active` untuk mengurangi risiko mass-assignment.
- Kalau migration `customers` belum tersedia, buat dan jalankan migration tersebut sebelum mencoba registrasi.
