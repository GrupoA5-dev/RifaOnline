<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->longText('rules')->nullable();
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('ticket_price_cents');
            $table->unsignedBigInteger('total_numbers');
            $table->unsignedTinyInteger('number_digits')->default(6);
            $table->unsignedInteger('min_purchase')->default(1);
            $table->unsignedInteger('max_purchase')->nullable();
            $table->string('allocation_mode', 20)->default('random');
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('reservation_minutes')->default(15);
            $table->string('cover_image')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('draw_mode', 40)->default('manual');
            $table->string('draw_reference')->nullable();
            $table->boolean('featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffles');
    }
};
