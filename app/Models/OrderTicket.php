<?php

namespace App\Models;

use App\Enums\OrderTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'raffle_id',
        'number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderTicketStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }
}
