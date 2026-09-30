<?php

namespace App\Actions\Raffles;

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

final class ReserveRandomTickets
{
    public function __construct(private readonly PixReservationWindow $reservationWindow) {}

    public function handle(Raffle $raffle, Customer $customer, int $quantity, int $discountCents = 0): Order
    {
        if ($quantity < 1) {
            throw new InvalidPurchaseQuantityException('A quantidade deve ser maior que zero.');
        }

        return DB::transaction(function () use ($raffle, $customer, $quantity, $discountCents): Order {
            /** @var Raffle $lockedRaffle */
            $lockedRaffle = Raffle::query()->lockForUpdate()->findOrFail($raffle->getKey());

            if (! $lockedRaffle->isPurchasable()) {
                throw new RaffleUnavailableException('Esta campanha não está disponível para compra.');
            }

            if ($quantity < (int) $lockedRaffle->min_purchase) {
                throw new InvalidPurchaseQuantityException('Quantidade abaixo da compra mínima.');
            }

            if ($lockedRaffle->max_purchase !== null && $quantity > (int) $lockedRaffle->max_purchase) {
                throw new InvalidPurchaseQuantityException('Quantidade acima da compra máxima.');
            }

            $allocatedCount = $lockedRaffle->allocations()->count();
            $availableCount = (int) $lockedRaffle->total_numbers - $allocatedCount;

            if ($availableCount < $quantity) {
                throw new InsufficientTicketsException('Não há números suficientes disponíveis.');
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

            $reservedNumbers = $this->reserveNumbers($lockedRaffle, $order, $quantity, $expiresAt);

            foreach ($reservedNumbers as $number) {
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

    /**
     * Percorre o universo de números em uma permutação pseudoaleatória, em lotes.
     * Isso evita carregar todos os números em memória e continua eficiente perto do esgotamento.
     *
     * @return array<int, int>
     */
    private function reserveNumbers(Raffle $raffle, Order $order, int $quantity, $expiresAt): array
    {
        $total = (int) $raffle->total_numbers;

        if ($total < 1) {
            throw new InsufficientTicketsException('A campanha não possui uma faixa de números válida.');
        }

        $start = random_int(0, $total - 1);
        $step = $this->coprimeStep($total);
        $cursor = 0;
        $numbers = [];
        $batchSize = 500;

        while (count($numbers) < $quantity && $cursor < $total) {
            $candidates = [];
            $limit = min($batchSize, $total - $cursor);

            for ($i = 0; $i < $limit; $i++) {
                $candidates[] = ($start + (($cursor + $i) * $step)) % $total;
            }

            $cursor += $limit;

            $occupied = DB::table('ticket_allocations')
                ->where('raffle_id', $raffle->getKey())
                ->whereIn('number', $candidates)
                ->pluck('number')
                ->mapWithKeys(fn ($number) => [(int) $number => true])
                ->all();

            foreach ($candidates as $number) {
                if (isset($occupied[$number])) {
                    continue;
                }

                $inserted = DB::table('ticket_allocations')->insertOrIgnore([
                    'raffle_id' => $raffle->getKey(),
                    'order_id' => $order->getKey(),
                    'number' => $number,
                    'status' => TicketAllocationStatus::Reserved->value,
                    'reserved_at' => now(),
                    'expires_at' => $expiresAt,
                    'paid_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted === 1) {
                    $numbers[] = $number;
                }

                if (count($numbers) === $quantity) {
                    break;
                }
            }
        }

        if (count($numbers) !== $quantity) {
            throw new InsufficientTicketsException('Não foi possível concluir a reserva com segurança. Tente novamente.');
        }

        sort($numbers, SORT_NUMERIC);

        return $numbers;
    }

    private function coprimeStep(int $total): int
    {
        if ($total <= 2) {
            return 1;
        }

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $step = random_int(1, $total - 1);

            if ($this->gcd($step, $total) === 1) {
                return $step;
            }
        }

        return 1;
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a);
    }
}
