<?php

namespace App\Filament\Resources\RaffleWinners\Pages;

use App\Filament\Resources\RaffleWinners\RaffleWinnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRaffleWinners extends ListRecords
{
    protected static string $resource = RaffleWinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Registrar ganhador')];
    }
}
