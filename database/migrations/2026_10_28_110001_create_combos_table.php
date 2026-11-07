<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
