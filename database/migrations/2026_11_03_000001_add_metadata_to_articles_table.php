<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'category')) {
                $table->string('category', 80)->nullable();
            }
            if (! Schema::hasColumn('articles', 'seo_title')) {
                $table->string('seo_title', 180)->nullable();
            }
            if (! Schema::hasColumn('articles', 'seo_description')) {
                $table->string('seo_description', 320)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['category', 'seo_title', 'seo_description'],
                fn (string $column): bool => Schema::hasColumn('articles', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
