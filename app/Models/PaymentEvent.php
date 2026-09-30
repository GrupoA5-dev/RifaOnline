<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = [
        'gateway',
        'event_key',
        'event_type',
        'resource_id',
        'request_id',
        'signature_valid',
        'payload',
        'processed_at',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
