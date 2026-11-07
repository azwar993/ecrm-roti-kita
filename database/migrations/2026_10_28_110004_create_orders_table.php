<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->string('order_source', 20)->default('website'); // website | counter
            $table->string('order_type', 20); // dine_in | takeaway
            $table->string('table_number', 30)->nullable();
            $table->string('pickup_code', 30)->nullable()->unique();
            $table->string('customer_name_snapshot', 150)->nullable();
            $table->string('customer_phone_snapshot', 30)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('promotion_code_snapshot', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['order_source', 'created_at']);
            $table->index(['order_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
