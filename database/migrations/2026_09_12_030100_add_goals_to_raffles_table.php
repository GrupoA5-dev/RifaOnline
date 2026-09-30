<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->unsignedBigInteger('revenue_goal_cents')->nullable()->after('ticket_price_cents');
            $table->unsignedBigInteger('sales_goal_numbers')->nullable()->after('total_numbers');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table): void {
            $table->dropColumn(['revenue_goal_cents', 'sales_goal_numbers']);
        });
    }
};
