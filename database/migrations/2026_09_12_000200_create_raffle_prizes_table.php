<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_prizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('prize_value_cents')->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_prizes');
    }
};
