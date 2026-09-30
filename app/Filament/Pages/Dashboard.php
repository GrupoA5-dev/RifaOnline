<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CampaignAnalyticsOverview;
use App\Filament\Widgets\CampaignPerformance;
use App\Filament\Widgets\RecentSales;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\SalesOverview;
use App\Models\Raffle;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard de vendas';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filtros do painel')
                ->schema([
                    Select::make('period')
                        ->label('Período')
                        ->options([
                            'today' => 'Hoje',
                            '7' => 'Últimos 7 dias',
                            '30' => 'Últimos 30 dias',
                            'all' => 'Todo o período',
                        ])
                        ->default('30')
                        ->live(),
                    Select::make('raffle_id')
                        ->label('Campanha')
                        ->placeholder('Todas as campanhas')
                        ->options(fn (): array => Raffle::query()->latest()->pluck('title', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->live(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            SalesOverview::class,
            CampaignAnalyticsOverview::class,
            RevenueChart::class,
            CampaignPerformance::class,
            RecentSales::class,
        ];
    }
}
