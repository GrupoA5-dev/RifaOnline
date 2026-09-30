<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Exports\PaymentsCsvExport;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPayments')
                ->label('Exportar XLS')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => PaymentsCsvExport::download()),
        ];
    }
}
