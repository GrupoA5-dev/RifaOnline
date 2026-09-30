<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 32)->index();
            $table->string('gateway_payment_id', 120)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('status', 32)->index();
            $table->string('status_detail', 120)->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method', 32)->default('pix');
            $table->string('external_reference', 120)->nullable()->index();
            $table->longText('qr_code')->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->text('ticket_url')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_payment_id'], 'payments_gateway_payment_unique');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
