<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Exports\OrdersCsvExport;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportOrders')
                ->label('Exportar XLS')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => OrdersCsvExport::download()),
        ];
    }
}
