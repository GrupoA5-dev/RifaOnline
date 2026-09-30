<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RafflePrize extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'position',
        'title',
        'description',
        'image',
        'prize_value',
    ];

    protected function prizeValue(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->prize_value_cents === null ? null : Money::format((int) $this->prize_value_cents),
            set: fn (string|int|float|null $value): array => ['prize_value_cents' => blank($value) ? null : Money::toCents($value)],
        );
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }
}
