<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'refunded_at')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->timestamp('refunded_at')->nullable();
            });
        }

        if (! Schema::hasTable('payment_ledger_entries')) {
            Schema::create('payment_ledger_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_id')->constrained()->restrictOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('entry_type', 20);
                $table->decimal('amount', 12, 2);
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->unique(['payment_id', 'entry_type']);
                $table->index(['entry_type', 'occurred_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve financial history when rolling back application code.
    }
};
