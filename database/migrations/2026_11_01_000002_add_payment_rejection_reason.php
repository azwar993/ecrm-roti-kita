<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'rejection_reason')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->text('rejection_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep payment review notes when rolling back application code.
    }
};
