<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->boolean('email_marketing_consent')->default(false)->after('notes');
            $table->timestamp('consent_updated_at')->nullable()->after('email_marketing_consent');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['email_marketing_consent', 'consent_updated_at']);
        });
    }
};
