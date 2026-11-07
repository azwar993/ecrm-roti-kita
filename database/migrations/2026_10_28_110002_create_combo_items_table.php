<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['combo_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
