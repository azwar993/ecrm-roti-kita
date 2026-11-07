<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $missingColumns = collect(['unit', 'price_status', 'admin_note'])
            ->reject(fn (string $column): bool => Schema::hasColumn('products', $column))
            ->all();

        if ($missingColumns === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($missingColumns): void {
            if (in_array('unit', $missingColumns, true)) {
                $table->string('unit', 30)->nullable()->after('category');
            }

            if (in_array('price_status', $missingColumns, true)) {
                $table->string('price_status', 20)->default('final');
            }

            if (in_array('admin_note', $missingColumns, true)) {
                $table->text('admin_note')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'unit')
            && DB::table('products')->whereNotNull('unit')->exists()) {
            throw new LogicException('Product units contain imported data; preserve them and use a forward migration.');
        }

        if (Schema::hasColumn('products', 'price_status')
            && DB::table('products')->where('price_status', '<>', 'final')->exists()) {
            throw new LogicException('Product price statuses contain review data; preserve them and use a forward migration.');
        }

        if (Schema::hasColumn('products', 'admin_note')
            && DB::table('products')->whereNotNull('admin_note')->exists()) {
            throw new LogicException('Product admin notes contain imported data; preserve them and use a forward migration.');
        }

        $columnsToDrop = collect(['unit', 'price_status', 'admin_note'])
            ->filter(fn (string $column): bool => Schema::hasColumn('products', $column))
            ->all();

        if ($columnsToDrop !== []) {
            Schema::table('products', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
