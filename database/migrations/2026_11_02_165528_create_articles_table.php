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
        if (! Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 180);
                $table->string('slug', 200)->unique();
                $table->text('excerpt')->nullable();
                $table->longText('body');
                $table->string('cover_path')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'published_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve article content when rolling back application code.
    }
};
