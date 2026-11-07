<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained('combos')->restrictOnDelete();
            $table->string('item_name_snapshot', 150);
            $table->string('item_sku_snapshot', 50)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            // Exactly one of product_id/combo_id must be populated; enforce in Laravel validation.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
