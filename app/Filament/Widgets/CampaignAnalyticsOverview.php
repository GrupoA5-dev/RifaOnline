<?php

namespace App\Filament\Widgets;

use App\Models\RaffleEvent;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignAnalyticsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $base = RaffleEvent::query();

        if ($raffleId = $this->raffleId()) {
            $base->where('raffle_id', $raffleId);
        }

        if ($start = $this->periodStart()) {
            $base->where('created_at', '>=', $start);
        }

        $views = (int) (clone $base)->where('event_type', 'view')->count();
        $clicks = (int) (clone $base)->where('event_type', '!=', 'view')->count();
        $campaignClicks = (int) (clone $base)->where('event_type', 'campaign_click')->count();
        $purchaseClicks = (int) (clone $base)->where('event_type', 'purchase_click')->count();
        $ctr = $views > 0 ? round(($clicks / $views) * 100, 1) : 0;

        return [
            Stat::make('Acessos às campanhas', number_format($views, 0, ',', '.'))
                ->description('Visualizações únicas aproximadas por sessão/30 min')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
            Stat::make('Cliques / interações', number_format($clicks, 0, ',', '.'))
                ->description(number_format($campaignClicks, 0, ',', '.').' clique(s) nos cards')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('primary'),
            Stat::make('CTR de interação', number_format($ctr, 1, ',', '.').'%')
                ->description('Cliques divididos por acessos')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($ctr >= 10 ? 'success' : 'gray'),
            Stat::make('Cliques em participar', number_format($purchaseClicks, 0, ',', '.'))
                ->description('Aberturas do checkout da campanha')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),
        ];
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
