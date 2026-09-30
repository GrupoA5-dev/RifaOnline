<?php

namespace App\Models;

use App\Enums\AllocationMode;
use App\Enums\RaffleStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Raffle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'rules',
        'status',
        'ticket_price',
        'revenue_goal',
        'total_numbers',
        'sales_goal_numbers',
        'number_digits',
        'min_purchase',
        'max_purchase',
        'allocation_mode',
        'starts_at',
        'ends_at',
        'reservation_minutes',
        'cover_image',
        'organizer_name',
        'instagram_url',
        'whatsapp_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'draw_mode',
        'draw_reference',
        'featured',
        'collect_name',
        'collect_email',
        'collect_phone',
        'collect_document',
    ];

    protected static function booted(): void
    {
        static::creating(function (Raffle $raffle): void {
            $raffle->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => RaffleStatus::class,
            'allocation_mode' => AllocationMode::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'featured' => 'boolean',
            'collect_name' => 'boolean',
            'collect_email' => 'boolean',
            'collect_phone' => 'boolean',
            'collect_document' => 'boolean',
        ];
    }

    protected function ticketPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Money::format((int) $this->ticket_price_cents),
            set: fn (string|int|float $value): array => ['ticket_price_cents' => Money::toCents($value)],
        );
    }

    protected function revenueGoal(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->revenue_goal_cents === null ? null : Money::format((int) $this->revenue_goal_cents),
            set: fn (string|int|float|null $value): array => [
                'revenue_goal_cents' => blank($value) ? null : Money::toCents($value),
            ],
        );
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(RafflePrize::class)->orderBy('position');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(RaffleWinner::class)->orderByDesc('announced_at')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RaffleEvent::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TicketAllocation::class);
    }

    public function isPurchasable(): bool
    {
        // O status administrativo é a fonte de verdade para venda.
        // Campanhas agendadas devem usar o status `scheduled`; ao mudar
        // explicitamente para `active`, datas antigas não podem mantê-la
        // invisível ou impedir novas compras.
        return $this->status === RaffleStatus::Active;
    }

    public function availableCount(): int
    {
        return max(0, (int) $this->total_numbers - $this->allocations()->count());
    }

    public function formatNumber(int $number): string
    {
        return str_pad((string) $number, (int) $this->number_digits, '0', STR_PAD_LEFT);
    }
}
