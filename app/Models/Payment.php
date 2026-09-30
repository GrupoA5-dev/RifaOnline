<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_payment_id',
        'idempotency_key',
        'status',
        'status_detail',
        'amount_cents',
        'currency',
        'payment_method',
        'external_reference',
        'qr_code',
        'qr_code_base64',
        'ticket_url',
        'expires_at',
        'paid_at',
        'refunded_at',
        'last_synced_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->uuid ??= (string) Str::uuid();
            $payment->idempotency_key ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
