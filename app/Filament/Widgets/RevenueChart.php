<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Receita por dia';
    protected ?string $description = 'Valores de pedidos confirmados no período selecionado.';
    protected ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        [$start, $days] = $this->range();

        $query = Order::query()
            ->where('status', OrderStatus::Paid->value)
            ->whereNotNull('paid_at');

        if ($start) {
            $query->where('paid_at', '>=', $start);
        }

        if ($raffleId = $this->raffleId()) {
            $query->where('raffle_id', $raffleId);
        }

        $orders = $query->get(['paid_at', 'total_cents']);

        if ($days === null) {
            $first = $orders->min('paid_at');
            $start = $first ? Carbon::parse($first)->startOfDay() : now()->subDays(29)->startOfDay();
            $days = max(1, min(90, $start->diffInDays(now()->startOfDay()) + 1));
            if ($days === 90) {
                $start = now()->subDays(89)->startOfDay();
            }
        }

        $labels = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $cents = (int) $orders
                ->filter(fn (Order $order): bool => $order->paid_at?->toDateString() === $date->toDateString())
                ->sum('total_cents');
            $data[] = round($cents / 100, 2);
        }

        $primary = (string) SystemSetting::value('branding', 'primary_color', '#22c55e');

        return [
            'datasets' => [[
                'label' => 'Receita (R$)',
                'data' => $data,
                'borderColor' => $primary,
                'backgroundColor' => $primary.'33',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function range(): array
    {
        return match ((string) ($this->pageFilters['period'] ?? '30')) {
            'today' => [now()->startOfDay(), 1],
            '7' => [now()->subDays(6)->startOfDay(), 7],
            '30' => [now()->subDays(29)->startOfDay(), 30],
            'all' => [null, null],
            default => [now()->subDays(29)->startOfDay(), 30],
        };
    }

    private function raffleId(): ?int
    {
        $value = $this->pageFilters['raffle_id'] ?? null;
        return filled($value) ? (int) $value : null;
    }
}
