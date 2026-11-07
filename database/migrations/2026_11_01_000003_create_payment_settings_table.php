<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('payment_settings')) {
            Schema::create('payment_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('qris_image')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_name', 150)->nullable();
                $table->string('bank_account_number', 80)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep merchant payment configuration on rollback.
    }
};
