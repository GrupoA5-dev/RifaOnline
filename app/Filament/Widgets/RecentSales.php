<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Money;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSales extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Vendas confirmadas')
            ->query($this->salesQuery())
            ->columns([
                TextColumn::make('paid_at')->label('Pago em')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('id')->label('Pedido #')->sortable(),
                TextColumn::make('raffle.title')->label('Campanha')->searchable(),
                TextColumn::make('customer.name')->label('Cliente')->searchable()->placeholder('Participante'),
                TextColumn::make('quantity')->label('Números')->numeric()->sortable(),
                TextColumn::make('total_cents')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state): string => 'R$ '.Money::format((int) $state)),
            ])
            ->defaultSort('paid_at', 'desc')
            ->paginated([5, 10, 25]);
    }

    private function salesQuery(): Builder
    {
        $query = Order::query()
            ->with(['raffle:id,title', 'customer:id,name'])
            ->where('status', OrderStatus::Paid->value)
            ->whereNotNull('paid_at');

        if ($raffleId = $this->raffleId()) {
            $query->where('raffle_id', $raffleId);
        }

        if ($start = $this->periodStart()) {
            $query->where('paid_at', '>=', $start);
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
