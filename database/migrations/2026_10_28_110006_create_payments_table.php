<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('method', 30); // cash | bank_transfer | qris
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->string('reference_number', 150)->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
