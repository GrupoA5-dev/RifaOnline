<?php

namespace App\Actions\Raffles;

use App\Enums\AllocationMode;
use App\Enums\OrderStatus;
use App\Enums\OrderTicketStatus;
use App\Enums\TicketAllocationStatus;
use App\Exceptions\InsufficientTicketsException;
use App\Exceptions\InvalidPurchaseQuantityException;
use App\Exceptions\RaffleUnavailableException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Raffle;
use App\Services\Payments\PixReservationWindow;
use Illuminate\Support\Facades\DB;

final class ReserveSelectedTickets
{
    public function __construct(private readonly PixReservationWindow $reservationWindow) {}

    /**
     * @param array<int, int> $numbers
     */
    public function handle(Raffle $raffle, Customer $customer, array $numbers, int $discountCents = 0): Order
    {
        $numbers = array_values(array_unique(array_map('intval', $numbers)));
        sort($numbers, SORT_NUMERIC);
        $quantity = count($numbers);

        if ($quantity < 1) {
            throw new InvalidPurchaseQuantityException('Selecione pelo menos um número.');
        }

        return DB::transaction(function () use ($raffle, $customer, $numbers, $quantity, $discountCents): Order {
            /** @var Raffle $lockedRaffle */
            $lockedRaffle = Raffle::query()->lockForUpdate()->findOrFail($raffle->getKey());

            if (! $lockedRaffle->isPurchasable()) {
                throw new RaffleUnavailableException('Esta campanha não está disponível para compra.');
            }

            if ($lockedRaffle->allocation_mode !== AllocationMode::Manual) {
                throw new RaffleUnavailableException('Esta campanha não utiliza escolha manual de números.');
            }

            if ($quantity < (int) $lockedRaffle->min_purchase) {
                throw new InvalidPurchaseQuantityException('Selecione pelo menos '.$lockedRaffle->min_purchase.' número(s).');
            }

            if ($lockedRaffle->max_purchase !== null && $quantity > (int) $lockedRaffle->max_purchase) {
                throw new InvalidPurchaseQuantityException('Você pode selecionar no máximo '.$lockedRaffle->max_purchase.' número(s).');
            }

            $maxNumber = (int) $lockedRaffle->total_numbers - 1;

            foreach ($numbers as $number) {
                if ($number < 0 || $number > $maxNumber) {
                    throw new InvalidPurchaseQuantityException('Um dos números selecionados não pertence a esta campanha.');
                }
            }

            $occupied = DB::table('ticket_allocations')
                ->where('raffle_id', $lockedRaffle->getKey())
                ->whereIn('number', $numbers)
                ->pluck('number')
                ->map(fn ($number) => $lockedRaffle->formatNumber((int) $number))
                ->values()
                ->all();

            if ($occupied !== []) {
                throw new InsufficientTicketsException(
                    'Um ou mais números acabaram de ser reservados: '.implode(', ', array_slice($occupied, 0, 8)).'. Escolha outros números.'
                );
            }

            $subtotalCents = (int) $lockedRaffle->ticket_price_cents * $quantity;
            $discountCents = max(0, min($discountCents, $subtotalCents));
            $expiresAt = $this->reservationWindow->expiresAt($lockedRaffle);

            $order = Order::query()->create([
                'raffle_id' => $lockedRaffle->getKey(),
                'customer_id' => $customer->getKey(),
                'status' => OrderStatus::AwaitingPayment,
                'quantity' => $quantity,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'total_cents' => $subtotalCents - $discountCents,
                'expires_at' => $expiresAt,
            ]);

            foreach ($numbers as $number) {
                $inserted = DB::table('ticket_allocations')->insertOrIgnore([
                    'raffle_id' => $lockedRaffle->getKey(),
                    'order_id' => $order->getKey(),
                    'number' => $number,
                    'status' => TicketAllocationStatus::Reserved->value,
                    'reserved_at' => now(),
                    'expires_at' => $expiresAt,
                    'paid_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted !== 1) {
                    throw new InsufficientTicketsException(
                        'O número '.$lockedRaffle->formatNumber($number).' acabou de ser reservado. Escolha outro.'
                    );
                }

                OrderTicket::query()->create([
                    'order_id' => $order->getKey(),
                    'raffle_id' => $lockedRaffle->getKey(),
                    'number' => $number,
                    'status' => OrderTicketStatus::Reserved,
                ]);
            }

            return $order->load(['raffle', 'customer', 'tickets']);
        }, 3);
    }
}
