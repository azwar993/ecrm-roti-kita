<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_ledger_entries')) {
            return;
        }

        Schema::table('payment_ledger_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_ledger_entries', 'reference')) {
                $table->string('reference', 150)->nullable();
            }
            if (! Schema::hasColumn('payment_ledger_entries', 'idempotency_key')) {
                $table->string('idempotency_key', 191)->nullable()->unique();
            }
            if (! Schema::hasColumn('payment_ledger_entries', 'description')) {
                $table->string('description', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_ledger_entries')) {
            return;
        }

        Schema::table('payment_ledger_entries', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['reference', 'idempotency_key', 'description'],
                fn (string $column): bool => Schema::hasColumn('payment_ledger_entries', $column),
            ));

            if (in_array('idempotency_key', $columns, true)) {
                $table->dropUnique(['idempotency_key']);
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
