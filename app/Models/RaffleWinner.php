<?php

namespace App\Models;

use App\Enums\OrderTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class RaffleWinner extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'raffle_prize_id',
        'ticket_number',
        'winner_name',
        'announced_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'announced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RaffleWinner $winner): void {
            /** @var Raffle|null $raffle */
            $raffle = Raffle::query()->find($winner->raffle_id);

            if (! $raffle) {
                throw ValidationException::withMessages([
                    'raffle_id' => 'Selecione uma campanha válida.',
                ]);
            }

            if ((int) $winner->ticket_number < 0 || (int) $winner->ticket_number >= (int) $raffle->total_numbers) {
                throw ValidationException::withMessages([
                    'ticket_number' => 'O número vencedor está fora da faixa desta campanha.',
                ]);
            }

            $ticket = OrderTicket::query()
                ->where('raffle_id', $raffle->getKey())
                ->where('number', (int) $winner->ticket_number)
                ->where('status', OrderTicketStatus::Paid->value)
                ->with('order.customer')
                ->first();

            if (! $ticket) {
                throw ValidationException::withMessages([
                    'ticket_number' => 'Esse número não pertence a um pedido pago desta campanha.',
                ]);
            }

            if ($winner->raffle_prize_id) {
                $prize = RafflePrize::query()
                    ->whereKey($winner->raffle_prize_id)
                    ->where('raffle_id', $raffle->getKey())
                    ->first();

                if (! $prize) {
                    throw ValidationException::withMessages([
                        'raffle_prize_id' => 'O prêmio selecionado não pertence a esta campanha.',
                    ]);
                }

                $winner->prize_title = $prize->title;
            } else {
                $winner->prize_title = null;
            }

            $winner->order_id = $ticket->order_id;
            $winner->customer_id = $ticket->order?->customer_id;
            $winner->winner_name = filled($winner->winner_name)
                ? trim((string) $winner->winner_name)
                : ((string) ($ticket->order?->customer?->name ?: 'Ganhador'));
        });
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(RafflePrize::class, 'raffle_prize_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
