<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->boolean('collect_name')->default(true)->after('featured');
            $table->boolean('collect_email')->default(true)->after('collect_name');
            $table->boolean('collect_phone')->default(true)->after('collect_email');
            $table->boolean('collect_document')->default(true)->after('collect_phone');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->dropColumn([
                'collect_name',
                'collect_email',
                'collect_phone',
                'collect_document',
            ]);
        });
    }
};
