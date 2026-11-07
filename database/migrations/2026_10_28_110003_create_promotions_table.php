<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('discount_type', 20); // percentage | fixed; validated in application
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
