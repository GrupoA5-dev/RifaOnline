<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\TicketAllocationStatus;
use App\Filament\Resources\Raffles\RaffleResource;
use App\Models\Order;
use App\Models\Raffle;
use App\Support\Money;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CampaignPerformance extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Desempenho das campanhas')
            ->query($this->campaignQuery())
            ->columns([
                TextColumn::make('title')
                    ->label('Campanha')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Raffle $record): string => RaffleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('access_count')
                    ->label('Acessos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('click_count')
                    ->label('Cliques')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ctr')
                    ->label('CTR')
                    ->state(function (Raffle $record): string {
                        $views = max(0, (int) ($record->getAttribute('access_count') ?? 0));
                        $clicks = max(0, (int) ($record->getAttribute('click_count') ?? 0));
                        return $views > 0 ? number_format(($clicks / $views) * 100, 1, ',', '.').'%' : '0,0%';
                    }),
                TextColumn::make('paid_numbers')
                    ->label('Vendidos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('paid_revenue_cents')
                    ->label('Receita')
                    ->state(fn (Raffle $record): string => 'R$ '.Money::format((int) ($record->getAttribute('paid_revenue_cents') ?? 0))),
                TextColumn::make('sales_goal')
                    ->label('Meta números')
                    ->state(fn (Raffle $record): string => number_format((int) ($record->sales_goal_numbers ?: $record->total_numbers), 0, ',', '.')),
                TextColumn::make('sales_progress')
                    ->label('Progresso')
                    ->state(function (Raffle $record): string {
                        $goal = max(1, (int) ($record->sales_goal_numbers ?: $record->total_numbers));
                        $paid = (int) ($record->getAttribute('paid_numbers') ?? 0);
                        return min(100, round(($paid / $goal) * 100, 1)).'%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => ((float) rtrim($state, '%')) >= 100 ? 'success' : 'primary'),
                TextColumn::make('revenue_goal_progress')
                    ->label('Meta receita')
                    ->state(function (Raffle $record): string {
                        $goal = (int) ($record->revenue_goal_cents ?? 0);
                        if ($goal <= 0) {
                            return 'Não definida';
                        }
                        $revenue = (int) ($record->getAttribute('paid_revenue_cents') ?? 0);
                        return min(100, round(($revenue / $goal) * 100, 1)).'%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Não definida' ? 'gray' : (((float) rtrim($state, '%')) >= 100 ? 'success' : 'primary')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }

    private function campaignQuery(): Builder
    {
        $start = $this->periodStart();

        $query = Raffle::query()
            ->withCount([
                'allocations as paid_numbers' => fn (Builder $query) => $query->where('status', TicketAllocationStatus::Paid->value),
                'events as access_count' => fn (Builder $query) => $query
                    ->where('event_type', 'view')
                    ->when($start, fn (Builder $query, Carbon $start) => $query->where('created_at', '>=', $start)),
                'events as click_count' => fn (Builder $query) => $query
                    ->where('event_type', '!=', 'view')
                    ->when($start, fn (Builder $query, Carbon $start) => $query->where('created_at', '>=', $start)),
            ])
            ->addSelect([
                'paid_revenue_cents' => Order::query()
                    ->selectRaw('COALESCE(SUM(total_cents), 0)')
                    ->whereColumn('raffle_id', 'raffles.id')
                    ->where('status', OrderStatus::Paid->value),
            ]);

        if ($raffleId = $this->raffleId()) {
            $query->whereKey($raffleId);
        }

        return $query;
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
