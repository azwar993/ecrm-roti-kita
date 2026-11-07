<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Roti Manis',
        'Roti Tawar',
        'Pastry',
        'Donat',
        'Cake & Dessert',
        'Cookies',
        'Snack Bakery',
    ];

    public const PRICE_STATUS_FINAL = 'final';

    public const PRICE_STATUS_PROPOSED = 'proposed';

    /** Older labels stay selectable until the product owner chooses to normalize them. */
    public const LEGACY_CATEGORIES = ['Roti', 'Cake & Tart', 'Snack Gurih', 'Manis', 'Kecut', 'Roti manis', 'Artisan', 'Paket'];

    protected $fillable = [
        'sku',
        'name',
        'category',
        'description',
        'price',
        'stock_qty',
        'image_path',
        'is_active',
        'unit',
        'price_status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Riwayat perubahan stok produk.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Daftar komposisi combo yang menggunakan produk ini.
     */
    public function comboItems(): HasMany     
    {
        return $this->hasMany(ComboItem::class);
    }
}
