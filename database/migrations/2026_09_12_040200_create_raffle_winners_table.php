<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_winners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raffle_prize_id')->nullable()->constrained('raffle_prizes')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ticket_number');
            $table->string('winner_name', 160);
            $table->string('prize_title', 255)->nullable();
            $table->timestamp('announced_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'ticket_number']);
            $table->index(['raffle_id', 'announced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_winners');
    }
};
