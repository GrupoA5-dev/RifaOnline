<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\RaffleStatus;
use App\Models\Order;
use App\Models\Raffle;
use App\Support\Money;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $paid = $this->orderQuery(OrderStatus::Paid);
        $revenue = (int) (clone $paid)->sum('total_cents');
        $tickets = (int) (clone $paid)->sum('quantity');
        $paidOrders = (int) (clone $paid)->count();
        $pendingOrders = (int) $this->orderQuery(OrderStatus::AwaitingPayment)->count();

        $selectedRaffle = $this->selectedRaffle();
        $fourth = $selectedRaffle
            ? $this->goalStat($selectedRaffle, $revenue, $tickets)
            : Stat::make('Campanhas ativas', Raffle::query()->where('status', RaffleStatus::Active->value)->count())
                ->description('Campanhas disponíveis para venda')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info');

        return [
            Stat::make('Receita confirmada', 'R$ '.Money::format($revenue))
                ->description($paidOrders.' pedido(s) pago(s)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($this->revenueSparkline())
                ->color('success'),
            Stat::make('Números vendidos', number_format($tickets, 0, ',', '.'))
                ->description('Somente pedidos confirmados')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),
            Stat::make('Aguardando pagamento', number_format($pendingOrders, 0, ',', '.'))
                ->description('Reservas ainda abertas')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),
            $fourth,
        ];
    }

    private function goalStat(Raffle $raffle, int $periodRevenue, int $periodTickets): Stat
    {
        $goalCents = (int) ($raffle->revenue_goal_cents ?? 0);
        $goalTickets = (int) ($raffle->sales_goal_numbers ?: $raffle->total_numbers);

        if ($goalCents > 0) {
            $allRevenue = (int) Order::query()
                ->where('raffle_id', $raffle->getKey())
                ->where('status', OrderStatus::Paid->value)
                ->sum('total_cents');
            $progress = min(100, round(($allRevenue / $goalCents) * 100, 1));

            return Stat::make('Meta da campanha', $progress.'%')
                ->description('R$ '.Money::format($allRevenue).' de R$ '.Money::format($goalCents))
                ->descriptionIcon('heroicon-m-flag')
                ->color($progress >= 100 ? 'success' : 'primary');
        }

        $allTickets = (int) Order::query()
            ->where('raffle_id', $raffle->getKey())
            ->where('status', OrderStatus::Paid->value)
            ->sum('quantity');
        $progress = $goalTickets > 0 ? min(100, round(($allTickets / $goalTickets) * 100, 1)) : 0;

        return Stat::make('Meta da campanha', $progress.'%')
            ->description(number_format($allTickets, 0, ',', '.').' de '.number_format($goalTickets, 0, ',', '.').' números')
            ->descriptionIcon('heroicon-m-flag')
            ->color($progress >= 100 ? 'success' : 'primary');
    }

    private function orderQuery(OrderStatus $status)
    {
        $query = Order::query()->where('status', $status->value);

        if ($raffleId = $this->raffleId()) {
            $query->where('raffle_id', $raffleId);
        }

        if ($start = $this->periodStart()) {
            $column = $status === OrderStatus::Paid ? 'paid_at' : 'created_at';
            $query->where($column, '>=', $start);
        }

        return $query;
    }

    private function revenueSparkline(): array
    {
        $start = now()->startOfDay()->subDays(6);
        $query = Order::query()
            ->where('status', OrderStatus::Paid->value)
            ->where('paid_at', '>=', $start);

        if ($raffleId = $this->raffleId()) {
            $query->where('raffle_id', $raffleId);
        }

        $orders = $query->get(['paid_at', 'total_cents']);

        return collect(range(0, 6))->map(function (int $offset) use ($start, $orders): float {
            $date = $start->copy()->addDays($offset)->toDateString();
            $cents = (int) $orders
                ->filter(fn (Order $order): bool => $order->paid_at?->toDateString() === $date)
                ->sum('total_cents');

            return round($cents / 100, 2);
        })->all();
    }

    private function selectedRaffle(): ?Raffle
    {
        $id = $this->raffleId();
        return $id ? Raffle::query()->find($id) : null;
    }

    private function raffleId(): ?int
    {
        $value = $this->pageFilters['raffle_id'] ?? null;
        return filled($value) ? (int) $value : null;
    }

    private function periodStart(): ?Carbon
    {
        return match ((string) ($this->pageFilters['period'] ?? '30')) {
            'today' => now()->startOfDay(),
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            'all' => null,
            default => now()->subDays(29)->startOfDay(),
        };
    }
}
