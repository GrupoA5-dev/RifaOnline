<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('document_type', 10)->nullable()->after('phone');
            $table->string('document_number', 32)->nullable()->after('document_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['document_number']);
            $table->dropColumn(['document_type', 'document_number']);
        });
    }
};
