<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->string('instagram_url')->nullable()->after('organizer_name');
            $table->string('whatsapp_url')->nullable()->after('instagram_url');
            $table->string('seo_title')->nullable()->after('whatsapp_url');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
            $table->text('seo_keywords')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->dropColumn([
                'instagram_url',
                'whatsapp_url',
                'seo_title',
                'seo_description',
                'seo_keywords',
            ]);
        });
    }
};
