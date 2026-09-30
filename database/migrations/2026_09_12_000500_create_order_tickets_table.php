<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raffle_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('number');
            $table->string('status', 24)->index();
            $table->timestamps();

            $table->unique(['order_id', 'number']);
            $table->index(['raffle_id', 'number']);
            $table->index(['raffle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tickets');
    }
};
