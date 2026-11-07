# RotiKita — sambungan relasi ke model yang sudah ada

Paket ini sengaja **tidak menyertakan `User.php`, `Customer.php`, `Product.php`** supaya tidak menimpa file autentikasi dan model produk yang sudah ada. Tambahkan relasi berikut secara manual ke class yang sesuai. Jika sebuah method sudah ada, jangan menggandakannya.

## 1. `app/Models/User.php`

Tambahkan import yang belum ada di bagian atas:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
```

Tambahkan method berikut di dalam class `User`:

```php
public function customer(): HasOne
{
    return $this->hasOne(Customer::class);
}

public function createdOrders(): HasMany
{
    return $this->hasMany(Order::class, 'created_by');
}

public function handledOrders(): HasMany
{
    return $this->hasMany(Order::class, 'handled_by');
}

public function verifiedPayments(): HasMany
{
    return $this->hasMany(Payment::class, 'verified_by');
}

public function changedOrderStatuses(): HasMany
{
    return $this->hasMany(OrderStatusHistory::class, 'changed_by');
}

public function stockMovements(): HasMany
{
    return $this->hasMany(StockMovement::class, 'created_by');
}

public function handledFeedback(): HasMany
{
    return $this->hasMany(Feedback::class, 'handled_by');
}

public function assignedProspects(): HasMany
{
    return $this->hasMany(Prospect::class, 'assigned_to');
}
```

Jika `customer()` sudah ada, jangan tambahkan untuk kedua kali.

## 2. `app/Models/Customer.php`

Tambahkan import jika belum ada:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Tambahkan ke dalam class:

```php
public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}

public function feedback(): HasMany
{
    return $this->hasMany(Feedback::class);
}

public function convertedProspects(): HasMany
{
    return $this->hasMany(Prospect::class, 'converted_customer_id');
}
```

Pertahankan method `user()` dan `$fillable` yang saat ini sudah ada.

## 3. `app/Models/Product.php`

Tambahkan import jika belum ada:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Tambahkan ke dalam class:

```php
public function comboItems(): HasMany
{
    return $this->hasMany(ComboItem::class);
}

public function combos(): BelongsToMany
{
    return $this->belongsToMany(Combo::class, 'combo_items')
        ->withPivot('quantity')
        ->withTimestamps();
}

public function orderItems(): HasMany
{
    return $this->hasMany(OrderItem::class);
}

public function stockMovements(): HasMany
{
    return $this->hasMany(StockMovement::class);
}
```

Pertahankan seluruh `$fillable` dan `casts()` yang sudah ada.

## 4. Catatan skema

- `orders.customer_id` nullable agar pesanan counter bisa dicatat tanpa akun customer; snapshot nama/telepon dapat dipakai untuk catatan kasir.
- `order_items` dirancang untuk menyimpan tepat salah satu dari `product_id` atau `combo_id`. Validasi ini harus diberlakukan di service/controller transaksi.
- Stok combo nanti dihitung dari `combo_items.quantity` dikalikan jumlah combo yang dipesan. Gunakan database transaction dan lock stok untuk menghindari stok negatif.
- `OrderStatusHistory` dan `StockMovement` hanya memiliki `created_at`, tidak `updated_at`; model mengatur `$timestamps = false` agar Eloquent tidak mengharapkan kolom `updated_at`.
- Jangan masukkan transaksi/pelanggan dummy. Tes dapat menggunakan produk nyata yang sudah dibuat, dengan transaksi pengujian diberi catatan dan kemudian dibatalkan sesuai prosedur.
