<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type', 20); // complaint | suggestion | review
            $table->unsignedTinyInteger('rating')->nullable(); // validate 1..5 in Laravel
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
