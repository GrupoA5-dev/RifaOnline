<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 32)->index();
            $table->string('event_key', 64)->unique();
            $table->string('event_type', 120)->nullable()->index();
            $table->string('resource_id', 120)->nullable()->index();
            $table->string('request_id', 120)->nullable()->index();
            $table->boolean('signature_valid')->default(false)->index();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
