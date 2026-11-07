# ROUTE PATCH — RotiKita Checkout v1

Jangan menimpa seluruh `routes/web.php`. Buka file yang sudah ada dan lakukan tiga perubahan kecil berikut.

## 1. Tambahkan import di atas

Tambahkan di antara `use` yang sudah ada:

```php
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
```

Jangan hapus import ProductController, AuthController, CatalogController, atau Route.

## 2. Di dalam grup admin

Cari baris lama:

```php
Route::view('/orders', 'admin.orders')->name('orders');
```

Ganti baris itu saja dengan:

```php
Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'verifyPayment'])->name('orders.payment');
Route::get('/orders/{order}/receipt', [AdminOrderController::class, 'receipt'])->name('orders.receipt');
```

## 3. Di dalam grup customer

Cari baris lama:

```php
Route::view('/orders', 'customer.orders')->name('orders');
```

Ganti baris itu saja dengan blok berikut, sekaligus tambahkan route keranjang dan checkout:

```php
Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/receipt', [CustomerOrderController::class, 'receipt'])->name('orders.receipt');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::put('/cart/items/{type}/{id}', [CartController::class, 'update'])
    ->where('type', 'product|combo')->whereNumber('id')->name('cart.items.update');
Route::delete('/cart/items/{type}/{id}', [CartController::class, 'destroy'])
    ->where('type', 'product|combo')->whereNumber('id')->name('cart.items.destroy');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
```

Semua route harus tetap berada di dalam grup `prefix('admin') ... middleware(['auth', 'role:admin'])` atau `prefix('customer') ... middleware(['auth', 'role:customer'])` yang sudah ada.

## 4. Setelah simpan

Jalankan:

```powershell
$php = "C:\laragon\bin\php\php-8.3.36-1-Win32-vs16-x64\php.exe"
& $php artisan optimize:clear
& $php artisan route:list
& $php artisan view:cache
```

Pastikan route `customer.cart`, `customer.checkout`, `customer.orders.show`, `admin.orders.show`, `admin.orders.status`, dan `admin.orders.payment` muncul.
