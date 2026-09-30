<?php

namespace App\Filament\Resources\RaffleWinners\Pages;

use App\Filament\Resources\RaffleWinners\RaffleWinnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRaffleWinner extends EditRecord
{
    protected static string $resource = RaffleWinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
