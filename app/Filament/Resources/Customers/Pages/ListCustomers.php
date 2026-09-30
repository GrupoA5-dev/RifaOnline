<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Exports\CustomersCsvExport;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCustomers')
                ->label('Exportar XLS')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => CustomersCsvExport::download()),
        ];
    }
}
