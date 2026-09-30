<?php

namespace App\Models;

use App\Enums\TicketAllocationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'order_id',
        'number',
        'status',
        'reserved_at',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketAllocationStatus::class,
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
