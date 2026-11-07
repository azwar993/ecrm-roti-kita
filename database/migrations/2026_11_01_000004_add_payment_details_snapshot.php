<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'payment_details')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->json('payment_details')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Preserve payment instructions attached to historic orders.
    }
};
