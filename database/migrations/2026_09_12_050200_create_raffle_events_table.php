<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40)->index();
            $table->char('session_hash', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['raffle_id', 'event_type', 'created_at'], 'raffle_events_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_events');
    }
};
